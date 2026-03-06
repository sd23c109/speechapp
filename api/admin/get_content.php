<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/ContentManagement.php';

use MKA\Admin\ContentManagement;

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

$contentType = $_GET['type'] ?? '';

if (empty($contentType)) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Content type required']);
    exit;
}

try {
    $content = ContentManagement::getAvailableContent($userUuid, $contentType);

    echo json_encode([
        'status' => 'success',
        'data' => $content
    ]);
} catch (Exception $e) {
    error_log("get_content error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}
