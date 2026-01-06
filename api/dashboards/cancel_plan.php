<?php
session_start();
require_once('/opt/mka/bootstrap.php');
use MKA\Paypal\PaypalSubscriptionManager;
use MKA\Tasks\CheckSubscriptionLimits;
use MKA\Log\MKALogger;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId = $_POST['subscription_id'] ?? '';

    if ($subId) {
        $result = PaypalSubscriptionManager::cancelSubscription($subId);

        if ($result) {
            // Update DB to mark subscription as cancelled
            $stmt = $GLOBALS['pdo']->prepare("
                UPDATE user_subscriptions 
                SET status = 'canceled', updated_at = NOW() 
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subId]);
            
            $stmt = $GLOBALS['pdo']->prepare("
                SELECT user_uuid
                FROM user_subscriptions 
                WHERE paypal_subscription_id = ?
            ");
            $stmt->execute([$subId]);
            $user_uuid = $stmt->fetchColumn();
            
            $stmt = $GLOBALS['pdo']->prepare("
                UPDATE mka_users
                SET IsPaid='n', IsTrial='n'
                WHERE UserUUID = ?
            ");
            $stmt->execute([$user_uuid]);
            
             CheckSubscriptionLimits::enforceFormLimit($user_uuid);
             MKALogger::log('subscription_canceled', [
                'user_uuid' => $user_uuid,
                'paypal_subscription_id' => $subId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
             // Destroy session to log them out
            session_unset();
            session_destroy();

            header("Location: /dashboards/account_inactive.php");
            exit;
        } else {
            $_SESSION['flash_message'] = "Failed to cancel subscription. Please try again or contact support.";
        }
    }
}
header("Location: /dashboards/profile.php");
exit;

