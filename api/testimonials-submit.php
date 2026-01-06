<?php
header("Access-Control-Allow-Origin: *"); // Allow all domains
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


header('Content-Type: application/json');

require_once __DIR__.'/../bootstrap.php';  // LOAD your bootstrap + PDO setup
require_once __DIR__.'/../core/Testimonials/TestimonialManager.php';



// --- AUTH ---
$headers = getallheaders();
$apiKey = null;

if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $apiKey = $matches[1];
    }
}

if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing API key']);
    exit;
}

// Validate API key
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

$user_uuid = $keyInfo['user_uuid'];

// --- PARSE INPUT ---

$input = json_decode(file_get_contents('php://input'), true);
//error_log('testimonial INCOMING');
//error_log(json_encode($input));

if (empty($input['user_name']) || empty($input['testimonial_text'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing name or testimonial text']);
    exit;
}

$userName = trim($input['user_name']);
$userEmail = trim($input['user_email']);
$testimonialText = trim($input['testimonial_text']);
$rewardId = trim($input['reward_id']);
$testimonialId = generateUUID();

// --- INSERT TESTIMONIAL ---
$stmt = $pdo->prepare("
    INSERT INTO mka_testimonials (user_uuid, user_name, user_email, testimonial_text, status, submitted_at, testimonial_id, reward_id)
    VALUES (:user_uuid, :user_name, :user_email, :testimonial_text, 'pending', NOW(), :testimonial_id, :reward_id)
");

try {
    $stmt->execute([
        ':user_uuid' => $user_uuid,
        ':user_name' => $userName,
        ':user_email' => $userEmail,
        ':testimonial_text' => $testimonialText,
        ':testimonial_id' => $testimonialId,
        ':reward_id' => $rewardId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Testimonial submitted!']);
} catch (PDOException $e) {
    error_log("Submit Testimonial DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }


