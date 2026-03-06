<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/VideoManagement.php';

use MKA\Admin\VideoManagement;

header('Content-Type: application/json');

// Enable error logging
error_log("=== VIDEO UPLOAD DEBUG START ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("FILES: " . print_r($_FILES, true));
error_log("POST: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$userUuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$userUuid) {
    error_log("ERROR: User not authenticated");
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

error_log("User UUID: " . $userUuid);

// Check if file was uploaded
if (!isset($_FILES['video_file'])) {
    error_log("ERROR: No video_file in FILES array");
    error_log("Available FILES keys: " . implode(', ', array_keys($_FILES)));
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'No file uploaded - video_file not in request']);
    exit;
}

if ($_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE) {
    error_log("ERROR: UPLOAD_ERR_NO_FILE");
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'No file uploaded - UPLOAD_ERR_NO_FILE']);
    exit;
}

if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
    error_log("ERROR: Upload error code " . $_FILES['video_file']['error']);

    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
    ];

    $errorMsg = $uploadErrors[$_FILES['video_file']['error']] ?? 'Unknown upload error';

    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'File upload error: ' . $errorMsg]);
    exit;
}

error_log("File upload successful, temp location: " . $_FILES['video_file']['tmp_name']);
error_log("File size: " . $_FILES['video_file']['size']);
error_log("File type: " . $_FILES['video_file']['type']);

// Get video metadata from POST
$videoInfo = [
    'video_name' => $_POST['video_name'] ?? '',
    'allow_sound' => isset($_POST['allow_sound']) ? (bool)$_POST['allow_sound'] : true,
    'autoplay_loop' => isset($_POST['autoplay_loop']) ? (bool)$_POST['autoplay_loop'] : false
];

error_log("Video info: " . print_r($videoInfo, true));

try {
    $result = VideoManagement::uploadVideo($_FILES['video_file'], $videoInfo, $userUuid);

    error_log("Upload result: " . print_r($result, true));

    if ($result['status'] === 'success') {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("EXCEPTION in upload_video: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

error_log("=== VIDEO UPLOAD DEBUG END ===");
