<?php
// bridge_telemetry.php
// Purpose: CLI-only bridge to fetch MikroTik telemetry data via RouterOS API
// Executes from api_dashboard.php to bypass web-server network restrictions

if (php_sapi_name() !== 'cli') die("CLI Only");

// Load the RouterOS API class
require_once(dirname(__DIR__) . '/routeros_api.class.php');

$data = json_decode($argv[1] ?? '{}', true);

if (!$data || !isset($data['ip'], $data['user'], $data['pass'])) {
    die(json_encode(['error' => 'Invalid parameters']));
}

$API = new RouterosAPI();
$API->timeout = 5; // Increase timeout for high-latency WireGuard links

try {
    if ($API->connect($data['ip'], $data['user'], $data['pass'])) {
        
        // Fetch System Resources (CPU, Memory & Uptime)
        $resources = $API->comm("/system/resource/print");
        $resource = $resources[0] ?? [];
        
        // Fetch Active Hotspot Users
        $activeCount = $API->comm("/ip/hotspot/active/print", ["count-only" => ""]);
        $active_users = isset($activeCount[0]) ? (int)$activeCount[0] : 0;
        
        // Fetch Interface statistics for data usage
        $interfaces = $API->comm("/interface/print");
        $total_rx = 0;
        $total_tx = 0;
        
        foreach ($interfaces as $interface) {
            if (isset($interface['rx-byte'])) {
                $total_rx += (int)$interface['rx-byte'];
            }
            if (isset($interface['tx-byte'])) {
                $total_tx += (int)$interface['tx-byte'];
            }
        }
        
        // Convert bytes to GB
        $data_usage_gb = round(($total_rx + $total_tx) / (1024 ** 3), 2);
        
        // Parse uptime (format: 00:00:00 or similar)
        $uptime = $resource['uptime'] ?? '00:00:00';
        
        // Parse CPU load
        $cpu_load = isset($resource['cpu-load']) ? (int)$resource['cpu-load'] : 0;
        
        // Parse memory usage
        $total_memory = isset($resource['total-memory']) ? (int)$resource['total-memory'] : 0;
        $free_memory = isset($resource['free-memory']) ? (int)$resource['free-memory'] : 0;
        $used_memory = $total_memory - $free_memory;
        $memory_percent = $total_memory > 0 ? round(($used_memory / $total_memory) * 100, 2) : 0;
        
        echo json_encode([
            'success'      => true,
            'cpu'          => $cpu_load,
            'uptime'       => $uptime,
            'memory'       => $memory_percent,
            'memory_used'  => round($used_memory / (1024 ** 2), 2), // MB
            'memory_total' => round($total_memory / (1024 ** 2), 2), // MB
            'active_users' => $active_users,
            'data_usage'   => $data_usage_gb,
            'rx_bytes'     => $total_rx,
            'tx_bytes'     => $total_tx
        ]);
        
        $API->disconnect();
    } else {
        echo json_encode(['success' => false, 'error' => 'Connection failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>