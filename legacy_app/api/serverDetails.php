<?php
session_start();
require_once('../config.php');
require_once('RouterosAPI.php');

if (!isset($_GET['server_id'])) {
    echo json_encode(['error' => 'Server ID missing']);
    exit;
}

$server_id = intval($_GET['server_id']);
$stmt = $pdo->prepare("SELECT * FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    echo json_encode(['error' => 'Server not found']);
    exit;
}

$liveData = fetchServerData($server['ip_address'], $server['api_username'], $server['api_password']);

$response = array_merge($server, $liveData);
echo json_encode($response);
