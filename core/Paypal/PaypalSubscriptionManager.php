<?php
namespace MKA\Paypal;
require_once __DIR__.'/../../config/paypal.php'; // paypal configs

class PaypalSubscriptionManager {

    protected static function getAccessToken() {
        error_log('GET TOKEN FIRST WITH >>>>');
        error_log('BASE:'.PAYPAL_API_BASE);
        error_log('CLIENT ID:'.PAYPAL_CLIENT_ID);
        error_log('SECRET:'.PAYPAL_SECRET);
        
        $ch = curl_init(PAYPAL_API_BASE . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials'
        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US'
        ]);

        $response = curl_exec($ch);
        
        $data = json_decode($response, true);
        error_log('TOKEN DATA: '.$response);
        curl_close($ch);

        return $data['access_token'] ?? null;
    }
    

    public static function cancelSubscription($subscriptionId, $reason = 'User requested cancel') {
        $accessToken = self::getAccessToken();
        if (!$accessToken) return false;

        $ch = curl_init(PAYPAL_API_BASE . "/v1/billing/subscriptions/$subscriptionId/cancel");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode(['reason' => $reason]),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer $accessToken"
            ]
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($status === 204); // PayPal returns 204 No Content on success
    }
    
    public static function getPlanIdForTier(string $tier): ?string {
        return match ($tier) {
            'starter' => 'P-8TY88622S6861012TNCCUTNA',
            'lite' => 'P-6HM73249VT513120DNCCUUFI',
            'standard' => 'P-56G98557MN118924LNCCUUTA',
            'pro' => 'P-4EK43356UT0475350NCCUU7Q',
            default => null
        };
    }
    
    public static function changeSubscription($subscriptionId, string $newTier): bool {
        
        
        $accessToken = self::getAccessToken();
        if (!$accessToken) return false;
        
        $newPlanId = self::getPlanIdForTier($newTier);
        if (!$newPlanId) return false;
       
        $payload = [
            'plan_id' => $newPlanId,
            'application_context' => [
                'brand_name' => 'MKAdvantage',
                'locale' => 'en-US'
            ]
        ];

        $ch = curl_init(PAYPAL_API_BASE . "/v1/billing/subscriptions/$subscriptionId/revise");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer $accessToken"
            ]
        ]);

        $response = curl_exec($ch);
       
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // PayPal returns 200 on success
        return ($status === 200);
    }

}
