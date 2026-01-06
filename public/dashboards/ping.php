<?php
session_start();

// CSRF token check
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$serverToken = $_SESSION['csrf_token'] ?? '';

if (!$serverToken || !$clientToken || !hash_equals($serverToken, $clientToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token mismatch']);
    exit;
}

// Reset session timer
$_SESSION['LAST_ACTIVITY'] = time();
http_response_code(200);
echo json_encode(['status' => 'ok']);
