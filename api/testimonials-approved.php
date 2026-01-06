<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once '../core/Testimonials/TestimonialManager.php';

$manager = new MKA\Testimonials\TestimonialManager($pdo);



// --- AUTHORIZATION ---
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

$user_uuid = $keyInfo['user_uuid'];

// --- FETCH APPROVED TESTIMONIALS FOR THIS USER ---
$stmt = $pdo->prepare("
    SELECT testimonial_text, user_name, submitted_at
    FROM mka_testimonials
    WHERE status = 'approved'
      AND (product_id IN (
           SELECT product_id FROM mka_products WHERE user_uuid = :user_uuid)
        OR service_id IN (
           SELECT service_id FROM mka_services WHERE user_uuid = :user_uuid)
        OR (product_id IS NULL AND service_id IS NULL))
    ORDER BY approved_at DESC
");
$stmt->execute([':user_uuid' => $user_uuid]);
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'testimonials' => $testimonials]);

