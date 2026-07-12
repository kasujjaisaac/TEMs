<?php
require_once('config.php');
session_start();

if (empty($_GET['token']) || strlen($_GET['token']) < 32) {
    die("Invalid verification link.");
}
$token = $_GET['token'];

$stmt = $pdo->prepare("SELECT id, tenant_id, name FROM users WHERE verification_token = ? AND email_verified = 0 AND status = 'pending'");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Mark user as verified and activate
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active', verification_token = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['tenant_id'] = $user['tenant_id'];
    header("Location: complete-profile.php");
    exit;
} else {
    $msg = "Invalid or expired verification link.";
}
?>
<!DOCTYPE html>
<html>
<head><title>Email Verification</title></head>
<body>
    <h2>Email Verification</h2>
    <p><?php echo isset($msg) ? $msg : "Redirecting..."; ?></p>
    <a href="login.html">Go to Login</a>
</body>
</html>