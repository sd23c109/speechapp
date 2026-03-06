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

if (!$input || !isset($input['assignment_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$assignmentId = $input['assignment_id'];

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

    // Remove all user assignments
    $stmt = $pdo->prepare("
        DELETE FROM exercise_assignment_users 
        WHERE assignment_group_id = ?
    ");
    $stmt->execute([$assignmentId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'All users unassigned successfully'
    ]);

} catch (Exception $e) {
    error_log("Unassign all users error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to unassign users'
    ]);
}