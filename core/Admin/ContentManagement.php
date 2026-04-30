<?php
namespace MKA\Admin;
require_once '/opt/mka/core/Log/MKALogger.php';
use PDO;
use MKA\Log\MKALogger;

/**
 * ContentManagement
 *
 * Manages exercise content (consonants, vowels, CV blends, 3CV blends, words)
 * with tier-based limits enforcement
 */
class ContentManagement {

    /**
     * Get available content for a user based on their tier
     *
     * Rules:
     * 1. Super users: ALL content (system + their own) - unlimited
     * 2. Enterprise users: System content + their own content (up to tier limit)
     * 3. End users: Their parent admin's content (up to parent's tier limit)
     */

    public static function getAvailableContent(string $userUuid, string $contentType): array {
        global $pdo;

        // Step 1: Get user info only — don't fail if no subscription exists
        $stmt = $pdo->prepare("
        SELECT user_type, parent_user_uuid
        FROM mka_users
        WHERE UserUUID = ?
    ");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("getAvailableContent DEBUG: userUuid=$userUuid user=" . json_encode($user));  // ADD THIS


        if (!$user) {
            return [];
        }

        // Step 2: Get tier limit separately — defaults to 0 (unlimited) if no subscription row
        $stmt = $pdo->prepare("
        SELECT pt.limit_sounds_words
        FROM user_subscriptions us
        INNER JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
        WHERE us.user_uuid = ?
          AND us.status IN ('trial', 'active')
        ORDER BY us.started_at DESC
        LIMIT 1
    ");
        $stmt->execute([$userUuid]);
        $tier = $stmt->fetch(PDO::FETCH_ASSOC);
        $limit = $tier['limit_sounds_words'] ?? 0;

        $isSuperUser      = ($user['user_type'] === 'super_user');
        $isEnterpriseAdmin = ($user['user_type'] === 'enterprise_admin');
        $isEndUser        = ($user['user_type'] === 'end_user');

        // Step 3: Determine which owner UUID to query content for
        $ownerUuid = null;
        if ($isSuperUser || $isEnterpriseAdmin) {
            $ownerUuid = $userUuid;
        } elseif ($isEndUser && !empty($user['parent_user_uuid'])) {
            // Invited end user — use parent's content
            $ownerUuid = $user['parent_user_uuid'];
        }
        // If end_user with no parent, ownerUuid stays null → returns system content only

        // Step 4: Query based on content type
        switch ($contentType) {
            case 'consonant':
                return self::getConsonants($ownerUuid, $limit, $isSuperUser);
            case 'vowel':
                return self::getVowels($ownerUuid, $limit, $isSuperUser);
            case 'cv_blend':
                return self::getCVBlends($ownerUuid, $limit, $isSuperUser);
            case '3cv_blend':
                return self::get3CVBlends($ownerUuid, $limit, $isSuperUser);
            case 'word':
                return self::getWords($ownerUuid, $limit, $isSuperUser);
            default:
                return [];
        }
    }
    private static function getConsonants(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT c.consonant_id, c.consonant_code as code, c.consonant_label as label,
                   c.image_filename, c.image_path, c.display_order, c.owner_user_uuid,
                   COALESCE(GROUP_CONCAT(a.group_name ORDER BY a.assignment_id SEPARATOR '|||'), '') AS groups
            FROM exercise_consonants c
            LEFT JOIN exercise_card_group_assignments a ON a.parent_card_type = 'consonant' AND a.card_type = 'consonant' AND a.card_id = c.consonant_id
            WHERE c.is_active = 1
              AND (c.owner_user_uuid IS NULL OR c.owner_user_uuid = ?)
            GROUP BY c.consonant_id
            ORDER BY c.display_order ASC, c.consonant_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->bindValue(1, $ownerUuid, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getVowels(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT v.vowel_id, v.vowel_code as code, v.vowel_label as label, v.vowel_type,
                   v.image_filename, v.image_path, v.display_order, v.owner_user_uuid,
                   COALESCE(GROUP_CONCAT(a.group_name ORDER BY a.assignment_id SEPARATOR '|||'), '') AS groups
            FROM exercise_vowels v
            LEFT JOIN exercise_card_group_assignments a ON a.parent_card_type = 'vowel' AND a.card_type = 'vowel' AND a.card_id = v.vowel_id
            WHERE v.is_active = 1
              AND (v.owner_user_uuid IS NULL OR v.owner_user_uuid = ?)
            GROUP BY v.vowel_id
            ORDER BY v.display_order ASC, v.vowel_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->bindValue(1, $ownerUuid, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getCVBlends(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT
                cv.cv_id,
                cv.cv_code,
                cv.cv_type,
                cv.icon_filename,
                cv.icon_path,
                cv.display_order,
                cv.owner_user_uuid,
                COALESCE(GROUP_CONCAT(a.group_name ORDER BY a.assignment_id SEPARATOR '|||'), '') AS groups
            FROM exercise_cv_blends cv
            LEFT JOIN exercise_card_group_assignments a ON a.parent_card_type = 'cv_blend' AND a.card_type = 'cv_blend' AND a.card_id = cv.cv_id
            WHERE cv.is_active = 1
              AND (cv.owner_user_uuid IS NULL OR cv.owner_user_uuid = ?)
            GROUP BY cv.cv_id
            ORDER BY cv.display_order ASC, cv.cv_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->bindValue(1, $ownerUuid, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function get3CVBlends(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT 
                b.blend_3cv_id,
                b.blend_code,
                b.icon_filename,
                b.icon_path,
                b.display_order,
                b.owner_user_uuid,
                c.consonant_code,
                v.vowel_code
            FROM exercise_3cv_blends b
            INNER JOIN exercise_consonants c ON b.consonant_id = c.consonant_id
            INNER JOIN exercise_vowels v ON b.vowel_id = v.vowel_id
            WHERE b.is_active = 1 
              AND (b.owner_user_uuid IS NULL OR b.owner_user_uuid = ?)
            ORDER BY b.display_order ASC, b.blend_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->bindValue(1, $ownerUuid, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getWords(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT w.word_id, w.word_text, w.word_category, w.syllable_count, w.syllable_breakdown,
                   w.image_filename, w.image_path, w.display_order, w.owner_user_uuid,
                   COALESCE(GROUP_CONCAT(a.group_name ORDER BY a.assignment_id SEPARATOR '|||'), '') AS groups
            FROM exercise_words w
            LEFT JOIN exercise_card_group_assignments a ON a.card_id = w.word_id
              AND a.card_type = IF(w.syllable_breakdown IS NOT NULL, '3cv_blend', 'word')
              AND a.parent_card_type = IF(w.syllable_breakdown IS NOT NULL, '3cv_blend', 'word')
            WHERE w.is_active = 1
              AND (w.owner_user_uuid IS NULL OR w.owner_user_uuid = ?)
            GROUP BY w.word_id
            ORDER BY w.display_order ASC, w.word_text ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->bindValue(1, $ownerUuid, PDO::PARAM_STR);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get content statistics for a user
     */
    /**
     * Get content statistics for a user
     */
    public static function getContentStats(string $userUuid): array {
        global $pdo;

        $stats = [
            'consonant' => 0,
            'vowel' => 0,
            'cv_blend' => 0,
            '3cv_blend' => 0,
            'word' => 0,
            'total' => 0
        ];

        // Count user's custom content
        $tables = [
            'consonant' => 'exercise_consonants',
            'vowel' => 'exercise_vowels',
            'cv_blend' => 'exercise_cv_blends',
            '3cv_blend' => 'exercise_3cv_blends',
            'word' => 'exercise_words'
        ];

        foreach ($tables as $type => $table) {
            $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE owner_user_uuid = ? AND is_active = 1
        ");
            $stmt->execute([$userUuid]);
            $stats[$type] = (int)$stmt->fetchColumn();
        }

        $stats['total'] = array_sum(array_filter($stats, 'is_int'));

        // Get tier limit
        $stmt = $pdo->prepare("
        SELECT pt.limit_sounds_words, pt.name as tier_name
        FROM mka_users u
        LEFT JOIN user_subscriptions us ON u.UserUUID = us.user_uuid 
            AND us.status IN ('trial', 'active')
        LEFT JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
        WHERE u.UserUUID = ?
        LIMIT 1
    ");
        $stmt->execute([$userUuid]);
        $tier = $stmt->fetch(PDO::FETCH_ASSOC);

        $stats['limit'] = $tier['limit_sounds_words'] ?? 0;
        $stats['tier_name'] = $tier['tier_name'] ?? 'Unknown';
        $stats['remaining'] = max(0, $stats['limit'] - $stats['total']);
        $stats['percent_used'] = ($stats['limit'] > 0)
            ? round(($stats['total'] / $stats['limit']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Create new vowel
     */
    public static function createVowel(array $data, string $createdBy): array {
        if (!self::canCreateContent($createdBy)) {
            return ['status' => 'fail', 'message' => 'You have reached your tier limit'];
        }

        global $pdo;

        $code = strtoupper(trim($data['code'] ?? ''));
        $label = trim($data['label'] ?? $code);
        $type = $data['vowel_type'] ?? 'short';

        if (empty($code) || strlen($code) > 10) {
            return ['status' => 'fail', 'message' => 'Invalid vowel code'];
        }

        if (!in_array($type, ['short', 'long', 'special'])) {
            return ['status' => 'fail', 'message' => 'Invalid vowel type'];
        }

        try {
            $stmt = $pdo->prepare("
            INSERT INTO exercise_vowels 
            (owner_user_uuid, vowel_code, vowel_type, vowel_label, display_order, is_active)
            VALUES (?, ?, ?, ?, 999, 1)
        ");
            $stmt->execute([$createdBy, $code, $type, $label]);

            MKALogger::log('vowel_created', [
                'created_by' => $createdBy,
                'code' => $code,
                'type' => $type
            ]);

            return ['status' => 'success', 'message' => 'Vowel created successfully'];

        } catch (\PDOException $e) {
            error_log("Vowel creation error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Create new consonant
     */
    public static function createConsonant(array $data, string $createdBy): array {
        if (!self::canCreateContent($createdBy)) {
            return ['status' => 'fail', 'message' => 'You have reached your tier limit'];
        }

        global $pdo;

        $code = strtoupper(trim($data['code'] ?? ''));
        $label = trim($data['label'] ?? $code);

        if (empty($code) || strlen($code) > 10) {
            return ['status' => 'fail', 'message' => 'Invalid consonant code'];
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO exercise_consonants 
                (owner_user_uuid, consonant_code, consonant_label, display_order, is_active)
                VALUES (?, ?, ?, 999, 1)
            ");
            $stmt->execute([$createdBy, $code, $label]);

            MKALogger::log('consonant_created', [
                'created_by' => $createdBy,
                'code' => $code
            ]);

            return ['status' => 'success', 'message' => 'Consonant created successfully'];

        } catch (\PDOException $e) {
            error_log("Consonant creation error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Create new word
     */
    public static function createWord(array $data, string $createdBy): array {
        if (!self::canCreateContent($createdBy)) {
            return ['status' => 'fail', 'message' => 'You have reached your tier limit'];
        }

        global $pdo;

        $word = strtolower(trim($data['word'] ?? ''));
        $category = trim($data['category'] ?? '');
        $syllableCount = (int)($data['syllable_count'] ?? 2);
        $breakdown = trim($data['breakdown'] ?? '');

        if (empty($word) || strlen($word) > 100) {
            return ['status' => 'fail', 'message' => 'Invalid word'];
        }

        // Auto-generate breakdown if not provided
        if (empty($breakdown) && strlen($word) > 2) {
            $left = substr($word, 0, 2);
            $right = substr($word, 2);
            $breakdown = "$left-$right";
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO exercise_words 
                (owner_user_uuid, word_text, word_category, syllable_count, 
                 syllable_breakdown, display_order, is_active)
                VALUES (?, ?, ?, ?, ?, 999, 1)
            ");
            $stmt->execute([$createdBy, $word, $category, $syllableCount, $breakdown]);

            MKALogger::log('word_created', [
                'created_by' => $createdBy,
                'word' => $word
            ]);

            return ['status' => 'success', 'message' => 'Word created successfully'];

        } catch (\PDOException $e) {
            error_log("Word creation error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Create new CV blend
     */
    public static function createCVBlend(array $data, string $createdBy): array {
        if (!self::canCreateContent($createdBy)) {
            return ['status' => 'fail', 'message' => 'You have reached your tier limit'];
        }

        global $pdo;

        $consonantId = (int)($data['consonant_id'] ?? 0);
        $vowelId = (int)($data['vowel_id'] ?? 0);

        if ($consonantId <= 0 || $vowelId <= 0) {
            return ['status' => 'fail', 'message' => 'Both consonant and vowel are required'];
        }

        try {
            // Get consonant and vowel codes to build cv_code
            $stmt = $pdo->prepare("SELECT consonant_code FROM exercise_consonants WHERE consonant_id = ?");
            $stmt->execute([$consonantId]);
            $consonant = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT vowel_code FROM exercise_vowels WHERE vowel_id = ?");
            $stmt->execute([$vowelId]);
            $vowel = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$consonant || !$vowel) {
                return ['status' => 'fail', 'message' => 'Invalid consonant or vowel'];
            }

            $cvCode = $consonant['consonant_code'] . '-' . $vowel['vowel_code'];

            // Check if this combination already exists for this user
            $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM exercise_cv_blends 
            WHERE owner_user_uuid = ? AND consonant_id = ? AND vowel_id = ?
        ");
            $stmt->execute([$createdBy, $consonantId, $vowelId]);

            if ($stmt->fetchColumn() > 0) {
                return ['status' => 'fail', 'message' => 'This CV blend already exists'];
            }

            $stmt = $pdo->prepare("
            INSERT INTO exercise_cv_blends 
            (owner_user_uuid, consonant_id, vowel_id, cv_code, display_order, is_active)
            VALUES (?, ?, ?, ?, 999, 1)
        ");
            $stmt->execute([$createdBy, $consonantId, $vowelId, $cvCode]);

            MKALogger::log('cv_blend_created', [
                'created_by' => $createdBy,
                'cv_code' => $cvCode
            ]);

            return ['status' => 'success', 'message' => 'CV blend created successfully'];

        } catch (\PDOException $e) {
            error_log("CV blend creation error: " . $e->getMessage());

            if ($e->getCode() == 23000) { // Duplicate entry
                return ['status' => 'fail', 'message' => 'This CV blend already exists'];
            }

            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Create new 3CV blend
     */
    public static function create3CVBlend(array $data, string $createdBy): array {
        if (!self::canCreateContent($createdBy)) {
            return ['status' => 'fail', 'message' => 'You have reached your tier limit'];
        }

        global $pdo;

        $consonantId = (int)($data['consonant_id'] ?? 0);
        $vowelId = (int)($data['vowel_id'] ?? 0);

        if ($consonantId <= 0 || $vowelId <= 0) {
            return ['status' => 'fail', 'message' => 'Both consonant and vowel are required'];
        }

        try {
            // Get consonant and vowel codes to build blend_code
            $stmt = $pdo->prepare("SELECT consonant_code FROM exercise_consonants WHERE consonant_id = ?");
            $stmt->execute([$consonantId]);
            $consonant = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT vowel_code FROM exercise_vowels WHERE vowel_id = ?");
            $stmt->execute([$vowelId]);
            $vowel = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$consonant || !$vowel) {
                return ['status' => 'fail', 'message' => 'Invalid consonant or vowel'];
            }

            $blendCode = $consonant['consonant_code'] . '-' . $vowel['vowel_code'];

            // Check if this combination already exists for this user
            $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM exercise_3cv_blends 
            WHERE owner_user_uuid = ? AND consonant_id = ? AND vowel_id = ?
        ");
            $stmt->execute([$createdBy, $consonantId, $vowelId]);

            if ($stmt->fetchColumn() > 0) {
                return ['status' => 'fail', 'message' => 'This 3CV blend already exists'];
            }

            $stmt = $pdo->prepare("
            INSERT INTO exercise_3cv_blends 
            (owner_user_uuid, consonant_id, vowel_id, blend_code, display_order, is_active)
            VALUES (?, ?, ?, ?, 999, 1)
        ");
            $stmt->execute([$createdBy, $consonantId, $vowelId, $blendCode]);

            MKALogger::log('3cv_blend_created', [
                'created_by' => $createdBy,
                'blend_code' => $blendCode
            ]);

            return ['status' => 'success', 'message' => '3CV blend created successfully'];

        } catch (\PDOException $e) {
            error_log("3CV blend creation error: " . $e->getMessage());

            if ($e->getCode() == 23000) { // Duplicate entry
                return ['status' => 'fail', 'message' => 'This 3CV blend already exists'];
            }

            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Check if user can create more content
     */
    private static function canCreateContent(string $userUuid): bool {
        $stats = self::getContentStats($userUuid);

        // Super users have no limit
        if ($stats['limit'] === 0) {
            return true;
        }

        return $stats['total'] < $stats['limit'];
    }
}