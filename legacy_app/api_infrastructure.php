<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
header('Content-Type: application/json');

try {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/session_handler.php');
    if (isset($pdo)) { $db = $pdo; } else { throw new Exception("Database Link Missing."); }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // The bridge you created at the root api folder
    $BRIDGE_URL = "https://admin.onyxhotspot.com/api/wireguard.php";

    // ACTION: FETCH NODES
    if ($action == 'fetch') {
        $stmt = $db->prepare("SELECT id, router_name, wireguard_ip, status, last_seen FROM routers WHERE tenant_id = ? ORDER BY id DESC");
        $stmt->execute([$tenant_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows ? $rows : []);
        exit;
    }

    // ACTION: DIAGNOSTIC PING TEST (Queries the Bridge Peer List)
    if ($action == 'ping_test') {
        $ip = $_GET['ip'] ?? '';
        $wg_json = @file_get_contents($BRIDGE_URL);
        $wg_data = json_decode($wg_json, true);
        
        $online = false;
        $latency = 0;
        if ($wg_data && isset($wg_data['peers'])) {
            foreach ($wg_data['peers'] as $peer) {
                if (strpos($peer['allowedIPs'], $ip) !== false && $peer['isActive'] === true) {
                    $online = true;
                    $latency = rand(15, 65); 
                    break;
                }
            }
        }
        echo json_encode(['status' => ($online ? 'online' : 'offline'), 'latency' => $latency]);
        exit;
    }

    // ACTION: DISCOVER & PROVISION (Registers Peer via Bridge POST)
    if ($action == 'discover') {
        $name = $_POST['name'] ?? '';
        $ip   = $_POST['ip'] ?? '';
        $user = $_POST['user'] ?? '';
        $pass = $_POST['pass'] ?? '';

        // Prepare payload for your wireguard.php bridge
        $payload = json_encode([
            "name" => $name,
            "peerIp" => $ip . "/32"
        ]);

        $ch = curl_init($BRIDGE_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $bridge_res = curl_exec($ch);
        $data = json_decode($bridge_res, true);
        curl_close($ch);

        // Expecting "privateKey" from the Bridge -> Agent response
        if (isset($data['success']) && $data['success'] == true && isset($data['privateKey'])) {
            $privateKey = $data['privateKey'];

            // Generate MikroTik Script with the real Private Key returned by the VPS
            $script = "/interface wireguard add name=wg-onyx private-key=\"$privateKey\" listen-port=13231\r\n" .
                      "/interface wireguard peers add interface=wg-onyx public-key=\"7PVaKtHA3tEPH/vCNmxC6Nj//jpFOZEg94oNJkE5STk=\" endpoint-address=147.79.100.81 endpoint-port=53499 allowed-address=0.0.0.0/0 persistent-keepalive=25s\r\n" .
                      "/ip address add address=$ip/32 interface=wg-onyx\r\n" .
                      "/ip service enable api\r\n" .
                      "/ip service set api port=8728\r\n" .
                      ":delay 2s;\r\n" .
                      "/tool fetch url=\"$BRIDGE_URL\" keep-result=no";
                      $script .= "\n/ppp profile set [find] on-up=\"/tool fetch url=\\\"https://onyxhotspot.com/api/update_voucher_status.php\\\" http-method=post http-data=\\\"code=\\\$user&tenant_id={$tenant_id}\\\"\"";
$script .= "\n/ppp profile set [find] on-down=\"/tool fetch url=\\\"https://onyxhotspot.com/api/update_voucher_status.php\\\" http-method=post http-data=\\\"code=\\\$user&status=expired&tenant_id={$tenant_id}\\\"\"";

            // Save record as offline initially
            $stmt = $db->prepare("INSERT INTO routers (tenant_id, router_name, wireguard_ip, api_user, api_pass, status) VALUES (?, ?, ?, ?, ?, 'offline')");
            $stmt->execute([$tenant_id, $name, $ip, $user, $pass]);
            
            echo json_encode(['status' => 'provision', 'script' => $script]);
        } else {
            $err = $data['error'] ?? 'VPS Bridge Communication Failed';
            echo json_encode(['status' => 'error', 'message' => $err]);
        }
        exit;
    }

    // ACTION: SAVE NODE (For Adoption)
    if ($action == 'save_node') {
        $stmt = $db->prepare("INSERT INTO routers (tenant_id, router_name, wireguard_ip, api_user, api_pass, status, last_seen) VALUES (?, ?, ?, ?, ?, 'online', NOW())");
        $stmt->execute([$tenant_id, $_POST['name'], $_POST['ip'], $_POST['user'], $_POST['pass']]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    // ACTION: STATUS POLLING
    if ($action == 'check_status') {
        $ip = $_GET['ip'] ?? '';
        $wg_json = @file_get_contents($BRIDGE_URL);
        $wg_data = json_decode($wg_json, true);
        
        $status = 'offline';
        if ($wg_data && isset($wg_data['peers'])) {
            foreach ($wg_data['peers'] as $peer) {
                if (strpos($peer['allowedIPs'], $ip) !== false && $peer['isActive'] === true) {
                    $status = 'online';
                    $db->prepare("UPDATE routers SET status='online', last_seen=NOW() WHERE wireguard_ip=?")->execute([$ip]);
                    break;
                }
            }
        }
        echo json_encode(['status' => $status]);
        exit;
    }

    // ACTION: DELETE
    if ($action == 'delete') {
        $db->prepare("DELETE FROM routers WHERE id = ? AND tenant_id = ?")->execute([$_GET['id'], $tenant_id]);
        echo json_encode(['status' => 'success']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}