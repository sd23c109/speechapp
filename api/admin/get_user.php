<?php
require_once '/opt/mka/bootstrap.php';

header('Content-Type: application/json');

$requestingUser = $_SESSION['user_data']['user_uuid'] ?? null;
$targetUserUuid = $_GET['user_uuid'] ?? '';

if (!$targetUserUuid) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'User UUID required']);
    exit;
}

// Get user data
$stmt = $GLOBALS['pdo']->prepare("
    SELECT UserUUID, Email, Name, company_name, user_type, Status, IsPaid, email_confirmed, CreatedAt
    FROM mka_users
    WHERE UserUUID = ?
");
$stmt->execute([$targetUserUuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'fail', 'message' => 'User not found']);
    exit;
}

// Permission check - can only view users you created or yourself
$stmt = $GLOBALS['pdo']->prepare("
    SELECT parent_user_uuid, user_type FROM mka_users WHERE UserUUID = ?
");
$stmt->execute([$targetUserUuid]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Get requesting user type
$stmt = $GLOBALS['pdo']->prepare("
    SELECT user_type FROM mka_users WHERE UserUUID = ?
");
$stmt->execute([$requestingUser]);
$requestingUserType = $stmt->fetchColumn();

// Super users can see anyone
// Enterprise admins can see users they created
// Users can see themselves
$canView = false;

if ($requestingUserType === 'super_user') {
    $canView = true;
} else if ($targetUser['parent_user_uuid'] === $requestingUser) {
    $canView = true;
} else if ($targetUserUuid === $requestingUser) {
    $canView = true;
}

if (!$canView) {
    http_response_code(403);
    echo json_encode(['status' => 'fail', 'message' => 'Access denied']);
    exit;
}

echo json_encode(['status' => 'success', 'user' => $user]);
