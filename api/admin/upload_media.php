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

// Check if file was uploaded
if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'No file uploaded']);
    exit;
}

// Handle upload errors
if ($_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
    ];

    $errorMsg = $uploadErrors[$_FILES['media_file']['error']] ?? 'Unknown upload error';

    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'File upload error: ' . $errorMsg]);
    exit;
}

// Get media metadata from POST
// Note: allow_sound and autoplay_loop should be NULL for images, handled by MediaManagement class
$mediaInfo = [
    'media_name' => $_POST['media_name'] ?? '',
    'allow_sound' => isset($_POST['allow_sound']) ? ($_POST['allow_sound'] === '1' || $_POST['allow_sound'] === 'true') : true,
    'autoplay_loop' => isset($_POST['autoplay_loop']) ? ($_POST['autoplay_loop'] === '1' || $_POST['autoplay_loop'] === 'true') : false
];

try {
    $result = MediaManagement::uploadMedia($_FILES['media_file'], $mediaInfo, $userUuid);

    if ($result['status'] === 'success') {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("upload_media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}
