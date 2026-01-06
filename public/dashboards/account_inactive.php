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
    
    $result = SignupHandler::reactivateUser($_POST);
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
    <title>Reactivate Account | MKAdvantage Online Tool kit</title>
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
                                            <img src="https://www.mkadvantage.com/wp-content/uploads/2025/03/PNGVersion.png" alt="dark logo" height="170">
                                        </a>
                                        <a href="index.html" class="logo-light">
                                            <img src="https://www.mkadvantage.com/wp-content/uploads/2025/03/PNGVersion.png" alt="logo" height="170">
                                        </a>
                                        <h4 class="fw-bold mt-4">Reactivate Your Account</h4>
                                        <p class="text-muted w-lg-75 mx-auto">Welcome Back.  Put in your email address and choose a plan.</p>
                                    </div>

                                    <form id="reactivateForm" method="POST" action="">
                                         <input type="hidden" name="user_uuid" id="user_uuid" value="">
                                        <!--<div class="mb-3">
                                            <label for="userName" class="form-label">Name <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-user text-muted fs-xl"></i></span>
                                                <input type="text" class="form-control" id="mka_name" name="mka_name" placeholder="Full Name" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="userName" class="form-label">Business Name <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-building text-muted fs-xl"></i></span>
                                                <input type="text" class="form-control" id="mka_company" name="mka_company" placeholder="Business Name" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="userName" class="form-label">Domain Name <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-world text-muted fs-xl"></i></span>
                                                <input type="text" class="form-control" id="mka_domain" name="mka_domain" placeholder="example: yourbusiness.com" required>
                                            </div>
                                        </div> -->

                                        <div class="mb-3">
                                            <label for="userEmail" class="form-label">Email address <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ti ti-mail text-muted fs-xl"></i></span>
                                                <input type="email" class="form-control" id="mka_email" name="mka_email" placeholder="you@example.com" required>
                                            </div>
                                        </div>
                                                                               

                                        <!--
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input form-check-input-light fs-14" type="checkbox" checked id="termAndPolicy">
                                                <label class="form-check-label" for="termAndPolicy">Agree to the Terms & Policies</label>
                                            </div>
                                        </div>  -->
                                        

                                        <div class="d-grid" id="startButtons">
                                            <button type="button" class="btn btn-primary fw-semibold py-2" id="payNowTrigger">
                                              Reactivate
                                            </button>
                                            
                                        </div>
                                    </form>

                                    <p class="text-muted text-center mt-4 mb-0">
                                        Already have an account? <a href="login.php" class="text-decoration-underline link-offset-3 fw-semibold">Login</a>
                                    </p>

                                    <p class="text-center text-muted mt-4 mb-0">
                                       Online Tool Kit by <span class="fw-semibold">MKAdvantage</span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="h-100 position-relative card-side-img rounded-end-4 rounded-end rounded-0 overflow-hidden">
                                    <div class="p-4 card-img-overlay rounded-4 rounded-start-0 auth-overlay d-flex align-items-end justify-content-center">

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
              <h4 class="text-white fw-bold mb-0">Reactivate</h4>
              <p class="text-white-50 mb-0">Click below to reactivate your subscription.</p>
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
            <p class="text-muted small fst-italic">You’ll be charged after completing PayPal checkout. You can cancel anytime from your dashboard.</p>
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
    


// Attach listeners to form fields for live validation
document.querySelectorAll('[id^="mka_email"]').forEach(input => {
  input.addEventListener('input', validateSignupFields);
});



function validateSignupFields() {
  
  const inputs = document.querySelectorAll('[id^="mka_email"]');
  let allFilled = true;

  inputs.forEach(input => {
    if (!input.value.trim()) {
      allFilled = false;
    }
  });

const payButton = document.querySelector('#payNowTrigger');
  if (payButton) {
    payButton.disabled = !allFilled;
  }
}

//PAY AND START NEEDS A USER BEFORE PAYPAL PAYMENT
document.getElementById('payNowTrigger').addEventListener('click', function (e) {
  const form = document.getElementById('reactivateForm');
  const formData = new FormData(form);

  fetch('account_inactive.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
  })
  .then(response => response.json())
  .then(data => {
      console.log(data);
      if (data.status === 'success') {
          document.getElementById('user_uuid').value = data.user_uuid;

          // ✅ Only open offcanvas now that user is validated
          let offcanvasEl = document.getElementById('SignUpPayNow');
          let offcanvas = new bootstrap.Offcanvas(offcanvasEl);
          offcanvas.show();
      } else {
          alert(data.message || 'Account lookup failed.');
      }
  })
  .catch(err => {
      alert('Error verifying account.');
      console.error(err);
  });
});



</script>

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
              console.log('WHAT WE GET:')
              console.log(data);
            if (data.status === 'ok') {
                console.log('We got everything');
              //window.location.href = '/dashboards/login.php';
            } else {
                email = document.getElementById('mka_email').value
                console.log('Something missing')
                //window.location.href = '/dashboards/500_signup.php?error=withresponse&email='+email;
              
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

</body>

</html>