<?php
require_once __DIR__ . '/../bootstrap.php';

use MKA\Users\AuthTokenManager;

session_start(); // <-- CRITICAL to start session at top

$token = $_GET['token'] ?? '';
if (!$token) {
    echo "Missing token.";
    exit;
}

$auth = new AuthTokenManager();
$userData = $auth->validate($token);

if (!$userData) {
    echo "Invalid token.";
    exit;
}

// ve the user UUID into session
$_SESSION['MKA_UserUUID'] = $userData['user_uuid'];

$config = require __DIR__ . '/../config/google.php';
$scope = implode(' ', [
    'https://mail.google.com/',
    'https://www.googleapis.com/auth/userinfo.email'
]);

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => $scope,
    'access_type' => 'offline',
    'prompt' => 'consent',
]);

header('Location: ' . $authUrl);
exit;
