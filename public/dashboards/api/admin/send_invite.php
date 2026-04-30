<?php
session_start();

// Check authentication
if (empty($_SESSION['user_data']['user_uuid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

// Verify user is enterprise_admin or super_user
$allowedRoles = ['enterprise_admin', 'super_user'];
if (!in_array($_SESSION['user_data']['user_type'], $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['status' => 'fail', 'message' => 'Permission denied']);
    exit;
}

// Delegate to real implementation
require_once '/opt/mka/api/admin/send_invite.php';
