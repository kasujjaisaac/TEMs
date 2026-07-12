<?php
// Test script to debug the API
header('Content-Type: application/json');

$root = $_SERVER['DOCUMENT_ROOT'];
$debug = [];

// Test 1: Check files exist
$debug['files'] = [
    'session_handler.php' => file_exists($root . '/session_handler.php'),
    'routeros_api.class.php' => file_exists($root . '/routeros_api.class.php'),
    'bridge_telemetry.php' => file_exists($root . '/api/bridge_telemetry.php'),
];

// Test 2: Try to load session
try {
    require_once($root . '/session_handler.php');
    $debug['session'] = 'Loaded successfully';
    $debug['pdo_exists'] = isset($pdo);
    $debug['tenant_id'] = $tenant_id ?? 'Not set';
} catch (Exception $e) {
    $debug['session'] = 'Error: ' . $e->getMessage();
}

// Test 3: Check database tables
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'transactions'");
        $debug['transactions_table'] = $stmt->rowCount() > 0 ? 'Exists' : 'Missing';
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'accounts'");
        $debug['accounts_table'] = $stmt->rowCount() > 0 ? 'Exists' : 'Missing';
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'routers'");
        $debug['routers_table'] = $stmt->rowCount() > 0 ? 'Exists' : 'Missing';
    } catch (Exception $e) {
        $debug['database_error'] = $e->getMessage();
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>