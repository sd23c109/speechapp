<?php
$GLOBALS['current_dashboard'] = 'speechapp';
include('../../dashboards/_init.php');
include('_menu_loader.php');

require_once '/opt/mka/core/Admin/ContentManagement.php';
use MKA\Admin\ContentManagement;

$userUuid          = $_SESSION['user_data']['user_uuid'] ?? null;
$stats             = ContentManagement::getContentStats($userUuid);
$isSuperUser       = ($_SESSION['user_data']['user_type'] ?? '') === 'super_user';
$isEnterpriseAdmin = ($_SESSION['user_data']['user_type'] ?? '') === 'enterprise_admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MKAdvantage – Content Management</title>

    <link rel="shortcut icon" href="/dashboards/img/favicon.ico">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/style.min.css?v=<?= ASSET_VER ?>" rel="stylesheet" type="text/css">
    <link href="plugins/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css">

    <script src="plugins/jquery/js/jquery.min.js"></script>

    <style>
        /* ---- Progress bar ---- */
        .progress-bar-custom { height: 8px; border-radius: 4px; background: #e5e7eb; overflow: hidden; }
        .progress-fill       { height: 100%; background: #10b981; transition: width 0.3s; }
        .progress-fill.warning { background: #f59e0b; }
        .progress-fill.danger  { background: #ef4444; }

        /* ---- Card type badges ---- */
        .content-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .badge-isolation { background: #dbeafe; color: #1e40af; }
        .badge-cv        { background: #fef3c7; color: #92400e; }
        .badge-cvcv      { background: #e0e7ff; color: #3730a3; }
        .badge-words     { background: #dcfce7; color: #166534; }

        /* ---- Thumbnails (shared) ---- */
        .card-thumbnail {
            width: 108px; height: 108px;
            object-fit: contain;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px;
        }
        .thumb-placeholder {
            width: 108px; height: 108px;
            background: #f3f4f6;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            color: #9ca3af; font-size: 1.6rem;
        }

        /* ---- Modal header ---- */
        .modal-header { display: flex !important; align-items: center !important; justify-content: space-between !important; padding: 1rem !important; }
        .modal-header .modal-title { margin: 0; flex: 1; }
        .modal-header .close { padding: 0; margin: 0; margin-left: 1rem; background: transparent; border: 0; font-size: 1.5rem; font-weight: 700; line-height: 1; opacity: .5; }
        .modal-header .close:hover { opacity: .75; }

        /* ---- Slide alert ---- */
        .alert-slide-container {
            position: fixed; top: 0; left: 50%;
            transform: translateX(-50%) translateY(-100%);
            z-index: 9999; min-width: 400px; max-width: 600px;
            transition: transform 0.4s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .alert-slide-container.show { transform: translateX(-50%) translateY(20px); }

        /* ---- Assignment panel (inside Use This modal) ---- */
        .assignments-checkboxes {
            max-height: 300px; overflow-y: auto;
            border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; background: white;
        }
        .assignments-checkboxes .form-check { padding: 12px 16px; border-bottom: 1px solid #f1f1f1; }
        .assignments-checkboxes .form-check:last-child { border-bottom: none; }
        .assignments-checkboxes .form-check:hover { background: #f8f9fa; }

        /* ---- Tabs ---- */
        .nav-tabs .nav-link { font-weight: 500; color: #495057; }
        .nav-tabs .nav-link.active { font-weight: 600; }
        .tab-content { padding-top: 20px; }

        /* ---- DataTable image column ---- */
        #cardsTable img, #mediaTable img { vertical-align: middle; }

        /* ---- Media type badge ---- */
        .badge-video { background: #f3e8ff; color: #6b21a8; }
        .badge-image { background: #e0f2fe; color: #0369a1; }

        /* ---- Image preview in card modal ---- */
        #cardCurrentImage { max-width: 140px; max-height: 140px; border: 1px solid #dee2e6; border-radius: 6px; padding: 4px; }

        /* ---- Org type buttons (match exercises page green) ---- */
        .btn-org-type {
            border: 1px solid #28a745;
            color: #28a745;
            background: #fff;
            font-weight: 600;
        }
        .btn-org-type:hover {
            background: #f0faf2;
            color: #28a745;
            border-color: #28a745;
        }
        .btn-org-type.active,
        .btn-org-type:active {
            background: #28a745 !important;
            color: #fff !important;
            border-color: #28a745 !important;
        }

        /* ---- Card Organization ---- */
        .org-group-btn { border-radius: 20px !important; font-size: 0.82rem; font-weight: 600; transition: all 0.15s; }
        .org-group-btn.active { background: #1c84c6; color: #fff; border-color: #1c84c6; }
        /* Drop-target states when dragging a card over group buttons */
        .org-group-btn.drop-target {
            outline: 2px dashed #1c84c6;
            outline-offset: 3px;
            animation: pulse-drop 0.9s ease-in-out infinite alternate;
        }
        .org-group-btn.drop-hover {
            background: #1c84c6 !important;
            color: #fff !important;
            border-color: #1c84c6 !important;
            transform: scale(1.08);
            outline: none;
        }
        @keyframes pulse-drop {
            from { outline-color: #1c84c6; }
            to   { outline-color: #93c5fd; }
        }
        .org-card-tile.is-dragging { opacity: 0.35; }
        .org-card-tile {
            width: 130px; flex-shrink: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
            cursor: grab;
            position: relative;
            transition: box-shadow 0.15s;
            user-select: none;
        }
        .org-card-tile:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.12); }
        .org-card-tile.sortable-ghost { opacity: 0.35; }
        .org-card-tile img { width: 80px; height: 80px; object-fit: contain; border-radius: 4px; background: #f9fafb; }
        .org-card-tile .tile-label { font-size: 0.78rem; font-weight: 600; margin-top: 6px; word-break: break-all; line-height: 1.2; }
        .org-card-tile .tile-remove {
            position: absolute; top: 4px; right: 4px;
            width: 20px; height: 20px; line-height: 20px;
            background: #ef4444; color: #fff; border-radius: 50%;
            font-size: 0.65rem; cursor: pointer; border: none;
            display: flex; align-items: center; justify-content: center;
        }
        .org-card-tile .tile-remove:hover { background: #b91c1c; }
        #orgSaveOrderBtn { display: none; min-width: 130px; }
        #orgDragHint { display: none; }

        /* ---- Pill styles for assignment exercise builder ---- */
        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            padding: 4px 8px;
            margin: 3px;
            background: #fff;
            user-select: none;
            font-weight: 600;
            font-size: 0.8rem;
            min-width: 40px;
        }
        .pill:hover { background: #f1f5f9; }
        .pill-section-header { font-weight: 800; margin: 10px 0 4px; opacity: .85; font-size: 0.8rem; }

        /* ---- Exercise card preview in assignment modal ---- */
        #modalExercisePreview {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            align-items: stretch;
            min-height: 180px;
            background: #f8f9fa;
        }
        #modalExercisePreview.vertical-layout {
            flex-direction: column;
            align-items: center;
        }
        #modalExercisePreview .exercise-card {
            flex: 1 1 0;
            min-width: 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #modalExercisePreview.vertical-layout .exercise-card {
            flex: 0 0 auto;
            width: 100%;
            max-width: 400px;
        }
        #modalExercisePreview .exercise-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        #modalExercisePreview .exercise-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #modalExercisePreview .exercise-main-img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 100px;
            object-fit: contain;
        }
        #modalExercisePreview .exercise-icon-img {
            display: block;
            margin: 4px auto 0;
            max-width: 55px;
            max-height: 55px;
            object-fit: contain;
        }
        .cv-bottom-row { margin-top: 6px; display: flex; justify-content: center; gap: 6px; }
        .cv-bottom-row img { max-width: 60px; height: auto; }
        .word-bottom-row { margin-top: 6px; display: flex; justify-content: center; align-items: center; gap: 4px; }
        .exercise-list-item-handle { cursor: grab; color: #9ca3af; }
    </style>
</head>

<body>
<!-- Slide Alert -->
<div id="slideAlert" class="alert-slide-container" style="display:none;">
    <div class="alert mb-0" id="slideAlertContent" role="alert">
        <button type="button" class="btn-close float-end" onclick="hideSlideAlert()"></button>
        <strong id="slideAlertTitle"></strong>
        <span id="slideAlertMessage"></span>
    </div>
</div>

<!-- Slide Confirm -->
<div id="slideConfirm" class="alert-slide-container" style="display:none;">
    <div class="alert alert-warning mb-0" id="slideConfirmContent" role="alert">
        <div class="d-flex justify-content-between align-items-center">
            <div><strong id="slideConfirmTitle"></strong><span id="slideConfirmMessage"></span></div>
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

        <div class="row border-bottom white-bg dashboard-header">
            <div class="col-xl-8">
                <h1>Content Management</h1>
                <span class="text-muted">Manage your exercise sounds, words, and blends</span>
            </div>
            <div class="col-xl-4 text-end">
                <div class="mt-3">
                    <span class="badge bg-primary" style="font-size:1rem; padding:8px 16px;">
                        Tier: <?= htmlspecialchars($stats['tier_name']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="wrapper wrapper-content animated fadeIn">

            <?php if (!$isSuperUser && $stats['limit'] > 0): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="ibox mb-0">
                        <div class="ibox-content">
                            <h5 class="mb-2">Tier Usage</h5>
                            <div class="progress-bar-custom">
                                <?php
                                $percent    = $stats['percent_used'];
                                $colorClass = $percent >= 90 ? 'danger' : ($percent >= 75 ? 'warning' : '');
                                ?>
                                <div class="progress-fill <?= $colorClass ?>" style="width:<?= min($percent,100) ?>%"></div>
                            </div>
                            <div class="mt-2 d-flex justify-content-between">
                                <span class="text-muted small"><?= $stats['total'] ?> / <?= $stats['limit'] ?> used (<?= $stats['percent_used'] ?>%)</span>
                                <span class="text-muted small"><?= $stats['remaining'] ?> remaining</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================================================
                 TABS
            ============================================================ -->
            <ul class="nav nav-tabs" id="contentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-cards" data-bs-toggle="tab" data-bs-target="#pane-cards" type="button" role="tab">
                        <i class="fa fa-th-large me-1"></i> Cards
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-org" data-bs-toggle="tab" data-bs-target="#pane-org" type="button" role="tab">
                        <i class="fa fa-sitemap me-1"></i> Card Organization
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-assignments" data-bs-toggle="tab" data-bs-target="#pane-assignments" type="button" role="tab">
                        <i class="fa fa-tasks me-1"></i> Assignments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-media" data-bs-toggle="tab" data-bs-target="#pane-media" type="button" role="tab">
                        <i class="fa fa-photo-video me-1"></i> Success Media
                    </button>
                </li>
                <?php if ($isSuperUser): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-videos" data-bs-toggle="tab" data-bs-target="#pane-videos" type="button" role="tab">
                        <i class="fa fa-video me-1"></i> Video Tutorials
                    </button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content" id="contentTabsContent">

                <!-- ======================================================
                     TAB 1 — CARDS
                ====================================================== -->
                <div class="tab-pane fade show active" id="pane-cards" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="ibox">
                                <div class="ibox-title d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Cards</h5>
                                    <?php if ($isSuperUser || $isEnterpriseAdmin): ?>
                                    <button class="btn btn-primary btn-sm" onclick="openAddCardModal()">
                                        <i class="fa fa-plus"></i> Add New Card
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="ibox-content">
                                    <table id="cardsTable" class="table table-striped table-hover table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th style="width:120px">Picture</th>
                                                <th>Sound / Word</th>
                                                <th>Card Type</th>
                                                <?php if ($isSuperUser || $isEnterpriseAdmin): ?>
                                                <th style="width:80px">Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody id="cardsTableBody">
                                            <tr>
                                                <td colspan="<?= ($isSuperUser || $isEnterpriseAdmin) ? 4 : 3 ?>" class="text-center text-muted py-4">
                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                    <p class="mt-2 mb-0">Loading cards…</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================
                     TAB 2 — CARD ORGANIZATION
                ====================================================== -->
                <div class="tab-pane fade" id="pane-org" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="ibox">
                                <div class="ibox-title d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h5 class="mb-0">Card Organization</h5>
                                    <!-- Card type picker -->
                                    <div class="btn-group btn-group-sm" id="orgTypeButtons" role="group">
                                        <button type="button" class="btn btn-org-type org-type-btn active" data-type="consonant">Consonants</button>
                                        <button type="button" class="btn btn-org-type org-type-btn" data-type="vowel">Vowels</button>
                                        <button type="button" class="btn btn-org-type org-type-btn" data-type="cv">CV</button>
                                        <button type="button" class="btn btn-org-type org-type-btn" data-type="cv_blending">CV Blending</button>
                                        <button type="button" class="btn btn-org-type org-type-btn" data-type="syllable_shifts">Syllable Shifts</button>
                                        <button type="button" class="btn btn-org-type org-type-btn" data-type="3cv_blend">CV-CV Blending</button>
                                        <button type="button" class="btn btn-org-type org-type-btn" data-type="word">Words</button>
                                    </div>
                                </div>
                                <div class="ibox-content">

                                    <!-- Group tabs row -->
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-3" id="orgGroupButtons">
                                        <span class="text-muted small">Loading groups…</span>
                                    </div>

                                    <!-- Active group toolbar -->
                                    <div id="orgGroupToolbar" class="d-flex align-items-center gap-2 mb-3" style="display:none !important;">
                                        <button class="btn btn-sm btn-success" onclick="openAddCardsToGroupModal()">
                                            <i class="fa fa-plus"></i> Add Cards to Group
                                        </button>
                                        <button class="btn btn-sm btn-info text-white" onclick="openCopyCardsModal()">
                                            <i class="fa fa-copy"></i> Copy to Group
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openRenameGroupModal()">
                                            <i class="fa fa-pencil-alt"></i> Rename Group
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteGroup()">
                                            <i class="fa fa-trash"></i> Delete Group
                                        </button>
                                        <?php if ($isEnterpriseAdmin): ?>
                                        <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="resetOrgToDefault()" title="Restore to default card organization from your administrator">
                                            <i class="fa fa-undo"></i> Reset to Default
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-primary" id="orgSaveOrderBtn" onclick="saveGroupOrder()" style="display:none;">
                                            <i class="fa fa-save me-1"></i>Save Order
                                        </button>
                                        <span class="text-muted small ms-2" id="orgDragHint" style="display:none;">
                                            <i class="fa fa-arrows-alt"></i> Drag cards to reorder
                                        </span>
                                    </div>

                                    <!-- Card grid (draggable) -->
                                    <div id="orgCardGrid" class="d-flex flex-wrap gap-3" style="min-height:120px;">
                                        <div class="text-muted py-4 w-100 text-center">
                                            <i class="fa fa-hand-point-up fa-2x mb-2 d-block"></i>
                                            Select a card type above to begin
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================
                     TAB 3 — ASSIGNMENTS
                ====================================================== -->
                <div class="tab-pane fade" id="pane-assignments" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="ibox">
                                <div class="ibox-title d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Assignments</h5>
                                    <button class="btn btn-primary btn-sm" onclick="openCreateAssignmentModal()">
                                        <i class="fa fa-plus"></i> Add New Assignment
                                    </button>
                                </div>
                                <div class="ibox-content">
                                    <table id="assignmentsTable" class="table table-striped table-hover table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Description</th>
                                                <th>Assigned To</th>
                                                <th style="width:90px">Exercises</th>
                                                <th style="width:110px">Date Added</th>
                                                <th style="width:110px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="assignmentsTableBody">
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                    <p class="mt-2 mb-0">Loading assignments…</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================
                     TAB 4 — SUCCESS MEDIA
                ====================================================== -->
                <div class="tab-pane fade" id="pane-media" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="ibox">
                                <div class="ibox-title d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Success Media</h5>
                                    <button class="btn btn-primary btn-sm" onclick="openUploadMediaModal()">
                                        <i class="fa fa-upload"></i> Upload New Media
                                    </button>
                                </div>
                                <div class="ibox-content">
                                    <table id="mediaTable" class="table table-striped table-hover table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th style="width:120px">Preview</th>
                                                <th>Name</th>
                                                <th style="width:80px">Type</th>
                                                <th style="width:90px">Default</th>
                                                <th>Assignments</th>
                                                <th style="width:110px">Settings</th>
                                                <th style="width:75px">Size</th>
                                                <th style="width:110px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mediaTableBody">
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                    <p class="mt-2 mb-0">Loading media…</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($isSuperUser): ?>
                <!-- ======================================================
                     TAB 5 — VIDEO TUTORIALS
                ====================================================== -->
                <div class="tab-pane fade" id="pane-videos" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="ibox">
                                <div class="ibox-title d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Video Tutorials</h5>
                                    <button class="btn btn-primary btn-sm" onclick="openUploadVideoModal()">
                                        <i class="fa fa-upload"></i> Upload New Video
                                    </button>
                                </div>
                                <div class="ibox-content">
                                    <table id="videosTable" class="table table-striped table-hover table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th style="width:120px">Preview</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th style="width:160px">Assigned To</th>
                                                <th style="width:120px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="videosTableBody">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                    <p class="mt-2 mb-0">Loading videos…</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /.tab-content -->

        </div><!-- /.wrapper-content -->
        <div class="footer"></div>
    </div>
</div>


<!-- ============================================================
     ADD / EDIT CARD MODAL
============================================================ -->
<div class="modal fade" id="deleteCardModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-trash me-2"></i>Delete Card</h5>
                <button type="button" class="close text-white" onclick="$('#deleteCardModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Delete <strong id="deleteCardName"></strong>?</p>
                <div id="deleteCardWarning" class="alert alert-warning small" style="display:none;"></div>
                <p class="text-muted small mb-0">This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="$('#deleteCardModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="deleteCardConfirmBtn" onclick="confirmDeleteCard()">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="cardModalTitle">Add New Card</h5>
                <button type="button" class="close text-white" onclick="$('#cardModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="cardModalError" class="alert alert-danger" style="display:none;"></div>
                <form id="cardForm" enctype="multipart/form-data">
                    <input type="hidden" id="cardAction"        name="action"          value="add">
                    <input type="hidden" id="cardId"            name="card_id"         value="">
                    <input type="hidden" id="cardSourceType"    name="source_type"     value="">
                    <input type="hidden" id="cardOldSourceType" name="old_source_type" value="">

                    <div class="mb-3" id="cardTypeRow">
                        <label class="form-label fw-semibold">Card Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="cardType" onchange="onCardTypeChange(this.value)" required>
                            <option value="">— choose type —</option>
                            <option value="consonant">Sounds in Isolation (Consonant)</option>
                            <option value="vowel">Sounds in Isolation (Vowel)</option>
                            <option value="cv_blend">CV/Blending/Shifts</option>
                            <option value="3cv_blend">CV-CV Blending</option>
                            <option value="word">Words</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sound / Word <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cardSoundText" name="sound_text"
                               placeholder="e.g. B, AH, B-AH, butterfly" required maxlength="50">
                        <small class="form-text text-muted" id="soundTextHint"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Picture</label>
                        <div id="cardCurrentImageWrap" style="display:none; margin-bottom:10px;">
                            <p class="text-muted small mb-1">Current picture:</p>
                            <img id="cardCurrentImage" src="" alt="Current">
                        </div>
                        <input type="file" class="form-control" id="cardImageFile" name="image"
                               accept="image/png,image/jpeg,image/webp" onchange="previewCardImage(event)">
                        <small class="form-text text-muted">PNG, JPEG or WebP, max 5 MB</small>
                        <div id="cardImagePreviewWrap" style="display:none; margin-top:10px;">
                            <p class="text-muted small mb-1">New picture preview:</p>
                            <img id="cardImagePreview" src="" alt="Preview"
                                 style="max-width:160px; max-height:160px; border:1px solid #ddd; border-radius:6px; padding:4px;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#cardModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="cardSubmitBtn" onclick="submitCard()">
                    <i class="fa fa-save"></i> <span id="cardSubmitText">Add Card</span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ============================================================
     UPLOAD MEDIA MODAL
============================================================ -->
<div class="modal fade" id="uploadMediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Upload New Media</h5>
                <button type="button" class="close text-white" onclick="$('#uploadMediaModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="uploadMediaError" class="alert alert-danger" style="display:none;"></div>
                <form id="mediaUploadForm" enctype="multipart/form-data">

                    <!-- Row 1: File + Name/Settings -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="mediaFile" name="media_file"
                                   accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp" required>
                            <div class="form-text">
                                <strong>Videos:</strong> Max 10 MB, MP4/WebM/MOV, up to 5s<br>
                                <strong>Images:</strong> Max 5 MB, JPEG/PNG/GIF/WebP
                            </div>
                            <div id="mediaPreview" class="mt-3" style="display:none;">
                                <video id="previewVideo" controls style="max-width:100%; max-height:160px; display:none;"></video>
                                <img id="previewImage" style="max-width:100%; max-height:160px; display:none;" alt="Preview">
                                <div id="mediaInfo" class="mt-2 small text-muted"></div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mediaName" name="media_name"
                                   required maxlength="255" placeholder="e.g., Great Job!">
                            <div id="soundControls" style="display:none;" class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="allowSound" name="allow_sound" checked>
                                    <label class="form-check-label" for="allowSound">Allow Sound</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="autoplayLoop" name="autoplay_loop">
                                    <label class="form-check-label" for="autoplayLoop">Autoplay &amp; Loop</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Assignment / Default -->
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <label class="form-label fw-semibold">Default &amp; Assignment</label>
                            <div class="p-3 bg-light rounded">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="uploadSetDefault">
                                    <label class="form-check-label" for="uploadSetDefault">
                                        <strong>Set as default success media</strong><br>
                                        <small class="text-muted">Shows after exercises not tied to a specific assignment</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7 mb-2">
                            <label class="form-label fw-semibold">Assign to Assignments <span class="text-muted fw-normal">(optional)</span></label>
                            <div id="uploadAssignmentsList" class="assignments-checkboxes">
                                <div class="text-center py-2 text-muted small"><i class="fa fa-spinner fa-spin me-1"></i>Loading assignments…</div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer" id="uploadModalFooter">
                <button type="button" class="btn btn-secondary" onclick="$('#uploadMediaModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnUploadMedia" onclick="handleMediaUpload()">
                    <i class="fa fa-upload me-1"></i>Upload
                </button>
            </div>
            <!-- Shown after successful upload -->
            <div class="modal-footer bg-success bg-opacity-10" id="uploadSuccessFooter" style="display:none;">
                <span class="text-success fw-semibold"><i class="fa fa-check-circle me-1"></i>Uploaded &amp; saved successfully!</span>
                <button type="button" class="btn btn-success btn-sm ms-auto" onclick="$('#uploadMediaModal').modal('hide')">
                    <i class="fa fa-check me-1"></i>Done
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ============================================================
     EDIT MEDIA MODAL
============================================================ -->
<div class="modal fade" id="editMediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Media</h5>
                <button type="button" class="close text-white" onclick="$('#editMediaModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editMediaId">
                <input type="hidden" id="editMediaCurrentType">

                <div class="row">
                    <!-- Left: current preview + replace file -->
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Current Media</label>
                        <div id="editCurrentPreview" class="mb-3 text-center p-3 bg-light rounded" style="min-height:120px; display:flex; align-items:center; justify-content:center;">
                            <!-- filled by JS -->
                        </div>
                        <label class="form-label fw-semibold">Replace File <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="file" class="form-control form-control-sm" id="editMediaFile"
                               accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp"
                               onchange="onEditFileSelected(event)">
                        <div class="form-text">Leave blank to keep current file.<br>MP4/WebM/MOV up to 10 MB · JPEG/PNG/GIF/WebP up to 5 MB</div>
                        <div id="editNewPreview" class="mt-2 text-center" style="display:none;">
                            <video id="editNewVideo" controls style="max-width:100%; max-height:140px; display:none;"></video>
                            <img   id="editNewImage" style="max-width:100%; max-height:140px; display:none;" alt="New preview">
                            <div id="editNewInfo" class="small text-muted mt-1"></div>
                        </div>
                    </div>

                    <!-- Right: name + settings -->
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editMediaName" maxlength="255">
                        </div>
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editSetDefault">
                                <label class="form-check-label" for="editSetDefault">
                                    <strong>Set as default success media</strong><br>
                                    <small class="text-muted">Replaces any existing default. Shows after exercises not tied to a specific assignment.</small>
                                </label>
                            </div>
                        </div>
                        <div id="editMediaVideoSettings">
                            <hr class="my-2">
                            <label class="form-label fw-semibold">Video Settings</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="editAllowSound">
                                <label class="form-check-label" for="editAllowSound">Allow Sound</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editAutoplayLoop">
                                <label class="form-check-label" for="editAutoplayLoop">Autoplay &amp; Loop</label>
                            </div>
                        </div>
                        <div id="editMediaError" class="alert alert-danger mt-3" style="display:none;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#editMediaModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="editMediaSaveBtn" onclick="saveMediaEdit()">
                    <i class="fa fa-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ============================================================
     MANAGE MEDIA ASSIGNMENTS MODAL
============================================================ -->
<div class="modal fade" id="useMediaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Manage — <span id="useMediaName"></span></h5>
                <button type="button" class="close text-white" onclick="$('#useMediaModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="useMediaId">
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="useAsDefault" onchange="setAsDefault(document.getElementById('useMediaId').value, this.checked)">
                        <label class="form-check-label" for="useAsDefault">
                            <strong>Set as default success media</strong><br>
                            <small class="text-muted">Shows after any exercise not covered by an assignment-specific media item</small>
                        </label>
                    </div>
                </div>
                <div class="mb-2 fw-semibold">Assign to specific assignments:</div>
                <div class="text-muted small mb-2">Check the assignments that should use this media as their success reward.</div>
                <div id="useMediaAssignmentsList" class="assignments-checkboxes">
                    <div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Loading…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#useMediaModal').modal('hide')">Close</button>
                <button type="button" class="btn btn-success" onclick="saveMediaAssignments()">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ============================================================
     ADD CARDS TO GROUP MODAL
============================================================ -->
<div class="modal fade" id="addCardsToGroupModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add Cards to Group — <span id="addCardsGroupName"></span></h5>
                <button type="button" class="close text-white" onclick="$('#addCardsToGroupModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Select cards from any category to add to this group. Cards already in this group are excluded.</p>
                <!-- Type tabs -->
                <ul class="nav nav-tabs mb-0" id="addCardsTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-tab-type="consonant">Consonants</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-tab-type="vowel">Vowels</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-tab-type="cv_blend">CV / Blending</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-tab-type="3cv_blend">CV-CV Blending</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-tab-type="word">Words</a></li>
                </ul>
                <div style="border:1px solid #dee2e6;border-top:none;border-radius:0 0 4px 4px;padding:10px;">
                    <div id="addCardsPickerGrid" class="d-flex flex-wrap gap-3" style="min-height:80px; max-height:380px; overflow-y:auto;">
                        <div class="text-center w-100 py-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
                    </div>
                </div>
                <div class="mt-2 text-muted small" id="addCardsSelCount"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#addCardsToGroupModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmAddCardsToGroup()">
                    <i class="fa fa-plus"></i> Add Selected
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     COPY CARDS TO GROUP MODAL
============================================================ -->
<div class="modal fade" id="copyCardsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Copy Cards to Another Group — <span id="copyCardsFromGroup"></span></h5>
                <button type="button" class="close text-white" onclick="$('#copyCardsModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Destination Group <span class="text-danger">*</span></label>
                    <select class="form-control" id="copyCardsTargetGroup">
                        <option value="">— select a group —</option>
                    </select>
                </div>
                <p class="text-muted small mb-2">Select cards to copy. They stay in <strong id="copyCardsFromGroup2"></strong> and are also added to the destination.</p>
                <div id="copyCardsPickerGrid" class="d-flex flex-wrap gap-3" style="min-height:80px; max-height:420px; overflow-y:auto;">
                    <div class="text-center w-100 py-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#copyCardsModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-info text-white" onclick="confirmCopyCards()">
                    <i class="fa fa-copy"></i> Copy Selected
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     RENAME GROUP MODAL
============================================================ -->
<div class="modal fade" id="renameGroupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Rename Group</h5>
                <button type="button" class="close text-white" onclick="$('#renameGroupModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Group Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="renameGroupInput" maxlength="100"
                           placeholder="e.g. Stage 1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#renameGroupModal').modal('hide')">Cancel</button>
                <button type="button" id="renameGroupConfirmBtn" class="btn btn-primary" onclick="confirmRenameGroup()">
                    <i class="fa fa-save"></i> Rename
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ASSIGNMENT MODAL (create / edit)
============================================================ -->
<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="assignmentModalTitle">Create Assignment</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- LEFT: Assignment details + exercise list -->
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label for="assignmentName" class="form-label fw-semibold">Assignment Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="assignmentName" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="assignmentDescription" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="assignmentDescription" rows="2"></textarea>
                        </div>

                        <!-- Assign Users (shown to super_user / enterprise_admin) -->
                        <div class="mb-3" id="assignmentUsersWrapper" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 fw-semibold">Assign To Users</label>
                                <button type="button" class="btn btn-sm btn-warning" id="btnUnassignAll" style="display:none;">
                                    <i class="fa fa-user-times"></i> Unassign All
                                </button>
                            </div>
                            <div id="currentlyAssignedUsers" class="mb-2" style="display:none;">
                                <small class="text-muted d-block mb-1">Currently Assigned:</small>
                                <div id="assignedUsersList" class="d-flex flex-wrap gap-1 mb-2"></div>
                            </div>
                            <select class="form-select" id="assignmentUsers" multiple size="5"></select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users to add</small>
                        </div>

                        <hr>

                        <h6>Exercises in Assignment</h6>
                        <div id="exerciseList" class="list-group mb-3" style="max-height: 350px; overflow-y: auto;">
                            <div class="text-muted text-center py-3" id="exerciseListEmpty">No exercises added yet</div>
                        </div>
                    </div>

                    <!-- RIGHT: Exercise builder -->
                    <div class="col-md-7">
                        <h6>Build Exercise</h6>

                        <div class="mb-3">
                            <label for="exerciseName" class="form-label fw-semibold">Exercise Name</label>
                            <input type="text" class="form-control" id="exerciseName" placeholder="e.g., Consonant Practice">
                        </div>

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
                            <select id="modalCardCount" class="form-select form-select-sm" style="width:auto;">
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

                        <div id="modalSelectionPanel" class="border rounded p-2 mb-3" style="max-height:150px; overflow-y:auto; display:none;"></div>

                        <div id="modalExercisePreview" class="border rounded p-3 mb-3">
                            <div class="text-muted text-center py-4">Select a category and build your exercise</div>
                        </div>

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
                    <i class="fa fa-save me-1"></i> Save Assignment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     VIDEO TUTORIALS MODALS
====================================================== -->

<!-- Upload Video Modal -->
<div class="modal fade" id="uploadVideoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-upload me-2"></i>Add Video Tutorial</h5>
                <button type="button" class="close text-white" onclick="$('#uploadVideoModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="uploadVideoError" class="alert alert-danger" style="display:none;"></div>
                <form id="uploadVideoForm" enctype="multipart/form-data">
                    <input type="hidden" id="uploadSourceType" name="source_type" value="upload">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="uploadVideoTitle" name="title" maxlength="200" required placeholder="e.g. Sounds in Isolation Demo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="uploadVideoDesc" name="description" rows="2" maxlength="1000" placeholder="Brief description of this tutorial…"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Video Source</label>
                        <div class="btn-group w-100 mb-2" role="group">
                            <button type="button" class="btn btn-sm btn-primary" id="uploadToggleFile" onclick="setUploadSource('upload')">
                                <i class="fa fa-upload me-1"></i> Upload File
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="uploadToggleUrl" onclick="setUploadSource('url')">
                                <i class="fa fa-link me-1"></i> External URL (YouTube, Vimeo…)
                            </button>
                        </div>
                        <div id="uploadFileWrap">
                            <input type="file" class="form-control" id="uploadVideoFile" name="video_file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                            <small class="form-text text-muted">MP4, WebM, MOV, AVI — max 500 MB</small>
                        </div>
                        <div id="uploadUrlWrap" style="display:none;">
                            <input type="url" class="form-control" id="uploadVideoUrl" name="video_url" placeholder="https://www.youtube.com/watch?v=…">
                            <small class="form-text text-muted">YouTube, Vimeo, or any direct video URL</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#uploadVideoModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadVideoBtn" onclick="submitUploadVideo()">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Video Modal -->
<div class="modal fade" id="editVideoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-pencil-alt me-2"></i>Edit Video</h5>
                <button type="button" class="close text-white" onclick="$('#editVideoModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="editVideoError" class="alert alert-danger" style="display:none;"></div>
                <form id="editVideoForm" enctype="multipart/form-data">
                    <input type="hidden" id="editVideoId" name="video_id">
                    <input type="hidden" id="editSourceType" name="source_type" value="upload">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editVideoTitle" name="title" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="editVideoDesc" name="description" rows="2" maxlength="1000"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Video Source</label>
                        <div class="btn-group w-100 mb-2" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editToggleFile" onclick="setEditSource('upload')">
                                <i class="fa fa-upload me-1"></i> Upload File
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editToggleUrl" onclick="setEditSource('url')">
                                <i class="fa fa-link me-1"></i> External URL
                            </button>
                        </div>
                        <div id="editFileWrap">
                            <div id="editVideoCurrentWrap" class="mb-2" style="display:none;">
                                <p class="text-muted small mb-1">Current video:</p>
                                <video id="editVideoPreview" controls style="max-width:100%;max-height:120px;border-radius:6px;border:1px solid #dee2e6;"></video>
                            </div>
                            <input type="file" class="form-control" id="editVideoFile" name="video_file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                            <small class="form-text text-muted">Leave blank to keep existing. MP4, WebM, MOV, AVI — max 500 MB</small>
                        </div>
                        <div id="editUrlWrap" style="display:none;">
                            <input type="url" class="form-control" id="editVideoUrl" name="video_url" placeholder="https://www.youtube.com/watch?v=…">
                            <small class="form-text text-muted">YouTube, Vimeo, or any direct video URL</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#editVideoModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="editVideoBtn" onclick="submitEditVideo()">
                    <i class="fa fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Video Modal -->
<div class="modal fade" id="assignVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-link me-2"></i>Assign Video — <span id="assignVideoName"></span></h5>
                <button type="button" class="close text-white" onclick="$('#assignVideoModal').modal('hide')"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Select which Video Example button this video should appear on. Only one video per slot. Clicking an occupied slot will replace the current assignment.</p>
                <div id="assignSlotGrid" class="row g-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#assignVideoModal').modal('hide')">Close</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="unassignVideoBtn" onclick="unassignVideo()" style="display:none;">
                    <i class="fa fa-unlink"></i> Remove Assignment
                </button>
            </div>
        </div>
    </div>
</div>

<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/metismenu/js/metisMenu.min.js"></script>
<script src="plugins/pace-js/js/pace.min.js"></script>
<script src="plugins/simplebar/js/simplebar.min.js"></script>
<script src="js/inspinia.js?v=<?= ASSET_VER ?>"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>
<script src="plugins/datatables/js/dataTables.min.js"></script>
<script src="plugins/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="plugins/sortablejs/Sortable.min.js"></script>

<script>
// ================================================================
// Utilities
// ================================================================
let slideAlertTimeout      = null;
let confirmResolveFunction = null;
const isSuperUser          = <?= $isSuperUser ? 'true' : 'false' ?>;
const isEnterpriseAdmin    = <?= $isEnterpriseAdmin ? 'true' : 'false' ?>;
const canManageCards       = isSuperUser || isEnterpriseAdmin;

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = String(text ?? '');
    return d.innerHTML;
}

function formatFileSize(bytes) {
    if (!bytes) return '—';
    const k = 1024, sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
}

function showAlert(message, title = '', type = 'info', duration = 5000) {
    const container = document.getElementById('slideAlert');
    const content   = document.getElementById('slideAlertContent');
    if (!container) return;
    if (slideAlertTimeout) clearTimeout(slideAlertTimeout);
    content.className = 'alert mb-0';
    content.classList.add({ success:'alert-success', warning:'alert-warning', danger:'alert-danger' }[type] || 'alert-info');
    document.getElementById('slideAlertTitle').textContent   = title ? title + ': ' : '';
    document.getElementById('slideAlertMessage').textContent = message;
    container.style.display = 'block';
    container.offsetHeight;
    container.classList.add('show');
    slideAlertTimeout = setTimeout(hideSlideAlert, duration);
    return Promise.resolve();
}
function hideSlideAlert() {
    const c = document.getElementById('slideAlert');
    if (!c) return;
    c.classList.remove('show');
    setTimeout(() => c.style.display = 'none', 400);
}

function showConfirm(message, title = 'Confirm', yesText = 'Yes', noText = 'No', type = 'warning') {
    return new Promise(resolve => {
        const container = document.getElementById('slideConfirm');
        if (!container) { resolve(confirm(message)); return; }
        confirmResolveFunction = resolve;
        const content = document.getElementById('slideConfirmContent');
        content.className = 'alert mb-0';
        content.classList.add({ danger:'alert-danger', success:'alert-success', info:'alert-info' }[type] || 'alert-warning');
        let yesBtn = document.getElementById('slideConfirmYes');
        let noBtn  = document.getElementById('slideConfirmNo');
        const newY = yesBtn.cloneNode(true); const newN = noBtn.cloneNode(true);
        yesBtn.parentNode.replaceChild(newY, yesBtn);
        noBtn.parentNode.replaceChild(newN, noBtn);
        document.getElementById('slideConfirmTitle').textContent   = title ? title + ': ' : '';
        document.getElementById('slideConfirmMessage').innerHTML = message;
        document.getElementById('slideConfirmYes').textContent = yesText;
        document.getElementById('slideConfirmNo').textContent  = noText;
        document.getElementById('slideConfirmYes').className = ({ danger:'btn btn-sm btn-danger me-2', success:'btn btn-sm btn-success me-2', info:'btn btn-sm btn-primary me-2' }[type] || 'btn btn-sm btn-warning me-2');
        document.getElementById('slideConfirmYes').addEventListener('click', () => hideSlideConfirm(true));
        document.getElementById('slideConfirmNo').addEventListener('click',  () => hideSlideConfirm(false));
        container.style.display = 'block'; container.offsetHeight; container.classList.add('show');
    });
}
function hideSlideConfirm(result) {
    const c = document.getElementById('slideConfirm');
    if (!c) return;
    c.classList.remove('show');
    setTimeout(() => { c.style.display = 'none'; if (confirmResolveFunction) { confirmResolveFunction(result); confirmResolveFunction = null; } }, 400);
}

// ================================================================
// TAB 1 — CARDS
// ================================================================
let cardsTable = null;

const BADGE_CLASS = {
    consonant: 'badge-isolation', vowel: 'badge-isolation',
    cv_blend:  'badge-cv',        '3cv_blend': 'badge-cvcv',
    word:      'badge-words',
};

function thumbHtml(url) {
    if (url) return `<img src="${escapeHtml(url)}" class="card-thumbnail" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div class="thumb-placeholder" style="display:none;"><i class="fa fa-image"></i></div>`;
    return `<div class="thumb-placeholder"><i class="fa fa-image"></i></div>`;
}

function badgeHtml(displayType, sourceType) {
    return `<span class="content-type-badge ${BADGE_CLASS[sourceType] || ''}">${escapeHtml(displayType)}</span>`;
}

function initCardsTable(data) {
    if (cardsTable) { cardsTable.destroy(); $('#cardsTable tbody').empty(); }
    const tbody = document.getElementById('cardsTableBody');
    tbody.innerHTML = '';
    data.forEach(card => {
        const tr = document.createElement('tr');
        const canEdit   = canManageCards;
        const canDelete = isSuperUser || (isEnterpriseAdmin && card.is_own == 1);
        tr.innerHTML = `
            <td>${thumbHtml(card.image_path)}</td>
            <td><strong>${escapeHtml(card.sound_text)}</strong></td>
            <td>${badgeHtml(card.display_type, card.source_type)}</td>
            ${canManageCards ? `<td><div class="d-flex gap-1">
                ${canEdit   ? `<button class="btn btn-xs btn-outline-primary" title="Edit" onclick='openEditCardModal(${JSON.stringify(card)})'><i class="fa fa-pencil-alt"></i></button>` : ''}
                ${canDelete ? `<button class="btn btn-xs btn-outline-danger" title="Delete" onclick='openDeleteCardModal(${JSON.stringify(card)})'><i class="fa fa-trash"></i></button>` : ''}
            </div></td>` : ''}
        `;
        tbody.appendChild(tr);
    });
    cardsTable = $('#cardsTable').DataTable({
        pageLength: 25,
        order: [[2,'asc'],[1,'asc']],
        columnDefs: [
            { orderable: false, targets: 0 },
            { orderable: false, targets: canManageCards ? 3 : -1 },
        ],
        language: { search: 'Search cards:', emptyTable: 'No cards found' },
    });
    // Mobile: title = sound text (col 1), hide thumbnail (col 0) and type badge (col 2)
    if (window.MKAMobile) {
        MKAMobile.initTable('#cardsTable', {
            titleCol   : 1,
            hideCols   : [0, 2],
            dtInstance : cardsTable
        });
    }
}

async function loadCards() {
    try {
        const resp = await fetch('/dashboards/api/admin/get_cards.php');
        const data = await resp.json();
        if (data.status === 'success') {
            initCardsTable(data.data);
        } else {
            document.getElementById('cardsTableBody').innerHTML =
                `<tr><td colspan="${canManageCards?4:3}" class="text-center text-danger">Failed to load: ${escapeHtml(data.message)}</td></tr>`;
        }
    } catch(e) { console.error(e); }
}

// ---- Add / Edit Card Modal ----
const cardTypeHints = {
    consonant: 'Enter the consonant code, e.g. B, CH, SH',
    vowel:     'Enter the vowel code, e.g. AH, EE, OO',
    cv_blend:  'Enter the CV blend code, e.g. B-AH, M-EE',
    '3cv_blend': 'Enter the blend code, e.g. B-AH',
    word:      'Enter the word, e.g. butterfly',
};

function onCardTypeChange(val) {
    document.getElementById('cardSourceType').value       = val;
    document.getElementById('soundTextHint').textContent  = cardTypeHints[val] || '';
}

function openAddCardModal() {
    document.getElementById('cardModalTitle').textContent  = 'Add New Card';
    document.getElementById('cardSubmitText').textContent  = 'Add Card';
    document.getElementById('cardAction').value            = 'add';
    document.getElementById('cardId').value                = '';
    document.getElementById('cardSourceType').value        = '';
    document.getElementById('cardOldSourceType').value     = '';
    document.getElementById('cardType').value              = '';
    document.getElementById('cardType').required           = true;
    document.getElementById('cardSoundText').value         = '';
    document.getElementById('cardTypeRow').style.display   = '';
    document.getElementById('cardCurrentImageWrap').style.display = 'none';
    document.getElementById('cardImagePreviewWrap').style.display = 'none';
    document.getElementById('cardImageFile').value         = '';
    document.getElementById('cardModalError').style.display = 'none';
    document.getElementById('soundTextHint').textContent   = '';
    $('#cardModal').modal('show');
}

function openEditCardModal(card) {
    document.getElementById('cardModalTitle').textContent  = 'Edit Card — ' + card.sound_text;
    document.getElementById('cardSubmitText').textContent  = 'Save Changes';
    document.getElementById('cardAction').value            = 'update';
    document.getElementById('cardId').value                = card.id;
    document.getElementById('cardSourceType').value        = card.source_type;
    document.getElementById('cardOldSourceType').value     = card.source_type;
    document.getElementById('cardType').value              = card.source_type;
    document.getElementById('cardType').required           = true;
    document.getElementById('cardSoundText').value         = card.sound_text;
    document.getElementById('cardTypeRow').style.display   = '';
    document.getElementById('cardImagePreviewWrap').style.display = 'none';
    document.getElementById('cardImageFile').value         = '';
    document.getElementById('cardModalError').style.display = 'none';
    const imgWrap = document.getElementById('cardCurrentImageWrap');
    const imgEl   = document.getElementById('cardCurrentImage');
    if (card.image_path) { imgEl.src = card.image_path; imgWrap.style.display = 'block'; }
    else imgWrap.style.display = 'none';
    document.getElementById('soundTextHint').textContent = cardTypeHints[card.source_type] || '';
    $('#cardModal').modal('show');
}

function previewCardImage(event) {
    const file = event.target.files[0];
    const wrap = document.getElementById('cardImagePreviewWrap');
    if (!file) { wrap.style.display = 'none'; return; }
    document.getElementById('cardImagePreview').src = URL.createObjectURL(file);
    wrap.style.display = 'block';
}

async function submitCard() {
    const action    = document.getElementById('cardAction').value;
    const srcType   = document.getElementById('cardSourceType').value;
    const soundText = document.getElementById('cardSoundText').value.trim();
    const errorEl   = document.getElementById('cardModalError');
    errorEl.style.display = 'none';
    if (!soundText) { errorEl.textContent = 'Sound / Word text is required.'; errorEl.style.display = 'block'; return; }
    if (!srcType)   { errorEl.textContent = 'Please choose a card type.';     errorEl.style.display = 'block'; return; }
    const btn = document.getElementById('cardSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
    try {
        const resp = await fetch('/dashboards/api/admin/save_card.php', { method:'POST', body: new FormData(document.getElementById('cardForm')) });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            $('#cardModal').modal('hide');
            await loadCards();
        } else if (data.status === 'duplicate') {
            errorEl.textContent = data.message || 'A card with that text already exists.';
            errorEl.style.display = 'block';
            showAlert(data.message || 'Duplicate card', 'Duplicate', 'warning', 6000);
        } else {
            errorEl.textContent = data.message || 'An error occurred.';
            errorEl.style.display = 'block';
        }
    } catch(e) { errorEl.textContent = 'Network error.'; errorEl.style.display = 'block'; }
    finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa fa-save"></i> <span id="cardSubmitText">${action === 'add' ? 'Add Card' : 'Save Changes'}</span>`;
    }
}

// ---- Delete Card ----
let _deleteCardPending = null;

function openDeleteCardModal(card) {
    _deleteCardPending = card;
    document.getElementById('deleteCardName').textContent = card.sound_text + ' (' + card.display_type + ')';
    document.getElementById('deleteCardWarning').style.display = 'none';
    document.getElementById('deleteCardWarning').textContent = '';
    const btn = document.getElementById('deleteCardConfirmBtn');
    btn.disabled = false;
    btn.dataset.force = '0';
    btn.innerHTML = '<i class="fa fa-trash"></i> Delete';
    $('#deleteCardModal').modal('show');
}

async function confirmDeleteCard() {
    if (!_deleteCardPending) return;
    const btn = document.getElementById('deleteCardConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    const fd = new FormData();
    fd.append('source_type', _deleteCardPending.source_type);
    fd.append('card_id',     _deleteCardPending.id);
    fd.append('force',       btn.dataset.force || '0');
    try {
        const resp = await fetch('/dashboards/api/admin/delete_card.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.status === 'success') {
            $('#deleteCardModal').modal('hide');
            toastr.success(data.message);
            await loadCards();
        } else if (data.status === 'confirm') {
            const warn = document.getElementById('deleteCardWarning');
            warn.textContent = data.message;
            warn.style.display = 'block';
            btn.dataset.force = '1';
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Delete Anyway';
        } else {
            toastr.error(data.message || 'Failed to delete card');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-trash"></i> Delete';
        }
    } catch(e) {
        toastr.error('Network error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-trash"></i> Delete';
    }
}

// ================================================================
// TAB 2 — CARD ORGANIZATION
// ================================================================
let orgCurrentType  = 'consonant';
let orgCurrentGroup = null;   // null = none selected; '__ungrouped__' = ungrouped
let orgSortable     = null;
let orgOrderDirty   = false;

// Drag-to-button state
let _orgDragCardId    = null;
let _orgDragCardType  = null;
let _orgDragFromGroup = null;
let _orgDroppedOnBtn  = false;

function _orgDragStart(cardId, cardType, fromGroup) {
    _orgDragCardId    = cardId;
    _orgDragCardType  = cardType;
    _orgDragFromGroup = fromGroup;
    _orgDroppedOnBtn  = false;
    // Highlight all group buttons that are valid drop targets
    document.querySelectorAll('.org-group-btn[data-group]').forEach(btn => {
        if (btn.dataset.group !== fromGroup && !btn.classList.contains('org-new-group-btn')) {
            btn.classList.add('drop-target');
        }
    });
}

function _orgDragEnd() {
    _orgDragCardId    = null;
    _orgDragCardType  = null;
    _orgDragFromGroup = null;
    document.querySelectorAll('.org-group-btn').forEach(btn => {
        btn.classList.remove('drop-target', 'drop-hover');
    });
}

function _wireDropTarget(btn, groupName) {
    btn.addEventListener('dragover', e => {
        if (_orgDragCardId === null) return;
        if (groupName === _orgDragFromGroup) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        btn.classList.add('drop-hover');
    });
    btn.addEventListener('dragleave', () => btn.classList.remove('drop-hover'));
    btn.addEventListener('drop', async e => {
        e.preventDefault();
        btn.classList.remove('drop-hover', 'drop-target');
        const cardId   = _orgDragCardId;
        const cardType = _orgDragCardType;
        const fromGrp  = _orgDragFromGroup;
        _orgDragEnd();
        if (cardId === null || groupName === fromGrp) return;
        _orgDroppedOnBtn = true;
        await moveCardToGroup(cardId, cardType, groupName === '__ungrouped__' ? null : groupName, fromGrp);
    });
}

// ---- Render group button row ----
function renderOrgGroupButtons(groups, ungroupedCount) {
    const container = document.getElementById('orgGroupButtons');
    container.innerHTML = '';

    // Ungrouped button
    const ungBtn = document.createElement('button');
    ungBtn.className = 'btn btn-sm btn-outline-secondary org-group-btn' + (orgCurrentGroup === '__ungrouped__' ? ' active' : '');
    ungBtn.dataset.group = '__ungrouped__';
    ungBtn.innerHTML = `<i class="fa fa-inbox me-1"></i> Ungrouped <span class="badge bg-secondary ms-1">${ungroupedCount}</span>`;
    ungBtn.onclick = () => selectOrgGroup('__ungrouped__');
    _wireDropTarget(ungBtn, '__ungrouped__');
    container.appendChild(ungBtn);

    // Named groups
    groups.forEach(g => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-outline-primary org-group-btn' + (orgCurrentGroup === g.folder_name ? ' active' : '');
        btn.dataset.group = g.folder_name;
        btn.innerHTML = `${escapeHtml(g.folder_name)} <span class="badge bg-secondary ms-1">${g.card_count}</span>`;
        btn.onclick = () => selectOrgGroup(g.folder_name);
        _wireDropTarget(btn, g.folder_name);
        container.appendChild(btn);
    });

    // + New Group button (not a drop target)
    const newBtn = document.createElement('button');
    newBtn.className = 'btn btn-sm btn-outline-success org-group-btn org-new-group-btn';
    newBtn.innerHTML = '<i class="fa fa-plus"></i> New Group';
    newBtn.onclick = openNewGroupPrompt;
    container.appendChild(newBtn);
}

// ---- Load groups for active card type ----
async function loadOrgGroups(cardType, keepSelection) {
    if (!keepSelection) orgCurrentGroup = null;
    document.getElementById('orgGroupButtons').innerHTML = '<span class="text-muted small">Loading…</span>';
    document.getElementById('orgGroupToolbar').style.cssText = 'display:none !important;';
    document.getElementById('orgCardGrid').innerHTML =
        '<div class="text-muted py-4 w-100 text-center"><i class="fa fa-hand-point-up fa-2x mb-2 d-block"></i>Select a group above to manage cards</div>';
    try {
        const resp = await fetch(`/dashboards/api/admin/get_card_groups.php?card_type=${encodeURIComponent(cardType)}`);
        const data = await resp.json();
        if (data.status === 'success') {
            renderOrgGroupButtons(data.groups, data.ungrouped_count);
            if (orgCurrentGroup) {
                // Restore selection after refresh
                await loadGroupCards(cardType, orgCurrentGroup);
            }
        } else {
            document.getElementById('orgGroupButtons').innerHTML = `<span class="text-danger small">${escapeHtml(data.message)}</span>`;
        }
    } catch(e) {
        document.getElementById('orgGroupButtons').innerHTML = '<span class="text-danger small">Error loading groups</span>';
    }
}

// ---- Select group ----
async function selectOrgGroup(groupName) {
    if (orgOrderDirty) {
        const ok = await showConfirm('You have unsaved order changes. Discard them?', 'Unsaved Changes', 'Discard', 'Stay', 'warning');
        if (!ok) return;
        orgOrderDirty = false;
    }
    orgCurrentGroup = groupName;
    // Update active state on buttons
    document.querySelectorAll('.org-group-btn[data-group]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.group === groupName);
    });
    await loadGroupCards(orgCurrentType, groupName);
}

// ---- Render card tile ----
const _typeLabels = {consonant:'Consonants',vowel:'Vowels',cv_blend:'CV/Blend',
    cv:'CV',cv_blending:'CV Blending',syllable_shifts:'Syllable Shifts',
    '3cv_blend':'CV-CV','word':'Words'};
const _typeBadgeColors = {consonant:'#1e40af',vowel:'#166534',cv_blend:'#92400e',
    cv:'#92400e',cv_blending:'#b45309',syllable_shifts:'#0369a1',
    '3cv_blend':'#3730a3',word:'#9a3412'};
const _typeBadgeBgs   = {consonant:'#dbeafe',vowel:'#dcfce7',cv_blend:'#fef3c7',
    cv:'#fef3c7',cv_blending:'#fde68a',syllable_shifts:'#e0f2fe',
    '3cv_blend':'#e0e7ff',word:'#ffedd5'};

function orgCardTileHtml(card, isUngrouped) {
    const tile = document.createElement('div');
    tile.className = 'org-card-tile';
    tile.dataset.id = card.id;
    tile.dataset.cardType = card.source_type || orgCurrentType;
    tile.draggable = true;
    const ct = tile.dataset.cardType;
    const isCrossType = ct !== orgCurrentType;
    const typeBadge = isCrossType
        ? `<div style="position:absolute;top:2px;left:2px;background:${_typeBadgeBgs[ct]};color:${_typeBadgeColors[ct]};font-size:0.55rem;font-weight:700;padding:1px 4px;border-radius:3px;line-height:1.4;">${_typeLabels[ct]||ct}</div>`
        : '';
    tile.innerHTML = `
        ${!isUngrouped ? `<button class="tile-remove" title="Remove from group" onclick="removeCardFromGroup(${card.id},'${ct}')"><i class="fa fa-times"></i></button>` : ''}
        ${typeBadge}
        ${card.image_path
            ? `<img src="${escapeHtml(card.image_path)}" loading="lazy" onerror="this.style.display='none';" alt="${escapeHtml(card.sound_text)}">`
            : `<div style="width:80px;height:80px;background:#f3f4f6;border:1px dashed #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto;"><i class="fa fa-image" style="color:#9ca3af;font-size:1.4rem;"></i></div>`
        }
        <div class="tile-label">${escapeHtml(card.sound_text)}</div>
    `;
    // Wire native drag events (works alongside SortableJS for button-drop targets)
    tile.addEventListener('dragstart', e => {
        e.dataTransfer.effectAllowed = 'move';
        tile.classList.add('is-dragging');
        _orgDragStart(parseInt(tile.dataset.id), tile.dataset.cardType, orgCurrentGroup);
    });
    tile.addEventListener('dragend', () => {
        tile.classList.remove('is-dragging');
        _orgDragEnd();
    });
    return tile;
}

// ---- Load cards for selected group ----
async function loadGroupCards(cardType, groupName) {
    const grid = document.getElementById('orgCardGrid');
    const toolbar = document.getElementById('orgGroupToolbar');
    grid.innerHTML = '<div class="text-center py-3 w-100"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    try {
        const resp = await fetch(`/dashboards/api/admin/get_group_cards.php?card_type=${encodeURIComponent(cardType)}&group=${encodeURIComponent(groupName)}`);
        const data = await resp.json();
        if (data.status !== 'success') throw new Error(data.message);
        const cards = data.data;
        grid.innerHTML = '';
        const isUngrouped = groupName === '__ungrouped__';
        if (cards.length === 0) {
            grid.innerHTML = '<div class="text-muted small py-3 w-100 text-center">No cards in this group</div>';
        } else {
            cards.forEach(card => grid.appendChild(orgCardTileHtml(card, isUngrouped)));
        }
        // Init SortableJS for within-group reordering (ungrouped is drag-to-button only)
        if (orgSortable) { orgSortable.destroy(); orgSortable = null; }
        if (!isUngrouped) {
            orgSortable = Sortable.create(grid, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onStart: evt => {
                    _orgDragStart(parseInt(evt.item.dataset.id), evt.item.dataset.cardType || orgCurrentType, orgCurrentGroup);
                },
                onEnd: evt => {
                    _orgDragEnd();
                    if (_orgDroppedOnBtn) { _orgDroppedOnBtn = false; return; } // handled by button drop
                    orgOrderDirty = true;
                    document.getElementById('orgSaveOrderBtn').style.display = 'inline-flex';
                    document.getElementById('orgDragHint').style.display = 'none';
                },
            });
        }
        // Show toolbar
        toolbar.style.cssText = '';
        // Show/hide drag hint & save btn based on card count
        const saveBtn = document.getElementById('orgSaveOrderBtn');
        const hint    = document.getElementById('orgDragHint');
        saveBtn.style.display = 'none';
        orgOrderDirty = false;
        hint.style.display = (!isUngrouped && cards.length > 1) ? 'inline' : 'none';
        // Show/hide add-to-group & rename for ungrouped
        const addBtn = document.querySelector('[onclick="openAddCardsToGroupModal()"]');
        const renBtn = document.querySelector('[onclick="openRenameGroupModal()"]');
        if (addBtn) addBtn.style.display = isUngrouped ? 'none' : 'inline-flex';
        if (renBtn) renBtn.style.display = isUngrouped ? 'none' : 'inline-flex';
    } catch(e) {
        grid.innerHTML = `<div class="text-danger small py-3 w-100">${escapeHtml(e.message || 'Error loading cards')}</div>`;
    }
}

// ---- Save current card order ----
async function saveGroupOrder() {
    if (!orgCurrentGroup || orgCurrentGroup === '__ungrouped__') return;
    const orderedCards = Array.from(document.querySelectorAll('#orgCardGrid .org-card-tile')).map(t => ({id: parseInt(t.dataset.id), card_type: t.dataset.cardType || orgCurrentType}));
    const btn = document.getElementById('orgSaveOrderBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving…';
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action:'save_order', parent_card_type: orgCurrentType, group: orgCurrentGroup, ordered_cards: orderedCards }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Order saved');
            orgOrderDirty = false;
            btn.style.display = 'none';
            document.getElementById('orgDragHint').style.display = orderedCards.length > 1 ? 'inline' : 'none';
        } else {
            toastr.error(data.message || 'Failed to save order');
        }
    } catch(e) { toastr.error('Network error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Order'; }
}

// ---- Remove a card from its group ----
async function removeCardFromGroup(cardId, cardType) {
    const ok = await showConfirm('Remove this card from the group?', 'Remove', 'Remove', 'Cancel', 'warning');
    if (!ok) return;
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'remove_from_group', parent_card_type: orgCurrentType, card_type: cardType || orgCurrentType, card_id: cardId, group: orgCurrentGroup }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Card removed from group');
            await loadOrgGroups(orgCurrentType, true);
        } else {
            toastr.error(data.message || 'Failed');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- Move card to a group via drag-and-drop onto a button ----
async function moveCardToGroup(cardId, cardType, groupName, fromGroup) {
    fromGroup = fromGroup || orgCurrentGroup;
    cardType  = cardType  || orgCurrentType;
    // Moving to ungrouped = remove from source group only
    const body = groupName
        ? { action:'move_to_group', parent_card_type: orgCurrentType, card_type: cardType, card_id: cardId, from_group: fromGroup, to_group: groupName }
        : { action:'remove_from_group', parent_card_type: orgCurrentType, card_type: cardType, card_id: cardId, group: fromGroup };
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(body),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(groupName ? `Moved to "${groupName}"` : 'Removed from group');
            await loadOrgGroups(orgCurrentType, true);
        } else {
            toastr.error(data.message || 'Failed to move card');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- New Group prompt ----
async function openNewGroupPrompt() {
    document.querySelector('#renameGroupModal .modal-title').textContent = 'Create New Group';
    document.getElementById('renameGroupConfirmBtn').innerHTML = '<i class="fa fa-plus"></i> Create Group';
    document.getElementById('renameGroupInput').value = '';
    document.getElementById('renameGroupInput').dataset.mode = 'new';
    $('#renameGroupModal').modal('show');
    setTimeout(() => document.getElementById('renameGroupInput').focus(), 300);
}

// ---- Rename Group modal ----
function openRenameGroupModal() {
    if (!orgCurrentGroup || orgCurrentGroup === '__ungrouped__') {
        showAlert('Please select a named group first', 'Note', 'info'); return;
    }
    document.querySelector('#renameGroupModal .modal-title').textContent = 'Rename Group';
    document.getElementById('renameGroupConfirmBtn').innerHTML = '<i class="fa fa-save"></i> Rename';
    document.getElementById('renameGroupInput').value = orgCurrentGroup;
    document.getElementById('renameGroupInput').dataset.mode = 'rename';
    $('#renameGroupModal').modal('show');
    setTimeout(() => document.getElementById('renameGroupInput').focus(), 300);
}

async function confirmRenameGroup() {
    const input   = document.getElementById('renameGroupInput');
    const newName = input.value.trim();
    const mode    = input.dataset.mode || 'rename';
    if (!newName) { toastr.error('Group name is required'); return; }

    if (mode === 'new') {
        orgCurrentGroup = newName;
        $('#renameGroupModal').modal('hide');

        // Inject the new group button immediately (group has no cards yet so loadOrgGroups won't return it)
        const container = document.getElementById('orgGroupButtons');
        const newGroupBtn = document.createElement('button');
        newGroupBtn.className = 'btn btn-sm btn-outline-primary org-group-btn active';
        newGroupBtn.dataset.group = newName;
        newGroupBtn.innerHTML = `${escapeHtml(newName)} <span class="badge bg-secondary ms-1">0</span>`;
        newGroupBtn.onclick = () => selectOrgGroup(newName);
        _wireDropTarget(newGroupBtn, newName);
        // Insert before the "+ New Group" button
        const newBtn = container.querySelector('.org-new-group-btn');
        container.insertBefore(newGroupBtn, newBtn || null);

        // Deactivate all other group buttons
        container.querySelectorAll('.org-group-btn[data-group]').forEach(btn => {
            if (btn !== newGroupBtn) btn.classList.remove('active');
        });

        // Show an empty grid with the toolbar so the user can add cards
        const grid    = document.getElementById('orgCardGrid');
        const toolbar = document.getElementById('orgGroupToolbar');
        grid.innerHTML = '<div class="text-muted py-4 w-100 text-center"><i class="fa fa-layer-group fa-2x mb-2 d-block"></i>Group is empty — use "Add Cards to Group" to get started.</div>';
        toolbar.style.cssText = '';
        const addBtn = document.querySelector('[onclick="openAddCardsToGroupModal()"]');
        const renBtn = document.querySelector('[onclick="openRenameGroupModal()"]');
        if (addBtn) addBtn.style.display = 'inline-flex';
        if (renBtn) renBtn.style.display = 'inline-flex';

        // Prompt user to add cards right away
        await openAddCardsToGroupModal();
        return;
    }

    // Rename existing
    if (newName === orgCurrentGroup) { $('#renameGroupModal').modal('hide'); return; }
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'rename_group', parent_card_type: orgCurrentType, old_name: orgCurrentGroup, new_name: newName }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Group renamed');
            orgCurrentGroup = newName;
            $('#renameGroupModal').modal('hide');
            await loadOrgGroups(orgCurrentType, true);
        } else {
            toastr.error(data.message || 'Failed');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- Delete Group ----
async function deleteGroup() {
    if (!orgCurrentGroup || orgCurrentGroup === '__ungrouped__') {
        showAlert('Please select a named group first', 'Note', 'info'); return;
    }
    const groupName = orgCurrentGroup;
    const btn = document.querySelector(`[data-group="${CSS.escape(groupName)}"]`);
    const count = btn ? (btn.querySelector('.badge')?.textContent?.trim() || '0') : '?';
    const ok = await showConfirm(
        `Delete group "<strong>${escapeHtml(groupName)}</strong>"?<br><small class="text-muted">${count} card assignment(s) will be removed. The cards themselves are not deleted.</small>`,
        'Delete Group', 'Delete', 'Cancel', 'danger'
    );
    if (!ok) return;
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_group', parent_card_type: orgCurrentType, group: groupName }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            orgCurrentGroup = null;
            orgOrderDirty   = false;
            await loadOrgGroups(orgCurrentType, false);
        } else {
            toastr.error(data.message || 'Failed to delete group');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- Reset to Default (enterprise_admin only) ----
async function resetOrgToDefault() {
    const ok = await showConfirm(
        'This will remove your custom card organization and restore the default layout from your administrator.<br><small class="text-muted">Your changes will be lost and cannot be undone.</small>',
        'Reset to Default', 'Reset', 'Cancel', 'danger'
    );
    if (!ok) return;
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset_to_default', parent_card_type: orgCurrentType }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Card organization reset to default');
            orgCurrentGroup = null;
            orgOrderDirty   = false;
            await loadOrgGroups(orgCurrentType, false);
        } else {
            toastr.error(data.message || 'Failed to reset');
        }
    } catch(e) { toastr.error('Network error'); }
}

// Allow Enter key in rename input
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('renameGroupInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') confirmRenameGroup();
    });
});

// ---- Add Cards to Group modal ----
let _addCardsAllCards  = [];
const _addCardsSelected = new Map();  // "type:id" → {id, card_type}

function _updateAddSelCount() {
    const el = document.getElementById('addCardsSelCount');
    if (el) el.textContent = _addCardsSelected.size > 0 ? `${_addCardsSelected.size} card(s) selected across all tabs` : '';
}

function _renderAddCardsPicker(filterType) {
    const grid = document.getElementById('addCardsPickerGrid');
    const visible = _addCardsAllCards.filter(c => c.source_type === filterType);
    grid.innerHTML = '';
    if (visible.length === 0) {
        grid.innerHTML = '<div class="text-muted small py-3 w-100 text-center">No cards available in this category</div>';
        _updateAddSelCount();
        return;
    }
    visible.forEach(card => {
        const key  = `${card.source_type}:${card.id}`;
        const div  = document.createElement('div');
        div.className = 'org-card-tile';
        div.style.cursor = 'pointer';
        div.dataset.id = card.id;
        div.dataset.cardType = card.source_type;
        const isChecked = _addCardsSelected.has(key);
        const groupBadge = card.current_groups
            ? `<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(28,132,198,0.85);color:#fff;font-size:0.6rem;padding:1px 3px;border-radius:0 0 4px 4px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" title="${escapeHtml(card.current_groups)}">${escapeHtml(card.current_groups)}</div>`
            : '';
        div.innerHTML = `
            <div class="form-check" style="position:absolute;top:4px;left:4px;">
                <input class="form-check-input picker-cb" type="checkbox" value="${card.id}" data-card-type="${escapeHtml(card.source_type)}" id="pick-${escapeHtml(card.source_type)}-${card.id}"${isChecked?' checked':''}>
            </div>
            ${card.image_path
                ? `<img src="${escapeHtml(card.image_path)}" loading="lazy" alt="${escapeHtml(card.sound_text)}">`
                : `<div style="width:80px;height:80px;background:#f3f4f6;border:1px dashed #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto;"><i class="fa fa-image" style="color:#9ca3af;font-size:1.4rem;"></i></div>`
            }
            <div class="tile-label">${escapeHtml(card.sound_text)}</div>
            ${groupBadge}
        `;
        if (isChecked) div.style.outline = '2px solid #1c84c6';
        const syncSel = cb => {
            const k = `${card.source_type}:${card.id}`;
            if (cb.checked) { _addCardsSelected.set(k, {id: card.id, card_type: card.source_type}); div.style.outline = '2px solid #1c84c6'; }
            else            { _addCardsSelected.delete(k); div.style.outline = ''; }
            _updateAddSelCount();
        };
        div.addEventListener('click', e => {
            const cb = div.querySelector('.picker-cb');
            if (!e.target.classList.contains('picker-cb')) cb.checked = !cb.checked;
            syncSel(cb);
        });
        grid.appendChild(div);
    });
    _updateAddSelCount();
}

async function openAddCardsToGroupModal() {
    if (!orgCurrentGroup || orgCurrentGroup === '__ungrouped__') {
        showAlert('Please select a named group first', 'Note', 'info'); return;
    }
    _addCardsSelected.clear();
    _addCardsAllCards = [];
    document.getElementById('addCardsGroupName').textContent = orgCurrentGroup;
    document.getElementById('addCardsPickerGrid').innerHTML =
        '<div class="text-center w-100 py-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';

    // Wire type tabs
    document.querySelectorAll('#addCardsTabs .nav-link').forEach(tab => {
        tab.onclick = e => {
            e.preventDefault();
            document.querySelectorAll('#addCardsTabs .nav-link').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            _renderAddCardsPicker(tab.dataset.tabType);
        };
    });
    document.querySelectorAll('#addCardsTabs .nav-link').forEach((t,i) => t.classList.toggle('active', i===0));

    $('#addCardsToGroupModal').modal('show');
    try {
        const url = `/dashboards/api/admin/get_group_cards.php?card_type=${encodeURIComponent(orgCurrentType)}&group=__available__&exclude_group=${encodeURIComponent(orgCurrentGroup)}`;
        const data = await fetch(url).then(r => r.json());
        if (data.status !== 'success') throw new Error(data.message);
        _addCardsAllCards = data.data;

        // Badge each tab with available count
        const countsByType = {};
        _addCardsAllCards.forEach(c => { countsByType[c.source_type] = (countsByType[c.source_type]||0)+1; });
        document.querySelectorAll('#addCardsTabs .nav-link').forEach(tab => {
            const n = countsByType[tab.dataset.tabType] || 0;
            tab.textContent = tab.textContent.replace(/ \d+$/,'');
            if (n > 0) tab.innerHTML += ` <span class="badge bg-secondary">${n}</span>`;
        });

        _renderAddCardsPicker('consonant');
    } catch(e) {
        document.getElementById('addCardsPickerGrid').innerHTML =
            `<div class="text-danger small py-3 w-100">${escapeHtml(e.message || 'Error')}</div>`;
    }
}

async function confirmAddCardsToGroup() {
    if (_addCardsSelected.size === 0) { toastr.warning('No cards selected'); return; }
    const cards = Array.from(_addCardsSelected.values());
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ action:'assign_to_group', parent_card_type: orgCurrentType, group: orgCurrentGroup, cards }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            $('#addCardsToGroupModal').modal('hide');
            await loadOrgGroups(orgCurrentType, true);
        } else {
            toastr.error(data.message || 'Failed');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- Copy Cards to Group modal ----
async function openCopyCardsModal() {
    if (!orgCurrentGroup || orgCurrentGroup === '__ungrouped__') {
        showAlert('Select a named group first', 'Note', 'info'); return;
    }

    // Populate target group dropdown with all other groups
    const select = document.getElementById('copyCardsTargetGroup');
    select.innerHTML = '<option value="">— select a group —</option>';
    document.querySelectorAll('.org-group-btn[data-group]').forEach(btn => {
        const grp = btn.dataset.group;
        if (grp === orgCurrentGroup || grp === '__ungrouped__') return;
        const opt = document.createElement('option');
        opt.value = grp;
        opt.textContent = grp;
        select.appendChild(opt);
    });

    if (select.options.length <= 1) {
        showAlert('No other groups to copy to. Create another group first.', 'Note', 'info'); return;
    }

    document.getElementById('copyCardsFromGroup').textContent  = orgCurrentGroup;
    document.getElementById('copyCardsFromGroup2').textContent = orgCurrentGroup;

    const grid = document.getElementById('copyCardsPickerGrid');
    grid.innerHTML = '<div class="text-center w-100 py-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    $('#copyCardsModal').modal('show');

    try {
        const resp = await fetch(`/dashboards/api/admin/get_group_cards.php?card_type=${encodeURIComponent(orgCurrentType)}&group=${encodeURIComponent(orgCurrentGroup)}`);
        const data = await resp.json();
        if (data.status !== 'success') throw new Error(data.message);
        const cards = data.data;
        grid.innerHTML = '';
        if (cards.length === 0) {
            grid.innerHTML = '<div class="text-muted small py-3 w-100 text-center">No cards in this group</div>';
            return;
        }
        cards.forEach(card => {
            const div = document.createElement('div');
            div.className = 'org-card-tile';
            div.style.cursor = 'pointer';
            div.dataset.id = card.id;
            div.dataset.cardType = card.source_type || orgCurrentType;
            const ct = div.dataset.cardType;
            const isCross = ct !== orgCurrentType;
            const typeBadge = isCross
                ? `<div style="position:absolute;top:2px;left:2px;background:${_typeBadgeBgs[ct]||'#e5e7eb'};color:${_typeBadgeColors[ct]||'#374151'};font-size:0.55rem;font-weight:700;padding:1px 4px;border-radius:3px;line-height:1.4;">${_typeLabels[ct]||ct}</div>`
                : '';
            div.innerHTML = `
                <div class="form-check" style="position:absolute;top:4px;left:4px;">
                    <input class="form-check-input picker-cb" type="checkbox" value="${card.id}" data-card-type="${escapeHtml(ct)}" id="copy-${escapeHtml(ct)}-${card.id}">
                </div>
                ${typeBadge}
                ${card.image_path
                    ? `<img src="${escapeHtml(card.image_path)}" loading="lazy" alt="${escapeHtml(card.sound_text)}">`
                    : `<div style="width:80px;height:80px;background:#f3f4f6;border:1px dashed #d1d5db;border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto;"><i class="fa fa-image" style="color:#9ca3af;font-size:1.4rem;"></i></div>`
                }
                <div class="tile-label">${escapeHtml(card.sound_text)}</div>
            `;
            div.addEventListener('click', (e) => {
                if (e.target.classList.contains('picker-cb')) return;
                const cb = div.querySelector('.picker-cb');
                cb.checked = !cb.checked;
                div.style.outline = cb.checked ? '2px solid #17a2b8' : '';
            });
            grid.appendChild(div);
        });
    } catch(e) {
        grid.innerHTML = `<div class="text-danger small py-3 w-100">${escapeHtml(e.message || 'Error')}</div>`;
    }
}

async function confirmCopyCards() {
    const targetGroup = document.getElementById('copyCardsTargetGroup').value;
    if (!targetGroup) { toastr.warning('Please select a destination group'); return; }
    const checked = document.querySelectorAll('#copyCardsPickerGrid .picker-cb:checked');
    if (checked.length === 0) { toastr.warning('No cards selected'); return; }
    const cards = Array.from(checked).map(cb => ({id: parseInt(cb.value), card_type: cb.dataset.cardType || orgCurrentType}));
    try {
        const resp = await fetch('/dashboards/api/admin/save_card_organization.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                action: 'copy_to_group',
                parent_card_type: orgCurrentType,
                from_group: orgCurrentGroup,
                target_group: targetGroup,
                cards,
            }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            $('#copyCardsModal').modal('hide');
            await loadOrgGroups(orgCurrentType, true);
        } else {
            toastr.error(data.message || 'Failed');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- Wire up card-type buttons in org tab ----
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.org-type-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (orgOrderDirty) {
                const ok = await showConfirm('You have unsaved order changes. Discard them?', 'Unsaved Changes', 'Discard', 'Stay', 'warning');
                if (!ok) return;
                orgOrderDirty = false;
            }
            document.querySelectorAll('.org-type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            orgCurrentType  = btn.dataset.type;
            orgCurrentGroup = null;
            await loadOrgGroups(orgCurrentType, false);
        });
    });

    // Load on first tab click
    document.getElementById('tab-org').addEventListener('shown.bs.tab', () => {
        loadOrgGroups(orgCurrentType, false);
    });
});

// ================================================================
// TAB 3 — SUCCESS MEDIA
// ================================================================
let mediaTable  = null;
let mediaLoaded = false;
const _mediaStore = {};   // keyed by media_id — avoids quoting issues in onclick attrs

function mediaThumbnailHtml(media) {
    if (media.media_type === 'image' && media.media_path) {
        return `<img src="${escapeHtml(media.media_path)}" class="card-thumbnail" loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="thumb-placeholder" style="display:none;"><i class="fa fa-image"></i></div>`;
    }
    return `<div class="thumb-placeholder" style="width:108px;height:108px;"><i class="fa fa-video fa-2x"></i></div>`;
}

function mediaTypeBadge(media) {
    if (media.media_type === 'video') return `<span class="content-type-badge badge-video"><i class="fa fa-video me-1"></i>Video</span>`;
    return `<span class="content-type-badge badge-image"><i class="fa fa-image me-1"></i>Image</span>`;
}

function mediaDefaultBadge(isDefault) {
    return isDefault
        ? `<span class="badge bg-success text-white" style="font-size:0.85rem;"><i class="fa fa-star me-1" style="color:#fff;"></i>Default</span>`
        : `<span class="text-muted small">—</span>`;
}

function mediaAssignmentsCellHtml(names) {
    if (!names || names.length === 0) return '<span class="text-muted small">None</span>';
    return names.map(n => `<span class="badge bg-light text-dark border me-1 mb-1" style="font-size:0.85rem;">${escapeHtml(n)}</span>`).join('');
}

function mediaSettingsBadges(media) {
    if (media.media_type !== 'video') return '<span class="text-muted small">—</span>';
    const sound = media.allow_sound
        ? `<span class="badge bg-success me-1" title="Sound on"><i class="fa fa-volume-up"></i></span>`
        : `<span class="badge bg-secondary me-1" title="Muted"><i class="fa fa-volume-mute"></i></span>`;
    const loop = media.autoplay_loop
        ? `<span class="badge bg-info" title="Loops"><i class="fa fa-redo"></i></span>`
        : `<span class="badge bg-secondary" title="Plays once"><i class="fa fa-play"></i></span>`;
    return sound + loop;
}

function initMediaTable(data) {
    if (mediaTable) { mediaTable.destroy(); $('#mediaTable tbody').empty(); }
    // Populate lookup store (avoids quote-escaping issues in onclick attributes)
    data.forEach(m => { _mediaStore[m.media_id] = m; });
    const tbody = document.getElementById('mediaTableBody');
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No media uploaded yet</td></tr>';
    }
    data.forEach(media => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${mediaThumbnailHtml(media)}</td>
            <td>
                <strong>${escapeHtml(media.media_name)}</strong>
                <br><small class="text-muted">${new Date(media.created_at).toLocaleDateString()}</small>
            </td>
            <td>${mediaTypeBadge(media)}</td>
            <td>${mediaDefaultBadge(media.is_default)}</td>
            <td>${mediaAssignmentsCellHtml(media.assignment_names)}</td>
            <td>${mediaSettingsBadges(media)}</td>
            <td class="text-nowrap">${formatFileSize(media.file_size_bytes)}</td>
            <td>
                <div class="btn-group-vertical btn-group-sm w-100">
                    <button class="btn btn-outline-primary" onclick="openEditMediaModal(_mediaStore[${media.media_id}])">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-outline-success" onclick="openUseMediaModal(_mediaStore[${media.media_id}])">
                        <i class="fa fa-link"></i> Assign
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteMedia(${media.media_id})">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
    mediaTable = $('#mediaTable').DataTable({
        pageLength: 25,
        order: [[1,'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 3, 4, 5, 7] },
        ],
        language: { search: 'Search media:', emptyTable: 'No media uploaded yet' },
    });
    // Mobile: title = file name (col 1), hide thumbnail (col 0) and metadata cols
    if (window.MKAMobile) {
        MKAMobile.initTable('#mediaTable', {
            titleCol   : 1,
            hideCols   : [0, 3, 4, 5],
            dtInstance : mediaTable
        });
    }
}

async function loadMedia() {
    try {
        const resp = await fetch('/dashboards/api/admin/get_media_full.php');
        const data = await resp.json();
        if (data.status === 'success') {
            initMediaTable(data.data);
            mediaLoaded = true;
        } else {
            document.getElementById('mediaTableBody').innerHTML =
                `<tr><td colspan="8" class="text-center text-danger">Error: ${escapeHtml(data.message)}</td></tr>`;
        }
    } catch(e) { console.error(e); }
}

// ---- Upload Media Modal ----
let _lastUploadedMediaId = null;

async function openUploadMediaModal() {
    _lastUploadedMediaId = null;
    document.getElementById('mediaUploadForm').reset();
    document.getElementById('mediaPreview').style.display        = 'none';
    document.getElementById('soundControls').style.display       = 'none';
    document.getElementById('uploadMediaError').style.display    = 'none';
    document.getElementById('uploadModalFooter').style.display   = '';
    document.getElementById('uploadSuccessFooter').style.display = 'none';
    document.getElementById('uploadSetDefault').checked          = false;
    document.getElementById('uploadAssignmentsList').innerHTML   =
        '<div class="text-center py-2 text-muted small"><i class="fa fa-spinner fa-spin me-1"></i>Loading…</div>';
    $('#uploadMediaModal').modal('show');

    // Load assignments in background
    try {
        const resp = await fetch('/dashboards/api/admin/get_assignments.php');
        const data = await resp.json();
        const assignments = (data.status === 'success' ? data.data : []) || [];
        document.getElementById('uploadAssignmentsList').innerHTML = assignments.length === 0
            ? '<div class="text-muted small p-2">No assignments created yet</div>'
            : assignments.map(a => `
                <div class="form-check">
                    <input class="form-check-input upload-assign-cb" type="checkbox"
                           id="upassign-${a.assignment_group_id}" value="${a.assignment_group_id}">
                    <label class="form-check-label" for="upassign-${a.assignment_group_id}">
                        ${escapeHtml(a.assignment_name)}
                        <small class="text-muted d-block">${a.exercise_count || 0} exercise(s)</small>
                    </label>
                </div>`).join('');
    } catch(e) {
        document.getElementById('uploadAssignmentsList').innerHTML =
            '<div class="text-muted small p-2">Could not load assignments</div>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('mediaFile').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const pContainer = document.getElementById('mediaPreview');
        const pVideo     = document.getElementById('previewVideo');
        const pImage     = document.getElementById('previewImage');
        const mInfo      = document.getElementById('mediaInfo');
        const sControls  = document.getElementById('soundControls');
        if (!file) { pContainer.style.display = 'none'; return; }
        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');
        if (!isVideo && !isImage) { showAlert('Please select a video or image file', 'Error', 'danger'); event.target.value = ''; return; }
        const maxSize = isVideo ? 10*1024*1024 : 5*1024*1024;
        if (file.size > maxSize) { showAlert('File too large', 'Error', 'danger'); event.target.value = ''; return; }
        const url = URL.createObjectURL(file);
        pContainer.style.display = 'block';
        if (isVideo) {
            pVideo.style.display = 'block'; pImage.style.display = 'none'; pVideo.src = url; sControls.style.display = 'block';
            pVideo.addEventListener('loadedmetadata', () => {
                if (pVideo.duration > 10) showAlert('Video exceeds 10 seconds', 'Warning', 'warning');
                mInfo.innerHTML = `<strong>Duration:</strong> ${pVideo.duration.toFixed(2)}s &nbsp; <strong>Size:</strong> ${formatFileSize(file.size)}`;
            });
        } else {
            pVideo.style.display = 'none'; pImage.style.display = 'block'; pImage.src = url; sControls.style.display = 'none';
            pImage.onload = () => { mInfo.innerHTML = `<strong>${pImage.naturalWidth}×${pImage.naturalHeight}px</strong> &nbsp; ${formatFileSize(file.size)}`; };
        }
        const nameInput = document.getElementById('mediaName');
        if (!nameInput.value) {
            const s = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
            nameInput.value = s.charAt(0).toUpperCase() + s.slice(1);
        }
    });
});

async function handleMediaUpload() {
    const fileInput = document.getElementById('mediaFile');
    const file      = fileInput.files[0];
    const errorEl   = document.getElementById('uploadMediaError');
    errorEl.style.display = 'none';
    if (!file) { errorEl.textContent = 'Please select a file.'; errorEl.style.display = 'block'; return; }
    const isVideo = file.type.startsWith('video/');
    const isImage = file.type.startsWith('image/');
    if (!isVideo && !isImage) { errorEl.textContent = 'Please select a video or image file.'; errorEl.style.display = 'block'; return; }
    const maxSize = isVideo ? 10*1024*1024 : 5*1024*1024;
    if (file.size > maxSize) { errorEl.textContent = 'File too large.'; errorEl.style.display = 'block'; return; }
    const formData = new FormData();
    formData.append('media_file', file);
    formData.append('media_name', document.getElementById('mediaName').value);
    if (isVideo) {
        formData.append('allow_sound',   document.getElementById('allowSound').checked   ? '1' : '0');
        formData.append('autoplay_loop', document.getElementById('autoplayLoop').checked ? '1' : '0');
    }
    const btn = document.getElementById('btnUploadMedia');
    btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Uploading…';
    try {
        const resp = await fetch('/dashboards/api/admin/upload_media.php', { method:'POST', body: formData });
        const data = await resp.json();
        if (data.status === 'success') {
            _lastUploadedMediaId = data.media_id;

            // Save default setting
            const setDefault = document.getElementById('uploadSetDefault').checked;
            if (setDefault) {
                await fetch('/dashboards/api/admin/set_default_media.php', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ media_id: data.media_id })
                });
            }

            // Save assignment selections
            const checked = document.querySelectorAll('#uploadAssignmentsList .upload-assign-cb:checked');
            const assignmentIds = Array.from(checked).map(cb => parseInt(cb.value));
            if (assignmentIds.length > 0) {
                await fetch('/dashboards/api/admin/assign_media_to_assignments.php', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ media_id: data.media_id, assignment_ids: assignmentIds })
                });
            }

            // Reload table
            mediaLoaded = false;
            await loadMedia();

            document.getElementById('uploadModalFooter').style.display   = 'none';
            document.getElementById('uploadSuccessFooter').style.display = '';
        } else { errorEl.textContent = data.message || 'Upload failed.'; errorEl.style.display = 'block'; }
    } catch(e) { errorEl.textContent = 'Network error.'; errorEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa fa-upload me-1"></i>Upload'; }
}

// ---- Edit Media Modal ----
let _editSelectedFileType = null; // 'video' | 'image' | null (no new file)

function _editShowVideoSettings(show) {
    document.getElementById('editMediaVideoSettings').style.display = show ? '' : 'none';
}

function _editCurrentPreviewHtml(media) {
    if (media.media_type === 'image' && media.media_path) {
        return `<img src="${escapeHtml(media.media_path)}" style="max-width:100%;max-height:160px;border-radius:6px;border:1px solid #dee2e6;" alt="Current">`;
    }
    if (media.media_type === 'video' && media.media_path) {
        return `<video src="${escapeHtml(media.media_path)}" controls style="max-width:100%;max-height:160px;border-radius:6px;"></video>`;
    }
    return `<div class="thumb-placeholder" style="width:100%;height:100px;"><i class="fa fa-file-alt fa-2x"></i></div>`;
}

function openEditMediaModal(media) {
    _editSelectedFileType = null;
    document.getElementById('editMediaId').value          = media.media_id;
    document.getElementById('editMediaCurrentType').value = media.media_type;
    document.getElementById('editMediaName').value        = media.media_name;
    document.getElementById('editMediaFile').value        = '';
    document.getElementById('editNewPreview').style.display = 'none';
    document.getElementById('editMediaError').style.display = 'none';
    document.getElementById('editCurrentPreview').innerHTML = _editCurrentPreviewHtml(media);
    document.getElementById('editSetDefault').checked    = !!media.is_default;

    const isVideo = media.media_type === 'video';
    document.getElementById('editAllowSound').checked   = media.allow_sound;
    document.getElementById('editAutoplayLoop').checked = media.autoplay_loop;
    _editShowVideoSettings(isVideo);

    const btn = document.getElementById('editMediaSaveBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';

    $('#editMediaModal').modal('show');
}

function onEditFileSelected(event) {
    const file = event.target.files[0];
    const previewWrap = document.getElementById('editNewPreview');
    const errEl = document.getElementById('editMediaError');
    errEl.style.display = 'none';

    if (!file) {
        previewWrap.style.display = 'none';
        _editSelectedFileType = null;
        // Revert settings to current media type
        const curType = document.getElementById('editMediaCurrentType').value;
        _editShowVideoSettings(curType === 'video');
        return;
    }

    const isVideo = file.type.startsWith('video/');
    const isImage = file.type.startsWith('image/');
    if (!isVideo && !isImage) {
        errEl.textContent = 'Please select a video or image file.';
        errEl.style.display = 'block';
        event.target.value = '';
        return;
    }
    const maxSize = isVideo ? 10*1024*1024 : 5*1024*1024;
    if (file.size > maxSize) {
        errEl.textContent = `File too large (max ${isVideo?'10':'5'} MB).`;
        errEl.style.display = 'block';
        event.target.value = '';
        return;
    }

    _editSelectedFileType = isVideo ? 'video' : 'image';
    _editShowVideoSettings(isVideo);

    const url = URL.createObjectURL(file);
    const vid  = document.getElementById('editNewVideo');
    const img  = document.getElementById('editNewImage');
    const info = document.getElementById('editNewInfo');
    previewWrap.style.display = 'block';

    if (isVideo) {
        vid.style.display = 'block'; img.style.display = 'none';
        vid.src = url;
        vid.addEventListener('loadedmetadata', () => {
            if (vid.duration > 10) showAlert('Video exceeds 10 seconds', 'Warning', 'warning');
            info.textContent = `${vid.duration.toFixed(1)}s · ${formatFileSize(file.size)}`;
        }, { once: true });
    } else {
        vid.style.display = 'none'; img.style.display = 'block';
        img.src = url;
        img.onload = () => { info.textContent = `${img.naturalWidth}×${img.naturalHeight}px · ${formatFileSize(file.size)}`; };
    }
}

async function saveMediaEdit() {
    const mediaId = document.getElementById('editMediaId').value;
    const newName = document.getElementById('editMediaName').value.trim();
    const errEl   = document.getElementById('editMediaError');
    const btn     = document.getElementById('editMediaSaveBtn');
    errEl.style.display = 'none';
    if (!newName) { errEl.textContent = 'Name is required.'; errEl.style.display = 'block'; return; }

    const fileInput    = document.getElementById('editMediaFile');
    const hasNewFile   = fileInput.files.length > 0;
    const effectiveType = _editSelectedFileType || document.getElementById('editMediaCurrentType').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving…';

    try {
        let data;
        if (hasNewFile) {
            // Use multipart replace_media endpoint
            const fd = new FormData();
            fd.append('media_id',     mediaId);
            fd.append('media_name',   newName);
            fd.append('media_file',   fileInput.files[0]);
            if (effectiveType === 'video') {
                fd.append('allow_sound',   document.getElementById('editAllowSound').checked   ? '1' : '0');
                fd.append('autoplay_loop', document.getElementById('editAutoplayLoop').checked ? '1' : '0');
            }
            const resp = await fetch('/dashboards/api/admin/replace_media.php', { method:'POST', body: fd });
            data = await resp.json();
        } else {
            // Metadata-only update
            const payload = { media_id: parseInt(mediaId), media_name: newName };
            if (effectiveType === 'video') {
                payload.allow_sound   = document.getElementById('editAllowSound').checked;
                payload.autoplay_loop = document.getElementById('editAutoplayLoop').checked;
            }
            const resp = await fetch('/dashboards/api/admin/update_media.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
            });
            data = await resp.json();
        }

        if (data.status === 'success') {
            // Handle default setting
            const setDefault = document.getElementById('editSetDefault').checked;
            const wasDefault  = !!(_mediaStore[mediaId] && _mediaStore[mediaId].is_default);
            if (setDefault && !wasDefault) {
                await fetch('/dashboards/api/admin/set_default_media.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ media_id: mediaId })
                });
            } else if (!setDefault && wasDefault) {
                await fetch('/dashboards/api/admin/clear_default_media.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ media_id: mediaId })
                });
            }

            toastr.success('Media updated');
            $('#editMediaModal').modal('hide');
            mediaLoaded = false;
            await loadMedia();
        } else {
            errEl.textContent = data.message || 'Save failed.';
            errEl.style.display = 'block';
        }
    } catch(e) {
        errEl.textContent = 'Network error.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save me-1"></i>Save Changes';
    }
}

// ---- Assign Media Modal ----
async function openUseMediaModal(media) {
    const mediaId = media.media_id;
    document.getElementById('useMediaId').value   = mediaId;
    document.getElementById('useMediaName').textContent = media.media_name;
    document.getElementById('useAsDefault').checked = media.is_default;
    document.getElementById('useMediaAssignmentsList').innerHTML =
        '<div class="text-center py-2"><i class="fa fa-spinner fa-spin"></i> Loading…</div>';
    $('#useMediaModal').modal('show');

    try {
        const [assignResp, maResp] = await Promise.all([
            fetch('/dashboards/api/admin/get_assignments.php'),
            fetch(`/dashboards/api/admin/get_media_assignments.php?media_id=${mediaId}`)
        ]);
        const assignData  = await assignResp.json();
        const maData      = await maResp.json();
        const assignedIds = maData.status === 'success' ? maData.data.map(a => parseInt(a.assignment_group_id)) : [];
        const assignments = (assignData.status === 'success' ? assignData.data : []) || [];
        document.getElementById('useMediaAssignmentsList').innerHTML = assignments.length === 0
            ? '<div class="text-muted small p-2">No assignments created yet</div>'
            : assignments.map(a => `
                <div class="form-check">
                    <input class="form-check-input assignment-cb" type="checkbox"
                           id="ma-${mediaId}-${a.assignment_group_id}" value="${a.assignment_group_id}"
                           ${assignedIds.includes(parseInt(a.assignment_group_id)) ? 'checked' : ''}>
                    <label class="form-check-label" for="ma-${mediaId}-${a.assignment_group_id}">
                        ${escapeHtml(a.assignment_name)}
                        <small class="text-muted d-block">${a.exercise_count || 0} exercise(s)</small>
                    </label>
                </div>`).join('');
    } catch(e) {
        document.getElementById('useMediaAssignmentsList').innerHTML = '<div class="alert alert-danger small">Error loading assignments</div>';
    }
}

async function setAsDefault(mediaId, isDefault) {
    const ep = isDefault ? '/dashboards/api/admin/set_default_media.php' : '/dashboards/api/admin/clear_default_media.php';
    try {
        const resp = await fetch(ep, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({media_id: mediaId}) });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(isDefault ? 'Set as default' : 'Removed as default');
            mediaLoaded = false; // will refresh table when modal closes
        } else {
            toastr.error(data.message || 'Failed');
            document.getElementById('useAsDefault').checked = !isDefault;
        }
    } catch(e) { toastr.error('Error'); }
}

async function saveMediaAssignments() {
    const mediaId       = document.getElementById('useMediaId').value;
    const checkboxes    = document.querySelectorAll('#useMediaAssignmentsList .assignment-cb:checked');
    const assignmentIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    try {
        const resp = await fetch('/dashboards/api/admin/assign_media_to_assignments.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ media_id: mediaId, assignment_ids: assignmentIds })
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Assignments saved');
            $('#useMediaModal').modal('hide');
            mediaLoaded = false;
            await loadMedia();
        } else toastr.error(data.message || 'Failed');
    } catch(e) { toastr.error('Network error'); }
}

// ---- Delete Media ----
async function deleteMedia(mediaId) {
    const confirmed = await showConfirm('Delete this media? This cannot be undone.', 'Delete Media', 'Delete', 'Cancel', 'danger');
    if (!confirmed) return;
    try {
        const resp = await fetch('/dashboards/api/admin/delete_media.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({media_id: mediaId})
        });
        const data = await resp.json();
        if (data.status === 'success') { toastr.success('Deleted'); mediaLoaded = false; await loadMedia(); }
        else toastr.error(data.message || 'Failed');
    } catch(e) { toastr.error('Delete failed'); }
}

// ================================================================
// TAB 3 — ASSIGNMENTS
// ================================================================
let assignmentsTable  = null;
let assignmentsLoaded = false;
const _assignmentStore = {};

function assignedToHtml(names) {
    if (!names || names.length === 0) return '<span class="text-muted small">—</span>';
    return names.map(n => `<span class="badge bg-light text-dark border me-1" style="font-size:0.85rem;">${escapeHtml(n)}</span>`).join('');
}

function initAssignmentsTable(data) {
    if (assignmentsTable) { assignmentsTable.destroy(); $('#assignmentsTable tbody').empty(); }
    data.forEach(a => { _assignmentStore[a.assignment_group_id] = a; });
    const tbody = document.getElementById('assignmentsTableBody');
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No assignments found</td></tr>';
    }
    data.forEach(a => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${escapeHtml(a.assignment_name)}</strong></td>
            <td>${escapeHtml(a.assignment_description || '')}</td>
            <td>${assignedToHtml(a.assigned_names)}</td>
            <td class="text-center">${a.exercise_count}</td>
            <td class="text-nowrap">${new Date(a.created_at).toLocaleDateString()}</td>
            <td>
                <div class="btn-group-vertical btn-group-sm w-100">
                    <button class="btn btn-outline-primary" onclick="editAssignment(${a.assignment_group_id})">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-outline-success" onclick="assignAssignment(${a.assignment_group_id})">
                        <i class="fa fa-link"></i> Assign
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteAssignment(${a.assignment_group_id})">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
    assignmentsTable = $('#assignmentsTable').DataTable({
        pageLength: 25,
        order: [[4, 'desc']],
        columnDefs: [
            { orderable: false, targets: [2, 5] },
        ],
        language: { search: 'Search assignments:', emptyTable: 'No assignments found' },
    });
    // Mobile: title = assignment title (col 0), hide description and date cols
    if (window.MKAMobile) {
        MKAMobile.initTable('#assignmentsTable', {
            titleCol   : 0,
            hideCols   : [1, 4],
            dtInstance : assignmentsTable
        });
    }
}

async function loadAssignments() {
    try {
        const resp = await fetch('/dashboards/api/admin/get_assignments_full.php');
        const data = await resp.json();
        if (data.status === 'success') {
            initAssignmentsTable(data.data);
            assignmentsLoaded = true;
        } else {
            document.getElementById('assignmentsTableBody').innerHTML =
                `<tr><td colspan="6" class="text-center text-danger">Error: ${escapeHtml(data.message)}</td></tr>`;
        }
    } catch(e) { console.error(e); }
}

// ================================================================
// ASSIGNMENT MODAL — content globals + helpers
// ================================================================
let CONSONANTS     = [];
let VOWELS         = [];
let WORDS          = [];
let CV_BLEND_ITEMS = [];
let BLEND_3CV_ITEMS = [];
let CONSONANT_IMAGES = {};
let VOWEL_IMAGES   = {};
let CV_IMAGES      = {};
let contentDataLoaded = false;

const IMG_BASE  = '/assets/portal/exercises/images/';
const ASSET_VER = '<?= max(
    filemtime('/opt/mka/public/assets/portal/exercises/images/generated_images_consonants'),
    filemtime('/opt/mka/public/assets/portal/exercises/images/generated_images_cv'),
    filemtime('/opt/mka/public/assets/portal/exercises/images/generated_images_vowels')
) ?>';

function parseCVCode(code) {
    if (code.includes('-')) { const idx = code.indexOf('-'); return [code.slice(0, idx), code.slice(idx + 1)]; }
    const two = code.slice(0, 2);
    if (['CH', 'SH', 'TH'].includes(two)) return [two, code.slice(2)];
    return [code[0], code.slice(1)];
}

async function loadContentData() {
    if (contentDataLoaded) return;
    try {
        const [consResp, vowResp, wordResp, cvResp, cv3Resp] = await Promise.all([
            fetch('/dashboards/api/admin/get_content.php?type=consonant'),
            fetch('/dashboards/api/admin/get_content.php?type=vowel'),
            fetch('/dashboards/api/admin/get_content.php?type=word'),
            fetch('/dashboards/api/admin/get_content.php?type=cv_blend'),
            fetch('/dashboards/api/admin/get_content.php?type=3cv_blend'),
        ]);
        const [consData, vowData, wordData, cvData, cv3Data] = await Promise.all([
            consResp.json(), vowResp.json(), wordResp.json(), cvResp.json(), cv3Resp.json()
        ]);

        if (consData.status === 'success') {
            CONSONANTS = consData.data.map(c => c.code);
            consData.data.forEach(c => { if (c.image_path) CONSONANT_IMAGES[c.code] = c.image_path; });
        }
        if (vowData.status === 'success') {
            VOWELS = vowData.data.map(v => ({ code: v.code, label: v.label }));
            vowData.data.forEach(v => { if (v.image_path) VOWEL_IMAGES[v.code] = v.image_path; });
        }
        if (wordData.status === 'success') {
            WORDS = wordData.data.map(w => w.word_text);
        }
        if (cvData.status === 'success') {
            CV_BLEND_ITEMS = cvData.data.map(cv => {
                const [c, v] = parseCVCode(cv.cv_code);
                if (cv.icon_path) CV_IMAGES[`${c}-${v}`] = cv.icon_path;
                return { c, v };
            });
        }
        if (cv3Data.status === 'success') {
            BLEND_3CV_ITEMS = cv3Data.data.map(b => ({ c: b.consonant_code, v: b.vowel_code }));
        }
        contentDataLoaded = true;
    } catch (e) {
        console.error('Error loading content data:', e);
    }
}

function loadImage(src) {
    return new Promise(resolve => {
        if (!src) return resolve(false);
        const img = new Image();
        img.onload  = () => resolve(true);
        img.onerror = () => resolve(false);
        img.src = src + '?v=' + ASSET_VER;
    });
}

async function firstExistingImage(basePath) {
    for (const ext of ['.png', '.jpg', '.jpeg']) {
        const url = basePath + ext;
        if (await loadImage(url)) return url;
    }
    return null;
}

function buttonPill(text, onClick) {
    const el = document.createElement('div');
    el.className  = 'pill';
    el.textContent = text;
    el.addEventListener('click', onClick);
    return el;
}

function buttonPillImage(src, alt, onClick) {
    const el  = document.createElement('div');
    el.className = 'pill';
    const img = document.createElement('img');
    img.src = src + '?v=' + ASSET_VER;
    img.alt = alt;
    img.style.cssText = 'width:60px;height:60px;object-fit:contain;';
    img.draggable = false;
    el.appendChild(img);
    el.addEventListener('click', onClick);
    return el;
}

function iconPathFor(item) {
    if (item.type === 'vowel')     return VOWEL_IMAGES[item.id]     || `${IMG_BASE}vowel_${item.id}.png`;
    if (item.type === 'consonant') return CONSONANT_IMAGES[item.id] || `${IMG_BASE}consonant_${item.id}.png`;
    if (item.type === 'cv')        return CV_IMAGES[item.id]        || `${IMG_BASE}cv_${item.id.replace('-','_')}.jpg`;
    if (item.type === '3cv')       return `${IMG_BASE}3cv_${item.id.replace('-','_')}.jpg`;
    return '';
}

function topPathFor(item) {
    if (item.type === 'vowel')     return VOWEL_IMAGES[item.id]     || `${IMG_BASE}top_vowel_${item.id}.png`;
    if (item.type === 'consonant') return CONSONANT_IMAGES[item.id] || `${IMG_BASE}top_consonant_${item.id}.png`;
    if (item.type === 'cv')        return CV_IMAGES[item.id]        || `${IMG_BASE}top_cv_${item.id.replace('-','_')}.jpg`;
    if (item.type === '3cv')       return `${IMG_BASE}top_3cv_${item.id.replace('-','_')}.jpg`;
    return '';
}

function prettyLabel(item) {
    if (item.type === 'cv') return item.id.replace('-', ' + ');
    return item.id;
}

// ================================================================
// ASSIGNMENT MODAL — state + functions
// ================================================================
let amCurrentId        = null;    // assignment being edited
let amExercises        = [];      // exercise list for current assignment
let amModalCards       = [];      // card slots in the exercise builder
let amSelectedType     = null;    // active category type
let amIsEditing        = false;   // true when editing, false when creating

async function openCreateAssignmentModal() {
    await loadContentData();
    amIsEditing  = false;
    amCurrentId  = null;
    amExercises  = [];

    document.getElementById('assignmentModalTitle').textContent = 'Create Assignment';
    document.getElementById('assignmentName').value        = '';
    document.getElementById('assignmentDescription').value = '';
    document.getElementById('exerciseName').value          = '';

    const userSel = document.getElementById('assignmentUsers');
    if (userSel) Array.from(userSel.options).forEach(o => o.selected = false);

    document.getElementById('currentlyAssignedUsers').style.display = 'none';
    document.getElementById('btnUnassignAll').style.display          = 'none';
    document.getElementById('assignedUsersList').innerHTML           = '';

    amUpdateExerciseList();
    amClearBuilder();
    new bootstrap.Modal('#assignmentModal').show();
}

async function editAssignment(id) {
    await loadContentData();
    try {
        const resp = await fetch(`/dashboards/api/admin/get_assignment.php?assignment_id=${id}`);
        const data = await resp.json();
        if (data.status !== 'success') { await showAlert('Error loading assignment: ' + (data.message || ''), 'Error', 'danger'); return; }

        const a = data.data;
        amIsEditing  = true;
        amCurrentId  = id;
        amExercises  = a.exercises || [];

        document.getElementById('assignmentModalTitle').textContent    = 'Edit Assignment';
        document.getElementById('assignmentName').value                = a.assignment_name || '';
        document.getElementById('assignmentDescription').value         = a.assignment_description || '';
        document.getElementById('exerciseName').value                  = '';

        if (a.assigned_users && a.assigned_users.length > 0) {
            amDisplayAssignedUsers(a.assigned_users);
        } else {
            document.getElementById('currentlyAssignedUsers').style.display = 'none';
            document.getElementById('btnUnassignAll').style.display          = 'none';
        }

        const userSel = document.getElementById('assignmentUsers');
        if (userSel && a.assigned_users) {
            Array.from(userSel.options).forEach(o => { o.selected = a.assigned_users.includes(o.value); });
        }

        amUpdateExerciseList();
        amClearBuilder();
        new bootstrap.Modal('#assignmentModal').show();
    } catch (e) {
        console.error(e);
        await showAlert('Error loading assignment', 'Error', 'danger');
    }
}

function assignAssignment(id) {
    editAssignment(id);
}

async function deleteAssignment(id) {
    const confirmed = await showConfirm('Delete this assignment? This cannot be undone.', 'Delete Assignment', 'Delete', 'Cancel', 'danger');
    if (!confirmed) return;
    try {
        const resp = await fetch('/dashboards/api/admin/delete_assignment.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignment_id: id })
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Assignment deleted');
            assignmentsLoaded = false;
            await loadAssignments();
        } else {
            await showAlert('Error: ' + (data.message || 'Failed to delete'), 'Error', 'danger');
        }
    } catch (e) {
        await showAlert('Error deleting assignment', 'Error', 'danger');
    }
}

async function amSaveAssignment() {
    const name = document.getElementById('assignmentName').value.trim();
    const desc = document.getElementById('assignmentDescription').value.trim();
    if (!name) { await showAlert('Please enter an assignment name', 'Error', 'danger'); return; }
    if (amExercises.length === 0) { await showAlert('Please add at least one exercise to the assignment', 'Error', 'danger'); return; }

    const userSel     = document.getElementById('assignmentUsers');
    const assignedUsers = userSel ? Array.from(userSel.selectedOptions).map(o => o.value) : [];

    const payload = { assignment_name: name, assignment_description: desc, exercises: amExercises, assigned_users: assignedUsers };
    if (amIsEditing && amCurrentId) payload.assignment_id = amCurrentId;

    const btn = document.getElementById('btnSaveAssignment');
    btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Saving…';
    try {
        const resp = await fetch('/dashboards/api/admin/save_assignment.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
            assignmentsLoaded = false;
            await loadAssignments();
            toastr.success(amIsEditing ? 'Assignment updated' : 'Assignment created');
        } else {
            await showAlert('Error: ' + (data.message || 'Failed to save'), 'Error', 'danger');
        }
    } catch (e) {
        await showAlert('Error saving assignment', 'Error', 'danger');
    } finally {
        btn.disabled = false; btn.innerHTML = '<i class="fa fa-save me-1"></i> Save Assignment';
    }
}

// ---- Exercise list ----
function amUpdateExerciseList() {
    const listDiv = document.getElementById('exerciseList');
    if (amExercises.length === 0) {
        listDiv.innerHTML = '<div class="text-muted text-center py-3" id="exerciseListEmpty">No exercises added yet</div>';
        return;
    }
    listDiv.innerHTML = '';
    amExercises.forEach((ex, index) => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center';

        const left = document.createElement('div');
        left.className = 'd-flex align-items-center';

        const handle = document.createElement('i');
        handle.className = 'fa fa-grip-vertical exercise-list-item-handle me-2';
        left.appendChild(handle);

        const nameSpan = document.createElement('span');
        nameSpan.className = 'fw-bold';
        nameSpan.textContent = `${index + 1}. ${ex.exercise_name}`;
        left.appendChild(nameSpan);

        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary ms-2';
        badge.textContent = `${ex.card_count} cards • ${ex.orientation}`;
        left.appendChild(badge);

        const btnGroup = document.createElement('div');
        btnGroup.className = 'btn-group btn-group-sm';

        const upBtn = document.createElement('button');
        upBtn.className = 'btn btn-outline-secondary';
        upBtn.innerHTML = '<i class="fa fa-arrow-up"></i>';
        upBtn.onclick = () => amMoveExercise(index, -1);
        if (index === 0) upBtn.disabled = true;

        const downBtn = document.createElement('button');
        downBtn.className = 'btn btn-outline-secondary';
        downBtn.innerHTML = '<i class="fa fa-arrow-down"></i>';
        downBtn.onclick = () => amMoveExercise(index, 1);
        if (index === amExercises.length - 1) downBtn.disabled = true;

        const delBtn = document.createElement('button');
        delBtn.className = 'btn btn-outline-danger';
        delBtn.innerHTML = '<i class="fa fa-trash"></i>';
        delBtn.onclick = () => amDeleteExercise(index);

        btnGroup.appendChild(upBtn);
        btnGroup.appendChild(downBtn);
        btnGroup.appendChild(delBtn);

        item.appendChild(left);
        item.appendChild(btnGroup);
        listDiv.appendChild(item);
    });
}

function amMoveExercise(index, dir) {
    const ni = index + dir;
    if (ni < 0 || ni >= amExercises.length) return;
    [amExercises[index], amExercises[ni]] = [amExercises[ni], amExercises[index]];
    amUpdateExerciseList();
}

async function amDeleteExercise(index) {
    const ok = await showConfirm('Delete this exercise?', 'Delete Exercise', 'Delete', 'Cancel', 'danger');
    if (!ok) return;
    amExercises.splice(index, 1);
    amUpdateExerciseList();
}

// ---- Exercise builder ----
function amClearBuilder() {
    amSelectedType   = null;
    amModalCards     = [];
    document.getElementById('exerciseName').value              = '';
    document.getElementById('modalSelectionPanel').style.display = 'none';
    document.getElementById('modalSelectionPanel').innerHTML   = '';
    document.getElementById('btnSaveExercise').disabled        = true;

    document.querySelectorAll('.modal-category-btn').forEach(b => b.classList.remove('active'));

    const preview = document.getElementById('modalExercisePreview');
    preview.className = 'border rounded p-3 mb-3';
    preview.innerHTML = '<div class="text-muted text-center py-4">Select a category and build your exercise</div>';
}

function amHandleCategoryClick(btn) {
    const type = btn.dataset.type;
    amSelectedType = type;
    document.querySelectorAll('.modal-category-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    amShowSelectionPanel(type);
    amInitPreview();
}

function amShowSelectionPanel(type) {
    const panel = document.getElementById('modalSelectionPanel');
    panel.innerHTML = '';
    panel.style.display = 'block';

    if (type === 'letters') {
        const ch = document.createElement('div'); ch.className = 'pill-section-header'; ch.textContent = 'Consonants'; panel.appendChild(ch);
        const cw = document.createElement('div'); cw.className = 'd-flex flex-wrap';
        CONSONANTS.forEach(c => {
            const src = CONSONANT_IMAGES[c] || `${IMG_BASE}consonant_${c}.png`;
            cw.appendChild(buttonPillImage(src, c, () => amAddCard({ type: 'consonant', id: c })));
        });
        panel.appendChild(cw);

        const vh = document.createElement('div'); vh.className = 'pill-section-header'; vh.textContent = 'Vowels'; panel.appendChild(vh);
        const vw = document.createElement('div'); vw.className = 'd-flex flex-wrap';
        VOWELS.forEach(v => {
            const src = VOWEL_IMAGES[v.code] || `${IMG_BASE}vowel_${v.code}.png`;
            vw.appendChild(buttonPillImage(src, v.label, () => amAddCard({ type: 'vowel', id: v.code })));
        });
        panel.appendChild(vw);

    } else if (type === 'cv') {
        const wrap = document.createElement('div'); wrap.className = 'd-flex flex-wrap';
        CV_BLEND_ITEMS.forEach(item => {
            wrap.appendChild(buttonPill(`${item.c} + ${item.v}`, () => amAddCard({ type: 'cv', id: `${item.c}-${item.v}` })));
        });
        panel.appendChild(wrap);

    } else if (type === '3cv') {
        const wrap = document.createElement('div'); wrap.className = 'd-flex flex-wrap';
        BLEND_3CV_ITEMS.forEach(item => {
            wrap.appendChild(buttonPill(`${item.c}${item.v}`, () => amAddCard({ type: '3cv', id: `${item.c}-${item.v}` })));
        });
        panel.appendChild(wrap);

    } else if (type === 'soundmixing') {
        const wrap = document.createElement('div'); wrap.className = 'd-flex flex-wrap';
        CV_BLEND_ITEMS.forEach(item => {
            wrap.appendChild(buttonPill(`${item.c}-${item.v}`, () => amAddCard({ type: 'cv', id: `${item.c}-${item.v}` })));
        });
        panel.appendChild(wrap);

    } else if (type === 'wordsyllable') {
        const wrap = document.createElement('div'); wrap.className = 'd-flex flex-wrap';
        WORDS.forEach(w => {
            wrap.appendChild(buttonPill(w, () => amAddCard({ type: 'word', id: w })));
        });
        panel.appendChild(wrap);
    }
}

function amInitPreview() {
    const preview = document.getElementById('modalExercisePreview');
    const count   = parseInt(document.getElementById('modalCardCount').value);
    amModalCards  = new Array(count).fill(null);
    preview.innerHTML = '';
    preview.className = 'border rounded p-3 mb-3';
    const orient = document.querySelector('input[name="modalOrientation"]:checked').value;
    if (orient === 'vertical') preview.classList.add('vertical-layout');

    for (let i = 0; i < count; i++) {
        const card = document.createElement('div');
        card.className = 'exercise-card';
        card.dataset.index = i;
        const box = document.createElement('div'); box.className = 'exercise-box';
        const content = document.createElement('div'); content.className = 'exercise-content';
        const span = document.createElement('span'); span.textContent = 'Choose sound'; span.className = 'text-muted';
        content.appendChild(span); box.appendChild(content); card.appendChild(box);
        preview.appendChild(card);
    }
    document.getElementById('btnSaveExercise').disabled = true;
}

async function amAddCard(item) {
    const count = parseInt(document.getElementById('modalCardCount').value);
    let idx = amModalCards.findIndex(x => x === null);
    if (idx === -1) idx = count - 1;
    amModalCards[idx] = item;
    await amRenderCard(idx, item);
    document.getElementById('btnSaveExercise').disabled = !amModalCards.every(c => c !== null);
}

async function amRenderCard(index, item) {
    const preview = document.getElementById('modalExercisePreview');
    const card    = preview.querySelectorAll('.exercise-card')[index];
    if (!card) return;
    const content = card.querySelector('.exercise-content');
    content.innerHTML = '';

    const label = item.type === 'word' ? item.id : prettyLabel(item);

    if (item.type === 'consonant' || item.type === 'vowel') {
        const topSrc  = topPathFor(item);
        const iconSrc = iconPathFor(item);
        const hasTop  = await loadImage(topSrc);
        const hasIcon = await loadImage(iconSrc);
        if (hasTop) {
            const img = document.createElement('img'); img.src = topSrc + '?v=' + ASSET_VER; img.alt = label; img.className = 'exercise-main-img'; content.appendChild(img);
        } else {
            const s = document.createElement('span'); s.textContent = label; content.appendChild(s);
        }
        if (hasIcon) {
            const img = document.createElement('img'); img.src = iconSrc + '?v=' + ASSET_VER; img.alt = label; img.className = 'exercise-icon-img'; content.appendChild(img);
        }

    } else if (item.type === 'word') {
        const topSrc = await firstExistingImage(`${IMG_BASE}top_${item.id.toLowerCase()}`);
        if (topSrc) {
            const img = document.createElement('img'); img.src = topSrc + '?v=' + ASSET_VER; img.alt = item.id; img.className = 'exercise-main-img'; content.appendChild(img);
        } else {
            const s = document.createElement('span'); s.textContent = item.id; content.appendChild(s);
        }

    } else if (item.type === 'cv' || item.type === '3cv') {
        const [cons, vowelCode] = item.id.split('-');
        const baseId  = `${cons}_${vowelCode}`;
        const topBase = item.type === '3cv' ? `${IMG_BASE}top_3cv_${baseId}` : `${IMG_BASE}top_cv_${baseId}`;
        const topSrc  = await firstExistingImage(topBase);
        const consSrc = CONSONANT_IMAGES[cons] || await firstExistingImage(`${IMG_BASE}consonant_${cons}`);
        const vowelSrc = VOWEL_IMAGES[vowelCode] || await firstExistingImage(`${IMG_BASE}vowel_${vowelCode}`);

        if (topSrc) {
            const img = document.createElement('img'); img.src = topSrc + '?v=' + ASSET_VER; img.alt = label; img.className = 'exercise-main-img'; content.appendChild(img);
        } else {
            const s = document.createElement('span'); s.textContent = label; content.appendChild(s);
        }

        const bottomRow = document.createElement('div'); bottomRow.className = 'cv-bottom-row';
        if (consSrc) {
            const ci = document.createElement('img'); ci.src = (typeof consSrc === 'string' ? consSrc : '') + '?v=' + ASSET_VER; ci.alt = cons; bottomRow.appendChild(ci);
        } else {
            const cs = document.createElement('span'); cs.textContent = cons; bottomRow.appendChild(cs);
        }
        if (vowelSrc) {
            const vi = document.createElement('img'); vi.src = (typeof vowelSrc === 'string' ? vowelSrc : '') + '?v=' + ASSET_VER; vi.alt = vowelCode; bottomRow.appendChild(vi);
        } else {
            const vs = document.createElement('span'); vs.textContent = vowelCode; bottomRow.appendChild(vs);
        }
        content.appendChild(bottomRow);
    }
}

async function amSaveExercise() {
    const name = document.getElementById('exerciseName').value.trim();
    if (!name) { await showAlert('Please enter an exercise name', 'Error', 'danger'); return; }
    if (!amModalCards.every(c => c !== null)) { await showAlert('Please fill all card slots', 'Error', 'danger'); return; }

    const cardCount   = parseInt(document.getElementById('modalCardCount').value);
    const orientation = document.querySelector('input[name="modalOrientation"]:checked').value;

    amExercises.push({ exercise_name: name, card_count: cardCount, orientation, cards: [...amModalCards] });
    amUpdateExerciseList();
    amClearBuilder();
}

function amUpdateOrientation() {
    const preview = document.getElementById('modalExercisePreview');
    const orient  = document.querySelector('input[name="modalOrientation"]:checked').value;
    preview.classList.toggle('vertical-layout', orient === 'vertical');
}

function amRebuildPreview() {
    const old = [...amModalCards];
    amInitPreview();
    const newCount = parseInt(document.getElementById('modalCardCount').value);
    for (let i = 0; i < Math.min(old.length, newCount); i++) {
        if (old[i]) { amModalCards[i] = old[i]; amRenderCard(i, old[i]); }
    }
}

// ---- User assignment section ----
async function amCheckPermissions() {
    try {
        const resp = await fetch('/dashboards/api/admin/check_permissions.php');
        const data = await resp.json();
        if (data.can_assign_users) {
            document.getElementById('assignmentUsersWrapper').style.display = 'block';
            amLoadAffiliatedUsers();
        }
    } catch (e) { console.error('Permission check error:', e); }
}

async function amLoadAffiliatedUsers() {
    try {
        const resp = await fetch('/dashboards/api/admin/get_affiliated_users.php');
        const data = await resp.json();
        if (data.status === 'success') {
            const sel = document.getElementById('assignmentUsers');
            sel.innerHTML = '';
            data.users.forEach(u => {
                const opt = document.createElement('option');
                opt.value       = u.UserUUID;
                opt.textContent = `${u.Name} (${u.Email})`;
                sel.appendChild(opt);
            });
        }
    } catch (e) { console.error('Error loading users:', e); }
}

function amDisplayAssignedUsers(uuids) {
    const listDiv     = document.getElementById('assignedUsersList');
    const container   = document.getElementById('currentlyAssignedUsers');
    const unassignBtn = document.getElementById('btnUnassignAll');
    const userSel     = document.getElementById('assignmentUsers');

    if (!uuids || uuids.length === 0) {
        container.style.display  = 'none';
        unassignBtn.style.display = 'none';
        if (userSel) Array.from(userSel.options).forEach(o => o.selected = false);
        return;
    }

    container.style.display  = 'block';
    unassignBtn.style.display = 'inline-block';
    listDiv.innerHTML = '';

    const allOpts = userSel ? Array.from(userSel.options) : [];
    allOpts.forEach(o => o.selected = false);

    uuids.forEach(uuid => {
        const opt = allOpts.find(o => o.value === uuid);
        if (opt) {
            opt.selected = true;
            const badge = document.createElement('span');
            badge.className = 'badge bg-success me-1 mb-1';
            badge.textContent = opt.textContent;
            listDiv.appendChild(badge);
        }
    });
}

async function amUnassignAll() {
    if (!amCurrentId) return;
    const ok = await showConfirm('Unassign ALL users from this assignment?', 'Unassign All', 'Unassign', 'Cancel', 'danger');
    if (!ok) return;
    try {
        const resp = await fetch('/dashboards/api/admin/unassign_all_users.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assignment_id: amCurrentId })
        });
        const data = await resp.json();
        if (data.status === 'success') {
            document.getElementById('assignedUsersList').innerHTML = '';
            document.getElementById('currentlyAssignedUsers').style.display = 'none';
            document.getElementById('btnUnassignAll').style.display          = 'none';
            const sel = document.getElementById('assignmentUsers');
            if (sel) Array.from(sel.options).forEach(o => o.selected = false);
            toastr.success('All users unassigned');
        } else {
            await showAlert('Error: Failed to unassign users', 'Error', 'danger');
        }
    } catch (e) { await showAlert('Error unassigning users', 'Error', 'danger'); }
}

// ================================================================
// Init
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    loadCards();

    // ---- Assignment modal event wiring ----
    document.getElementById('btnSaveAssignment').addEventListener('click', amSaveAssignment);
    document.getElementById('btnSaveExercise').addEventListener('click', amSaveExercise);
    document.getElementById('btnClearExercise').addEventListener('click', amClearBuilder);
    document.getElementById('btnUnassignAll').addEventListener('click', amUnassignAll);

    document.querySelectorAll('.modal-category-btn').forEach(btn => {
        btn.addEventListener('click', function() { amHandleCategoryClick(this); });
    });

    document.querySelectorAll('input[name="modalOrientation"]').forEach(radio => {
        radio.addEventListener('change', amUpdateOrientation);
    });

    document.getElementById('modalCardCount').addEventListener('change', () => {
        if (amSelectedType) amRebuildPreview();
    });

    // Check permissions (shows "Assign To Users" section if applicable)
    amCheckPermissions();

    document.getElementById('tab-assignments').addEventListener('shown.bs.tab', () => {
        if (!assignmentsLoaded) loadAssignments();
    });

    // Load media only when the tab is first shown
    document.getElementById('tab-media').addEventListener('shown.bs.tab', () => {
        if (!mediaLoaded) loadMedia();
    });

    // Load videos only when the tab is first shown (super_user only)
    const tabVideos = document.getElementById('tab-videos');
    if (tabVideos) tabVideos.addEventListener('shown.bs.tab', () => {
        if (!videosLoaded) loadVideos();
    });
});

// ================================================================
// TAB 5 — VIDEO TUTORIALS
// ================================================================

let videosTable  = null;
let videosLoaded = false;
let _videosPending = null; // holds video object for assign/edit/delete

const VIDEO_SLOTS = [
    { key: 'panel-letters',      label: 'Sounds in Isolation' },
    { key: 'panel-cv',           label: 'CV' },
    { key: 'panel-3cv',          label: 'CV Blending' },
    { key: 'panel-soundmixing',  label: 'Syllable Shifts' },
    { key: 'panel-wordsyllable', label: 'Words' },
    { key: 'panel-syllables',    label: 'CV-CV Blending' },
];

async function loadVideos() {
    try {
        const resp = await fetch('/dashboards/api/admin/get_videos.php');
        const data = await resp.json();
        if (data.status === 'success') {
            videosLoaded = true;
            initVideosTable(data.data);
        } else {
            document.getElementById('videosTableBody').innerHTML =
                `<tr><td colspan="5" class="text-center text-danger">${escapeHtml(data.message)}</td></tr>`;
        }
    } catch(e) { console.error(e); }
}

// ---- YouTube ID extractor ----
function ytVideoId(url) {
    try {
        const u = new URL(url);
        if (u.hostname.includes('youtu.be')) return u.pathname.slice(1);
        if (u.hostname.includes('youtube.com')) return u.searchParams.get('v') || (u.pathname.startsWith('/embed/') ? u.pathname.split('/')[2] : null);
    } catch(e) {}
    return null;
}

function videoPreviewHtml(v) {
    if (!v.video_path) return `<div class="thumb-placeholder"><i class="fa fa-film"></i></div>`;
    if (v.source_type === 'url') {
        const ytId = ytVideoId(v.video_path);
        if (ytId) return `<img src="https://img.youtube.com/vi/${ytId}/mqdefault.jpg" style="max-width:100px;max-height:64px;border-radius:4px;border:1px solid #dee2e6;" loading="lazy">`;
        return `<a href="${escapeHtml(v.video_path)}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="fa fa-external-link me-1"></i>View</a>`;
    }
    return `<video src="${escapeHtml(v.video_path)}" style="max-width:100px;max-height:64px;border-radius:4px;border:1px solid #dee2e6;" preload="metadata"></video>`;
}

function initVideosTable(data) {
    if (videosTable) { videosTable.destroy(); $('#videosTable tbody').empty(); }
    const tbody = document.getElementById('videosTableBody');
    tbody.innerHTML = '';
    data.forEach(v => {
        const slotLabel = v.slot_key
            ? (VIDEO_SLOTS.find(s => s.key === v.slot_key)?.label || v.slot_key)
            : '<span class="text-muted">—</span>';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${videoPreviewHtml(v)}</td>
            <td><strong>${escapeHtml(v.title)}</strong></td>
            <td class="text-muted small">${escapeHtml(v.description || '')}</td>
            <td>${slotLabel}</td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-xs btn-outline-primary" title="Edit" onclick='openEditVideoModal(${JSON.stringify(v)})'><i class="fa fa-pencil-alt"></i></button>
                    <button class="btn btn-xs btn-outline-info" title="Assign" onclick='openAssignVideoModal(${JSON.stringify(v)})'><i class="fa fa-link"></i></button>
                    <button class="btn btn-xs btn-outline-danger" title="Delete" onclick='deleteVideo(${v.video_id},"${escapeHtml(v.title).replace(/"/g,'&quot;')}")'><i class="fa fa-trash"></i></button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
    videosTable = $('#videosTable').DataTable({
        pageLength: 25,
        order: [[1,'asc']],
        columnDefs: [{ orderable: false, targets: [0,4] }],
        language: { search: 'Search videos:', emptyTable: 'No videos added yet' },
    });
    // Mobile: title = video title (col 1), hide thumbnail and extra cols
    if (window.MKAMobile) {
        MKAMobile.initTable('#videosTable', {
            titleCol   : 1,
            hideCols   : [0, 3],
            dtInstance : videosTable
        });
    }
}

// ---- Upload source toggle ----
function setUploadSource(type) {
    document.getElementById('uploadSourceType').value = type;
    document.getElementById('uploadFileWrap').style.display  = type === 'upload' ? '' : 'none';
    document.getElementById('uploadUrlWrap').style.display   = type === 'url'    ? '' : 'none';
    document.getElementById('uploadToggleFile').className = 'btn btn-sm ' + (type === 'upload' ? 'btn-primary' : 'btn-outline-primary');
    document.getElementById('uploadToggleUrl').className  = 'btn btn-sm ' + (type === 'url'    ? 'btn-primary' : 'btn-outline-primary');
}

function setEditSource(type) {
    document.getElementById('editSourceType').value = type;
    document.getElementById('editFileWrap').style.display = type === 'upload' ? '' : 'none';
    document.getElementById('editUrlWrap').style.display  = type === 'url'    ? '' : 'none';
    document.getElementById('editToggleFile').className = 'btn btn-sm ' + (type === 'upload' ? 'btn-primary' : 'btn-outline-primary');
    document.getElementById('editToggleUrl').className  = 'btn btn-sm ' + (type === 'url'    ? 'btn-primary' : 'btn-outline-primary');
}

// ---- Upload ----
function openUploadVideoModal() {
    document.getElementById('uploadVideoForm').reset();
    document.getElementById('uploadVideoError').style.display = 'none';
    setUploadSource('upload');
    const btn = document.getElementById('uploadVideoBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-save"></i> Save';
    $('#uploadVideoModal').modal('show');
}

async function submitUploadVideo() {
    const errorEl    = document.getElementById('uploadVideoError');
    errorEl.style.display = 'none';
    const title      = document.getElementById('uploadVideoTitle').value.trim();
    const sourceType = document.getElementById('uploadSourceType').value;
    if (!title) { errorEl.textContent = 'Name is required.'; errorEl.style.display = 'block'; return; }
    if (sourceType === 'upload' && !document.getElementById('uploadVideoFile').files[0]) {
        errorEl.textContent = 'Please select a video file.'; errorEl.style.display = 'block'; return;
    }
    if (sourceType === 'url' && !document.getElementById('uploadVideoUrl').value.trim()) {
        errorEl.textContent = 'Please enter a video URL.'; errorEl.style.display = 'block'; return;
    }
    const btn = document.getElementById('uploadVideoBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
    try {
        const resp = await fetch('/dashboards/api/admin/upload_video.php', { method:'POST', body: new FormData(document.getElementById('uploadVideoForm')) });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            $('#uploadVideoModal').modal('hide');
            videosLoaded = false;
            await loadVideos();
        } else {
            errorEl.textContent = data.message || 'Save failed.';
            errorEl.style.display = 'block';
        }
    } catch(e) { errorEl.textContent = 'Network error.'; errorEl.style.display = 'block'; }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fa fa-save"></i> Save'; }
}

// ---- Edit ----
function openEditVideoModal(video) {
    document.getElementById('editVideoId').value    = video.video_id;
    document.getElementById('editVideoTitle').value = video.title;
    document.getElementById('editVideoDesc').value  = video.description || '';
    document.getElementById('editVideoFile').value  = '';
    document.getElementById('editVideoError').style.display = 'none';

    const isUrl = video.source_type === 'url';
    setEditSource(isUrl ? 'url' : 'upload');

    if (isUrl) {
        document.getElementById('editVideoUrl').value = video.video_path || '';
    } else {
        const wrap    = document.getElementById('editVideoCurrentWrap');
        const preview = document.getElementById('editVideoPreview');
        if (video.video_path) { preview.src = video.video_path; wrap.style.display = 'block'; }
        else wrap.style.display = 'none';
    }
    const btn = document.getElementById('editVideoBtn');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-save"></i> Save Changes';
    $('#editVideoModal').modal('show');
}

async function submitEditVideo() {
    const errorEl = document.getElementById('editVideoError');
    errorEl.style.display = 'none';
    const title = document.getElementById('editVideoTitle').value.trim();
    if (!title) { errorEl.textContent = 'Name is required.'; errorEl.style.display = 'block'; return; }
    const btn = document.getElementById('editVideoBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
    try {
        const resp = await fetch('/dashboards/api/admin/update_video.php', { method:'POST', body: new FormData(document.getElementById('editVideoForm')) });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            $('#editVideoModal').modal('hide');
            videosLoaded = false;
            await loadVideos();
        } else {
            errorEl.textContent = data.message || 'Save failed.';
            errorEl.style.display = 'block';
        }
    } catch(e) { errorEl.textContent = 'Network error.'; errorEl.style.display = 'block'; }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save"></i> Save Changes';
    }
}

// ---- Delete ----
async function deleteVideo(videoId, title) {
    const ok = await showConfirm(
        `Delete video <strong>${escapeHtml(title)}</strong>? The file will be permanently removed.`,
        'Delete Video', 'Delete', 'Cancel', 'danger'
    );
    if (!ok) return;
    try {
        const fd = new FormData(); fd.append('video_id', videoId);
        const resp = await fetch('/dashboards/api/admin/delete_video.php', { method:'POST', body: fd });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            videosLoaded = false;
            await loadVideos();
        } else {
            toastr.error(data.message || 'Delete failed');
        }
    } catch(e) { toastr.error('Network error'); }
}

// ---- Assign ----
async function openAssignVideoModal(video) {
    _videosPending = video;
    document.getElementById('assignVideoName').textContent = video.title;

    // Show/hide unassign button
    const unassignBtn = document.getElementById('unassignVideoBtn');
    unassignBtn.style.display = video.slot_key ? 'inline-block' : 'none';

    // Load current slot assignments (need fresh data)
    const grid = document.getElementById('assignSlotGrid');
    grid.innerHTML = '<div class="text-center w-100 py-3"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    $('#assignVideoModal').modal('show');

    try {
        const resp = await fetch('/dashboards/api/admin/get_videos.php');
        const data = await resp.json();
        const allVideos = data.status === 'success' ? data.data : [];
        // Build slot → current video name map
        const slotMap = {};
        allVideos.forEach(v => { if (v.slot_key) slotMap[v.slot_key] = v.title; });

        grid.innerHTML = '';
        VIDEO_SLOTS.forEach(slot => {
            const isCurrent   = video.slot_key === slot.key;
            const occupiedBy  = slotMap[slot.key];
            const isOccupied  = !!occupiedBy && !isCurrent;
            const col = document.createElement('div');
            col.className = 'col-md-4 col-sm-6';
            col.innerHTML = `
                <button class="btn w-100 text-start p-3 ${isCurrent ? 'btn-info text-white' : isOccupied ? 'btn-outline-warning' : 'btn-outline-secondary'}"
                        onclick="assignVideoToSlot('${slot.key}')">
                    <div class="fw-semibold"><i class="fa fa-${isCurrent ? 'check-circle' : 'video'} me-1"></i>${escapeHtml(slot.label)}</div>
                    <small class="${isCurrent ? 'text-white-50' : 'text-muted'}">
                        ${isCurrent ? 'Currently assigned here' : occupiedBy ? 'Replace: ' + escapeHtml(occupiedBy) : 'Empty'}
                    </small>
                </button>`;
            grid.appendChild(col);
        });
    } catch(e) {
        grid.innerHTML = '<div class="text-danger small">Failed to load slot data</div>';
    }
}

async function assignVideoToSlot(slotKey) {
    if (!_videosPending) return;
    try {
        const resp = await fetch('/dashboards/api/admin/assign_video.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ video_id: _videosPending.video_id, slot_key: slotKey }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success(data.message);
            $('#assignVideoModal').modal('hide');
            videosLoaded = false;
            await loadVideos();
        } else {
            toastr.error(data.message || 'Failed');
        }
    } catch(e) { toastr.error('Network error'); }
}

async function unassignVideo() {
    if (!_videosPending) return;
    try {
        const resp = await fetch('/dashboards/api/admin/assign_video.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ video_id: _videosPending.video_id, slot_key: null }),
        });
        const data = await resp.json();
        if (data.status === 'success') {
            toastr.success('Assignment removed');
            $('#assignVideoModal').modal('hide');
            videosLoaded = false;
            await loadVideos();
        } else {
            toastr.error(data.message || 'Failed');
        }
    } catch(e) { toastr.error('Network error'); }
}
</script>
</body>
</html>
