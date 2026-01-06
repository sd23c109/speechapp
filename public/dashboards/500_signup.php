<?php
require_once '/opt/mka/bootstrap.php';
require_once '/opt/mka/core/Email/BrevoMailer.php';

use MKA\Email\BrevoMailer;

$errorType = $_GET['error'] ?? 'unknown';
$failUser = htmlspecialchars($_GET['email'] ?? 'no email given');

$subject = 'MKAdvantage Signup Failure Alert';
$to = 'chris@virtuops.com';

$html = "<h2>Signup Error Report</h2>";

switch ($errorType) {
    case 'withresponse':
        $html .= "<p><strong>Status:</strong> We received a response from <code>handle_success.php</code> for <strong>$failUser</strong>, but it was invalid or incomplete.</p>";
        $html .= "<p>Check what was returned in the JSON. Could be missing <code>tier</code>, <code>user_uuid</code>, or <code>subscription_id</code>.</p>";
        break;

    case 'noresponse':
        $html .= "<p><strong>Status:</strong> No response received from <code>handle_success.php</code> for <strong>$failUser</strong>.</p>";
        $html .= "<p>Check <code>error_log</code> for possible fatal PHP errors or bad headers.</p>";
        break;

    default:
        $html .= "<p><strong>Status:</strong> Unknown error encountered during signup redirect.</p>";
        break;
}

$html .= "<p><em>This alert was triggered automatically by <code>signup.php → 500_signup.php</code>.</em></p>";

// Send the alert
BrevoMailer::send($to, $subject, $html);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> 500 Error - SignUp Issue</title>
    <meta content="WebAppLayers" name="author" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="img/favicon.ico">

    <!-- Bootstrap css -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">

    <!-- Icons css -->
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Animate.css -->
    <link href="plugins/animate/css/animate.min.css" rel="stylesheet">

    <!-- Style css -->
    <link href="css/style.min.css" rel="stylesheet" type="text/css">

    <!-- Head.js -->
    <script src="js/head.js"></script>
</head>

<body class="gray-bg">

    <div class="middle-box text-center animated fadeInDown">
        <h1>500</h1>
        <h3 class="font-bold">Internal Server Error</h3>

        <div class="error-desc">
            We could not complete your signup. We apologize.<br />
            You can go back to main page:
            <br>

            <a href="index.php" class="btn btn-primary mt-3">Dashboard</a>
        </div>
    </div>

    <!-- Mainly Plugin Scripts -->
    <script src="plugins/jquery/js/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="plugins/pace-js/js/pace.min.js"></script>
    <script src="plugins/wow.js/js/wow.min.js"></script>
    <script src="plugins/lucide/js/lucide.min.js"></script>
    <script src="plugins/simplebar/js/simplebar.min.js"></script>

    <!-- Custom and Plugin Javascript -->
    <script src="js/inspinia.js"></script>

</body>

</html>