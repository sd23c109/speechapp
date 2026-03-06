<?php
// Thin public wrapper - just authentication and delegation
session_start();

// Now call the real logic
require_once '/opt/mka/api/admin/create_upgrade_checkout.php';

