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

$config = require __DIR__ . '/../config/exchange.php';

$scope = implode(' ', [
    'openid',
    'offline_access',
    'SMTP.Send',
    'email',
]);

$authUrl = 'https://login.microsoftonline.com/' . $config['tenant'] . '/oauth2/v2.0/authorize?' . http_build_query([
    'client_id' => $config['client_id'],
    'response_type' => 'code',
    'redirect_uri' => $config['redirect_uri'],
    'response_mode' => 'query',
    'scope' => $scope,
    'state' => bin2hex(random_bytes(8)), // Optional security
]);

header('Location: ' . $authUrl);
exit;

