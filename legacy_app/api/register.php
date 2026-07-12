<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Collect POST data
$organization = trim($_POST['organization'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$package = $_POST['package'] ?? '';

// Minimal validation
if (!$organization || !$name || !$email || !$password || !$confirm_password || !$package) {
    returnResponse("Please fill in all required fields.", false, $isAjax);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    returnResponse("Invalid email address.", false, $isAjax);
}
if ($password !== $confirm_password) {
    returnResponse("Passwords do not match.", false, $isAjax);
}

try {
    // Connect to DB
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    returnResponse("Database connection failed: " . $e->getMessage(), false, $isAjax);
}

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    returnResponse("Email already in use.", false, $isAjax);
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
// Generate token for email verification
$token = bin2hex(random_bytes(32));

try {
    // Insert tenant using the correct column name 'name'
    $stmt = $pdo->prepare("INSERT INTO tenants (name) VALUES (?)");
    $stmt->execute([$organization]);
    $tenant_id = $pdo->lastInsertId();

    // Insert user with tenant_id
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, organization, name, email, password, package, email_verified, profile_complete, status, token) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 'inactive', ?)");
    $stmt->execute([$tenant_id, $organization, $name, $email, $hashed_password, $package, $token]);
    $user_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    returnResponse("Registration failed: " . $e->getMessage(), false, $isAjax);
}

// PHPMailer for verification email
require_once(__DIR__ . '/../PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verification link now points to verify.php
$verification_link = "https://onyxhotspot.com/verify.php?token=" . urlencode($token);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'onyxmikrotiks@gmail.com';
    $mail->Password   = 'vnas igwq myaj fofq'; // Use your real Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('onyxmikrotiks@gmail.com', 'Onyx Hotspot');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Please verify your email address';
    $mail->Body    = "Thank you for registering!<br>Click the link below to verify your email and complete your profile:<br><a href='$verification_link'>$verification_link</a>";

    $mail->send();
    returnResponse("Registration successful! Please check your email for verification instructions.", true, $isAjax);
} catch (Exception $e) {
    // Optionally: rollback/delete inserted user and tenant if email fails
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM tenants WHERE id=?")->execute([$tenant_id]);
    returnResponse("Error sending verification email: " . $mail->ErrorInfo, false, $isAjax);
}

// Helper function: respond as JSON for AJAX or plain for normal POST
function returnResponse($msg, $success, $isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => $success ? "success" : "error",
            "message" => $msg
        ]);
        exit;
    } else {
        // Render minimal HTML (or redirect)
        $class = $success ? "success" : "error";
        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Register</title>";
        echo "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap' rel='stylesheet'>";
        echo "<style>.success{color:#38a169;text-align:center;}.error{color:#e53e3e;text-align:center;}</style></head><body>";
        echo "<div class='$class'>$msg</div>";
        echo "<div style='text-align:center; margin-top:2em;'><a href='../register.html'>Back to Register</a></div>";
        echo "</body></html>";
        exit;
    }
}
?>