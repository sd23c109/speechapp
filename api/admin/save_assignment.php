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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['assignment_name']) || !isset($input['exercises'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$assignmentId = $input['assignment_id'] ?? null;
$assignmentName = trim($input['assignment_name']);
$assignmentDescription = trim($input['assignment_description'] ?? '');
$exercises = $input['exercises'];
$assignedUsers = $input['assigned_users'] ?? [];

if (empty($assignmentName)) {
    echo json_encode(['status' => 'error', 'message' => 'Assignment name required']);
    exit;
}

if (empty($exercises)) {
    echo json_encode(['status' => 'error', 'message' => 'At least one exercise required']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($assignmentId) {
        // UPDATE existing assignment

        // Verify user is creator
        $stmt = $pdo->prepare("SELECT created_by_user_uuid FROM exercise_assignment_groups WHERE assignment_group_id = ?");
        $stmt->execute([$assignmentId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing || $existing['created_by_user_uuid'] !== $userUUID) {
            throw new Exception('Permission denied');
        }

        // Update assignment details
        $stmt = $pdo->prepare("
            UPDATE exercise_assignment_groups 
            SET assignment_name = ?, assignment_description = ?, updated_at = NOW()
            WHERE assignment_group_id = ?
        ");
        $stmt->execute([$assignmentName, $assignmentDescription, $assignmentId]);

        // Delete existing exercises and cards (cascade will handle cards)
        $stmt = $pdo->prepare("DELETE FROM exercise_assignment_exercises WHERE assignment_group_id = ?");
        $stmt->execute([$assignmentId]);

        // Delete existing user assignments
        $stmt = $pdo->prepare("DELETE FROM exercise_assignment_users WHERE assignment_group_id = ?");
        $stmt->execute([$assignmentId]);

        $assignmentGroupId = $assignmentId;

    } else {
        // CREATE new assignment
        $stmt = $pdo->prepare("
            INSERT INTO exercise_assignment_groups 
            (created_by_user_uuid, assignment_name, assignment_description, is_active, created_at)
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$userUUID, $assignmentName, $assignmentDescription]);
        $assignmentGroupId = $pdo->lastInsertId();
    }

    // Insert exercises and cards
    foreach ($exercises as $idx => $exercise) {
        // Insert exercise
        $stmt = $pdo->prepare("
            INSERT INTO exercise_assignment_exercises 
            (assignment_group_id, exercise_name, card_count, orientation, display_order, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $assignmentGroupId,
            $exercise['exercise_name'],
            $exercise['card_count'],
            $exercise['orientation'],
            $idx
        ]);
        $exerciseId = $pdo->lastInsertId();

        // Insert cards
        // Insert cards
        foreach ($exercise['cards'] as $cardIdx => $card) {
            // Normalize content type to match database enum
            $contentType = $card['type'];
            if ($contentType === 'cv') {
                $contentType = 'cv_blend';
            } elseif ($contentType === '3cv') {
                $contentType = '3cv_blend';
            }

            $contentId = getContentId($pdo, $card['type'], $card['id'], $userUUID);

            if ($contentId) {
                $stmt = $pdo->prepare("
            INSERT INTO exercise_assignment_cards 
            (assignment_exercise_id, content_type, content_id, card_position, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
                $stmt->execute([
                    $exerciseId,
                    $contentType,  // Use normalized type here
                    $contentId,
                    $cardIdx
                ]);
            }
        }
    }

    // Assign to users
    if (empty($assignedUsers)) {
        $assignedUsers = [$userUUID];
    }

    foreach ($assignedUsers as $assignedUserUUID) {
        $stmt = $pdo->prepare("
            INSERT INTO exercise_assignment_users 
            (assignment_group_id, assigned_to_user_uuid, assigned_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$assignmentGroupId, $assignedUserUUID]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'assignment_id' => $assignmentGroupId,
        'message' => $assignmentId ? 'Assignment updated successfully' : 'Assignment saved successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Save assignment error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save assignment: ' . $e->getMessage()
    ]);
}

// Helper function to get content ID from database
function getContentId($pdo, $type, $code, $userUUID) {
    try {
        if ($type === 'consonant') {
            $stmt = $pdo->prepare("
                SELECT consonant_id 
                FROM exercise_consonants 
                WHERE consonant_code = ? AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
                LIMIT 1
            ");
            $stmt->execute([$code, $userUUID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['consonant_id'] : null;

        } elseif ($type === 'vowel') {
            $stmt = $pdo->prepare("
                SELECT vowel_id 
                FROM exercise_vowels 
                WHERE vowel_code = ? AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
                LIMIT 1
            ");
            $stmt->execute([$code, $userUUID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['vowel_id'] : null;

        } elseif ($type === 'cv' || $type === 'cv_blend') {
            list($consCode, $vowCode) = explode('-', $code);
            $stmt = $pdo->prepare("
                SELECT cv.cv_id 
                FROM exercise_cv_blends cv
                JOIN exercise_consonants c ON cv.consonant_id = c.consonant_id
                JOIN exercise_vowels v ON cv.vowel_id = v.vowel_id
                WHERE c.consonant_code = ? AND v.vowel_code = ?
                AND (cv.owner_user_uuid IS NULL OR cv.owner_user_uuid = ?)
                LIMIT 1
            ");
            $stmt->execute([$consCode, $vowCode, $userUUID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['cv_id'] : null;

        } elseif ($type === '3cv' || $type === '3cv_blend') {
            list($consCode, $vowCode) = explode('-', $code);
            $stmt = $pdo->prepare("
                SELECT b.blend_3cv_id 
                FROM exercise_3cv_blends b
                JOIN exercise_consonants c ON b.consonant_id = c.consonant_id
                JOIN exercise_vowels v ON b.vowel_id = v.vowel_id
                WHERE c.consonant_code = ? AND v.vowel_code = ?
                AND (b.owner_user_uuid IS NULL OR b.owner_user_uuid = ?)
                LIMIT 1
            ");
            $stmt->execute([$consCode, $vowCode, $userUUID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['blend_3cv_id'] : null;

        } elseif ($type === 'word') {
            $stmt = $pdo->prepare("
                SELECT word_id 
                FROM exercise_words 
                WHERE word_text = ? AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
                LIMIT 1
            ");
            $stmt->execute([$code, $userUUID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['word_id'] : null;
        }

        return null;

    } catch (Exception $e) {
        error_log("getContentId error: " . $e->getMessage());
        return null;
    }
}