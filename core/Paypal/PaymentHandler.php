<?php
namespace MKA\Paypal;

class PaymentHandler {

    public static function storePayment(array $data) {
        global $pdo;

        $useruuid = self::getUserUUIDBySubscription($data['recurring_payment_id'] ?? '');

        if (!$useruuid) {
            error_log("No user matched for subscription ID: " . ($data['recurring_payment_id'] ?? ''));
            return false;
        }
        
        $stmt = $pdo->prepare("SELECT 1 FROM mka_payments WHERE TxnID = ?");
        $stmt->execute([$data['txn_id'] ?? '']);
        if ($stmt->fetch()) {
            error_log("Duplicate payment txn_id detected: " . $data['txn_id']);
            return false;
        }

        $stmt = $pdo->prepare("
            INSERT INTO mka_payments (
                UserUUID, TxnID, SubscriptionID, PaymentStatus, PaymentAmount,
                PaymentCurrency, TxnType, PaymentDate, RawPost
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $useruuid,
            $data['txn_id'] ?? '',
            $data['recurring_payment_id'] ?? '',
            $data['payment_status'] ?? 'Pending',
            $data['mc_gross'] ?? 0.00,
            $data['mc_currency'] ?? 'USD',
            $data['txn_type'] ?? '',
            date('Y-m-d H:i:s', strtotime($data['payment_date'] ?? 'now')),
            json_encode($data)
        ]);
    }

    private static function getUserUUIDBySubscription($subscriptionId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT user_uuid FROM user_subscriptions WHERE paypal_subscription_id = ?");
        $stmt->execute([$subscriptionId]);
        return $stmt->fetchColumn();
    }
    
   
}
