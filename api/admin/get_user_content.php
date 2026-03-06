<?php
require_once '/opt/mka/bootstrap.php';

header('Content-Type: application/json');

$userUuid = $_SESSION['user_data']['user_uuid'] ?? null;
if (!$userUuid) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'message' => 'Not authenticated']);
    exit;
}

$type = $_GET['type'] ?? '';

$tables = [
    'consonant' => 'exercise_consonants',
    'vowel' => 'exercise_vowels',
    'cv_blend' => 'exercise_cv_blends',
    '3cv_blend' => 'exercise_3cv_blends',
    'word' => 'exercise_words'
];

if (!isset($tables[$type])) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Invalid type']);
    exit;
}

$table = $tables[$type];

// For CV and 3CV blends, join with consonants and vowels to get the codes
if ($type === 'cv_blend') {
    $stmt = $pdo->prepare("
        SELECT cv.*, c.consonant_code, v.vowel_code
        FROM exercise_cv_blends cv
        JOIN exercise_consonants c ON cv.consonant_id = c.consonant_id
        JOIN exercise_vowels v ON cv.vowel_id = v.vowel_id
        WHERE cv.owner_user_uuid = ? AND cv.is_active = 1
        ORDER BY cv.display_order ASC
    ");
} elseif ($type === '3cv_blend') {
    $stmt = $pdo->prepare("
        SELECT b.*, c.consonant_code, v.vowel_code
        FROM exercise_3cv_blends b
        JOIN exercise_consonants c ON b.consonant_id = c.consonant_id
        JOIN exercise_vowels v ON b.vowel_id = v.vowel_id
        WHERE b.owner_user_uuid = ? AND b.is_active = 1
        ORDER BY b.display_order ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM $table 
        WHERE owner_user_uuid = ? AND is_active = 1
        ORDER BY display_order ASC
    ");
}

$stmt->execute([$userUuid]);

echo json_encode([
    'status' => 'success',
    'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);