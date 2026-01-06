<?php
    require_once('../../bootstrap.php');
require_once('/opt/mka/core/Email/BrevoMailer.php');
    session_start();
    use MKA\Email\BrevoMailer;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['userEmail']);

    $stmt = $pdo->prepare("SELECT * FROM mka_users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $code = random_int(100000, 999999);
        $now = date('Y-m-d H:i:s');

        $update = $pdo->prepare("UPDATE mka_users SET password_reset_code = ?, password_reset_datetime = ? WHERE Email = ?");
        $update->execute([$code, $now, $email]);

        // Send email (simplified)
        $link = "https://speechapp.virtuopsdev.com/dashboards/new_password.php?email=" . urlencode($email);
        $html = "
            <p>Your password reset code is: <strong>{$code}</strong></p>
            <p><a href='{$link}'>Click here to reset your password</a></p>
            <p>This link is valid for 10 minutes.</p>
        ";
        BrevoMailer::send([$email], 'Password Reset Request', $html);

        $_SESSION['toast_success'] = 'If your email exists in our system you will get reset instructions.';
    } else {
        $_SESSION['toast_success'] = 'If your email exists in our system you will get reset instructions.';
    }

    header('Location: forgot_password.php'); // or wherever
   
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reset Password | MKAdvantage Online Toolkit</title>
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
     <script src="plugins/jquery/js/jquery.min.js"></script>
    
         
    <script src="plugins/jquery-mask-plugin/js/jquery.mask.min.js"></script>
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
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
                                        <a href="index.html" class="logo-dark">
                                            <img src="https://www.mkadvantage.com/wp-content/uploads/2025/03/PNGVersion.png" alt="dark logo" height="170">
                                        </a>
                                        <a href="index.html" class="logo-light">
                                            <img src="https://www.mkadvantage.com/wp-content/uploads/2025/03/PNGVersion.png" alt="logo" height="170">
                                        </a>
                                        <h4 class="fw-bold mt-4">Password Reset</h4>
                                       
                                           <p class="text-muted w-lg-75 mx-auto">Enter your email address and we'll send you a link to reset your password.</p>
                                    </div>

                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="userEmail" class="form-label">Email address <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                                <input type="email" class="form-control" name="userEmail" id="userEmail" placeholder="you@example.com" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input form-check-input-light fs-14" type="checkbox" id="termAndPolicy">
                                                <label class="form-check-label" for="termAndPolicy">Agree the Terms & Policy</label>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary fw-semibold py-2">Send Request</button>
                                        </div>
                                    </form>

                                    <p class="text-muted text-center mt-4 mb-0">
                                        Return to <a href="login.php" class="text-decoration-underline link-offset-3 fw-semibold"> Sign in</a>
                                    </p>

                                     <p class="text-center text-muted mt-4 mb-0">
                                        <span class="fw-semibold">MKAdvantage Inc.</span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="h-100 position-relative card-side-img rounded-end-4 rounded-end rounded-0 overflow-hidden">
                                    <div class="p-4 card-img-overlay rounded-4 rounded-start-0 auth-overlay d-flex align-items-end justify-content-center"></div>
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

    <!-- App js -->
    <script src="js/app.js"></script>
    <script src="plugins/toastr/js/toastr.min.js"></script>
    <?php
    if (!empty($_SESSION['toast_success'])) {
    
    
 ?> <script>
    $(document).ready(function () {
        toastr.options = {
              "positionClass": "toast-top-center",
              "timeOut": "5000"
            };
        toastr.success("<?= addslashes($_SESSION['toast_success']) ?>");

        
    });
</script>
<?php unset($_SESSION['toast_success']); 
    } else if  (!empty($_SESSION['toast_error'])) {
    
    
 ?> <script>
    $(document).ready(function () {
        toastr.options = {
              "positionClass": "toast-top-center",
              "timeOut": "5000"
            };
        toastr.error("<?= addslashes($_SESSION['toast_error']) ?>");

        // Redirect after 3 seconds
        
    });
</script>
<?php unset($_SESSION['toast_error']); } ?>

</body>

</html>