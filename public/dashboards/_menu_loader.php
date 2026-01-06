<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$planname='TRIAL';
$planclass = 'text-bg-primary';
$user_uuid = $_SESSION['user_data']['user_info']['UserUUID'];


if ($_SESSION['user_data']['user_info']['IsTrial'] == 'y'){

    $planclass = 'text-bg-warning';
    $expiresAt = $_SESSION['user_data']['user_info']['TrialExpires'];
    $now = new DateTime();
    $expiration = new DateTime($expiresAt);

    $interval = $now->diff($expiration);
    $daysRemaining = (int)$interval->format('%r%a');
    $planname .= ' <a href="profile.php"><span style="color:black; text-decoration:underline;"> You have '.$daysRemaining.' days left in your trial.  Upgrade Now</span></a>';

} else {
    $planname = $_SESSION['user_data']['plan_name'];
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
    ?>
    <script src="../assets/formbuilder/mka-js/idle-timeout.js"></script>
    <?php

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
                <img alt="image" class="rounded-circle" src="<?=!empty($_SESSION['user_data']['avatar']) ? $_SESSION['user_data']['avatar'] : 'img/favicon.ico'?>" height="48" />

                <a data-bs-toggle="dropdown" class="dropdown-toggle" href="#">
                    <span class="d-block mt-1 fw-semibold fs-14 ff-secondary"><?=$_SESSION['user_data']['company_name']?></span>
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
            <li>
                <a href="#">
                    <i data-lucide="smile"></i>
                    <span class="nav-label">Speech App</span>
                    <span class="fa arrow"></span>
                </a>
                <ul class="nav nav-second-level collapse">


                        <li><a href="speechapp.php">Exercises</a></li>
                   

                </ul>
            </li>


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
<div class="row border-bottom">
    <nav class="navbar navbar-top" role="navigation">
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
