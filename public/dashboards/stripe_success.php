<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');
require_once('/opt/mka/core/Log/MKALogger.php');

use MKA\Payment\StripeConfig;
use MKA\Log\MKALogger;

$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    header('Location: /dashboards/login.php?error=no_session');
    exit;
}

try {
    global $pdo;

    \Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());

    $session      = \Stripe\Checkout\Session::retrieve($sessionId);
    $subscription = \Stripe\Subscription::retrieve($session->subscription);

    $userUuid       = $session->client_reference_id;
    $customerId     = $session->customer;
    $subscriptionId = $session->subscription;
    $userType       = $session->metadata->user_type ?? 'end_user';
    $startMode      = $session->metadata->start_mode ?? 'paid';
    $isUpgrade      = ($session->metadata->is_upgrade ?? '') === 'true';

    if (!$userUuid) {
        throw new Exception('No user reference found in checkout session.');
    }

    $subStatus  = ($subscription->status === 'trialing') ? 'trial' : 'active';
    $expiresAt  = date('Y-m-d H:i:s', $subscription->current_period_end);
    $baseAmount = StripeConfig::getBaseAmount($userType);
    $priceId    = $subscription->items->data[0]->price->id;

    $pdo->beginTransaction();

    // 1. Update existing trial subscription OR insert new one
    $stmt = $pdo->prepare("
        SELECT subscription_uuid
        FROM user_subscriptions
        WHERE user_uuid = ? AND status IN ('trial', 'active')
        ORDER BY started_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userUuid]);
    $existingSub = $stmt->fetchColumn();

    if ($existingSub) {
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions
            SET stripe_subscription_id = ?,
                stripe_customer_id     = ?,
                stripe_price_id        = ?,
                payment_provider       = 'stripe',
                status                 = ?,
                expires_at             = ?,
                base_amount            = ?,
                final_amount           = ?,
                updated_at             = NOW()
            WHERE subscription_uuid = ?
        ");
        $stmt->execute([
            $subscriptionId, $customerId, $priceId,
            $subStatus, $expiresAt, $baseAmount, $baseAmount,
            $existingSub
        ]);
    } else {
        $stmt = $pdo->prepare("SELECT tier_uuid FROM product_tiers WHERE plan_id LIKE ? LIMIT 1");
        $stmt->execute([$userType . '%']);
        $tierRow  = $stmt->fetch(PDO::FETCH_ASSOC);
        $tierUuid = $tierRow['tier_uuid'] ?? null;

        $subscriptionUuid = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions
            (subscription_uuid, user_uuid, tier_uuid, stripe_subscription_id, stripe_customer_id,
             stripe_price_id, payment_provider, status, started_at, expires_at,
             base_amount, discount_amount, final_amount, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'stripe', ?, NOW(), ?, ?, 0.00, ?, NOW())
        ");
        $stmt->execute([
            $subscriptionUuid, $userUuid, $tierUuid,
            $subscriptionId, $customerId, $priceId,
            $subStatus, $expiresAt, $baseAmount, $baseAmount
        ]);
    }

    // 2. Mark user as paid and active
    $stmt = $pdo->prepare("
        UPDATE mka_users
        SET Status = 'active', IsPaid = 'y', TrialExpires = NULL
        WHERE UserUUID = ?
    ");
    $stmt->execute([$userUuid]);

    // 3. Remove API key expiry (paid users don't expire)
    $stmt = $pdo->prepare("
        UPDATE mka_api_keys
        SET status = 'active', expires_at = NULL
        WHERE user_uuid = ?
    ");
    $stmt->execute([$userUuid]);

    $pdo->commit();

    // 4. Log user into session
    $stmt = $pdo->prepare("SELECT * FROM mka_users WHERE UserUUID = ?");
    $stmt->execute([$userUuid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_data'] = [
            'user_uuid'           => $user['UserUUID'],
            'email'               => $user['Email'],
            'name'                => $user['Name'],
            'user_type'           => $user['user_type'],
            'plan_name'           => $userType,
            'is_trial'            => 'n',
            'is_paid'             => 'y',
            'trial_expires'       => null,
            'company_name'        => $user['company_name'],
            'company_slug'        => $user['company_slug'],
            'subscription_status' => 'active',
        ];
    }

    MKALogger::log('stripe_payment_success', [
        'user_uuid'       => $userUuid,
        'user_type'       => $userType,
        'is_upgrade'      => $isUpgrade,
        'subscription_id' => $subscriptionId,
        'amount'          => $baseAmount,
    ]);

    $_SESSION['toast_success'] = $isUpgrade
        ? 'Upgrade successful! Welcome back — your account is now active.'
        : 'Payment successful! Welcome to Virtual Speech App.';

    header('Location: /dashboards/speechapp.php');
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Stripe success handler error: " . $e->getMessage());
    $_SESSION['toast_error'] = 'There was an error processing your payment. Please contact support.';
    header('Location: /dashboards/login.php');
    exit;
}
