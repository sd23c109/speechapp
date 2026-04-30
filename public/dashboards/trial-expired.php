<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');

use MKA\Payment\StripeConfig;

$userUuid = $_COOKIE['mka_user_uuid'] ?? $_SESSION['user_data']['user_uuid'] ?? null;

if (!$userUuid) {
    header('Location: /dashboards/login.php');
    exit;
}

global $pdo;

$stmt = $pdo->prepare("
    SELECT u.*,
           us.expires_at as trial_expired_at,
           us.status as sub_status
    FROM mka_users u
    LEFT JOIN user_subscriptions us ON u.UserUUID = us.user_uuid AND us.status = 'trial'
    WHERE u.UserUUID = ?
    LIMIT 1
");
$stmt->execute([$userUuid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /dashboards/login.php');
    exit;
}

// SLP affiliation (relevant for end_users)
$affiliation = null;
if ($user['user_type'] === 'end_user') {
    $stmt = $pdo->prepare("
        SELECT u.Name as slp_name
        FROM patient_affiliations pa
        JOIN mka_users u ON pa.slp_uuid = u.UserUUID
        WHERE pa.patient_uuid = ? AND pa.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$userUuid]);
    $affiliation = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stripe = StripeConfig::getClient();

// Handle payment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method_id'], $_POST['selected_plan'])) {
    header('Content-Type: application/json');
    try {
        $selectedPlan = $_POST['selected_plan'];
        if (!in_array($selectedPlan, ['slp', 'patient'])) {
            throw new Exception('Invalid plan selected.');
        }

        $isSLPPlan  = ($selectedPlan === 'slp');
        $priceId    = $isSLPPlan ? StripeConfig::PRICE_SLP    : StripeConfig::PRICE_PATIENT;
        $tierUuid   = $isSLPPlan ? 'TIER-ENTERPRISE-0000-000000000004' : 'TIER-LITE-0000-000000000001';
        $newUserType = $isSLPPlan ? 'enterprise_admin' : 'end_user';

        $pdo->beginTransaction();

        // Create or retrieve Stripe customer
        $stmt = $pdo->prepare("SELECT stripe_customer_id FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $stripeCustomerId = $stmt->fetchColumn();

        if (!$stripeCustomerId) {
            $customer = $stripe->customers->create([
                'email'            => $user['Email'],
                'name'             => $user['Name'],
                'payment_method'   => $_POST['payment_method_id'],
                'invoice_settings' => ['default_payment_method' => $_POST['payment_method_id']],
            ]);
            $stripeCustomerId = $customer->id;
            $stmt = $pdo->prepare("UPDATE mka_users SET stripe_customer_id = ? WHERE UserUUID = ?");
            $stmt->execute([$stripeCustomerId, $userUuid]);
        } else {
            $stripe->paymentMethods->attach($_POST['payment_method_id'], ['customer' => $stripeCustomerId]);
            $stripe->customers->update($stripeCustomerId, [
                'invoice_settings' => ['default_payment_method' => $_POST['payment_method_id']],
            ]);
        }

        $subscription = $stripe->subscriptions->create([
            'customer' => $stripeCustomerId,
            'items'    => [['price' => $priceId]],
            'expand'   => ['latest_invoice.payment_intent'],
        ]);

        // Update subscription row
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions
            SET status = 'active',
                payment_provider = 'stripe',
                stripe_subscription_id = ?,
                stripe_price_id = ?,
                tier_uuid = ?,
                started_at = NOW(),
                expires_at = NULL
            WHERE user_uuid = ? AND status = 'trial'
        ");
        $stmt->execute([$subscription->id, $priceId, $tierUuid, $userUuid]);

        // Update user — mark paid, update user_type if switching plans
        $stmt = $pdo->prepare("
            UPDATE mka_users SET Status = 'active', IsPaid = 'y', user_type = ? WHERE UserUUID = ?
        ");
        $stmt->execute([$newUserType, $userUuid]);

        // Clear API key expiry
        $stmt = $pdo->prepare("UPDATE mka_api_keys SET status = 'active', expires_at = NULL WHERE user_uuid = ?");
        $stmt->execute([$userUuid]);

        $pdo->commit();

        echo json_encode(['success' => true, 'subscription_id' => $subscription->id]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Default recommended plan based on trial type
$defaultPlan = ($user['user_type'] === 'enterprise_admin') ? 'slp' : 'patient';
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
        .auth-brand img { height: 100px; width: auto; border-radius: 12px; object-fit: contain; }
        .plan-card {
            border: 2px solid #dee2e6;
            border-radius: 0.75rem;
            padding: 1.25rem;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }
        .plan-card:hover { border-color: #0d6efd; }
        .plan-card.selected { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
        .plan-card.selected .plan-radio { accent-color: #0d6efd; }
        .plan-price { font-size: 2rem; font-weight: 700; color: #0d6efd; }
        .feature-check { color: #198754; margin-right: 6px; }
        .recommended-badge { font-size: .7rem; }
        #card-element {
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background: white;
        }
        #card-errors { color: #dc3545; margin-top: 8px; font-size: 0.875rem; }
        .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.15em; }
    </style>
</head>
<body>
<div class="auth-box d-flex align-items-center py-4">
    <div class="container-xxl">
        <div class="row align-items-center justify-content-center">
            <div class="col-xl-10">
                <div class="card rounded-4">
                    <div class="row justify-content-between g-0">
                        <div class="col-lg-7">
                            <div class="card-body px-4 py-4">

                                <div class="auth-brand text-center mb-3">
                                    <a href="index.php">
                                        <img src="/dashboards/img/Logo1.png" alt="logo" height="100">
                                    </a>
                                    <h4 class="fw-bold mt-3 text-danger">
                                        <i class="ti ti-clock-exclamation"></i> Your Trial Has Expired
                                    </h4>
                                    <p class="text-muted mb-0">Choose the plan that's right for you and continue without interruption.</p>
                                </div>

                                <?php if ($user['trial_expired_at']): ?>
                                    <div class="alert alert-warning py-2 mb-3">
                                        <i class="ti ti-info-circle"></i>
                                        Trial expired on <strong><?= date('F j, Y', strtotime($user['trial_expired_at'])) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if ($affiliation): ?>
                                    <div class="alert alert-info py-2 mb-3">
                                        <i class="ti ti-user-heart"></i>
                                        <strong>Your Speech Therapist:</strong> <?= htmlspecialchars($affiliation['slp_name']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Plan Selection -->
                                <p class="fw-semibold mb-2">Select your plan:</p>
                                <div class="row g-3 mb-4">

                                    <!-- Patient Plan -->
                                    <div class="col-md-6">
                                        <div class="plan-card <?= $defaultPlan === 'patient' ? 'selected' : '' ?>"
                                             id="card-patient" onclick="selectPlan('patient')">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <input class="plan-radio me-1" type="radio" name="plan"
                                                           id="plan-patient" value="patient"
                                                           <?= $defaultPlan === 'patient' ? 'checked' : '' ?>>
                                                    <label class="fw-semibold" for="plan-patient">Patient / Parent</label>
                                                </div>
                                                <?php if ($defaultPlan === 'patient'): ?>
                                                    <span class="badge bg-primary recommended-badge">Recommended</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="plan-price">$10<span class="fs-6 text-muted fw-normal">/mo</span></div>
                                            <ul class="list-unstyled mt-2 mb-0 small">
                                                <li><i class="ti ti-check feature-check"></i>Unlimited speech exercises</li>
                                                <li><i class="ti ti-check feature-check"></i>Progress tracking</li>
                                                <li><i class="ti ti-check feature-check"></i>All therapy modules</li>
                                                <li><i class="ti ti-check feature-check"></i>SLP connection support</li>
                                                <li><i class="ti ti-check feature-check"></i>Cancel anytime</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- SLP Plan -->
                                    <div class="col-md-6">
                                        <div class="plan-card <?= $defaultPlan === 'slp' ? 'selected' : '' ?>"
                                             id="card-slp" onclick="selectPlan('slp')">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <input class="plan-radio me-1" type="radio" name="plan"
                                                           id="plan-slp" value="slp"
                                                           <?= $defaultPlan === 'slp' ? 'checked' : '' ?>>
                                                    <label class="fw-semibold" for="plan-slp">Speech-Language Pathologist</label>
                                                </div>
                                                <?php if ($defaultPlan === 'slp'): ?>
                                                    <span class="badge bg-primary recommended-badge">Recommended</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="plan-price">$100<span class="fs-6 text-muted fw-normal">/mo</span></div>
                                            <ul class="list-unstyled mt-2 mb-0 small">
                                                <li><i class="ti ti-check feature-check"></i>Manage unlimited patients</li>
                                                <li><i class="ti ti-check feature-check"></i>Custom exercise programs</li>
                                                <li><i class="ti ti-check feature-check"></i>Full analytics dashboard</li>
                                                <li><i class="ti ti-check feature-check"></i>All therapy content</li>
                                                <li><i class="ti ti-check feature-check"></i>Priority support</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Form -->
                                <form id="payment-form">
                                    <input type="hidden" id="selected-plan-input" name="selected_plan" value="<?= $defaultPlan ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Card Information <span class="text-danger">*</span></label>
                                        <div id="card-element"></div>
                                        <div id="card-errors" role="alert"></div>
                                    </div>
                                    <div class="alert alert-success py-2">
                                        <i class="ti ti-shield-check"></i>
                                        <small>Secure, encrypted payment. Cancel anytime.</small>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" id="submit-button" class="btn btn-primary btn-lg fw-semibold">
                                            <span id="button-text">Subscribe for $<?= $defaultPlan === 'slp' ? '100' : '10' ?>/month</span>
                                            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </button>
                                        <a href="/dashboards/logout.php" class="btn btn-outline-secondary">Log Out</a>
                                    </div>
                                </form>

                                <p class="text-muted text-center mt-3 mb-0 small">
                                    By subscribing, you agree to our Terms of Service and Privacy Policy.
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-5 d-none d-lg-block">
                            <div class="h-100 position-relative card-side-img rounded-end-4 overflow-hidden"></div>
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
    const planPrices = { patient: '$10', slp: '$100' };

    function selectPlan(plan) {
        document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('card-' + plan).classList.add('selected');
        document.getElementById('plan-' + plan).checked = true;
        document.getElementById('selected-plan-input').value = plan;
        document.getElementById('button-text').textContent = 'Subscribe for ' + planPrices[plan] + '/month';
    }

    const stripe = Stripe('<?= StripeConfig::getPublishableKey() ?>');
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: { fontSize: '16px', color: '#32325d', fontFamily: '"Inter", sans-serif', '::placeholder': { color: '#aab7c4' } },
            invalid: { color: '#dc3545' }
        }
    });
    cardElement.mount('#card-element');
    cardElement.on('change', e => {
        document.getElementById('card-errors').textContent = e.error ? e.error.message : '';
    });

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        submitButton.disabled = true;
        buttonText.classList.add('d-none');
        spinner.classList.remove('d-none');

        try {
            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    email: '<?= htmlspecialchars($user['Email']) ?>',
                    name: '<?= htmlspecialchars($user['Name']) ?>'
                }
            });

            if (error) throw new Error(error.message);

            const selectedPlan = document.getElementById('selected-plan-input').value;
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'payment_method_id=' + encodeURIComponent(paymentMethod.id)
                    + '&selected_plan=' + encodeURIComponent(selectedPlan)
            });

            const result = await response.json();
            if (result.success) {
                toastr.options = {
                    positionClass: 'toast-top-center', timeOut: '3000',
                    onHidden: () => window.location.href = '/dashboards/speechapp.php'
                };
                toastr.success('Payment successful! Redirecting...');
            } else {
                throw new Error(result.error || 'Payment failed');
            }
        } catch (error) {
            toastr.options = { positionClass: 'toast-top-center', timeOut: '5000' };
            toastr.error(error.message || 'Payment failed. Please try again.');
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
        }
    });
</script>

<?php if (!empty($_SESSION['toast_error'])): ?>
    <script>
        $(document).ready(function() {
            toastr.options = { positionClass: 'toast-top-center', timeOut: '5000' };
            toastr.error("<?= addslashes($_SESSION['toast_error']) ?>");
        });
    </script>
    <?php unset($_SESSION['toast_error']); endif; ?>

</body>
</html>
