<?php
require_once('/opt/mka/bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Email/InviteEmail.php');
require_once('/opt/mka/core/Billing/SLPBilling.php');

use MKA\Email\InviteEmail;
use MKA\Billing\SLPBilling;

// DON'T set header yet - wait until after SendGrid sends

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['slp_uuid']) || !isset($input['patient_email'])) {
    header('Content-Type: application/json'); // Set it here before output
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$slpUuid = $input['slp_uuid'];
$patientEmail = trim($input['patient_email']);
$patientName = trim($input['patient_name'] ?? '');

try {
    global $pdo;

    // Verify SLP exists and is enterprise_admin
    $stmt = $pdo->prepare("SELECT Name, Email FROM mka_users WHERE UserUUID = ? AND user_type = 'enterprise_admin'");
    $stmt->execute([$slpUuid]);
    $slp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slp) {
        throw new Exception('Invalid SLP user');
    }

    // Check if SLP has available capacity
    if (!SLPBilling::canAffiliate($slpUuid, $pdo)) {
        throw new Exception('No available capacity. Please purchase additional slots.');
    }

    // Validate email format
    if (!filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Check if patient already exists
    $stmt = $pdo->prepare("SELECT UserUUID FROM mka_users WHERE Email = ?");
    $stmt->execute([$patientEmail]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        throw new Exception('A user with this email already exists');
    }

    // Check if there's already a pending invite
    $stmt = $pdo->prepare("
        SELECT invite_uuid 
        FROM patient_invites 
        WHERE slp_uuid = ? AND patient_email = ? AND status = 'pending' AND expires_at > NOW()
    ");
    $stmt->execute([$slpUuid, $patientEmail]);
    $existingInvite = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingInvite) {
        throw new Exception('An invite has already been sent to this email');
    }

    // Generate invite token and UUID
    $inviteUuid = bin2hex(random_bytes(16));
    $inviteToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

    // Insert invite
    $stmt = $pdo->prepare("
        INSERT INTO patient_invites 
        (invite_uuid, slp_uuid, patient_email, patient_name, invite_token, status, expires_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?)
    ");

    $stmt->execute([
        $inviteUuid,
        $slpUuid,
        $patientEmail,
        $patientName,
        $inviteToken,
        $expiresAt
    ]);

    // Send invite email - THIS is where SendGrid makes its API call
    $emailResult = InviteEmail::sendPatientInvite($patientEmail, $patientName, $slp['Name'], $inviteToken);



    if ($emailResult['status'] !== 'success') {
        // Delete the invite if email failed
        $stmt = $pdo->prepare("DELETE FROM patient_invites WHERE invite_uuid = ?");
        $stmt->execute([$inviteUuid]);

        throw new Exception($emailResult['message']);
    }

    // NOW set the header before sending response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Invite sent successfully to ' . $patientEmail
    ]);

} catch (Exception $e) {
    error_log("Send invite error: " . $e->getMessage());

    // Set header before sending error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}