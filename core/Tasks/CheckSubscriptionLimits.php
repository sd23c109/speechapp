<?php
namespace MKA\Tasks;

require_once '/opt/mka/vendor/autoload.php';
require_once '/opt/mka/core/Log/MKALogger.php';

use MKA\Log\MKALogger;
use PDO;

class CheckSubscriptionLimits
{
    /**
     * Check if the user can create another form for a given tool.
     *
     * @param string $user_uuid  UUID of the user (from session)
     * @param string $toolSlug   Slug of the tool you’re checking (default: hipaa_forms)
     * @return bool
     */
    public static function canCreateForm(string $user_uuid, string $toolSlug = 'hipaa_forms'): bool
    {
        global $pdo;
         try {
                // ------------------------------------------------------------------
                // 1. Look up the user’s CURRENT tier & its JSON‑encoded feature set
                // ------------------------------------------------------------------
                $stmt = $pdo->prepare("
                    SELECT pt.features_json, us.status as subStatus
                    FROM   user_subscriptions us
                    JOIN   product_tiers pt ON pt.tier_uuid = us.tier_uuid
                    WHERE  us.user_uuid = ?
                      AND  (us.status = 'active' OR us.status = 'trial' OR us.status = 'canceled')
                    LIMIT  1
                ");
                $stmt->execute([$user_uuid]);
                $featuresJson = $stmt->fetchColumn();
                
               

                if (!$featuresJson) {
                    //CHECK FOR TRIAL.  TRIAL ACCOUNTS HAVE NO SUBSCRIPTION.
                    $userRow = $pdo->prepare("SELECT IsTrial FROM mka_users WHERE UserUUID = ? LIMIT 1");
                    $userRow->execute([$user_uuid]);
                    $isTrial = $userRow->fetchColumn();

                    if ($isTrial === 'y') {
                        $subStatus = 'trial';
                        $maxForms = 40;
                        
                    } else {
                        // No valid subscription or Trial → block creation
                        MKALogger::log('subscription_limit_block', [
                            'user_uuid' => $user_uuid,
                            'reason'    => 'no_active_subscription'
                        ]);
                        $maxForms = 0; 
                    }
                    
                   
                } else {
                    $featuresData = $features[0] ? json_decode($featuresJson[0]['features_json'], true) : [];
                    $maxForms = (int)($featuresData['hipaa_forms']['max_forms'] ?? 0);
                    $subStatus = $features[0]['subStatus'];
                }
         } catch (PDOException $e) {
                $message =  json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
                error_log($message);
            }

        // ------------------------------------------------------------------
        // 2. Decode JSON → PHP array and fetch the limit for this tool
        // ------------------------------------------------------------------
        $features = json_decode($featuresJson, true) ?? [];
        $maxForms = $features[$toolSlug]['max_forms'] ?? PHP_INT_MAX; // Unlimited if not set

        // ------------------------------------------------------------------
        // 3. Count how many forms the user already has
        // ------------------------------------------------------------------
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mka_forms.form_definitions WHERE user_uuid = ?");
        $stmt->execute([$user_uuid]);
        $currentCount = (int)$stmt->fetchColumn();
        
        // ------------------------------------------------------------------
        // 3a. If user canceled subscription, they get zero forms
        // ------------------------------------------------------------------
        
        if ($subStatus == 'canceled') {
            $currentCount = 0;
        }

        // ------------------------------------------------------------------
        // 4. Return TRUE if they’re still under the limit
        // ------------------------------------------------------------------
        return $currentCount < $maxForms;
    }
    
    public static function enforceFormLimit($user_uuid): void
    {
        try {
            // 1. Get max forms from features_json
            $stmt = $GLOBALS['pdo']->prepare("
                SELECT pt.features_json, us.status as subStatus
                FROM user_subscriptions us
                JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
                WHERE us.user_uuid = ?
                LIMIT 1
            ");
            $stmt->execute([$user_uuid]);
           // $features = $stmt->fetchColumn();
            $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
           
            $featuresData = $features[0] ? json_decode($features[0]['features_json'], true) : [];
            $maxForms = (int)($featuresData['hipaa_forms']['max_forms'] ?? 0);
            $subStatus = $features[0]['subStatus'];

            if ($maxForms <= 0) {
                return;
            }
            
            //Before anything, if you are canceled, all forms are disabled.
            if ($subStatus == 'canceled') {
                $stmt = $GLOBALS['pdo_hipaa']->prepare("
                        UPDATE form_definitions 
                        SET is_active = 'disabled', updated_at = NOW()
                        WHERE user_uuid = ?
                   ");
                    $stmt->execute([$user_uuid]);    
            return;  
            }

            // 2. Count active forms
            $stmt = $GLOBALS['pdo_hipaa']->prepare("
                SELECT COUNT(*) 
                FROM form_definitions
                WHERE user_uuid = ? AND is_active = 'active'
            ");
            $stmt->execute([$user_uuid]);
            $activeForms = (int)$stmt->fetchColumn();
            
           

            // 3. If over limit, disable oldest
            if ($activeForms > $maxForms) {
                $overBy = $activeForms - $maxForms;

                $stmt = $GLOBALS['pdo_hipaa']->prepare("
                    SELECT form_uuid
                    FROM form_definitions
                    WHERE user_uuid = ? AND is_active = 'active'
                    ORDER BY created_at ASC
                    LIMIT $overBy
                ");
                $stmt->execute([$user_uuid]);
                $oldForms = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if ($oldForms) {
                    $in = str_repeat('?,', count($oldForms) - 1) . '?';
                    $stmt = $GLOBALS['pdo_hipaa']->prepare("
                        UPDATE form_definitions 
                        SET is_active = 'disabled', updated_at = NOW()
                        WHERE form_uuid IN ($in)
                    ");
                    $stmt->execute($oldForms);
                }
                
                MKALogger::log('subscription_limit_changed', [
                'user_uuid' => $user_uuid,
                'reason'    => 'over_form_limit'
                ]);
            }

        } catch (Exception $e) {
            error_log("CheckSubscriptionLimits::enforceFormLimit failed for user {$user_uuid}: " . $e->getMessage());
        }
    }
}
