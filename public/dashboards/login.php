<?php
require_once '../../bootstrap.php';
require_once('/opt/mka/core/Auth/LoginHandler.php');
require_once('/opt/mka/core/Log/MKALogger.php');
use MKA\Auth\LoginHandler;
use MKA\Log\MKALogger;
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $result = LoginHandler::handle($_POST);

    if ($result['success']) {
        MKALogger::log('login', [
        
        'user_uuid' => $_SESSION['user_data']['user_uuid']
        ]);
        
        header('Location: /index.php'); // or wherever
        exit;
    } else {
        
       MKALogger::log('login_failure', [
            'username_attempted' => $_POST['email'] ?? '(unknown)'
        ]);
        $error = $result['message'];
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sign In | The Virtual Speech App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MKAdvantage Online Tools are perfect for every business looking to accelerate their online presence and better serve their customers">
    <meta name="keywords" content="MKAdvantage, admin dashboard, HIPAA Forms, responsive admin, web app UI, admin theme, website tools">
    <meta name="author" content="MKAdvantage, Inc.">

    <!-- App favicon -->
    <link rel="shortcut icon" href="img/favicon.ico">

    <!-- Theme Config Js -->
    <script src="js/config.js"></script>

    <!-- Vendor css -->
    <link href="css/vendors.min.css" rel="stylesheet" type="text/css">

    <!-- App css -->
    <link href="css/app.min.css" rel="stylesheet" type="text/css">
     <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
      <script src="plugins/jquery/js/jquery.min.js"></script>

    <style>
        .card-side-img {
            background-image:url("img/front_page.png");
            background-size: cover;          /* fill the area */
            background-position: center;     /* center focus */
            background-repeat: no-repeat;    /* no tiling */
            min-height: 100%;                /* ensure visibility */
        }

        .auth-brand img {
            height: 120px;            /* adjust size as needed */
            width: auto;              /* keep aspect ratio */
            border-radius: 12px;      /* rounded corners */
            object-fit: contain;      /* ensures clean scaling */
        }
    </style>


    <script src="plugins/jquery-mask-plugin/js/jquery.mask.min.js"></script>
</head>

<body>

    <div class="auth-box d-flex align-items-center">
        <div class="container-xxl">
            <div class="row align-items-center justify-content-center">
                <div class="col-xl-10">
                    <div class="card rounded-4">
                        <div class="row justify-content-between g-0">
                            <div class="col-lg-6">
                                <div class="card-body">
                                    <div class="auth-brand text-center mb-4">
                                        <a href="index.php" class="logo-dark">
                                            <img src="img/Logo1.png" alt="dark logo" height="170">
                                        </a>
                                        <a href="index.html" class="logo-light">
                                            <img src="img/Logo1.png" alt="logo" height="170">
                                        </a>
                                        <h4 class="fw-bold mt-4">Welcome to The Virtual Speech App!</h4>
                                        <p class="text-muted w-lg-75 mx-auto">Let's get you signed in. Enter your email and password to continue.</p>
                                    </div>

                                    <form role="form" method="post">
                                        <div class="mb-3">
                                            <label for="userEmail" class="form-label">Email address <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                                <input type="email" class="form-control" name="email" id="email" placeholder="you@example.com" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="userPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-lock-password text-muted fs-xl"></i></span>
                                                <input type="password" class="form-control" name="password" id="password" placeholder="••••••••" required>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input form-check-input-light fs-14" type="checkbox" id="rememberMe">
                                                <label class="form-check-label" for="rememberMe">Keep me signed in</label>
                                            </div>
                                            <a href="forgot_password.php" class="text-decoration-underline link-offset-3 text-muted">Forgot Password?</a>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary fw-semibold py-2">Sign In</button>
                                        </div>
                                    </form>

                                    <p class="text-muted text-center mt-4 mb-0">
                                        New here? <a href="signup.php" class="text-decoration-underline link-offset-3 fw-semibold">Create a trial account</a>
                                    </p>

                                    <p class="text-center text-muted mt-4 mb-0">
                                        <span class="fw-semibold">Crossroads Therapy Clinic, LLC.</span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="h-100 position-relative card-side-img rounded-end-4 rounded-end rounded-0 overflow-hidden">
                                    <div class="p-4 rounded-4 rounded-start-0 d-flex align-items-end justify-content-center">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end auth-fluid-->

    <!-- Vendor js -->
    <script src="js/vendors.min.js"></script>
     <script src="plugins/toastr/js/toastr.min.js"></script>
      <script src="js/app.js"></script>
<?php
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'){ 
?>
<script>
$(document).ready(function () {
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-center",
        "timeOut": "6000"
    };
    toastr.warning("You were logged out due to 15 minutes of inactivity.", "Session Timeout");
});
</script>
<?php 
}

$status = $_GET['status'] ?? null;
if ($status === 'confirmemail'){
?>
<script>
 $(document).ready(function () {
  toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-center",
    "timeOut": "8000"
  };
  toastr.success("Your account was created!<br>Please check your email to confirm BEFORE LOGIN.", "Success");
});
</script>
<?php
}
?>


    <!-- App js -->
   

</body>

</html>