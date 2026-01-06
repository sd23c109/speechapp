<?php
require_once '/opt/mka/bootstrap.php';
use MKA\Paypal\PaypalSubscriptionManager;

header('Content-Type: application/json');

$subscriptionId = $_POST['subscription_id'] ?? null;

// Optional: You can add auth/validation here if this endpoint is user-facing
if (!$subscriptionId) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Missing subscription ID']);
    exit;
}

if (PaypalSubscriptionManager::cancelSubscription($subscriptionId)) {
    // Update user record
    global $pdo;
    $stmt = $pdo->prepare("UPDATE mka_users SET IsPaid = 'n' WHERE PayPalSubscriptionID = ?");
    $stmt->execute([$subscriptionId]);

    echo json_encode(['status' => 'success', 'message' => 'Subscription cancelled']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'message' => 'Unable to cancel subscription']);
}
