<?php
require_once('/opt/mka/dashboards/_init.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_data']['user_uuid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$pdo = $GLOBALS['pdo'];
$userUUID = $_SESSION['user_data']['user_uuid'];

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['assignment_id']) || !isset($input['user_uuid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$assignmentId = $input['assignment_id'];
$targetUserUUID = $input['user_uuid'];

try {
    // Verify user is the creator
    $stmt = $pdo->prepare("
        SELECT created_by_user_uuid 
        FROM exercise_assignment_groups 
        WHERE assignment_group_id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
        exit;
    }

    if ($assignment['created_by_user_uuid'] !== $userUUID) {
        echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
        exit;
    }

    // Remove the user assignment
    $stmt = $pdo->prepare("
        DELETE FROM exercise_assignment_users 
        WHERE assignment_group_id = ? AND assigned_to_user_uuid = ?
    ");
    $stmt->execute([$assignmentId, $targetUserUUID]);

    echo json_encode([
        'status' => 'success',
        'message' => 'User unassigned successfully'
    ]);

} catch (Exception $e) {
    error_log("Unassign user error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to unassign user'
    ]);
}