<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/core/Auth/SignupHandler.php');
require_once('/opt/mka/core/Tasks/Utils.php');
require_once('/opt/mka/core/Log/MKALogger.php');

use MKA\Auth\SignupHandler;
use MKA\Log\MKALogger;
use MKA\Tasks\Utils;

// ✅ Ensure request is POST and JSON response
header('Content-Type: application/json');

// Check if user is logged in

if (empty($_SESSION['user_data']['user_uuid'])) {
    echo json_encode(['status' => 'fail', 'message' => 'Unauthorized.']);
    exit;
}

$userUuid = $_SESSION['user_data']['user_uuid'];

// Sanitize incoming data
$company = trim($_POST['company_name'] ?? '');
$name    = trim($_POST['name'] ?? '');
$domain  = trim($_POST['website'] ?? '');

$domain = Utils::extractDomain($domain);

if (empty($company) || empty($name) || empty($domain)) {
    echo json_encode(['status' => 'fail', 'message' => 'All fields are required.']);
    exit;
}

// Optional domain format check (very basic)
if (!filter_var('https://' . $domain, FILTER_VALIDATE_URL) && !preg_match('/\./', $domain)) {
    echo json_encode(['status' => 'fail', 'message' => 'Invalid website format.']);
    exit;
}

// Call update method
$result = SignupHandler::updateNewUser($userUuid, $company, $name, $domain);

// If success, update session data to reflect new values
if ($result['status'] === 'success') {
    $_SESSION['user_data']['company_name'] = $company;
    $_SESSION['user_data']['company_slug'] = SignupHandler::generateSlug($company);
    $_SESSION['user_data']['Name'] = $name;
    $_SESSION['user_data']['Domain'] = $domain;
}

echo json_encode($result);

