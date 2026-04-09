<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Payment/StripeConfig.php';
require_once '/opt/mka/vendor/autoload.php';

use MKA\Payment\StripeConfig;
use Stripe\Stripe as StripeClient;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$userType = trim($input['user_type'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
    exit;
}

if (!in_array($userType, ['end_user', 'enterprise_admin'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user type.']);
    exit;
}

// Look up user by email
$pdo  = $GLOBALS['pdo'];
$stmt = $pdo->prepare("SELECT UserUUID, Status, IsPaid FROM mka_users WHERE Email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
    exit;
}

if ($user['IsPaid'] === 'y') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'This account is already active.']);
    exit;
}

// Create Stripe checkout session
StripeClient::setApiKey(StripeConfig::getSecretKey());

try {
    $priceId = StripeConfig::getPriceId($userType);

    $session = \Stripe\Checkout\Session::create([
        'mode'                 => 'subscription',
        'payment_method_types' => ['card'],
        'customer_email'       => $email,
        'client_reference_id'  => $user['UserUUID'],
        'metadata'             => [
            'user_type'  => $userType,
            'is_upgrade' => 'true',
            'start_mode' => 'paid',
        ],
        'line_items' => [[
            'price'    => $priceId,
            'quantity' => 1,
        ]],
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/dashboards/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => 'https://' . $_SERVER['HTTP_HOST'] . '/dashboards/stripe_cancel.php',
    ]);

    echo json_encode(['success' => true, 'session_id' => $session->id]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}