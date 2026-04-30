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

try {
    $stmt = $GLOBALS['pdo']->query("
        SELECT video_id, title, description, video_path, source_type, thumbnail_path,
               slot_key, created_at
        FROM instruction_videos
        WHERE is_active = 1
        ORDER BY display_order ASC, created_at DESC
    ");
    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
} catch (Exception $e) {
    error_log('get_videos error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Database error']);
}
