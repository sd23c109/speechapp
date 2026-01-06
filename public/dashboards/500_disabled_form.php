<?php
error_log('Disabled form access was attempted.');

$status = $_GET['status'];
$message = '';
$messagetitle = '';

if ($status == 'disabled') {
    $message = "We apologize.  You cannot access this form at this time.  <br /> 
            Please try a different form or contact your healthcare provider.<br />";
    $messagetitle = "This Form Has Been Disabled";
}

if($status == 'error') {
    $message = "We apologize.  You cannot access this form at this time.  <br /> 
            Please contact your healthcare provider about the form error.<br />";
    $messagetitle = "This Form Has An Error";
} 

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Disabled Form</title>
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
        <h2><?=$messagetitle?></h2>
        <h3 class="font-bold"><?=$message?></h3>

        
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