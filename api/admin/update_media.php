<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/MediaManagement.php';

use MKA\Admin\MediaManagement;

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

$mediaId = (int)($input['media_id'] ?? 0);
if ($mediaId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid media ID']);
    exit;
}

// Build updates array
$updates = [];
if (isset($input['media_name'])) {
    $updates['media_name'] = $input['media_name'];
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

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'No valid fields to update']);
    exit;
}

try {
    $result = MediaManagement::updateMedia($mediaId, $updates, $userUuid);

    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("update_media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}

