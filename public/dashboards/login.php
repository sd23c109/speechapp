<?php
require_once '../../bootstrap.php';
require_once('/opt/mka/core/Auth/LoginHandler.php');
require_once('/opt/mka/core/Log/MKALogger.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');
use MKA\Payment\StripeConfig;
use MKA\Auth\LoginHandler;
use MKA\Log\MKALogger;


session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = LoginHandler::handle($_POST);

    if ($result['success']) {
        MKALogger::log('login', [
                'user_uuid' => $_SESSION['user_data']['user_uuid']
        ]);

        header('Location: /dashboards/speechapp.php');
        exit;
    } else {
        MKALogger::log('login_failure', [
                'username_attempted' => $_POST['email'] ?? '(unknown)'
        ]);
        if (!empty($result['trial_expired'])) {
            setcookie('mka_user_uuid', $result['user_uuid'], time() + 3600, '/', '');
            header('Location: /dashboards/trial-expired.php');
            exit;
        }
        $error = $result['message'];
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sign In | The Virtual Speech App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MKAdvantage Online Tools are perfect for every business looking to accelerate their online presence and better serve their customers">
    <meta name="keywords" content="MKAdvantage, admin dashboard, HIPAA Forms, responsive admin, web app UI, admin theme, website tools">
    <meta name="author" content="MKAdvantage, Inc.">

    <!-- App favicon -->
    <link rel="shortcut icon"href="/dashboards/img/favicon.ico">

    <!-- Theme Config Js -->
    <script src="js/config.js"></script>

    <!-- Vendor css -->
    <link href="css/vendors.min.css" rel="stylesheet" type="text/css">

    <!-- App css -->
    <link href="css/app.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <!-- Mobile responsive overrides -->
    <link href="css/mobile.css" rel="stylesheet" type="text/css">
    <script src="plugins/jquery/js/jquery.min.js"></script>

    <style>
        .card-side-img {
            background-image:url("img/front_page.png");
            background-size: cover;          /* fill the area */
            background-position: center;     /* center focus */
            background-repeat: no-repeat;    /* no tiling */
            min-height: 100%;                /* ensure visibility */
        }

        .auth-brand img {
            height: 120px;            /* adjust size as needed */
            width: auto;              /* keep aspect ratio */
            border-radius: 12px;      /* rounded corners */
            object-fit: contain;      /* ensures clean scaling */
        }
    </style>


    <script src="plugins/jquery-mask-plugin/js/jquery.mask.min.js"></script>
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
                                        <img src="img/Logo1.png" alt="dark logo" height="170">
                                    </a>
                                    <a href="index.html" class="logo-light">
                                        <img src="img/Logo1.png" alt="logo" height="170">
                                    </a>
                                    <h4 class="fw-bold mt-4">Welcome to The Virtual Speech App!</h4>
                                    <p class="text-muted w-lg-75 mx-auto">Let's get you signed in. Enter your email and password to continue.</p>
                                </div>

                                <form role="form" method="post">
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <i class="ti ti-alert-circle me-2"></i>
                                            <?= htmlspecialchars($error) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="userEmail" class="form-label">Email address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                            <input type="email" class="form-control" name="email" id="email" placeholder="you@example.com" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="userPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-lock-password text-muted fs-xl"></i></span>
                                            <input type="password" class="form-control" name="password" id="password" placeholder="••••••••" required>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input form-check-input-light fs-14" type="checkbox" id="rememberMe">
                                            <label class="form-check-label" for="rememberMe">Keep me signed in</label>
                                        </div>
                                        <a href="forgot_password.php" class="text-decoration-underline link-offset-3 text-muted">Forgot Password?</a>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary fw-semibold py-2">Sign In</button>
                                    </div>
                                </form>

                                <p class="text-muted text-center mt-4 mb-0">
                                    New here? <a href="signup.php" class="text-decoration-underline link-offset-3 fw-semibold">Create a trial account</a>
                                </p>

                                <p class="text-muted text-center mt-2 mb-0">
                                    Trial expired? <a href="#" class="text-decoration-underline link-offset-3 fw-bold text-primary"
                                                      data-bs-toggle="modal" data-bs-target="#upgradeModal"
                                                      id="upgradeLink">UPGRADE</a>
                                </p>

                                <p class="text-center text-muted mt-4 mb-0">
                                    <span class="fw-semibold">Crossroads Therapy Clinic, LLC.</span>
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-6 d-none d-lg-block">
                            <div class="h-100 position-relative card-side-img rounded-end-4 rounded-end rounded-0 overflow-hidden">
                                <div class="p-4 rounded-4 rounded-start-0 d-flex align-items-end justify-content-center">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end auth-fluid-->

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="upgradeModalLabel">
                    <i class="ti ti-rocket text-primary"></i> Upgrade Your Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted mb-4">Enter your account email and select your plan to continue.</p>

                <div id="upgradeError" class="alert alert-danger d-none"></div>

                <div class="mb-3">
                    <label class="form-label">Account Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                        <input type="email" class="form-control" id="upgradeEmail" placeholder="you@example.com">
                    </div>
                    <small class="text-muted">The email address on your existing trial account</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">I am a:</label>
                    <div class="d-flex flex-column gap-2 mt-1">
                        <div class="form-check border rounded-3 p-3 ps-5 upgrade-plan-option" id="planPatientBox">
                            <input class="form-check-input" type="radio" name="upgrade_user_type"
                                   id="upgradePlanPatient" value="end_user" checked>
                            <label class="form-check-label w-100" for="upgradePlanPatient">
                                <strong>Patient / Parent</strong>
                                <span class="text-muted ms-2">$10/month</span>
                            </label>
                        </div>
                        <div class="form-check border rounded-3 p-3 ps-5 upgrade-plan-option" id="planSLPBox">
                            <input class="form-check-input" type="radio" name="upgrade_user_type"
                                   id="upgradePlanSLP" value="enterprise_admin">
                            <label class="form-check-label w-100" for="upgradePlanSLP">
                                <strong>Speech Therapist / SLP</strong>
                                <span class="text-muted ms-2">$100/month</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold px-4" id="upgradeBtn">
                    <i class="ti ti-credit-card me-1"></i> Proceed to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script src="js/vendors.min.js"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>
<script src="js/app.js"></script>

<!-- Modal setup and upgrade functions — no Stripe dependency, defined immediately -->
<script>
    // Highlight selected plan box on change
    document.querySelectorAll('input[name="upgrade_user_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.upgrade-plan-option').forEach(function(box) {
                box.classList.remove('border-primary');
            });
            this.closest('.upgrade-plan-option').classList.add('border-primary');
        });
    });
    document.getElementById('planPatientBox').classList.add('border-primary');

    // Pre-fill upgrade email when the upgrade link is clicked
    document.getElementById('upgradeLink').addEventListener('click', function() {
        var loginEmail = document.getElementById('email').value;
        if (loginEmail) {
            document.getElementById('upgradeEmail').value = loginEmail;
        }
        document.getElementById('upgradeError').classList.add('d-none');
    });


    document.getElementById('upgradeBtn').addEventListener('click', startUpgrade);

    function startUpgrade() {
        var email    = document.getElementById('upgradeEmail').value.trim();
        var userType = document.querySelector('input[name="upgrade_user_type"]:checked').value;
        var btn      = document.getElementById('upgradeBtn');
        var errorDiv = document.getElementById('upgradeError');

        if (!email) {
            errorDiv.textContent = 'Please enter your account email.';
            errorDiv.classList.remove('d-none');
            return;
        }

        btn.disabled   = true;
        btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span> Redirecting...';
        errorDiv.classList.add('d-none');

        fetch('/dashboards/api/stripe/create_upgrade_checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, user_type: userType })
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.session_id) {
                    // Stripe is loaded by now — safe to use
                    return Stripe('<?= \MKA\Payment\StripeConfig::getPublishableKey() ?>').redirectToCheckout({ sessionId: data.session_id });
                } else {
                    throw new Error(data.message || 'Could not create checkout session.');
                }
            })
            .catch(function(err) {
                errorDiv.textContent = err.message;
                errorDiv.classList.remove('d-none');
                btn.disabled  = false;
                btn.innerHTML = '<i class="ti ti-credit-card me-1"></i> Proceed to Payment';
            });
    }
</script>

<!-- Stripe loaded last, only needed at redirect time -->
<script src="https://js.stripe.com/v3/"></script>


<?php
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'){
    ?>
    <script>
        $(document).ready(function () {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-center",
                "timeOut": "6000"
            };
            toastr.warning("You were logged out due to 15 minutes of inactivity.", "Session Timeout");
        });
    </script>
    <?php
}

$status = $_GET['status'] ?? null;
if ($status === 'confirmemail'){
    ?>
    <script>
        $(document).ready(function () {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-center",
                "timeOut": "8000"
            };
            toastr.success("Your account was created!<br>Please check your email to confirm BEFORE LOGIN.", "Success");
        });
    </script>
    <?php
}
?>


<?php if (isset($_SESSION['toast_warning'])): ?>
    <script>
        $(document).ready(function () {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-center",
                "timeOut": "6000"
            };
            toastr.warning("<?= addslashes($_SESSION['toast_warning']) ?>", "Checkout Cancelled");
        });
    </script>
    <?php
    unset($_SESSION['toast_warning']);
endif;
?>


</body>

</html>