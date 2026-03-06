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

try {
    $result = MediaManagement::setUserDefaultMedia($userUuid, $mediaId);

    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("set_default_media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}

