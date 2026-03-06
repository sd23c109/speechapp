<?php
require_once('/opt/mka/dashboards/_init.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_data']['user_uuid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$userUUID = $_SESSION['user_data']['user_uuid'];
$pdo = $GLOBALS['pdo'];
// Check if user is super_user or enterprise_admin
$stmt = $pdo->prepare("
    SELECT user_type 
    FROM mka_users 
    WHERE UserUUID = ?
");
$stmt->execute([$userUUID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$canAssignUsers = false;
if ($user && in_array($user['user_type'], ['super_user', 'enterprise_admin'])) {
    $canAssignUsers = true;
}

echo json_encode([
    'status' => 'success',
    'can_assign_users' => $canAssignUsers,
    'user_type' => $user['user_type'] ?? 'end_user'
]);
