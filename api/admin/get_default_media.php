<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/MediaManagement.php';

use MKA\Admin\MediaManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

try {
    $defaultMedia = MediaManagement::getUserDefaultMedia($userUuid);

    echo json_encode([
        'status' => 'success',
        'data' => $defaultMedia
    ]);
} catch (Exception $e) {
    error_log("get_default_media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}

