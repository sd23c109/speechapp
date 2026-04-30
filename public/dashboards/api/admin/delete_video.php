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

$videoId = (int)($_POST['video_id'] ?? 0);
if (!$videoId) {
    http_response_code(400); echo json_encode(['status'=>'fail','message'=>'video_id required']); exit;
}

try {
    $pdo  = $GLOBALS['pdo'];
    $stmt = $pdo->prepare("SELECT video_path FROM instruction_videos WHERE video_id = ? AND is_active = 1");
    $stmt->execute([$videoId]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404); echo json_encode(['status'=>'fail','message'=>'Video not found']); exit;
    }

    $pdo->prepare("UPDATE instruction_videos SET is_active = 0, slot_key = NULL WHERE video_id = ?")->execute([$videoId]);

    $filePath = '/opt/mka/public' . $row['video_path'];
    if (file_exists($filePath)) @unlink($filePath);

    echo json_encode(['status'=>'success','message'=>'Video deleted']);
} catch (Exception $e) {
    error_log('delete_video error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Database error']);
}
