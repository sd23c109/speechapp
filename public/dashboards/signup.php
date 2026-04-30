<?php
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Auth/SignupHandler.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');

use MKA\Auth\SignupHandler;
use MKA\Payment\StripeConfig;

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = SignupHandler::handle($_POST);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    if ($result['status'] === 'success') {
        $_SESSION['toast_success'] = $result['message'];
    } else {
        $_SESSION['toast_error'] = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create New Account | Virtual Speech App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="/dashboards/img/favicon.ico">
    <script src="plugins/jquery/js/jquery.min.js"></script>
    <link href="css/vendors.min.css" rel="stylesheet" type="text/css">
    <link href="css/app.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <!-- Mobile responsive overrides -->
    <link href="css/mobile.css" rel="stylesheet" type="text/css">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ts = Math.floor(Date.now() / 1000);
            const el = document.getElementById('rendered_at');
            if (el) el.value = ts;
        });
    </script>

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
        /* Make radio buttons more visible */
        .form-check-input {
            border: 2px solid #6c757d; /* Darker border for unchecked */
        }

        .form-check-input:checked {
            background-color: #0d6efd; /* Blue when checked */
            border-color: #0d6efd;
        }

        .form-check-input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
                                    <h4 class="fw-bold mt-4">Sign Up for The Virtual Speech App</h4>
                                    <p class="text-muted w-lg-75 mx-auto">Create your account by entering your details below.</p>
                                </div>

                                <form id="signupForm" method="POST" action="">
                                    <input type="hidden" name="user_uuid" id="user_uuid" value="">

                                    <!-- Honeypot -->
                                    <div style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
                                        <label>Website</label>
                                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                                    </div>

                                    <input type="hidden" name="rendered_at" id="rendered_at" value="">

                                    <div class="mb-3">
                                        <label for="mka_email" class="form-label">Email address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                            <input type="email" class="form-control" id="mka_email" name="mka_email" placeholder="you@example.com" required>
                                        </div>
                                    </div>

                                    <div class="mb-3" data-password="bar">
                                        <label for="mka_password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-lock-password text-muted fs-xl"></i></span>
                                            <input type="password" class="form-control" id="mka_password" name="mka_password" placeholder="*************" required>
                                        </div>
                                        <div class="password-bar my-2"></div>
                                        <p class="text-muted fs-xs mb-0">Use 8+ characters with letters, numbers & symbols.</p>
                                    </div>

                                    <div class="mb-3">
                                        <label for="mka_password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ti ti-lock-check text-muted fs-xl"></i></span>
                                            <input type="password" class="form-control" id="mka_password_confirm" name="mka_password_confirm" placeholder="Confirm Password" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="g-recaptcha" data-sitekey="6LcZUQksAAAAAODodfhM1F5Y1lFWu56X9gajD6CH" data-callback="onCaptchaSuccess"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Start with:</label><br>
                                        <div class="mb-2">
                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="start_mode" id="startTrial" value="trial">
                                                <label class="form-check-label" for="startTrial">Start 14 Day Trial</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="start_mode" id="startPaid" value="paid">
                                                <label class="form-check-label" for="startPaid">Start Subscription</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden until start_mode is selected -->
                                    <div class="mb-3" id="planSelection" style="display:none;">
                                        <label class="form-label" id="planSelectionLabel">I am a:</label><br>
                                        <div class="mb-2">
                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="user_type" id="typePatient" value="end_user">
                                                <label class="form-check-label" for="typePatient" id="labelPatient">Patient / Parent</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input type="radio" class="form-check-input" name="user_type" id="typeSLP" value="enterprise_admin">
                                                <label class="form-check-label" for="typeSLP" id="labelSLP">Speech Therapist / SLP</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden until both selections made -->
                                    <div class="d-grid" id="startButtons" style="display:none !important;"></div>
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

<!-- Stripe Checkout Modal -->
<div class="modal fade" id="stripeCheckoutModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Complete Your Subscription</h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Redirecting to secure checkout...</p>
            </div>
        </div>
    </div>
</div>

<script src="js/vendors.min.js"></script>
<script src="js/app.js"></script>
<script src="js/pages/auth-password.js"></script>
<script src="plugins/toastr/js/toastr.min.js"></script>
<script src="https://js.stripe.com/v3/"></script>

<script>
    let _stripe = null;
    function getStripe() {
        if (!_stripe) {
            const key = '<?= StripeConfig::getPublishableKey() ?>';
            if (!key) throw new Error('Payment is not configured. Please contact support.');
            _stripe = Stripe(key);
        }
        return _stripe;
    }

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

    // Step 1: start_mode selection reveals user_type options
    document.querySelectorAll('input[name="start_mode"]').forEach(el => {
        el.addEventListener('change', function() {
            const isTrial = (this.value === 'trial');

            // Update labels
            document.getElementById('labelPatient').textContent = isTrial ? 'Patient / Parent' : 'Patient / Parent ($10/month)';
            document.getElementById('labelSLP').textContent     = isTrial ? 'Speech Therapist / SLP' : 'Speech Therapist / SLP ($100/month)';

            // Uncheck any previously selected user_type and show the section
            document.querySelectorAll('input[name="user_type"]').forEach(r => r.checked = false);
            document.getElementById('planSelection').style.display = 'block';

            // Hide button until user_type is also chosen
            document.getElementById('startButtons').style.display = 'none';
        });
    });

    // Step 2: user_type selection reveals the submit button
    document.querySelectorAll('input[name="user_type"]').forEach(el => {
        el.addEventListener('change', function() {
            const startMode = document.querySelector('input[name="start_mode"]:checked')?.value;
            if (!startMode) return;

            const container = document.getElementById('startButtons');
            container.style.display = 'block';

            if (startMode === 'trial') {
                container.innerHTML = `<button type="submit" class="btn btn-primary fw-semibold py-2">Start Trial</button>`;
            } else {
                container.innerHTML = `
                    <button type="button" class="btn btn-primary fw-semibold py-2" id="payNowTrigger" disabled>
                        Subscribe Now
                    </button>`;
                validateSignupFields();
            }
        });
    });
    // Field validation
    document.querySelectorAll('[id^="mka_"]').forEach(input => {
        input.addEventListener('input', validateSignupFields);
    });

    function validateSignupFields() {
        const inputs = document.querySelectorAll('[id^="mka_"]');
        let allFilled = true;

        inputs.forEach(input => {
            if (!input.value.trim()) allFilled = false;
        });

        let captchaOK = false;
        if (window.grecaptcha && typeof grecaptcha.getResponse === "function") {
            captchaOK = grecaptcha.getResponse().trim().length > 0;
        }

        const payButton = document.querySelector('#payNowTrigger');
        if (payButton) {
            payButton.disabled = !(allFilled && captchaOK);
        }
    }

    window.onCaptchaSuccess = function() { validateSignupFields(); };

    // Handle paid signup with Stripe
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'payNowTrigger') {
            e.preventDefault();

            // Check captcha
            if (!window.grecaptcha || grecaptcha.getResponse().length === 0) {
                toastr.options = { positionClass: "toast-top-center", timeOut: "4000" };
                toastr.error("Please complete the reCAPTCHA.");
                return;
            }

            const form = document.getElementById('signupForm');
            const formData = new FormData(form);

            // Show loading modal
            const modal = new bootstrap.Modal(document.getElementById('stripeCheckoutModal'));
            modal.show();

            // Create user first, then redirect to Stripe
            fetch('signup_stripe.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('user_uuid').value = data.user_uuid;

                        // Get user type and create Stripe checkout
                        // Get user type and start mode
                        const userType = document.querySelector('input[name="user_type"]:checked').value;
                        const startMode = document.querySelector('input[name="start_mode"]:checked').value;

                        return fetch('/dashboards/api/stripe/create_checkout.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                user_uuid: data.user_uuid,
                                user_type: userType,
                                start_mode: startMode  // ← Add this
                            })
                        });
                    } else {
                        throw new Error(data.message || 'Signup failed');
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.session_id) {
                        // Redirect to Stripe Checkout
                        return getStripe().redirectToCheckout({ sessionId: data.session_id });
                    } else {
                        throw new Error(data.message || 'Failed to create checkout session');
                    }
                })
                .catch(err => {
                    modal.hide();
                    toastr.options = { positionClass: "toast-top-center", timeOut: "5000" };
                    toastr.error(err.message || 'Error creating account');
                });
        }
    });

    document.addEventListener('DOMContentLoaded', validateSignupFields);
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
            setTimeout(function() {
                window.location.href = "login.php";
            }, 3000);
        });
    </script>
    <?php unset($_SESSION['toast_success']); endif; ?>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>