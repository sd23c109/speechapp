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

        // Get user info and tier
        $stmt = $pdo->prepare("
            SELECT u.user_type, u.parent_user_uuid, pt.name as tier_name, pt.limit_sounds_words
            FROM mka_users u
            LEFT JOIN user_subscriptions us ON u.UserUUID = us.user_uuid 
                AND us.status IN ('trial', 'active')
            LEFT JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
            WHERE u.UserUUID = ?
            ORDER BY us.started_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [];
        }

        $limit = $user['limit_sounds_words'] ?? 0;
        $isSuperUser = ($user['user_type'] === 'super_user');
        $isEnterpriseAdmin = ($user['user_type'] === 'enterprise_admin');
        $isEndUser = ($user['user_type'] === 'end_user');

        // Determine which owner_user_uuid to query
        $ownerUuid = null;
        if ($isSuperUser || $isEnterpriseAdmin) {
            $ownerUuid = $userUuid;
        } elseif ($isEndUser && $user['parent_user_uuid']) {
            $ownerUuid = $user['parent_user_uuid'];
        }

        // Query based on content type
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
            SELECT consonant_id, consonant_code as code, consonant_label as label, 
                   image_filename, image_path, display_order, owner_user_uuid
            FROM exercise_consonants
            WHERE is_active = 1 
              AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
            ORDER BY display_order ASC, consonant_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->execute([$ownerUuid, $limit]);
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getVowels(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT vowel_id, vowel_code as code, vowel_label as label, vowel_type,
                   image_filename, image_path, display_order, owner_user_uuid
            FROM exercise_vowels
            WHERE is_active = 1 
              AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
            ORDER BY display_order ASC, vowel_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->execute([$ownerUuid, $limit]);
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
                cv.icon_filename,
                cv.icon_path,
                cv.display_order,
                cv.owner_user_uuid,
                c.consonant_code,
                v.vowel_code
            FROM exercise_cv_blends cv
            INNER JOIN exercise_consonants c ON cv.consonant_id = c.consonant_id
            INNER JOIN exercise_vowels v ON cv.vowel_id = v.vowel_id
            WHERE cv.is_active = 1 
              AND (cv.owner_user_uuid IS NULL OR cv.owner_user_uuid = ?)
            ORDER BY cv.display_order ASC, cv.cv_code ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->execute([$ownerUuid, $limit]);
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
            $stmt->execute([$ownerUuid, $limit]);
        } else {
            $stmt->execute([$ownerUuid]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getWords(?string $ownerUuid, int $limit, bool $isSuperUser): array {
        global $pdo;

        $sql = "
            SELECT word_id, word_text, word_category, syllable_count, syllable_breakdown,
                   image_filename, image_path, display_order, owner_user_uuid
            FROM exercise_words
            WHERE is_active = 1 
              AND (owner_user_uuid IS NULL OR owner_user_uuid = ?)
            ORDER BY display_order ASC, word_text ASC
        ";

        if (!$isSuperUser && $limit > 0) {
            $sql .= " LIMIT ?";
        }

        $stmt = $pdo->prepare($sql);

        if (!$isSuperUser && $limit > 0) {
            $stmt->execute([$ownerUuid, $limit]);
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