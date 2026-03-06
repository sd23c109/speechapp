<?php
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');

use MKA\Payment\StripeConfig;

header('Content-Type: application/json');

// Check if user is authenticated or has user_uuid from signup
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_uuid']) || !isset($input['user_type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$userUUID = $input['user_uuid'];
$userType = $input['user_type'];
$startMode = $input['start_mode'] ?? 'paid'; // Default to paid if not specified

try {
    global $pdo;

    // Verify user exists
    $stmt = $pdo->prepare("SELECT Email, Name FROM mka_users WHERE UserUUID = ?");
    $stmt->execute([$userUUID]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Set Stripe API key
    \Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());

    // Get price ID based on user type
    $priceId = StripeConfig::getPriceId($userType);

    // Create or get Stripe customer
    $stmt = $pdo->prepare("
        SELECT stripe_customer_id 
        FROM user_subscriptions 
        WHERE user_uuid = ? 
        LIMIT 1
    ");
    $stmt->execute([$userUUID]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['stripe_customer_id']) {
        $customerId = $existing['stripe_customer_id'];
    } else {
        // Create new Stripe customer
        $customer = \Stripe\Customer::create([
            'email' => $user['Email'],
            'name' => $user['Name'],
            'metadata' => [
                'user_uuid' => $userUUID,
                'user_type' => $userType
            ]
        ]);
        $customerId = $customer->id;
    }

    // Build checkout session parameters
    $checkoutParams = [
        'customer' => $customerId,
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'https://speechapp.virtuopsdev.com/dashboards/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://speechapp.virtuopsdev.com/dashboards/signup.php?canceled=1',
        'client_reference_id' => $userUUID,
        'subscription_data' => [
            'metadata' => [
                'user_uuid' => $userUUID,
                'user_type' => $userType,
                'start_mode' => $startMode
            ]
        ],
        'metadata' => [
            'user_uuid' => $userUUID,
            'user_type' => $userType,
            'start_mode' => $startMode
        ]
    ];

    // Only add trial if start_mode is 'trial'
    if ($startMode === 'trial') {
        $checkoutParams['subscription_data']['trial_period_days'] = 14;
    }

    // Create Checkout Session
    $session = \Stripe\Checkout\Session::create($checkoutParams);

    echo json_encode([
        'status' => 'success',
        'session_id' => $session->id
    ]);

} catch (Exception $e) {
    error_log("Stripe checkout error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create checkout session: ' . $e->getMessage()
    ]);
}