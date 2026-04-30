<?php
namespace MKA\Auth;

use PDO;

class LoginHandler
{
    public static function handle(array $post)
    {
        require_once '../../bootstrap.php';

        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';

        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Missing email or password.'];
        }

        $stmt = $GLOBALS['pdo']->prepare("
            SELECT *
            FROM mka_users
            WHERE Email = :email
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['email_confirmed'] !== 'y') {
            return ['success' => false, 'message' => 'Please confirm your email before logging in.'];
        }

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            return ['success' => false, 'message' => 'Invalid login credentials.'];
        }

        // Check for expired trial (but NOT for super_user or paid users)
        if ($user['user_type'] !== 'super_user' && $user['IsPaid'] !== 'y' && !empty($user['TrialExpires'])) {
            $now = new \DateTime();
            $trialExpires = new \DateTime($user['TrialExpires']);

            if ($now > $trialExpires) {
                return ['success' => false, 'trial_expired' => true, 'user_uuid' => $user['UserUUID']];
            }
        }

        // Check for unpaid account (but NOT for super_user)
        if ($user['user_type'] !== 'super_user' && $user['IsPaid'] === 'n' && $user['Status'] === 'active') {
            return ['success' => false, 'message' => 'Payment required. Please subscribe to continue.'];
        }

        // Ensure the user has an active account membership
        $GLOBALS['pdo']->beginTransaction();

        try {
            // Does this user already belong to an active account?
            $stmt = $GLOBALS['pdo']->prepare("
                SELECT mua.account_uuid
                FROM mka_user_accounts AS mua
                WHERE mua.user_uuid = :u
                  AND mua.status = 'active'
                ORDER BY FIELD(mua.role,'SUPER_USER','OWNER','ADMIN','STAFF','CONTRACTOR','PATIENT') ASC
                LIMIT 1
            ");
            $stmt->execute([':u' => $user['UserUUID']]);
            $accountUuid = $stmt->fetchColumn();

            if (!$accountUuid) {
                // Need to create a new account and attach user with appropriate role

                // Use company_name + company_slug from mka_users
                $accountName = $user['company_name'];
                $slug = $user['company_slug'];

                // Check if account with this slug already exists
                $stmt = $GLOBALS['pdo']->prepare("
        SELECT account_uuid FROM mka_accounts WHERE slug = ?
    ");
                $stmt->execute([$slug]);
                $existingAccountUuid = $stmt->fetchColumn();

                if ($existingAccountUuid) {
                    // Account exists, just add user to it
                    $accountUuid = $existingAccountUuid;
                } else {
                    // Create new account
                    $accountUuid = self::uuidV4();

                    // Determine role based on user_type
                    $role = self::getRoleForUserType($user['user_type']);

                    // Create account
                    $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO mka_accounts
                (account_uuid, name, slug, owner_user_uuid, created_at)
            VALUES
                (:uuid, :name, :slug, :owner, NOW())
        ");
                    $stmt->execute([
                        ':uuid'  => $accountUuid,
                        ':name'  => $accountName,
                        ':slug'  => $slug,
                        ':owner' => $user['UserUUID']
                    ]);
                }

                // Determine role based on user_type
                $role = self::getRoleForUserType($user['user_type']);

                // Add user to that account with appropriate role (if not already added)
                $stmt = $GLOBALS['pdo']->prepare("
        SELECT COUNT(*) FROM mka_user_accounts 
        WHERE user_uuid = :u AND account_uuid = :a
    ");
                $stmt->execute([':u' => $user['UserUUID'], ':a' => $accountUuid]);

                if ($stmt->fetchColumn() == 0) {
                    $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO mka_user_accounts
                (user_uuid, account_uuid, role, status, created_at)
            VALUES
                (:u, :a, :role, 'active', NOW())
        ");
                    $stmt->execute([
                        ':u' => $user['UserUUID'],
                        ':a' => $accountUuid,
                        ':role' => $role
                    ]);
                }
            }

            $GLOBALS['pdo']->commit();

        } catch (\Throwable $e) {
            $GLOBALS['pdo']->rollBack();
            error_log("Login provisioning error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login provisioning error: ' . $e->getMessage()];
        }

        // Store session
        $_SESSION['user_data'] = [
            'user_uuid'     => $user['UserUUID'],
            'email'         => $user['Email'],
            'user_type'     => $user['user_type'],  // ADDED: Store user type
            'is_trial'      => $user['Status'],
            'trial_expires' => $user['TrialExpires'],
            'is_paid'       => $user['IsPaid'],
            'company_name'  => $user['company_name'],
            'company_slug'  => $user['company_slug'],
            'account_uuid'  => $accountUuid,
        ];

        return ['success' => true];
    }

    /**
     * Get role based on user type
     */
    private static function getRoleForUserType(string $userType): string {
        return match($userType) {
            'super_user' => 'SUPER_USER',
            'enterprise_admin' => 'OWNER',
            'end_user' => 'PATIENT',
            default => 'PATIENT'
        };
    }

    private static function uuidV4(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}