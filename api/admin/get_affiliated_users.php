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
// Get user type
$stmt = $pdo->prepare("
    SELECT user_type 
    FROM mka_users 
    WHERE UserUUID = ?
");
$stmt->execute([$userUUID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !in_array($user['user_type'], ['super_user', 'enterprise_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
    exit;
}

// Get affiliated users
// For super_user: get all users
// For enterprise_admin: get users under their account (parent_user_uuid)
if ($user['user_type'] === 'super_user') {
    $stmt = $pdo->prepare("
        SELECT UserUUID, Name, Email 
        FROM mka_users 
        WHERE UserUUID != ?
        ORDER BY Name
    ");
    $stmt->execute([$userUUID]);
} else {
    // enterprise_admin: get their child users
    $stmt = $pdo->prepare("
        SELECT UserUUID, Name, Email 
        FROM mka_users 
        WHERE parent_user_uuid = ?
        ORDER BY Name
    ");
    $stmt->execute([$userUUID]);
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'users' => $users
]);