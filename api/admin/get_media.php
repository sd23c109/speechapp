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

// Optional: Filter by media type (video or image)
$mediaType = $_GET['type'] ?? null;
if ($mediaType && !in_array($mediaType, ['video', 'image'])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid media type. Must be "video" or "image"']);
    exit;
}

try {
    $media = MediaManagement::getAvailableMedia($userUuid, $mediaType);

    echo json_encode([
        'status' => 'success',
        'data' => $media
    ]);
} catch (Exception $e) {
    error_log("get_media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}
