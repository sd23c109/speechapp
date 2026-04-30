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
$userType = $_SESSION['user_data']['user_type'] ?? '';

$assignmentId = $_GET['assignment_id'] ?? null;

if (!$assignmentId) {
    echo json_encode(['status' => 'error', 'message' => 'Assignment ID required']);
    exit;
}

try {
    // Get assignment details
    // super_user sees any assignment; others must be creator or assignee
    if ($userType === 'super_user') {
        $stmt = $pdo->prepare("
            SELECT
                ag.assignment_group_id,
                ag.assignment_name,
                ag.assignment_description,
                ag.created_by_user_uuid,
                ag.created_at,
                1 as is_creator
            FROM exercise_assignment_groups ag
            WHERE ag.assignment_group_id = ?
            AND ag.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$assignmentId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT
                ag.assignment_group_id,
                ag.assignment_name,
                ag.assignment_description,
                ag.created_by_user_uuid,
                ag.created_at,
                (ag.created_by_user_uuid = ?) as is_creator
            FROM exercise_assignment_groups ag
            LEFT JOIN exercise_assignment_users au ON ag.assignment_group_id = au.assignment_group_id
            WHERE ag.assignment_group_id = ?
            AND ag.is_active = 1
            AND (au.assigned_to_user_uuid = ? OR ag.created_by_user_uuid = ?)
            LIMIT 1
        ");
        $stmt->execute([$userUUID, $assignmentId, $userUUID, $userUUID]);
    }
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found or access denied']);
        exit;
    }

    // Get exercises for this assignment
    $stmt = $pdo->prepare("
        SELECT 
            assignment_exercise_id,
            exercise_name,
            card_count,
            orientation,
            display_order
        FROM exercise_assignment_exercises
        WHERE assignment_group_id = ?
        ORDER BY display_order ASC
    ");

    $stmt->execute([$assignmentId]);
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get cards for each exercise
    foreach ($exercises as &$exercise) {
        $stmt = $pdo->prepare("
            SELECT 
                content_type,
                content_id,
                card_position
            FROM exercise_assignment_cards
            WHERE assignment_exercise_id = ?
            ORDER BY card_position ASC
        ");

        $stmt->execute([$exercise['assignment_exercise_id']]);
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Resolve content_id to actual content data
        $resolvedCards = [];
        foreach ($cards as $card) {
            $contentData = resolveContent($pdo, $card['content_type'], $card['content_id']);
            if ($contentData) {
                $resolvedCards[] = [
                    'type' => $card['content_type'],
                    'id' => $contentData['code'],
                    'position' => $card['card_position']
                ];
            }
        }

        $exercise['cards'] = $resolvedCards;
    }

    // Get assigned users (for editing)
    $stmt = $pdo->prepare("
        SELECT assigned_to_user_uuid
        FROM exercise_assignment_users
        WHERE assignment_group_id = ?
    ");
    $stmt->execute([$assignmentId]);
    $assignedUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $assignment['exercises'] = $exercises;
    $assignment['assigned_users'] = $assignedUsers;
    $assignment['is_creator'] = (bool)$assignment['is_creator'];

    echo json_encode([
        'status' => 'success',
        'data' => $assignment
    ]);

} catch (Exception $e) {
    error_log("Get assignment error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load assignment: ' . $e->getMessage()
    ]);
}

// Helper function to resolve content ID back to code
function resolveContent($pdo, $type, $contentId)
{
    try {
        if ($type === 'consonant') {
            $stmt = $pdo->prepare("SELECT consonant_code as code FROM exercise_consonants WHERE consonant_id = ?");
            $stmt->execute([$contentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } elseif ($type === 'vowel') {
            $stmt = $pdo->prepare("SELECT vowel_code as code FROM exercise_vowels WHERE vowel_id = ?");
            $stmt->execute([$contentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } elseif ($type === 'cv' || $type === 'cv_blend') {
            $stmt = $pdo->prepare("
                SELECT CONCAT(c.consonant_code, '-', v.vowel_code) as code
                FROM exercise_cv_blends cv
                JOIN exercise_consonants c ON cv.consonant_id = c.consonant_id
                JOIN exercise_vowels v ON cv.vowel_id = v.vowel_id
                WHERE cv.cv_id = ?
            ");
            $stmt->execute([$contentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } elseif ($type === '3cv' || $type === '3cv_blend') {
            $stmt = $pdo->prepare("
                SELECT CONCAT(c.consonant_code, '-', v.vowel_code) as code
                FROM exercise_3cv_blends b
                JOIN exercise_consonants c ON b.consonant_id = c.consonant_id
                JOIN exercise_vowels v ON b.vowel_id = v.vowel_id
                WHERE b.blend_3cv_id = ?
            ");
            $stmt->execute([$contentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } elseif ($type === 'word') {
            $stmt = $pdo->prepare("SELECT word_text as code FROM exercise_words WHERE word_id = ?");
            $stmt->execute([$contentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return null;

    } catch (Exception $e) {
        error_log("resolveContent error: " . $e->getMessage());
        return null;
    }
}
