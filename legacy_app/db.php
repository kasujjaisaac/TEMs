<?php
/**
 * Onyx Hub - Database Connection Bridge
 */

define('ONYX_SKIP_AUTO_CONNECT', true);
require_once __DIR__ . '/config.php';

// 3. Establish Connection
try {
    // This creates the $pdo object used by api_support.php
    $pdo = onyx_create_pdo();
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false, 
        "message" => "Database Connection Failed. Check DB_HOST, DB_NAME, DB_USER, and DB_PASS in config.php."
    ]);
    exit;
}
