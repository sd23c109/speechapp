<?php
require_once('../../bootstrap.php');
session_start();

if (empty($_GET['email'])) {
    session_destroy();
    header("Location: forgot_password.php");
    exit;
}

$email = htmlspecialchars($_GET['email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log(json_encode($_POST));
    $email = $_POST['userEmail'];
    $code = (int)implode('', $_POST['code']); // 6 inputs named code[0]..code[5]
    $pass = $_POST['password'];
    $confirm = $_POST['confirmPassword'];

    if ($pass !== $confirm) {
        $_SESSION['toast_error'] = 'Passwords do not match.';
        header("Location: new_password.php?email=" . urlencode($email));
        exit;
    }

    $stmt = $pdo->prepare("SELECT password_reset_code, password_reset_datetime FROM mka_users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['password_reset_code'] != $code) {
        $_SESSION['toast_error'] = 'Invalid reset code.';
    } elseif (strtotime($user['password_reset_datetime']) < time() - 600) {
        $pdo->prepare("UPDATE mka_users SET password_reset_code = NULL, password_reset_datetime = NULL WHERE Email = ?")->execute([$email]);
        $_SESSION['toast_error'] = 'Reset link has expired.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE mka_users SET PasswordHash = ?, password_reset_code = NULL, password_reset_datetime = NULL WHERE Email = ?");
        $update->execute([$hash, $email]);
        $_SESSION['toast_success'] = 'Password updated. You can now log in.';
        
    }

    header("Location: new_password.php?email=" . urlencode($email));
    exit;
}

    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>New Password | MKAdvantage Online Toolkit</title>
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
                                        <h4 class="fw-bold mt-4">Setup New Password</h4>
                                        <p class="text-muted w-lg-75 mx-auto">We've emailed you a 6-digit verification code. Please enter it below to confirm your Email Address.</p>
                                    </div>

                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="userEmail" class="form-label">Email address <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                                <input type="email" class="form-control" name="userEmail" value="<?= $email ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Enter your 6-digit code <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-2 two-factor">
                                                <input type="text" class="form-control text-center" name="code[0]" maxlength="1" required>
                                                <input type="text" class="form-control text-center" name="code[1]" maxlength="1" required>
                                                <input type="text" class="form-control text-center" name="code[2]" maxlength="1" required>
                                                <input type="text" class="form-control text-center" name="code[3]" maxlength="1" required>
                                                <input type="text" class="form-control text-center" name="code[4]" maxlength="1" required>
                                                <input type="text" class="form-control text-center" name="code[5]" maxlength="1" required>
                                            </div>
                                        </div>

                                        <div class="mb-3" data-password="bar">
                                            <label for="userPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-password text-muted fs-xl"></i></span>
                                                <input type="password" class="form-control" name="password" id="userPassword" placeholder="••••••••" required>
                                            </div>
                                            <div class="password-bar my-2"></div>
                                            <p class="text-muted fs-xs mb-0">Use 8+ characters with letters, numbers & symbols.</p>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirmPassword" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-password text-muted fs-xl"></i></span>
                                                <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="••••••••" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input form-check-input-light fs-14" type="checkbox" name="termAndPolicy" id="termAndPolicy">
                                                <label class="form-check-label" for="termAndPolicy">Agree the Terms & Policy</label>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary fw-semibold py-2">Update Password</button>
                                        </div>
                                    </form>

                                    <p class="mt-4 text-muted text-center mb-4">Don’t have a code? <a href="#" class="text-decoration-underline link-offset-2 fw-semibold">Resend</a> or <a href="#" class="text-decoration-underline link-offset-2 fw-semibold">Call Us</a></p>
                                    <p class="text-muted text-center mb-0">
                                        Return to <a href="auth-2-sign-in.html" class="text-decoration-underline link-offset-3 fw-semibold">Sign in</a>
                                    </p>

                                    <p class="text-center text-muted mt-4 mb-0">
                                        © 2014 -
                                        <script>document.write(new Date().getFullYear())</script> INSPINIA — by <span class="fw-semibold">WebAppLayers</span>
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

    <!-- Two Factor Validator Js -->
   <!-- <script src="js/pages/auth-two-factor.js"></script>

    <!-- Password Suggestion Js -->
  <!--  <script src="js/pages/auth-password.js"></script>  -->
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

        // Redirect after 3 seconds
        setTimeout(function () {
            window.location.href = "login.php";
        }, 5000);
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
        setTimeout(function () {
            window.location.href = "login.php";
        }, 5000);
    });
</script>
<?php unset($_SESSION['toast_error']); } ?>
<script>
$(document).ready(function () {
  const inputs = $('input[name^="code"]');

  inputs.on('input', function () {
    const $this = $(this);
    const val = $this.val();

    // Move to next if input is filled
    if (val.length === 1) {
      const next = inputs.get(inputs.index(this) + 1);
      if (next) $(next).focus();
    }
  });

  inputs.on('keydown', function (e) {
    const $this = $(this);

    if (e.key === "Backspace" && !$this.val()) {
      const prev = inputs.get(inputs.index(this) - 1);
      if (prev) $(prev).focus();
    }
  });

  // Optional: select contents on focus
  inputs.on('focus', function () {
    $(this).select();
  });
});
</script>

</body>

</html>