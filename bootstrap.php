<?php

if (!defined('MKA_BASE')) {
    define('MKA_BASE', __DIR__);
}

require_once __DIR__ . '/config/db.php'; // local app DB

//require_once __DIR__ . '/config/load_secrets_mka.php'; // loads and defines HIPAA constants

// Main EC2 DB connection
try {
    $GLOBALS['pdo'] = new PDO("mysql:host=" . MKA_DB_HOST . ";port=" . MKA_DB_PORT . ";dbname=" . MKA_DB_NAME . ";charset=utf8mb4", MKA_DB_USER, MKA_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC

    ]);
} catch (PDOException $e) {
    die("Main DB Connection failed: " . $e->getMessage());
}

