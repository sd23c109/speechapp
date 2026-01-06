<?php
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../core/Testimonials/TestimonialManager.php';

$pdo = new PDO($dsn, $db_user, $db_pass);
$manager = new MKA\Testimonials\TestimonialManager($pdo);

// --- CORS HEADERS ---
header('Access-Control-Allow-Origin: *');  // Or limit to specific domains
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- AUTHORIZATION ---
$headers = getallheaders();
$apiKey = null;

// Support Authorization: Bearer <key> or X-API-Key: <key>
if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $apiKey = $matches[1];
    }
} elseif (isset($headers['X-API-Key'])) {
    $apiKey = $headers['X-API-Key'];
}

if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing API key']);
    exit;
}

// --- VALIDATE API KEY ---
$stmt = $pdo->prepare("
    SELECT * FROM mka_api_keys
    WHERE api_key = :api_key
      AND status = 'active'
      AND (expires_at IS NULL OR expires_at > NOW())
");
$stmt->execute([':api_key' => $apiKey]);
$keyInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$keyInfo) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired API key']);
    exit;
}

$user_uuid = $keyInfo['user_uuid']; // Use this if you want to tie records to user

// --- MAIN API SWITCH ---
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['user_name']) || empty($input['user_email']) || empty($input['testimonial_text'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields: user_name, user_email, testimonial_text']);
            exit;
        }

        // Optionally force product_id/service_id to belong to $user_uuid

        $input['user_uuid'] = $user_uuid; // if you want to track who submitted

        $testimonial_id = $manager->addTestimonial($input);
        echo json_encode(['success' => true, 'testimonial_id' => $testimonial_id]);
        break;

    case 'GET':
        $status = $_GET['status'] ?? 'pending';
        $result = $manager->getTestimonials($status);
        echo json_encode(['success' => true, 'testimonials' => $result]);
        break;

    case 'PUT':
    case 'PATCH':
        parse_str(file_get_contents('php://input'), $putVars);
        if (empty($putVars['testimonial_id']) || empty($putVars['admin_user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'testimonial_id and admin_user_id required']);
            exit;
        }
        $reward_type = $putVars['reward_type'] ?? null;
        $reward_value = $putVars['reward_value'] ?? null;
        $updated = $manager->approveTestimonial($putVars['testimonial_id'], $putVars['admin_user_id'], $reward_type, $reward_value);
        echo json_encode(['success' => $updated > 0]);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $delVars);
        if (empty($delVars['testimonial_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'testimonial_id required']);
            exit;
        }
        $deleted = $manager->deleteTestimonial($delVars['testimonial_id']);
        echo json_encode(['success' => $deleted > 0]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}

