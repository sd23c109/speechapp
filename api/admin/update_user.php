<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/UserManagement.php';

use MKA\Admin\UserManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

session_start();
$updatedBy = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$updatedBy) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userUuid = $data['user_uuid'] ?? '';

if (!$userUuid) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'User UUID required']);
    exit;
}

$result = UserManagement::updateUser($userUuid, $data, $updatedBy);

if ($result['status'] === 'success' && array_key_exists('slp_affiliation', $data)) {
    // Only super_users can change affiliations
    $stmt = $GLOBALS['pdo']->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
    $stmt->execute([$updatedBy]);
    $updaterType = $stmt->fetchColumn();

    if ($updaterType === 'super_user') {
        $newSlpUuid = trim($data['slp_affiliation'] ?? '');

        // Deactivate all current active affiliations for this patient
        $stmt = $GLOBALS['pdo']->prepare("
            UPDATE patient_affiliations
            SET status = 'inactive', deactivated_at = NOW()
            WHERE patient_uuid = ? AND status = 'active'
        ");
        $stmt->execute([$userUuid]);

        if ($newSlpUuid !== '') {
            // Verify the target SLP exists and is enterprise_admin
            $stmt = $GLOBALS['pdo']->prepare("SELECT UserUUID FROM mka_users WHERE UserUUID = ? AND user_type = 'enterprise_admin'");
            $stmt->execute([$newSlpUuid]);
            if ($stmt->fetchColumn()) {
                // Reactivate existing row if present, otherwise insert new
                $stmt = $GLOBALS['pdo']->prepare("
                    SELECT affiliation_uuid FROM patient_affiliations
                    WHERE patient_uuid = ? AND slp_uuid = ?
                    LIMIT 1
                ");
                $stmt->execute([$userUuid, $newSlpUuid]);
                $existingUuid = $stmt->fetchColumn();

                if ($existingUuid) {
                    $stmt = $GLOBALS['pdo']->prepare("
                        UPDATE patient_affiliations
                        SET status = 'active', deactivated_at = NULL, affiliated_at = NOW()
                        WHERE affiliation_uuid = ?
                    ");
                    $stmt->execute([$existingUuid]);
                } else {
                    $stmt = $GLOBALS['pdo']->prepare("
                        INSERT INTO patient_affiliations (patient_uuid, slp_uuid, status)
                        VALUES (?, ?, 'active')
                    ");
                    $stmt->execute([$userUuid, $newSlpUuid]);
                }
            }
        }
    }
}

http_response_code($result['status'] === 'success' ? 200 : 400);
echo json_encode($result);