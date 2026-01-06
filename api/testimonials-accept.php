<?php
require_once(__DIR__.'/../bootstrap.php');
require_once(__DIR__ . '/../dashboards/_init.php');

if (!isset($_GET['id'])) {
    header('Location: /dashboards/incentive_testimonials.php');
    exit;
}
error_log('test accept???');
error_log(json_encode($_SESSION['user_data']));
$stmt = $pdo->prepare("
    UPDATE mka_testimonials
    SET status = 'approved', approved_at = NOW()
    WHERE testimonial_id = :id AND user_uuid = :user_uuid
");
$stmt->execute([
    ':id' => $_GET['id'],
    ':user_uuid' => $_SESSION['user_data']['user_uuid']
]);

header('Location: /dashboards/incentive_testimonials.php');
exit;
?>

