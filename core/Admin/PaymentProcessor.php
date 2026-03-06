<?php
namespace MKA\Admin;

use PDO;
use MKA\Log\MKALogger;

class PaymentProcessor {

    /**
     * Process PayPal payment
     */
    public static function processPayPalPayment(array $data): array {
        global $pdo;

        $subscriptionId = $data['subscription_id'] ?? '';
        $userUuid = $data['user_uuid'] ?? '';
        $tierUuid = $data['tier_uuid'] ?? '';
        $amount = $data['amount'] ?? 0;

        try {
            $pdo->beginTransaction();

            // Update subscription
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions
                SET paypal_subscription_id = ?,
                    payment_provider = 'paypal',
                    status = 'active',
                    updated_at = NOW()
                WHERE subscription_uuid = ?
            ");
            $stmt->execute([$subscriptionId, $data['subscription_uuid']]);

            // Update user as paid
            $stmt = $pdo->prepare("
                UPDATE mka_users
                SET IsPaid = 'y', Status = 'n'
                WHERE UserUUID = ?
            ");
            $stmt->execute([$userUuid]);

            // Record transaction
            $transactionId = self::recordTransaction([
                'payer_uuid' => $userUuid,
                'receiver_uuid' => self::getReceiverForPayment($userUuid),
                'amount' => $amount,
                'provider' => 'paypal',
                'provider_transaction_id' => $subscriptionId,
                'subscription_uuid' => $data['subscription_uuid'],
                'tier_uuid' => $tierUuid,
                'status' => 'completed'
            ]);

            $pdo->commit();

            MKALogger::log('payment_processed', [
                'user_uuid' => $userUuid,
                'provider' => 'paypal',
                'amount' => $amount
            ]);

            return ['status' => 'success', 'transaction_id' => $transactionId];

        } catch (\PDOException $e) {
            $pdo->rollBack();
            error_log("PayPal payment error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Payment processing failed'];
        }
    }

    /**
     * Process Stripe payment
     */
    public static function processStripePayment(array $data): array {
        global $pdo;

        $subscriptionId = $data['subscription_id'] ?? '';
        $userUuid = $data['user_uuid'] ?? '';
        $tierUuid = $data['tier_uuid'] ?? '';
        $amount = $data['amount'] ?? 0;

        try {
            $pdo->beginTransaction();

            // Update subscription
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions
                SET stripe_subscription_id = ?,
                    payment_provider = 'stripe',
                    status = 'active',
                    updated_at = NOW()
                WHERE subscription_uuid = ?
            ");
            $stmt->execute([$subscriptionId, $data['subscription_uuid']]);

            // Update user as paid
            $stmt = $pdo->prepare("
                UPDATE mka_users
                SET IsPaid = 'y', Status = 'n'
                WHERE UserUUID = ?
            ");
            $stmt->execute([$userUuid]);

            // Record transaction
            $transactionId = self::recordTransaction([
                'payer_uuid' => $userUuid,
                'receiver_uuid' => self::getReceiverForPayment($userUuid),
                'amount' => $amount,
                'provider' => 'stripe',
                'provider_transaction_id' => $subscriptionId,
                'subscription_uuid' => $data['subscription_uuid'],
                'tier_uuid' => $tierUuid,
                'status' => 'completed'
            ]);

            $pdo->commit();

            MKALogger::log('payment_processed', [
                'user_uuid' => $userUuid,
                'provider' => 'stripe',
                'amount' => $amount
            ]);

            return ['status' => 'success', 'transaction_id' => $transactionId];

        } catch (\PDOException $e) {
            $pdo->rollBack();
            error_log("Stripe payment error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Payment processing failed'];
        }
    }

    /**
     * Record transaction in database
     */
    private static function recordTransaction(array $data): int {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                payer_user_uuid, receiver_user_uuid, amount, currency,
                payment_provider, provider_transaction_id, subscription_uuid,
                tier_uuid, status, completed_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $data['payer_uuid'],
            $data['receiver_uuid'],
            $data['amount'],
            $data['currency'] ?? 'USD',
            $data['provider'],
            $data['provider_transaction_id'],
            $data['subscription_uuid'],
            $data['tier_uuid'],
            $data['status']
        ]);

        return $pdo->lastInsertId();
    }

    /**
     * Get receiver UUID (who gets the money)
     * - End-user pays enterprise admin
     * - Enterprise admin pays super-user
     */
    private static function getReceiverForPayment(string $payerUuid): string {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT parent_user_uuid FROM mka_users WHERE UserUUID = ?
        ");
        $stmt->execute([$payerUuid]);

        return $stmt->fetchColumn() ?: $payerUuid;
    }

    /**
     * Generate PayPal subscription button/link
     */
    public static function generatePayPalButton(string $userUuid, string $tierUuid): array {
        global $pdo;

        // Get tier details
        $stmt = $pdo->prepare("
            SELECT pt.*, u.user_type
            FROM product_tiers pt
            LEFT JOIN mka_users u ON pt.owner_user_uuid = u.UserUUID
            WHERE pt.tier_uuid = ?
        ");
        $stmt->execute([$tierUuid]);
        $tier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tier) {
            return ['status' => 'fail', 'message' => 'Tier not found'];
        }

        // Get user details
        $stmt = $pdo->prepare("SELECT * FROM mka_users WHERE UserUUID = ?");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Determine which PayPal account to use
        $paypalAccount = self::getPayPalAccountForTier($tier);

        return [
            'status' => 'success',
            'plan_id' => $tier['plan_id'],
            'amount' => $tier['price_monthly'],
            'paypal_account' => $paypalAccount,
            'return_url' => 'https://speechapp.virtuopsdev.com/api/paypal/success.php',
            'cancel_url' => 'https://speechapp.virtuopsdev.com/api/paypal/cancel.php',
            'notify_url' => 'https://speechapp.virtuopsdev.com/api/paypal/ipn.php',
            'custom' => json_encode([
                'user_uuid' => $userUuid,
                'tier_uuid' => $tierUuid
            ])
        ];
    }

    /**
     * Generate Stripe checkout session
     */
    public static function generateStripeCheckout(string $userUuid, string $tierUuid): array {
        // Similar to PayPal but for Stripe
        // Use Stripe PHP SDK to create checkout session

        return [
            'status' => 'success',
            'checkout_url' => 'https://stripe.checkout.url'
        ];
    }

    private static function getPayPalAccountForTier(array $tier): string {
        // If tier has owner (enterprise admin), use their PayPal
        // Otherwise use super-user's PayPal

        if ($tier['owner_user_uuid']) {
            // Load enterprise admin's PayPal email from settings
            return 'admin@example.com';  // TODO: Load from admin settings
        }

        return 'superuser@speechapp.com';  // Super-user's PayPal
    }
}