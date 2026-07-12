<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/db.php');

header('Content-Type: application/json');

try {
    // Read POST data properly
    $code = $_POST['code'] ?? null;
    $tenant_id = $_POST['tenant_id'] ?? null;
    $status = $_POST['status'] ?? 'inuse'; // default inuse

    if (!$code || !$tenant_id) {
        throw new Exception("Missing voucher code or tenant_id");
    }

    $stmt = $pdo->prepare("
        UPDATE vouchers 
        SET status = :status,
            start_time = IF(:status='inuse', NOW(), start_time)
        WHERE code = :code
        AND tenant_id = :tenant_id
        AND status != :status
    ");

    $stmt->execute([
        'status' => $status,
        'code' => $code,
        'tenant_id' => $tenant_id
    ]);

    echo json_encode([
        "success" => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}