<?php
session_start();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Allow either authenticated session OR slp_uuid from request
if (empty($_SESSION['user_data']['user_uuid']) && empty($input['slp_uuid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

// If slp_uuid provided in request, temporarily set it in session
if (!empty($input['slp_uuid'])) {
    $_SESSION['user_data']['user_uuid'] = $input['slp_uuid'];
}

// Delegate to real implementation
require_once '/opt/mka/api/billing/purchase_capacity_pack.php';
