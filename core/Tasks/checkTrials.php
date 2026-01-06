<?php
require_once '/opt/mka/bootstrap.php';
use MKA\Email\BrevoMailer;

global $pdo;

$sql = "SELECT Email, Name FROM mka_users WHERE IsTrial = 'y' AND TrialExpires < NOW()";
$stmt = $pdo->query($sql);

while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $update = $pdo->prepare("UPDATE mka_users SET IsTrial = 'n' WHERE Email = ?");
    $update->execute([$user['Email']]);

    // Send notification to admin (you)
    $subject = "Trial Expired: " . $user['Email'];
    $body = "<p><strong>{$user['Name']}</strong>'s trial has expired.</p><p>Email: {$user['Email']}</p>";

   
    
    if (BrevoMailer::send(BREVO_NOTIFY_EMAIL, $subject, $body)) {
    error_log("BrevoMailer sent to " . BREVO_NOTIFY_EMAIL);
    } else {
    error_log("BrevoMailer failed to send to " . BREVO_NOTIFY_EMAIL);
    }
    
    
}
