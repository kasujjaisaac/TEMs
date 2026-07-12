<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Onyx-Key");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$VPS_URL = "https://api.onyxhotspot.com/api/wg_agent.php";
$SECRET_KEY = "ONYX_SECURE_TOKEN_2024";

$method = $_SERVER['REQUEST_METHOD'];
$request_body = file_get_contents('php://input');

$ch = curl_init($VPS_URL);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Onyx-Key: ' . $SECRET_KEY
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// --- CRITICAL FIX FOR JSON ERROR ---
if (empty($response) || $http_code >= 400) {
    echo json_encode([
        "success" => false, 
        "error" => "VPS Response Error (Code: $http_code)",
        "raw_response" => substr($response, 0, 100)
    ]);
    exit;
}

// If it's a GET request, we process the handshake data here
if ($method === 'GET') {
    $data = json_decode($response, true);
    $lines = explode("\n", trim($data['raw'] ?? ''));
    array_shift($lines); // Remove header
    
    $peers = [];
    foreach ($lines as $line) {
        $cols = explode("\t", $line);
        if (count($cols) < 5) continue;
        
        // Calculate Handshake human-readable time
        $timestamp = (int)$cols[4];
        $handshake = ($timestamp === 0) ? "Never" : date('d M, H:i', $timestamp);
        
        // Color coding logic: active if within last 3 minutes
        $isActive = ($timestamp > (time() - 180));

        $peers[] = [
            'publicKey' => $cols[0],
            'allowedIPs' => $cols[3],
            'handshake' => $handshake,
            'isActive' => $isActive,
            'name' => "Peer-" . substr($cols[0], 0, 6)
        ];
    }
    echo json_encode(["success" => true, "peers" => $peers]);
} else {
    // For POST/DELETE, just pass the VPS response through
    echo $response;
}