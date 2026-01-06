<?php
namespace MKA\Email;

class BrevoMailer {

    public static function send($to, $subject, $htmlBody, $textBody = '') {
        require_once MKA_BASE . '/config/brevo.php';

        $data = [
            'sender' => [
                'name'  => BREVO_FROM_NAME,
                'email' => BREVO_FROM_EMAIL,
            ],
            'to' => array_map(function($email) {
                return ['email' => trim($email)];
            }, (array) $to),
            'subject' => $subject,
            'htmlContent' => $htmlBody,
            'textContent' => $textBody ?: strip_tags($htmlBody) //Brevo doesn't like this being empty
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . BREVO_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return true;
        } else {
            error_log("Brevo API error: $response");
            return false;
        }
    }
}

