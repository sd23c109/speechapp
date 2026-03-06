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

session_start();
$updatedBy = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$updatedBy) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userUuid = $data['user_uuid'] ?? '';

if (!$userUuid) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'User UUID required']);
    exit;
}

$result = UserManagement::updateUser($userUuid, $data, $updatedBy);

http_response_code($result['status'] === 'success' ? 200 : 400);
echo json_encode($result);