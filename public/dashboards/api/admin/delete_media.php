<?php
session_start();
if (empty($_SESSION['user_data']['user_uuid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

require_once '/opt/mka/api/admin/delete_media.php';

