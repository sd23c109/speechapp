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

$title       = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');
$sourceType  = $_POST['source_type'] === 'url' ? 'url' : 'upload';

if (!$title) {
    http_response_code(400); echo json_encode(['status'=>'fail','message'=>'Title is required']); exit;
}

try {
    $userUuid = $_SESSION['user_data']['user_uuid'];
    $pdo      = $GLOBALS['pdo'];

    if ($sourceType === 'url') {
        $videoUrl = trim($_POST['video_url'] ?? '');
        if (!$videoUrl || !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            http_response_code(400); echo json_encode(['status'=>'fail','message'=>'A valid URL is required']); exit;
        }
        $stmt = $pdo->prepare("
            INSERT INTO instruction_videos (owner_user_uuid, title, description, video_filename, video_path, source_type)
            VALUES (?, ?, ?, '', ?, 'url')
        ");
        $stmt->execute([$userUuid, $title, $description ?: null, $videoUrl]);
    } else {
        if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(400); echo json_encode(['status'=>'fail','message'=>'No video file uploaded']); exit;
        }
        if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400); echo json_encode(['status'=>'fail','message'=>'Upload error: code '.$_FILES['video_file']['error']]); exit;
        }
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
            http_response_code(500); echo json_encode(['status'=>'error','message'=>'Failed to save video file']); exit;
        }
        try {
            $stmt = $pdo->prepare("
                INSERT INTO instruction_videos (owner_user_uuid, title, description, video_filename, video_path, source_type)
                VALUES (?, ?, ?, ?, ?, 'upload')
            ");
            $stmt->execute([$userUuid, $title, $description ?: null, $filename, $webDir.$filename]);
        } catch (Exception $e) {
            @unlink($destPath);
            throw $e;
        }
    }

    http_response_code(201);
    echo json_encode(['status'=>'success','message'=>'Video saved successfully','video_id'=>$pdo->lastInsertId()]);
} catch (Exception $e) {
    error_log('upload_video error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Database error']);
}
