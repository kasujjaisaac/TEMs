<?php
header('Content-Type: application/json');

$root = $_SERVER['DOCUMENT_ROOT'];
$debug = [];

// Test 1: Check files exist
$debug['files'] = [
    'session_handler.php' => file_exists($root . '/session_handler.php') ? 'EXISTS' : 'MISSING',
    'routeros_api.class.php' => file_exists($root . '/routeros_api.class.php') ? 'EXISTS' : 'MISSING',
    'bridge_telemetry.php' => file_exists($root . '/api/bridge_telemetry.php') ? 'EXISTS' : 'MISSING',
];

// Test 2: Try to load session
$debug['session'] = [];
try {
    require_once($root . '/session_handler.php');
    $debug['session']['loaded'] = 'YES';
    $debug['session']['pdo_exists'] = isset($pdo) ? 'YES' : 'NO';
    $debug['session']['tenant_id'] = $tenant_id ?? 'NOT SET';
    $debug['session']['user_id'] = $_SESSION['user_id'] ?? 'NOT SET';
} catch (Exception $e) {
    $debug['session']['error'] = $e->getMessage();
}

// Test 3: Check database connection
$debug['database'] = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT 1");
        $debug['database']['connection'] = 'OK';
        
        // Check tables
        $tables = ['transactions', 'accounts', 'routers'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $debug['database'][$table] = $stmt->rowCount() > 0 ? 'EXISTS' : 'MISSING';
        }
    } catch (Exception $e) {
        $debug['database']['error'] = $e->getMessage();
    }
} else {
    $debug['database']['error'] = 'PDO not initialized';
}

// Test 4: Check if shell_exec works
$debug['shell_exec'] = [];
$debug['shell_exec']['disabled'] = function_exists('shell_exec') ? 'NO' : 'YES';
if (function_exists('shell_exec')) {
    $test = shell_exec('php -v 2>&1');
    $debug['shell_exec']['test'] = strlen($test) > 0 ? 'WORKS' : 'NO OUTPUT';
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>