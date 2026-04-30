<?php
$GLOBALS['current_dashboard'] = 'speechapp';
include('/opt/mka/bootstrap.php');
include('/opt/mka/dashboards/_init.php');
error_log(json_encode($_SESSION));
include('_menu_loader.php');
$pdo = $GLOBALS['pdo'];


$successMedia = null;

// If in assignment mode, check for assignment-specific media
if (isset($_SESSION['current_assignment_id'])) {
    $assignmentId = $_SESSION['current_assignment_id'];

    $stmt = $pdo->prepare("
        SELECT m.media_id, m.media_type, m.media_name, 
               m.media_path, m.allow_sound, m.autoplay_loop,
               asm.selection_type
        FROM assignment_success_media asm
        INNER JOIN exercise_success_media m ON asm.media_id = m.media_id
        WHERE asm.assignment_group_id = ? AND m.is_active = 1
        ORDER BY asm.display_order ASC
        LIMIT 1
    ");
    $stmt->execute([$assignmentId]);
    $successMedia = $stmt->fetch(PDO::FETCH_ASSOC);
}

// If no assignment media, check for user's default OR their parent's default
if (!$successMedia && isset($_SESSION['user_data']['user_uuid'])) {
    $userUuid = $_SESSION['user_data']['user_uuid'];
    $userType = $_SESSION['user_data']['user_type'] ?? '';

    if ($userType === 'end_user') {
        // For end users, check their parent's (SLP's) default media
        $stmt = $pdo->prepare("
            SELECT m.media_id, m.media_type, m.media_name, 
                   m.media_path, m.allow_sound, m.autoplay_loop
            FROM mka_users u
            INNER JOIN user_default_success_media d ON u.parent_user_uuid = d.user_uuid
            INNER JOIN exercise_success_media m ON d.media_id = m.media_id
            WHERE u.UserUUID = ? AND m.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$userUuid]);
        $successMedia = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // For SLPs/admins, check their own default
        $stmt = $pdo->prepare("
            SELECT m.media_id, m.media_type, m.media_name, 
                   m.media_path, m.allow_sound, m.autoplay_loop
            FROM user_default_success_media d
            INNER JOIN exercise_success_media m ON d.media_id = m.media_id
            WHERE d.user_uuid = ? AND m.is_active = 1
        ");
        $stmt->execute([$userUuid]);
        $successMedia = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Debug logging
error_log("=== SUCCESS MEDIA DEBUG ===");
error_log("User type: " . ($userType ?? 'UNKNOWN'));
error_log("successMedia result: " . print_r($successMedia, true));
error_log("===========================");

// Load video tutorial slot assignments
$videoSlotMap = [];
try {
    $vstmt = $pdo->query("
        SELECT slot_key, title, video_path, source_type
        FROM instruction_videos
        WHERE is_active = 1 AND slot_key IS NOT NULL
    ");
    foreach ($vstmt->fetchAll(PDO::FETCH_ASSOC) as $vrow) {
        $videoSlotMap[$vrow['slot_key']] = $vrow;
    }
} catch (Exception $e) {
    error_log('video slot map error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpeechApp - Crossroads Therapy Clinic</title>

    <link rel="shortcut icon"href="/dashboards/img/favicon.ico">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <link href="plugins/gritter/css/jquery.gritter.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/animate/css/animate.min.css" rel="stylesheet">
    <link href="css/style.min.css?v=<?= ASSET_VER ?>" rel="stylesheet" type="text/css">


    <link href="plugins/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="plugins/datatables/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <script src="plugins/jquery/js/jquery.min.js"></script>





    <style>
        /* --- Exercise UI (minimal, theme-friendly) --- */
        .category-btn { padding: 10px 14px; font-weight: 600; }
        #exercise-panel-title { transition: color .2s, font-size .2s; }
        #exercise-panel-title.panel-active { font-size: 1.35rem; color: #198754; font-weight: 700; }
        .slide-panel {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 12px;
            transition: max-height .3s ease;
            border: none;
            background: transparent;
            padding: 0 12px;
            margin-top: .5rem;
            border-radius: .5rem;
            display: none;
        }

        /* Expanded state — restore your border + background */
        .slide-panel.open {
            max-height: 360px;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            display: block;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            margin: 6px;
            font-weight: 600;
        }
        .pill:hover { background: #f1f5f9; }

        .controls .nav-btn {
            border:1px solid #d1d5db;
            background:#fff;
            border-radius:.5rem;
            padding:10px 14px;
            display:inline-flex;
            align-items:center;
            gap:6px;
            cursor:pointer;
        }
        .controls .nav-btn:hover { background:#f8fafc; }

        /* Success button */
        .success-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight:700;
        }
        .success-btn:disabled {
            background-color: #c6c6c6 !important;
            color: #7a7a7a !important;
            cursor: not-allowed;
            opacity: 1 !important;
        }
        .success-btn:not(:disabled):hover {
            background-color: #218838;
        }

        .ghost { opacity:.4; pointer-events:none; }

        /* ===========================
           CARD GRID + CARD LAYOUT
           =========================== */

        /* Grid container for multiple exercise cards */
        #exercise-view {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-items: flex-start;
        }

        /* One flex item per card (content box + button) */
        .exercise-card {
            flex: 1 1 0;
            max-width: none;
            min-width: 0;
            display: flex;
            flex-direction: column;          /* box on top, button under */
            align-items: stretch;
        }

        /* Inner bordered box */
        .exercise-box {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            min-height: 200px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Area that holds the images/text */
        .exercise-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* When a card is completed, hide its inner content but keep the box */
        .exercise-card.completed .exercise-content {
            visibility: hidden;
        }

        /* Change Card button under each box */
        .change-card-btn {
            margin-top: 6px;
            width: 100%;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            background-color: #28a745;
            color: #fff;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .change-card-btn:hover {
            background-color: #218838;
        }
        .syllable-btn {
            background-color: #007bff;
        }
        .syllable-btn:hover {
            background-color: #0069d9;
        }
        .blend-btn {
            background-color: #007bff;
        }
        .blend-btn:hover {
            background-color: #0069d9;
        }

        /* Top image */
        .exercise-main-img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }

        /* Icon underneath */
        .exercise-icon-img {
            display: block;
            margin: 8px auto 0;
            max-width: 100%;
        }

        .exercise-card.selected {
            outline: 2px solid #007bff;
        }

        .cv-bottom-row {
            margin-top: 8px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .cv-bottom-row img {
            max-width: 100px;
            height: auto;
        }

        .word-bottom-row {
            margin-top: 8px;
            display: flex;
            justify-content: center;
            align-items: baseline;
            gap: 4px;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .word-part {
            text-transform: lowercase;
        }

        .word-plus {
            opacity: 0.7;
        }

        /* Cards always share the row equally — no responsive overrides needed */

        /* Force every card’s bordered box to be the same height */
        .exercise-box{
            height: 220px;              /* tweak this number */
            min-height: 220px;          /* keep consistent even if overridden elsewhere */
            overflow: hidden;
        }

        /* Keep content centered */
        .exercise-content{
            height: 100%;
        }

        /* Constrain images so they can't make the box taller */
        .exercise-main-img{
            max-height: 140px;          /* tweak */
            width: 100%;
            object-fit: contain;
        }

        .exercise-icon-img{
            max-height: 60px;           /* tweak */
            width: 100%;
            object-fit: contain;
        }

        /* Constrain the bottom row images too (CV mix area) */
        .cv-bottom-row img{
            max-height: 60px;           /* tweak */
            width: auto;
            object-fit: contain;
        }

        .cv-parts-text{
            font-size: 34px;
            font-weight: 900;
            padding: 6px 10px;
            border-radius: 10px;
            display: inline-block;
        }

        .cv-whole-text{
            font-size: 34px;
            font-weight: 900;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .pill-section-header{
            font-weight: 800;
            margin: 12px 0 6px;
            opacity: .85;
        }

        .slide-panel{
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 12px;
        }

        #exercise-view.vertical-layout {
            flex-direction: column;
            align-items: center;
        }

        #exercise-view.vertical-layout .exercise-card {
            flex: 0 0 auto;
            max-width: 675px;
            width: 100%;
        }

        /* === VERTICAL layout: 2.25× sizing === */
        #exercise-view.vertical-layout .exercise-box {
            height: 495px;
            min-height: 495px;
        }
        #exercise-view.vertical-layout .exercise-main-img {
            max-height: 315px;
        }
        #exercise-view.vertical-layout .exercise-icon-img {
            max-height: 135px;
        }
        #exercise-view.vertical-layout .cv-parts-text {
            font-size: 76px;
        }
        #exercise-view.vertical-layout .cv-whole-text {
            font-size: 76px;
        }
        #exercise-view.vertical-layout .cv-bottom-row img {
            max-height: 135px;
        }

        /* === HORIZONTAL layout: cards stretch equally, images fill card width === */
        #exercise-view:not(.vertical-layout) .exercise-card {
            flex: 1 1 0;
            max-width: none;
            min-width: 0;
        }
        #exercise-view:not(.vertical-layout) .exercise-box {
            height: auto;
            min-height: 0;
            overflow: visible;
        }
        #exercise-view:not(.vertical-layout) .exercise-main-img {
            max-height: none;
            width: 100%;
            height: auto;
        }
        #exercise-view:not(.vertical-layout) .exercise-icon-img {
            max-height: none;
            width: 100%;
            height: auto;
        }

        .btn-purple {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: white;
        }

        .btn-purple:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
            color: white;
        }

        /* Assignment modal specific styles */
        #exerciseList .list-group-item {
            cursor: move;
        }

        #exerciseList .list-group-item:hover {
            background-color: #f8f9fa;
        }

        #modalExercisePreview .exercise-card {
            flex: 0 0 calc(33.33% - 8px);
            max-width: calc(33.33% - 8px);
        }

        #modalExercisePreview .exercise-box {
            height: 180px;
            min-height: 180px;
        }

        #modalExercisePreview .exercise-main-img {
            max-height: 100px;
        }

        #modalExercisePreview .exercise-icon-img {
            max-height: 50px;
        }

        #modalExercisePreview.vertical-layout {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #modalExercisePreview.vertical-layout .exercise-card {
            flex: 0 0 auto;
            max-width: 250px;
            width: 100%;
            margin-bottom: 8px;
        }

        .exercise-list-item-handle {
            cursor: move;
            color: #999;
        }

        .exercise-list-item-handle:hover {
            color: #333;
        }

        .white-badge-text {
            color: #ffffff;
        }

        /* Sliding Alert Banner */
        .alert-slide-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            transform: translateY(-100%);
            transition: transform 0.4s ease-in-out;
        }

        .alert-slide-container.show {
            transform: translateY(0);
        }

        .alert-slide-container .alert {
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .pill {
            transition: transform 0.1s ease;
        }

        .pill:active {
            transform: scale(0.95);
        }

    </style>

</head>

<body>
<!-- Sliding Alert Banner -->
<div id="slideAlert" class="alert-slide-container" style="display: none;">
    <div class="alert mb-0" id="slideAlertContent" role="alert">
        <button type="button" class="btn-close float-end" onclick="hideSlideAlert()"></button>
        <strong id="slideAlertTitle"></strong>
        <span id="slideAlertMessage"></span>
    </div>
</div>

<!-- Sliding Confirm Banner -->
<div id="slideConfirm" class="alert-slide-container" style="display: none;">
    <div class="alert alert-warning mb-0" id="slideConfirmContent" role="alert">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong id="slideConfirmTitle"></strong>
                <span id="slideConfirmMessage"></span>
            </div>
            <div class="ms-3">
                <button type="button" class="btn btn-sm btn-danger me-2" id="slideConfirmYes">Yes</button>
                <button type="button" class="btn btn-sm btn-secondary" id="slideConfirmNo">No</button>
            </div>
        </div>
    </div>
</div>


<div id="wrapper">

    <?=$menu?>

    <div id="page-wrapper" class="gray-bg dashbard-1">

        <?=$topbar?>

        <!-- Header band (keep your existing message) -->
        <div class="row border-bottom white-bg dashboard-header">
            <div class="col-xl-6">
                <h1>Visual Speech Practice Guide</h1>
                <span class="text-muted">Click on Exercises Below To Get Started</span>
            </div>
        </div>

        <!-- ===== Exercise UI goes here ===== -->
        <div class="wrapper wrapper-content animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5 id="exercise-panel-title">Exercises Vowels, Consonants, Syllables</h5>
                        </div>
                        <div class="ibox-content">

                            <!-- Top category buttons -->
                            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                                <div class="btn-group flex-wrap" role="group">
                                    <button class="btn btn-primary category-btn" data-panel="panel-letters">Sounds in Isolation</button>
                                    <button class="btn btn-primary category-btn" data-panel="panel-cv">CV</button>
                                    <button class="btn btn-primary category-btn" data-panel="panel-3cv">CV Blending</button>
                                </div>

                                <div class="btn-group flex-wrap" role="group">
                                    <button class="btn btn-info category-btn" data-panel="panel-soundmixing">Syllable Shifts</button>
                                    <button class="btn btn-info category-btn" data-panel="panel-wordsyllable">Words</button>
                                    <button class="btn btn-info category-btn" data-panel="panel-syllables">CV-CV Blending</button>
                                </div>

                                <div class="btn-group flex-wrap" role="group">
                                    <button class="btn btn-purple category-btn" data-panel="panel-assignments">Assignments</button>
                                </div>

                                <div class="ms-auto d-flex align-items-center">
                                    <label for="exerciseCountSelect" class="me-2 mb-0 small text-muted">
                                        # Exercises
                                    </label>
                                    <select id="exerciseCountSelect" class="form-select form-select-sm" style="width: auto;">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3" selected>3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Slide-down selection panels -->
                            <div id="panel-letters" class="slide-panel">
                                <div id="lettersounds-list"></div>
                            </div>
                            <div id="panel-cv" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="cv-list"></div>
                            </div>
                            <div id="panel-3cv" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="3cv-list"></div>
                            </div>
                            <div id="panel-soundmixing" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="soundmixing-list"></div>
                            </div>
                            <div id="panel-wordsyllable" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="wordsyllable-list"></div>
                            </div>
                            <div id="panel-syllables" class="slide-panel">
                                <div class="d-flex flex-wrap py-2" id="syllables-list"></div>
                            </div>

                            <!-- Assignments panel -->
                            <div id="panel-assignments" class="slide-panel">
                                <?php
                                if ($_SESSION['user_data']['user_type'] == 'enterprise_admin' || $_SESSION['user_data']['user_type'] == 'super_user') {


                                ?>
                                <div class="mb-3">
                                    <button class="btn btn-sm btn-success" id="btnCreateAssignment">
                                        <i class="fa fa-plus"></i> Create Assignment
                                    </button>
                                </div>
                                <?php
                                }
                                ?>
                                <div id="assignments-table-wrapper">
                                    <table id="assignmentsTable" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Date Added</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- DataTables will populate this -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Video button ABOVE main panel (full width) -->
                            <div class="mt-3" style="display:none;">
                                <button class="btn btn-outline-primary w-100" id="btnVideo">
                                    <i class="fa fa-play-circle me-1"></i> Video Example
                                </button>
                                <div class="mt-2 small text-muted">
                                    Watch a quick demo of how to perform this exercise.
                                </div>
                            </div>

                            <!-- Main exercise panel FULL WIDTH -->
                            <div class="row mt-3 g-3">
                                <div class="col-12">
                                    <div class="exercise-frame">
                                        <div id="exercise-view" class="exercise-grid">
                                            <!-- JS will inject 6 .exercise-card elements here -->
                                            <div class="exercise-placeholder text-muted">
                                                Pick an exercise above
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic choices below -->
                            <div class="mt-4">
                                <h6 class="mb-2" id="choices-title" style="display:none;"></h6>
                                <div id="choices-wrap" class="d-flex flex-wrap"></div>
                            </div>

                            <!-- Controls -->
                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <div class="controls">
                                  <!--  <button class="nav-btn" id="btnBack" title="Previous">
                                        &#x25C0; <span>Back</span>
                                    </button>
                                    <button class="nav-btn" id="btnNext" title="Next">
                                        <span>Next</span> &#x25B6;
                                    </button> -->
                                </div>
                                <!-- Start disabled -->
                                <button class="success-btn" id="btnSuccess" disabled>
                                    <i class="fa fa-check-circle me-1"></i> Success
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===== /Exercise UI ===== -->

        <div class="footer"></div>

    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoTitle">Video Example</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="videoPlayerWrap" class="ratio ratio-16x9 border rounded"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <h4 class="mb-2">Nice job!</h4>
            <img src="img/success.jpg" onerror="this.style.display='none';" class="img-fluid mb-2" alt="Success">
            <p class="text-muted mb-3">You completed this exercise.</p>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<!-- Reusable Alert/Confirm Modal -->
<div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="alertModalHeader">
                <h5 class="modal-title" id="alertModalTitle">Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="alertModalBody">
                <!-- Message goes here -->
            </div>
            <div class="modal-footer" id="alertModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="alertModalCancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="alertModalOkBtn">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Creation/Edit Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignmentModalTitle">Create Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- LEFT SIDE: Assignment Info & Exercise List -->
                    <div class="col-md-5">
                        <!-- Assignment Details -->
                        <div class="mb-3">
                            <label for="assignmentName" class="form-label">Assignment Name</label>
                            <input type="text" class="form-control" id="assignmentName" required>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="assignmentDescription" rows="2"></textarea>
                        </div>

                        <!-- User Assignment (for super_user/enterprise_admin only) -->
                        <!-- User Assignment (for super_user/enterprise_admin only) -->
                        <div class="mb-3" id="assignmentUsersWrapper" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Assign To Users</label>
                                <button type="button" class="btn btn-sm btn-warning" id="btnUnassignAll" style="display: none;">
                                    <i class="fa fa-user-times"></i> Unassign All
                                </button>
                            </div>

                            <!-- Currently Assigned Users -->
                            <div id="currentlyAssignedUsers" class="mb-2" style="display: none;">
                                <small class="text-muted d-block mb-1">Currently Assigned:</small>
                                <div id="assignedUsersList" class="d-flex flex-wrap gap-1 mb-2">
                                    <!-- Populated by JS -->
                                </div>
                            </div>

                            <select class="form-select" id="assignmentUsers" multiple size="5">
                                <!-- Populated by JS -->
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users to add</small>
                        </div>

                        <hr>

                        <!-- Exercise List -->
                        <h6>Exercises in Assignment</h6>
                        <div id="exerciseList" class="list-group mb-3" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-muted text-center py-3" id="exerciseListEmpty">
                                No exercises added yet
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT SIDE: Exercise Builder -->
                    <div class="col-md-7">
                        <h6>Build Exercise</h6>

                        <!-- Exercise Name -->
                        <div class="mb-3">
                            <label for="exerciseName" class="form-label">Exercise Name</label>
                            <input type="text" class="form-control" id="exerciseName" placeholder="e.g., Consonant Practice">
                        </div>

                        <!-- Exercise Controls -->
                        <div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
                            <div class="btn-group flex-wrap" role="group">
                                <button class="btn btn-sm btn-primary modal-category-btn" data-type="letters">Sounds in Isolation</button>
                                <button class="btn btn-sm btn-primary modal-category-btn" data-type="cv">CV</button>
                                <button class="btn btn-sm btn-primary modal-category-btn" data-type="3cv">CV Blending</button>
                            </div>

                            <div class="btn-group flex-wrap" role="group">
                                <button class="btn btn-sm btn-info modal-category-btn" data-type="soundmixing">Syllable Shifts</button>
                                <button class="btn btn-sm btn-info modal-category-btn" data-type="wordsyllable">Word/Syllable</button>
                            </div>

                            <select id="modalCardCount" class="form-select form-select-sm" style="width: auto;">
                                <option value="1">1 Card</option>
                                <option value="2">2 Cards</option>
                                <option value="3" selected>3 Cards</option>
                                <option value="4">4 Cards</option>
                                <option value="5">5 Cards</option>
                                <option value="6">6 Cards</option>
                            </select>

                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="modalOrientation" id="modalOrientHorizontal" value="horizontal" checked>
                                <label class="btn btn-sm btn-outline-secondary" for="modalOrientHorizontal">
                                    <i class="fa fa-arrows-alt-h"></i> Horizontal
                                </label>
                                <input type="radio" class="btn-check" name="modalOrientation" id="modalOrientVertical" value="vertical">
                                <label class="btn btn-sm btn-outline-secondary" for="modalOrientVertical">
                                    <i class="fa fa-arrows-alt-v"></i> Vertical
                                </label>
                            </div>
                        </div>

                        <!-- Selection Panel (mimics main page) -->
                        <div id="modalSelectionPanel" class="border rounded p-2 mb-3" style="max-height: 150px; overflow-y: auto; display: none;">
                            <!-- Pills will be injected here -->
                        </div>

                        <!-- Card Preview Area -->
                        <div id="modalExercisePreview" class="border rounded p-3 mb-3" style="min-height: 250px; background: #f8f9fa;">
                            <div class="text-muted text-center py-5">
                                Select a category and build your exercise
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" id="btnSaveExercise" disabled>
                                <i class="fa fa-check"></i> Save Exercise to Assignment
                            </button>
                            <button class="btn btn-secondary" id="btnClearExercise">
                                <i class="fa fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveAssignment">
                    <i class="fa fa-save"></i> Save Assignment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Your existing JS bundle stack -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/metismenu/js/metisMenu.min.js"></script>
<script src="plugins/pace-js/js/pace.min.js"></script>
<script src="plugins/wow.js/js/wow.min.js"></script>
<script src="plugins/lucide/js/lucide.min.js"></script>
<script src="plugins/simplebar/js/simplebar.min.js"></script>
<script src="js/inspinia.js?v=<?= ASSET_VER ?>"></script>
<script src="plugins/flot/js/jquery.flot.js"></script>
<script src="plugins/jquery-flot-tooltip/js/jquery.flot.tooltip.min.js"></script>
<script src="plugins/flot-spline/js/jquery.flot.spline.js"></script>
<script src="plugins/jquery-flot-resize/js/index.js"></script>
<script src="plugins/peity/js/jquery.peity.min.js"></script>
<script src="js/demo/peity-demo.js"></script>
<script src="plugins/jquery-ui/js/jquery-ui.min.js"></script>
<script src="plugins/gritter/js/jquery.gritter.js"></script>
<script src="plugins/jquery-sparkline/js/jquery.sparkline.min.js"></script>
<script src="js/demo/sparkline-demo.js"></script>
<script src="plugins/chartjs/js/Chart.min.js"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>

<script src="plugins/datatables/js/dataTables.min.js"></script>

<script>
    /* ========================================
   REUSABLE MODAL ALERT/CONFIRM SYSTEM
   ======================================== */

    let modalAlertResolve = null;
    let slideAlertTimeout = null;
    let confirmResolveFunction = null;

    function showAlert(message, title = '', type = 'info', duration = 5000) {
        const container = document.getElementById('slideAlert');
        const alertContent = document.getElementById('slideAlertContent');
        const alertTitle = document.getElementById('slideAlertTitle');
        const alertMessage = document.getElementById('slideAlertMessage');

        if (!container) return;

        if (slideAlertTimeout) {
            clearTimeout(slideAlertTimeout);
        }

        alertContent.className = 'alert mb-0';
        switch(type) {
            case 'success':
                alertContent.classList.add('alert-success');
                break;
            case 'warning':
                alertContent.classList.add('alert-warning');
                break;
            case 'danger':
                alertContent.classList.add('alert-danger');
                break;
            default:
                alertContent.classList.add('alert-info');
        }

        alertTitle.textContent = title ? title + ': ' : '';
        alertMessage.textContent = message;

        container.style.display = 'block';
        container.offsetHeight;
        container.classList.add('show');

        slideAlertTimeout = setTimeout(() => {
            hideSlideAlert();
        }, duration);
    }

    function hideSlideAlert() {
        const container = document.getElementById('slideAlert');
        if (!container) return;

        container.classList.remove('show');

        setTimeout(() => {
            container.style.display = 'none';
        }, 400);
    }

    function showConfirm(message, title = 'Confirm', yesText = 'Yes', noText = 'No', type = 'warning') {
        return new Promise((resolve) => {
            const container = document.getElementById('slideConfirm');
            const confirmContent = document.getElementById('slideConfirmContent');
            const confirmTitle = document.getElementById('slideConfirmTitle');
            const confirmMessage = document.getElementById('slideConfirmMessage');
            const yesBtn = document.getElementById('slideConfirmYes');
            const noBtn = document.getElementById('slideConfirmNo');

            if (!container) {
                resolve(confirm(message));
                return;
            }

            confirmResolveFunction = resolve;

            confirmContent.className = 'alert mb-0';
            switch(type) {
                case 'danger':
                    confirmContent.classList.add('alert-danger');
                    yesBtn.className = 'btn btn-sm btn-danger me-2';
                    break;
                case 'success':
                    confirmContent.classList.add('alert-success');
                    yesBtn.className = 'btn btn-sm btn-success me-2';
                    break;
                case 'info':
                    confirmContent.classList.add('alert-info');
                    yesBtn.className = 'btn btn-sm btn-primary me-2';
                    break;
                default:
                    confirmContent.classList.add('alert-warning');
                    yesBtn.className = 'btn btn-sm btn-warning me-2';
            }

            confirmTitle.textContent = title ? title + ': ' : '';
            confirmMessage.textContent = message;
            yesBtn.textContent = yesText;
            noBtn.textContent = noText;

            const newYesBtn = yesBtn.cloneNode(true);
            const newNoBtn = noBtn.cloneNode(true);
            yesBtn.parentNode.replaceChild(newYesBtn, yesBtn);
            noBtn.parentNode.replaceChild(newNoBtn, noBtn);

            document.getElementById('slideConfirmYes').addEventListener('click', () => {
                hideSlideConfirm(true);
            });

            document.getElementById('slideConfirmNo').addEventListener('click', () => {
                hideSlideConfirm(false);
            });

            container.style.display = 'block';
            container.offsetHeight;
            container.classList.add('show');
        });
    }

    function hideSlideConfirm(result) {
        const container = document.getElementById('slideConfirm');
        if (!container) return;

        container.classList.remove('show');

        setTimeout(() => {
            container.style.display = 'none';

            if (confirmResolveFunction) {
                confirmResolveFunction(result);
                confirmResolveFunction = null;
            }
        }, 400);
    }

    /* -------- Variables -------- */
    let soundMixAssignments = [];
    let currentExerciseItem = null;
    let wordAssignments = [];
    let standardAssignments = [];
    let cardAssignments = [];
    let selectedCardIndex = null;
    let cardSelectEnabled = true;

    let isSoundMixingMode = false;
    let isWordSyllableMode = false;
    let isCVBlendingMode = false;
    let cvBlendingSource = null; // 'soundmixing' when entered from Syllable Shifts
    let savedSoundMixAssignments = null; // snapshot saved before navigating to CV Blending

    let CONSONANTS = [];
    let VOWELS = [];
    let WORDS = [];
    let CV_BLEND_ITEMS = [];
    let BLEND_3CV_ITEMS = [];
    let CONSONANT_IMAGES = {};
    let VOWEL_IMAGES = {};
    let CV_IMAGES = {};
    let WORD_IMAGES = {};
    let CONSONANT_FOLDER_MAP = {};
    let VOWEL_FOLDER_MAP = {};
    let CV_FOLDER_MAP = {};
    let WORD_FOLDER_MAP = {};
    let WORD_SYLLABLE_MAP = {};
    let SECTION_GROUPS = {};  // parent_type → [{name, cards:[]}]

    // SUCCESS MEDIA VARIABLES - DECLARE FIRST
    let currentMedia = [];
    let selectedSuccessMedia = null;

    // Load success media from PHP
    <?php if ($successMedia): ?>
    selectedSuccessMedia = <?php echo json_encode($successMedia); ?>;
    console.log('Success media loaded from server:', selectedSuccessMedia);
    <?php endif; ?>

    // Play success media function
    function playSuccessMedia() {
        if (!selectedSuccessMedia) return;

        const isVideo = selectedSuccessMedia.media_type === 'video';

        const mediaModal = document.createElement('div');
        mediaModal.className = 'modal fade';

        const closeBtn = `<div class="text-center mt-2">
            <button type="button" data-bs-dismiss="modal"
                style="background:rgba(0,0,0,0.6); color:#fff; border:none;
                       border-radius:20px; padding:6px 22px; font-size:1rem;
                       cursor:pointer;"
                aria-label="Close">Close</button>
        </div>`;

        if (isVideo) {
            mediaModal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body p-0">
                            <video
                                ${selectedSuccessMedia.autoplay_loop ? 'loop' : ''}
                                ${selectedSuccessMedia.allow_sound ? '' : 'muted'}
                                autoplay
                                style="width: 100%; border-radius: 8px; display:block;"
                                onended="this.closest('.modal').querySelector('[data-bs-dismiss]').click()"
                            >
                                <source src="${selectedSuccessMedia.media_path}" type="video/mp4">
                            </video>
                            ${closeBtn}
                        </div>
                    </div>
                </div>
            `;
        } else {
            mediaModal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body p-0 text-center">
                            <img src="${selectedSuccessMedia.media_path}"
                                 style="max-width: 100%; max-height: 75vh; border-radius: 8px; display:block; margin:0 auto;"
                                 alt="${selectedSuccessMedia.media_name}">
                            ${closeBtn}
                        </div>
                    </div>
                </div>
            `;
        }

        document.body.appendChild(mediaModal);
        const modal = new bootstrap.Modal(mediaModal);
        modal.show();

        if (!isVideo) {
            setTimeout(() => {
                modal.hide();
            }, 3000);
        }

        mediaModal.addEventListener('hidden.bs.modal', function() {
            mediaModal.remove();
            resetCardsAfterSuccess();
        });
    }

    // Load content from database
    async function loadContent() {
        console.log('Starting loadContent...');
        try {
            const consResp = await fetch('/dashboards/api/admin/get_content.php?type=consonant');
            const consData = await consResp.json();
            if (consData.status === 'success') {
                CONSONANTS = consData.data.map(c => c.code);
                consData.data.forEach(c => {
                    if (c.image_path) CONSONANT_IMAGES[c.code] = c.image_path;
                    CONSONANT_FOLDER_MAP[c.code] = c.groups ? c.groups.split('|||').filter(Boolean) : null;
                });
            }

            const vowResp = await fetch('/dashboards/api/admin/get_content.php?type=vowel');
            const vowData = await vowResp.json();
            if (vowData.status === 'success') {
                VOWELS = vowData.data.map(v => ({
                    code: v.code,
                    label: v.label
                }));
                vowData.data.forEach(v => {
                    if (v.image_path) VOWEL_IMAGES[v.code] = v.image_path;
                    VOWEL_FOLDER_MAP[v.code] = v.groups ? v.groups.split('|||').filter(Boolean) : null;
                });
            }

            const wordResp = await fetch('/dashboards/api/admin/get_content.php?type=word');
            const wordData = await wordResp.json();
            if (wordData.status === 'success') {
                WORDS = wordData.data.map(w => w.word_text);
                wordData.data.forEach(w => {
                    if (w.image_path) WORD_IMAGES[w.word_text.toLowerCase()] = w.image_path;
                    WORD_FOLDER_MAP[w.word_text] = w.groups ? w.groups.split('|||').filter(Boolean) : null;
                    if (w.syllable_breakdown) WORD_SYLLABLE_MAP[w.word_text] = w.syllable_breakdown;
                });
            }

            const cvResp = await fetch('/dashboards/api/admin/get_content.php?type=cv_blend');
            const cvData = await cvResp.json();
            if (cvData.status === 'success') {
                CV_BLEND_ITEMS = cvData.data.map(cv => {
                    const [c, v] = parseCVCode(cv.cv_code);
                    const key = `${c}-${v}`;
                    if (cv.icon_path) CV_IMAGES[key] = cv.icon_path;
                    CV_FOLDER_MAP[key] = cv.groups ? cv.groups.split('|||').filter(Boolean) : null;
                    return { c, v };
                });
            }

            const cv3Resp = await fetch('/dashboards/api/admin/get_content.php?type=3cv_blend');
            const cv3Data = await cv3Resp.json();
            if (cv3Data.status === 'success') {
                BLEND_3CV_ITEMS = cv3Data.data.map(b => ({
                    c: b.consonant_code,
                    v: b.vowel_code
                }));
            }

            // Load cross-type group data for all sections
            await Promise.allSettled(
                ['consonant','vowel','cv_blend','cv','cv_blending','syllable_shifts','3cv_blend','word'].map(pt =>
                    fetch(`/dashboards/api/admin/get_section_groups.php?parent_type=${pt}`)
                        .then(r => r.json())
                        .then(d => { if (d.status === 'success') SECTION_GROUPS[pt] = d.groups; })
                )
            );

            buildLists();
            initExerciseGrid();

        } catch (error) {
            console.error('Error loading content:', error);
        }
    }

    let SEQUENCE = [];
    const IMG_BASE = "/assets/portal/exercises/images/";
    const ASSET_VER = "<?= max(
        filemtime('/opt/mka/public/assets/portal/exercises/images/generated_images_consonants'),
        filemtime('/opt/mka/public/assets/portal/exercises/images/generated_images_cv'),
        filemtime('/opt/mka/public/assets/portal/exercises/images/generated_images_vowels')
    ) ?>";

    // Maps cons_vowelCode → filename stem in generated_images_cv/00_*.png
    // Keys match the DB cv_code format: CONS-VOWEL with - replaced by _
    const CV_SOUND_MAP = {
        'B_AH':'Bah','B_EE':'Bee','B_EH':'Beh','B_IH':'Bih','B_O':'Bo','B_OO':'Boo','B_UH':'Buh',
        'CH_A':'Cha','CH_EE':'Chee','CH_OH':'Choh','CH_OO':'Choo',
        'D_A':'Da','D_EE':'Dee','D_EH':'Deh','D_IH':'Dih','D_OH':'Doh','D_OO':'Doo','D_UH':'Duh',
        'F_A':'Fa','F_EE':'Fee','F_O':'Fo','F_OO':'Foo',
        'G_A':'Ga','G_EE':'Gee','G_EH':'Geh','G_IH':'Gih','G_O':'Go','G_OO':'Goo','G_UH':'Guh',
        'H_A':'Ha','H_EE':'Hee','H_EH':'Heh','H_IH':'Hih','H_OH':'Hoh','H_OO':'Hoo','H_UH':'Huh',
        'J_A':'Ja','J_EE':'Jee','J_OH':'Joh','J_OO':'Joo',
        'K_A':'Ka','K_EE':'Kee','K_EH':'Keh','K_IH':'Kih','K_OH':'Koh','K_OO':'Koo','K_UH':'Kuh',
        'L_A':'La','L_EE':'Lee','L_OH':'Loh','L_OO':'Loo',
        'M_A':'Ma','M_E':'Me','M_EH':'Meh','M_IH':'Mih','M_OH':'Moh','M_OO':'Moo','M_UH':'Muh',
        'N_A':'Na','N_EE':'Nee','N_EH':'Neh','N_IH':'Nih','N_O':'No','N_OO':'Noo','N_UH':'Nuh',
        'P_A':'Pa','P_EA':'Pea','P_EH':'Peh','P_IH':'Pih','P_OH':'Poh','P_OO':'Poo','P_UH':'Puh',
        'R_A':'Ra','R_EE':'Ree','R_OH':'Roh','R_OO':'Roo',
        'S_A':'Sa','S_EE':'See','S_OH':'Soh','S_OO':'Soo',
        'SH_A':'Sha','SH_EE':'Shee','SH_OH':'Shoh','SH_OO':'Shoo',
        'T_A':'Ta','T_EE':'Tee','T_EH':'Teh','T_IH':'Tih','T_OE':'Toe','T_WO':'Two','T_UH':'Tuh',
        'TH_A':'Tha','TH_EE':'Thee','TH_OH':'Thoh','TH_OO':'Thoo',
        'V_A':'Va','V_EE':'Vee','V_OH':'Voh','V_OO':'Voo',
        'W_A':'Wa','W_E':'We','W_EH':'Weh','W_IH':'Wih','W_OH':'Woh','W_OO':'Woo','W_UH':'Wuh',
        'Y_A':'Ya','Y_EE':'Yee','Y_EH':'Yeh','Y_IH':'Yih','Y_OH':'Yoh','Y_OO':'Yoo','Y_UH':'Yuh',
        'Z_A':'Za','Z_EE':'Zee','Z_OH':'Zoh','Z_OO':'Zoo',
    };

    // Splits a cv_code into [consonant, vowel].
    // Codes with a dash (W-OH, SH-OH, TH-OO) split at the dash.
    // CH/SH/TH prefixes are 2-char consonants; everything else is 1-char.
    function parseCVCode(code) {
        if (code.includes('-')) {
            const idx = code.indexOf('-');
            return [code.slice(0, idx), code.slice(idx + 1)];
        }
        const two = code.slice(0, 2);
        if (['CH', 'SH', 'TH'].includes(two)) return [two, code.slice(2)];
        return [code[0], code.slice(1)];
    }

    function cv3Label(item) { return `${item.c} + ${item.v}`; }
    function getWhole(item) { return cv3Label(item); }
    function getPartsText(item) { return cv3Label(item); }

    function videoFor(item){ return ""; }

    let holdTimer = null;
    let holdTriggered = false;

    function handlePillPress(item, pillElement) {
        holdTriggered = false;

        pillElement.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            fillAllCards(item);
        });

        let touchTimer = null;

        const startPress = (e) => {
            holdTriggered = false;
            touchTimer = setTimeout(() => {
                holdTriggered = true;
                fillAllCards(item);

                pillElement.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    pillElement.style.transform = 'scale(1)';
                }, 200);
            }, 500);
        };

        const endPress = () => {
            if (touchTimer) {
                clearTimeout(touchTimer);
            }
        };

        const cancelPress = () => {
            if (touchTimer) {
                clearTimeout(touchTimer);
            }
            holdTriggered = false;
        };

        pillElement.addEventListener('mousedown', startPress);
        pillElement.addEventListener('mouseup', endPress);
        pillElement.addEventListener('mouseleave', cancelPress);

        pillElement.addEventListener('touchstart', startPress);
        pillElement.addEventListener('touchend', endPress);
        pillElement.addEventListener('touchcancel', cancelPress);
    }

    function fillAllCards(item) {
        const grid = document.getElementById('exercise-view');
        if (!grid) return;

        const count = getExerciseCount();

        if (isCVBlendingMode) {
            showAlert('Fill all cards is not available in CV Blending mode', 'Info', 'info');
            return;
        }

        if (!cardAssignments || cardAssignments.length !== count) {
            initExerciseGrid();
        }

        for (let i = 0; i < count; i++) {
            cardAssignments[i] = item;
            renderCardAtIndex(i, item);
        }

        showAlert(`All ${count} cards filled with ${prettyLabel(item)}`, 'Cards Filled', 'success', 2000);
    }

    let currentIndex = -1;

    function buttonPillImage(src, alt, onClick) {
        const el = document.createElement('div');
        el.className = 'pill';
        const img = document.createElement('img');
        img.src = src + '?v=' + ASSET_VER;
        img.alt = alt;
        img.style.width = "90px";
        img.style.height = "90px";
        img.style.objectFit = "contain";
        img.draggable = false;
        el.appendChild(img);

        el.addEventListener('click', (e) => {
            if (!holdTriggered) {
                onClick();
            }
            holdTriggered = false;
        });

        return el;
    }

    function buttonPill(text, onClick) {
        const el = document.createElement('div');
        el.className = 'pill';
        el.textContent = text;

        el.addEventListener('click', (e) => {
            if (!holdTriggered) {
                onClick();
            }
            holdTriggered = false;
        });

        return el;
    }

    function getExerciseCount() {
        const sel = document.getElementById('exerciseCountSelect');
        if (!sel) return 3;
        const n = parseInt(sel.value, 10);
        return (isNaN(n) ? 3 : Math.min(Math.max(n, 1), 6));
    }

    function addListHeader($parent, text) {
        const h = document.createElement('div')
        h.className = 'pill-section-header'
        h.textContent = text
        $parent.appendChild(h)
    }

    function buildLists() {
        const basePath = "/assets/portal/exercises/images/"

        function buildTabs(parent, folderNames, folderMap, buildPill) {
            const nav = document.createElement('div');
            nav.style.cssText = 'display:flex; flex-wrap:wrap; gap:4px; margin-bottom:6px;';

            const pillArea = document.createElement('div');
            pillArea.style.cssText = 'display:flex; flex-wrap:wrap; padding:4px 0; width:100%;';

            const tabBtns = [];

            function activate(idx) {
                tabBtns.forEach((b, j) => {
                    b.style.background  = j === idx ? '#28a745' : '#fff';
                    b.style.color       = j === idx ? '#fff'    : '#28a745';
                    b.style.borderColor = '#28a745';
                });
                pillArea.innerHTML = '';
                folderMap[folderNames[idx]].forEach(item => pillArea.appendChild(buildPill(item)));
            }

            folderNames.forEach((folder, i) => {
                const tab = document.createElement('button');
                tab.style.cssText = 'border:1px solid #28a745; border-radius:4px; padding:2px 10px; font-size:0.78rem; font-weight:600; cursor:pointer;';
                tab.textContent = folder;
                tab.addEventListener('click', () => activate(i));
                nav.appendChild(tab);
                tabBtns.push(tab);
            });

            if (folderNames.length > 0) activate(0);

            parent.appendChild(nav);
            parent.appendChild(pillArea);
        }

        const $letters = document.getElementById('lettersounds-list')
        const $cv      = document.getElementById('cv-list')
        const $3cv     = document.getElementById('3cv-list')
        const $sm      = document.getElementById('soundmixing-list')
        const $ws      = document.getElementById('wordsyllable-list')
        const $syl     = document.getElementById('syllables-list')

        if ($letters) $letters.innerHTML = ''
        if ($cv)      $cv.innerHTML      = ''
        if ($3cv)     $3cv.innerHTML     = ''
        if ($sm)      $sm.innerHTML      = ''
        if ($ws)      $ws.innerHTML      = ''
        if ($syl)     $syl.innerHTML     = ''

        SEQUENCE = [
            ...VOWELS.map(v => ({type:"vowel", id:v.code})),
            ...CONSONANTS.map(c => ({type:"consonant", id:c}))
        ];

        // Build the right pill for any cross-type card
        function buildPillForCard(card) {
            if (card.source_type === 'consonant' && card.consonant_code) {
                const src = CONSONANT_IMAGES[card.consonant_code] || `${basePath}consonant_${card.consonant_code}.png`;
                const pill = buttonPillImage(src, card.consonant_code, () => handleItemSelection({ type: 'consonant', id: card.consonant_code }));
                handlePillPress({ type: 'consonant', id: card.consonant_code }, pill);
                return pill;
            }
            if (card.source_type === 'vowel' && card.vowel_code) {
                const src = VOWEL_IMAGES[card.vowel_code] || `${basePath}vowel_${card.vowel_code}.png`;
                const pill = buttonPillImage(src, card.vowel_code, () => handleItemSelection({ type: 'vowel', id: card.vowel_code }));
                handlePillPress({ type: 'vowel', id: card.vowel_code }, pill);
                return pill;
            }
            if (card.source_type === 'cv_blend' && card.cv_code) {
                const [c, v] = parseCVCode(card.cv_code);
                return buttonPill(`${c}${v}`, () => renderBlendingExercise(c, v, 'cv'));
            }
            if (card.source_type === '3cv_blend' && card.syllable_breakdown) {
                const label = card.syllable_breakdown.split('-').join(' + ');
                return buttonPill(label, () => renderSyllableExercise(card.word_text));
            }
            if (card.source_type === 'word' && card.word_text) {
                const pill = buttonPill(card.word_text, () => selectWordExercise(card.word_text));
                handlePillPress({ type: 'word', id: card.word_text }, pill);
                return pill;
            }
            return buttonPill(card.sound_text || '?', () => {});
        }

        if ($letters) {
            addListHeader($letters, 'Consonants');
            const consSectionGroups = SECTION_GROUPS['consonant'] || [];
            if (consSectionGroups.length > 0) {
                const consFolders = {};
                consSectionGroups.forEach(g => { consFolders[g.name] = g.cards; });
                const consFolderNames = consSectionGroups.map(g => g.name);
                const groupedConsCodes = new Set();
                consSectionGroups.forEach(g => g.cards.forEach(c => {
                    if (c.source_type === 'consonant' && c.consonant_code) groupedConsCodes.add(c.consonant_code);
                }));
                const ungroupedCons = CONSONANTS.filter(c => !groupedConsCodes.has(c));
                if (ungroupedCons.length > 0) {
                    consFolders['Other'] = ungroupedCons.map(c => ({ source_type: 'consonant', consonant_code: c, sound_text: c }));
                    consFolderNames.push('Other');
                }
                buildTabs($letters, consFolderNames, consFolders, card => buildPillForCard(card));
            } else {
                const consFolders = {};
                CONSONANTS.forEach(c => {
                    const grps = CONSONANT_FOLDER_MAP[c];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!consFolders[folder]) consFolders[folder] = [];
                        consFolders[folder].push(c);
                    });
                });
                const consFolderNames = Object.keys(consFolders).filter(f => f !== 'Other').sort();
                if (consFolders['Other']) consFolderNames.push('Other');
                buildTabs($letters, consFolderNames, consFolders, c => {
                    const src = CONSONANT_IMAGES[c] || `${basePath}consonant_${c}.png`;
                    const pill = buttonPillImage(src, c, () => handleItemSelection({ type: "consonant", id: c }));
                    handlePillPress({ type: "consonant", id: c }, pill);
                    return pill;
                });
            }

            addListHeader($letters, 'Vowels');
            const vowSectionGroups = SECTION_GROUPS['vowel'] || [];
            if (vowSectionGroups.length > 0) {
                const vowFolders = {};
                vowSectionGroups.forEach(g => { vowFolders[g.name] = g.cards; });
                const vowFolderNames = vowSectionGroups.map(g => g.name);
                const groupedVowCodes = new Set();
                vowSectionGroups.forEach(g => g.cards.forEach(c => {
                    if (c.source_type === 'vowel' && c.vowel_code) groupedVowCodes.add(c.vowel_code);
                }));
                const ungroupedVow = VOWELS.filter(v => !groupedVowCodes.has(v.code));
                if (ungroupedVow.length > 0) {
                    vowFolders['Other'] = ungroupedVow.map(v => ({ source_type: 'vowel', vowel_code: v.code, sound_text: v.label }));
                    vowFolderNames.push('Other');
                }
                buildTabs($letters, vowFolderNames, vowFolders, card => buildPillForCard(card));
            } else {
                const vowFolders = {};
                VOWELS.forEach(v => {
                    const grps = VOWEL_FOLDER_MAP[v.code];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!vowFolders[folder]) vowFolders[folder] = [];
                        vowFolders[folder].push(v);
                    });
                });
                const vowFolderNames = Object.keys(vowFolders).filter(f => f !== 'Other').sort();
                if (vowFolders['Other']) vowFolderNames.push('Other');
                buildTabs($letters, vowFolderNames, vowFolders, v => {
                    const src = VOWEL_IMAGES[v.code] || `${basePath}vowel_${v.code}.png`;
                    const pill = buttonPillImage(src, v.label, () => handleItemSelection({ type: "vowel", id: v.code }));
                    handlePillPress({ type: "vowel", id: v.code }, pill);
                    return pill;
                });
            }
        }

        if ($cv) {
            const cvSectionGroups = SECTION_GROUPS['cv'] || SECTION_GROUPS['cv_blend'] || [];
            if (cvSectionGroups.length > 0) {
                const cvFolders = {};
                cvSectionGroups.forEach(g => { cvFolders[g.name] = g.cards; });
                const cvFolderNames = cvSectionGroups.map(g => g.name);
                const groupedCvKeys = new Set();
                cvSectionGroups.forEach(g => g.cards.forEach(c => {
                    if (c.source_type === 'cv_blend' && c.cv_code) {
                        const [cons, vowel] = parseCVCode(c.cv_code);
                        groupedCvKeys.add(`${cons}-${vowel}`);
                    }
                }));
                const ungroupedCv = CV_BLEND_ITEMS.filter(item => !groupedCvKeys.has(`${item.c}-${item.v}`));
                if (ungroupedCv.length > 0) {
                    cvFolders['Other'] = ungroupedCv.map(item => ({ source_type: 'cv_blend', cv_code: `${item.c}-${item.v}`, sound_text: `${item.c}${item.v}` }));
                    cvFolderNames.push('Other');
                }
                buildTabs($cv, cvFolderNames, cvFolders, card => buildPillForCard(card));
            } else {
                const cvFolders = {};
                CV_BLEND_ITEMS.forEach(item => {
                    const key = `${item.c}-${item.v}`;
                    const grps = CV_FOLDER_MAP[key];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!cvFolders[folder]) cvFolders[folder] = [];
                        cvFolders[folder].push(item);
                    });
                });
                const cvFolderNames = Object.keys(cvFolders).filter(f => f !== 'Other').sort();
                if (cvFolders['Other']) cvFolderNames.push('Other');
                buildTabs($cv, cvFolderNames, cvFolders, item => {
                    return buttonPill(`${item.c}${item.v}`, () => renderBlendingExercise(item.c, item.v, 'cv'));
                });
            }
        }

        if ($3cv) {
            const cvBlendingSectionGroups = SECTION_GROUPS['cv_blending'] || [];
            if (cvBlendingSectionGroups.length > 0) {
                const blend3Folders = {};
                cvBlendingSectionGroups.forEach(g => { blend3Folders[g.name] = g.cards; });
                const blend3FolderNames = cvBlendingSectionGroups.map(g => g.name);
                buildTabs($3cv, blend3FolderNames, blend3Folders, card => {
                    if (card.source_type === 'cv_blend' && card.cv_code) {
                        const [c, v] = parseCVCode(card.cv_code);
                        return buttonPill(cv3Label({c, v}), () => renderBlendingExercise(c, v, '3cv'));
                    }
                    return buildPillForCard(card);
                });
            } else {
                const blend3Folders = {};
                CV_BLEND_ITEMS.forEach(item => {
                    const key = `${item.c}-${item.v}`;
                    const grps = CV_FOLDER_MAP[key];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!blend3Folders[folder]) blend3Folders[folder] = [];
                        blend3Folders[folder].push(item);
                    });
                });
                const blend3FolderNames = Object.keys(blend3Folders).filter(f => f !== 'Other').sort();
                if (blend3Folders['Other']) blend3FolderNames.push('Other');
                buildTabs($3cv, blend3FolderNames, blend3Folders, item => {
                    return buttonPill(cv3Label(item), () => renderBlendingExercise(item.c, item.v, '3cv'));
                });
            }
        }

        if ($sm) {
            const syllableShiftsSectionGroups = SECTION_GROUPS['syllable_shifts'] || [];
            if (syllableShiftsSectionGroups.length > 0) {
                const smFolders = {};
                syllableShiftsSectionGroups.forEach(g => { smFolders[g.name] = g.cards; });
                const smFolderNames = syllableShiftsSectionGroups.map(g => g.name);
                buildTabs($sm, smFolderNames, smFolders, card => {
                    if (card.source_type === 'cv_blend' && card.cv_code) {
                        const [c, v] = parseCVCode(card.cv_code);
                        const id = `${c}-${v}`;
                        const pill = buttonPill(`${c}${v}`, () => selectSoundMix({ type: 'cv', id }));
                        handlePillPress({ type: 'cv', id }, pill);
                        return pill;
                    }
                    // Non-cv_blend: use soundMix slot tracking, no blend button
                    if (card.source_type === 'consonant' && card.consonant_code) {
                        const src = CONSONANT_IMAGES[card.consonant_code] || `${IMG_BASE}consonant_${card.consonant_code}.png`;
                        return buttonPillImage(src, card.consonant_code, () => selectNonCVSoundMix(card));
                    }
                    if (card.source_type === 'vowel' && card.vowel_code) {
                        const src = VOWEL_IMAGES[card.vowel_code] || `${IMG_BASE}vowel_${card.vowel_code}.png`;
                        return buttonPillImage(src, card.vowel_code, () => selectNonCVSoundMix(card));
                    }
                    return buttonPill(card.sound_text || '?', () => selectNonCVSoundMix(card));
                });
            } else {
                const smFolders = {};
                CV_BLEND_ITEMS.forEach(item => {
                    const key = `${item.c}-${item.v}`;
                    const grps = CV_FOLDER_MAP[key];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!smFolders[folder]) smFolders[folder] = [];
                        smFolders[folder].push(item);
                    });
                });
                const smFolderNames = Object.keys(smFolders).filter(f => f !== 'Other').sort();
                if (smFolders['Other']) smFolderNames.push('Other');
                buildTabs($sm, smFolderNames, smFolders, item => {
                    const id = `${item.c}-${item.v}`;
                    const pill = buttonPill(`${item.c}${item.v}`, () => selectSoundMix({ type: 'cv', id }));
                    handlePillPress({ type: 'cv', id }, pill);
                    return pill;
                });
            }
        }

        const WORD_FOLDER_ORDER = ['CVCV','VC','CV1CV2','C1V1C2V2 Stage 1','C1V1C2V2 Stage 2','CVC- Bilabial','CVC-Alveolar','CVC-Bilabial/Alveolar'];

        if ($ws) {
            const wordSectionGroups = SECTION_GROUPS['word'] || [];
            if (wordSectionGroups.length > 0) {
                const wordFolders = {};
                wordSectionGroups.forEach(g => { wordFolders[g.name] = g.cards; });
                const wordFolderNames = wordSectionGroups.map(g => g.name);
                const groupedWords = new Set();
                wordSectionGroups.forEach(g => g.cards.forEach(c => {
                    if (c.source_type === 'word' && c.word_text) groupedWords.add(c.word_text);
                }));
                const ungroupedWords = WORDS.filter(w => !WORD_SYLLABLE_MAP[w] && !groupedWords.has(w));
                if (ungroupedWords.length > 0) {
                    wordFolders['Other'] = ungroupedWords.map(w => ({ source_type: 'word', word_text: w, sound_text: w }));
                    wordFolderNames.push('Other');
                }
                buildTabs($ws, wordFolderNames, wordFolders, card => buildPillForCard(card));
            } else {
                const wordFolders = {};
                WORDS.forEach(w => {
                    const grps = WORD_FOLDER_MAP[w];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!wordFolders[folder]) wordFolders[folder] = [];
                        wordFolders[folder].push(w);
                    });
                });
                const wordFolderNames = Object.keys(wordFolders).filter(f => f !== 'Other').sort((a, b) => {
                    const ai = WORD_FOLDER_ORDER.indexOf(a), bi = WORD_FOLDER_ORDER.indexOf(b);
                    if (ai === -1 && bi === -1) return a.localeCompare(b);
                    if (ai === -1) return 1;
                    if (bi === -1) return -1;
                    return ai - bi;
                });
                if (wordFolders['Other']) wordFolderNames.push('Other');
                buildTabs($ws, wordFolderNames, wordFolders, w => {
                    const pill = buttonPill(w, () => selectWordExercise(w));
                    handlePillPress({ type: "word", id: w }, pill);
                    return pill;
                });
            }
        }

        if ($syl) {
            const sectionGroups = SECTION_GROUPS['3cv_blend'] || [];

            if (sectionGroups.length > 0) {
                // Use cross-type group data — groups can contain cards of any type
                const sylFolders = {};
                sectionGroups.forEach(g => { sylFolders[g.name] = g.cards; });
                const sylFolderNames = sectionGroups.map(g => g.name);

                buildTabs($syl, sylFolderNames, sylFolders, card => {
                    const makePill = () => {
                        if (card.source_type === '3cv_blend' && card.syllable_breakdown) {
                            const label = card.syllable_breakdown.split('-').join(' + ');
                            return buttonPill(label, () => renderSyllableExercise(card.word_text));
                        }
                        if (card.source_type === 'consonant' && card.consonant_code) {
                            const src = CONSONANT_IMAGES[card.consonant_code] || `${basePath}consonant_${card.consonant_code}.png`;
                            const pill = buttonPillImage(src, card.consonant_code, () => handleItemSelection({ type: 'consonant', id: card.consonant_code }));
                            handlePillPress({ type: 'consonant', id: card.consonant_code }, pill);
                            return pill;
                        }
                        if (card.source_type === 'vowel' && card.vowel_code) {
                            const src = VOWEL_IMAGES[card.vowel_code] || `${basePath}vowel_${card.vowel_code}.png`;
                            const pill = buttonPillImage(src, card.vowel_code, () => handleItemSelection({ type: 'vowel', id: card.vowel_code }));
                            handlePillPress({ type: 'vowel', id: card.vowel_code }, pill);
                            return pill;
                        }
                        if (card.source_type === 'cv_blend' && card.cv_code) {
                            const [cvC, cvV] = parseCVCode(card.cv_code);
                            const cvKey = `${cvC}-${cvV}`;
                            const src = CV_IMAGES[cvKey] || `${basePath}generated_images_cv/00_${cvKey.replace('-', '_')}.png`;
                            return buttonPillImage(src, `${cvC}${cvV}`, () => renderBlendingExercise(cvC, cvV, 'cv'));
                        }
                        if (card.source_type === 'word' && card.word_text) {
                            return buttonPill(card.word_text, () => selectWordExercise(card.word_text));
                        }
                        // Fallback: text pill with whatever label we have
                        return buttonPill(card.sound_text || '?', () => {});
                    };
                    return makePill();
                });
            } else {
                // Fallback: original word-based folder grouping
                const sylWords = WORDS.filter(w => WORD_SYLLABLE_MAP[w]);
                const sylFolders = {};
                sylWords.forEach(w => {
                    const grps = WORD_FOLDER_MAP[w];
                    const list = (grps && grps.length) ? grps : ['Other'];
                    list.forEach(folder => {
                        if (!sylFolders[folder]) sylFolders[folder] = [];
                        sylFolders[folder].push(w);
                    });
                });
                const sylFolderNames = Object.keys(sylFolders).filter(f => f !== 'Other').sort((a, b) => {
                    const ai = WORD_FOLDER_ORDER.indexOf(a), bi = WORD_FOLDER_ORDER.indexOf(b);
                    if (ai === -1 && bi === -1) return a.localeCompare(b);
                    if (ai === -1) return 1;
                    if (bi === -1) return -1;
                    return ai - bi;
                });
                if (sylFolders['Other']) sylFolderNames.push('Other');
                buildTabs($syl, sylFolderNames, sylFolders, w => {
                    const breakdown = WORD_SYLLABLE_MAP[w];
                    const label = breakdown.split('-').join(' + ');
                    return buttonPill(label, () => renderSyllableExercise(w));
                });
            }
        }
    }

    function iconPathFor(item) {
        if (item.type === "vowel")     return VOWEL_IMAGES[item.id] || `${IMG_BASE}vowel_${item.id}.png`;
        if (item.type === "consonant") return CONSONANT_IMAGES[item.id] || `${IMG_BASE}consonant_${item.id}.png`;
        if (item.type === "cv")        return CV_IMAGES[item.id] || `${IMG_BASE}cv_${item.id.replace('-', '_')}.jpg`;
        if (item.type === "3cv")        return CV_IMAGES[item.id] || `${IMG_BASE}3cv_${item.id.replace('-', '_')}.jpg`;
        return "";
    }

    function topPathFor(item) {
        if (item.type === "vowel")     return VOWEL_IMAGES[item.id] || `${IMG_BASE}top_vowel_${item.id}.png`;
        if (item.type === "consonant") return CONSONANT_IMAGES[item.id] || `${IMG_BASE}top_consonant_${item.id}.png`;
        if (item.type === "cv")        return CV_IMAGES[item.id] || `${IMG_BASE}top_cv_${item.id.replace('-', '_')}.jpg`;
        if (item.type === "3cv")        return CV_IMAGES[item.id] || `${IMG_BASE}top_3cv_${item.id.replace('-', '_')}.jpg`;
        return "";
    }

    async function renderCardAtIndex(index, item) {
        const grid  = document.getElementById('exercise-view');
        if (!grid) return;

        const cards   = grid.querySelectorAll('.exercise-card');
        const card    = cards[index];
        if (!card) return;

        const content = card.querySelector('.exercise-content');
        if (!content) return;

        content.innerHTML = '';
        card.classList.remove('completed');

        const label = (item.type === 'word') ? item.id : prettyLabel(item);

        if (item.type === 'consonant' || item.type === 'vowel') {
            let topSrc  = topPathFor(item);
            let iconSrc = iconPathFor(item);

            const hasTop  = await loadImage(topSrc);
            const hasIcon = await loadImage(iconSrc);

            if (hasTop) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            }

            const labelSpan = document.createElement('span');
            labelSpan.className = 'cv-parts-text';
            labelSpan.textContent = label;
            content.appendChild(labelSpan);

            return;
        }

        if (item.type === 'word') {
            const word    = item.id;
            const topSrc  = WORD_IMAGES[word.toLowerCase()] || await firstExistingImage(`${IMG_BASE}top_${word.toLowerCase()}`);

            if (topSrc) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = word;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = word;
                content.appendChild(span);
            }

            const wordSpan = document.createElement('span');
            wordSpan.className = 'cv-parts-text';
            wordSpan.textContent = word;
            content.appendChild(wordSpan);
            return;
        }

        if (item.type === 'cv' || item.type === '3cv') {
            const [cons, vowelCode] = item.id.split('-');
            const baseId = `${cons}_${vowelCode}`;

            let topBase = `${IMG_BASE}top_cv_${baseId}`;
            if (item.type === '3cv') {
                topBase = `${IMG_BASE}top_3cv_${baseId}`;
            }

            const cvImgOverride = (item.type === '3cv') ? CV_IMAGES[item.id] : null;
            const topSrc   = cvImgOverride || await firstExistingImage(topBase);
            const consSrc  = CONSONANT_IMAGES[cons] || await firstExistingImage(`${IMG_BASE}consonant_${cons}`);
            const vowelSrc = VOWEL_IMAGES[vowelCode] || await firstExistingImage(`${IMG_BASE}vowel_${vowelCode}`);

            if (topSrc) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            const bottomRow = document.createElement('div');
            bottomRow.className = 'cv-bottom-row';

            if (consSrc) {
                const cImg = document.createElement('img');
                cImg.src = consSrc + '?v=' + ASSET_VER;
                cImg.alt = `Consonant ${cons}`;
                bottomRow.appendChild(cImg);
            } else {
                const cText = document.createElement('span');
                cText.textContent = cons;
                bottomRow.appendChild(cText);
            }

            if (vowelSrc) {
                const vImg = document.createElement('img');
                vImg.src = vowelSrc + '?v=' + ASSET_VER;
                vImg.alt = `Vowel ${vowelCode}`;
                bottomRow.appendChild(vImg);
            } else {
                const vText = document.createElement('span');
                vText.textContent = vowelCode;
                bottomRow.appendChild(vText);
            }

            content.appendChild(bottomRow);
            return;
        }

        const span = document.createElement('span');
        span.textContent = label;
        content.appendChild(span);
    }

    function resetCardsAfterSuccess() {
        document.querySelectorAll('#exercise-view .exercise-card').forEach(c => c.classList.remove('completed'));
        const btn = document.getElementById('btnSuccess');
        if (btn) btn.disabled = true;

        // Reset fill indices so next pill click fills from card 0 instead of last slot
        selectedCardIndex = null;
        if (cardAssignments.length)     cardAssignments     = new Array(cardAssignments.length).fill(null);
        if (soundMixAssignments.length) soundMixAssignments = new Array(soundMixAssignments.length).fill(null);
        if (wordAssignments.length)     wordAssignments     = new Array(wordAssignments.length).fill(null);
    }

    function resetExerciseArea() {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        const title      = document.getElementById('choices-title');
        const wrap       = document.getElementById('choices-wrap');

        standardAssignments = [];

        if (grid) {
            grid.classList.remove('vertical-layout');
            if (isCVBlendingMode) {
                grid.innerHTML = '';
            } else {
                grid.innerHTML = `
                <div class="exercise-placeholder text-muted">
                    Pick an exercise above
                </div>
            `;
            }
        }

        if (btnSuccess) {
            btnSuccess.disabled = true;
        }

        if (title) {
            title.style.display = 'none';
            title.textContent   = '';
        }
        if (wrap) {
            wrap.innerHTML = '';
        }

        currentExerciseItem  = null;
        currentIndex         = -1;
        soundMixAssignments  = [];
        wordAssignments      = [];
    }

    function loadImage(src) {
        return new Promise(resolve => {
            if (!src) return resolve(false);
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = src + `?v=${Date.now()}`;
        });
    }

    async function selectSoundMix(item) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        isSoundMixingMode  = true;
        isWordSyllableMode = false;

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) {
            title.style.display = 'none';
            title.textContent   = '';
        }
        if (wrap) wrap.innerHTML = '';

        const count        = getExerciseCount();
        const isRestoring  = savedSoundMixAssignments !== null;

        if (isRestoring || !soundMixAssignments || soundMixAssignments.length !== count || grid.children.length !== count) {
            soundMixAssignments  = isRestoring ? savedSoundMixAssignments.slice() : new Array(count).fill(null);
            savedSoundMixAssignments = null;
            grid.innerHTML = "";
            grid.classList.remove('vertical-layout');
            if (btnSuccess) btnSuccess.disabled = true;

            for (let i = 0; i < count; i++) {
                const card = document.createElement('div');
                card.className = 'exercise-card';

                const content = document.createElement('div');
                content.className = 'exercise-content';

                const span = document.createElement('span');
                span.textContent = 'Choose sound';
                span.classList.add('text-muted');
                content.appendChild(span);

                card.appendChild(content);

                const actions = document.createElement('div');
                actions.className = 'exercise-card-actions';

                const changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'change-card-btn';
                changeBtn.textContent = 'Change Card';

                changeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    handleChangeCard(card);
                });

                actions.appendChild(changeBtn);

                grid.appendChild(card);
                card.appendChild(actions);

                wireExerciseCardClick(card);
            }
        }

        if (isRestoring) {
            // Rebuild all previously filled cards from saved state
            const allCards = grid.querySelectorAll('.exercise-card');
            for (let i = 0; i < count; i++) {
                if (soundMixAssignments[i]) {
                    await fillOneSoundMixCard(soundMixAssignments[i], allCards[i]);
                }
            }
            return;
        }

        let idx = soundMixAssignments.findIndex(x => x === null);
        if (idx === -1) idx = count - 1;

        soundMixAssignments[idx] = item;

        const allCards = grid.querySelectorAll('.exercise-card');
        await fillOneSoundMixCard(item, allCards[idx]);
    }

    async function fillOneSoundMixCard(item, card) {
        const [cons, vowelCode] = item.id.split('-');
        const baseId = `${cons}_${vowelCode}`;

        const topSrc = CV_IMAGES[item.id]
            || (CV_SOUND_MAP[baseId] ? `${IMG_BASE}generated_images_cv/00_${CV_SOUND_MAP[baseId]}.png` : null)
            || await firstExistingImage(`${IMG_BASE}top_cv_${baseId}`);
        const label = prettyLabel(item);

        const content = card.querySelector('.exercise-content');
        content.innerHTML = '';

        if (topSrc) {
            const topImg = document.createElement('img');
            topImg.src = topSrc + '?v=' + ASSET_VER;
            topImg.alt = label;
            topImg.className = 'exercise-main-img';
            content.appendChild(topImg);
        } else {
            const span = document.createElement('span');
            span.textContent = label;
            content.appendChild(span);
        }

        const bottomRow = document.createElement('div');
        bottomRow.className = 'cv-bottom-row';
        const cvText = document.createElement('span');
        cvText.className = 'cv-parts-text';
        cvText.textContent = cons + vowelCode;
        bottomRow.appendChild(cvText);
        content.appendChild(bottomRow);

        const actions = card.querySelector('.exercise-card-actions');
        if (actions) {
            const existing = actions.querySelector('.blend-btn');
            if (existing) existing.remove();
            const blendBtn = document.createElement('button');
            blendBtn.type = 'button';
            blendBtn.className = 'change-card-btn blend-btn';
            blendBtn.textContent = 'CV Blending';
            blendBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                cvBlendingSource = 'soundmixing';
                savedSoundMixAssignments = soundMixAssignments.slice();
                switchPanelContext('panel-3cv');
                renderBlendingExercise(cons, vowelCode, '3cv');
            });
            actions.appendChild(blendBtn);
        }
    }

    // Render a non-cv_blend card into an existing soundMix slot (no blend button)
    async function fillNonCVSoundMixCard(card, cardEl) {
        const existing = cardEl.querySelector('.blend-btn');
        if (existing) existing.remove();

        const content = cardEl.querySelector('.exercise-content');
        if (!content) return;
        content.innerHTML = '';

        if (card.source_type === 'consonant' && card.consonant_code) {
            const src = CONSONANT_IMAGES[card.consonant_code] || `${IMG_BASE}consonant_${card.consonant_code}.png`;
            const img = document.createElement('img');
            img.src = src + '?v=' + ASSET_VER;
            img.alt = card.consonant_code;
            img.className = 'exercise-main-img';
            content.appendChild(img);
            const span = document.createElement('span');
            span.className = 'cv-parts-text';
            span.textContent = card.consonant_code;
            content.appendChild(span);
        } else if (card.source_type === 'vowel' && card.vowel_code) {
            const src = VOWEL_IMAGES[card.vowel_code] || `${IMG_BASE}vowel_${card.vowel_code}.png`;
            const img = document.createElement('img');
            img.src = src + '?v=' + ASSET_VER;
            img.alt = card.vowel_code;
            img.className = 'exercise-main-img';
            content.appendChild(img);
            const span = document.createElement('span');
            span.className = 'cv-parts-text';
            span.textContent = card.vowel_code;
            content.appendChild(span);
        } else if (card.source_type === '3cv_blend' && card.syllable_breakdown) {
            const span = document.createElement('span');
            span.className = 'cv-parts-text';
            span.textContent = card.syllable_breakdown.split('-').join(' + ');
            content.appendChild(span);
        } else if (card.source_type === 'word' && card.word_text) {
            const span = document.createElement('span');
            span.className = 'cv-parts-text';
            span.textContent = card.word_text;
            content.appendChild(span);
        } else {
            const span = document.createElement('span');
            span.textContent = card.sound_text || '?';
            content.appendChild(span);
        }

        const btnSuccess = document.getElementById('btnSuccess');
        if (btnSuccess && soundMixAssignments.length > 0 && soundMixAssignments.every(x => x !== null)) {
            btnSuccess.disabled = false;
        }
    }

    // Place a non-cv_blend card into the soundMix exercise grid (fills next empty slot)
    async function selectNonCVSoundMix(card) {
        const grid      = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        isSoundMixingMode  = true;
        isWordSyllableMode = false;
        isCVBlendingMode   = false;

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) { title.style.display = 'none'; title.textContent = ''; }
        if (wrap)  wrap.innerHTML = '';

        const count = getExerciseCount();

        // Init the soundMix grid if it isn't already set up
        if (!soundMixAssignments || soundMixAssignments.length !== count || grid.children.length !== count) {
            soundMixAssignments = new Array(count).fill(null);
            savedSoundMixAssignments = null;
            grid.innerHTML = '';
            grid.classList.remove('vertical-layout');
            if (btnSuccess) btnSuccess.disabled = true;

            for (let i = 0; i < count; i++) {
                const cardEl  = document.createElement('div');
                cardEl.className = 'exercise-card';
                const content = document.createElement('div');
                content.className = 'exercise-content';
                const span = document.createElement('span');
                span.textContent = 'Choose sound';
                span.classList.add('text-muted');
                content.appendChild(span);
                cardEl.appendChild(content);
                const actions = document.createElement('div');
                actions.className = 'exercise-card-actions';
                const changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'change-card-btn';
                changeBtn.textContent = 'Change Card';
                changeBtn.addEventListener('click', function(e) { e.stopPropagation(); handleChangeCard(cardEl); });
                actions.appendChild(changeBtn);
                grid.appendChild(cardEl);
                cardEl.appendChild(actions);
                wireExerciseCardClick(cardEl);
            }
        }

        let idx = soundMixAssignments.findIndex(x => x === null);
        if (idx === -1) idx = 0;

        soundMixAssignments[idx] = { _nonCV: true, _card: card };

        const allCards = grid.querySelectorAll('.exercise-card');
        if (allCards[idx]) {
            await fillNonCVSoundMixCard(card, allCards[idx]);
        }
    }

    async function firstExistingImage(basePath) {
        const exts = ['.png', '.jpg', '.jpeg'];

        for (const ext of exts) {
            const url = basePath + ext;
            const ok = await loadImage(url);
            if (ok) {
                return url;
            }
        }
        return null;
    }

    let activePanelId = null

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.category-btn')
        if (!btn) return

        const target = btn.dataset.panel
        const targetEl = document.getElementById(target)
        if (!targetEl) return

        const isSwitchingPanels = (activePanelId !== target)

        document.querySelectorAll('.slide-panel').forEach(p => {
            if (p.id === target) p.classList.toggle('open')
            else p.classList.remove('open')
        })

        const titleEl = document.getElementById('exercise-panel-title')
        if (titleEl) {
            titleEl.textContent = btn.textContent.trim()
            titleEl.classList.add('panel-active')
        }

        if (isSwitchingPanels) {
            activePanelId = target
            updateVideoBtnForPanel(target)

            isSoundMixingMode  = (target === 'panel-soundmixing')
            isWordSyllableMode = (target === 'panel-wordsyllable')
            isCVBlendingMode   = (target === 'panel-cv')
            cvBlendingSource   = null

            cardSelectEnabled = !isCVBlendingMode
            selectedCardIndex = null

            resetExerciseArea()
            document.querySelectorAll('.exercise-card').forEach(c => c.classList.remove('selected'))
        }
    })

    function switchPanelContext(panelId) {
        // Open the destination pill panel, close all others
        document.querySelectorAll('.slide-panel').forEach(p => {
            if (p.id === panelId) p.classList.add('open');
            else p.classList.remove('open');
        });

        // Update the header title to match the destination panel's category button
        const categoryBtn = document.querySelector(`.category-btn[data-panel="${panelId}"]`);
        const titleEl = document.getElementById('exercise-panel-title');
        if (titleEl && categoryBtn) {
            titleEl.textContent = categoryBtn.textContent.trim();
            titleEl.classList.add('panel-active');
        }

        activePanelId      = panelId;
        isSoundMixingMode  = (panelId === 'panel-soundmixing');
        isWordSyllableMode = (panelId === 'panel-wordsyllable');
        isCVBlendingMode   = (panelId === 'panel-cv');

        updateVideoBtnForPanel(panelId);
    }

    function selectByObject(obj){
        let idx = SEQUENCE.findIndex(x => x.type===obj.type && x.id===obj.id);
        if (idx === -1) { SEQUENCE.push(obj); idx = SEQUENCE.length-1; }
        currentIndex = idx;
        renderExercise(SEQUENCE[currentIndex]);
        document.querySelectorAll('.slide-panel').forEach(p=>p.classList.remove('open'));
    }

    async function renderExercise(item) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');

        const label = prettyLabel(item);
        const NUMBER_OF_CARDS = getExerciseCount();

        if (!grid) return;

        currentExerciseItem = item;

        grid.innerHTML = "";
        grid.classList.remove('vertical-layout');
        if (btnSuccess) {
            btnSuccess.disabled = true;
        }

        standardAssignments = new Array(NUMBER_OF_CARDS).fill(item);

        for (let i = 0; i < NUMBER_OF_CARDS; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';

            const content = document.createElement('div');
            content.className = 'exercise-content';

            let topSrc, iconSrc, hasTop = false, hasIcon = false;
            let cvConsonantSrc = null;
            let cvVowelSrc     = null;

            if (item.type === "cv" || item.type === "3cv") {
                if (item.type === "cv") {
                    var cons = item.id.slice(0,1)
                    var vowelCode = item.id.slice(1,3)
                    var baseId = `${cons}_${vowelCode}`;
                } else {
                    var [cons, vowelCode] = item.id.split('-');
                    var baseId = `${cons}_${vowelCode}`;
                }

                let topBase = `${IMG_BASE}top_cv_${baseId}`;
                if (item.type === "3cv") {
                    topBase = `${IMG_BASE}top_3cv_${baseId}`;
                }

                topSrc  = (item.type === "3cv" ? CV_IMAGES[item.id] : null) || await firstExistingImage(topBase);
                hasTop  = !!topSrc;

                cvConsonantSrc   = CONSONANT_IMAGES[cons] || await firstExistingImage(`${IMG_BASE}consonant_${cons}`);
                cvVowelSrc       = VOWEL_IMAGES[vowelCode] || await firstExistingImage(`${IMG_BASE}vowel_${vowelCode}`);
                hasIcon          = !!(cvConsonantSrc || cvVowelSrc);
            } else {
                topSrc  = topPathFor(item);
                iconSrc = iconPathFor(item);

                hasTop  = await loadImage(topSrc);
                hasIcon = await loadImage(iconSrc);
            }

            if (hasTop) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            if (item.type === "cv" || item.type === "3cv") {
                const bottomRow = document.createElement('div');
                bottomRow.className = 'cv-bottom-row';

                if (cvConsonantSrc) {
                    const cImg = document.createElement('img');
                    cImg.src = cvConsonantSrc + '?v=' + ASSET_VER;
                    cImg.alt = `Consonant ${label}`;
                    bottomRow.appendChild(cImg);
                } else {
                    const cText = document.createElement('span');
                    cText.textContent = label.split(' + ')[0] || item.id.split('-')[0];
                    bottomRow.appendChild(cText);
                }

                if (cvVowelSrc) {
                    const vImg = document.createElement('img');
                    vImg.src = cvVowelSrc + '?v=' + ASSET_VER;
                    vImg.alt = `Vowel ${label}`;
                    bottomRow.appendChild(vImg);
                } else {
                    const vText = document.createElement('span');
                    const parts = item.id.split('-');
                    vText.textContent = parts[1] || '';
                    bottomRow.appendChild(vText);
                }

                content.appendChild(bottomRow);
            } else {
                if (hasIcon) {
                    const iconImg = document.createElement('img');
                    iconImg.src = iconSrc + '?v=' + ASSET_VER;
                    iconImg.alt = label + " icon";
                    iconImg.className = 'exercise-icon-img';
                    content.appendChild(iconImg);
                } else {
                    const mini = document.createElement('div');
                    mini.textContent = label;
                    mini.style.opacity = ".6";
                    mini.style.fontSize = "18px";
                    content.appendChild(mini);
                }
            }

            const actions = document.createElement('div');
            actions.className = 'exercise-card-actions';

            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
            changeBtn.className = 'change-card-btn';
            changeBtn.textContent = 'Change Card';
            changeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleChangeCard(card);
            });

            actions.appendChild(changeBtn);

            card.appendChild(content);
            grid.appendChild(card);
            card.appendChild(actions);

            wireExerciseCardClick(card);
        }

        const vsrc = videoFor(item);
        const vp   = document.getElementById('videoPlayer');

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        wrap.innerHTML = "";

        if (item.type === "vowel") {
            title.style.display = "";
            title.textContent = "Pick a consonant to blend with this vowel:";
            CONSONANTS.forEach(c =>
                wrap.appendChild(
                    buttonPill(c, () => selectByObject({ type: "cv", id: `${c}-${item.id}` }))
                )
            );
        } else if (item.type === "consonant") {
            title.style.display = "";
            title.textContent = "Pick a vowel to blend with this consonant:";
            VOWELS.forEach(v =>
                wrap.appendChild(
                    buttonPill(v.label, () => selectByObject({ type: "cv", id: `${item.id}-${v.code}` }))
                )
            );
        } else {
            title.style.display = "none";
        }

        $('#btnBack').toggleClass('ghost', currentIndex <= 0);
        $('#btnNext').toggleClass('ghost', currentIndex >= SEQUENCE.length - 1);
    }

    async function renderBlendingExercise(cons, vowelCode, mode = 'cv') {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        const count = getExerciseCount();

        isSoundMixingMode  = false;
        isWordSyllableMode = false;
        isCVBlendingMode   = true;
        cardSelectEnabled  = false;
        selectedCardIndex  = null;

        grid.innerHTML = '';
        grid.classList.remove('vertical-layout');

        // Clone button to clear any stale handlers, then wire a fresh success listener
        if (btnSuccess) {
            const freshBtn = btnSuccess.cloneNode(true);
            freshBtn.disabled = true;
            btnSuccess.parentNode.replaceChild(freshBtn, btnSuccess);
            freshBtn.addEventListener('click', function () {
                if (selectedSuccessMedia) { playSuccessMedia(); } else { new bootstrap.Modal('#successModal').show(); }
            });
        }

        const whole = `${cons}${vowelCode}`;
        const parts = `${cons} + ${vowelCode}`;

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) { title.style.display = 'none'; title.textContent = ''; }
        if (wrap) wrap.innerHTML = '';

        if (mode === 'cv') {
            // Horizontal layout — like consonant/vowel exercises
            const cvCode  = `${cons}-${vowelCode}`;
            const imgSrc  = CV_IMAGES[cvCode] || null;

            for (let i = 0; i < count; i++) {
                const card = document.createElement('div');
                card.className = 'exercise-card';
                const content = document.createElement('div');
                content.className = 'exercise-content';

                if (imgSrc) {
                    const img = document.createElement('img');
                    img.src = imgSrc + '?v=' + ASSET_VER;
                    img.alt = whole;
                    img.className = 'exercise-main-img';
                    content.appendChild(img);
                }

                const span = document.createElement('span');
                span.className = 'cv-parts-text';
                span.textContent = whole;
                content.appendChild(span);

                card.appendChild(content);
                wireExerciseCardClick(card);
                grid.appendChild(card);
            }

            // Toggle button below the cards, centered
            if (wrap) {
                wrap.style.justifyContent = 'center';
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-success mt-2';
                toggleBtn.style.cssText = 'font-size:1.25rem; padding:0.5rem 1.25rem; color:#fff;';
                if (cvBlendingSource === 'soundmixing') {
                    toggleBtn.textContent = 'Syllable Shifts';
                    toggleBtn.addEventListener('click', () => {
                        cvBlendingSource = null;
                        switchPanelContext('panel-soundmixing');
                        selectSoundMix({ type: 'cv', id: `${cons}-${vowelCode}` });
                    });
                } else {
                    toggleBtn.textContent = 'CV Blending';
                    toggleBtn.addEventListener('click', () => {
                        switchPanelContext('panel-3cv');
                        renderBlendingExercise(cons, vowelCode, '3cv');
                    });
                }
                wrap.appendChild(toggleBtn);
            }
        } else {
            // CV — vertical stack + image on right
            grid.classList.add('vertical-layout');

            const baseId   = `${cons}_${vowelCode}`;
            const wholeSrc = CV_IMAGES[`${cons}-${vowelCode}`]
                || (CV_SOUND_MAP[baseId] ? `${IMG_BASE}generated_images_cv/00_${CV_SOUND_MAP[baseId]}.png` : null)
                || await firstExistingImage(`${IMG_BASE}top_3cv_${baseId}`);

            const cardsCol = document.createElement('div');
            cardsCol.style.cssText = 'display:flex; flex-direction:column;';

            for (let i = 0; i < count; i++) {
                const card = document.createElement('div');
                card.className = 'exercise-card';
                const content = document.createElement('div');
                content.className = 'exercise-content';
                const span = document.createElement('span');
                span.className = 'cv-parts-text';
                span.textContent = parts;
                content.appendChild(span);
                card.appendChild(content);
                wireExerciseCardClick(card);
                cardsCol.appendChild(card);
            }

            const wholeCard = document.createElement('div');
            wholeCard.className = 'exercise-card';
            const wholeContent = document.createElement('div');
            wholeContent.className = 'exercise-content';
            const wholeSpan = document.createElement('span');
            wholeSpan.className = 'cv-whole-text';
            wholeSpan.textContent = whole;
            wholeContent.appendChild(wholeSpan);
            wholeCard.appendChild(wholeContent);
            wireExerciseCardClick(wholeCard);
            cardsCol.appendChild(wholeCard);

            if (wholeSrc) {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'display:flex; flex-direction:row; justify-content:center; align-items:center; gap:30px; width:100%; height:100%;';
                const imageCol = document.createElement('div');
                imageCol.style.cssText = 'display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px;';
                const img = document.createElement('img');
                img.src = wholeSrc + '?v=' + ASSET_VER;
                img.alt = whole;
                img.style.cssText = 'max-width:400px; max-height:400px; object-fit:contain;';
                imageCol.appendChild(img);

                // Toggle button under the image
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-success';
                toggleBtn.style.cssText = 'font-size:1.25rem; padding:0.5rem 1.25rem; color:#fff;';
                if (cvBlendingSource === 'soundmixing') {
                    toggleBtn.textContent = 'Syllable Shifts';
                    toggleBtn.addEventListener('click', () => {
                        cvBlendingSource = null;
                        switchPanelContext('panel-soundmixing');
                        selectSoundMix({ type: 'cv', id: `${cons}-${vowelCode}` });
                    });
                } else {
                    toggleBtn.textContent = 'C-V';
                    toggleBtn.addEventListener('click', () => {
                        switchPanelContext('panel-cv');
                        renderBlendingExercise(cons, vowelCode, 'cv');
                    });
                }
                imageCol.appendChild(toggleBtn);

                wrapper.appendChild(cardsCol);
                wrapper.appendChild(imageCol);
                grid.appendChild(wrapper);
            } else {
                // No image — add toggle button in wrap
                if (wrap) {
                    wrap.style.justifyContent = 'center';
                    const toggleBtn = document.createElement('button');
                    toggleBtn.className = 'btn btn-success mt-2';
                    toggleBtn.style.cssText = 'font-size:1.25rem; padding:0.5rem 1.25rem; color:#fff;';
                    if (cvBlendingSource === 'soundmixing') {
                        toggleBtn.textContent = 'Syllable Shifts';
                        toggleBtn.addEventListener('click', () => {
                            cvBlendingSource = null;
                            switchPanelContext('panel-soundmixing');
                            selectSoundMix({ type: 'cv', id: `${cons}-${vowelCode}` });
                        });
                    } else {
                        toggleBtn.textContent = 'C-V';
                        toggleBtn.addEventListener('click', () => {
                            switchPanelContext('panel-cv');
                            renderBlendingExercise(cons, vowelCode, 'cv');
                        });
                    }
                    wrap.appendChild(toggleBtn);
                }
                grid.appendChild(cardsCol);
            }
        }
    }

    async function renderSyllableExercise(word) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        const count = getExerciseCount();

        isSoundMixingMode  = false;
        isWordSyllableMode = false;
        isCVBlendingMode   = true;
        cardSelectEnabled  = false;
        selectedCardIndex  = null;

        grid.innerHTML = '';
        grid.classList.add('vertical-layout');

        // Clone button and wire fresh success handler
        if (btnSuccess) {
            const freshBtn = btnSuccess.cloneNode(true);
            freshBtn.disabled = true;
            btnSuccess.parentNode.replaceChild(freshBtn, btnSuccess);
            freshBtn.addEventListener('click', function () {
                if (selectedSuccessMedia) { playSuccessMedia(); } else { new bootstrap.Modal('#successModal').show(); }
            });
        }

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) { title.style.display = 'none'; title.textContent = ''; }
        if (wrap)  wrap.innerHTML = '';

        const breakdown  = (word && WORD_SYLLABLE_MAP[word]) ? WORD_SYLLABLE_MAP[word] : (word || '');
        const partsLabel = breakdown.split('-').join(' + ');
        const imgSrc     = WORD_IMAGES[word.toLowerCase()] || null;

        const cardsCol = document.createElement('div');
        cardsCol.style.cssText = 'display:flex; flex-direction:column;';

        // count cards showing syllable breakdown (e.g. "bu + bble")
        for (let i = 0; i < count; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';
            const content = document.createElement('div');
            content.className = 'exercise-content';
            const span = document.createElement('span');
            span.className = 'cv-parts-text';
            span.textContent = partsLabel;
            content.appendChild(span);
            card.appendChild(content);
            wireExerciseCardClick(card);
            cardsCol.appendChild(card);
        }

        // Final card showing whole word
        const wholeCard = document.createElement('div');
        wholeCard.className = 'exercise-card';
        const wholeContent = document.createElement('div');
        wholeContent.className = 'exercise-content';
        const wholeSpan = document.createElement('span');
        wholeSpan.className = 'cv-whole-text';
        wholeSpan.textContent = word;
        wholeContent.appendChild(wholeSpan);
        wholeCard.appendChild(wholeContent);
        wireExerciseCardClick(wholeCard);
        cardsCol.appendChild(wholeCard);

        if (imgSrc) {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'display:flex; flex-direction:row; justify-content:center; align-items:center; gap:30px; width:100%; height:100%;';

            const imageCol = document.createElement('div');
            imageCol.style.cssText = 'display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px;';
            const img = document.createElement('img');
            img.src = imgSrc + '?v=' + ASSET_VER;
            img.alt = word;
            img.style.cssText = 'max-width:400px; max-height:400px; object-fit:contain;';
            imageCol.appendChild(img);

            // Toggle button to Words exercise
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-success';
            toggleBtn.style.cssText = 'font-size:1.25rem; padding:0.5rem 1.25rem; color:#fff;';
            toggleBtn.textContent = 'Word';
            toggleBtn.addEventListener('click', () => {
                if (!wordAssignments || wordAssignments.every(w => w === null)) {
                    wordAssignments = new Array(getExerciseCount()).fill(word);
                }
                restoreWordGrid();
            });
            imageCol.appendChild(toggleBtn);

            wrapper.appendChild(cardsCol);
            wrapper.appendChild(imageCol);
            grid.appendChild(wrapper);
        } else {
            grid.appendChild(cardsCol);
        }

        // Toggle button to Syllables under cards (when no image)
        if (!imgSrc && wrap) {
            wrap.style.justifyContent = 'center';
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-success mt-2';
            toggleBtn.style.cssText = 'font-size:1.25rem; padding:0.5rem 1.25rem; color:#fff;';
            toggleBtn.textContent = 'Word';
            toggleBtn.addEventListener('click', () => {
                if (!wordAssignments || wordAssignments.every(w => w === null)) {
                    wordAssignments = new Array(getExerciseCount()).fill(word);
                }
                restoreWordGrid();
            });
            wrap.appendChild(toggleBtn);
        }
    }

    async function renderSingleStandardCard(card, item) {
        const content = card.querySelector('.exercise-content');
        if (!content) return;

        content.innerHTML = "";

        const label = prettyLabel(item);

        let topSrc, iconSrc;
        let hasTop = false, hasIcon = false;

        if (item.type === "cv" || item.type === "3cv") {
            const [cons, vowelCode] = item.id.split('-');
            const baseId = `${cons}_${vowelCode}`;

            let topBase = `${IMG_BASE}top_cv_${baseId}`;
            if (item.type === "3cv") topBase = `${IMG_BASE}top_3cv_${baseId}`;

            topSrc = (item.type === "3cv" ? CV_IMAGES[item.id] : null) || await firstExistingImage(topBase);
            hasTop = !!topSrc;

            const consSrc  = CONSONANT_IMAGES[cons] || await firstExistingImage(`${IMG_BASE}consonant_${cons}`);
            const vowelSrc = VOWEL_IMAGES[vowelCode] || await firstExistingImage(`${IMG_BASE}vowel_${vowelCode}`);

            const bottomRow = document.createElement('div');
            bottomRow.className = 'cv-bottom-row';

            if (consSrc) {
                const img = document.createElement('img');
                img.src = consSrc + '?v=' + ASSET_VER;
                bottomRow.appendChild(img);
            }

            if (vowelSrc) {
                const img = document.createElement('img');
                img.src = vowelSrc + '?v=' + ASSET_VER;
                bottomRow.appendChild(img);
            }

            if (hasTop) {
                const img = document.createElement('img');
                img.src = topSrc + '?v=' + ASSET_VER;
                img.className = "exercise-main-img";
                content.appendChild(img);
            } else {
                content.appendChild(document.createTextNode(label));
            }

            content.appendChild(bottomRow);
            return;
        }

        topSrc  = topPathFor(item);
        iconSrc = iconPathFor(item);

        hasTop  = await loadImage(topSrc);
        hasIcon = await loadImage(iconSrc);

        if (hasTop) {
            const img = document.createElement('img');
            img.src = topSrc + '?v=' + ASSET_VER;
            img.className = "exercise-main-img";
            content.appendChild(img);
        } else {
            content.appendChild(document.createTextNode(label));
        }

        if (hasIcon) {
            const img = document.createElement('img');
            img.src = iconSrc + '?v=' + ASSET_VER;
            img.className = "exercise-icon-img";
            content.appendChild(img);
        }
    }

    async function selectWordExercise(word) {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        isSoundMixingMode  = false;
        isWordSyllableMode = true;

        grid.classList.remove('vertical-layout');

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) { title.style.display = 'none'; title.textContent = ''; }
        if (wrap)  wrap.innerHTML = '';

        const count = getExerciseCount();

        if (!wordAssignments || wordAssignments.length !== count || grid.children.length !== count) {
            wordAssignments = new Array(count).fill(null);
            grid.innerHTML = '';

            if (btnSuccess) btnSuccess.disabled = true;

            for (let i = 0; i < count; i++) {
                const card = document.createElement('div');
                card.className = 'exercise-card';

                const content = document.createElement('div');
                content.className = 'exercise-content';

                const span = document.createElement('span');
                span.textContent = 'Choose word';
                span.classList.add('text-muted');
                content.appendChild(span);

                const actions = document.createElement('div');
                actions.className = 'exercise-card-actions';

                const changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'change-card-btn';
                changeBtn.textContent = 'Change Card';
                changeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    handleChangeCard(card);
                });
                actions.appendChild(changeBtn);

                card.appendChild(content);
                card.appendChild(actions);
                grid.appendChild(card);

                wireExerciseCardClick(card);
            }
        }

        let idx = wordAssignments.findIndex(x => x === null);
        if (idx === -1) idx = count - 1;

        wordAssignments[idx] = word;

        const topSrc = WORD_IMAGES[word.toLowerCase()] || await firstExistingImage(`${IMG_BASE}top_${word.toLowerCase()}`);

        const cards   = grid.querySelectorAll('.exercise-card');
        const card    = cards[idx];
        const content = card.querySelector('.exercise-content');
        content.innerHTML = '';

        if (topSrc) {
            const topImg = document.createElement('img');
            topImg.src = topSrc + '?v=' + ASSET_VER;
            topImg.alt = word;
            topImg.className = 'exercise-main-img';
            content.appendChild(topImg);
        } else {
            const span = document.createElement('span');
            span.textContent = word;
            content.appendChild(span);
        }

        const wordSpan = document.createElement('span');
        wordSpan.className = 'cv-parts-text';
        wordSpan.textContent = word;
        content.appendChild(wordSpan);

        // Per-card Syllables button (only if breakdown exists)
        const actions = card.querySelector('.exercise-card-actions');
        if (actions) {
            const existing = actions.querySelector('.syllable-btn');
            if (existing) existing.remove();

            if (WORD_SYLLABLE_MAP[word]) {
                const sylBtn = document.createElement('button');
                sylBtn.type = 'button';
                sylBtn.className = 'change-card-btn syllable-btn';
                sylBtn.textContent = 'Syllables';
                sylBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    renderSyllableExercise(word);
                });
                actions.appendChild(sylBtn);
            }
        }
    }

    async function restoreWordGrid() {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid || !wordAssignments) return;

        isSoundMixingMode  = false;
        isWordSyllableMode = true;
        isCVBlendingMode   = false;
        cardSelectEnabled  = false;
        selectedCardIndex  = null;

        grid.classList.remove('vertical-layout');
        grid.innerHTML = '';

        const title = document.getElementById('choices-title');
        const wrap  = document.getElementById('choices-wrap');
        if (title) { title.style.display = 'none'; title.textContent = ''; }
        if (wrap)  wrap.innerHTML = '';

        if (btnSuccess) btnSuccess.disabled = true;

        for (let i = 0; i < wordAssignments.length; i++) {
            const word = wordAssignments[i];
            const card = document.createElement('div');
            card.className = 'exercise-card';

            const content = document.createElement('div');
            content.className = 'exercise-content';

            if (word) {
                const topSrc = WORD_IMAGES[word.toLowerCase()] || await firstExistingImage(`${IMG_BASE}top_${word.toLowerCase()}`);
                if (topSrc) {
                    const topImg = document.createElement('img');
                    topImg.src = topSrc + '?v=' + ASSET_VER;
                    topImg.alt = word;
                    topImg.className = 'exercise-main-img';
                    content.appendChild(topImg);
                } else {
                    const span = document.createElement('span');
                    span.textContent = word;
                    content.appendChild(span);
                }
                const wordSpan = document.createElement('span');
                wordSpan.textContent = word;
                content.appendChild(wordSpan);
            } else {
                const span = document.createElement('span');
                span.textContent = 'Choose word';
                span.classList.add('text-muted');
                content.appendChild(span);
            }

            const actions = document.createElement('div');
            actions.className = 'exercise-card-actions';

            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
            changeBtn.className = 'change-card-btn';
            changeBtn.textContent = 'Change Card';
            changeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleChangeCard(card);
            });
            actions.appendChild(changeBtn);

            if (word && WORD_SYLLABLE_MAP[word]) {
                const sylBtn = document.createElement('button');
                sylBtn.type = 'button';
                sylBtn.className = 'change-card-btn syllable-btn';
                sylBtn.textContent = 'Syllables';
                sylBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    renderSyllableExercise(word);
                });
                actions.appendChild(sylBtn);
            }

            card.appendChild(content);
            card.appendChild(actions);
            grid.appendChild(card);

            wireExerciseCardClick(card);
        }
    }

    function prettyLabel(item){
        if (item.type==='cv') return item.id.replace('-', ' + ');
        return item.id;
    }

    $('#btnBack').on('click', function(){ if (currentIndex>0){ currentIndex--; renderExercise(SEQUENCE[currentIndex]); }});
    $('#btnNext').on('click', function(){ if (currentIndex<SEQUENCE.length-1){ currentIndex++; renderExercise(SEQUENCE[currentIndex]); }});

    // ---- Video Tutorial slot map (injected from PHP) ----
    const VIDEO_SLOT_MAP = <?= json_encode($videoSlotMap) ?>;

    function ytEmbedUrl(url) {
        try {
            const u = new URL(url);
            let id = null;
            if (u.hostname.includes('youtu.be')) id = u.pathname.slice(1);
            else if (u.hostname.includes('youtube.com')) id = u.searchParams.get('v') || (u.pathname.startsWith('/embed/') ? u.pathname.split('/')[2] : null);
            if (id) return 'https://www.youtube.com/embed/' + id + '?autoplay=1';
            if (u.hostname.includes('vimeo.com')) return 'https://player.vimeo.com/video' + u.pathname + '?autoplay=1';
        } catch(e) {}
        return null;
    }

    function updateVideoBtnForPanel(panelId) {
        const btn  = document.getElementById('btnVideo');
        const wrap = btn ? btn.closest('.mt-3') : null;
        if (!btn) return;
        const hasVideo = panelId && VIDEO_SLOT_MAP[panelId];
        if (wrap) wrap.style.display = hasVideo ? '' : 'none';
    }

    $('#btnVideo').on('click', function () {
        const video = VIDEO_SLOT_MAP[activePanelId];
        if (!video) return;
        const wrap = document.getElementById('videoPlayerWrap');
        document.getElementById('videoTitle').textContent = video.title || 'Video Example';
        const embedUrl = video.source_type === 'url' ? ytEmbedUrl(video.video_path) : null;
        if (embedUrl) {
            wrap.innerHTML = `<iframe src="${embedUrl}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:100%;border:0;"></iframe>`;
        } else {
            wrap.innerHTML = `<video src="${video.video_path}" controls autoplay style="width:100%;height:100%;border-radius:4px;"></video>`;
        }
        const modal = new bootstrap.Modal('#videoModal');
        modal.show();
        // Stop playback when modal closes
        document.getElementById('videoModal').addEventListener('hidden.bs.modal', () => {
            wrap.innerHTML = '';
        }, { once: true });
    });

    // SUCCESS BUTTON - Updated to use custom media or default
    $('#btnSuccess').on('click', function () {
        if (selectedSuccessMedia) {
            playSuccessMedia();
        } else {
            new bootstrap.Modal('#successModal').show();
        }
    });

    const successModalEl = document.getElementById('successModal');
    if (successModalEl) {
        successModalEl.addEventListener('hidden.bs.modal', function () {
            resetCardsAfterSuccess();
        });
    }

    function initExerciseGrid() {
        const grid       = document.getElementById('exercise-view');
        const btnSuccess = document.getElementById('btnSuccess');
        if (!grid) return;

        const count = getExerciseCount();

        cardAssignments    = new Array(count).fill(null);
        selectedCardIndex  = null;
        grid.innerHTML     = '';

        for (let i = 0; i < count; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';

            const box = document.createElement('div');
            box.className = 'exercise-box';

            const content = document.createElement('div');
            content.className = 'exercise-content';

            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);

            box.appendChild(content);
            card.appendChild(box);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'change-card-btn';
            btn.textContent = 'Select Card';

            (function(index) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setSelectedCard(index);
                });

                box.addEventListener('click', function () {
                    const btnSuccess = document.getElementById('btnSuccess');

                    if (!cardAssignments[index]) return;
                    if (card.classList.contains('completed')) return;

                    card.classList.add('completed');

                    const remaining = document.querySelectorAll('#exercise-view .exercise-card:not(.completed)').length;
                    if (remaining === 0 && btnSuccess) {
                        btnSuccess.disabled = false;
                    }
                });
            })(i);

            card.appendChild(btn);
            grid.appendChild(card);
        }

        if (btnSuccess) btnSuccess.disabled = true;
    }

    function setSelectedCard(index) {
        selectedCardIndex = index;

        const cards = document.querySelectorAll('#exercise-view .exercise-card');
        cards.forEach((c, i) => {
            if (i === index) c.classList.add('selected');
            else c.classList.remove('selected');
        });

        cardAssignments[index] = null;

        const card = cards[index];
        if (!card) return;

        card.classList.remove('completed');

        const content = card.querySelector('.exercise-content');
        if (content) {
            content.innerHTML = '';
            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);
        }

        const btnSuccess = document.getElementById('btnSuccess');
        if (btnSuccess) btnSuccess.disabled = true;
    }

    function handleItemSelection(item) {
        if (isCVBlendingMode) return;
        const grid = document.getElementById('exercise-view');
        if (!grid) return;

        const desiredCount = getExerciseCount();

        if (cardAssignments.length !== desiredCount ||
            grid.querySelectorAll('.exercise-card').length !== desiredCount) {
            initExerciseGrid();
        }

        let index = (selectedCardIndex !== null)
            ? selectedCardIndex
            : cardAssignments.findIndex(x => x === null);

        if (index === -1) {
            index = 0;
        }

        cardAssignments[index] = item;
        renderCardAtIndex(index, item);
    }

    function wireExerciseCardClick(card) {
        const btnSuccess = document.getElementById('btnSuccess');
        if (!card) return;

        card.addEventListener('click', function () {
            if (card.classList.contains('completed')) return;

            card.classList.add('completed');

            const remaining = document.querySelectorAll('#exercise-view .exercise-card:not(.completed)').length;

            if (remaining === 0 && btnSuccess) {
                btnSuccess.disabled = false;
            }
        });
    }

    /* ========================================
   ASSIGNMENT SYSTEM
   ======================================== */

    let assignmentsTable = null;
    let currentAssignmentId = null;
    let currentAssignmentExercises = [];
    let modalExerciseCards = [];
    let modalSelectedType = null;
    let isEditingAssignment = false;

    let currentAssignmentData = null;
    let currentAssignmentExerciseIndex = 0;
    let isPlayingAssignment = false;

    // Initialize assignments when DOM loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeAssignments();
        loadContent();
        // Add this in DOMContentLoaded for debugging
        console.log('=== DEBUGGING ===');
        console.log('selectedSuccessMedia:', selectedSuccessMedia);
        console.log('CONSONANTS loaded:', CONSONANTS.length);
        console.log('VOWELS loaded:', VOWELS.length);
        console.log('CV_BLEND_ITEMS loaded:', CV_BLEND_ITEMS.length);

        const sel = document.getElementById('exerciseCountSelect');
        if (sel) {
            sel.addEventListener('change', function () {
                initExerciseGrid();
            });
        }
    });

    function initializeAssignments() {
        checkUserAssignmentPermissions();

        // Only add listener if button exists (for enterprise_admin/super_user)
        const btnCreate = document.getElementById('btnCreateAssignment');
        if (btnCreate) {
            btnCreate.addEventListener('click', openCreateAssignmentModal);
        }

        const btnSave = document.getElementById('btnSaveAssignment');
        if (btnSave) {
            btnSave.addEventListener('click', saveAssignment);
        }

        const btnSaveEx = document.getElementById('btnSaveExercise');
        if (btnSaveEx) {
            btnSaveEx.addEventListener('click', saveExerciseToAssignment);
        }

        const btnClear = document.getElementById('btnClearExercise');
        if (btnClear) {
            btnClear.addEventListener('click', clearExerciseBuilder);
        }

        const btnUnassign = document.getElementById('btnUnassignAll');
        if (btnUnassign) {
            btnUnassign.addEventListener('click', unassignAllUsers);
        }

        document.querySelectorAll('.modal-category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.dataset.type;
                handleModalCategoryClick(type);
            });
        });

        document.querySelectorAll('input[name="modalOrientation"]').forEach(radio => {
            radio.addEventListener('change', updateModalPreviewOrientation);
        });

        const modalCardCount = document.getElementById('modalCardCount');
        if (modalCardCount) {
            modalCardCount.addEventListener('change', function() {
                if (modalSelectedType) {
                    rebuildModalPreview();
                }
            });
        }
    }


  function initializeAssignmentsTable() {
        assignmentsTable = $('#assignmentsTable').DataTable({
            ajax: {
                url: '/dashboards/api/admin/get_assignments.php',
                dataSrc: 'data'
            },
            columns: [
                {
                    data: 'assignment_name',
                    render: function(data, type, row) {
                        return `<a href="#" class="assignment-title-link" data-id="${row.assignment_group_id}">${data}</a>`;
                    }
                },
                { data: 'assignment_description' },
                {
                    data: 'created_at',
                    render: function(data) {
                        return new Date(data).toLocaleDateString();
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        let html = '<div class="btn-group btn-group-sm">';

                        if (row.is_creator) {
                            html += `<button class="btn btn-sm btn-primary edit-assignment-btn" data-id="${row.assignment_group_id}" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </button>`;
                            html += `<button class="btn btn-sm btn-danger delete-assignment-btn" data-id="${row.assignment_group_id}" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>`;
                        }

                        html += '</div>';
                        return html;
                    }
                }
            ],
            order: [[2, 'desc']],
            pageLength: 10,
            responsive: true
        });

        $('#assignmentsTable').on('click', '.assignment-title-link', function(e) {
            e.preventDefault();
            const assignmentId = $(this).data('id');
            loadAndPlayAssignment(assignmentId);
        });

        $('#assignmentsTable').on('click', '.edit-assignment-btn', function(e) {
            e.stopPropagation();
            const assignmentId = $(this).data('id');
            editAssignment(assignmentId);
        });

        $('#assignmentsTable').on('click', '.delete-assignment-btn', function(e) {
            e.stopPropagation();
            const assignmentId = $(this).data('id');
            deleteAssignment(assignmentId);
        });

        // Mobile: title = assignment name (col 0), hide description (col 1) and date (col 2)
        if (window.MKAMobile) {
            MKAMobile.initTable('#assignmentsTable', {
                titleCol   : 0,
                hideCols   : [1, 2],
                dtInstance : assignmentsTable
            });
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.category-btn');
        if (!btn) return;

        const target = btn.dataset.panel;

        if (target === 'panel-assignments' && !assignmentsTable) {
            initializeAssignmentsTable();
        }
    });

    async function loadAndPlayAssignment(assignmentId) {
        try {
            const response = await fetch(`/dashboards/api/admin/get_assignment.php?assignment_id=${assignmentId}`);
            const data = await response.json();

            if (data.status !== 'success') {
                await showAlert('Error: ' + (data.message || 'Error loading assignment'), 'Error', 'danger');
                return;
            }

            currentAssignmentData = data.data;
            currentAssignmentExerciseIndex = 0;
            isPlayingAssignment = true;

            // LOAD ASSIGNMENT-SPECIFIC SUCCESS MEDIA (for end of assignment only)
            try {
                const mediaResp = await fetch(`/dashboards/api/admin/get_assignment_media.php?assignment_id=${assignmentId}`);
                const mediaData = await mediaResp.json();

                if (mediaData.status === 'success' && mediaData.data) {
                    // Store assignment media separately - don't override selectedSuccessMedia
                    window.assignmentSuccessMedia = mediaData.data;
                    // Save the current default media for use during exercises
                    window.originalSuccessMedia = selectedSuccessMedia;
                    console.log('Loaded assignment-specific media for end:', window.assignmentSuccessMedia);
                    console.log('Using default media during exercises:', window.originalSuccessMedia);
                } else {
                    window.assignmentSuccessMedia = null;
                    console.log('No assignment-specific media');
                }
            } catch (err) {
                console.log('Error loading assignment media:', err);
                window.assignmentSuccessMedia = null;
            }

            document.querySelectorAll('.slide-panel').forEach(p => p.classList.remove('open'));

            playAssignmentExercise(0);

        } catch (error) {
            console.error('Error loading assignment:', error);
            await showAlert('Error: Error loading assignment', 'Error', 'danger');
        }
    }

 async function playAssignmentExercise(exerciseIndex) {
        if (!currentAssignmentData || !currentAssignmentData.exercises[exerciseIndex]) {
            showAssignmentCompletion();
            return;
        }

        const exercise = currentAssignmentData.exercises[exerciseIndex];
        const grid = document.getElementById('exercise-view');
        let btnSuccess = document.getElementById('btnSuccess');

        if (!grid) return;

        grid.innerHTML = '';
        grid.classList.remove('vertical-layout');

        if (exercise.orientation === 'vertical') {
            grid.classList.add('vertical-layout');
        }

        if (btnSuccess) btnSuccess.disabled = true;

        const title = document.getElementById('choices-title');
        const wrap = document.getElementById('choices-wrap');
        if (title) title.style.display = 'none';
        if (wrap) wrap.innerHTML = '';

        for (let i = 0; i < exercise.cards.length; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';

            const content = document.createElement('div');
            content.className = 'exercise-content';
            content.innerHTML = '<span class="text-muted">Loading...</span>';

            card.appendChild(content);
            grid.appendChild(card);

            await renderCardAtIndex(i, exercise.cards[i]);
        }

        const oldSuccessBtn = document.getElementById('btnSuccess');
        if (oldSuccessBtn) {
            const newSuccessBtn = oldSuccessBtn.cloneNode(true);
            oldSuccessBtn.parentNode.replaceChild(newSuccessBtn, oldSuccessBtn);

            btnSuccess = newSuccessBtn;

            newSuccessBtn.addEventListener('click', function() {
                if (!newSuccessBtn.disabled) {
                    handleAssignmentExerciseComplete();
                }
            });
        }

        const cards = grid.querySelectorAll('.exercise-card');
        cards.forEach(card => {
            card.addEventListener('click', function() {
                if (card.classList.contains('completed')) return;

                card.classList.add('completed');

                const remaining = grid.querySelectorAll('.exercise-card:not(.completed)').length;

                if (remaining === 0) {
                    const currentSuccessBtn = document.getElementById('btnSuccess');
                    if (currentSuccessBtn) {
                        currentSuccessBtn.disabled = false;
                    }
                }
            });
        });
    }

    async function unassignUserFromAssignment(assignmentId, userUUID) {
        const confirmed = await showConfirm(
            'Are you sure you want to unassign this user from the assignment?',
            'Unassign User',
            'Unassign',
            'Cancel',
            'danger'
        );
        if (!confirmed) return;

        try {
            const response = await fetch('/dashboards/api/admin/unassign_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    assignment_id: assignmentId,
                    user_uuid: userUUID
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                if (assignmentsTable) {
                    assignmentsTable.ajax.reload();
                }
                updateAssignedUsersDisplay();
            } else {
                await showAlert('Error: ' + (data.message || 'Failed to unassign user'), 'Error', 'danger');
            }
        } catch (error) {
            console.error('Error unassigning user:', error);
            await showAlert('Error: Error unassigning user', 'Error', 'danger');
        }
    }

    async function unassignAllUsers() {
        if (!currentAssignmentId) return;

        const confirmed = await showConfirm(
            'Unassign ALL users from this assignment? This cannot be undone.',
            'Unassign All Users',
            'Unassign',
            'Cancel',
            'danger'
        );
        if (!confirmed) return;

        try {
            const response = await fetch('/dashboards/api/admin/unassign_all_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    assignment_id: currentAssignmentId
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                document.getElementById('assignedUsersList').innerHTML = '';
                document.getElementById('currentlyAssignedUsers').style.display = 'none';
                document.getElementById('btnUnassignAll').style.display = 'none';

                const userSelect = document.getElementById('assignmentUsers');
                if (userSelect) {
                    Array.from(userSelect.options).forEach(opt => opt.selected = false);
                }

                await showAlert('All Users Unassigned Successfully', 'Success', 'Success');
            } else {
                await showAlert('Error: Failed to unassign users', 'Error', 'danger');
            }
        } catch (error) {
            console.error('Error unassigning all users:', error);
            await showAlert('Error unassigning all users', 'Error', 'danger');
        }
    }

    async function updateAssignedUsersDisplay() {
        if (!isEditingAssignment || !currentAssignmentId) {
            document.getElementById('currentlyAssignedUsers').style.display = 'none';
            document.getElementById('btnUnassignAll').style.display = 'none';
            return;
        }

        fetch(`/dashboards/api/admin/get_assignment.php?assignment_id=${currentAssignmentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data.assigned_users) {
                    displayAssignedUsers(data.data.assigned_users);
                }
            })
            .catch(error => {
                console.error('Error fetching assigned users:', error);
            });
    }

    function displayAssignedUsers(assignedUserUUIDs) {
        const listDiv = document.getElementById('assignedUsersList');
        const containerDiv = document.getElementById('currentlyAssignedUsers');
        const unassignAllBtn = document.getElementById('btnUnassignAll');
        const userSelect = document.getElementById('assignmentUsers');

        if (!assignedUserUUIDs || assignedUserUUIDs.length === 0) {
            containerDiv.style.display = 'none';
            unassignAllBtn.style.display = 'none';

            if (userSelect) {
                Array.from(userSelect.options).forEach(opt => opt.selected = false);
            }
            return;
        }

        containerDiv.style.display = 'block';
        unassignAllBtn.style.display = 'inline-block';
        listDiv.innerHTML = '';

        const allOptions = Array.from(userSelect.options);

        allOptions.forEach(opt => opt.selected = false);

        assignedUserUUIDs.forEach(uuid => {
            const option = allOptions.find(opt => opt.value === uuid);
            if (option) {
                option.selected = true;

                const badge = document.createElement('span');
                badge.className = 'badge bg-success white-badge-text';
                badge.innerHTML = `${option.textContent} <i class="fa fa-times ms-1" style="cursor: pointer; color:white;"></i>`;

                const removeIcon = badge.querySelector('i');
                removeIcon.addEventListener('click', () => {
                    unassignUserFromAssignment(currentAssignmentId, uuid);
                });

                listDiv.appendChild(badge);
            }
        });
    }

    function handleAssignmentExerciseComplete() {
        const isLastExercise = (currentAssignmentExerciseIndex >= currentAssignmentData.exercises.length - 1);

        // Determine which media to use
        let mediaToShow = null;

        if (isLastExercise && window.assignmentSuccessMedia) {
            // Last exercise - use assignment-specific media if available
            mediaToShow = window.assignmentSuccessMedia;
        } else {
            // During exercises - use default media (could be user's or parent's default)
            mediaToShow = window.originalSuccessMedia || selectedSuccessMedia;
        }

        // Show media if available
        if (mediaToShow) {
            // Temporarily set it as the selected media
            const previousMedia = selectedSuccessMedia;
            selectedSuccessMedia = mediaToShow;

            playSuccessMedia();

            // Restore after showing
            selectedSuccessMedia = previousMedia;

            // Wait for modal to close, then proceed
            const mediaModal = document.querySelector('.modal.show');
            if (mediaModal) {
                mediaModal.addEventListener('hidden.bs.modal', function handler() {
                    mediaModal.removeEventListener('hidden.bs.modal', handler);
                    currentAssignmentExerciseIndex++;
                    setTimeout(() => {
                        playAssignmentExercise(currentAssignmentExerciseIndex);
                    }, 300);
                }, { once: true });
            } else {
                setTimeout(() => {
                    currentAssignmentExerciseIndex++;
                    playAssignmentExercise(currentAssignmentExerciseIndex);
                }, 3500);
            }
        } else {
            // No media - advance directly to next exercise
            currentAssignmentExerciseIndex++;
            setTimeout(() => {
                playAssignmentExercise(currentAssignmentExerciseIndex);
            }, 300);
        }
    }


    function showAssignmentCompletion() {
        const grid = document.getElementById('exercise-view');
        if (!grid) return;

        grid.innerHTML = `
        <div class="text-center py-5">
            <div>
                <button class="btn btn-primary btn-lg" onclick="returnToAssignments()">
                    <i class="fa fa-list"></i> Back to Assignments
                </button>
            </div>
        </div>
    `;

        // Reset state
        isPlayingAssignment = false;
        currentAssignmentData = null;
        currentAssignmentExerciseIndex = 0;

        // RESTORE ORIGINAL SUCCESS MEDIA
        if (window.originalSuccessMedia !== undefined) {
            selectedSuccessMedia = window.originalSuccessMedia;
            delete window.originalSuccessMedia;
            console.log('Restored original success media');
        }

        const btnSuccess = document.getElementById('btnSuccess');
        if (btnSuccess) btnSuccess.disabled = true;
    }

 function returnToAssignments() {
        const assignmentsBtn = document.querySelector('[data-panel="panel-assignments"]');
        if (assignmentsBtn) {
            assignmentsBtn.click();
        }

        resetExerciseArea();
    }

    async function editAssignment(assignmentId) {
        try {
            const response = await fetch(`/dashboards/api/admin/get_assignment.php?assignment_id=${assignmentId}`);
            const data = await response.json();

            if (data.status !== 'success') {
                await showAlert('Error: ' + (data.message || 'Error loading assignment'), 'Error', 'danger');
                return;
            }

            const assignment = data.data;

            isEditingAssignment = true;
            currentAssignmentId = assignmentId;
            currentAssignmentExercises = assignment.exercises;

            document.getElementById('assignmentModalTitle').textContent = 'Edit Assignment';
            document.getElementById('assignmentName').value = assignment.assignment_name;
            document.getElementById('assignmentDescription').value = assignment.assignment_description || '';

            if (assignment.assigned_users && assignment.assigned_users.length > 0) {
                displayAssignedUsers(assignment.assigned_users);
            }

            const userSelect = document.getElementById('assignmentUsers');
            if (userSelect && assignment.assigned_users) {
                Array.from(userSelect.options).forEach(opt => {
                    opt.selected = assignment.assigned_users.includes(opt.value);
                });
            }

            updateExerciseList();
            clearExerciseBuilder();

            new bootstrap.Modal('#assignmentModal').show();

        } catch (error) {
            console.error('Error loading assignment for edit:', error);
            await showAlert('Error loading assignment', 'Error', 'danger');
        }
    }

    async function deleteAssignment(assignmentId) {
        const confirmed = await showConfirm(
            'Are you sure you want to delete this assignment?',
            'Delete Assignment',
            'Delete',
            'Cancel',
            'danger'
        );
        if (!confirmed) return;

        try {
            const response = await fetch('/dashboards/api/admin/delete_assignment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assignment_id: assignmentId })
            });

            const data = await response.json();

            if (data.status === 'success') {
                assignmentsTable.ajax.reload();
                await showAlert('Assignment deleted successfully', 'Success', 'success');
            } else {
                await showAlert('Error: ' + (data.message || 'Failed to delete assignment'), 'Error', 'danger');
            }
        } catch (error) {
            console.error('Error deleting assignment:', error);
            await showAlert('Error deleting assignment', 'Error', 'danger');
        }
    }

    async function checkUserAssignmentPermissions() {
        try {
            const response = await fetch('/dashboards/api/admin/check_permissions.php');
            const data = await response.json();

            if (data.can_assign_users) {
                document.getElementById('assignmentUsersWrapper').style.display = 'block';
                loadAffiliatedUsers();
            }
        } catch (error) {
            console.error('Error checking permissions:', error);
        }
    }

    async function loadAffiliatedUsers() {
        try {
            const response = await fetch('/dashboards/api/admin/get_affiliated_users.php');
            const data = await response.json();

            if (data.status === 'success') {
                const select = document.getElementById('assignmentUsers');
                select.innerHTML = '';

                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.UserUUID;
                    option.textContent = user.Name + ' (' + user.Email + ')';
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    function openCreateAssignmentModal() {
        isEditingAssignment = false;
        currentAssignmentId = null;
        currentAssignmentExercises = [];

        document.getElementById('assignmentModalTitle').textContent = 'Create Assignment';
        document.getElementById('assignmentName').value = '';
        document.getElementById('assignmentDescription').value = '';
        document.getElementById('exerciseName').value = '';

        const userSelect = document.getElementById('assignmentUsers');
        if (userSelect) {
            Array.from(userSelect.options).forEach(opt => opt.selected = false);
        }

        updateExerciseList();
        clearExerciseBuilder();

        new bootstrap.Modal('#assignmentModal').show();
    }

    function handleModalCategoryClick(type) {
        modalSelectedType = type;

        document.querySelectorAll('.modal-category-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        event.target.classList.add('active');

        showModalSelectionPanel(type);

        initializeModalPreview();
    }

    function showModalSelectionPanel(type) {
        const panel = document.getElementById('modalSelectionPanel');
        panel.innerHTML = '';
        panel.style.display = 'block';

        if (type === 'letters') {
            const consHeader = document.createElement('div');
            consHeader.className = 'pill-section-header';
            consHeader.textContent = 'Consonants';
            panel.appendChild(consHeader);

            const consWrap = document.createElement('div');
            consWrap.className = 'd-flex flex-wrap';
            CONSONANTS.forEach(c => {
                const src = CONSONANT_IMAGES[c] || `${IMG_BASE}consonant_${c}.png`;
                consWrap.appendChild(
                    buttonPillImage(src, c, () => {
                        addCardToModalExercise({ type: "consonant", id: c });
                    })
                );
            });
            panel.appendChild(consWrap);

            const vowHeader = document.createElement('div');
            vowHeader.className = 'pill-section-header';
            vowHeader.textContent = 'Vowels';
            panel.appendChild(vowHeader);

            const vowWrap = document.createElement('div');
            vowWrap.className = 'd-flex flex-wrap';
            VOWELS.forEach(v => {
                const src = VOWEL_IMAGES[v.code] || `${IMG_BASE}vowel_${v.code}.png`;
                vowWrap.appendChild(
                    buttonPillImage(src, v.label, () => {
                        addCardToModalExercise({ type: "vowel", id: v.code });
                    })
                );
            });
            panel.appendChild(vowWrap);

        } else if (type === 'cv') {
            const wrap = document.createElement('div');
            wrap.className = 'd-flex flex-wrap';
            CV_BLEND_ITEMS.forEach(item => {
                const label = `${item.c} + ${item.v}`;
                wrap.appendChild(
                    buttonPill(label, () => {
                        addCardToModalExercise({ type: "cv", id: `${item.c}-${item.v}` });
                    })
                );
            });
            panel.appendChild(wrap);

        } else if (type === '3cv') {
            const wrap = document.createElement('div');
            wrap.className = 'd-flex flex-wrap';
            BLEND_3CV_ITEMS.forEach(item => {
                const label = `${item.c}${item.v}`;
                wrap.appendChild(
                    buttonPill(label, () => {
                        addCardToModalExercise({ type: "3cv", id: `${item.c}-${item.v}` });
                    })
                );
            });
            panel.appendChild(wrap);

        } else if (type === 'soundmixing') {
            const wrap = document.createElement('div');
            wrap.className = 'd-flex flex-wrap';
            CV_BLEND_ITEMS.forEach(item => {
                const label = `${item.c}-${item.v}`;
                wrap.appendChild(
                    buttonPill(label, () => {
                        addCardToModalExercise({ type: "cv", id: `${item.c}-${item.v}` });
                    })
                );
            });
            panel.appendChild(wrap);

        } else if (type === 'wordsyllable') {
            const wrap = document.createElement('div');
            wrap.className = 'd-flex flex-wrap';
            WORDS.forEach(w => {
                wrap.appendChild(
                    buttonPill(w, () => {
                        addCardToModalExercise({ type: "word", id: w });
                    })
                );
            });
            panel.appendChild(wrap);
        }
    }

    function initializeModalPreview() {
        const preview = document.getElementById('modalExercisePreview');
        const count = parseInt(document.getElementById('modalCardCount').value);

        modalExerciseCards = new Array(count).fill(null);

        preview.innerHTML = '';
        preview.className = 'border rounded p-3 mb-3';

        const orientation = document.querySelector('input[name="modalOrientation"]:checked').value;
        if (orientation === 'vertical') {
            preview.classList.add('vertical-layout');
        }

        for (let i = 0; i < count; i++) {
            const card = document.createElement('div');
            card.className = 'exercise-card';
            card.dataset.index = i;

            const box = document.createElement('div');
            box.className = 'exercise-box';

            const content = document.createElement('div');
            content.className = 'exercise-content';

            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);

            box.appendChild(content);
            card.appendChild(box);
            preview.appendChild(card);
        }

        document.getElementById('btnSaveExercise').disabled = true;
    }

    async function addCardToModalExercise(item) {
        const count = parseInt(document.getElementById('modalCardCount').value);

        let idx = modalExerciseCards.findIndex(x => x === null);
        if (idx === -1) {
            idx = count - 1;
        }

        modalExerciseCards[idx] = item;

        await renderModalCard(idx, item);

        const allFilled = modalExerciseCards.every(c => c !== null);
        document.getElementById('btnSaveExercise').disabled = !allFilled;
    }

    async function renderModalCard(index, item) {
        const preview = document.getElementById('modalExercisePreview');
        const cards = preview.querySelectorAll('.exercise-card');
        const card = cards[index];

        if (!card) return;

        const content = card.querySelector('.exercise-content');
        content.innerHTML = '';

        const label = (item.type === 'word') ? item.id : prettyLabel(item);

        if (item.type === 'consonant' || item.type === 'vowel') {
            let topSrc = topPathFor(item);
            let iconSrc = iconPathFor(item);

            const hasTop = await loadImage(topSrc);
            const hasIcon = await loadImage(iconSrc);

            if (hasTop) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            if (hasIcon) {
                const iconImg = document.createElement('img');
                iconImg.src = iconSrc + '?v=' + ASSET_VER;
                iconImg.alt = label + " icon";
                iconImg.className = 'exercise-icon-img';
                content.appendChild(iconImg);
            }
        } else if (item.type === 'word') {
            const word = item.id;
            const topSrc = WORD_IMAGES[word.toLowerCase()] || await firstExistingImage(`${IMG_BASE}top_${word.toLowerCase()}`);

            if (topSrc) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = word;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = word;
                content.appendChild(span);
            }

            const left = word.slice(0, 2);
            const right = word.slice(2);

            const bottomRow = document.createElement('div');
            bottomRow.className = 'word-bottom-row';

            const leftSpan = document.createElement('span');
            leftSpan.className = 'word-part';
            leftSpan.textContent = left;

            const plusSpan = document.createElement('span');
            plusSpan.className = 'word-plus';
            plusSpan.textContent = '+';

            const rightSpan = document.createElement('span');
            rightSpan.className = 'word-part';
            rightSpan.textContent = right;

            bottomRow.appendChild(leftSpan);
            bottomRow.appendChild(plusSpan);
            bottomRow.appendChild(rightSpan);

            content.appendChild(bottomRow);
        } else if (item.type === 'cv' || item.type === '3cv') {
            const [cons, vowelCode] = item.id.split('-');
            const baseId = `${cons}_${vowelCode}`;

            let topBase = `${IMG_BASE}top_cv_${baseId}`;
            if (item.type === '3cv') {
                topBase = `${IMG_BASE}top_3cv_${baseId}`;
            }

            const topSrc = (item.type === '3cv' ? CV_IMAGES[item.id] : null) || await firstExistingImage(topBase);
            const consSrc = CONSONANT_IMAGES[cons] || await firstExistingImage(`${IMG_BASE}consonant_${cons}`);
            const vowelSrc = VOWEL_IMAGES[vowelCode] || await firstExistingImage(`${IMG_BASE}vowel_${vowelCode}`);

            if (topSrc) {
                const topImg = document.createElement('img');
                topImg.src = topSrc + '?v=' + ASSET_VER;
                topImg.alt = label;
                topImg.className = 'exercise-main-img';
                content.appendChild(topImg);
            } else {
                const span = document.createElement('span');
                span.textContent = label;
                content.appendChild(span);
            }

            const bottomRow = document.createElement('div');
            bottomRow.className = 'cv-bottom-row';

            if (consSrc) {
                const cImg = document.createElement('img');
                cImg.src = consSrc + '?v=' + ASSET_VER;
                cImg.alt = `Consonant ${cons}`;
                bottomRow.appendChild(cImg);
            } else {
                const cText = document.createElement('span');
                cText.textContent = cons;
                bottomRow.appendChild(cText);
            }

            if (vowelSrc) {
                const vImg = document.createElement('img');
                vImg.src = vowelSrc + '?v=' + ASSET_VER;
                vImg.alt = `Vowel ${vowelCode}`;
                bottomRow.appendChild(vImg);
            } else {
                const vText = document.createElement('span');
                vText.textContent = vowelCode;
                bottomRow.appendChild(vText);
            }

            content.appendChild(bottomRow);
        }
    }

    function updateModalPreviewOrientation() {
        const preview = document.getElementById('modalExercisePreview');
        const orientation = document.querySelector('input[name="modalOrientation"]:checked').value;

        if (orientation === 'vertical') {
            preview.classList.add('vertical-layout');
        } else {
            preview.classList.remove('vertical-layout');
        }
    }

    function rebuildModalPreview() {
        const currentCards = [...modalExerciseCards];

        initializeModalPreview();

        const newCount = parseInt(document.getElementById('modalCardCount').value);
        for (let i = 0; i < Math.min(currentCards.length, newCount); i++) {
            if (currentCards[i]) {
                modalExerciseCards[i] = currentCards[i];
                renderModalCard(i, currentCards[i]);
            }
        }
    }

    function clearExerciseBuilder() {
        modalSelectedType = null;
        modalExerciseCards = [];

        document.getElementById('exerciseName').value = '';
        document.getElementById('modalSelectionPanel').style.display = 'none';
        document.getElementById('modalSelectionPanel').innerHTML = '';

        const preview = document.getElementById('modalExercisePreview');
        preview.className = 'border rounded p-3 mb-3';
        preview.innerHTML = '<div class="text-muted text-center py-5">Select a category and build your exercise</div>';

        document.querySelectorAll('.modal-category-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        document.getElementById('btnSaveExercise').disabled = true;
    }

    async function saveExerciseToAssignment() {
        const exerciseName = document.getElementById('exerciseName').value.trim();

        if (!exerciseName) {
            await showAlert('Please enter an exercise name', 'Error', 'danger');
            return;
        }

        const allFilled = modalExerciseCards.every(c => c !== null);
        if (!allFilled) {
            await showAlert('Please fill all card slots', 'Error', 'danger');
            return;
        }

        const cardCount = parseInt(document.getElementById('modalCardCount').value);
        const orientation = document.querySelector('input[name="modalOrientation"]:checked').value;

        const exercise = {
            exercise_name: exerciseName,
            card_count: cardCount,
            orientation: orientation,
            cards: [...modalExerciseCards]
        };

        currentAssignmentExercises.push(exercise);
        updateExerciseList();
        clearExerciseBuilder();
    }

    function updateExerciseList() {
        const listDiv = document.getElementById('exerciseList');

        if (currentAssignmentExercises.length === 0) {
            listDiv.innerHTML = '<div class="text-muted text-center py-3" id="exerciseListEmpty">No exercises added yet</div>';
            return;
        }

        listDiv.innerHTML = '';

        currentAssignmentExercises.forEach((ex, index) => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.dataset.index = index;

            const leftSide = document.createElement('div');
            leftSide.className = 'd-flex align-items-center';

            const handle = document.createElement('i');
            handle.className = 'fa fa-grip-vertical exercise-list-item-handle me-2';
            leftSide.appendChild(handle);

            const nameSpan = document.createElement('span');
            nameSpan.textContent = `${index + 1}. ${ex.exercise_name}`;
            nameSpan.className = 'fw-bold';
            leftSide.appendChild(nameSpan);

            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary ms-2';
            badge.textContent = `${ex.card_count} cards • ${ex.orientation}`;
            leftSide.appendChild(badge);

            const btnGroup = document.createElement('div');
            btnGroup.className = 'btn-group btn-group-sm';

            const upBtn = document.createElement('button');
            upBtn.className = 'btn btn-outline-secondary';
            upBtn.innerHTML = '<i class="fa fa-arrow-up"></i>';
            upBtn.onclick = () => moveExercise(index, -1);
            if (index === 0) upBtn.disabled = true;

            const downBtn = document.createElement('button');
            downBtn.className = 'btn btn-outline-secondary';
            downBtn.innerHTML = '<i class="fa fa-arrow-down"></i>';
            downBtn.onclick = () => moveExercise(index, 1);
            if (index === currentAssignmentExercises.length - 1) downBtn.disabled = true;

            const delBtn = document.createElement('button');
            delBtn.className = 'btn btn-outline-danger';
            delBtn.innerHTML = '<i class="fa fa-trash"></i>';
            delBtn.onclick = () => deleteExercise(index);

            btnGroup.appendChild(upBtn);
            btnGroup.appendChild(downBtn);
            btnGroup.appendChild(delBtn);

            item.appendChild(leftSide);
            item.appendChild(btnGroup);
            listDiv.appendChild(item);
        });
    }

    function moveExercise(index, direction) {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= currentAssignmentExercises.length) return;

        const temp = currentAssignmentExercises[index];
        currentAssignmentExercises[index] = currentAssignmentExercises[newIndex];
        currentAssignmentExercises[newIndex] = temp;

        updateExerciseList();

        if (isEditingAssignment && currentAssignmentId) {
            autoSaveExerciseOrder();
        }
    }

    async function deleteExercise(index) {
        const confirmed = await showConfirm(
            'Delete This Exercise?',
            'Delete Exercise',
            'Delete',
            'Cancel',
            'danger'
        );
        if (!confirmed) return;

        currentAssignmentExercises.splice(index, 1);
        updateExerciseList();

        if (isEditingAssignment && currentAssignmentId) {
            autoSaveAfterDelete();
        }
    }

    async function autoSaveExerciseOrder() {
        console.log('Auto-saving exercise order...');
    }

    async function autoSaveAfterDelete() {
        console.log('Auto-saving after delete...');
    }

    async function saveAssignment() {
        const name = document.getElementById('assignmentName').value.trim();
        const description = document.getElementById('assignmentDescription').value.trim();

        if (!name) {
            await showAlert('Please enter assignment name', 'Error', 'danger');
            return;
        }

        if (currentAssignmentExercises.length === 0) {
            await showAlert('Error: Please add at least one exercise to the assignment', 'Error', 'danger');
            return;
        }

        const userSelect = document.getElementById('assignmentUsers');
        const assignedUsers = userSelect ? Array.from(userSelect.selectedOptions).map(opt => opt.value) : [];

        const payload = {
            assignment_name: name,
            assignment_description: description,
            exercises: currentAssignmentExercises,
            assigned_users: assignedUsers
        };

        if (isEditingAssignment && currentAssignmentId) {
            payload.assignment_id = currentAssignmentId;
        }

        try {
            const response = await fetch('/dashboards/api/admin/save_assignment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.status === 'success') {
                bootstrap.Modal.getInstance('#assignmentModal').hide();

                if (assignmentsTable) {
                    assignmentsTable.ajax.reload();
                }

                await showAlert(
                    isEditingAssignment ? 'Assignment updated successfully!' : 'Assignment saved successfully!',
                    'Success',
                    'success'
                );
                isEditingAssignment = false;
                currentAssignmentId = null;
            } else {
                await showAlert('Error: ' + (data.message || 'Failed to save assignment'), 'Error', 'danger');
            }
        } catch (error) {
            console.error('Error saving assignment:', error);
            await showAlert('Error: ' + (error.message || 'Error saving assignment'), 'Error', 'danger');
        }
    }

    function getCardIndex(card) {
        const grid  = document.getElementById('exercise-view');
        if (!grid) return -1;
        const cards = Array.from(grid.querySelectorAll('.exercise-card'));
        return cards.indexOf(card);
    }

    function handleChangeCard(card) {
        const btnSuccess = document.getElementById('btnSuccess');
        const idx        = getCardIndex(card);
        if (idx === -1) return;

        card.classList.remove('completed');

        if (btnSuccess) {
            btnSuccess.disabled = true;
        }

        const content = card.querySelector('.exercise-content');
        if (!content) return;

        if (isSoundMixingMode) {
            if (soundMixAssignments && soundMixAssignments.length > idx) {
                soundMixAssignments[idx] = null;
            }

            content.innerHTML = "";
            const span = document.createElement('span');
            span.textContent = 'Choose sound';
            span.classList.add('text-muted');
            content.appendChild(span);

        } else if (isWordSyllableMode) {
            if (wordAssignments && wordAssignments.length > idx) {
                wordAssignments[idx] = null;
            }

            content.innerHTML = "";
            const span = document.createElement('span');
            span.textContent = 'Choose word';
            span.classList.add('text-muted');
            content.appendChild(span);

        } else {
            if (currentExerciseItem && standardAssignments.length > idx) {
                let newItem = null;

                if (currentExerciseItem.type === "consonant") {
                    const options = CONSONANTS.filter(x => x !== standardAssignments[idx].id);
                    const choice = options[Math.floor(Math.random() * options.length)];
                    newItem = {type: "consonant", id: choice};
                }

                if (currentExerciseItem.type === "vowel") {
                    const options = VOWELS.filter(v => v.code !== standardAssignments[idx].id);
                    const choice = options[Math.floor(Math.random() * options.length)];
                    newItem = {type: "vowel", id: choice.code};
                }

                if (currentExerciseItem.type === "cv") {
                    const options = [];
                    CONSONANTS.forEach(c =>
                        VOWELS.forEach(v =>
                            options.push({type:"cv", id:`${c}-${v.code}`})
                        )
                    );

                    const filtered = options.filter(o => o.id !== standardAssignments[idx].id);
                    newItem = filtered[Math.floor(Math.random() * filtered.length)];
                }

                if (currentExerciseItem.type === "3cv") {
                    const options = [];
                    CONSONANTS.forEach(c =>
                        VOWELS.forEach(v =>
                            options.push({type:"3cv", id:`${c}-${v.code}`})
                        )
                    );

                    const filtered = options.filter(o => o.id !== standardAssignments[idx].id);
                    newItem = filtered[Math.floor(Math.random() * filtered.length)];
                }

                standardAssignments[idx] = newItem;
                renderSingleStandardCard(card, newItem);
            }
        }
    }
</script>
</body>

</html>
