<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');
require_once('/opt/mka/core/Billing/SLPBilling.php');

use MKA\Payment\StripeConfig;
use MKA\Billing\SLPBilling;

// Get the session ID from URL
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    header('Location: /dashboards/admin_users.php?error=no_session');
    exit;
}

try {
    global $pdo;

    // Set Stripe API key
    \Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());

    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    if (!$session) {
        throw new Exception('Invalid session');
    }

    $slpUuid = $session->client_reference_id;
    $customerId = $session->customer;
    $subscriptionId = $session->subscription;

    // Retrieve the subscription to get more details
    $subscription = \Stripe\Subscription::retrieve($subscriptionId);

    // Get pack size and addon type from metadata
    $packSize = (int)($session->metadata->pack_size ?? 0);
    $addonType = $session->metadata->addon_type ?? '';

    if ($addonType !== 'capacity_pack' || !in_array($packSize, [1, 5, 10])) {
        throw new Exception('Invalid capacity pack purchase');
    }

    // Get price from pack size
    $priceMonthly = SLPBilling::PACK_PRICING[$packSize];

    // Generate addon UUID
    $addonUuid = bin2hex(random_bytes(16));

    // Check if this subscription already exists
    $stmt = $pdo->prepare("
        SELECT addon_uuid 
        FROM slp_capacity_addons 
        WHERE stripe_subscription_id = ?
        LIMIT 1
    ");
    $stmt->execute([$subscriptionId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing addon
        $stmt = $pdo->prepare("
            UPDATE slp_capacity_addons 
            SET status = 'active',
                updated_at = NOW()
            WHERE addon_uuid = ?
        ");
        $stmt->execute([$existing['addon_uuid']]);

        $message = "Capacity pack reactivated successfully!";

    } else {
        // Insert new capacity addon
        $stmt = $pdo->prepare("
            INSERT INTO slp_capacity_addons 
            (addon_uuid, slp_uuid, pack_size, price_monthly, stripe_subscription_id, status, purchased_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");

        $stmt->execute([
            $addonUuid,
            $slpUuid,
            $packSize,
            $priceMonthly,
            $subscriptionId
        ]);

        $message = "Capacity pack purchased successfully! You now have {$packSize} additional patient slot" . ($packSize > 1 ? 's' : '') . ".";
    }

    // Update or create stripe_customer_id in user_subscriptions if not exists
    $stmt = $pdo->prepare("
        SELECT subscription_uuid 
        FROM user_subscriptions 
        WHERE user_uuid = ? 
        LIMIT 1
    ");
    $stmt->execute([$slpUuid]);
    $userSub = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userSub) {
        // Update existing
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET stripe_customer_id = ?
            WHERE subscription_uuid = ?
        ");
        $stmt->execute([$customerId, $userSub['subscription_uuid']]);
    } else {
        // This shouldn't normally happen for SLPs, but just in case
        error_log("Warning: SLP {$slpUuid} purchased capacity pack but has no base subscription record");
    }

    // Get updated capacity info
    $totalCapacity = SLPBilling::getTotalCapacity($slpUuid, $pdo);
    $affiliatedCount = SLPBilling::getAffiliatedPatientCount($slpUuid, $pdo);
    $availableSlots = SLPBilling::getAvailableSlots($slpUuid, $pdo);
    $monthlyBill = SLPBilling::calculateMonthlyBill($slpUuid, $pdo);

    // Set success message with capacity details
    $_SESSION['toast_success'] = $message . " Total capacity: {$totalCapacity} slots ({$availableSlots} available). Current bill: $" . number_format($monthlyBill, 2) . "/month.";

    // Redirect to admin_users page
    header('Location: /dashboards/admin_users.php');
    exit;

} catch (Exception $e) {
    error_log("Capacity pack success handler error: " . $e->getMessage());
    $_SESSION['toast_error'] = 'There was an error processing your capacity pack purchase. Please contact support.';
    header('Location: /dashboards/admin_users.php');
    exit;
}
