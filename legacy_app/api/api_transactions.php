<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once($_SERVER['DOCUMENT_ROOT'].'/db.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* =========================
   🔹 GET ALL TRANSACTIONS
========================= */
if($action === 'list'){
    try {
        $stmt = $pdo->query("
            SELECT * FROM billing_transactions
            ORDER BY id DESC
            LIMIT 50
        ");

        echo json_encode([
            "success"=>true,
            "data"=>$stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }
    exit;
}


/* =========================
   🔹 FILTER TRANSACTIONS
========================= */
if($action === 'filter'){

    $status = $_GET['status'] ?? '';
    $username = $_GET['username'] ?? '';

    try {

        $sql = "SELECT * FROM billing_transactions WHERE 1=1";
        $params = [];

        if($status){
            $sql .= " AND status=?";
            $params[] = $status;
        }

        if($username){
            $sql .= " AND username LIKE ?";
            $params[] = "%$username%";
        }

        $sql .= " ORDER BY id DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            "success"=>true,
            "data"=>$stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }

    exit;
}


/* =========================
   🔹 CREATE TRANSACTION
========================= */
if($action === 'create'){

    $data = json_decode(file_get_contents("php://input"), true);

    $username = $data['username'] ?? '';
    $amount   = $data['amount'] ?? 0;
    $method   = $data['method'] ?? '';
    $status   = $data['status'] ?? 'pending';

    if(!$username || !$amount){
        echo json_encode(["success"=>false,"message"=>"Missing fields"]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO billing_transactions
            (username, amount, method, status, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$username, $amount, $method, $status]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }

    exit;
}


/* =========================
   🔹 UPDATE TRANSACTION
========================= */
if($action === 'update'){

    $data = json_decode(file_get_contents("php://input"), true);

    $id     = $data['id'] ?? null;
    $status = $data['status'] ?? null;

    if(!$id || !$status){
        echo json_encode(["success"=>false]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            UPDATE billing_transactions
            SET status=?, updated_at=NOW()
            WHERE id=?
        ");

        $stmt->execute([$status, $id]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }

    exit;
}


/* =========================
   🔹 DELETE TRANSACTION
========================= */
if($action === 'delete'){

    $id = $_GET['id'] ?? null;

    if(!$id){
        echo json_encode(["success"=>false]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("DELETE FROM billing_transactions WHERE id=?");
        $stmt->execute([$id]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }

    exit;
}


/* =========================
   🔹 STATS (ADVANCED)
========================= */
if($action === 'stats'){

    try {

        $total = $pdo->query("
            SELECT SUM(amount) FROM billing_transactions WHERE status='paid'
        ")->fetchColumn();

        $pending = $pdo->query("
            SELECT COUNT(*) FROM billing_transactions WHERE status='pending'
        ")->fetchColumn();

        $paid = $pdo->query("
            SELECT COUNT(*) FROM billing_transactions WHERE status='paid'
        ")->fetchColumn();

        echo json_encode([
            "success"=>true,
            "total"=>$total ?: 0,
            "pending"=>$pending,
            "paid"=>$paid
        ]);

    } catch(Exception $e){
        echo json_encode(["success"=>false]);
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