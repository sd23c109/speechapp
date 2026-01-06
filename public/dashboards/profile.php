<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$GLOBALS['current_dashboard'] = 'formbuilder';
include('../../dashboards/_init.php');
include('_menu_loader.php');
error_log(json_encode($_SESSION));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title> MKAdvantage Dashboard - Your Online Business Toolkit</title>
    <meta content="MKAdvantage" name="author" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="img/favicon.ico">

    <!-- Toastr css -->
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">

    <!-- Gritter -->
    <link href="plugins/gritter/css/jquery.gritter.css" rel="stylesheet">

    <!-- Bootstrap css -->
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">

    <!-- Icons css -->
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Animate.css -->
    <link href="plugins/animate/css/animate.min.css" rel="stylesheet">
    
    <!-- Formbuilder -->
    <script src="plugins/jquery/js/jquery.min.js"></script>
    <link rel="stylesheet" href="/mka-assets/formbuilder/form-builder.min.css">
    <script src="/mka-assets/formbuilder/form-builder.min.js"></script>
    <script src="/mka-assets/formbuilder/form-render.min.js"></script>


    <!-- Style css -->
    <link href="css/style.min.css" rel="stylesheet" type="text/css">

    <!-- Head.js -->
    <script src="js/head.js"></script>
</head>

<body>

    <div id="wrapper">
  
        <?=$menu?>
        <!-- Sidenav Menu End -->

        <div id="page-wrapper" class="gray-bg dashbard-1">

            <!-- Topbar -->
            <?=$topbar?>

            <div class="row border-bottom white-bg dashboard-header ">

                <div class="col-xl-6">
                    <h1>User Profile</h1>
                    <span class="text-muted">Manage your user information from here.</span>
                
                </div> 
          
         
            
            </div>
            <div class="card-body" style="padding:10px;">
                <div class="d-flex justify-content-between align-items-center flex-wrap p-3 bg-white rounded shadow-sm">

                    <!-- Left Section: Avatar + Name + Company -->
                    <div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
                        <div class="avatar avatar-xxl">
                            <img src="img/favicon.ico" alt="avatar-2" class="img-fluid img-thumbnail rounded-circle">
                        </div>
                        <div>
                            <h4 class="text-nowrap fw-bold mb-1">
                                <?= htmlspecialchars($_SESSION['user_data']['user_info']['Name']) ?>
                            </h4>
                            <p class="text-muted mb-1">
                                <?= htmlspecialchars($_SESSION['user_data']['user_info']['company_name']) ?>
                            </p>
                            <span class="badge bg-soft-primary text-primary fw-medium fs-xs">
                                Member Since: <?= htmlspecialchars($_SESSION['user_data']['user_info']['CreatedAt']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Right Section: Buttons -->
                    <div class="d-flex gap-2">
                     <!-- Upgrade Button -->
                     <?php
                       if ($_SESSION['user_data']['user_info']['IsTrial'] == 'y') {  
                     ?>
                           <button type="button" class="btn btn-outline-success" id="payNowTrigger" data-bs-toggle="offcanvas" data-bs-target="#SignUpPayNow">
                           Upgrade Plan
                           </button>
                     <?php
                       } else {
                       
                     ?>
                          <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                            Upgrade Plan
                          </button>
                     <?php
                       }
                     ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#downgradeModal">
                            Downgrade Plan
                          </button>
                         <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            Cancel Plan
                          </button>
                       
                    </div>

                </div>

            </div>

        

            <div class="footer">
           
            </div>

        </div>
    </div>
    
    <?php
$currentTier = strtolower($_SESSION['user_data']['plan_name']) ?? 'pro'; 
$availablePlans = [
    'starter' => 'Starter (1 Form)',
    'lite' => 'Lite (10 Forms)',
    'standard' => 'Standard (25 Forms)',
    'pro' => 'Pro (Unlimited Forms)'
];
$planOrder = ['starter', 'lite', 'standard', 'pro'];
?>
 <!--Downgrade Modal-->
<div class="modal fade" id="downgradeModal" tabindex="-1" aria-labelledby="downgradeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="downgradeModalLabel">Select Downgrade Plan</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="downgradeForm" action="/dashboards/actions/downgrade_plan.php" method="post">
          <input type="hidden" name="subscription_id" value="<?= htmlspecialchars($_SESSION['user_data']['subscription_id']) ?>">
          
          
          <?php foreach ($planOrder as $plan): ?>
              <?php
              // Disable if current or higher plan
              $disabled = (array_search($plan, $planOrder) >= array_search($currentTier, $planOrder)) ? 'disabled' : '';
              ?>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="new_tier" id="plan_<?= $plan ?>" value="<?= $plan ?>" <?= $disabled ?>>
                <label class="form-check-label <?= $disabled ? 'text-muted' : '' ?>" for="plan_<?= $plan ?>">
                  <?= $availablePlans[$plan] ?> <?= $disabled ? '(Not available)' : '' ?>
                </label>
              </div>
          <?php endforeach; ?>
          
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="downgradeForm">Confirm Downgrade</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="upgradeForm" action="/dashboards/actions/upgrade_plan.php" method="post">
        <div class="modal-header">
          <h5 class="modal-title">Select a Plan to Upgrade To</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="subscription_id" value="<?= htmlspecialchars($_SESSION['user_data']['subscription_id']) ?>">

          <?php foreach ($planOrder as $plan): ?>
            <?php
              $disabled = (array_search($plan, $planOrder) <= array_search($currentTier, $planOrder)) ? 'disabled' : '';
              $labelClass = $disabled ? 'text-muted' : '';
             
                  
                  
            ?>
            <div class="form-check mb-2">
              <input class="form-check-input" type="radio" name="new_tier" id="upgrade_<?= $plan ?>" value="<?= $plan ?>" <?= $disabled ?>>
              <label class="form-check-label <?= $labelClass ?>" for="upgrade_<?= $plan ?>">
                <?= $availablePlans[$plan] ?> <?= $disabled ? '(Not available)' : '' ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Confirm Upgrade</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!--Cancel Modal-->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="downgradeModalLabel">Cancel Plan?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="cancelForm" action="/dashboards/actions/cancel_plan.php" method="post">
        <input type="hidden" name="subscription_id" value="<?= htmlspecialchars($_SESSION['user_data']['subscription_id']) ?>"> 
        </form>
        <p>Are you sure?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
        <button type="submit" class="btn btn-danger" form="cancelForm">Confirm Cancellation</button>
      </div>
    </div>
  </div>
</div>

  <div class="offcanvas offcanvas-end overflow-hidden" tabindex="-1" id="SignUpPayNow" aria-modal="true" role="dialog">
        <div class="d-flex justify-content-between text-bg-primary gap-2 p-3" style="background-image: url(images/user-bg-pattern.png);">
            <input type="hidden" name="user_uuid" id="user_uuid" value="<?=$_SESSION['user_data']['user_uuid']?>">
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

<script>
['downgradeForm', 'upgradeForm', 'cancelForm'].forEach(id => {
  document.getElementById(id).addEventListener('submit', function(e) {
      
      if (!document.querySelector(`#${id} input[name="new_tier"]:checked`) && id !== 'cancelForm') {
          e.preventDefault();
          alert('Please select a plan before confirming.');
      }
  });
});
</script>

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
              window.location.reload();
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




<?php if (!empty($_SESSION['flash_message'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    toastr.success("<?= addslashes($_SESSION['flash_message']) ?>");
});
</script>
<?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>


   
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

    <!-- Flot -->
    <script src="plugins/flot/js/jquery.flot.js"></script>
    <script src="plugins/jquery-flot-tooltip/js/jquery.flot.tooltip.min.js"></script>
    <script src="plugins/flot-spline/js/jquery.flot.spline.js"></script>
    <script src="plugins/jquery-flot-resize/js/index.js"></script>

    <!-- Peity -->
    <script src="plugins/peity/js/jquery.peity.min.js"></script>

    <!-- Peity Chart Demo js -->
    <script src="js/demo/peity-demo.js"></script>

    <!-- jQuery UI -->
    <script src="plugins/jquery-ui/js/jquery-ui.min.js"></script>

    <!-- GITTER -->
    <script src="plugins/gritter/js/jquery.gritter.js"></script>

    <!-- Sparkline -->
    <script src="plugins/jquery-sparkline/js/jquery.sparkline.min.js"></script>

    <!-- Sparkline demo data  -->
    <script src="js/demo/sparkline-demo.js"></script>

    <!-- ChartJS-->
    <script src="plugins/chartjs/js/Chart.min.js"></script>

    <!-- Toastr -->
    <script src="plugins/toastr/js/toastr.min.js"></script>

   

</body>

</html>

