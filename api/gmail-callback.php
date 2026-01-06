<?php
require_once __DIR__ . '/../bootstrap.php';

use MKA\Users\AuthTokenManager;

session_start();

$config = require __DIR__ . '/../config/google.php';

if (!isset($_GET['code'])) {
    echo "No code provided.";
    exit;
}

// Exchange authorization code for access token
$response = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'code' => $_GET['code'],
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]),
    ]
]));

$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    echo "Failed to get access token.";
    exit;
}

$access_token = $data['access_token'];
$refresh_token = $data['refresh_token'] ?? null;

// Fetch user's Gmail email address
$userinfo = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer " . $access_token . "\r\n"
    ]
]));

$userinfo = json_decode($userinfo, true);

$email = $userinfo['email'] ?? '';

if (!$email) {
    echo "Failed to fetch user email.";
    exit;
}

// Now save to mka_senders
$userUUID = $_SESSION['MKA_UserUUID'] ?? null;
if (!$userUUID) {
    echo "Missing user session.";
    exit;
}

$db = $GLOBALS['pdo'];
$stmt = $db->prepare("
    INSERT INTO mka_senders
    (SenderUUID, UserUUID, Email, Provider, AuthType, AuthToken, RefreshToken, SMTPServer, SMTPPort, UseTLS)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    bin2hex(random_bytes(16)),
    $userUUID,
    $email,                   //Save fetched email here
    'gmail',
    'oauth',
    $access_token,
    $refresh_token,
    'smtp.gmail.com',
    587,
    'y'
]);



header('Location: https://www.mkadvantage.com/dashboard?msg=gmail-success'); // TODO reflect message based on $_GET['msg']
exit;
