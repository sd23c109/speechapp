<?php
require_once '../../bootstrap.php'; // or whatever initializes DB

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid confirmation link.");
}

$stmt = $pdo->prepare("SELECT UserUUID FROM mka_users WHERE email_confirmation_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

$message = "";

if (!$user) {
    $message = "Invalid or expired confirmation token.";
    $style = "display:none;";
} else {
   // Mark as confirmed
$update = $pdo->prepare("UPDATE mka_users SET email_confirmed = 'y', email_confirmation_token = NULL WHERE UserUUID = ?");
$update->execute([$user['UserUUID']]); 
$message = "Email confirmed!";
$style="";
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Email Confirmed</title>
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
        <h2><?php echo $message?></h2>
        <h3 class="font-bold" style="<?php echo $style ?>">You May Now Log In.</h3>

        <div class="error-desc" style="<?php echo $style ?>">
            We hope you enjoy your journey with us<br />
            You can log in here:
            <br>

            <a href="login.php" class="btn btn-primary mt-3" style="<?php echo $style ?>">Let's Go!</a>
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

