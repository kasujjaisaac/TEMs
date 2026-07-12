<?php
session_start();
header('Content-Type: application/json');

// ===== DATABASE CONNECTION =====
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Helper for responses within this script
function sendResponse($success, $message = "") {
    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

// ===== GET ACTION =====
$action = $_GET['action'] ?? '';

// 🔴 TEMP: FORCE USER (to avoid session issues)
$user = $_SESSION['username'] ?? 'ONYX TESTS';

// ===== NEW: TICKET NOTIFICATION LOGIC =====
if ($action === 'create_ticket' || $action === 'reply_ticket') {
    // Prepare notification data
    $ticket_id = $_POST['ticket_id'] ?? null;
    $message = $_POST['message'] ?? '';
    $user_id = $_SESSION['username'] ?? 'guest';

    try {
        // 1. Get the ticket owner based on ticket_id using MySQLi
        $stmt = $conn->prepare("SELECT username FROM support_tickets WHERE ticket_id = ?");
        $stmt->bind_param("s", $ticket_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ticket_owner = $res->fetch_column();

        if ($ticket_owner) {
            // 2. Insert notification for the ticket owner
            $notif_msg = "You have a new reply on ticket #$ticket_id: $message";
            $sql = "INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read, created_at) VALUES (?, 'support', 'New reply on your ticket', ?, ?, 0, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $ticket_owner, $notif_msg, $ticket_id);
            $stmt->execute();

            // 3. Insert notification for the user who replied
            $receipt_msg = "Your reply to ticket #$ticket_id has been received.";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $user_id, $receipt_msg, $ticket_id);
            $stmt->execute();

            sendResponse(true, "Notification sent to ticket owner and replier.");
        } else {
            sendResponse(false, "Ticket owner not found.");
        }
    } catch (Exception $e) {
        sendResponse(false, "Error sending notification: " . $e->getMessage());
    }
}

// ===== LOAD MORE =====
if ($action === 'load_more') {
    $offset = intval($_GET['offset'] ?? 0);

    $stmt = $conn->prepare("
        SELECT * FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20 OFFSET ?
    ");

    if (!$stmt) {
        echo json_encode(['error' => 'Prepare failed']);
        exit;
    }

    $stmt->bind_param("si", $user, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['data' => $data]);
    exit;
}

// ===== MARK READ =====
if ($action === 'mark_read') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("is", $id, $user);
    $stmt->execute();

    echo json_encode(['status' => 'ok']);
    exit;
}

// ===== DELETE =====
if ($action === 'delete') {

    $id = intval($_GET['id']);

    $stmt = $conn->prepare("
        DELETE FROM notifications
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    echo json_encode([
        'success' => true
    ]);

    exit;
}
// ===== CHECK NEW =====
if ($action === 'check_new') {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode(['count' => (int)$result['total']]);
    exit;
}

// ===== DEFAULT =====
echo json_encode(['error' => 'Invalid action']);
