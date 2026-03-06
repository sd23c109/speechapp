<?php
$GLOBALS['current_dashboard'] = 'speechapp';
include('../../dashboards/_init.php');
include('_menu_loader.php');

require_once '/opt/mka/core/Admin/ContentManagement.php';
use MKA\Admin\ContentManagement;

// Get user's content statistics
$userUuid = $_SESSION['user_data']['user_uuid'] ?? null;
$stats = ContentManagement::getContentStats($userUuid);
$isSuperUser = ($_SESSION['user_data']['user_type'] ?? '') === 'super_user';
$isEnterpriseAdmin = ($_SESSION['user_data']['user_type'] ?? '') === 'enterprise_admin';



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MKAdvantage – Content Management</title>

    <link rel="shortcut icon"href="/dashboards/img/favicon.ico">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/style.min.css" rel="stylesheet" type="text/css">

    <script src="plugins/jquery/js/jquery.min.js"></script>

    <style>
        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e40af;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #10b981;
            transition: width 0.3s;
        }
        .progress-fill.warning {
            background: #f59e0b;
        }
        .progress-fill.danger {
            background: #ef4444;
        }
        .content-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-consonant { background: #dbeafe; color: #1e40af; }
        .badge-vowel { background: #fce7f3; color: #9f1239; }
        .badge-cv { background: #fef3c7; color: #92400e; }
        .badge-3cv { background: #e0e7ff; color: #3730a3; }
        .badge-word { background: #dcfce7; color: #166534; }

        .quick-add-section {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
        }
        .content-table {
            font-size: 0.875rem;
        }
        .content-table th {
            background: #f9fafb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6b7280;
        }

        .modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 1rem !important;
        }

        .modal-header .modal-title {
            margin: 0;
            flex: 1;
        }

        .modal-header .close {
            padding: 0;
            margin: 0;
            margin-left: 1rem;
            background: transparent;
            border: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            opacity: .5;
        }

        .modal-header .close:hover {
            opacity: .75;
        }

        .video-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: box-shadow 0.2s;
        }

        .video-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .video-thumbnail {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
        }

        .video-settings {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .video-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        .alert-slide-container {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%) translateY(-100%);
            z-index: 9999;
            min-width: 400px;
            max-width: 600px;
            transition: transform 0.4s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .alert-slide-container.show {
            transform: translateX(-50%) translateY(20px);
        }

        .media-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: box-shadow 0.2s;
        }

        .media-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .media-thumbnail {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            object-fit: cover;
        }

        .media-settings {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .media-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }

        /* Assignment selector panel styles */
        .assignment-selector-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .assignments-checkboxes {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            background: white;
        }

        .assignments-checkboxes .form-check {
            padding: 20px;
            border-bottom: 1px solid #f1f1f1;
        }

        .assignments-checkboxes .form-check:last-child {
            border-bottom: none;
        }

        .assignments-checkboxes .form-check:hover {
            background: #f8f9fa;
        }

        .use-media-btn.active {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }
    </style>




</head>

<body>
<div id="slideAlert" class="alert-slide-container" style="display: none;">
    <div class="alert mb-0" id="slideAlertContent" role="alert">
        <button type="button" class="btn-close float-end" onclick="hideSlideAlert()"></button>
        <strong id="slideAlertTitle"></strong>
        <span id="slideAlertMessage"></span>
    </div>
</div>

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

        <div class="row border-bottom white-bg dashboard-header">
            <div class="col-xl-8">
                <h1>Content Management</h1>
                <span class="text-muted">Manage your exercise sounds, words, and blends</span>
            </div>
            <div class="col-xl-4 text-end">
                <div class="mt-3">
                    <span class="badge bg-primary" style="font-size: 1rem; padding: 8px 16px;">
                        Tier: <?= htmlspecialchars($stats['tier_name']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="wrapper wrapper-content animated fadeIn">

            <!-- Usage Statistics -->
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-label">Total Content</div>
                        <div class="stat-value"><?= $stats['total'] ?></div>
                        <?php if (!$isSuperUser): ?>
                            <div class="text-muted small">of <?= $stats['limit'] ?> allowed</div>
                        <?php else: ?>
                            <div class="text-muted small">Unlimited</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-label">Consonants</div>
                        <div class="stat-value"><?= $stats['consonant'] ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-label">Vowels</div>
                        <div class="stat-value"><?= $stats['vowel'] ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-label">Words</div>
                        <div class="stat-value"><?= $stats['word'] ?></div>
                    </div>
                </div>

                <!-- Add after the existing stat cards in the first row -->
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-label">CV Blends</div>
                        <div class="stat-value"><?= $stats['cv_blend'] ?></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-label">3CV Blends</div>
                        <div class="stat-value"><?= $stats['3cv_blend'] ?></div>
                    </div>
                </div>
            </div>

            <?php if (!$isSuperUser && $stats['limit'] > 0): ?>
                <!-- Usage Progress Bar -->
                <div class="row">
                    <div class="col-12">
                        <div class="ibox">
                            <div class="ibox-content">
                                <h5 class="mb-2">Tier Usage</h5>
                                <div class="progress-bar-custom">
                                    <?php
                                    $percent = $stats['percent_used'];
                                    $colorClass = '';
                                    if ($percent >= 90) $colorClass = 'danger';
                                    elseif ($percent >= 75) $colorClass = 'warning';
                                    ?>
                                    <div class="progress-fill <?= $colorClass ?>" style="width: <?= min($percent, 100) ?>%"></div>
                                </div>
                                <div class="mt-2 d-flex justify-content-between">
                                <span class="text-muted small">
                                    <?= $stats['total'] ?> / <?= $stats['limit'] ?> used (<?= $stats['percent_used'] ?>%)
                                </span>
                                    <span class="text-muted small">
                                    <?= $stats['remaining'] ?> remaining
                                </span>
                                </div>
                                <?php if ($stats['remaining'] <= 5 && $stats['remaining'] > 0): ?>
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>Warning:</strong> You're approaching your tier limit. Consider upgrading to add more content.
                                    </div>
                                <?php elseif ($stats['remaining'] <= 0): ?>
                                    <div class="alert alert-danger mt-3 mb-0">
                                        <i class="fa fa-ban"></i>
                                        <strong>Limit Reached:</strong> You've reached your tier limit. Please upgrade to add more content.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Add Buttons -->
            <?php if (($isSuperUser || $isEnterpriseAdmin) && ($stats['remaining'] > 0 || $stats['limit'] == -1 )): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="ibox">
                            <div class="ibox-title">
                                <h5>Quick Add Content</h5>
                            </div>
                            <div class="ibox-content">
                                <div class="quick-add-section">
                                    <p class="text-muted mb-3">Add new content to your library</p>
                                    <div class="btn-group" role="group">
                                        <!--
                                        <button class="btn btn-primary" onclick="showAddModal('consonant')">
                                            <i class="fa fa-plus"></i> Add Consonant
                                        </button>
                                        <button class="btn btn-primary" onclick="showAddModal('vowel')">
                                            <i class="fa fa-plus"></i> Add Vowel
                                        </button>
                                        -->
                                        <button class="btn btn-primary" onclick="showAddModal('cv_blend')">
                                            <i class="fa fa-plus"></i> Add CV Blend
                                        </button>
                                        <button class="btn btn-primary" onclick="showAddModal('3cv_blend')">
                                            <i class="fa fa-plus"></i> Add 3CV Blend
                                        </button>
                                        <button class="btn btn-primary" onclick="showAddModal('word')">
                                            <i class="fa fa-plus"></i> Add Word
                                        </button>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#successMediaModal">
                                            <i class="fa fa-video"></i>
                                            <span>Success Media</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Content Lists -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Your Custom Consonants</h5>
                        </div>
                        <div class="ibox-content">
                            <div id="consonants-list">
                                <div class="text-center text-muted py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Your Custom Vowels</h5>
                        </div>
                        <div class="ibox-content">
                            <div id="vowels-list">
                                <div class="text-center text-muted py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Your Custom Words</h5>
                        </div>
                        <div class="ibox-content">
                            <div id="words-list">
                                <div class="text-center text-muted py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add after the vowels list -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Your Custom CV Blends</h5>
                        </div>
                        <div class="ibox-content">
                            <div id="cv-blends-list">
                                <div class="text-center text-muted py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Your Custom 3CV Blends</h5>
                        </div>
                        <div class="ibox-content">
                            <div id="3cv-blends-list">
                                <div class="text-center text-muted py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="footer"></div>
    </div>
</div>

<
<div class="modal fade" id="addConsonantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Consonant</h5>
                <button type="button" class="close text-white" onclick="$('#addConsonantModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="consonantError" class="alert alert-danger" style="display:none;"></div>
                <form id="addConsonantForm">
                    <div class="form-group">
                        <label>Consonant <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="consonant" required maxlength="2"
                               placeholder="e.g., b, ch, sh">
                        <small class="form-text text-muted">Single letter or digraph</small>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" class="form-control" name="category"
                               placeholder="e.g., plosive, fricative">
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-group">
                        <label>Picture</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="consonantImage" name="image"
                                   accept="image/png,image/jpeg" onchange="handleImageSelect(event, 'consonant')">
                            <label class="custom-file-label" for="consonantImage">Choose image...</label>
                        </div>
                        <small class="form-text text-muted">PNG or JPEG only, max 3MB</small>

                        <!-- Image Preview -->
                        <div id="consonantImagePreviewContainer" style="display:none; margin-top: 10px;">
                            <img id="consonantImagePreview" src="" alt="Preview"
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeImage('consonant')" style="display: block; margin-top: 5px;">
                                <i class="fa fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="consonantExistingImagePath" name="existing_image_path" value="">
                    <input type="hidden" id="consonantDeleteOldImage" name="delete_old_image" value="0">
                    <input type="hidden" name="content_type" value="consonant">
                    <input type="hidden" id="consonantId" name="content_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#addConsonantModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitConsonant()">
                    <i class="fa fa-save"></i> <span id="consonantSubmitText">Add Consonant</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addVowelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vowel</h5>
                <button type="button" class="close text-white" onclick="$('#addVowelModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="vowelError" class="alert alert-danger" style="display:none;"></div>
                <form id="addVowelForm">
                    <div class="form-group">
                        <label>Vowel <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="vowel" required maxlength="2"
                               placeholder="e.g., a, ee, oa">
                        <small class="form-text text-muted">Single letter or vowel team</small>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" class="form-control" name="category"
                               placeholder="e.g., short, long, r-controlled">
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-group">
                        <label>Picture</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="vowelImage" name="image"
                                   accept="image/png,image/jpeg" onchange="handleImageSelect(event, 'vowel')">
                            <label class="custom-file-label" for="vowelImage">Choose image...</label>
                        </div>
                        <small class="form-text text-muted">PNG or JPEG only, max 3MB</small>

                        <!-- Image Preview -->
                        <div id="vowelImagePreviewContainer" style="display:none; margin-top: 10px;">
                            <img id="vowelImagePreview" src="" alt="Preview"
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeImage('vowel')" style="display: block; margin-top: 5px;">
                                <i class="fa fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="vowelExistingImagePath" name="existing_image_path" value="">
                    <input type="hidden" id="vowelDeleteOldImage" name="delete_old_image" value="0">
                    <input type="hidden" name="content_type" value="vowel">
                    <input type="hidden" id="vowelId" name="content_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#addVowelModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitVowel()">
                    <i class="fa fa-save"></i> <span id="vowelSubmitText">Add Vowel</span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Add CV Blend Modal -->
<div class="modal fade" id="addCVBlendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add CV Blend</h5>
                <button type="button" class="close text-white" onclick="$('#addCVBlendModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="cvBlendError" class="alert alert-danger" style="display:none;"></div>
                <form id="addCVBlendForm">
                    <div class="form-group">
                        <label>Blend <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="blend" required maxlength="2"
                               placeholder="e.g., bl">
                        <small class="form-text text-muted">2 letters only</small>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" class="form-control" name="category"
                               placeholder="e.g., l-blend, r-blend">
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-group">
                        <label>Picture</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="cvBlendImage" name="image"
                                   accept="image/png,image/jpeg" onchange="handleImageSelect(event, 'cvBlend')">
                            <label class="custom-file-label" for="cvBlendImage">Choose image...</label>
                        </div>
                        <small class="form-text text-muted">PNG or JPEG only, max 3MB</small>

                        <!-- Image Preview -->
                        <div id="cvBlendImagePreviewContainer" style="display:none; margin-top: 10px;">
                            <img id="cvBlendImagePreview" src="" alt="Preview"
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeImage('cvBlend')" style="display: block; margin-top: 5px;">
                                <i class="fa fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="cvBlendExistingImagePath" name="existing_image_path" value="">
                    <input type="hidden" id="cvBlendDeleteOldImage" name="delete_old_image" value="0">
                    <input type="hidden" name="content_type" value="cv_blend">
                    <input type="hidden" id="cvBlendId" name="content_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#addCVBlendModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCVBlend()">
                    <i class="fa fa-save"></i> <span id="cvBlendSubmitText">Add Blend</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add 3CV Blend Modal -->
<div class="modal fade" id="add3CVBlendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add 3-Letter CV Blend</h5>
                <button type="button" class="close text-white" onclick="$('#add3CVBlendModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="blend3Error" class="alert alert-danger" style="display:none;"></div>
                <form id="add3CVBlendForm">
                    <div class="form-group">
                        <label>Blend <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="blend" required maxlength="3"
                               placeholder="e.g., str">
                        <small class="form-text text-muted">3 letters only</small>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" class="form-control" name="category"
                               placeholder="e.g., consonant blend">
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-group">
                        <label>Picture</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="blend3Image" name="image"
                                   accept="image/png,image/jpeg" onchange="handleImageSelect(event, 'blend3')">
                            <label class="custom-file-label" for="blend3Image">Choose image...</label>
                        </div>
                        <small class="form-text text-muted">PNG or JPEG only, max 3MB</small>

                        <!-- Image Preview -->
                        <div id="blend3ImagePreviewContainer" style="display:none; margin-top: 10px;">
                            <img id="blend3ImagePreview" src="" alt="Preview"
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeImage('blend3')" style="display: block; margin-top: 5px;">
                                <i class="fa fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="blend3ExistingImagePath" name="existing_image_path" value="">
                    <input type="hidden" id="blend3DeleteOldImage" name="delete_old_image" value="0">
                    <input type="hidden" name="content_type" value="3cv_blend">
                    <input type="hidden" id="blend3Id" name="content_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#add3CVBlendModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submit3CVBlend()">
                    <i class="fa fa-save"></i> <span id="blend3SubmitText">Add Blend</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Word Modal -->
<div class="modal fade" id="addWordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Word</h5>
                <button type="button" class="close text-white" onclick="$('#addWordModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="wordError" class="alert alert-danger" style="display:none;"></div>
                <form id="addWordForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Word <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="word" required maxlength="100"
                               placeholder="e.g., butterfly">
                        <small class="form-text text-muted">Lowercase, no spaces</small>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" class="form-control" name="category"
                               placeholder="e.g., animals, food">
                        <small class="form-text text-muted">Optional grouping</small>
                    </div>
                    <div class="form-group">
                        <label>Syllable Count</label>
                        <input type="number" class="form-control" name="syllable_count" value="2" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Syllable Breakdown</label>
                        <input type="text" class="form-control" name="breakdown"
                               placeholder="e.g., but-ter-fly">
                        <small class="form-text text-muted">If blank, will auto-generate simple breakdown</small>
                    </div>

                    <!-- Image Upload Section -->
                    <div class="form-group">
                        <label>Picture</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="wordImage" name="image"
                                   accept="image/png,image/jpeg" onchange="handleImageSelect(event)">
                            <label class="custom-file-label" for="wordImage">Choose image...</label>
                        </div>
                        <small class="form-text text-muted">PNG or JPEG only, max 3MB</small>

                        <!-- Image Preview -->
                        <div id="imagePreviewContainer" style="display:none; margin-top: 10px;">
                            <img id="imagePreview" src="" alt="Preview"
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeImage()" style="display: block; margin-top: 5px;">
                                <i class="fa fa-trash"></i> Remove Image
                            </button>
                        </div>
                    </div>

                    <!-- Hidden field to track if we're updating and should delete old image -->
                    <input type="hidden" id="existingImagePath" name="existing_image_path" value="">
                    <input type="hidden" id="deleteOldImage" name="delete_old_image" value="0">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#addWordModal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitWord()">
                    <i class="fa fa-save"></i> <span id="submitButtonText">Add Word</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Media Modal (Videos and Images) -->
<div class="modal fade" id="successMediaModal" tabindex="-1" aria-labelledby="successMediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successMediaModalLabel">Manage Success Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <!-- Upload Section -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fa fa-upload"></i> Upload New Media</h6>
                    </div>
                    <div class="card-body">
                        <form id="mediaUploadForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="mediaFile" class="form-label">Video or Image <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="mediaFile" name="media_file"
                                           accept="video/mp4,video/webm,video/quicktime,image/jpeg,image/png,image/gif,image/webp" required>
                                    <div class="form-text">
                                        <strong>Videos:</strong> Max 10MB, MP4/WebM/MOV, up to 5 seconds<br>
                                        <strong>Images:</strong> Max 5MB, JPEG/PNG/GIF/WebP
                                    </div>

                                    <!-- Media preview - FIXED -->
                                    <div id="mediaPreview" class="mt-3" style="display: none;">
                                        <video id="previewVideo" controls style="max-width: 100%; max-height: 200px; display: none;"></video>
                                        <img id="previewImage" style="max-width: 100%; max-height: 200px; display: none;" alt="Preview">
                                        <div id="mediaInfo" class="mt-2 small text-muted"></div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="mediaName" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="mediaName" name="media_name"
                                           required maxlength="255" placeholder="e.g., Great Job Celebration">

                                    <!-- Sound controls (only shown for videos) - FIXED ID -->
                                    <div id="soundControls" style="display: none;">
                                        <div class="form-check mt-3">
                                            <input class="form-check-input" type="checkbox" id="allowSound" name="allow_sound" checked>
                                            <label class="form-check-label" for="allowSound">
                                                Allow Sound
                                            </label>
                                        </div>

                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="autoplayLoop" name="autoplay_loop">
                                            <label class="form-check-label" for="autoplayLoop">
                                                Autoplay & Loop
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary" id="btnUploadMedia" onclick="handleUploadClick()">
                                    <i class="fa fa-upload"></i> Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Media List -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fa fa-photo-video"></i> Your Media</h6>
                    </div>
                    <div class="card-body">
                        <div id="mediaListContainer">
                            <div class="text-center text-muted py-4">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2">Loading media...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>




<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/metismenu/js/metisMenu.min.js"></script>
<script src="plugins/pace-js/js/pace.min.js"></script>
<script src="plugins/simplebar/js/simplebar.min.js"></script>
<script src="js/inspinia.js"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>
<script>
    //Initialize Media Management variables (supports both videos and images)
    let currentMedia = [];
    let selectedSuccessMedia = null;
    let slideAlertTimeout = null;
    let confirmResolveFunction = null;

    // Show alert function - COMPLETE VERSION
    function showAlert(message, title = '', type = 'info', duration = 5000) {
        const container = document.getElementById('slideAlert');
        const alertContent = document.getElementById('slideAlertContent');
        const alertTitle = document.getElementById('slideAlertTitle');
        const alertMessage = document.getElementById('slideAlertMessage');

        if (!container) return;

        // Clear any existing timeout
        if (slideAlertTimeout) {
            clearTimeout(slideAlertTimeout);
        }

        // Set alert type class
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
            default: // info
                alertContent.classList.add('alert-info');
        }

        // Set content
        alertTitle.textContent = title ? title + ': ' : '';
        alertMessage.textContent = message;

        // Show the alert
        container.style.display = 'block';

        // Trigger reflow to ensure animation works
        container.offsetHeight;

        // Slide down
        container.classList.add('show');

        // Auto-hide after duration
        slideAlertTimeout = setTimeout(() => {
            hideSlideAlert();
        }, duration);
    }

    function hideSlideAlert() {
        const container = document.getElementById('slideAlert');
        if (!container) return;

        // Slide up
        container.classList.remove('show');

        // Hide completely after animation
        setTimeout(() => {
            container.style.display = 'none';
        }, 400);
    }

    // Show confirm function
    function showConfirm(message, title = 'Confirm', yesText = 'Yes', noText = 'No', type = 'warning') {
        return new Promise((resolve) => {
            const container = document.getElementById('slideConfirm');
            const confirmContent = document.getElementById('slideConfirmContent');
            const confirmTitle = document.getElementById('slideConfirmTitle');
            const confirmMessage = document.getElementById('slideConfirmMessage');
            const yesBtn = document.getElementById('slideConfirmYes');
            const noBtn = document.getElementById('slideConfirmNo');

            if (!container) {
                resolve(confirm(message)); // Fallback to native confirm
                return;
            }

            // Store resolve function
            confirmResolveFunction = resolve;

            // Set alert type class
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
                default: // warning
                    confirmContent.classList.add('alert-warning');
                    yesBtn.className = 'btn btn-sm btn-warning me-2';
            }

            // Set content
            confirmTitle.textContent = title ? title + ': ' : '';
            confirmMessage.textContent = message;
            yesBtn.textContent = yesText;
            noBtn.textContent = noText;

            // Remove old event listeners by cloning
            const newYesBtn = yesBtn.cloneNode(true);
            const newNoBtn = noBtn.cloneNode(true);
            yesBtn.parentNode.replaceChild(newYesBtn, yesBtn);
            noBtn.parentNode.replaceChild(newNoBtn, noBtn);

            // Add new event listeners
            document.getElementById('slideConfirmYes').addEventListener('click', () => {
                hideSlideConfirm(true);
            });

            document.getElementById('slideConfirmNo').addEventListener('click', () => {
                hideSlideConfirm(false);
            });

            // Show the confirm
            container.style.display = 'block';

            // Trigger reflow
            container.offsetHeight;

            // Slide down
            container.classList.add('show');
        });
    }

    function hideSlideConfirm(result) {
        const container = document.getElementById('slideConfirm');
        if (!container) return;

        // Slide up
        container.classList.remove('show');

        // Hide completely after animation
        setTimeout(() => {
            container.style.display = 'none';

            // Resolve the promise
            if (confirmResolveFunction) {
                confirmResolveFunction(result);
                confirmResolveFunction = null;
            }
        }, 400);
    }

    // Show add modal
    function showAddModal(type) {
        if (type === 'consonant') {
            $('#addConsonantModal').modal('show');
        } else if (type === 'vowel') {
            $('#addVowelModal').modal('show');
        } else if (type === 'cv_blend') {
            loadConsonantsAndVowels('cv');
            $('#addCVBlendModal').modal('show');
        } else if (type === '3cv_blend') {
            loadConsonantsAndVowels('3cv');
            $('#add3CVBlendModal').modal('show');
        } else if (type === 'word') {
            $('#addWordModal').modal('show');
        }
    }

    // Submit consonant
    async function submitConsonant() {
        const form = document.getElementById('addConsonantForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('/dashboards/api/admin/create_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'consonant',
                    code: formData.get('code'),
                    label: formData.get('label')
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                toastr.success(result.message);
                $('#addConsonantModal').modal('hide');
                form.reset();
                loadUserContent();
                location.reload();
            } else {
                document.getElementById('consonantError').textContent = result.message;
                document.getElementById('consonantError').style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred');
        }
    }

    // Submit vowel
    async function submitVowel() {
        const form = document.getElementById('addVowelForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('/dashboards/api/admin/create_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'vowel',
                    code: formData.get('code'),
                    label: formData.get('label'),
                    vowel_type: formData.get('type')
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                toastr.success(result.message);
                $('#addVowelModal').modal('hide');
                form.reset();
                loadUserContent();
                location.reload();
            } else {
                document.getElementById('vowelError').textContent = result.message;
                document.getElementById('vowelError').style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred');
        }
    }

    // Submit word
    async function submitWord() {
        const form = document.getElementById('addWordForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('/dashboards/api/admin/create_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'word',
                    word: formData.get('word'),
                    category: formData.get('category'),
                    syllable_count: formData.get('syllable_count'),
                    breakdown: formData.get('breakdown')
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                toastr.success(result.message);
                $('#addWordModal').modal('hide');
                form.reset();
                loadUserContent();
                location.reload();
            } else {
                document.getElementById('wordError').textContent = result.message;
                document.getElementById('wordError').style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred');
        }
    }

    // Submit CV blend
    async function submitCVBlend() {
        const form = document.getElementById('addCVBlendForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('/dashboards/api/admin/create_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'cv_blend',
                    consonant_id: formData.get('consonant_id'),
                    vowel_id: formData.get('vowel_id')
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                toastr.success(result.message);
                $('#addCVBlendModal').modal('hide');
                form.reset();
                location.reload();
            } else {
                document.getElementById('cvBlendError').textContent = result.message;
                document.getElementById('cvBlendError').style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred');
        }
    }

    // Submit 3CV blend
    async function submit3CVBlend() {
        const form = document.getElementById('add3CVBlendForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('/dashboards/api/admin/create_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: '3cv_blend',
                    consonant_id: formData.get('consonant_id'),
                    vowel_id: formData.get('vowel_id')
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                toastr.success(result.message);
                $('#add3CVBlendModal').modal('hide');
                form.reset();
                location.reload();
            } else {
                document.getElementById('3cvBlendError').textContent = result.message;
                document.getElementById('3cvBlendError').style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred');
        }
    }

    // Load user's custom content
    async function loadUserContent() {
        try {
            // Load custom consonants
            const consResp = await fetch('/dashboards/api/admin/get_user_content.php?type=consonant');
            const consData = await consResp.json();
            displayConsonants(consData.data || []);

            // Load custom vowels
            const vowelResp = await fetch('/dashboards/api/admin/get_user_content.php?type=vowel');
            const vowelData = await vowelResp.json();
            displayVowels(vowelData.data || []);

            // Load custom CV blends
            const cvResp = await fetch('/dashboards/api/admin/get_user_content.php?type=cv_blend');
            const cvData = await cvResp.json();
            displayCVBlends(cvData.data || []);

            // Load custom 3CV blends
            const cv3Resp = await fetch('/dashboards/api/admin/get_user_content.php?type=3cv_blend');
            const cv3Data = await cv3Resp.json();
            display3CVBlends(cv3Data.data || []);

            // Load custom words
            const wordResp = await fetch('/dashboards/api/admin/get_user_content.php?type=word');
            const wordData = await wordResp.json();
            displayWords(wordData.data || []);

        } catch (error) {
            console.error('Error loading content:', error);
        }
    }

    // Load available consonants and vowels for blend creation
    async function loadConsonantsAndVowels(prefix) {
        try {
            // Load consonants
            const consResp = await fetch('/dashboards/api/admin/get_content.php?type=consonant');
            const consData = await consResp.json();

            const consSelect = document.getElementById(prefix + 'ConsonantSelect');
            consSelect.innerHTML = '<option value="">Select consonant...</option>';

            if (consData.status === 'success') {
                consData.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.consonant_id;
                    opt.textContent = c.code + ' - ' + (c.label || c.code);
                    consSelect.appendChild(opt);
                });
            }

            // Load vowels
            const vowelResp = await fetch('/dashboards/api/admin/get_content.php?type=vowel');
            const vowelData = await vowelResp.json();

            const vowelSelect = document.getElementById(prefix + 'VowelSelect');
            vowelSelect.innerHTML = '<option value="">Select vowel...</option>';

            if (vowelData.status === 'success') {
                vowelData.data.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.vowel_id;
                    opt.textContent = v.code + ' - ' + (v.label || v.code);
                    vowelSelect.appendChild(opt);
                });
            }

        } catch (error) {
            console.error('Error loading consonants/vowels:', error);
        }
    }

    function displayCVBlends(blends) {
        const container = document.getElementById('cv-blends-list');
        if (!container) return;

        if (blends.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No custom CV blends yet. Click "Add CV Blend" to create one.</p>';
            return;
        }

        let html = '<table class="table table-sm content-table"><thead><tr><th>Code</th><th>Consonant</th><th>Vowel</th><th>Actions</th></tr></thead><tbody>';

        blends.forEach(b => {
            html += `
            <tr>
                <td><strong>${b.cv_code}</strong></td>
                <td>${b.consonant_code}</td>
                <td>${b.vowel_code}</td>
                <td>
                    <button class="btn btn-xs btn-danger" onclick="deleteContent('cv_blend', ${b.cv_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function display3CVBlends(blends) {
        const container = document.getElementById('3cv-blends-list');
        if (!container) return;

        if (blends.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No custom 3CV blends yet. Click "Add 3CV Blend" to create one.</p>';
            return;
        }

        let html = '<table class="table table-sm content-table"><thead><tr><th>Code</th><th>Consonant</th><th>Vowel</th><th>Actions</th></tr></thead><tbody>';

        blends.forEach(b => {
            html += `
            <tr>
                <td><strong>${b.blend_code}</strong></td>
                <td>${b.consonant_code}</td>
                <td>${b.vowel_code}</td>
                <td>
                    <button class="btn btn-xs btn-danger" onclick="deleteContent('3cv_blend', ${b.blend_3cv_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function displayConsonants(consonants) {
        const container = document.getElementById('consonants-list');
        if (!container) return;

        if (consonants.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No custom consonants yet. Click "Add Consonant" to create one.</p>';
            return;
        }

        let html = '<table class="table table-sm content-table"><thead><tr><th>Code</th><th>Label</th><th>Actions</th></tr></thead><tbody>';

        consonants.forEach(c => {
            html += `
            <tr>
                <td><strong>${c.consonant_code}</strong></td>
                <td>${c.consonant_label || c.consonant_code}</td>
                <td>
                    <button class="btn btn-xs btn-danger" onclick="deleteContent('consonant', ${c.consonant_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function displayVowels(vowels) {
        const container = document.getElementById('vowels-list');
        if (!container) return;

        if (vowels.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No custom vowels yet. Click "Add Vowel" to create one.</p>';
            return;
        }

        let html = '<table class="table table-sm content-table"><thead><tr><th>Code</th><th>Type</th><th>Label</th><th>Actions</th></tr></thead><tbody>';

        vowels.forEach(v => {
            html += `
            <tr>
                <td><strong>${v.vowel_code}</strong></td>
                <td><span class="badge badge-secondary">${v.vowel_type}</span></td>
                <td>${v.vowel_label || v.vowel_code}</td>
                <td>
                    <button class="btn btn-xs btn-danger" onclick="deleteContent('vowel', ${v.vowel_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function displayWords(words) {
        const container = document.getElementById('words-list');
        if (!container) return;

        if (words.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No custom words yet. Click "Add Word" to create one.</p>';
            return;
        }

        let html = '<table class="table table-sm content-table"><thead><tr><th>Word</th><th>Category</th><th>Syllables</th><th>Breakdown</th><th>Actions</th></tr></thead><tbody>';

        words.forEach(w => {
            html += `
            <tr>
                <td><strong>${w.word_text}</strong></td>
                <td>${w.word_category || '-'}</td>
                <td>${w.syllable_count}</td>
                <td><code>${w.syllable_breakdown || '-'}</code></td>
                <td>
                    <button class="btn btn-xs btn-danger" onclick="deleteContent('word', ${w.word_id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    async function deleteContent(type, id) {
        const confirmed = await showConfirm('Are you sure you want to delete this content?');
        if (!confirmed) return;

        try {
            const response = await fetch('/dashboards/api/admin/delete_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, id })
            });

            const result = await response.json();

            if (result.status === 'success') {
                toastr.success('Content deleted');
                loadUserContent();
                location.reload();
            } else {
                toastr.error(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('An error occurred');
        }
    }

    // ========================================
    // MEDIA MANAGEMENT FUNCTIONS (Videos & Images)
    // ========================================

    // Load all media for current user
    async function loadMedia() {
        const container = document.getElementById('mediaListContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Loading media...</p>
            </div>
        `;

        try {
            const response = await fetch('/dashboards/api/admin/get_media.php');
            const data = await response.json();

            if (data.status === 'success') {
                currentMedia = data.data;
                renderMediaList(currentMedia);
            } else {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle"></i> Error loading media: ${data.message}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading media:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i> Error loading media
                </div>
            `;
        }
    }

    // Updated renderMediaList function with assignment selector

    function renderMediaList(mediaItems) {
        const container = document.getElementById('mediaListContainer');
        if (!container) return;

        if (mediaItems.length === 0) {
            container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fa fa-photo-video fa-3x mb-3"></i>
                <p>No media uploaded yet</p>
            </div>
        `;
            return;
        }

        container.innerHTML = mediaItems.map(media => {
            const isVideo = media.media_type === 'video';

            const previewHtml = isVideo
                ? `<video class="media-thumbnail" controls ${!media.allow_sound ? 'muted' : ''}>
                   <source src="${media.media_path}" type="video/mp4">
               </video>`
                : `<img src="${media.media_path}" class="media-thumbnail" alt="${escapeHtml(media.media_name)}">`;

            const settingsBadges = isVideo
                ? `<span class="badge ${media.allow_sound ? 'bg-success' : 'bg-secondary'} media-badge">
                   <i class="fa fa-${media.allow_sound ? 'volume-up' : 'volume-mute'}"></i>
                   ${media.allow_sound ? 'Sound On' : 'Sound Off'}
               </span>
               <span class="badge ${media.autoplay_loop ? 'bg-info' : 'bg-secondary'} media-badge">
                   <i class="fa fa-${media.autoplay_loop ? 'repeat' : 'play'}"></i>
                   ${media.autoplay_loop ? 'Loop' : 'Play Once'}
               </span>`
                : `<span class="badge bg-primary media-badge">
                   <i class="fa fa-image"></i> Image
               </span>`;

            return `
            <div class="media-card" data-media-id="${media.media_id}">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        ${previewHtml}
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-2">
                            <i class="fa fa-${isVideo ? 'video' : 'image'} me-1"></i>
                            ${escapeHtml(media.media_name)}
                        </h6>
                        <div class="media-settings">
                            ${settingsBadges}
                            <span class="badge bg-light text-dark media-badge">
                                ${formatFileSize(media.file_size_bytes)}
                            </span>
                        </div>
                        <div class="small text-muted mt-2">
                            Uploaded: ${new Date(media.created_at).toLocaleDateString()}
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <div class="btn-group-vertical btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editMedia(${media.media_id})">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-outline-success use-media-btn" onclick="toggleUseMediaPanel(${media.media_id})">
                                <i class="fa fa-check"></i> Use This
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteMedia(${media.media_id})">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Assignment Selector Panel (initially hidden) -->
                <div class="assignment-selector-panel" id="assignmentPanel-${media.media_id}" style="display: none;">
                    <hr class="my-3">
                    <h6 class="mb-3"><i class="fa fa-clipboard-list"></i> Where to use this media:</h6>

                    <!-- Default for regular exercises -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="defaultMedia-${media.media_id}"
                               onchange="setAsDefault(${media.media_id}, this.checked)">
                        <label class="form-check-label" for="defaultMedia-${media.media_id}">
                            <strong>Use as default success media</strong>
                            <br>
                            <small class="text-muted">This will show for all regular exercises (not assignments)</small>
                        </label>
                    </div>

                    <hr class="my-3">

                    <!-- Assignments list -->
                    <div class="mb-2"><strong>Assign to specific assignments:</strong></div>
                    <div id="assignmentsList-${media.media_id}" class="assignments-checkboxes">
                        <div class="text-center py-2">
                            <i class="fa fa-spinner fa-spin"></i> Loading assignments...
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button class="btn btn-sm btn-secondary" onclick="toggleUseMediaPanel(${media.media_id})">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="saveMediaAssignments(${media.media_id})">
                            <i class="fa fa-save"></i> Save Assignments
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');
    }

    // Toggle the assignment selector panel
    async function toggleUseMediaPanel(mediaId) {
        const panel = document.getElementById(`assignmentPanel-${mediaId}`);
        const allPanels = document.querySelectorAll('.assignment-selector-panel');

        // Close all other panels
        allPanels.forEach(p => {
            if (p.id !== `assignmentPanel-${mediaId}`) {
                p.style.display = 'none';
            }
        });

        // Toggle this panel
        if (panel.style.display === 'none') {
            panel.style.display = 'block';

            // Load current settings
            await loadMediaAssignmentSettings(mediaId);
        } else {
            panel.style.display = 'none';
        }
    }

    // Load current assignment settings for this media
    async function loadMediaAssignmentSettings(mediaId) {
        const defaultCheckbox = document.getElementById(`defaultMedia-${mediaId}`);
        const assignmentsContainer = document.getElementById(`assignmentsList-${mediaId}`);

        try {
            // Check if this is the default media
            const defaultResp = await fetch('/dashboards/api/admin/get_default_media.php');
            const defaultData = await defaultResp.json();

            if (defaultData.status === 'success' && defaultData.data) {
                defaultCheckbox.checked = (defaultData.data.media_id == mediaId);
            }

            // Load all assignments
            const assignResp = await fetch('/dashboards/api/admin/get_assignments.php');
            const assignData = await assignResp.json();

            if (assignData.status === 'success') {
                const assignments = assignData.data || [];

                // Load which assignments currently use this media
                const mediaAssignResp = await fetch(`/dashboards/api/admin/get_media_assignments.php?media_id=${mediaId}`);
                const mediaAssignData = await mediaAssignResp.json();
                const assignedIds = mediaAssignData.status === 'success'
                    ? mediaAssignData.data.map(a => a.assignment_group_id)
                    : [];

                if (assignments.length === 0) {
                    assignmentsContainer.innerHTML = '<div class="text-muted small">No assignments created yet</div>';
                } else {
                    assignmentsContainer.innerHTML = assignments.map(assign => `
                    <div class="form-check mb-2">
                        <input class="form-check-input assignment-checkbox" type="checkbox"
                               id="assign-${mediaId}-${assign.assignment_group_id}"
                               value="${assign.assignment_group_id}"
                               ${assignedIds.includes(assign.assignment_group_id) ? 'checked' : ''}>
                        <label class="form-check-label" for="assign-${mediaId}-${assign.assignment_group_id}">
                            ${escapeHtml(assign.assignment_name)}
                            <br>
                            <small class="text-muted">${assign.exercise_count || 0} exercises</small>
                        </label>
                    </div>
                `).join('');
                }
            }

        } catch (error) {
            console.error('Error loading settings:', error);
            assignmentsContainer.innerHTML = '<div class="alert alert-danger small">Error loading assignments</div>';
        }
    }

    // Set media as default
    async function setAsDefault(mediaId, isDefault) {
        try {
            const endpoint = isDefault
                ? '/dashboards/api/admin/set_default_media.php'
                : '/dashboards/api/admin/clear_default_media.php';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ media_id: mediaId })
            });

            const data = await response.json();

            if (data.status === 'success') {
                showAlert(
                    isDefault ? 'Set as default success media' : 'Removed as default',
                    'Success',
                    'success',
                    2000
                );

                // Uncheck other default checkboxes
                if (isDefault) {
                    document.querySelectorAll('[id^="defaultMedia-"]').forEach(cb => {
                        if (cb.id !== `defaultMedia-${mediaId}`) {
                            cb.checked = false;
                        }
                    });
                }
            } else {
                showAlert(data.message || 'Failed to update default', 'Error', 'danger');
                // Revert checkbox
                document.getElementById(`defaultMedia-${mediaId}`).checked = !isDefault;
            }
        } catch (error) {
            console.error('Error setting default:', error);
            showAlert('Error updating default media', 'Error', 'danger');
        }
    }

    // Save media assignments
    async function saveMediaAssignments(mediaId) {
        const checkboxes = document.querySelectorAll(`#assignmentsList-${mediaId} .assignment-checkbox:checked`);
        const assignmentIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        try {
            const response = await fetch('/dashboards/api/admin/assign_media_to_assignments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    media_id: mediaId,
                    assignment_ids: assignmentIds
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                showAlert('Assignments updated successfully', 'Success', 'success', 2000);
                toggleUseMediaPanel(mediaId); // Close the panel
            } else {
                showAlert(data.message || 'Failed to update assignments', 'Error', 'danger');
            }
        } catch (error) {
            console.error('Error saving assignments:', error);
            showAlert('Error updating assignments', 'Error', 'danger');
        }
    }



    // Preview media file before upload (handles both video and image)
    function previewMediaFile(event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById('mediaPreview');
        const previewVideo = document.getElementById('previewVideo');
        const previewImage = document.getElementById('previewImage');
        const mediaInfo = document.getElementById('mediaInfo');
        const soundControls = document.getElementById('soundControls');

        if (!file) {
            previewContainer.style.display = 'none';
            return;
        }

        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');

        if (!isVideo && !isImage) {
            showAlert('Please select a video or image file', 'Error', 'danger');
            event.target.value = '';
            return;
        }

        // Validate file size
        const maxSize = isVideo ? (10 * 1024 * 1024) : (5 * 1024 * 1024);
        const maxSizeMB = maxSize / (1024 * 1024);

        if (file.size > maxSize) {
            showAlert(`File size exceeds ${maxSizeMB}MB limit for ${isVideo ? 'videos' : 'images'}`, 'Error', 'danger');
            event.target.value = '';
            return;
        }

        // Create object URL for preview
        const url = URL.createObjectURL(file);
        previewContainer.style.display = 'block';

        if (isVideo) {
            previewVideo.style.display = 'block';
            previewImage.style.display = 'none';
            previewVideo.src = url;
            soundControls.style.display = 'block';

            // Get video duration
            previewVideo.addEventListener('loadedmetadata', function() {
                const duration = previewVideo.duration;

                if (duration > 5) {
                    showAlert('Video duration exceeds 5 seconds', 'Warning', 'warning');
                }

                mediaInfo.innerHTML = `
                    <strong>Type:</strong> Video<br>
                    <strong>File:</strong> ${file.name}<br>
                    <strong>Size:</strong> ${formatFileSize(file.size)}<br>
                    <strong>Duration:</strong> ${duration.toFixed(2)}s
                `;
            });
        } else {
            previewVideo.style.display = 'none';
            previewImage.style.display = 'block';
            previewImage.src = url;
            soundControls.style.display = 'none';

            // Get image dimensions
            previewImage.onload = function() {
                mediaInfo.innerHTML = `
                    <strong>Type:</strong> Image<br>
                    <strong>File:</strong> ${file.name}<br>
                    <strong>Size:</strong> ${formatFileSize(file.size)}<br>
                    <strong>Dimensions:</strong> ${previewImage.naturalWidth} × ${previewImage.naturalHeight}px
                `;
            };
        }

        // Auto-suggest name from filename
        const nameInput = document.getElementById('mediaName');
        if (!nameInput.value) {
            const suggestedName = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
            nameInput.value = suggestedName.charAt(0).toUpperCase() + suggestedName.slice(1);
        }
    }

    // Handle upload button click
    function handleUploadClick() {
        const form = document.getElementById('mediaUploadForm');
        if (!form) {
            console.error('Form not found!');
            showAlert('Form not found', 'Error', 'danger');
            return;
        }
        handleMediaUpload({ preventDefault: () => {}, target: form });
    }

    // Handle media upload (supports both video and image)
    async function handleMediaUpload(event) {
        event.preventDefault();

        const form = event.target;
        const uploadBtn = document.getElementById('btnUploadMedia');

        // Get the file input
        const fileInput = document.getElementById('mediaFile');
        const file = fileInput.files[0];

        if (!file) {
            showAlert('Please select a file', 'Error', 'danger');
            return;
        }

        const isVideo = file.type.startsWith('video/');
        const isImage = file.type.startsWith('image/');

        if (!isVideo && !isImage) {
            showAlert('Please select a video or image file', 'Error', 'danger');
            return;
        }

        // Validate file size
        const maxSize = isVideo ? (10 * 1024 * 1024) : (5 * 1024 * 1024);
        if (file.size > maxSize) {
            const maxMB = maxSize / (1024 * 1024);
            showAlert(`File size exceeds ${maxMB}MB limit`, 'Error', 'danger');
            return;
        }

        // Create FormData
        const formData = new FormData();
        formData.append('media_file', file);
        formData.append('media_name', document.getElementById('mediaName').value);

        // Only add sound settings for videos
        if (isVideo) {
            formData.append('allow_sound', document.getElementById('allowSound').checked ? '1' : '0');
            formData.append('autoplay_loop', document.getElementById('autoplayLoop').checked ? '1' : '0');
        }

        // Disable button during upload
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Uploading...';

        try {
            const response = await fetch('/dashboards/api/admin/upload_media.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                showAlert(`${isVideo ? 'Video' : 'Image'} uploaded successfully!`, 'Success', 'success');

                // Reset form
                form.reset();
                document.getElementById('mediaPreview').style.display = 'none';

                // Reload media list
                loadMedia();
            } else {
                showAlert(data.message || 'Upload failed', 'Error', 'danger');
            }
        } catch (error) {
            console.error('Upload error:', error);
            showAlert('Upload failed. Please try again.', 'Error', 'danger');
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Upload';
        }
    }

    // Edit media settings
    async function editMedia(mediaId) {
        const media = currentMedia.find(m => m.media_id == mediaId);
        if (!media) return;

        const newName = prompt('Enter new name:', media.media_name);
        if (!newName || newName === media.media_name) return;

        try {
            const response = await fetch('/dashboards/api/admin/update_media.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    media_id: mediaId,
                    media_name: newName
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                showAlert('Media updated successfully', 'Success', 'success');
                loadMedia();
            } else {
                showAlert(data.message || 'Update failed', 'Error', 'danger');
            }
        } catch (error) {
            console.error('Update error:', error);
            showAlert('Update failed', 'Error', 'danger');
        }
    }

    // Delete media
    async function deleteMedia(mediaId) {
        const confirmed = await showConfirm(
            'Are you sure you want to delete this media?',
            'Delete Media',
            'Delete',
            'Cancel',
            'danger'
        );

        if (!confirmed) return;

        try {
            const response = await fetch('/dashboards/api/admin/delete_media.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ media_id: mediaId })
            });

            const data = await response.json();

            if (data.status === 'success') {
                showAlert('Media deleted successfully', 'Success', 'success');
                loadMedia();
            } else {
                showAlert(data.message || 'Delete failed', 'Error', 'danger');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showAlert('Delete failed', 'Error', 'danger');
        }
    }

    // Use media in current exercise
    function useMediaInExercise(mediaId) {
        const media = currentMedia.find(m => m.media_id == mediaId);
        if (!media) return;

        selectedSuccessMedia = media;

        showAlert(`"${media.media_name}" will play on exercise completion`, 'Media Selected', 'success', 3000);

        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('successMediaModal'));
        if (modal) modal.hide();
    }

    // Play success media when exercise completes (handles both video and image)
    function playSuccessMedia() {
        if (!selectedSuccessMedia) return;

        const isVideo = selectedSuccessMedia.media_type === 'video';

        const mediaModal = document.createElement('div');
        mediaModal.className = 'modal fade';

        if (isVideo) {
            mediaModal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body p-0">
                            <video
                                ${selectedSuccessMedia.autoplay_loop ? 'loop' : ''}
                                ${selectedSuccessMedia.allow_sound ? '' : 'muted'}
                                autoplay
                                style="width: 100%; border-radius: 8px;"
                                onended="this.closest('.modal').querySelector('.btn-close').click()"
                            >
                                <source src="${selectedSuccessMedia.media_path}" type="video/mp4">
                            </video>
                            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Image with auto-close after 3 seconds
            mediaModal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body p-0 text-center">
                            <img src="${selectedSuccessMedia.media_path}"
                                 style="max-width: 100%; max-height: 80vh; border-radius: 8px;"
                                 alt="${escapeHtml(selectedSuccessMedia.media_name)}">
                            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                </div>
            `;
        }

        document.body.appendChild(mediaModal);
        const modal = new bootstrap.Modal(mediaModal);
        modal.show();

        // Auto-close images after 3 seconds
        if (!isVideo) {
            setTimeout(() => {
                modal.hide();
            }, 3000);
        }

        mediaModal.addEventListener('hidden.bs.modal', function() {
            mediaModal.remove();
        });
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load content on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - initializing...');

        loadUserContent();

        const mediaModal = document.getElementById('successMediaModal');
        if (mediaModal) {
            console.log('Media modal found');
            mediaModal.addEventListener('shown.bs.modal', function() {
                console.log('Media modal shown, loading media...');
                loadMedia();
            });
        } else {
            console.log('WARNING: Media modal not found');
        }

        // Setup media upload form
        const uploadForm = document.getElementById('mediaUploadForm');
        if (uploadForm) {
            console.log('Upload form found, attaching handler');
            uploadForm.addEventListener('submit', handleMediaUpload);
        } else {
            console.log('WARNING: Upload form not found');
        }

        // Setup media file preview
        const mediaFileInput = document.getElementById('mediaFile');
        if (mediaFileInput) {
            console.log('Media file input found');
            mediaFileInput.addEventListener('change', previewMediaFile);
        } else {
            console.log('WARNING: Media file input not found');
        }
    });
</script>

</body>
</html>