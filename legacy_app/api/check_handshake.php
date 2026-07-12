<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/session_handler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

header('Content-Type: application/json');

$vpn_ip = $_GET['ip'] ?? null;

if (!$vpn_ip) {
    echo json_encode(['connected' => false, 'error' => 'No IP provided']);
    exit;
}

// Perform a quick ICMP ping from the VPS to the VPN IP
$host = escapeshellcmd($vpn_ip);
// -c 1 (1 packet), -W 1 (1 second timeout)
exec("ping -c 1 -W 1 $host", $output, $result);

$is_live = ($result === 0);

if ($is_live) {
    // Update the tunnel status in DB
    $stmt = $pdo->prepare("UPDATE device_tunnels SET status = 'connected', last_handshake = NOW() WHERE vpn_ip = ?");
    $stmt->execute([$vpn_ip]);
    
    // Also mark the device as online in the main table
    $pdo->prepare("UPDATE devices SET status = 'online' WHERE id = (SELECT device_id FROM device_tunnels WHERE vpn_ip = ? LIMIT 1)")
        ->execute([$vpn_ip]);
}

echo json_encode(['connected' => $is_live]);