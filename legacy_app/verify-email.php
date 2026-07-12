<?php
require 'debug.php';
require 'database.php';

try {
    // Get the token from the URL
    $token = isset($_GET['token']) ? $_GET['token'] : '';

    // Check if the token is valid
    $stmt = $conn->prepare("SELECT * FROM users WHERE verificationToken = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Update the verified status to 1 and clear the token
        $updateStmt = $conn->prepare("UPDATE users SET verified = 1, verificationToken = NULL WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        if ($updateStmt->execute()) {
            // Redirect to login with a success message
            header("Location: index.html?verified=success");
            exit();
        } else {
            echo "<h2>Verification Failed</h2>";
            echo "<p>There was an issue verifying your email. Please try again later.</p>";
        }
    } else {
        echo "<h2>Invalid or Expired Token</h2>";
        echo "<p>This verification link is invalid or has already been used.</p>";
    }
} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    echo "<h2>Server Error</h2>";
    echo "<p>There was a server error. Please try again later.</p>";
}
?>
