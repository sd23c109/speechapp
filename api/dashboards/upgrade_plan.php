<?php
session_start();
require_once('/opt/mka/bootstrap.php');
use MKA\Paypal\PaypalSubscriptionManager;
use MKA\Tasks\CheckSubscriptionLimits;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId = $_POST['subscription_id'] ?? '';
    $newTier = $_POST['new_tier'] ?? '';

    if ($subId && $newTier) {
        $result = PaypalSubscriptionManager::changeSubscription($subId, $newTier); // Works for upgrades too

        if ($result) {
            // Update subscription tier in DB
            $stmt = $GLOBALS['pdo']->prepare("
                UPDATE user_subscriptions 
                SET tier_uuid = (SELECT tier_uuid FROM product_tiers WHERE slug = ? LIMIT 1),
                    updated_at = NOW()
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$newTier, $subId]);

            $_SESSION['flash_message'] = "Your plan has been successfully upgraded to {$newTier}. The new rate will be charged on your next billing cycle.";
            $user_uuid = $_SESSION['user_data']['user_uuid'];
            CheckSubscriptionLimits::enforceFormLimit($user_uuid);

            $_SESSION['flash_message'] .= " Your plan has been updated. You can enable more forms now.";
            
        } else {
            $_SESSION['flash_message'] = "Failed to upgrade plan. Please try again or contact support.";
        }
    }
}
header("Location: /dashboards/profile.php");
exit;
