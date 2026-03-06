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

try {
    // Get assignments that are assigned to this user OR created by this user
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            ag.assignment_group_id,
            ag.assignment_name,
            ag.assignment_description,
            ag.created_by_user_uuid,
            ag.created_at,
            ag.updated_at,
            (ag.created_by_user_uuid = ?) as is_creator,
            COUNT(DISTINCT ae.assignment_exercise_id) as exercise_count
        FROM exercise_assignment_groups ag
        INNER JOIN exercise_assignment_users au ON ag.assignment_group_id = au.assignment_group_id
        LEFT JOIN exercise_assignment_exercises ae ON ag.assignment_group_id = ae.assignment_group_id
        WHERE ag.is_active = 1
        AND au.assigned_to_user_uuid = ?
        GROUP BY ag.assignment_group_id
        
        UNION
        
        SELECT DISTINCT
            ag.assignment_group_id,
            ag.assignment_name,
            ag.assignment_description,
            ag.created_by_user_uuid,
            ag.created_at,
            ag.updated_at,
            (ag.created_by_user_uuid = ?) as is_creator,
            COUNT(DISTINCT ae.assignment_exercise_id) as exercise_count
        FROM exercise_assignment_groups ag
        LEFT JOIN exercise_assignment_exercises ae ON ag.assignment_group_id = ae.assignment_group_id
        WHERE ag.is_active = 1
        AND ag.created_by_user_uuid = ?
        GROUP BY ag.assignment_group_id
        
        ORDER BY created_at DESC
    ");

    $stmt->execute([$userUUID, $userUUID, $userUUID, $userUUID]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get assigned users for each assignment
    foreach ($assignments as &$assignment) {
        $assignment['is_creator'] = (bool)$assignment['is_creator'];

        // Get assigned users
        $stmt = $pdo->prepare("
            SELECT u.UserUUID, u.Name, u.Email
            FROM exercise_assignment_users au
            JOIN mka_users u ON au.assigned_to_user_uuid = u.UserUUID
            WHERE au.assignment_group_id = ?
            ORDER BY u.Name
        ");
        $stmt->execute([$assignment['assignment_group_id']]);
        $assignment['assigned_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $assignments
    ]);

} catch (Exception $e) {
    error_log("Get assignments error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load assignments'
    ]);
}