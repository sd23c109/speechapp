<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

header('Content-Type: application/json');
error_log('GETTING FORM CONFIG');
$GLOBALS['current_dashboard'] = 'incentive_engine';
require_once(__DIR__ . '/../bootstrap.php');

if (!isset($_GET['user_uuid'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_uuid']);
    exit;
}

// Get default_reward_id from user record
$stmt = $pdo->prepare("SELECT default_reward_id FROM mka_users WHERE UserUUID = ?");
$stmt->execute([$_GET['user_uuid']]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
 error_log('Did I get a reward');
error_log(json_encode($userRow));
if (!$userRow) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

if (!$userRow['default_reward_id']) {
    // No default reward set
    echo json_encode(['reward_id' => null]);
    exit;
}

// Get reward details
$stmt = $pdo->prepare("SELECT reward_id, title, description, expires_at FROM mka_rewards WHERE reward_id = ?");
$stmt->execute([$userRow['default_reward_id']]);
$rewardRow = $stmt->fetch(PDO::FETCH_ASSOC);

error_log('Did I get a reward');
error_log(json_encode($rewardRow));

if (!$rewardRow) {
    echo json_encode(['reward_id' => null]);
    exit;
}

// Check expiration
$isExpired = ($rewardRow['expires_at'] && strtotime($rewardRow['expires_at']) < time());

echo json_encode([
    'reward_id' => $rewardRow['reward_id'],
    'reward_title' => $rewardRow['title'],
    'reward_description' => $rewardRow['description'],
    'expires_at' => $rewardRow['expires_at'],
    'is_expired' => $isExpired,
    'fine_print' => '*Approval required.'
]);


?>

