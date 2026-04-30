<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$GLOBALS['current_dashboard'] = 'speechapp';
include('../../dashboards/_init.php');
include('_menu_loader.php');

require_once '/opt/mka/vendor/autoload.php';
require_once '/opt/mka/core/Payment/StripeConfig.php';
$stripePublishableKey = \MKA\Payment\StripeConfig::getPublishableKey();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title> MKAdvantage Dashboard - Your Online Business Toolkit</title>
    <meta content="MKAdvantage" name="author" />

    <!-- Favicon -->
    <link rel="shortcut icon"href="/dashboards/img/favicon.ico">

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
    

    <!-- Style css -->
    <link href="css/style.min.css?v=<?= ASSET_VER ?>" rel="stylesheet" type="text/css">

    <!-- Head.js -->
    <script src="js/head.js"></script>
    <!-- Stripe -->
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .plan-card { border: 2px solid #dee2e6; border-radius: .75rem; padding: 1.25rem; cursor: pointer; transition: border-color .15s, box-shadow .15s; background: #fff; }
        .plan-card:hover { border-color: #0d6efd; }
        .plan-card.selected { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
        .plan-price { font-size: 2rem; font-weight: 700; color: #0d6efd; }
        .feature-check { color: #198754; margin-right: 6px; }
        #oc-card-element { padding: 12px; border: 1px solid #dee2e6; border-radius: .375rem; background: white; }
        #oc-card-errors { color: #dc3545; margin-top: 6px; font-size: .875rem; }
    </style>
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
            <?php
                $avatarSrc   = !empty($_SESSION['user_data']['avatar']) ? htmlspecialchars($_SESSION['user_data']['avatar']) : 'img/favicon.ico';
                $userName    = htmlspecialchars($_SESSION['user_data']['user_info']['Name'] ?? '');
                $company     = htmlspecialchars($_SESSION['user_data']['user_info']['company_name'] ?? '');
                $isSLP       = in_array($_SESSION['user_data']['user_type'] ?? '', ['enterprise_admin', 'super_user']);
                $memberSince = htmlspecialchars($_SESSION['user_data']['user_info']['CreatedAt'] ?? '');
                $defaultPlan = $isSLP ? 'slp' : 'patient';
                $userEmail   = htmlspecialchars($_SESSION['user_data']['user_info']['Email'] ?? '');
            ?>

            <!-- Profile header bar -->
            <div class="card-body" style="padding:10px;">
                <div class="d-flex justify-content-between align-items-center flex-wrap p-3 bg-white rounded shadow-sm">
                    <div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
                        <img id="headerAvatarPreview"
                             src="<?= $avatarSrc ?>"
                             alt="avatar"
                             class="rounded-circle"
                             style="width:64px;height:64px;object-fit:cover;border:2px solid #dee2e6;">
                        <div>
                            <h4 class="fw-bold mb-1" id="headerName"><?= $userName ?></h4>
                            <?php if ($isSLP && $company !== ''): ?>
                            <p class="text-muted mb-1" id="headerCompany"><?= $company ?></p>
                            <?php else: ?>
                            <p class="text-muted mb-1" id="headerCompany" style="display:none"></p>
                            <?php endif; ?>
                            <span class="badge bg-soft-primary text-primary fw-medium fs-xs">
                                Member Since: <?= $memberSince ?>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="offcanvas" data-bs-target="#upgradeOffcanvas">Upgrade Plan</button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel Plan</button>
                    </div>
                </div>
            </div>

            <!-- Edit Profile card -->
            <div class="row mx-2 mt-3">
                <div class="col-lg-6 col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-4">

                                <!-- Avatar upload -->
                                <div class="text-center" style="min-width:110px;">
                                    <div class="position-relative d-inline-block" style="cursor:pointer;" onclick="document.getElementById('avatarInput').click();" title="Click to change photo">
                                        <img id="avatarPreview"
                                             src="<?= $avatarSrc ?>"
                                             data-original="<?= $avatarSrc ?>"
                                             alt="avatar"
                                             class="rounded-circle"
                                             style="width:100px;height:100px;object-fit:cover;border:2px solid #dee2e6;">
                                        <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:26px;height:26px;">
                                            <i class="fa fa-camera text-white" style="font-size:12px;"></i>
                                        </div>
                                        <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                                    </div>
                                    <div class="text-muted small mt-2">Click to change photo</div>
                                </div>

                                <!-- Profile fields -->
                                <div class="flex-grow-1">
                                    <form id="profileForm">
                                        <div class="mb-3">
                                            <label for="profileName" class="form-label fw-semibold">Display Name</label>
                                            <input type="text" class="form-control" id="profileName" name="name"
                                                   value="<?= $userName ?>" maxlength="100" required>
                                        </div>
                                        <?php if ($isSLP): ?>
                                        <div class="mb-3">
                                            <label for="profileCompany" class="form-label fw-semibold">Practice / Company Name</label>
                                            <input type="text" class="form-control" id="profileCompany" name="company_name"
                                                   value="<?= $company ?>" maxlength="100"
                                                   placeholder="e.g. Springfield Speech Clinic">
                                        </div>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        

            <div class="footer">
           
            </div>

        </div>
    </div>
    
<!-- Cancel Plan Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-danger"><i class="fa fa-triangle-exclamation me-2"></i>Cancel Subscription?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-1">Your subscription will be cancelled immediately and you will lose access to your account.</p>
        <p class="text-muted small mb-0">You can subscribe again at any time.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
        <button type="button" class="btn btn-danger" id="confirmCancelBtn">
            <span id="cancelBtnText">Yes, Cancel My Plan</span>
            <span id="cancelSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Upgrade Plan Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="upgradeOffcanvas" style="width:420px; max-width:100%;">
    <div class="offcanvas-header text-white" style="background: linear-gradient(135deg,#0d6efd,#6610f2);">
        <div>
            <h5 class="offcanvas-title fw-bold mb-0">Choose Your Plan</h5>
            <p class="mb-0 opacity-75 small">Secure, encrypted payment. Cancel anytime.</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body px-4 py-3">

        <!-- Plan cards -->
        <p class="fw-semibold mb-2">Select your plan:</p>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="plan-card <?= $defaultPlan === 'patient' ? 'selected' : '' ?>" id="oc-card-patient" onclick="ocSelectPlan('patient')">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <input class="plan-radio me-1" type="radio" name="oc_plan" id="oc-plan-patient" value="patient" <?= $defaultPlan === 'patient' ? 'checked' : '' ?>>
                            <label class="fw-semibold" for="oc-plan-patient">Patient / Parent</label>
                        </div>
                        <?php if ($defaultPlan === 'patient'): ?>
                            <span class="badge bg-primary" style="font-size:.7rem;">Recommended</span>
                        <?php endif; ?>
                    </div>
                    <div class="plan-price">$10<span class="fs-6 text-muted fw-normal">/mo</span></div>
                    <ul class="list-unstyled mt-2 mb-0 small">
                        <li><i class="fa fa-check feature-check"></i>Unlimited speech exercises</li>
                        <li><i class="fa fa-check feature-check"></i>Progress tracking</li>
                        <li><i class="fa fa-check feature-check"></i>All therapy modules</li>
                        <li><i class="fa fa-check feature-check"></i>SLP connection support</li>
                    </ul>
                </div>
            </div>
            <div class="col-12">
                <div class="plan-card <?= $defaultPlan === 'slp' ? 'selected' : '' ?>" id="oc-card-slp" onclick="ocSelectPlan('slp')">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <input class="plan-radio me-1" type="radio" name="oc_plan" id="oc-plan-slp" value="slp" <?= $defaultPlan === 'slp' ? 'checked' : '' ?>>
                            <label class="fw-semibold" for="oc-plan-slp">Speech-Language Pathologist</label>
                        </div>
                        <?php if ($defaultPlan === 'slp'): ?>
                            <span class="badge bg-primary" style="font-size:.7rem;">Recommended</span>
                        <?php endif; ?>
                    </div>
                    <div class="plan-price">$100<span class="fs-6 text-muted fw-normal">/mo</span></div>
                    <ul class="list-unstyled mt-2 mb-0 small">
                        <li><i class="fa fa-check feature-check"></i>Manage unlimited patients</li>
                        <li><i class="fa fa-check feature-check"></i>Custom exercise programs</li>
                        <li><i class="fa fa-check feature-check"></i>Full analytics dashboard</li>
                        <li><i class="fa fa-check feature-check"></i>Priority support</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Stripe card element -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Card Information <span class="text-danger">*</span></label>
            <div id="oc-card-element"></div>
            <div id="oc-card-errors" role="alert"></div>
        </div>

        <div class="alert alert-success py-2 mb-3">
            <i class="fa fa-shield-halved me-1"></i>
            <small>Secure, encrypted payment. Cancel anytime.</small>
        </div>

        <div class="d-grid">
            <button type="button" id="oc-subscribe-btn" class="btn btn-primary btn-lg fw-semibold">
                <span id="oc-btn-text">Subscribe for $<?= $defaultPlan === 'slp' ? '100' : '10' ?>/month</span>
                <span id="oc-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>

        <p class="text-muted text-center mt-3 mb-0 small">
            By subscribing, you agree to our Terms of Service and Privacy Policy.
        </p>
    </div>
</div>

<script>
// ── Avatar upload ────────────────────────────────────────────────────────────
document.getElementById('avatarInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatarPreview').src = e.target.result;
        document.getElementById('headerAvatarPreview').src = e.target.result;
    };
    reader.readAsDataURL(file);

    const formData = new FormData();
    formData.append('avatar', file);

    fetch('api/admin/update_avatar.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'ok') {
                toastr.success('Profile photo updated!');
                const url = data.avatar_url + '?t=' + Date.now();
                document.querySelectorAll('.profile-element img.rounded-circle').forEach(img => img.src = url);
            } else {
                toastr.error(data.message || 'Upload failed');
                const orig = document.getElementById('avatarPreview').dataset.original;
                document.getElementById('avatarPreview').src = orig;
                document.getElementById('headerAvatarPreview').src = orig;
            }
        })
        .catch(() => toastr.error('Upload failed'));
});

// ── Profile form save ────────────────────────────────────────────────────────
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('api/admin/update_profile.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'ok') {
                toastr.success('Profile updated!');
                document.getElementById('headerName').textContent = data.name;
                const companyEl    = document.getElementById('headerCompany');
                const companyInput = document.getElementById('profileCompany');
                if (companyEl && companyInput) {
                    const val = companyInput.value.trim();
                    companyEl.textContent = val;
                    companyEl.style.display = val ? '' : 'none';
                }
            } else {
                toastr.error(data.message || 'Save failed');
            }
        })
        .catch(() => toastr.error('Save failed'));
});

// ── Cancel plan ──────────────────────────────────────────────────────────────
document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    const btn     = this;
    const text    = document.getElementById('cancelBtnText');
    const spinner = document.getElementById('cancelSpinner');
    btn.disabled  = true;
    text.classList.add('d-none');
    spinner.classList.remove('d-none');

    fetch('api/admin/cancel_subscription.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'ok') {
                toastr.options = { positionClass: 'toast-top-center', timeOut: 3000, onHidden: () => window.location.href = '/dashboards/logout.php' };
                toastr.success('Subscription cancelled. Logging you out…');
            } else {
                toastr.error(data.message || 'Cancellation failed. Please contact support.');
                btn.disabled = false;
                text.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        })
        .catch(() => {
            toastr.error('Network error. Please try again.');
            btn.disabled = false;
            text.classList.remove('d-none');
            spinner.classList.add('d-none');
        });
});

// ── Stripe upgrade ───────────────────────────────────────────────────────────
const ocPlanPrices = { patient: '$10', slp: '$100' };
let ocSelectedPlan = '<?= $defaultPlan ?>';

function ocSelectPlan(plan) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('oc-card-' + plan).classList.add('selected');
    document.getElementById('oc-plan-' + plan).checked = true;
    ocSelectedPlan = plan;
    document.getElementById('oc-btn-text').textContent = 'Subscribe for ' + ocPlanPrices[plan] + '/month';
}

const stripe      = Stripe('<?= htmlspecialchars($stripePublishableKey) ?>');
const ocElements  = stripe.elements();
const ocCardEl    = ocElements.create('card', {
    style: {
        base: { fontSize: '16px', color: '#32325d', fontFamily: '"Inter", sans-serif', '::placeholder': { color: '#aab7c4' } },
        invalid: { color: '#dc3545' }
    }
});

// Mount card element when offcanvas opens (avoids mounting to hidden element)
document.getElementById('upgradeOffcanvas').addEventListener('shown.bs.offcanvas', function() {
    ocCardEl.mount('#oc-card-element');
});
document.getElementById('upgradeOffcanvas').addEventListener('hidden.bs.offcanvas', function() {
    ocCardEl.unmount();
    document.getElementById('oc-card-errors').textContent = '';
});

ocCardEl.on('change', e => {
    document.getElementById('oc-card-errors').textContent = e.error ? e.error.message : '';
});

document.getElementById('oc-subscribe-btn').addEventListener('click', async function() {
    const btn     = this;
    const text    = document.getElementById('oc-btn-text');
    const spinner = document.getElementById('oc-spinner');
    btn.disabled  = true;
    text.classList.add('d-none');
    spinner.classList.remove('d-none');

    try {
        const { paymentMethod, error } = await stripe.createPaymentMethod({
            type: 'card',
            card: ocCardEl,
            billing_details: { email: '<?= $userEmail ?>', name: '<?= $userName ?>' }
        });
        if (error) throw new Error(error.message);

        const body = new URLSearchParams({
            payment_method_id: paymentMethod.id,
            selected_plan: ocSelectedPlan
        });

        const resp   = await fetch('api/admin/subscribe.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const result = await resp.json();

        if (result.success) {
            toastr.options = { positionClass: 'toast-top-center', timeOut: 3000, onHidden: () => window.location.reload() };
            toastr.success('Subscription activated! Refreshing…');
        } else {
            throw new Error(result.error || 'Subscription failed.');
        }
    } catch (err) {
        toastr.error(err.message || 'Payment failed. Please try again.');
        btn.disabled = false;
        text.classList.remove('d-none');
        spinner.classList.add('d-none');
    }
});
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
    <script src="js/inspinia.js?v=<?= ASSET_VER ?>"></script>

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

