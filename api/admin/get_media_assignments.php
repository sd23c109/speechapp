<?php
require_once '/opt/mka/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$mediaId = (int)($_GET['media_id'] ?? 0);
if ($mediaId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid media ID']);
    exit;
}

try {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT asm.assignment_group_id, asm.selection_type,
               ag.assignment_name
        FROM assignment_success_media asm
        INNER JOIN exercise_assignment_groups ag ON asm.assignment_group_id = ag.assignment_group_id
        WHERE asm.media_id = ?
        ORDER BY ag.assignment_name ASC
    ");
    $stmt->execute([$mediaId]);

    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $assignments
    ]);

} catch (Exception $e) {
    error_log("get_media_assignments error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}
