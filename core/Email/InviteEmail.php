<?php
namespace MKA\Email;

require_once '/opt/mka/core/Email/BrevoMailer.php';

class InviteEmail {

    /**
     * Send invite email to a patient using Brevo
     */
    public static function sendPatientInvite($patientEmail, $patientName, $slpName, $inviteToken) {

        $inviteUrl = "https://speechapp.virtuopsdev.com/dashboards/accept_invite.php?token={$inviteToken}";

        $subject = "You're invited to join {$slpName} on Virtual Speech App";

        // Plain text version
        $textBody = "Hello " . ($patientName ?: 'there') . ",\n\n";
        $textBody .= "{$slpName} has invited you to join their practice on Virtual Speech App.\n\n";
        $textBody .= "Click the link below to accept this invitation and create your account:\n\n";
        $textBody .= "{$inviteUrl}\n\n";
        $textBody .= "This invitation link will expire in 7 days.\n\n";
        $textBody .= "If you have any questions, please contact your speech therapist directly.\n\n";
        $textBody .= "Best regards,\n";
        $textBody .= "The Virtual Speech App Team\n";
        $textBody .= "https://speechapp.virtuopsdev.com";

        // HTML version
        $htmlBody = "<p>Hello " . htmlspecialchars($patientName ?: 'there') . ",</p>";
        $htmlBody .= "<p>" . htmlspecialchars($slpName) . " has invited you to join their practice on Virtual Speech App.</p>";
        $htmlBody .= "<p>Click the button below to accept this invitation and create your account:</p>";
        $htmlBody .= "<p><a href=\"{$inviteUrl}\" style=\"display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;\">Accept Invitation</a></p>";
        $htmlBody .= "<p style=\"font-size: 12px; color: #666;\">Or copy and paste this link: {$inviteUrl}</p>";
        $htmlBody .= "<p style=\"font-size: 12px; color: #666;\">This invitation link will expire in 7 days.</p>";
        $htmlBody .= "<p>If you have any questions, please contact your speech therapist directly.</p>";
        $htmlBody .= "<p>Best regards,<br>The Virtual Speech App Team<br><a href=\"https://speechapp.virtuopsdev.com\">https://speechapp.virtuopsdev.com</a></p>";

        try {
            $result = BrevoMailer::send($patientEmail, $subject, $htmlBody, $textBody);

            if ($result) {
                error_log("Invite email sent successfully to {$patientEmail} via Brevo");
                return [
                    'status' => 'success',
                    'message' => 'Invite sent successfully'
                ];
            } else {
                error_log("Brevo failed to send invite to {$patientEmail}");
                return [
                    'status' => 'error',
                    'message' => 'Failed to send invite email'
                ];
            }
        } catch (\Exception $e) {
            error_log("Brevo exception: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Email service error: ' . $e->getMessage()
            ];
        }
    }
}