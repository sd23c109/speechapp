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
            return ['status' => 'fail', 'message' => 'Please confirm your email before logging in.'];
        }

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            return ['success' => false, 'message' => 'Invalid login credentials.'];
        }

        // Check for expired trial
        if ($user['IsTrial'] === 'y' && !empty($user['TrialExpires'])) {
            $now = new \DateTime();
            $trialExpires = new \DateTime($user['TrialExpires']);

            if ($now > $trialExpires) {
                return ['success' => false, 'message' => 'Trial expired. Please upgrade your account.'];
            }
        }

        // Check for unpaid account
        if ($user['IsPaid'] === 'n' && $user['IsTrial'] === 'n') {
            return ['success' => false, 'message' => 'Payment required. Please subscribe to continue.'];
        }

        // ... after password_verify + email_confirmed + trial checks, before setting session:

// Ensure the user has an active account membership (trials included)
        // Ensure the user has an account membership (including trial users)
        $GLOBALS['pdo']->beginTransaction();

        try {
            // Does this user already belong to an active account?
            $stmt = $GLOBALS['pdo']->prepare("
        SELECT mua.account_uuid
        FROM mka_user_accounts AS mua
        WHERE mua.user_uuid = :u
          AND mua.status = 'active'
        ORDER BY FIELD(mua.role,'OWNER','ADMIN','STAFF','CONTRACTOR','PATIENT') ASC
        LIMIT 1
    ");
            $stmt->execute([':u' => $user['UserUUID']]);
            $accountUuid = $stmt->fetchColumn();

            if (!$accountUuid) {
                // Need to create a new account and attach user as OWNER
                $accountUuid = self::uuidV4();

                // Use company_name + company_slug from mka_users (these are REQUIRED columns)
                $accountName = $user['company_name'];
                $slug = $user['company_slug'];

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

                // Add user to that account
                $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO mka_user_accounts
                (user_uuid, account_uuid, role, status, created_at)
            VALUES
                (:u, :a, 'OWNER', 'active', NOW())
        ");
                $stmt->execute([':u' => $user['UserUUID'], ':a' => $accountUuid]);
            }

            $GLOBALS['pdo']->commit();

        } catch (\Throwable $e) {
            $GLOBALS['pdo']->rollBack();
            return ['success' => false, 'message' => 'Login provisioning error.'];
        }

        // Store session


        $_SESSION['user_data'] = [
            'user_uuid'     => $user['UserUUID'],
            'email'         => $user['Email'],
            'is_trial'      => $user['IsTrial'],
            'trial_expires' => $user['TrialExpires'],
            'is_paid'       => $user['IsPaid'],
            'company_name'  => $user['company_name'],
            'company_slug'  => $user['company_slug'],
            'account_uuid'  => $accountUuid,
        ];


        return ['success' => true];
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
