<?php
session_start();
require_once '/opt/mka/vendor/autoload.php';
use MKA\Log\MKALogger;
// Log to HIPAA before you go
$reason = $_GET['reason'] ?? 'manual';

if (isset($_SESSION['user_data']['user_uuid'])) {
    MKALogger::log($reason === 'timeout' ? 'timeout_logout' : 'logout', [
        'user_uuid' => $_SESSION['user_data']['user_uuid'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
}
// Destroy PHP session data
$_SESSION = [];
session_destroy();

// Remove session cookie � must match exactly how it was set
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
);

// Also remove our custom app cookie
setcookie('mka_user_uuid', '', time() - 3600, '/', '.mkadvantage.com');

// Optional: Clear all other app-related cookies
foreach ($_COOKIE as $key => $value) {
    setcookie($key, '', time() - 3600, '/', '.mkadvantage.com');
}


// Cache control
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: https://speechapp.virtuopsdev.com/dashboards/login.php");
exit;
