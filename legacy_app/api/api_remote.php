<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/db.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* =========================
   🔹 LIST REQUESTS
========================= */
if($action === 'list'){

    try {
        $stmt = $pdo->query("
            SELECT id, ticket_id, username AS client_user, status, created_at
            FROM remote_access_requests
            WHERE status='pending'
            ORDER BY created_at DESC
        ");

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch(Exception $e){
        echo json_encode([]);
    }

    exit;
}


/* =========================
   🔹 APPROVE REQUEST
========================= */
if($action === 'approve'){

    $data = json_decode(file_get_contents("php://input"), true);

    $id       = $data['id'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if(!$id || !$username || !$password){
        echo json_encode([
            "success"=>false,
            "message"=>"Missing credentials"
        ]);
        exit;
    }

    try {

        /* 🔐 VERIFY ADMIN */
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$admin || !password_verify($password, $admin['password'])){
            echo json_encode([
                "success"=>false,
                "message"=>"Invalid admin login"
            ]);
            exit;
        }

        /* 🔥 APPROVE REQUEST */
        $update = $pdo->prepare("
            UPDATE remote_access_requests
            SET status='approved', approved_at=NOW()
            WHERE id=? AND status='pending'
        ");
        $update->execute([$id]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode([
            "success"=>false,
            "message"=>"Server error"
        ]);
    }

    exit;
}


/* =========================
   🔹 REJECT REQUEST
========================= */
if($action === 'reject'){

    $id = $_GET['id'] ?? '';

    if(!$id){
        echo json_encode(["success"=>false]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            UPDATE remote_access_requests
            SET status='rejected'
            WHERE id=?
        ");
        $stmt->execute([$id]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode(["success"=>false]);
    }

    exit;
}


/* =========================
   🔹 CREATE REQUEST (FROM SUPPORT)
========================= */
if($action === 'request'){

    $data = json_decode(file_get_contents("php://input"), true);

    $ticket_id = $data['ticket_id'] ?? '';
    $username  = $data['username'] ?? '';
    $password  = $data['password'] ?? '';

    if(!$ticket_id || !$username || !$password){
        echo json_encode([
            "success"=>false,
            "message"=>"Missing fields"
        ]);
        exit;
    }

    try {

        /* 🔐 HASH PASSWORD */
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO remote_access_requests
            (ticket_id, username, password, status, created_at)
            VALUES (?,?,?,?,NOW())
        ");

        $stmt->execute([
            $ticket_id,
            $username,
            $hashed,
            'pending'
        ]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode([
            "success"=>false,
            "message"=>"DB error"
        ]);
    }

    exit;
}


/* =========================
   🔹 INVALID
========================= */
echo json_encode([
    "success"=>false,
    "message"=>"Invalid action"
]);