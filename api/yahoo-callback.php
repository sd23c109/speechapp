<?php
require_once __DIR__ . '/../bootstrap.php';

session_start();

$config = require __DIR__ . '/../config/yahoo.php';

if (!isset($_GET['code'])) {
    echo "No code provided.";
    exit;
}

// Exchange authorization code for access token
$response = file_get_contents('https://api.login.yahoo.com/oauth2/get_token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nAuthorization: Basic " . base64_encode($config['client_id'] . ':' . $config['client_secret']),
        'content' => http_build_query([
            'code' => $_GET['code'],
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
$email = ''; // Yahoo doesn't always return email here easily - we'll handle that better later

$userUUID = $_SESSION['MKA_UserUUID'] ?? null;
if (!$userUUID) {
    echo "Missing user session.";
    exit;
}

// Save sender info
$db = $GLOBALS['pdo'];
$stmt = $db->prepare("
    INSERT INTO mka_senders
    (SenderUUID, UserUUID, Email, Provider, AuthType, AuthToken, RefreshToken, SMTPServer, SMTPPort, UseTLS)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    bin2hex(random_bytes(16)),
    $userUUID,
    $email,
    'yahoo',
    'oauth',
    $access_token,
    $refresh_token,
    'smtp.mail.yahoo.com',
    587,
    'y'
]);

echo "Yahoo account connected!";

