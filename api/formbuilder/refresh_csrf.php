<?php
session_name('mka_public');
session_start();

$newToken = bin2hex(random_bytes(32));
$_SESSION['csrf_public_token'] = $newToken;

header('Content-Type: application/json');
echo json_encode(['csrf_token' => $newToken]);
