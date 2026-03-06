<?php
namespace MKA\Admin;

use PDO;

class PricingManagement {

    /**
     * Create custom pricing for enterprise admin
     */
    public static function setAdminPricing(string $adminUuid, array $pricing): array {
        global $pdo;

        $tierName = $pricing['tier_name'] ?? '';
        $priceMonthly = $pricing['price_monthly'] ?? 0;
        $priceYearly = $pricing['price_yearly'] ?? 0;
        $maxSounds = $pricing['max_sounds'] ?? null;
        $maxWords = $pricing['max_words'] ?? null;

        if (!in_array($tierName, ['lite', 'standard', 'pro'])) {
            return ['status' => 'fail', 'message' => 'Invalid tier name'];
        }

        try {
            // First, create a product tier for this admin
            $tierUuid = self::generateUUID();
            $planId = $adminUuid . '-' . $tierName . '-' . time();

            $stmt = $pdo->prepare("
                INSERT INTO product_tiers (
                    tier_uuid, name, plan_id, owner_user_uuid,
                    price_monthly, price_yearly, max_sounds, max_words
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    price_monthly = VALUES(price_monthly),
                    price_yearly = VALUES(price_yearly),
                    max_sounds = VALUES(max_sounds),
                    max_words = VALUES(max_words)
            ");

            $stmt->execute([
                $tierUuid, $tierName, $planId, $adminUuid,
                $priceMonthly, $priceYearly, $maxSounds, $maxWords
            ]);

            // Also create/update admin_pricing record
            $stmt = $pdo->prepare("
                INSERT INTO admin_pricing (
                    admin_user_uuid, tier_name, price_monthly, price_yearly,
                    max_sounds, max_words, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE
                    price_monthly = VALUES(price_monthly),
                    price_yearly = VALUES(price_yearly),
                    max_sounds = VALUES(max_sounds),
                    max_words = VALUES(max_words),
                    is_active = TRUE
            ");

            $stmt->execute([
                $adminUuid, $tierName, $priceMonthly, $priceYearly,
                $maxSounds, $maxWords
            ]);

            return ['status' => 'success', 'message' => 'Pricing updated successfully'];

        } catch (\PDOException $e) {
            error_log("Set pricing error: " . $e->getMessage());
            return ['status' => 'fail', 'message' => 'Database error occurred'];
        }
    }

    /**
     * Get pricing for an admin
     */
    public static function getAdminPricing(string $adminUuid): array {
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT * FROM admin_pricing 
            WHERE admin_user_uuid = ? AND is_active = TRUE
            ORDER BY tier_name ASC
        ");
        $stmt->execute([$adminUuid]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get available tiers for a user to subscribe to
     */
    public static function getAvailableTiers(string $userUuid): array {
        global $pdo;

        // Get user's parent (who they'll be paying)
        $stmt = $pdo->prepare("
            SELECT parent_user_uuid, user_type FROM mka_users WHERE UserUUID = ?
        ");
        $stmt->execute([$userUuid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [];
        }

        // If end-user, show their admin's pricing (or super-user's if direct)
        if ($user['user_type'] === 'end_user') {
            $stmt = $pdo->prepare("
                SELECT * FROM product_tiers 
                WHERE owner_user_uuid = ? OR owner_user_uuid IS NULL
                ORDER BY owner_user_uuid IS NOT NULL DESC, max_sounds ASC
            ");
            $stmt->execute([$user['parent_user_uuid']]);

        } else if ($user['user_type'] === 'enterprise_admin') {
            // Enterprise admins see super-user's enterprise tier
            $stmt = $pdo->prepare("
                SELECT * FROM product_tiers 
                WHERE name = 'enterprise' AND owner_user_uuid IS NULL
            ");
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function generateUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}