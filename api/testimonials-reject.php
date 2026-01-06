<?php
require_once(__DIR__.'/../bootstrap.php');
require_once(__DIR__ . '/../dashboards/_init.php');

if (!isset($_GET['id'])) {
    header('Location: /dashboards/incentive_testimonials.php');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE mka_testimonials
    SET status = 'rejected', approved_at = NULL
    WHERE testimonial_id = :id AND user_uuid = :user_uuid
");
$stmt->execute([
    ':id' => $_GET['id'],
    ':user_uuid' => $_SESSION['user_data']['user_uuid']
]);

header('Location: /dashboards/incentive_testimonials.php');
exit;
?>

