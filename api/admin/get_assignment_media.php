<?php
require_once '/opt/mka/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'message' => 'Method not allowed']);
    exit;
}

$assignmentId = (int)($_GET['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid assignment ID']);
    exit;
}

try {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT m.media_id, m.media_type, m.media_name, 
               m.media_path, m.allow_sound, m.autoplay_loop,
               asm.selection_type
        FROM assignment_success_media asm
        INNER JOIN exercise_success_media m ON asm.media_id = m.media_id
        WHERE asm.assignment_group_id = ? AND m.is_active = 1
        ORDER BY asm.display_order ASC
        LIMIT 1
    ");
    $stmt->execute([$assignmentId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $media ?: null
    ]);

} catch (Exception $e) {
    error_log("get_assignment_media error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server error'
    ]);
}
