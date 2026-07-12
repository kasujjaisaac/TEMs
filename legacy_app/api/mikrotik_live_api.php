<?php
header('Content-Type: application/json');
ini_set('display_errors', 1); error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']); exit;
}
require_once(__DIR__.'/config.php');
require_once(__DIR__.'/RouterosAPI.php');

$tenant_id = $_SESSION['tenant_id'];
$stmt = $pdo->prepare("SELECT id, name, ip_address, api_username, api_password FROM servers WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$liveData = [];
foreach ($servers as $s) {
    $password = $s['api_password']; // Must be plaintext or decrypted
    $API = new RouterosAPI();
    $isConnected = $API->connect($s['ip_address'], $s['api_username'], $password);

    $status = 'offline';
    $uptime = 'N/A';
    $cpu_load = null;
    $download_speed = null;
    $upload_speed = null;

    if ($isConnected) {
        $status = 'online';

        $API->write('/system/resource/print');
        $READ = $API->read(false);
        $res  = $API->parseResponse($READ);
        if (isset($res[0])) {
            $uptime = $res[0]['uptime'] ?? 'N/A';
            $cpu_load = $res[0]['cpu-load'] ?? null;
        }

        $API->write('/interface/print');
        $READ = $API->read(false);
        $interfaces = $API->parseResponse($READ);

        $download = 0; $upload = 0;
        if (is_array($interfaces)) {
            foreach ($interfaces as $iface) {
                $download += isset($iface['rx-byte']) ? $iface['rx-byte'] : 0;
                $upload   += isset($iface['tx-byte']) ? $iface['tx-byte'] : 0;
            }
        }
        $download_speed = round($download / 1024 / 1024, 2);
        $upload_speed   = round($upload   / 1024 / 1024, 2);

        $API->disconnect();
    }

    $liveData[] = [
        'id' => $s['id'],
        'name' => $s['name'],
        'ip_address' => $s['ip_address'],
        'status' => $status,
        'uptime' => $uptime,
        'cpu_load' => $cpu_load,
        'download_speed' => $download_speed,
        'upload_speed' => $upload_speed
    ];
}
echo json_encode(['success'=>true, 'data'=>$liveData]);