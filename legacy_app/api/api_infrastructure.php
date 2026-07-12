<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/session_handler.php');
require_once('routeros_api.class.php'); 

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. FETCH ROUTERS
if ($action == 'fetch_routers') {
    $stmt = $db->prepare("SELECT id, router_name, wireguard_ip, status, last_seen FROM routers WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 2. ADD / ONBOARD ROUTER
if ($action == 'add_router') {
    $name = $_POST['router_name'];
    $ip   = $_POST['wireguard_ip'];
    $user = $_POST['api_user'];
    $pass = $_POST['api_pass'];

    // Professional Bootstrap Script for MikroTik
    // This configures the WireGuard interface and API automatically
    $bootstrap = "/interface wireguard add name=Onyx-VPN listen-port=13231\n" .
                 "/interface wireguard peers add allowed-address=0.0.0.0/0 endpoint-address=YOUR_VPS_IP endpoint-port=51820 interface=Onyx-VPN public-key=\"YOUR_VPS_PUBLIC_KEY\"\n" .
                 "/ip address add address=$ip/24 interface=Onyx-VPN\n" .
                 "/ip service enable api";

    $stmt = $db->prepare("INSERT INTO routers (tenant_id, router_name, wireguard_ip, api_user, api_pass, status) VALUES (?, ?, ?, ?, ?, 'offline')");
    if($stmt->execute([$tenant_id, $name, $ip, $user, $pass])) {
        echo json_encode(['status' => 'success', 'bootstrap_script' => $bootstrap]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database save failed']);
    }
    exit;
}

// 3. CHECK STATUS (POLLING)
if ($action == 'check_status') {
    $ip = $_GET['ip'];
    $api = new RouterosAPI();
    // Try to connect to the router via the WireGuard Tunnel
    if ($api->connect($ip, 'admin', 'password')) { // Use actual credentials if stored
        $db->prepare("UPDATE routers SET status='online', last_seen=NOW() WHERE wireguard_ip=?")->execute([$ip]);
        echo json_encode(['status' => 'online']);
        $api->disconnect();
    } else {
        echo json_encode(['status' => 'offline']);
    }
    exit;
}