<?php
namespace MKA\Billing;

class SLPBilling {

    const BASE_COST = 100.00;
    const BASE_CAPACITY = 10;
    const CREDIT_PER_PATIENT = 10.00;

    const PACK_PRICING = [
        1 => 10.00,
        5 => 45.00,
        10 => 80.00
    ];

    /**
     * Get total capacity for an SLP (base + add-ons)
     */
    public static function getTotalCapacity($slpUuid, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(pack_size), 0) as addon_capacity
            FROM slp_capacity_addons
            WHERE slp_uuid = ? AND status = 'active'
        ");
        $stmt->execute([$slpUuid]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return self::BASE_CAPACITY + ($result['addon_capacity'] ?? 0);
    }

    /**
     * Get number of affiliated active patients
     */
    public static function getAffiliatedPatientCount($slpUuid, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as patient_count
            FROM patient_affiliations
            WHERE slp_uuid = ? AND status = 'active'
        ");
        $stmt->execute([$slpUuid]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['patient_count'] ?? 0;
    }

    /**
     * Get total add-on costs for an SLP
     */
    public static function getAddonCosts($slpUuid, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(price_monthly), 0) as total_addon_cost
            FROM slp_capacity_addons
            WHERE slp_uuid = ? AND status = 'active'
        ");
        $stmt->execute([$slpUuid]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['total_addon_cost'] ?? 0.00;
    }

    /**
     * Get number of affiliated PAID patients (used for billing credits only).
     * Trial patients do not reduce the SLP's bill — only active (paying) subscribers do.
     */
    public static function getPaidPatientCount($slpUuid, $pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as patient_count
            FROM patient_affiliations pa
            JOIN user_subscriptions us ON us.user_uuid = pa.patient_uuid AND us.status = 'active'
            WHERE pa.slp_uuid = ?
              AND pa.status = 'active'
        ");
        $stmt->execute([$slpUuid]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['patient_count'] ?? 0;
    }

    /**
     * Calculate monthly bill for an SLP.
     * Credits only apply for PAID patients — trial patients do not reduce the bill.
     */
    public static function calculateMonthlyBill($slpUuid, $pdo) {
        $addonCosts = self::getAddonCosts($slpUuid, $pdo);
        $paidPatientCount = self::getPaidPatientCount($slpUuid, $pdo);
        $credits = $paidPatientCount * self::CREDIT_PER_PATIENT;

        $total = self::BASE_COST + $addonCosts - $credits;

        return max(0, $total); // Floor at $0
    }

    /**
     * Check if SLP can affiliate more patients
     */
    public static function canAffiliate($slpUuid, $pdo) {
        $capacity = self::getTotalCapacity($slpUuid, $pdo);
        $current = self::getAffiliatedPatientCount($slpUuid, $pdo);

        return $current < $capacity;
    }

    /**
     * Get available slots
     */
    public static function getAvailableSlots($slpUuid, $pdo) {
        $capacity = self::getTotalCapacity($slpUuid, $pdo);
        $current = self::getAffiliatedPatientCount($slpUuid, $pdo);

        return max(0, $capacity - $current);
    }
}