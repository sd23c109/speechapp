<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');

use MKA\Payment\StripeConfig;

// Get user UUID from cookie or session
$userUuid = $_COOKIE['mka_user_uuid'] ?? $_SESSION['user_data']['user_uuid'] ?? null;

if (!$userUuid) {
    header('Location: /dashboards/login.php');
    exit;
}

global $pdo;

// Get user information
$stmt = $pdo->prepare("
    SELECT u.*, 
           us.expires_at as trial_expired_at,
           pt.name as current_plan
    FROM mka_users u
    LEFT JOIN user_subscriptions us ON u.UserUUID = us.user_uuid AND us.status = 'trial'
    LEFT JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
    WHERE u.UserUUID = ?
    LIMIT 1
");
$stmt->execute([$userUuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /dashboards/login.php');
    exit;
}

// Get SLP affiliation if exists
$stmt = $pdo->prepare("
    SELECT u.Name as slp_name, u.Email as slp_email
    FROM patient_affiliations pa
    JOIN mka_users u ON pa.slp_uuid = u.UserUUID
    WHERE pa.patient_uuid = ? AND pa.status = 'active'
    LIMIT 1
");
$stmt->execute([$userUuid]);
$affiliation = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize Stripe
$stripe = StripeConfig::getClient();

// Handle Stripe payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method_id'])) {
    try {
        $pdo->beginTransaction();

        // Get the lite tier
        $stmt = $pdo->prepare("SELECT tier_uuid, stripe_price_id FROM product_tiers WHERE name = 'lite' LIMIT 1");
        $stmt->execute();
        $tier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tier || !$tier['stripe_price_id']) {
            throw new Exception('Payment plan not configured');
        }

        // Create or retrieve Stripe customer
        $stmt = $pdo->prepare("SELECT stripe_customer_id FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $stripeCustomerId = $stmt->fetchColumn();

        if (!$stripeCustomerId) {
            // Create new Stripe customer
            $customer = $stripe->customers->create([
                'email' => $user['Email'],
                'name' => $user['Name'],
                'payment_method' => $_POST['payment_method_id'],
                'invoice_settings' => [
                    'default_payment_method' => $_POST['payment_method_id'],
                ],
            ]);
            $stripeCustomerId = $customer->id;

            // Update user with Stripe customer ID
            $stmt = $pdo->prepare("UPDATE mka_users SET stripe_customer_id = ? WHERE UserUUID = ?");
            $stmt->execute([$stripeCustomerId, $userUuid]);
        } else {
            // Attach payment method to existing customer
            $stripe->paymentMethods->attach(
                $_POST['payment_method_id'],
                ['customer' => $stripeCustomerId]
            );

            // Set as default payment method
            $stripe->customers->update($stripeCustomerId, [
                'invoice_settings' => [
                    'default_payment_method' => $_POST['payment_method_id'],
                ],
            ]);
        }

        // Create Stripe subscription
        $subscription = $stripe->subscriptions->create([
            'customer' => $stripeCustomerId,
            'items' => [['price' => $tier['stripe_price_id']]],
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        // Update user subscription in database
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'active',
                payment_provider = 'stripe',
                stripe_subscription_id = ?,
                started_at = NOW(),
                expires_at = NULL
            WHERE user_uuid = ? AND status = 'trial'
        ");
        $stmt->execute([$subscription->id, $userUuid]);

        // Update user status
        $stmt = $pdo->prepare("
            UPDATE mka_users 
            SET Status = 'active',
                IsPaid = 'y'
            WHERE UserUUID = ?
        ");
        $stmt->execute([$userUuid]);

        // Extend API key
        $stmt = $pdo->prepare("
            UPDATE mka_api_keys 
            SET status = 'active',
                expires_at = NULL
            WHERE user_uuid = ?
        ");
        $stmt->execute([$userUuid]);

        $pdo->commit();

        // Return success
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'subscription_id' => $subscription->id
        ]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment error: " . $e->getMessage());

        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Trial Expired - Upgrade Your Account | Virtual Speech App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="/dashboards/img/favicon.ico">
    <script src="https://js.stripe.com/v3/"></script>
    <script src="/dashboards/plugins/jquery/js/jquery.min.js"></script>
    <link href="/dashboards/css/vendors.min.css" rel="stylesheet" type="text/css">
    <link href="/dashboards/css/app.min.css" rel="stylesheet" type="text/css">
    <link href="/dashboards/plugins/toastr/css/toastr.min.css" rel="stylesheet">

    <style>
        .card-side-img {
            background-image: url("/dashboards/img/front_page.png");
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
        .pricing-badge {
            font-size: 3rem;
            font-weight: 700;
            color: #0d6efd;
        }
        .feature-check {
            color: #198754;
            margin-right: 8px;
        }
        #card-element {
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background: white;
        }
        #card-errors {
            color: #dc3545;
            margin-top: 8px;
            font-size: 0.875rem;
        }
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
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
                                        <img src="/dashboards/img/Logo1.png" alt="logo" height="170">
                                    </a>
                                    <h4 class="fw-bold mt-4 text-danger">
                                        <i class="ti ti-clock-exclamation"></i> Your Trial Has Expired
                                    </h4>
                                    <p class="text-muted w-lg-75 mx-auto">
                                        Don't worry! Continue your speech therapy journey for just <strong>$10/month</strong>
                                    </p>
                                </div>

                                <?php if ($user['trial_expired_at']): ?>
                                    <div class="alert alert-warning mb-4">
                                        <i class="ti ti-info-circle"></i>
                                        Your trial expired on <strong><?= date('F j, Y', strtotime($user['trial_expired_at'])) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if ($affiliation): ?>
                                    <div class="alert alert-info mb-4">
                                        <i class="ti ti-user-heart"></i>
                                        <strong>Your Speech Therapist:</strong> <?= htmlspecialchars($affiliation['slp_name']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Pricing Info -->
                                <div class="text-center mb-4 p-4 bg-light rounded-3">
                                    <div class="pricing-badge mb-2">$10<span class="fs-5 text-muted">/month</span></div>
                                    <p class="text-muted mb-3">Unlimited access to all features</p>

                                    <div class="text-start">
                                        <div class="mb-2">
                                            <i class="ti ti-check feature-check"></i>
                                            <span>Unlimited speech exercises</span>
                                        </div>
                                        <div class="mb-2">
                                            <i class="ti ti-check feature-check"></i>
                                            <span>Progress tracking & analytics</span>
                                        </div>
                                        <div class="mb-2">
                                            <i class="ti ti-check feature-check"></i>
                                            <span>Access to all therapy modules</span>
                                        </div>
                                        <div class="mb-2">
                                            <i class="ti ti-check feature-check"></i>
                                            <span>Connected with your speech therapist</span>
                                        </div>
                                        <div>
                                            <i class="ti ti-check feature-check"></i>
                                            <span>Cancel anytime, no contracts</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Form -->
                                <form id="payment-form">
                                    <div class="mb-3">
                                        <label class="form-label">Card Information <span class="text-danger">*</span></label>
                                        <div id="card-element"></div>
                                        <div id="card-errors" role="alert"></div>
                                    </div>

                                    <div class="alert alert-success">
                                        <i class="ti ti-shield-check"></i>
                                        <small>Your payment is secure and encrypted. You can cancel anytime.</small>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" id="submit-button" class="btn btn-primary btn-lg fw-semibold">
                                            <span id="button-text">Subscribe for $10/month</span>
                                            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                        <a href="/dashboards/logout.php" class="btn btn-outline-secondary">
                                            Log Out
                                        </a>
                                    </div>
                                </form>

                                <p class="text-muted text-center mt-4 mb-0 small">
                                    By subscribing, you agree to our Terms of Service and Privacy Policy.
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

<script src="/dashboards/js/vendors.min.js"></script>
<script src="/dashboards/js/app.js"></script>
<script src="/dashboards/plugins/toastr/js/toastr.min.js"></script>

<script>
    // Initialize Stripe
    const stripe = Stripe('<?= StripeConfig::getPublishableKey() ?>');
    const elements = stripe.elements();

    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Inter", sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#dc3545'
            }
        }
    });

    cardElement.mount('#card-element');

    // Handle card errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Disable submit button
        submitButton.disabled = true;
        buttonText.classList.add('d-none');
        spinner.classList.remove('d-none');

        try {
            // Create payment method
            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    email: '<?= htmlspecialchars($user['Email']) ?>',
                    name: '<?= htmlspecialchars($user['Name']) ?>'
                }
            });

            if (error) {
                throw new Error(error.message);
            }

            // Submit payment method to server
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'payment_method_id=' + paymentMethod.id
            });

            const result = await response.json();

            if (result.success) {
                toastr.options = {
                    "positionClass": "toast-top-center",
                    "timeOut": "3000",
                    "onHidden": function() {
                        window.location.href = '/dashboards/speechapp.php';
                    }
                };
                toastr.success('Payment successful! Redirecting to your dashboard...');
            } else {
                throw new Error(result.error || 'Payment failed');
            }

        } catch (error) {
            console.error('Payment error:', error);
            toastr.options = { "positionClass": "toast-top-center", "timeOut": "5000" };
            toastr.error(error.message || 'Payment failed. Please try again.');

            // Re-enable submit button
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
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

