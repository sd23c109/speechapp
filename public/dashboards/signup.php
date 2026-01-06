<?php
require_once('../../bootstrap.php');
require_once('/opt/mka/vendor/autoload.php');
require_once('/opt/mka/core/Auth/SignupHandler.php'); 

use MKA\Auth\SignupHandler;
$defaultTier = $_GET['tier'] ?? 'lite';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
          

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = SignupHandler::handle($_POST);
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    if ($result['status'] === 'success') {
        $_SESSION['toast_success'] = $result['message'];
        
        
    } else {
        $_SESSION['toast_error'] = $result['message'];
    }
}    
    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Create New Account | MKAdvantage Online Tool kit</title>
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
    
      <script src="plugins/jquery/js/jquery.min.js"></script>
    
         
    <script src="plugins/jquery-mask-plugin/js/jquery.mask.min.js"></script>

    <!-- App css -->
    <link href="css/app.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ts = Math.floor(Date.now() / 1000);
            const el = document.getElementById('rendered_at');
            if (el) el.value = ts;
        });
    </script>

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
                                        <h4 class="fw-bold mt-4">Sign Up for The Virtual Speech App.</h4>
                                        <p class="text-muted w-lg-75 mx-auto">Let's get you started. Create your account by entering your details below.</p>
                                    </div>

                                    <form id="signupForm" method="POST" action="">
                                        <input type="hidden" name="user_uuid" id="user_uuid" value="">




                                        <div style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
                                            <label>Website</label>
                                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                                        </div>


                                        <input type="hidden" name="rendered_at" id="rendered_at" value="">
                                        <div class="mb-3">
                                            <label for="userEmail" class="form-label">Email address <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                                <input type="email" class="form-control" id="mka_email" name="mka_email" placeholder="you@example.com" required>
                                            </div>
                                        </div>

                                        <div class="mb-3" data-password="bar">
                                            <label for="userPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-lock-password text-muted fs-xl"></i></span>
                                                <input type="password" class="form-control" id="mka_password" name="mka_password" placeholder="*************" required>
                                            </div>
                                            <div class="password-bar my-2"></div>
                                            <p class="text-muted fs-xs mb-0">Use 8+ characters with letters, numbers & symbols.</p>
                                        </div>
                                        <div class="mb-3">
                                            <label for="userPasswordConfirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-lock-check text-muted fs-xl"></i></span>
                                                <input type="password" class="form-control" id="mka_password_confirm" name="mka_password_confirm" placeholder="Confirm Password" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="g-recaptcha" data-sitekey="6LcZUQksAAAAAODodfhM1F5Y1lFWu56X9gajD6CH" data-callback="onCaptchaSuccess"></div>
                                        </div>



                                        <!--
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input form-check-input-light fs-14" type="checkbox" checked id="termAndPolicy">
                                                <label class="form-check-label" for="termAndPolicy">Agree to the Terms & Policies</label>
                                            </div>
                                        </div>  -->
                                        <div class="mb-3">
                                            <label class="form-label">Start with:</label><br>
                                            
                                           
                                            <div class="mb-2">
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="start_mode" id="startTrial">
                                                    <label class="form-check-label" for="radio1">Start 14 Day Trial</label>
                                                  </div>
                                                  <div class="form-check form-check-inline">
                                                    <input type="radio" class="form-check-input" name="start_mode" id="startPaid" checked>
                                                    <label class="form-check-label" for="radio2">Start Subscription</label>
                                                </div>
                                            </div>
                                            
                                        </div>

                                        <div class="d-grid" id="startButtons">
                                            <button type="button" class="btn btn-primary fw-semibold py-2" id="payNowTrigger" data-bs-toggle="offcanvas" data-bs-target="#SignUpPayNow" disabled>
                                              Pay and Start
                                            </button>
                                            
                                        </div>
                                    </form>

                                    <p class="text-muted text-center mt-4 mb-0">
                                        Already have an account? <a href="login.php" class="text-decoration-underline link-offset-3 fw-semibold">Login</a>
                                    </p>

                                    <p class="text-center text-muted mt-4 mb-0">
                                       Online Tool Kit by <span class="fw-semibold">Crossroads Therapy Clinic, LLC</span>
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
    
    <!--PAY NOW-->
    
    <div class="offcanvas offcanvas-end overflow-hidden" tabindex="-1" id="SignUpPayNow" aria-modal="true" role="dialog">
        <div class="d-flex justify-content-between text-bg-primary gap-2 p-3" style="background-image: url(images/user-bg-pattern.png);">
            <div>
              <h4 class="text-white fw-bold mb-0">Subscribe</h4>
              <p class="text-white-50 mb-0">Click below to complete your subscription.</p>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
         
          <div class="offcanvas-body">
         <div class="mb-3">
              <label class="form-label fw-semibold">Choose a Monthly Plan:</label>

              <div class="form-check mb-2">
                <input type="radio" class="form-check-input" name="tier" id="tierStarter" value="starter" <?= ($defaultTier === 'starter' ? 'checked' : '') ?>>
                <label class="form-check-label" for="tierStarter">
                  Starter (2 forms): <strong>$6.99/mo</strong>
                </label>
              </div>

              <div class="form-check mb-2">
                <input type="radio" class="form-check-input" name="tier" id="tierLite" value="lite" <?= ($defaultTier === 'lite' ? 'checked' : '') ?>>
                <label class="form-check-label" for="tierLite">
                  Lite (10 forms): <strong>$12.99/mo</strong>
                </label>
              </div>

              <div class="form-check mb-2">
                <input type="radio" class="form-check-input" name="tier" id="tierStandard" value="standard" <?= ($defaultTier === 'standard' ? 'checked' : '') ?>>
                <label class="form-check-label" for="tierStandard">
                  Standard (20 forms): <strong>$17.99/mo</strong>
                </label>
              </div>

              <div class="form-check mb-2">
                <input type="radio" class="form-check-input" name="tier" id="tierPro" value="pro" <?= ($defaultTier === 'pro' ? 'checked' : '') ?>>
                <label class="form-check-label" for="tierPro">
                  Pro (40 forms): <strong>$29.99/mo</strong>
                </label>
              </div>
            </div>

            <div class="mb-3">
              <div id="paypal-button-container"></div>
            </div>
            <p class="text-muted small fst-italic">You'll be charged after completing PayPal checkout. You can cancel anytime from your dashboard.</p>
          </div>
        
        
    </div>
    <!-- end auth-fluid-->

    <!-- Vendor js -->
    <script src="js/vendors.min.js"></script>

    <!-- App js -->
    <script src="js/app.js"></script>

    <!-- Password Suggestion Js -->
    <script src="js/pages/auth-password.js"></script>
    <script src="plugins/toastr/js/toastr.min.js"></script>
    
    
    <script>
        //SET RENDERED AT




    //PASSWORD COMPARE 
document.querySelector('form').addEventListener('submit', function(e) {
    const pass = document.getElementById('userPassword').value;
    const confirm = document.getElementById('userPasswordConfirm').value;

    if (pass !== confirm) {
        e.preventDefault();
         toastr.options = {
              "positionClass": "toast-top-center",
              "timeOut": "5000"
            };
        toastr.error("Passwords do not match.");
    }
});

//TOGGLE BETWEEN TRIAL AND START NOW
document.querySelectorAll('input[name="start_mode"]').forEach(el => {
  el.addEventListener('change', function () {
    const container = document.getElementById('startButtons');

    if (this.id === 'startTrial') {
      container.innerHTML = `
        <button type="submit" class="btn btn-primary fw-semibold py-2">Start Trial</button>
      `;
    } else {
      container.innerHTML = `
        <button type="button" class="btn btn-primary fw-semibold py-2" id="payNowTrigger" data-bs-toggle="offcanvas" data-bs-target="#SignUpPayNow" disabled>
          Pay and Start
        </button>
      `;
    }

    // Re-validate after injecting new button
    validateSignupFields();
  });
});

// Attach listeners to form fields for live validation
document.querySelectorAll('[id^="mka_"]').forEach(input => {
  input.addEventListener('input', validateSignupFields);
});



function validateSignupFields() {
  
  const inputs = document.querySelectorAll('[id^="mka_"]');
  let allFilled = true;

  inputs.forEach(input => {
    if (!input.value.trim()) {
      allFilled = false;
    }
  });

    let captchaOK = false;

    if (window.grecaptcha && typeof grecaptcha.getResponse === "function") {
        captchaOK = grecaptcha.getResponse().trim().length > 0;
    }

const payButton = document.querySelector('#payNowTrigger');
  if (payButton) {
    payButton.disabled = !(allFilled && captchaOK);
  }
}

window.onCaptchaSuccess = function() { validateSignupFields(); };  //rerun on captchaSuccess

//PAY AND START NEEDS A USER BEFORE PAYPAL PAYMENT

    document.getElementById('payNowTrigger').addEventListener('click', function (e) {
        // Block if captcha not solved
        if (!window.grecaptcha || grecaptcha.getResponse().length === 0) {
            e.preventDefault();
            toastr.options = { positionClass: "toast-top-center", timeOut: "4000" };
            toastr.error("Please complete the reCAPTCHA.");
            return;
        }

        const form = document.getElementById('signupForm');
        const formData = new FormData(form);

        // Only create the user if we haven't already
        if (!document.getElementById('user_uuid').value) {
            fetch('signup.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('user_uuid').value = data.user_uuid;
                    } else {
                        e.preventDefault();
                        toastr.options = { positionClass: "toast-top-center", timeOut: "5000" };
                        toastr.error(data.message || 'Signup failed.');
                    }
                })
                .catch(err => {
                    e.preventDefault();
                    toastr.options = { positionClass: "toast-top-center", timeOut: "5000" };
                    toastr.error('Error creating account.');
                });
        }
    });


    document.addEventListener('DOMContentLoaded', validateSignupFields);  //recaptcha

</script>
    <!-- reCAPTCHA v2 loader -->



    <!--PAYPAL BUTTONS-->

<script src="https://www.paypal.com/sdk/js?client-id=AX9hxbxKbSio-0qMSjGUE_JDEOlsynaCjzrynrgEmhTB8SpSu0u_x7xv8DaGGow18Ntj324vFlLX7Mpe&vault=true&intent=subscription&disable-funding=paylater"></script>
<script>
const planIdByTier = {
    
  starter: 'P-8TY88622S6861012TNCCUTNA',
  lite: 'P-6HM73249VT513120DNCCUUFI',
  standard: 'P-56G98557MN118924LNCCUUTA',
  pro: 'P-4EK43356UT0475350NCCUU7Q',
};

paypal.Buttons({
  style: {
    layout: 'horizontal',
    color: 'black',
    shape: 'pill',
    label: 'subscribe',
    height: 35,
    tagline: false
  },
  createSubscription: function (data, actions) {
    // Grab the selected tier from the form
    const selectedTier = document.querySelector('input[name="tier"]:checked')?.value;
    const planId = planIdByTier[selectedTier];
     

    if (!planId) {
      alert('Please select a valid pricing tier.');
      throw new Error('Missing plan ID');
    }

    return actions.subscription.create({
      plan_id: planId,
      custom: document.getElementById('user_uuid').value
    });
  },
  onApprove: function (data, actions) {
        
        const selectedTier = document.querySelector('input[name="tier"]:checked')?.value;
        const planId = planIdByTier[selectedTier];
        
        const payload = {
            subscriptionID: data.subscriptionID,
            user_uuid: document.getElementById('user_uuid').value,
            tier: planId 
            
        }
      fetch('handle_success.php', {
          method: 'POST',
          body: JSON.stringify(payload),
          headers: {
            'X-Requested-With': 'XMLHttpRequest' // this triggers the JSON response
          }
        })
          .then(response => response.json())
          .then(data => {
              console.log(data);
            if (data.status === 'ok') {
                console.log('We got everything');
              window.location.href = '/dashboards/login.php?status=confirmemail';
            } else {
                email = document.getElementById('mka_email').value
                console.log('Something missing')
                window.location.href = '/dashboards/500_signup.php?error=withresponse&email='+email;
              
            }
          })
          .catch(err => {
              email = document.getElementById('mka_email').value
              window.location.href = '/dashboards/500_signup.php?error=noresponse&email='+email;
            
          });  
      
    
  }
}).render('#paypal-button-container');
</script>




<?php 
            if (!empty($_SESSION['toast_error'])){
?>
<script>
    $(document).ready(function () {
        
        
        toastr.options = {
              "positionClass": "toast-top-center",
              "timeOut": "5000"
            };
        toastr.error("<?= addslashes($_SESSION['toast_error']) ?>");




                });



</script>

<?php unset($_SESSION['toast_error']); 

} else if (!empty($_SESSION['toast_success'])) {
    
    
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
        }, 3000);
    });
</script>
<?php unset($_SESSION['toast_success']); } ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>

</html>