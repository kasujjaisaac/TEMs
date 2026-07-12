<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'onyx_db');
define('DB_USER', 'u963586588_Business');
define('DB_PASS', 'Netflix@2@!@#');

function onyx_create_pdo(): PDO
{
    return new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

// Global PDO Connection
if (!defined('ONYX_SKIP_AUTO_CONNECT')) {
    try {
        $pdo = onyx_create_pdo();
    } catch (PDOException $e) {
        // In production, don't show the password/user in the error
        die("Database connection failed.");
    }
}
?>
