<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/VideoManagement.php';

use MKA\Admin\VideoManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$userUuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$userUuid) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$videoId = (int)($input['video_id'] ?? 0);
if ($videoId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid video ID']);
    exit;
}

$updates = [];
if (isset($input['video_name'])) {
    $updates['video_name'] = $input['video_name'];
}
if (isset($input['allow_sound'])) {
    $updates['allow_sound'] = (bool)$input['allow_sound'];
}
if (isset($input['autoplay_loop'])) {
    $updates['autoplay_loop'] = (bool)$input['autoplay_loop'];
}
if (isset($input['display_order'])) {
    $updates['display_order'] = (int)$input['display_order'];
}

try {
    $result = VideoManagement::updateVideo($videoId, $updates, $userUuid);

    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("update_video error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}

