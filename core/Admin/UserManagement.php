<?php
namespace MKA\Admin;
require_once '/opt/mka/core/Log/MKALogger.php';
use PDO;
use MKA\Log\MKALogger;

/**
 * UserManagement
 *
 * Handles user creation, updates, and management for:
 * - Super-user creating enterprise admins
 * - Enterprise admins creating end-users (lite/standard/pro)
 */
class UserManagement {

    /**
     * Create a new user
     *
     * @param array $data User data
     * @param string $createdBy UUID of user creating this account
     * @return array ['status' => 'success'|'fail', 'message' => string, 'user_uuid' => string]
     */
    public static function createUser(array $data, string $createdBy): array {
        global $pdo;

        // Validate creator permissions
        $creator = self::getUser($createdBy);
        if (!$creator) {
            return ['status' => 'fail', 'message' => 'Creator not found'];
        }

        // Extract data
        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';
        $userType = $data['user_type'] ?? 'end_user';
        $tier = $data['tier'] ?? null; // For end_users: 'lite', 'standard', 'pro'

        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'fail', 'message' => 'Invalid email address'];
        }

        if (empty($name) || strlen($name) > 100) {
            return ['status' => 'fail', 'message' => 'Name is required and must be less than 100 characters'];
        }

        if (empty($password) || strlen($password) < 8) {
            return ['status' => 'fail', 'message' => 'Password must be at least 8 characters'];
        }

        // Permission check
        if (!self::canCreateUserType($creator['user_type'], $userType)) {
            return ['status' => 'fail', 'message' => 'You do not have permission to create this user type'];
        }

        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mka_users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            return ['status' => 'fail', 'message' => 'Email already registered'];
        }

        // Generate UUID and slug
        $uuid = self::generateUUID();
        $slug = self::generateSlug($name);
        $companyName = $name;  // Default to user's name

        // Ensure slug uniqueness
        $slug = self::ensureUniqueSlug($slug, $uuid);

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO mka_users (
                    UserUUID, Email, PasswordHash, Name, company_name, company_slug,
                    user_type, parent_user_uuid, Status, IsPaid, email_confirmed, CreatedAt
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $isTrial = ($userType === 'end_user') ? 'y' : 'n';
            $isPaid = 'n';  // Will be updated when payment is received
            $emailConfirmed = 'n';  // Will need to confirm email

            $stmt->execute([
                $uuid, $email, $passwordHash, $name, $companyName, $slug,
                $userType, $createdBy, $isTrial, $isPaid, $emailConfirmed
            ]);

            // Create API key
            $apiKey = self::generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO mka_api_keys (api_key, user_uuid, status, expires_at)
                VALUES (?, ?, 'active', ?)
            ");
            $expiresAt = ($userType === 'end_user') ? date('Y-m-d H:i:s', strtotime('+14 days')) : null;
            $stmt->execute([$apiKey, $uuid, $expiresAt]);

            // Create account
            $accountUuid = self::generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO mka_accounts (account_uuid, name, slug, owner_user_uuid, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$accountUuid, $companyName, $slug, $uuid]);

            // Add user to account with appropriate role
            $role = self::getUserRole($userType);
            $stmt = $pdo->prepare("
                INSERT INTO mka_user_accounts (user_uuid, account_uuid, role, status, created_at)
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$uuid, $accountUuid, $role]);

            // If end_user, create subscription record
            if ($userType === 'end_user' && $createdBy) {
                // The tier parameter is ignored - end users inherit from parent admin
                self::createSubscriptionForEndUser($uuid, $tier, $createdBy);
            }

            $pdo->commit();

            // Log creation
            MKALogger::log('user_created', [
                'created_by' => $createdBy,
                'new_user_uuid' => $uuid,
                'user_type' => $userType,
                'tier' => $tier
            ]);

            // Send welcome email (TODO: implement)
            // self::sendWelcomeEmail($email, $name, $uuid);

            return [
                'status' => 'success',
                'message' => 'User created successfully',
                'user_uuid' => $uuid
            ];

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("User creation error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Create subscription for end user
     * End users inherit their parent enterprise admin's tier
     */
    private static function createSubscriptionForEndUser(string $userUuid, ?string $tierName, string $adminUuid): void {
        global $pdo;

        // IMPORTANT: End users must inherit their parent admin's tier
        // The $tierName parameter is ignored - we get the admin's actual tier instead

        // Get the enterprise admin's current subscription/tier
        $stmt = $pdo->prepare("
        SELECT pt.tier_uuid, pt.name
        FROM user_subscriptions us
        JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
        WHERE us.user_uuid = ? 
          AND us.status IN ('trial', 'active')
        ORDER BY us.started_at DESC
        LIMIT 1
    ");
        $stmt->execute([$adminUuid]);
        $adminTier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$adminTier) {
            // Creator is a super_user (no subscription row) — give patient enterprise access
            $stmt = $pdo->prepare("SELECT tier_uuid, name FROM product_tiers WHERE name = 'enterprise' LIMIT 1");
            $stmt->execute();
            $adminTier = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adminTier) {
                throw new \Exception("No enterprise tier found — cannot create subscription");
            }
        }

        // Use the admin's tier, not the requested tier
        $tierUuid = $adminTier['tier_uuid'];

        // Create subscription for end-user with inherited tier
        $subscriptionUuid = self::generateUUID();
        $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions (
            subscription_uuid, user_uuid, tier_uuid, status, started_at, expires_at
        ) VALUES (?, ?, ?, 'trial', NOW(), ?)
    ");
        $expiresAt = date('Y-m-d H:i:s', strtotime('+14 days'));
        $stmt->execute([$subscriptionUuid, $userUuid, $tierUuid, $expiresAt]);

        // Log for debugging
        MKALogger::log('end_user_subscription_created', [
            'user_uuid' => $userUuid,
            'parent_admin_uuid' => $adminUuid,
            'inherited_tier' => $adminTier['name'],
            'tier_uuid' => $tierUuid
        ]);
    }

    /**
     * Get user by UUID
     */
    private static function getUser(string $uuid): ?array {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$uuid]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Check if creator can create this user type
     */
    private static function canCreateUserType(string $creatorType, string $targetType): bool {
        $permissions = [
            'super_user' => ['enterprise_admin', 'end_user'],
            'enterprise_admin' => ['end_user'],
            'end_user' => []
        ];

        return in_array($targetType, $permissions[$creatorType] ?? []);
    }

    /**
     * Get role based on user type
     */
    private static function getUserRole(string $userType): string {
        return match($userType) {
            'super_user' => 'SUPER_USER',
            'enterprise_admin' => 'OWNER',
            'end_user' => 'PATIENT',
            default => 'PATIENT'
        };
    }


    /**
     * List users affiliated with a specific admin.
     * Matches on EITHER parent_user_uuid (direct creation path) OR patient_affiliations
     * (invite acceptance path), so both flows show up in Manage Users.
     */
    public static function listUsersByCreator(string $creatorUuid, array $filters = []): array {
        global $pdo;

        // Determine the caller's role
        $roleStmt = $pdo->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
        $roleStmt->execute([$creatorUuid]);
        $callerType = $roleStmt->fetchColumn();

        $providerExpr = "
            CASE
                WHEN u.user_type = 'end_user' THEN
                    COALESCE(
                        (SELECT m.Name FROM patient_affiliations a
                         JOIN mka_users m ON m.UserUUID = a.slp_uuid
                         WHERE a.patient_uuid = u.UserUUID AND a.status = 'active'
                         LIMIT 1),
                        (SELECT Name FROM mka_users WHERE user_type = 'super_user' LIMIT 1)
                    )
                ELSE NULL
            END AS provider_name
        ";

        if ($callerType === 'super_user') {
            // Super users see every account except other super users
            $sql = "
                SELECT DISTINCT
                    u.UserUUID, u.Email, u.Name, u.user_type, u.Status, u.IsPaid,
                    u.email_confirmed, u.CreatedAt,
                    us.status as subscription_status,
                    {$providerExpr}
                FROM mka_users u
                LEFT JOIN user_subscriptions us
                       ON us.user_uuid = u.UserUUID
                      AND us.status IN ('trial', 'active')
                WHERE u.user_type != 'super_user'
            ";
            $params = [];
        } else {
            $sql = "
                SELECT DISTINCT
                    u.UserUUID, u.Email, u.Name, u.user_type, u.Status, u.IsPaid,
                    u.email_confirmed, u.CreatedAt,
                    us.status as subscription_status,
                    {$providerExpr}
                FROM mka_users u
                LEFT JOIN patient_affiliations pa
                       ON pa.patient_uuid = u.UserUUID
                      AND pa.slp_uuid = ?
                      AND pa.status = 'active'
                LEFT JOIN user_subscriptions us
                       ON us.user_uuid = u.UserUUID
                      AND us.status IN ('trial', 'active')
                WHERE (
                    u.parent_user_uuid = ?
                    OR pa.patient_uuid IS NOT NULL
                )
            ";
            // creatorUuid passed twice: once for the pa JOIN, once for the WHERE
            $params = [$creatorUuid, $creatorUuid];
        }

        if (!empty($filters['user_type'])) {
            $sql .= " AND u.user_type = ?";
            $params[] = $filters['user_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND us.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY u.CreatedAt DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update user
     */
    public static function updateUser(string $userUuid, array $data, string $updatedBy): array {
        global $pdo;

        // Get existing user
        $user = self::getUser($userUuid);
        if (!$user) {
            return ['status' => 'fail', 'message' => 'User not found'];
        }

        // Permission check
        $updater = self::getUser($updatedBy);
        if (!self::canUpdateUser($updater, $user)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        $updates = [];
        $params = [];

        // Name
        if (isset($data['name']) && !empty($data['name'])) {
            $updates[] = "Name = ?";
            $params[] = trim($data['name']);
        }

        // Email (check uniqueness)
        if (isset($data['email']) && $data['email'] !== $user['Email']) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['status' => 'fail', 'message' => 'Invalid email'];
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mka_users WHERE Email = ? AND UserUUID != ?");
            $stmt->execute([$data['email'], $userUuid]);
            if ($stmt->fetchColumn() > 0) {
                return ['status' => 'fail', 'message' => 'Email already in use'];
            }

            $updates[] = "Email = ?";
            $params[] = trim($data['email']);
        }

        // Password
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['status' => 'fail', 'message' => 'Password must be at least 8 characters'];
            }
            $updates[] = "PasswordHash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($updates)) {
            return ['status' => 'success', 'message' => 'No changes made'];
        }

        try {
            $params[] = $userUuid;
            $sql = "UPDATE mka_users SET " . implode(", ", $updates) . " WHERE UserUUID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            MKALogger::log('user_updated', [
                'updated_by' => $updatedBy,
                'user_uuid' => $userUuid,
                'fields' => array_keys($data)
            ]);

            return ['status' => 'success', 'message' => 'User updated successfully'];

        } catch (\PDOException $e) {
            error_log("User update error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Check if updater can update target user
     */
    private static function canUpdateUser(array $updater, array $target): bool {
        // Super user can update anyone
        if ($updater['user_type'] === 'super_user') {
            return true;
        }

        // Enterprise admin can update their own end users
        if ($updater['user_type'] === 'enterprise_admin'
            && $target['parent_user_uuid'] === $updater['UserUUID']) {
            return true;
        }

        // Users can update themselves
        if ($updater['UserUUID'] === $target['UserUUID']) {
            return true;
        }

        return false;
    }

    /**
     * Delete/deactivate user
     */
    public static function deleteUser(string $userUuid, string $deletedBy): array {
        global $pdo;

        $user = self::getUser($userUuid);
        if (!$user) {
            return ['status' => 'fail', 'message' => 'User not found'];
        }

        $deleter = self::getUser($deletedBy);
        if (!self::canUpdateUser($deleter, $user)) {
            return ['status' => 'fail', 'message' => 'Permission denied'];
        }

        try {
            // Soft delete - deactivate instead of actual deletion
            $stmt = $pdo->prepare("
                UPDATE mka_user_accounts 
                SET status = 'inactive' 
                WHERE user_uuid = ?
            ");
            $stmt->execute([$userUuid]);

            $stmt = $pdo->prepare("
                UPDATE mka_api_keys 
                SET status = 'inactive' 
                WHERE user_uuid = ?
            ");
            $stmt->execute([$userUuid]);

            MKALogger::log('user_deleted', [
                'deleted_by' => $deletedBy,
                'user_uuid' => $userUuid
            ]);

            return ['status' => 'success', 'message' => 'User deactivated successfully'];

        } catch (\PDOException $e) {
            error_log("User delete error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Generate UUID v4
     */
    private static function generateUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate slug from name
     */
    private static function generateSlug(string $name): string {
        $slug = preg_replace('/\s+/', '-', $name);
        $slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $slug);
        return strtolower($slug);
    }

    /**
     * Ensure slug uniqueness
     */
    private static function ensureUniqueSlug(string $baseSlug, string $excludeUuid = null): string {
        global $pdo;

        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM mka_users 
                WHERE company_slug = ? AND UserUUID != ?
            ");
            $stmt->execute([$slug, $excludeUuid ?? '']);

            if ($stmt->fetchColumn() == 0) {
                break;
            }

            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
