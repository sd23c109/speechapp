<?php
/**
 * Global initialization and session enforcement
 * Used by Dashboards, FormBuilder, and Portal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('/opt/mka/core/Security/CSRFHelper.php');
require_once('/opt/mka/core/Tasks/CheckSubscriptionLimits.php');
use MKA\Security\CSRFHelper;
use MKA\Tasks\CheckSubscriptionLimits;

CSRFHelper::generateToken();

if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

/**
 * FIXED PATH:
 * Because _init.php lives under /public/dashboards/,
 * we go up TWO levels to reach bootstrap.php at /opt/mka/bootstrap.php
 */
require_once __DIR__ . '/../bootstrap.php';

// Redirect to login if not logged in
if (empty($_SESSION['user_data'])) {
    header("Location: https://speechapp.virtuopsdev.com/dashboards/login.php");
    exit;
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;

if ($user_uuid) {

    /*
     * Validate API key
     */
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT api_key, status, expires_at
        FROM mka_api_keys
        WHERE user_uuid = ?
        LIMIT 1
    ");
    $stmt->execute([$user_uuid]);
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$keyRow || $keyRow['status'] !== 'active' ||
        ($keyRow['expires_at'] && strtotime($keyRow['expires_at']) < time())
    ) {
        error_log("Trial expired or invalid API key for user $user_uuid");
        setcookie('mka_user_uuid', $user_uuid, time() + 3600, '/', '.speechapp.virtuopsdev.com');
        header('Location: https://speechapp.virtuopsdev.com/trial-expired');
        exit;
    }

    $_SESSION['user_data']['api_key'] = $keyRow['api_key'];

    /*
     * Load subscription
     */
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT 
            us.tier_uuid,
            us.paypal_subscription_id,
            us.expires_at, 
            us.status AS subscription_status,
            pt.name AS plan_name, 
            pt.features_json
        FROM user_subscriptions us
        INNER JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid 
        WHERE us.user_uuid = ?
          AND us.status IN ('trial','active')
        ORDER BY us.started_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_uuid]);
    $userSub = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
     * Load basic user info
     */
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT UserUUID, Email, Name, Domain, company_name, company_slug, TrialExpires, CreatedAt, IsTrial, IsPaid
        FROM mka_users
        WHERE UserUUID = ?
        LIMIT 1
    ");
    $stmt->execute([$user_uuid]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($userData)) {
        $_SESSION['user_data']['user_info'] = $userData;
    } else {
        header("Location: /dashboards/logout.php");
        exit;
    }

    if ($userSub) {
        $_SESSION['user_data']['plan_name']           = $userSub['plan_name'];
        $_SESSION['user_data']['subscription_id']     = $userSub['paypal_subscription_id'];
        $_SESSION['user_data']['expires_at']          = $userSub['expires_at'];
        $_SESSION['user_data']['plan_features']       = !empty($userSub['features_json'])
            ? json_decode($userSub['features_json'], true)
            : [];
        $_SESSION['user_data']['subscription_status'] = $userSub['subscription_status'];
    } else {
        if (($_SESSION['user_data']['user_info']['IsTrial'] ?? 'n') === 'n') {
            error_log("No active subscription or trial for user $user_uuid");
            session_unset();
            session_destroy();
            header("Location: /dashboards/account_inactive.php");
            exit;
        }
    }

    /*
     * -------------------------------
     * ACCOUNT + ROLE RESOLUTION (safe)
     * -------------------------------
     */
    $currentAccount = $_SESSION['current_account_uuid'] ?? null;
    $user_uuid = $_SESSION['user_data']['user_uuid'] ?? null;

    $hasActiveMembership = function(string $userUuid, string $accountUuid): bool {
        $stmt = $GLOBALS['pdo']->prepare("
        SELECT 1
        FROM mka_user_accounts
        WHERE user_uuid = :u
          AND account_uuid = :a
          AND status = 'active'
        LIMIT 1
    ");
        $stmt->execute([':u' => $userUuid, ':a' => $accountUuid]);
        return (bool)$stmt->fetchColumn();
    };

// 1) If session has a current account, make sure membership is valid; otherwise discard it.
    if ($currentAccount && !$hasActiveMembership($user_uuid, $currentAccount)) {
        $currentAccount = null;
        unset($_SESSION['current_account_uuid']);
    }

// 2) If login stored a preferred account in the session (from provisioning), and user is a member, use that.
    if (!$currentAccount) {
        $loginAccount = $_SESSION['user_data']['account_uuid'] ?? null;
        if ($loginAccount && $hasActiveMembership($user_uuid, $loginAccount)) {
            $currentAccount = $loginAccount;
        }
    }

// 3) If the host maps to an account, only use it if the user is a member of that account.
    if (!$currentAccount) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host) {
            $stmt = $GLOBALS['pdo']->prepare("
            SELECT account_uuid
            FROM mka_account_domains
            WHERE domain = :d
            LIMIT 1
        ");
            $stmt->execute([':d' => $host]);
            $mapped = $stmt->fetchColumn() ?: null;

            if ($mapped && $hasActiveMembership($user_uuid, $mapped)) {
                $currentAccount = $mapped;
            }
        }
    }

// 4) Fallback: first active membership by role priority.
    if (!$currentAccount) {
        $stmt = $GLOBALS['pdo']->prepare("
        SELECT account_uuid
        FROM mka_user_accounts
        WHERE user_uuid = :u AND status='active'
        ORDER BY FIELD(role,'OWNER','ADMIN','STAFF','CONTRACTOR','PATIENT') ASC
        LIMIT 1
    ");
        $stmt->execute([':u' => $user_uuid]);
        $currentAccount = $stmt->fetchColumn() ?: null;
    }

    if (!$currentAccount) {
        http_response_code(403);
        echo "No active account membership found for this user.";
        exit;
    }

    $_SESSION['current_account_uuid'] = $currentAccount;

// Resolve role (now guaranteed to exist if we passed checks)
    $stmt = $GLOBALS['pdo']->prepare("
    SELECT role
    FROM mka_user_accounts
    WHERE user_uuid = :u
      AND account_uuid = :a
      AND status='active'
    LIMIT 1
");
    $stmt->execute([':u' => $user_uuid, ':a' => $currentAccount]);
    $role = $stmt->fetchColumn();

    if (!$role) {
        http_response_code(403);
        echo "Access denied for this account.";
        exit;
    }

    $GLOBALS['_role_cache'] = $role;


    /*
     * Enforce plan limits (same as before)
     */
    try {
        CheckSubscriptionLimits::enforceFormLimit($user_uuid);
    } catch (Exception $e) {
        error_log("Subscription limit enforcement failed for user {$user_uuid}: " . $e->getMessage());
    }
}

/*
 * -------------------------------
 * HELPER FUNCTIONS
 * -------------------------------
 */
function current_user_uuid()    { return $_SESSION['user_data']['user_uuid'] ?? null; }
function current_account_uuid() { return $_SESSION['current_account_uuid'] ?? null; }
function current_role()         { return $GLOBALS['_role_cache'] ?? null; }

function require_role(array $allowed)
{
    $role = current_role();
    if (!$role || !in_array($role, $allowed, true)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

function csrf_token()  { return $_SESSION['csrf_token'] ?? ''; }
function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hdr = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $frm = $_POST['csrf_token'] ?? '';
        $token = $_SESSION['csrf_token'] ?? '';
        if (!$token || !hash_equals($token, $hdr ?: $frm)) {
            http_response_code(419);
            echo "CSRF validation failed";
            exit;
        }
    }
}

/*
 * -------------------------------
 * GLOBALS & TIMEOUT
 * -------------------------------
 */
$GLOBALS['apphome'] = "https://speechapp.virtuopsdev.com/dashboards/index.php";
$GLOBALS['mkahome'] = "https://speechapp.virtuopsdev.com";

$timeoutSeconds = 7200; // 2 hours
if (isset($_SESSION['LAST_ACTIVITY'])) {
    $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
    if ($elapsed > $timeoutSeconds) {
        require_once '/opt/mka/core/Log/MKALogger.php';
        \MKA\Log\MKALogger::log('auto_logout_timeout', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_uuid' => $_SESSION['user_data']['user_uuid'] ?? null,
        ]);

        session_unset();
        session_destroy();
        header("Location: /dashboards/logout.php?reason=timeout");
        exit;
    }
}
$_SESSION['LAST_ACTIVITY'] = time();
