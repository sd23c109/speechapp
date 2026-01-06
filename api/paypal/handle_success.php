<?php
require_once('../../bootstrap.php');
require_once('/opt/mka/core/Paypal/PaypalSubscriptionManager.php');
require_once('/opt/mka/core/Tasks/CheckSubscriptionLimits.php');
require_once('/opt/mka/core/Log/MKALogger.php');

use MKA\Paypal\PaypalSubscriptionManager;
use MKA\Tasks\CheckSubscriptionLimits;
use MKA\Log\MKALogger;

header('Content-Type: application/json');



$data = json_decode(file_get_contents('php://input'), true);
 error_log('HANDLE REACTIVATE SUCCESS');
 error_log(json_encode($data));
 
$subscriptionID = $data['subscriptionID'] ?? '';
$user_uuid      = $data['user_uuid'] ?? '';
$tier           = $data['tier'] ?? '';

if (empty($subscriptionID) || empty($user_uuid) || empty($tier)) {
    http_response_code(400);
    echo json_encode(['status' => 'fail', 'message' => 'Missing required fields.']);
    exit;
}

try {
    // Check if this user already has a subscription record
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT COUNT(*) FROM user_subscriptions WHERE user_uuid = ?
    ");
    $stmt->execute([$user_uuid]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        //Get the Tier UUID
        $stmt = $GLOBALS['pdo']->prepare("
        SELECT tier_uuid FROM product_tiers WHERE plan_id = ?
        ");
        $stmt->execute([$tier]);
        $tier_uuid = $stmt->fetchColumn();
        
        
        // 👉 REACTIVATION FLOW
        
        
        $stmt = $GLOBALS['pdo']->prepare("
            UPDATE user_subscriptions
            SET paypal_subscription_id = ?, status = 'active', tier_uuid = ?, updated_at = NOW()
            WHERE user_uuid = ?
        ");
        $stmt->execute([$subscriptionID, $tier_uuid, $user_uuid]);

        // Mark account active
        $stmt = $GLOBALS['pdo']->prepare("UPDATE mka_users SET IsPaid='y', IsTrial='n' WHERE UserUUID=?");
        $stmt->execute([$user_uuid]);

        // Reapply form limits
        CheckSubscriptionLimits::enforceFormLimit($user_uuid);

        MKALogger::log('subscription_reactivated', [
            'user_uuid' => $user_uuid,
            'subscription_id' => $subscriptionID,
            'tier' => $tier
        ]);

    } else {
        // 👉 INITIAL SUBSCRIPTION FLOW
        
        $stmt = $GLOBALS['pdo']->prepare("
          INSERT INTO user_subscriptions
            (user_uuid, paypal_subscription_id, status, tier_uuid, started_at, product_uuid)
          SELECT
            :user_uuid,
            :sub_id,
            'active',
            pt.tier_uuid,
            NOW(),
            pt.product_uuid
          FROM product_tiers pt
          WHERE pt.plan_id = :plan_id
          LIMIT 1
        ");
        $stmt->execute([
          ':user_uuid' => $user_uuid,
          ':sub_id'    => $subscriptionID,
          ':plan_id'   => $tier, // plan_id
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Insert failed: unknown plan_id');
        }


        // Mark user as paid
        $stmt = $GLOBALS['pdo']->prepare("UPDATE mka_users SET IsPaid='y', IsTrial='n' WHERE UserUUID=?");
        $stmt->execute([$user_uuid]);

        CheckSubscriptionLimits::enforceFormLimit($user_uuid);

        MKALogger::log('subscription_activated', [
            'user_uuid' => $user_uuid,
            'subscription_id' => $subscriptionID,
            'tier' => $tier
        ]);
    }

    echo json_encode(['status' => 'ok']);
    exit;

} catch (Exception $e) {
    MKALogger::log('subscription_handle_failed', [
        'user_uuid' => $user_uuid,
        'error' => $e->getMessage()
    ]);
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'message' => 'Internal error: ' . $e->getMessage()]);
    exit;
}
