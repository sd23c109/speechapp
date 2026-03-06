<?php
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');
require_once('/opt/mka/core/Billing/SLPBilling.php');

use MKA\Payment\StripeConfig;
use MKA\Billing\SLPBilling;

header('Content-Type: application/json');

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['slp_uuid']) || !isset($input['pack_size'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$slpUuid = $input['slp_uuid'];
$packSize = (int)$input['pack_size'];

// Validate pack size
if (!in_array($packSize, [1, 5, 10])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid pack size']);
    exit;
}

try {
    global $pdo;

    // Verify SLP exists and is enterprise_admin
    $stmt = $pdo->prepare("SELECT Email, Name, UserType FROM mka_users WHERE UserUUID = ?");
    $stmt->execute([$slpUuid]);
    $slp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slp || $slp['UserType'] !== 'enterprise_admin') {
        throw new Exception('Invalid SLP user');
    }

    // Set Stripe API key
    \Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());

    // Get price ID for this pack size
    $priceId = StripeConfig::getCapacityPackPriceId($packSize);
    if (!$priceId) {
        throw new Exception('Price ID not configured for pack size: ' . $packSize);
    }

    // Get or create Stripe customer
    $stmt = $pdo->prepare("
        SELECT stripe_customer_id 
        FROM user_subscriptions 
        WHERE user_uuid = ? 
        LIMIT 1
    ");
    $stmt->execute([$slpUuid]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['stripe_customer_id']) {
        $customerId = $existing['stripe_customer_id'];
    } else {
        // Create new Stripe customer
        $customer = \Stripe\Customer::create([
            'email' => $slp['Email'],
            'name' => $slp['Name'],
            'metadata' => [
                'user_uuid' => $slpUuid,
                'user_type' => 'enterprise_admin'
            ]
        ]);
        $customerId = $customer->id;
    }

    // Create Checkout Session for capacity pack
    $session = \Stripe\Checkout\Session::create([
        'customer' => $customerId,
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'https://speechapp.virtuopsdev.com/dashboards/capacity_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://speechapp.virtuopsdev.com/dashboards/admin_users.php?canceled=1',
        'client_reference_id' => $slpUuid,
        'subscription_data' => [
            'metadata' => [
                'slp_uuid' => $slpUuid,
                'pack_size' => $packSize,
                'addon_type' => 'capacity_pack'
            ]
        ],
        'metadata' => [
            'slp_uuid' => $slpUuid,
            'pack_size' => $packSize,
            'addon_type' => 'capacity_pack'
        ]
    ]);

    echo json_encode([
        'status' => 'success',
        'session_id' => $session->id
    ]);

} catch (Exception $e) {
    error_log("Capacity pack purchase error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create checkout: ' . $e->getMessage()
    ]);
}
