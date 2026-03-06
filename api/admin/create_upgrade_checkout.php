<?php
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');
require_once('/opt/mka/core/Log/MKALogger.php');

use MKA\Payment\StripeConfig;
use MKA\Log\MKALogger;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$userType = $input['user_type'] ?? 'end_user';

// Validate
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

if (!in_array($userType, ['end_user', 'enterprise_admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid plan type.']);
    exit;
}

try {
    global $pdo;

    // Look up user by email
    $stmt = $pdo->prepare("SELECT UserUUID, Name, Email, user_type, Status FROM mka_users WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with that email address.']);
        exit;
    }

    // Only allow upgrade for trial or expired-trial accounts
    // (Status = 'trial' covers active trials and expired ones since we don't flip Status on expiry)
    if (!in_array($user['Status'], ['trial', 'active'])) {
        echo json_encode(['status' => 'error', 'message' => 'This account is not eligible for upgrade.']);
        exit;
    }

    $userUuid = $user['UserUUID'];

    \Stripe\Stripe::setApiKey(StripeConfig::getSecretKey());

    // Reuse existing Stripe customer ID if one exists from a prior checkout attempt
    $stmt = $pdo->prepare("
        SELECT stripe_customer_id
        FROM user_subscriptions
        WHERE user_uuid = ? AND stripe_customer_id IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute([$userUuid]);
    $existingCustomerId = $stmt->fetchColumn();

    if ($existingCustomerId) {
        $customerId = $existingCustomerId;
    } else {
        $customer = \Stripe\Customer::create([
            'email'    => $user['Email'],
            'name'     => $user['Name'],
            'metadata' => ['user_uuid' => $userUuid, 'user_type' => $userType]
        ]);
        $customerId = $customer->id;
    }

    $priceId = StripeConfig::getPriceId($userType);

    $session = \Stripe\Checkout\Session::create([
        'customer'             => $customerId,
        'payment_method_types' => ['card'],
        'line_items'           => [['price' => $priceId, 'quantity' => 1]],
        'mode'                 => 'subscription',
        'success_url'          => 'https://speechapp.virtuopsdev.com/dashboards/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'           => 'https://speechapp.virtuopsdev.com/dashboards/login.php?canceled=1',
        'client_reference_id'  => $userUuid,
        'subscription_data'    => [
            'metadata' => [
                'user_uuid'  => $userUuid,
                'user_type'  => $userType,
                'start_mode' => 'paid',
                'is_upgrade' => 'true'
            ]
        ],
        'metadata' => [
            'user_uuid'  => $userUuid,
            'user_type'  => $userType,
            'start_mode' => 'paid',
            'is_upgrade' => 'true'
        ]
    ]);

    MKALogger::log('upgrade_checkout_created', [
        'user_uuid'   => $userUuid,
        'user_type'   => $userType,
        'session_id'  => $session->id
    ]);

    echo json_encode(['status' => 'success', 'session_id' => $session->id]);

} catch (\Exception $e) {
    error_log("Upgrade checkout error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Could not start checkout. Please try again.']);
}

