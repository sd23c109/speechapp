<?php
namespace MKA\Paypal;

use MKA\Paypal\PaymentHandler;

class IpnHandler
{
    /**
     * Verifies the IPN message with PayPal.
     */
    public static function verify($raw_post_data)
    {
        
        // Append 'cmd=_notify-validate' to the post string
        $post_data = 'cmd=_notify-validate&' . $raw_post_data;

        // Use sandbox URL for testing, live URL for production
        $paypalUrl = 'https://ipnpb.paypal.com/cgi-bin/webscr';
        // $paypalUrl = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'; // use this if testing with sandbox

        $ch = curl_init($paypalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CAINFO,'/opt/mka/cert/cacert.pem'); // optional, improves SSL verification
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
        $response = curl_exec($ch);
        curl_close($ch);

        return trim($response) === "VERIFIED";
    }

    /**
     * Handles a verified IPN POST from PayPal.
     */
    public static function handle(array $data)
    {
        global $pdo;
        error_log('WHAT IS REACTIVATE???');
        error_log(json_encode($data));
        $txnType = $data['txn_type'] ?? '';
        $subscriptionId = $data['recurring_payment_id'] ?? '';
        $paymentStatus = $data['payment_status'] ?? '';
        $txnId = $data['txn_id'] ?? '';
        $userUUID = $data['custom'] ?? $data['custom_id'] ?? null;

        // Log the raw IPN event to the payments table
        

        // Take actions based on txn_type
       
       switch ($txnType) {
        case 'recurring_payment_profile_created':
            error_log("IPN: New subscription created for {$subscriptionId}");

           /* if ($userUUID) {
                $stmt = $pdo->prepare("UPDATE mka_users SET IsPaid = 'y', IsTrial = 'n' WHERE UserUUID = ?");
                $stmt->execute([ $userUUID]);

                // Insert user_subscriptions row
                $subInsert = $pdo->prepare("INSERT INTO user_subscriptions (user_uuid, paypal_subscription_id, status, activated_at) VALUES (?, ?, 'active', NOW())");
                $subInsert->execute([$userUUID, $subscriptionId]);
                PaymentHandler::storePayment($data);
                error_log("IPN: User {$userUUID} upgraded to paid (signup)"); 
            } */
            break;

        case 'recurring_payment':
            if ($paymentStatus === 'Completed' && $subscriptionId) {
                // Optional: update subscription table status
                error_log("IPN: Attempting Stored Payment for Subscriber ID {$subscriptionId}");
                PaymentHandler::storePayment($data);
                
            }
            break;

        case 'recurring_payment_failed':
            error_log("IPN: Payment failed for {$subscriptionId}");
            break;

        case 'recurring_payment_profile_cancel':
        case 'recurring_payment_expired':
            if ($userUUID) {
                $stmt = $pdo->prepare("UPDATE mka_users SET IsPaid = 'n' WHERE UserUUID = ?");
                $stmt->execute([$userUUID]);

                $subUpdate = $pdo->prepare("UPDATE user_subscriptions SET status = 'canceled', canceled_at = NOW() WHERE user_uuid = ? and paypal_subscription_id= ?");
                $subUpdate->execute([$userUUID,$subscriptionId]);

                error_log("IPN: Subscription canceled or expired for user {$userUUID}");
            }
            break;

        default:
            error_log("NO MATCH FOR IPN: Received txn_type = {$txnType} for sub ID {$subscriptionId}");
            break;
    }
       
       
    }
    
    public static function handleInitial(string $userUUID, string $subscriptionId, string $tier_uuid, string $product_uuid) {
        //need this because Paypal decided to not send custom data back in IPN anymore, but they do send subscription id back.
         global $pdo;
         
       
        if ($userUUID) {
                $stmt = $pdo->prepare("UPDATE mka_users SET IsPaid = 'y', IsTrial = 'n' WHERE UserUUID = ?");
                $stmt->execute([ $userUUID]);
                
                //Check dup first
                
                $check = $pdo->prepare("SELECT 1 FROM user_subscriptions WHERE paypal_subscription_id = ?");
                $check->execute([$subscriptionId]);

                if ($check->fetch()) {
                    error_log("IPN: Subscription ID {$subscriptionId} already exists — skipping insert.");
                    return;
                }

                // Insert user_subscriptions row
                $subInsert = $pdo->prepare("INSERT INTO user_subscriptions (user_uuid, paypal_subscription_id, tier_uuid, product_uuid, status, started_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $subInsert->execute([$userUUID, $subscriptionId, $tier_uuid, $product_uuid]);
                
                error_log("IPN: User {$userUUID} upgraded to paid (signup)"); 
            }
    }
}
