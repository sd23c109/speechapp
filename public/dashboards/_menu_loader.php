<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$planclass = 'text-bg-primary';
$user_uuid = $_SESSION['user_data']['user_info']['UserUUID'];
$userType  = $_SESSION['user_data']['user_info']['user_type'] ?? 'end_user';

$roleLabel = match($userType) {
    'super_user'       => 'Super User',
    'enterprise_admin' => 'SLP',
    default            => 'Patient / Parent',
};

if ($_SESSION['user_data']['user_info']['Status'] == 'y') {
    $planclass  = 'text-bg-warning';
    $expiresAt  = $_SESSION['user_data']['user_info']['TrialExpires'];
    $now        = new DateTime();
    $expiration = new DateTime($expiresAt);
    $daysRemaining = (int)$now->diff($expiration)->format('%r%a');
    $planname = $roleLabel . ' Trial <a href="profile.php"><span style="color:black; text-decoration:underline;">— ' . $daysRemaining . ' days left. Upgrade Now</span></a>';
} else {
    $planname = $roleLabel;
}


ob_start();
echo "<!-- CSRF TOKEN IN SESSION: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . " -->";
echo "<!-- CSRF TOKEN IN JS: " . (\MKA\Security\CSRFHelper::getToken() ?? 'NOT SET') . " -->";
?>
<script>
    window.config = window.config || {};
    window.config.company_slug = "<?= $_SESSION['user_data']['company_slug'] ?>";
    window.config.company_name = "<?= addslashes($_SESSION['user_data']['company_name']) ?>";
    window.config.csrf_token = "<?= $_SESSION['csrf_token'] ?>";


    // Global fetch patch for CSRF
    (function() {
        const originalFetch = window.fetch;

        window.fetch = function(input, init = {}) {
            init.headers = {
                ...(init.headers || {}),
                'X-CSRF-Token': window.config.csrf_token
            };

            init.credentials = init.credentials || 'include';

            return originalFetch(input, init);
        };


    })();

    if (typeof jQuery !== "undefined") {
        $.ajaxSetup({
            headers: {
                'X-CSRF-Token': window.config.csrf_token
            },
            xhrFields: {
                withCredentials: true
            }
        });
    }
</script>
<?php
if (!empty($_SESSION['csrf_token'])) {
   //to do
    error_log($_SESSION['csrf_token']);

}
?>

<nav class="navbar-default" role="navigation">

    <div class="sidebar-collapse">
        <a class="close-canvas-menu"><i class="fa fa-times"></i></a>

        <div class="nav-header">
            <a href="<?=$GLOBALS['apphome']?>" class="brand-logo">
                <img alt="brand-image" src="img/Logo1.png"  class="sidebar-logo-white" style="width:150px; padding-top:5px; border-radius:10px;" />
                <img alt="brand-image" src="img/Logo1.png" class="sidebar-logo-black" style="width:150px; padding-top:5px; border-radius:10px;" />
            </a>

            <div class="logo-element">
                <img alt="image" src="img/logo-sm.png" height="28" />
            </div>

            <div class="dropdown profile-element">
                <img alt="image" class="rounded-circle" src="<?=!empty($_SESSION['user_data']['avatar']) ? $_SESSION['user_data']['avatar'] : 'img/favicon.ico'?>" width="48" height="48" style="object-fit:cover;" />

                <a data-bs-toggle="dropdown" class="dropdown-toggle" href="#">
                    <span class="d-block mt-1 fw-semibold fs-14 ff-secondary"><?= htmlspecialchars($_SESSION['user_data']['user_info']['Name'] ?? $_SESSION['user_data']['user_info']['Email'] ?? '') ?></span>
                    <span class="text-muted text-xs d-block ff-secondary">Account <b class="caret"></b></span>
                </a>

                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>

                    <li class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

        <ul class="nav metismenu" id="side-menu">



            <?php
            // Menu items
            $menu_file = __DIR__ . '/_menu_' . $GLOBALS['current_dashboard'] . '.php';
            if (file_exists($menu_file)) {
                include $menu_file;
            } else {
                echo '<!-- Menu not found -->';
            }
            ?>

        </ul>
    </div>
</nav>

<!--Trial Modal Reminder -->
<?php

$menu = ob_get_clean();

ob_start();
?>
<!-- Mobile responsive CSS + table JS (injected into every interior page) -->
<link href="/dashboards/css/mobile.css?v=<?= ASSET_VER ?>" rel="stylesheet" type="text/css">

<div class="row border-bottom">
    <nav class="navbar navbar-top" role="navigation">

        <!-- Hamburger: visible on tablets/phones, hidden on desktop (lg+) -->
        <button class="navbar-minimalize" type="button" title="Toggle menu">
            <i class="fa fa-bars"></i>
        </button>

        <div class="navbar-header">
            <div class="d-none d-md-flex" style="padding: 20px;">
                Plan:&nbsp;&nbsp;<span class="badge <?=$planclass?>" style="font-size:16px;"><?=$planname?></span>
            </div>
        </div>

        <ul class="nav navbar-top-links navbar-right">
            <li style="padding: 20px">
                <span class="me-2 text-muted welcome-message">Welcome.</span>
            </li>

            <li>
                <a href="logout.php" class="navbar-top-item">
                    <i class="fa fa-sign-out"></i>
                    <span class="align-middle d-none d-md-inline-flex">Log out</span>
                </a>
            </li>

        </ul>
    </nav>
</div>

<!-- mobile-tables.js: loaded after Bootstrap, before page-specific scripts -->
<script src="/dashboards/js/mobile-tables.js"></script>
<?php
$topbar = ob_get_clean();

ob_start();
?>
<div class="modal fade" id="trialModalReminder" tabindex="-1" aria-labelledby="trialModalReminderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="headerConfigModalLabel">Welcome!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="mb-6 text-center">
                    <p>We hope you are enjoying the trial.  Your current trial expires in </p>
                    <p>14days</p>

                </div>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="">Purchase a Plan</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

            </div>
        </div>
    </div>
</div>
<?php
$trialmodal = ob_get_clean();
?>
