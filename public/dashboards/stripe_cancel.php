<?php
session_start();

$_SESSION['toast_warning'] = 'Checkout was cancelled. You can upgrade any time.';

header('Location: /dashboards/login.php');
exit;
