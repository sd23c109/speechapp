<?php
session_start();
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/core/Paypal/PaypalSubscriptionManager.php');
use MKA\Paypal\PaypalSubscriptionManager;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId = $_POST['subscription_id'] ?? '';
    $newTier = $_POST['new_tier'] ?? '';
    $newTier = strtolower($newTier);
    error_log('VARS ARE '.$subId.' AND TIER '.$newTier);
        
    if ($subId && $newTier) {
        $result = PaypalSubscriptionManager::changeSubscription($subId, $newTier);

        if ($result) {
            // Update DB record
            $stmt = $GLOBALS['pdo']->prepare("
                UPDATE user_subscriptions 
                SET tier_uuid = (select tier_uuid from product_tiers where slug = ? LIMIT 1), updated_at = NOW() 
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$newTier, $subId]);

            $_SESSION['flash_message'] = "Plan downgraded successfully.";
            $user_uuid = $_SESSION['user_data']['user_uuid'];
            CheckSubscriptionLimits::enforceFormLimit($user_uuid);

            $_SESSION['flash_message'] .= " Your plan has been updated. Any forms over the limit have been disabled.";
        
        } else {
            $_SESSION['flash_message'] = "Failed to downgrade plan. Please try again or contact support.";
        }
    }
}


header("Location: /dashboards/profile.php");
exit;

