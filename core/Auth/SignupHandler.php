<?php
namespace MKA\Auth;
require_once '/opt/mka/core/Email/BrevoMailer.php';
require_once '/opt/mka/core/Log/MKALogger.php';

use MKA\Email\BrevoMailer;
use MKA\Log\MKALogger;

class SignupHandler {

    public static function handle($data) {
        global $pdo;

        error_log(json_encode($data));

        $email = trim($data['mka_email']);
        $pass = $data['mka_password'];
        $confirm = $data['mka_password_confirm'];
        $userType = $data['user_type'] ?? 'end_user'; // NEW
        $startMode = $data['start_mode'] ?? 'trial';

        $confirmationToken = bin2hex(random_bytes(32));

        $uuid = self::generateUUID();
        $slug = 'SLUG_'.$uuid;
        $company = 'COMPANY_'.$uuid;
        $domain = 'DOMAIN_'.$uuid;
        $name = 'NAME_'.$uuid;

        // Honeypot check
        if (!empty($_POST['website'])) {
            return ['status' => 'error', 'message' => 'Invalid submission.'];
        }

        // Time check
        $renderedAt = isset($_POST['rendered_at']) ? (int) $_POST['rendered_at'] : 0;
        $delta = time() - $renderedAt;
        if ($renderedAt <= 0 || $delta < 3 || $delta > 3600) {
            return ['status' => 'error', 'message' => 'Please try again.'];
        }

        // Recaptcha
        $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
        if (!$recaptchaToken) {
            return ['status' => 'error', 'message' => 'Captcha missing.'];
        }

        $secret = '6LcZUQksAAAAAI7LcyecfY96531br8Nj3R_H-PD5';
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secret,
                'response' => $recaptchaToken,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $captchaData = json_decode($resp, true);
        if (!$captchaData || empty($captchaData['success'])) {
            return ['status' => 'error', 'message' => 'Captcha failed.'];
        }

        // Validation
        if ($pass !== $confirm) {
            return ['status' => 'fail', 'message' => 'Passwords do not match.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'fail', 'message' => 'Invalid email address.'];
        }

        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mka_users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            return ['status' => 'fail', 'message' => 'That email is already registered.'];
        }

        // Create user
        $isTrial = ($startMode === 'trial') ? 'y' : 'n';
        $isPaid = 'n'; // Will be set to 'y' by webhook when payment succeeds

        $usercreate = self::generateUser(
            $uuid, $slug, $company, $domain, $name, $email, $pass,
            $confirmationToken, $isTrial, $isPaid, $userType // NEW PARAMETER
        );

        return $usercreate;
    }

// UPDATE this function to accept $userType
    protected static function generateUser($uuid, $slug, $company, $domain, $name, $email, $pass, $confirmationToken, $isTrial, $isPaid, $userType = 'end_user') {

        global $pdo;

        $insert = $pdo->prepare("
        INSERT INTO mka_users 
            (UserUUID, Name, Email, PasswordHash, Domain, Status, IsPaid, TrialExpires, 
             company_name, company_slug, email_confirmed, email_confirmation_token, user_type)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'n', ?, ?)
    ");

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $trialExpires = date('Y-m-d H:i:s', strtotime('+14 days'));

        try {
            $insert->execute([
                $uuid, $name, $email, $hash, $domain,
                $isTrial, $isPaid, $trialExpires,
                $company, $slug, $confirmationToken, $userType
            ]);

            // Create API Key
            $apiKey = self::generateUUID();
            $insertApi = $pdo->prepare("
            INSERT INTO mka_api_keys (api_key, user_uuid, expires_at, status)
            VALUES (?, ?, ?, 'active')
        ");
            $insertApi->execute([$apiKey, $uuid, $trialExpires]);

            MKALogger::log('account_creation_success', [
                'user_uuid' => $uuid,
                'email' => $email,
                'user_type' => $userType,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Send welcome email
            self::sendWelcomeEmail($email, $name, $email, $confirmationToken);

            return [
                'status' => 'success',
                'message' => 'Signup complete! Please confirm your email.',
                'user_uuid' => $uuid
            ];

        } catch (\PDOException $e) {
            error_log("Signup DB error: " . $e->getMessage());
            MKALogger::log('account_creation_failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return ['status' => 'fail', 'message' => 'A database error occurred.'];
        }
    }

    public static function reactivateUser($data) {
        global $pdo;

        $email = trim($data['mka_email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'fail', 'message' => 'A valid email address is required.'];     
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT UserUUID FROM mka_users WHERE Email = ?");
        $stmt->execute([$email]);
        $userUuid = $stmt->fetchColumn();

        if (!$userUuid) {
            return ['status' => 'fail', 'message' => 'No account found with that email.'];
        }

        // Prevent auto-reactivation if already paid
        $stmt = $pdo->prepare("SELECT IsPaid FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $isPaid = $stmt->fetchColumn();

        if ($isPaid === 'y') {
            return ['status' => 'fail', 'message' => 'This account is already active.'];
        }

        // Return UUID for PayPal custom field
        return [
            'status' => 'success',
            'message' => 'Account found. Proceed with payment to reactivate.',
            'user_uuid' => $userUuid
        ];
    }

    protected static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    protected static function sendWelcomeEmail($to, $name, $username, $confirmationToken) {
        $subject = 'Welcome to the Virtual Speech App!';
        $confirmUrl = "https://speechapp.virtuopsdev.com/dashboards/confirm_email.php?token={$confirmationToken}";

        $html = $html = "
            <p>Thanks for starting your 14 day free trial!</p>
            <p>Before you log in, please confirm your email:</p>
            <p><a href='{$confirmUrl}'>Click here to confirm your email</a></p>
            <p>If you didn’t request this, you can ignore this email.</p>
        ";

        BrevoMailer::send([$to], $subject, $html);

    }
    
    public static function generateSlug($company) {
    // Replace spaces with dashes
    $slug = preg_replace('/\s+/', '-', $company);

    // Remove non-alphanumeric characters except dashes
    $slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $slug);

    // Convert to lowercase
    $slug = strtolower($slug);

    return $slug;
}
    public static function updateNewUser($userUuid, $company, $name, $domain) {
    global $pdo;

    // Generate slug from company name
    $slug = self::generateSlug($company);

    // Ensure uniqueness for slug
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mka_users WHERE company_slug = ? AND UserUUID != ?");
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt->execute([$slug, $userUuid]);
        if ($stmt->fetchColumn() == 0) break;
        $slug = $baseSlug . '-' . $counter++;
    }

    // Update user record
    $sql = "UPDATE mka_users 
            SET company_name = ?, company_slug = ?, Name = ?, Domain = ?
            WHERE UserUUID = ?";
    $update = $pdo->prepare($sql);

    try {
        $update->execute([$company, $slug, $name, $domain, $userUuid]);

        MKALogger::log('account_update_success', [
            'user_uuid' => $userUuid,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        return ['status' => 'success', 'message' => 'Profile updated successfully.'];
    } catch (\PDOException $e) {
        MKALogger::log('account_update_failed', [
            'user_uuid' => $userUuid,
            'error' => $e->getMessage()
        ]);
        return ['status' => 'fail', 'message' => 'Could not update profile.'];
    }
}
 

} 