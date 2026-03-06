<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/UserManagement.php';

use MKA\Admin\UserManagement;

header('Content-Type: application/json');

$createdBy = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$createdBy) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$filters = [];
if (isset($_GET['user_type'])) {
    $filters['user_type'] = $_GET['user_type'];
}
if (isset($_GET['tier'])) {
    $filters['tier'] = $_GET['tier'];
}

$users = UserManagement::listUsersByCreator($createdBy, $filters);

echo json_encode(['status' => 'success', 'users' => $users]);
