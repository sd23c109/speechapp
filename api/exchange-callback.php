<?php
require_once __DIR__ . '/../bootstrap.php';

session_start();

$config = require __DIR__ . '/../config/exchange.php';

if (!isset($_GET['code'])) {
    echo "No code provided.";
    exit;
}

// Exchange authorization code for access token
$response = file_get_contents('https://login.microsoftonline.com/' . $config['tenant'] . '/oauth2/v2.0/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'client_id' => $config['client_id'],
            'scope' => 'openid offline_access SMTP.Send email',
            'code' => $_GET['code'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
            'client_secret' => $config['client_secret'],
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

// OPTIONAL: Fetch user's email address
$userInfo = file_get_contents('https://graph.microsoft.com/v1.0/me', false, stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer {$access_token}\r\n",
    ]
]));
$userInfoData = json_decode($userInfo, true);

$email = $userInfoData['mail'] ?? ($userInfoData['userPrincipalName'] ?? '');

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
    'exchange',
    'oauth',
    $access_token,
    $refresh_token,
    'smtp.office365.com',
    587,
    'y'
]);

echo "✅ Exchange account connected!";
