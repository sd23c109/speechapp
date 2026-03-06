<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');

use MKA\Payment\StripeConfig;

// Get invite token from URL
$inviteToken = $_GET['token'] ?? null;

if (!$inviteToken) {
    header('Location: /dashboards/signup.php?error=invalid_invite');
    exit;
}

global $pdo;

// Verify invite token
$stmt = $pdo->prepare("
    SELECT pi.*, u.Name as slp_name, u.Email as slp_email
    FROM patient_invites pi
    JOIN mka_users u ON pi.slp_uuid = u.UserUUID
    WHERE pi.invite_token = ?
    LIMIT 1
");
$stmt->execute([$inviteToken]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    $_SESSION['toast_error'] = 'Invalid invitation link.';
    header('Location: /dashboards/signup.php');
    exit;
}

// Check if invite is expired
if (strtotime($invite['expires_at']) < time()) {
    $_SESSION['toast_error'] = 'This invitation has expired. Please contact your speech therapist for a new invite.';
    header('Location: /dashboards/signup.php');
    exit;
}

// Check if invite is already accepted
if ($invite['status'] === 'accepted') {
    $_SESSION['toast_error'] = 'This invitation has already been used.';
    header('Location: /dashboards/login.php');
    exit;
}

// Handle form submission
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['mka_email'] ?? '');
    $password = $_POST['mka_password'] ?? '';
    $passwordConfirm = $_POST['mka_password_confirm'] ?? '';
    $name = trim($_POST['name'] ?? '');

    $errors = [];

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Email must match the invited email
    if (strtolower($email) !== strtolower($invite['patient_email'])) {
        $errors[] = 'Email must match the invited email: ' . $invite['patient_email'];
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match';
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT UserUUID FROM mka_users WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'An account with this email already exists';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create user account
            $userUuid = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $trialExpires = date('Y-m-d H:i:s', strtotime('+14 days'));

            $stmt = $pdo->prepare("
                INSERT INTO mka_users 
                (UserUUID, Name, Email, PasswordHash, user_type, Status, CreatedAt, company_name, company_slug, domain, email_confirmed, IsPaid, TrialExpires, parent_user_uuid)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                    $userUuid,
                    $name,
                    $email,
                    $hashedPassword,
                    'end_user',          // user_type
                    'trial',             // Status
                    '-',                 // company_name
                    '-',                 // company_slug
                    '-',                 // domain
                    'y',                 // email_confirmed
                    'n',                 // IsPaid
                    $trialExpires,       // TrialExpires
                    $invite['slp_uuid']  // parent_user_uuid — links user to SLP
            ]);

            // Get the lite tier for end users
            $stmt = $pdo->prepare("SELECT tier_uuid FROM product_tiers WHERE name = 'lite' LIMIT 1");
            $stmt->execute();
            $tier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tier) {
                throw new Exception('Default tier not found');
            }

            // Create trial subscription
            $subscriptionUuid = bin2hex(random_bytes(16));
            $trialEnds = date('Y-m-d H:i:s', strtotime('+14 days'));

            $stmt = $pdo->prepare("
                INSERT INTO user_subscriptions 
                (subscription_uuid, user_uuid, tier_uuid, payment_provider, status, started_at, expires_at)
                VALUES (?, ?, ?, 'manual', 'trial', NOW(), ?)
            ");

            $stmt->execute([
                    $subscriptionUuid,
                    $userUuid,
                    $tier['tier_uuid'],
                    $trialEnds
            ]);

            // Create API key for the new user
            $apiKey = bin2hex(random_bytes(32));
            $apiKeyExpires = date('Y-m-d H:i:s', strtotime('+14 days')); // Match trial period

            $stmt = $pdo->prepare("
                INSERT INTO mka_api_keys 
                (user_uuid, api_key, status, created_at, expires_at)
                VALUES (?, ?, 'active', NOW(), ?)
            ");

            $stmt->execute([
                    $userUuid,
                    $apiKey,
                    $apiKeyExpires
            ]);

            // Create affiliation with SLP
            $affiliationUuid = bin2hex(random_bytes(16));

            $stmt = $pdo->prepare("
                INSERT INTO patient_affiliations 
                (affiliation_uuid, patient_uuid, slp_uuid, affiliated_at, status)
                VALUES (?, ?, ?, NOW(), 'active')
            ");

            $stmt->execute([
                    $affiliationUuid,
                    $userUuid,
                    $invite['slp_uuid']
            ]);

            // Mark invite as accepted
            $stmt = $pdo->prepare("
                UPDATE patient_invites 
                SET status = 'accepted',
                    accepted_at = NOW(),
                    accepted_user_uuid = ?
                WHERE invite_uuid = ?
            ");

            $stmt->execute([$userUuid, $invite['invite_uuid']]);

            $pdo->commit();

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                        'status' => 'success',
                        'message' => 'Account created successfully! You can now log in.',
                        'user_uuid' => $userUuid
                ]);
                exit;
            }

            $_SESSION['toast_success'] = 'Account created successfully! You have been affiliated with ' . htmlspecialchars($invite['slp_name']) . '. You can now log in.';
            header('Location: /dashboards/login.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Accept invite error: " . $e->getMessage());

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to create account: ' . $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['toast_error'] = 'Failed to create account. Please try again.';
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                    'status' => 'error',
                    'message' => implode(', ', $errors)
            ]);
            exit;
        }

        $_SESSION['toast_error'] = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Accept Invitation | Virtual Speech App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="/dashboards/img/favicon.ico">
    <script src="plugins/jquery/js/jquery.min.js"></script>
    <link href="css/vendors.min.css" rel="stylesheet" type="text/css">
    <link href="css/app.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">

    <style>
        .card-side-img {
            background-image: url("img/front_page.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100%;
        }
        .auth-brand img {
            height: 120px;
            width: auto;
            border-radius: 12px;
            object-fit: contain;
        }
    </style>
</head>

<body>
<div class="auth-box d-flex align-items-center">
    <div class="container-xxl">
        <div class="row align-items-center justify-content-center">
            <div class="col-xl-10">
                <div class="card rounded-4">
                    <div class="row justify-content-between g-0">
                        <div class="col-lg-6">
                            <div class="card-body">
                                <div class="auth-brand text-center mb-4">
                                    <a href="index.php" class="logo-dark">
                                        <img src="img/Logo1.png" alt="logo" height="170">
                                    </a>
                                    <h4 class="fw-bold mt-4">Accept Your Invitation</h4>
                                    <p class="text-muted w-lg-75 mx-auto">
                                        You've been invited by <strong><?= htmlspecialchars($invite['slp_name']) ?></strong>
                                        to join Virtual Speech App.
                                    </p>
                                </div>

                                <div class="alert alert-info mb-4">
                                    <i class="ti ti-info-circle"></i>
                                    <strong>Creating account for:</strong> <?= htmlspecialchars($invite['patient_email']) ?>
                                </div>

                                <form id="acceptInviteForm" method="POST" action="">
                                    <input type="hidden" name="invite_token" value="<?= htmlspecialchars($inviteToken) ?>">

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-user text-muted fs-xl"></i></span>
                                            <input type="text" class="form-control" id="name" name="name"
                                                   value="<?= htmlspecialchars($invite['patient_name'] ?? '') ?>"
                                                   placeholder="Your full name" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="mka_email" class="form-label">Email address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                            <input type="email" class="form-control" id="mka_email" name="mka_email"
                                                   value="<?= htmlspecialchars($invite['patient_email']) ?>"
                                                   readonly required>
                                        </div>
                                        <small class="form-text text-muted">This is the email where your invitation was sent</small>
                                    </div>

                                    <div class="mb-3" data-password="bar">
                                        <label for="mka_password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-lock-password text-muted fs-xl"></i></span>
                                            <input type="password" class="form-control" id="mka_password" name="mka_password"
                                                   placeholder="*************" required>
                                        </div>
                                        <div class="password-bar my-2"></div>
                                        <p class="text-muted fs-xs mb-0">Use 8+ characters with letters, numbers & symbols.</p>
                                    </div>

                                    <div class="mb-3">
                                        <label for="mka_password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-lock-check text-muted fs-xl"></i></span>
                                            <input type="password" class="form-control" id="mka_password_confirm" name="mka_password_confirm"
                                                   placeholder="Confirm Password" required>
                                        </div>
                                    </div>

                                    <div class="alert alert-success">
                                        <i class="ti ti-check"></i>
                                        <strong>What happens next:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>You'll get a 14-day free trial</li>
                                            <li>You'll be affiliated with <?= htmlspecialchars($invite['slp_name']) ?></li>
                                            <li>After trial, it's only $10/month</li>
                                        </ul>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary fw-semibold py-2">
                                            Create Account & Start Trial
                                        </button>
                                    </div>
                                </form>

                                <p class="text-muted text-center mt-4 mb-0">
                                    Already have an account? <a href="login.php" class="text-decoration-underline link-offset-3 fw-semibold">Login</a>
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="h-100 position-relative card-side-img rounded-end-4 rounded-end rounded-0 overflow-hidden"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/vendors.min.js"></script>
<script src="js/app.js"></script>
<script src="js/pages/auth-password.js"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>

<script>
    // Password validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const pass = document.getElementById('mka_password').value;
        const confirm = document.getElementById('mka_password_confirm').value;

        if (pass !== confirm) {
            e.preventDefault();
            toastr.options = { "positionClass": "toast-top-center", "timeOut": "5000" };
            toastr.error("Passwords do not match.");
        }
    });
</script>

<?php if (!empty($_SESSION['toast_error'])): ?>
    <script>
        $(document).ready(function() {
            toastr.options = { "positionClass": "toast-top-center", "timeOut": "5000" };
            toastr.error("<?= addslashes($_SESSION['toast_error']) ?>");
        });
    </script>
    <?php unset($_SESSION['toast_error']); endif; ?>

<?php if (!empty($_SESSION['toast_success'])): ?>
    <script>
        $(document).ready(function() {
            toastr.options = { "positionClass": "toast-top-center", "timeOut": "5000" };
            toastr.success("<?= addslashes($_SESSION['toast_success']) ?>");
        });
    </script>
    <?php unset($_SESSION['toast_success']); endif; ?>

</body>
</html>
