<?php
/**
 * Onyx Hub Support API - Production Grade
 * Integrated: create_ticket, reply_ticket, and automated notifications
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

function sendResponse($success, $message = "") {
    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

// 1. DATABASE LINK (Hostinger Optimized Path)
$dbFile = dirname(__DIR__) . '/db.php';
if (file_exists($dbFile)) {
    require_once($dbFile);
} else {
    sendResponse(false, "System Error: db.php not found at " . $dbFile);
}

if (!isset($pdo)) {
    sendResponse(false, "Database Variable Error: \$pdo not found.");
}

// Start session to capture usernames
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';

if ($action === 'create_ticket') {
    $subject  = trim($_POST['subject'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $category = $_POST['category'] ?? 'General';
    $priority = $_POST['priority'] ?? 'Normal';
    
    // Generate a Professional Ticket ID
    $ticket_id = "TCK" . strtoupper(substr(md5(time()), 0, 6));

    if (empty($subject) || empty($message)) {
        sendResponse(false, "Subject and Message are required.");
    }

    try {
        /**
         * DYNAMIC COLUMN CHECK
         * Ensures compatibility if your table uses 'username', 'user_id', or 'user'
         */
        $check = $pdo->query("DESCRIBE support_tickets");
$columns = $check->fetchAll(PDO::FETCH_COLUMN);

/*
|--------------------------------------------------------------------------
| DETECT USER COLUMN
|--------------------------------------------------------------------------
*/

if (in_array('client_name', $columns)) {

    $userColumn = 'client_name';

} elseif (in_array('username', $columns)) {

    $userColumn = 'username';

} elseif (in_array('user_id', $columns)) {

    $userColumn = 'user_id';

} elseif (in_array('user', $columns)) {

    $userColumn = 'user';

} else {

    sendResponse(false, "No valid user column found in support_tickets");

}

                /*
        |--------------------------------------------------------------------------
        | DEBUG ACTIVE SESSION
        |--------------------------------------------------------------------------
        */

        file_put_contents(
            'session_debug.txt',
            print_r($_SESSION, true)
        );

        /*
        |--------------------------------------------------------------------------
        | GET CURRENT LOGGED USER
        |--------------------------------------------------------------------------
        */

        $currentUser =
            $_SESSION['tenant_name']
            ?? $_SESSION['company_name']
            ?? $_SESSION['business_name']
            ?? $_SESSION['name']
            ?? $_SESSION['username']
            ?? $_SESSION['organization']
            ?? 'Guest';
$sql = "INSERT INTO support_tickets 
(ticket_id, $userColumn, subject, message, category, priority, status, created_at) 
VALUES (?, ?, ?, ?, ?, ?, 'Open', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
    $ticket_id,
    $currentUser,
    $subject,
    $message,
    $category,
    $priority
]);
        
        sendResponse(true, "Ticket #$ticket_id created successfully!");

    } catch (PDOException $e) {
        sendResponse(false, "Database Error: " . $e->getMessage());
    }

} elseif ($action === 'reply_ticket') {
    $ticket_id = $_POST['ticket_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    $username = $_SESSION['username'] ?? 'Support Staff';

    if (empty($ticket_id) || empty($message)) {
        sendResponse(false, "Ticket ID and message are required.");
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert ticket response
        $sql = "
INSERT INTO support_replies
(
    ticket_id,
    sender,
    message,
    message_type,
    created_at
)

VALUES
(
    ?,
    ?,
    ?,
    'system',
    NOW()
)
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $ticket_id,
    $username,
    $message
]);

        // 2. Update ticket status to "In Progress"
        $update_sql = "UPDATE support_tickets SET status = 'In Progress' WHERE ticket_id = ?";
        $pdo->prepare($update_sql)->execute([$ticket_id]);

        // 3. Trigger Notification for the User (Integrated Logic)
        // We find the owner of the ticket to send them a notification
        $check = $pdo->query("DESCRIBE support_tickets");
$columns = $check->fetchAll(PDO::FETCH_COLUMN);

$userColumn = 'username';

if (!in_array('username', $columns)) {

    if (in_array('user_id', $columns)) {
        $userColumn = 'user_id';
    }

    else if (in_array('user', $columns)) {
        $userColumn = 'user';
    }
}

$owner_sql = "SELECT $userColumn FROM support_tickets WHERE ticket_id = ? LIMIT 1";

$owner_stmt = $pdo->prepare($owner_sql);

$owner_stmt->execute([$ticket_id]);

$owner = trim($owner_stmt->fetchColumn());

        if ($owner) {

    $notif_sql = "
    INSERT INTO notifications 
    (
        user_id,
        type,
        title,
        message,
        is_read,
        created_at
    ) 
    VALUES 
    (
        ?,
        'support',
        'New Reply Received',
        ?,
        0,
        NOW()
    )
    ";

    $notif_msg = "Ticket #$ticket_id: " . $message;

    $pdo->prepare($notif_sql)->execute([
        trim($owner),
        $notif_msg
    ]);
}

        $pdo->commit();
        sendResponse(true, "Your reply has been submitted and notification sent.");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        sendResponse(false, "Error processing reply: " . $e->getMessage());
    }

} else {
    sendResponse(false, "Invalid action requested.");
}