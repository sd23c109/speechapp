<?php
require_once '/opt/mka/bootstrap.php';
use MKA\Paypal\IpnHandler;

$raw_post_data = file_get_contents('php://input');
$verified = IpnHandler::verify($raw_post_data);

if ($verified) {
    
    $data = $_POST;
    
    IpnHandler::handle($data);
} else {
    error_log("Invalid IPN received.");
}