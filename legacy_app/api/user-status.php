<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

if (!isset($_SESSION['user_id'], $_SESSION['tenant_id']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403); die("Access denied");
}
$user_id   = $_SESSION['user_id'];
$tenant_id = $_SESSION['tenant_id'];

$target_id = intval($_POST['user_id'] ?? 0);
$status = ($_POST['status'] === 'active') ? 'active' : 'inactive';

// Prevent deactivating self
if ($target_id == $user_id) {
    http_response_code(400); die("You can't deactivate yourself.");
}

$stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND tenant_id = ?");
$stmt->execute([$status, $target_id, $tenant_id]);

// Audit log
$stmt = $pdo->prepare("INSERT INTO audit_logs (tenant_id, user_id, action, details) VALUES (?, ?, 'change_status', ?)");
$stmt->execute([$tenant_id, $user_id, json_encode(['target_id'=>$target_id,'new_status'=>$status])]);

echo "ok";
?>