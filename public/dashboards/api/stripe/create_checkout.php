<?php
session_start();

// Get JSON input to check for signup flow
$input = json_decode(file_get_contents('php://input'), true);

// Allow either authenticated session OR user_uuid from signup flow
if (empty($_SESSION['user_data']['user_uuid']) && empty($input['user_uuid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Problem creating user.  Please contact support.']);
    exit;
}

// If user_uuid provided in request (signup flow), temporarily set it in session for the real handler
if (!empty($input['user_uuid'])) {
    $_SESSION['user_data']['user_uuid'] = $input['user_uuid'];
}

// Now call the real logic
require_once '/opt/mka/api/stripe/create_checkout.php';