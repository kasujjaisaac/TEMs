<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_name = trim($_POST['org_name']);
    $admin_name = trim($_POST['admin_name']);
    $email = filter_input(INPUT_POST, 'admin_email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $errors = [];

    if (!$org_name || !$admin_name || !$email || strlen($password) < 8) {
        $errors[] = "Please fill all fields correctly.";
    }

    if (empty($errors)) {
        $verification_token = bin2hex(random_bytes(32));
        $trial_end = date('Y-m-d', strtotime('+14 days'));

        // Create tenant
        $stmt = $pdo->prepare("INSERT INTO tenants (name, status, trial_end) VALUES (?, 'trial', ?)");
        $stmt->execute([$org_name, $trial_end]);
        $tenant_id = $pdo->lastInsertId();

        // Hash password
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

        // Create admin user
        $stmt = $pdo->prepare("INSERT INTO users (tenant_id, email, name, password, role, status, email_verified, verification_token) VALUES (?, ?, ?, ?, 'admin', 'pending', 0, ?)");
        $stmt->execute([$tenant_id, $email, $admin_name, $hashed_pass, $verification_token]);

        // Send verification email
        $verify_link = "https://yourdomain.com/verify.php?token=$verification_token";
        $subject = "Verify your organization account";
        $message = "Hello $admin_name,\n\nPlease verify your email by clicking this link:\n$verify_link\n\nThank you!";
        mail($email, $subject, $message, "From: noreply@yourdomain.com");

        $success = "Signup successful! Please check your email to verify your account.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Organization Signup</title>
</head>
<body>
    <h2>Register Your Organization</h2>
    <?php if (!empty($errors)) foreach($errors as $e) echo "<p style='color:red;'>$e</p>"; ?>
    <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <form method="POST">
        <input type="text" name="org_name" placeholder="Organization Name" required><br>
        <input type="text" name="admin_name" placeholder="Your Name" required><br>
        <input type="email" name="admin_email" placeholder="Admin Email" required><br>
        <input type="password" name="password" placeholder="Password (min 8 chars)" required><br>
        <button type="submit">Sign Up</button>
    </form>
</body>
</html>