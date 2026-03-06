<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/ContentManagement.php';

use MKA\Admin\ContentManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$ownerUuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$ownerUuid) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$contentType = $data['content_type'] ?? '';

$result = match($contentType) {
    'consonant' => ContentManagement::addConsonant($data, $ownerUuid),
    'vowel' => ContentManagement::addVowel($data, $ownerUuid),
    'cv_blend' => ContentManagement::addCVBlend($data, $ownerUuid),
    'word' => ContentManagement::addWord($data, $ownerUuid),
    default => ['status' => 'fail', 'message' => 'Invalid content type']
};

http_response_code($result['status'] === 'success' ? 200 : 400);
echo json_encode($result);