<?php
require_once __DIR__ . '/../bootstrap.php';

use Core\Email\SenderManager;
use Core\Users\AuthTokenManager;

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$token = $_REQUEST['token'] ?? '';

$user = AuthTokenManager::validate($token);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$sender = new SenderManager($user['UserUUID']);

switch ($action) {
    case 'list':
        echo json_encode($sender->getAllSenders());
        break;
    case 'create':
        echo json_encode($sender->createSender($_POST));
        break;
    case 'delete':
        echo json_encode($sender->deleteSender($_POST['SenderUUID']));
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
