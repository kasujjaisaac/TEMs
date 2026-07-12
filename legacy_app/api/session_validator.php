<?php
// Ensure session is recognized globally across Onyx Hotspot
session_set_cookie_params(['path' => '/']);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session expired']);
    exit;
}

$tenant_id = $_SESSION['tenant_id'];

/** 
 * ONYX PATH FIX:
 * We use __DIR__ to ensure we go up exactly one level from the /api/ folder
 * to find your root configuration.
 */
$root = dirname(__DIR__); 

if (file_exists($root . '/config.php')) {
    require_once $root . '/config.php';
} else {
    // Fallback if your config is named differently
    require_once $root . '/db_config.php'; 
}

require_once $root . '/routeros_api.class.php';

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database object ($pdo) not found.']);
    exit;
}