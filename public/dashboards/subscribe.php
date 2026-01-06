<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Paypal/PaypalSubscriptionManager.php';

$tier = $_GET['tier'] ?? '';
$user_uuid = $_SESSION['user_data']['user_uuid'] ?? $_SESSION['pending_user_uuid'] ?? null;

$planId = MKA\Paypal\PaypalSubscriptionManager::getPlanIdForTier($tier);
if (!$user_uuid || !$planId) {
    http_response_code(400);
    echo "Invalid tier or user not logged in.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Subscribe - MKAdvantage</title>
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR-LIVE-CLIENT-ID&vault=true&intent=subscription"></script>
</head>
<body>
    <div class="container text-center mt-5">    
        <h2>You're almost there!</h2>
        <p>Subscribe to the <strong><?= htmlspecialchars($tier) ?></strong> plan and unlock full access.</p>

        <div id="paypal-button-container"></div>  
    </div>

    <script>
    paypal.Buttons({
        createSubscription: function (data, actions) {
            return actions.subscription.create({
                plan_id: '<?= $planId ?>'
            });
        },
        onApprove: function (data, actions) {
            fetch('handle_success.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    subscriptionID: data.subscriptionID,
                    userUUID: '<?= $user_uuid ?>',
                    tier: '<?= $tier ?>'
                })
            }).then(() => {
                window.location.href = '/dashboards/thank_you.php';
            });
        }
    }).render('#paypal-button-container');
    </script>
</body>
</html>

