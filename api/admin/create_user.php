<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/UserManagement.php';

use MKA\Admin\UserManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

// Get logged-in user
$createdBy = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$createdBy) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$result = UserManagement::createUser($data, $createdBy);

http_response_code($result['status'] === 'success' ? 200 : 400);
echo json_encode($result);
