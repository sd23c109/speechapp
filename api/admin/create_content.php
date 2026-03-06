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

$createdBy = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$createdBy) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';

switch ($type) {
    case 'consonant':
        $result = ContentManagement::createConsonant($data, $createdBy);
        break;
    case 'vowel':
        $result = ContentManagement::createVowel($data, $createdBy);
        break;
    case 'cv_blend':
        $result = ContentManagement::createCVBlend($data, $createdBy);
        break;
    case '3cv_blend':
        $result = ContentManagement::create3CVBlend($data, $createdBy);
        break;
    case 'word':
        $result = ContentManagement::createWord($data, $createdBy);
        break;
    default:
        $result = ['status' => 'fail', 'message' => 'Invalid content type'];
}

http_response_code($result['status'] === 'success' ? 200 : 400);
echo json_encode($result);