<?php
// Example backend for AJAX and traditional submits

// Detect AJAX request
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
    $msg = "Please fill in all required fields.";
    returnResponse($msg, false, $isAjax);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = "Invalid email address.";
    returnResponse($msg, false, $isAjax);
}
if ($password !== $confirm_password) {
    $msg = "Passwords do not match.";
    returnResponse($msg, false, $isAjax);
}

// TODO: Implement database check for existing user
// Simulate by checking a static email for demo
if (strtolower($email) === 'already@used.com') {
    $msg = "Email already in use.";
    returnResponse($msg, false, $isAjax);
}

// TODO: Save user data to DB here (hash password, store verified=0, generate token, etc.)

// Send verification email (adapt the code below as needed)
require_once(__DIR__ . '/../PHPMailer/src/Exception.php');
require_once(__DIR__ . '/../PHPMailer/src/PHPMailer.php');
require_once(__DIR__ . '/../PHPMailer/src/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$token = bin2hex(random_bytes(32));
$verification_link = "https://onyxhotspot.com/verify.php?email=" . urlencode($email) . "&token=" . $token;

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
    $mail->Body    = "Click the link below to verify your email:<br><a href='$verification_link'>$verification_link</a>";

    $mail->send();
    $msg = "Registration successful! Please check your email for verification instructions.";
    returnResponse($msg, true, $isAjax);
} catch (Exception $e) {
    $msg = "Error sending verification email: " . $mail->ErrorInfo;
    returnResponse($msg, false, $isAjax);
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
        echo "<div style='text-align:center; margin-top:2em;'><a href='register.html'>Back to Register</a></div>";
        echo "</body></html>";
        exit;
    }
}
?>