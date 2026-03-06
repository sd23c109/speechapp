<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/PricingManagement.php';

use MKA\Admin\PricingManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$adminUuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$adminUuid) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

// Verify user is enterprise_admin
$stmt = $GLOBALS['pdo']->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
$stmt->execute([$adminUuid]);
$userType = $stmt->fetchColumn();

if ($userType !== 'enterprise_admin') {
    http_response_code(403);
    echo json_encode(['status' => 'fail', 'message' => 'Permission denied']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$result = PricingManagement::setAdminPricing($adminUuid, $data);

http_response_code($result['status'] === 'success' ? 200 : 400);
echo json_encode($result);
