<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('../../bootstrap.php');
require_once('/opt/mka/core/Tasks/CheckSubscriptionLimits.php');
use MKA\Tasks\CheckSubscriptionLimits;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['form_uuid']) || empty($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$form_uuid = $_POST['form_uuid'];
$newStatus = ($_POST['status'] === 'active') ? 'active' : 'disabled';
$user_uuid = $_SESSION['user_data']['user_uuid'] ?? '';

if (!$user_uuid) {
    echo json_encode(['success' => false, 'message' => 'User session missing. Please log in again.']);
    exit;
}

try {
    // If trying to enable a form, enforce subscription limit first
    if ($newStatus === 'active') {
        // 1. Get the user's max forms allowed
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT pt.features_json, us.status as subStatus
            FROM user_subscriptions us
            JOIN product_tiers pt ON us.tier_uuid = pt.tier_uuid
            WHERE us.user_uuid = ?
            LIMIT 1
        ");
        $stmt->execute([$user_uuid]);
        $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $featuresData = $features[0] ? json_decode($features[0]['features_json'], true) : [];
        $maxForms = (int)($featuresData['hipaa_forms']['max_forms'] ?? 0);
        $subStatus = $features[0]['subStatus'];
        
        if ($subStatus == 'canceled') {
           echo json_encode([
                'success' => false,
                'message' => "You canceled your subscription.  "
            ]);
            exit; 
            
        }

        // 2. Count active forms
        $stmt = $GLOBALS['pdo_hipaa']->prepare("
            SELECT COUNT(*) 
            FROM mka_forms.form_definitions
            WHERE user_uuid = ? AND is_active = 'active'
        ");
        $stmt->execute([$user_uuid]);
        $activeForms = (int)$stmt->fetchColumn();
        
        

        // 3. If limit reached, block re-enable
        if ($maxForms > 0 && $activeForms >= $maxForms) {
            echo json_encode([
                'success' => false,
                'message' => "You have reached your plan's limit of {$maxForms} active forms. 
                              Please disable another form before re-enabling this one."
            ]);
            exit;
        }
    }

    // Update the form status
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        UPDATE mka_forms.form_definitions
        SET is_active = ?, updated_at = NOW()
        WHERE form_uuid = ? AND user_uuid = ?
    ");
    $success = $stmt->execute([$newStatus, $form_uuid, $user_uuid]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Form status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update form status.']);
    }

} catch (Exception $e) {
    error_log("toggle_form_status.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
