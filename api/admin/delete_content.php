<?php
require_once '/opt/mka/bootstrap.php';

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

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$id = (int)($data['id'] ?? 0);

if (empty($type) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid parameters']);
    exit;
}

// Map content types to tables and ID columns
$tableMap = [
    'consonant' => ['table' => 'exercise_consonants', 'id_col' => 'consonant_id'],
    'vowel' => ['table' => 'exercise_vowels', 'id_col' => 'vowel_id'],
    'cv_blend' => ['table' => 'exercise_cv_blends', 'id_col' => 'cv_id'],
    '3cv_blend' => ['table' => 'exercise_3cv_blends', 'id_col' => 'blend_3cv_id'],
    'word' => ['table' => 'exercise_words', 'id_col' => 'word_id']
];

if (!isset($tableMap[$type])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid content type']);
    exit;
}

$table = $tableMap[$type]['table'];
$idCol = $tableMap[$type]['id_col'];

try {
    // Verify the content belongs to this user before deleting
    $stmt = $pdo->prepare("
        SELECT owner_user_uuid 
        FROM $table 
        WHERE $idCol = ?
    ");
    $stmt->execute([$id]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$content) {
        http_response_code(404);
        echo json_encode(['status' => 'fail', 'message' => 'Content not found']);
        exit;
    }

    // Only allow deletion if:
    // 1. User owns the content, OR
    // 2. User is super_user (can delete system content where owner_user_uuid IS NULL)
    $userType = $_SESSION['user_data']['user_type'] ?? '';
    $isSuperUser = ($userType === 'super_user');

    if ($content['owner_user_uuid'] !== $userUuid && !$isSuperUser) {
        http_response_code(403);
        echo json_encode(['status' => 'fail', 'message' => 'Permission denied']);
        exit;
    }

    // Soft delete - set is_active = 0
    $stmt = $pdo->prepare("
        UPDATE $table 
        SET is_active = 0 
        WHERE $idCol = ?
    ");
    $stmt->execute([$id]);

    // Log the deletion
    if (class_exists('MKA\Log\MKALogger')) {
        \MKA\Log\MKALogger::log('content_deleted', [
            'deleted_by' => $userUuid,
            'content_type' => $type,
            'content_id' => $id
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Content deleted successfully'
    ]);

} catch (\PDOException $e) {
    error_log("Delete content error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'fail',
        'message' => 'Database error occurred'
    ]);
}
