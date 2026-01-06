<?php
require_once '../../bootstrap.php';
require_once '/opt/mka/vendor/autoload.php';
use MKA\Email\BrevoMailer;

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $message = "<p><strong>Form Submit Failure!</strong></p>";
    $message .= "<p><strong>Time:</strong> " . htmlspecialchars($input['timestamp'] ?? 'Unknown') . "</p>";
    $message .= "<p><strong>Form UUID:</strong> " . htmlspecialchars($input['form_uuid'] ?? 'Unknown') . "</p>";
    $message .= "<p><strong>Error:</strong><br><pre>" . htmlspecialchars($input['error'] ?? 'Unknown') . "</pre></p>";
    $message .= "<p><strong>URL:</strong><br>" . htmlspecialchars($input['url'] ?? 'Unknown') . "</p>";
    $message .= "<p><strong>User Agent:</strong><br>" . htmlspecialchars($input['userAgent'] ?? '') . "</p>";

    // Change to your dev email
    $recipient = ['chris@virtuops.com'];
    BrevoMailer::send($recipient, '⚠️ Form Submit Error Notification', $message);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Form failure log failed: " . $e->getMessage());
    echo json_encode(['success' => false]);
}
