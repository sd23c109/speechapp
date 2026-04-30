<?php
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');
require_once('/opt/mka/core/Log/MKALogger.php');
require_once('/opt/mka/core/Billing/SLPBilling.php');

use MKA\Billing\SLPBilling;

use MKA\Payment\StripeConfig;
use MKA\Log\MKALogger;

// Get the raw POST body
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

\Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());

try {
    // Verify webhook signature
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        StripeConfig::WEBHOOK_SECRET
    );

    global $pdo;

    // Handle different event types
    switch ($event->type) {

        case 'checkout.session.completed':
            $session = $event->data->object;
            handleCheckoutCompleted($pdo, $session);
            break;

        case 'customer.subscription.created':
            $subscription = $event->data->object;
            handleSubscriptionCreated($pdo, $subscription);
            break;

        case 'customer.subscription.updated':
        case 'customer.subscription.paused':
        case 'customer.subscription.resumed':
            $subscription = $event->data->object;
            handleSubscriptionUpdated($pdo, $subscription);
            break;

        case 'customer.subscription.deleted':
            $subscription = $event->data->object;
            handleSubscriptionDeleted($pdo, $subscription);
            break;

        case 'invoice.created':
            $invoice = $event->data->object;
            handleInvoiceCreated($pdo, $invoice);
            break;

        case 'invoice.payment_succeeded':
            $invoice = $event->data->object;
            handlePaymentSucceeded($pdo, $invoice);
            break;

        case 'invoice.payment_failed':
            $invoice = $event->data->object;
            handlePaymentFailed($pdo, $invoice);
            break;

        default:
            MKALogger::log('stripe_webhook_unhandled', [
                'event_type' => $event->type
            ]);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    error_log('Stripe webhook signature verification failed: ' . $e->getMessage());
    echo json_encode(['error' => 'Invalid signature']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Stripe webhook error: ' . $e->getMessage());
    echo json_encode(['error' => 'Webhook handler failed']);
}

// ===== HELPER FUNCTIONS =====

function handleCheckoutCompleted($pdo, $session) {
    $userUUID = $session->client_reference_id ?? $session->metadata->user_uuid ?? null;

    if (!$userUUID) {
        error_log('Checkout completed but no user_uuid found');
        return;
    }

    MKALogger::log('stripe_checkout_completed', [
        'user_uuid' => $userUUID,
        'session_id' => $session->id,
        'customer_id' => $session->customer
    ]);
}

function handleCapacityPackCreated($pdo, $subscription) {
    $slpUuid = $subscription->metadata->slp_uuid ?? null;
    $packSize = (int)($subscription->metadata->pack_size ?? 0);

    if (!$slpUuid || !in_array($packSize, [1, 5, 10])) {
        error_log("Invalid capacity pack subscription metadata");
        return;
    }

    try {
        // Get price from pack size
        $priceMonthly = SLPBilling::PACK_PRICING[$packSize];
        $addonUuid = generateUUID();

        $stmt = $pdo->prepare("
            INSERT INTO slp_capacity_addons 
            (addon_uuid, slp_uuid, pack_size, price_monthly, stripe_subscription_id, status, purchased_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ON DUPLICATE KEY UPDATE
                status = 'active',
                updated_at = NOW()
        ");

        $stmt->execute([
            $addonUuid,
            $slpUuid,
            $packSize,
            $priceMonthly,
            $subscription->id
        ]);

        MKALogger::log('capacity_pack_created', [
            'slp_uuid' => $slpUuid,
            'pack_size' => $packSize,
            'subscription_id' => $subscription->id
        ]);

    } catch (Exception $e) {
        error_log('Error creating capacity pack: ' . $e->getMessage());
    }
}

function handleSubscriptionCreated($pdo, $subscription) {
    $userUUID = $subscription->metadata->user_uuid ?? null;
    $userType = $subscription->metadata->user_type ?? 'end_user';
    $addonType = $subscription->metadata->addon_type ?? null;

    // Check if this is a capacity pack subscription
    if ($addonType === 'capacity_pack') {
        handleCapacityPackCreated($pdo, $subscription);
        return;
    }

    if (!$userUUID) {
        error_log('Subscription created but no user_uuid in metadata');
        return;
    }

    try {
        $pdo->beginTransaction();

        // Get base amount
        $baseAmount = StripeConfig::getBaseAmount($userType);

        // Create subscription record
        $subscriptionUUID = generateUUID();

        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions 
            (subscription_uuid, user_uuid, tier_uuid, stripe_subscription_id, stripe_customer_id, 
             stripe_price_id, payment_provider, status, base_amount, discount_amount, final_amount, 
             started_at, expires_at)
            VALUES (?, ?, NULL, ?, ?, ?, 'stripe', ?, ?, 0.00, ?, NOW(), ?)
        ");

        $status = ($subscription->status === 'trialing') ? 'trial' : 'active';
        $expiresAt = date('Y-m-d H:i:s', $subscription->current_period_end);

        $stmt->execute([
            $subscriptionUUID,
            $userUUID,
            $subscription->id,
            $subscription->customer,
            $subscription->items->data[0]->price->id,
            $status,
            $baseAmount,
            $baseAmount,
            $expiresAt
        ]);

        // Update user status
        $stmt = $pdo->prepare("
            UPDATE mka_users 
            SET IsPaid = ?, Status = ?, TrialExpires = ?
            WHERE UserUUID = ?
        ");

        $isPaid      = ($subscription->status === 'trialing') ? 'n' : 'y';
        $userStatus  = ($subscription->status === 'trialing') ? 'trial' : 'active';
        $trialExpiry = ($subscription->status === 'trialing') ? $expiresAt : null;

        $stmt->execute([$isPaid, $userStatus, $trialExpiry, $userUUID]);

        $pdo->commit();

        MKALogger::log('stripe_subscription_created', [
            'user_uuid' => $userUUID,
            'subscription_id' => $subscription->id,
            'status' => $status,
            'amount' => $baseAmount
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error creating subscription record: ' . $e->getMessage());
        MKALogger::log('stripe_subscription_create_failed', [
            'user_uuid' => $userUUID,
            'error' => $e->getMessage()
        ]);
    }
}
function handleSubscriptionUpdated($pdo, $subscription) {
    $addonType = $subscription->metadata->addon_type ?? null;

    // Handle capacity pack update
    if ($addonType === 'capacity_pack') {
        try {
            $stmt = $pdo->prepare("
                UPDATE slp_capacity_addons 
                SET status = ?,
                    updated_at = NOW()
                WHERE stripe_subscription_id = ?
            ");

            $newStatus = ($subscription->status === 'active' || $subscription->status === 'trialing') ? 'active' : 'cancelled';
            $stmt->execute([$newStatus, $subscription->id]);

            MKALogger::log('capacity_pack_updated', [
                'subscription_id' => $subscription->id,
                'status' => $newStatus
            ]);

        } catch (Exception $e) {
            error_log('Error updating capacity pack: ' . $e->getMessage());
        }
        return;
    }

    // Handle regular subscription update
    try {
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = ?, expires_at = ?, updated_at = NOW()
            WHERE stripe_subscription_id = ?
        ");

        $status = mapStripeStatus($subscription->status);
        $expiresAt = date('Y-m-d H:i:s', $subscription->current_period_end);

        $stmt->execute([$status, $expiresAt, $subscription->id]);

        MKALogger::log('stripe_subscription_updated', [
            'subscription_id' => $subscription->id,
            'status' => $status
        ]);

    } catch (Exception $e) {
        error_log('Error updating subscription: ' . $e->getMessage());
    }
}

function handleSubscriptionDeleted($pdo, $subscription) {
    $addonType = $subscription->metadata->addon_type ?? null;

    // Handle capacity pack deletion
    if ($addonType === 'capacity_pack') {
        try {
            $stmt = $pdo->prepare("
                UPDATE slp_capacity_addons 
                SET status = 'cancelled',
                    cancelled_at = NOW(),
                    updated_at = NOW()
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([$subscription->id]);

            MKALogger::log('capacity_pack_deleted', [
                'subscription_id' => $subscription->id
            ]);

        } catch (Exception $e) {
            error_log('Error deleting capacity pack: ' . $e->getMessage());
        }
        return;
    }

    // Handle regular subscription deletion
    try {
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW()
            WHERE stripe_subscription_id = ?
        ");
        $stmt->execute([$subscription->id]);

        // Also update user status
        $stmt = $pdo->prepare("
            UPDATE mka_users u
            JOIN user_subscriptions s ON u.UserUUID = s.user_uuid
            SET u.IsPaid = 'n'
            WHERE s.stripe_subscription_id = ?
        ");
        $stmt->execute([$subscription->id]);

        MKALogger::log('stripe_subscription_deleted', [
            'subscription_id' => $subscription->id
        ]);

    } catch (Exception $e) {
        error_log('Error deleting subscription: ' . $e->getMessage());
    }
}
function handlePaymentSucceeded($pdo, $invoice) {
    $subscriptionId = $invoice->subscription ?? null;

    if (!$subscriptionId) return;

    try {
        // Update subscription status to active
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'active', updated_at = NOW()
            WHERE stripe_subscription_id = ?
        ");
        $stmt->execute([$subscriptionId]);

        // Also update mka_users so IsPaid and Status are in sync
        $stmt = $pdo->prepare("
            UPDATE mka_users u
            JOIN user_subscriptions s ON u.UserUUID = s.user_uuid
            SET u.IsPaid = 'y', u.Status = 'active', u.TrialExpires = NULL
            WHERE s.stripe_subscription_id = ?
        ");
        $stmt->execute([$subscriptionId]);

        // Check if this is a patient payment and apply SLP credit
        $stmt = $pdo->prepare("
            SELECT user_uuid 
            FROM user_subscriptions 
            WHERE stripe_subscription_id = ?
        ");
        $stmt->execute([$subscriptionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            $patientUuid = $sub['user_uuid'];

            // Check if patient is affiliated with an SLP
            $stmt = $pdo->prepare("
                SELECT slp_uuid 
                FROM patient_affiliations 
                WHERE patient_uuid = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$patientUuid]);
            $affiliation = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($affiliation) {
                $slpUuid = $affiliation['slp_uuid'];

                // Calculate SLP's new bill (automatically includes credit)
                $newBill = SLPBilling::calculateMonthlyBill($slpUuid, $pdo);

                MKALogger::log('slp_credit_applied', [
                    'patient_uuid' => $patientUuid,
                    'slp_uuid' => $slpUuid,
                    'slp_new_bill' => $newBill
                ]);
            }
        }

        MKALogger::log('stripe_payment_succeeded', [
            'subscription_id' => $subscriptionId,
            'amount' => $invoice->amount_paid / 100
        ]);

    } catch (Exception $e) {
        error_log('Error processing payment success: ' . $e->getMessage());
    }
}
function handlePaymentFailed($pdo, $invoice) {
    $subscriptionId = $invoice->subscription ?? null;

    if (!$subscriptionId) return;

    MKALogger::log('stripe_payment_failed', [
        'subscription_id' => $subscriptionId,
        'amount' => $invoice->amount_due / 100
    ]);
}

function handleInvoiceCreated($pdo, $invoice) {
    // Only act on draft invoices (invoice.created always fires in draft state)
    if (($invoice->status ?? '') !== 'draft') return;

    $subscriptionId = $invoice->subscription ?? null;
    if (!$subscriptionId) return;

    // Look up subscription in our DB — only matches SLP base subscriptions
    // (capacity packs are stored in slp_capacity_addons, not user_subscriptions)
    $stmt = $pdo->prepare("
        SELECT u.UserUUID, u.user_type
        FROM user_subscriptions us
        JOIN mka_users u ON u.UserUUID = us.user_uuid
        WHERE us.stripe_subscription_id = ?
          AND us.status IN ('active', 'trial')
        LIMIT 1
    ");
    $stmt->execute([$subscriptionId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Credits only apply to SLP (enterprise_admin) base subscriptions
    if (!$user || $user['user_type'] !== 'enterprise_admin') return;

    $slpUuid = $user['UserUUID'];
    $paidCount = SLPBilling::getPaidPatientCount($slpUuid, $pdo);

    if ($paidCount <= 0) return;

    $creditCents = (int) round($paidCount * SLPBilling::CREDIT_PER_PATIENT * 100);
    $label = $paidCount . ' patient' . ($paidCount > 1 ? 's' : '') . ' × $' . number_format(SLPBilling::CREDIT_PER_PATIENT, 2);

    try {
        \Stripe\InvoiceItem::create([
            'customer'    => $invoice->customer,
            'invoice'     => $invoice->id,
            'amount'      => -$creditCents,
            'currency'    => 'usd',
            'description' => "Patient affiliation credit ({$label})",
        ]);

        MKALogger::log('slp_invoice_credit_applied', [
            'slp_uuid'      => $slpUuid,
            'invoice_id'    => $invoice->id,
            'patient_count' => $paidCount,
            'credit_amount' => $creditCents / 100,
        ]);
    } catch (Exception $e) {
        error_log("Failed to apply SLP invoice credit for {$slpUuid}: " . $e->getMessage());
    }
}

function mapStripeStatus($stripeStatus) {
    $statusMap = [
        'active' => 'active',
        'trialing' => 'trial',
        'canceled' => 'cancelled',
        'incomplete' => 'suspended',
        'incomplete_expired' => 'expired',
        'past_due' => 'suspended',
        'unpaid' => 'suspended',
        'paused' => 'suspended'
    ];

    return $statusMap[$stripeStatus] ?? 'suspended';
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}