<?php
// Thin public wrapper - just authentication and delegation
session_start();
if (empty($_SESSION['user_data']['user_uuid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

// Now call the real logic
require_once '/opt/mka/api/admin/add_update_picture.php';
