<?php
header('Content-Type: application/json');

$root = $_SERVER['DOCUMENT_ROOT'];
$tenant_id = $_GET['tenant_id'] ?? '80';

$response = [
    'status' => 'test',
    'tenant_id' => $tenant_id,
    'root' => $root,
    'files_exist' => [
        'api_dashboard.php' => file_exists($root . '/api/dashboard/api_dashboard.php'),
    ]
];

// Try to connect to database
try {
    $config_file = $root . '/config/database.php';
    if (file_exists($config_file)) {
        require_once($config_file);
        $response['database'] = 'config loaded';
    } else {
        $response['database'] = 'config not found at ' . $config_file;
    }
} catch (Exception $e) {
    $response['database'] = 'error: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>