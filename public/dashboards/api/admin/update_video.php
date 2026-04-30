<?php
session_start();
require_once '/opt/mka/bootstrap.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_data']['user_uuid'])) {
    http_response_code(401); echo json_encode(['status'=>'fail','message'=>'Not authenticated']); exit;
}
if (($_SESSION['user_data']['user_type'] ?? '') !== 'super_user') {
    http_response_code(403); echo json_encode(['status'=>'fail','message'=>'Access denied']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['status'=>'fail','message'=>'Method not allowed']); exit;
}

$videoId     = (int)($_POST['video_id']     ?? 0);
$title       = trim($_POST['title']         ?? '');
$description = trim($_POST['description']   ?? '');
$sourceType  = $_POST['source_type'] === 'url' ? 'url' : 'upload';

if (!$videoId || !$title) {
    http_response_code(400); echo json_encode(['status'=>'fail','message'=>'video_id and title are required']); exit;
}

try {
    $pdo = $GLOBALS['pdo'];

    $row = $pdo->prepare("SELECT video_id, video_path, video_filename, source_type FROM instruction_videos WHERE video_id = ? AND is_active = 1");
    $row->execute([$videoId]);
    $existing = $row->fetch();
    if (!$existing) {
        http_response_code(404); echo json_encode(['status'=>'fail','message'=>'Video not found']); exit;
    }

    $newVideoPath     = $existing['video_path'];
    $newVideoFilename = $existing['video_filename'];
    $newSourceType    = $sourceType;
    $oldFilePath      = null;

    if ($sourceType === 'url') {
        $videoUrl = trim($_POST['video_url'] ?? '');
        if (!$videoUrl || !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            http_response_code(400); echo json_encode(['status'=>'fail','message'=>'A valid URL is required']); exit;
        }
        // If switching away from an uploaded file, clean it up
        if ($existing['source_type'] === 'upload') {
            $oldFilePath = '/opt/mka/public' . $existing['video_path'];
        }
        $newVideoPath     = $videoUrl;
        $newVideoFilename = '';
    } else {
        // Upload type: optionally replace file
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $allowed  = ['video/mp4','video/webm','video/quicktime','video/x-msvideo','video/x-matroska'];
            $mimeType = mime_content_type($_FILES['video_file']['tmp_name']);
            if (!in_array($mimeType, $allowed, true)) {
                http_response_code(400); echo json_encode(['status'=>'fail','message'=>'Only MP4, WebM, MOV, AVI, MKV files are allowed']); exit;
            }
            if ($_FILES['video_file']['size'] > 500 * 1024 * 1024) {
                http_response_code(400); echo json_encode(['status'=>'fail','message'=>'File exceeds 500 MB limit']); exit;
            }
            $uploadDir = '/opt/mka/public/assets/portal/exercises/videos/';
            $webDir    = '/assets/portal/exercises/videos/';
            $ext       = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $filename  = uniqid('vid_', true) . '.' . $ext;
            $destPath  = $uploadDir . $filename;
            if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $destPath)) {
                http_response_code(500); echo json_encode(['status'=>'error','message'=>'Failed to save new video file']); exit;
            }
            if ($existing['source_type'] === 'upload') {
                $oldFilePath = '/opt/mka/public' . $existing['video_path'];
            }
            $newVideoPath     = $webDir . $filename;
            $newVideoFilename = $filename;
        }
    }

    $pdo->prepare("
        UPDATE instruction_videos
        SET title = ?, description = ?, video_path = ?, video_filename = ?, source_type = ?
        WHERE video_id = ?
    ")->execute([$title, $description ?: null, $newVideoPath, $newVideoFilename, $newSourceType, $videoId]);

    if ($oldFilePath && file_exists($oldFilePath)) {
        @unlink($oldFilePath);
    }

    echo json_encode(['status'=>'success','message'=>'Video updated successfully']);
} catch (Exception $e) {
    error_log('update_video error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Database error']);
}
