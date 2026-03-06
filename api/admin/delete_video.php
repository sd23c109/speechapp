<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/VideoManagement.php';

use MKA\Admin\VideoManagement;

header('Content-Type: application/json');

// Enable error logging
error_log("=== VIDEO DELETE DEBUG START ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Wrong request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$userUuid = $_SESSION['user_data']['user_uuid'] ?? null;
$userName = $_SESSION['user_data']['name'] ?? 'Unknown';
$userType = $_SESSION['user_data']['user_type'] ?? 'Unknown';

error_log("User UUID: " . ($userUuid ?? 'NULL'));
error_log("User Name: " . $userName);
error_log("User Type: " . $userType);

if (!$userUuid) {
    error_log("ERROR: User not authenticated - no UUID in session");
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$rawInput = file_get_contents('php://input');
error_log("Raw input: " . $rawInput);

$input = json_decode($rawInput, true);
error_log("Decoded input: " . print_r($input, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("ERROR: JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid JSON']);
    exit;
}

$videoId = (int)($input['video_id'] ?? 0);
error_log("Video ID to delete: " . $videoId);
error_log("Video ID type: " . gettype($videoId));

if ($videoId <= 0) {
    error_log("ERROR: Invalid video ID (must be positive integer)");
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid video ID']);
    exit;
}

try {
    error_log("Calling VideoManagement::deleteVideo($videoId, $userUuid)");
    $result = VideoManagement::deleteVideo($videoId, $userUuid);

    error_log("Delete result: " . print_r($result, true));

    if ($result['status'] === 'success') {
        error_log("SUCCESS: Video deleted");
        http_response_code(200);
    } else {
        error_log("FAIL: " . $result['message']);
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log("EXCEPTION in delete_video: " . $e->getMessage());
    error_log("Exception file: " . $e->getFile());
    error_log("Exception line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

error_log("=== VIDEO DELETE DEBUG END ===");