<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once('RouterosAPI.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$tenant_id = $_SESSION['tenant_id'];

// Fetch organization (tenant) name
$stmt = $pdo->prepare("SELECT name FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);
$organization_name = $tenant ? $tenant['name'] : "My Organization";

// Handle form submission for adding a new server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_server'])) {
    $server_name = $_POST['server_name'];
    $ip_address = $_POST['ip_address'];
    $api_user = $_POST['api_user'];
    $api_password = $_POST['api_password']; // Consider encrypting this in real apps

    if (!empty($server_name) && !empty($ip_address) && !empty($api_user) && !empty($api_password)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO servers (tenant_id, name, ip_address, api_username, api_password) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$tenant_id, $server_name, $ip_address, $api_user, $api_password]);
            header("Location: servers.php?success=1");
            exit;
        } catch (PDOException $e) {
            header("Location: servers.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Fetch registered servers for the current tenant
$stmt = $pdo->prepare("SELECT id, name, ip_address, api_username, api_password FROM servers WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If this is an AJAX request for live status
if (isset($_GET['action']) && $_GET['action'] === 'fetch_status') {
    $response = [];
    foreach ($servers as $server) {
        $router = new RouterosAPI();
        $router->debug = false;
        $status = [
            'id' => $server['id'],
            'status' => 'offline',
            'uptime' => 'N/A',
            'cpu_load' => 'N/A'
        ];
        if ($router->connect($server['ip_address'], $server['api_username'], $server['api_password'])) {
            $sys = $router->comm('/system/resource/print')[0] ?? [];
            $status['status'] = 'online';
            $status['uptime'] = $sys['uptime'] ?? 'N/A';
            $status['cpu_load'] = $sys['cpu-load'] ?? 'N/A';
            $router->disconnect();
        }
        $response[] = $status;
    }
    echo json_encode($response);
    exit;
}
?>
