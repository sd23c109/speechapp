<?php
namespace MKA\Payment;

class StripeConfig {

    // REPLACE THESE WITH YOUR ACTUAL KEYS
    const SECRET_KEY = '';
    const PUBLISHABLE_KEY = '';

    // Price IDs from Stripe Dashboard
    const PRICE_SLP = 'price_1Syhhx0VuoH6Rs71NAihTFvY';
    const PRICE_PATIENT = 'price_1SyhlX0VuoH6Rs71N90wkE4h';

    // Product IDs (for reference)
    const PRODUCT_SLP = 'prod_TwatKd9fk0c5RE';
    const PRODUCT_PATIENT = 'prod_TwaxoMq1Dql8U7';

    // Webhook secret (you'll get this when setting up webhook)
    const WEBHOOK_SECRET = '';

    public static function getSecretKey() {
        return self::SECRET_KEY;
    }

    public static function getPublishableKey() {
        return self::PUBLISHABLE_KEY;
    }

    public static function getPriceId($userType) {
        switch($userType) {
            case 'enterprise_admin':
                return self::PRICE_SLP;
            case 'end_user':
                return self::PRICE_PATIENT;
            default:
                throw new \Exception('Invalid user type for pricing');
        }
    }

    public static function getCapacityPackPriceId($packSize) {
        $priceIds = [
            1 => 'price_1T008v0VuoH6Rs71XiLNmAM5',   // Your Stripe price ID
            5 => 'price_1T008J0VuoH6Rs71Qvp2gIhG',   // Your Stripe price ID
            10 => 'price_1T004g0VuoH6Rs71vn8bn1Gn'  // Your Stripe price ID
        ];

        return $priceIds[$packSize] ?? null;
    }

    public static function getBaseAmount($userType) {
        switch($userType) {
            case 'enterprise_admin':
                return 100.00;
            case 'end_user':
                return 10.00;
            default:
                return 0.00;
        }
    }
}