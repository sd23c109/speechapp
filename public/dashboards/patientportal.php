<?php

$GLOBALS['current_dashboard'] = 'patientportal';
include('../../dashboards/_init.php');
include('_menu_loader.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title> Dashboard</title>
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


    <script src="plugins/jquery/js/jquery.min.js"></script>


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
                <h1>Visual Speech Practice Guide</h1>
                <span class="text-muted">Click on Exercises Below To Get Started</span>

            </div>


        </div>



        <div class="footer">

        </div>

    </div>
</div>

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
