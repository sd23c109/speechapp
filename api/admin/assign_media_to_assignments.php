<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Admin/MediaManagement.php';

use MKA\Admin\MediaManagement;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$userUuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$userUuid) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$mediaId = (int)($input['media_id'] ?? 0);
$assignmentIds = $input['assignment_ids'] ?? [];

if ($mediaId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid media ID']);
    exit;
}

if (!is_array($assignmentIds)) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'assignment_ids must be an array']);
    exit;
}

try {
    global $pdo;

    // Get user type for permission check
    $stmt = $pdo->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
    $stmt->execute([$userUuid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    // First, remove all existing assignments for this media
    $stmt = $pdo->prepare("
        DELETE asm FROM assignment_success_media asm
        INNER JOIN exercise_assignment_groups ag ON asm.assignment_group_id = ag.assignment_group_id
        WHERE asm.media_id = ? 
        " . ($user['user_type'] !== 'super_user' ? "AND ag.created_by_user_uuid = ?" : "")
    );

    if ($user['user_type'] !== 'super_user') {
        $stmt->execute([$mediaId, $userUuid]);
    } else {
        $stmt->execute([$mediaId]);
    }

    // Add new assignments
    if (!empty($assignmentIds)) {
        $stmt = $pdo->prepare("
            INSERT INTO assignment_success_media 
            (assignment_group_id, media_id, selection_type)
            SELECT ag.assignment_group_id, ?, 'sequential'
            FROM exercise_assignment_groups ag
            WHERE ag.assignment_group_id = ?
            " . ($user['user_type'] !== 'super_user' ? "AND ag.created_by_user_uuid = ?" : "")
        );

        foreach ($assignmentIds as $assignId) {
            if ($user['user_type'] !== 'super_user') {
                $stmt->execute([$mediaId, $assignId, $userUuid]);
            } else {
                $stmt->execute([$mediaId, $assignId]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Media assignments updated successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("assign_media_to_assignments error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}

