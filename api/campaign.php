<?php
require_once __DIR__ . '/../bootstrap.php';

use Core\Campaigns\CampaignManager;
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

$campaign = new CampaignManager($user['UserUUID']);

switch ($action) {
    case 'list':
        echo json_encode($campaign->getAllCampaigns());
        break;
    case 'create':
        echo json_encode($campaign->createCampaign($_POST));
        break;
    case 'update':
        echo json_encode($campaign->updateCampaign($_POST));
        break;
    case 'delete':
        if (empty($_POST['CampaignUUID'])) {
            echo json_encode(['error' => 'Missing CampaignUUID']);
        } else {
            echo json_encode($campaign->deleteCampaign($_POST['CampaignUUID']));
        }
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
