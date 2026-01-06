<?php
require_once __DIR__ . '/../bootstrap.php';

use MKA\Users\AuthTokenManager;

session_start();

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

$_SESSION['MKA_UserUUID'] = $userData['user_uuid'];

$config = require __DIR__ . '/../config/yahoo.php';

$scope = implode(' ', [
    'mail-r',
    'mail-w'
]);

$authUrl = 'https://api.login.yahoo.com/oauth2/request_auth?' . http_build_query([
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'],
    'response_type' => 'code',
    'scope' => $scope,
]);

header('Location: ' . $authUrl);
exit;
