<?php
require_once('../config.php'); // Database connection if needed for validation
require_once('RouterosAPI.php'); // RouterOS API class

header('Content-Type: application/json');

// Accept POST data from servers.php
$server_id     = $_POST['id'] ?? null;
$ip_address    = $_POST['ip_address'] ?? null;
$api_username  = $_POST['api_username'] ?? null;
$api_password  = $_POST['api_password'] ?? null;

if (!$server_id || !$ip_address || !$api_username || !$api_password) {
    echo json_encode(['id' => $server_id, 'status' => 'offline', 'uptime' => 'N/A', 'cpu_load' => 'N/A']);
    exit;
}

$router = new RouterosAPI();
$router->debug = false;

$result = [
    'id' => $server_id,
    'status' => 'offline',
    'uptime' => 'N/A',
    'cpu_load' => 'N/A',
    'download' => 0,
    'upload' => 0
];

if ($router->connect($ip_address, $api_username, $api_password)) {
    $sys = $router->comm('/system/resource/print')[0] ?? [];
    $iface = $router->comm('/interface/print', ['?name' => 'ether1']); // Adjust interface name if needed
    $rx = $iface[0]['rx-byte'] ?? 0;
    $tx = $iface[0]['tx-byte'] ?? 0;

    $result['status'] = 'online';
    $result['uptime'] = $sys['uptime'] ?? 'N/A';
    $result['cpu_load'] = $sys['cpu-load'] ?? 'N/A';
    $result['download'] = round($rx / 1024 / 1024, 2); // Convert to MB
    $result['upload'] = round($tx / 1024 / 1024, 2);

    $router->disconnect();
}

echo json_encode($result);
exit;
