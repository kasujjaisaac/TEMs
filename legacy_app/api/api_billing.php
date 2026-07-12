<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once($_SERVER['DOCUMENT_ROOT'].'/db.php');
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* =========================
   GET PENDING INVOICES
========================= */
if($action === 'get_pending'){
    try {
        $stmt = $pdo->query("
            SELECT id, username, amount 
            FROM billing_transactions
            WHERE status='pending'
            ORDER BY id DESC
        ");

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e){
        echo json_encode(["error"=>$e->getMessage()]);
    }
    exit;
}

/* =========================
   PAY INVOICE
========================= */
if($action === 'pay_invoice'){

    $data = json_decode(file_get_contents("php://input"), true);

    $invoice_id = $data['invoice_id'] ?? null;
    $method     = $data['method'] ?? null;

    if(!$invoice_id || !$method){
       http_response_code(500);
echo json_encode([
    "success"=>false,
    "error"=>$e->getMessage()
]);
    try {

        $check = $pdo->prepare("
            SELECT id FROM billing_transactions 
            WHERE id=? AND status='pending'
        ");
        $check->execute([$invoice_id]);

        if(!$check->fetch()){
            echo json_encode([
                "success"=>false,
                "message"=>"Invoice not found or already paid"
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE billing_transactions
            SET status='paid', method=?, paid_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$method, $invoice_id]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }

    exit;
}

/* =========================
   ADVANCE PAYMENT
========================= */
if($action === 'advance_payment'){

    $data = json_decode(file_get_contents("php://input"), true);

    $username = $data['username'] ?? '';
    $amount   = $data['amount'] ?? 0;
    $method   = $data['method'] ?? '';

    if(!$username || !$amount){
        echo json_encode(["success"=>false,"message"=>"Missing data"]);
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO billing_transactions
            (username, amount, method, status, created_at)
            VALUES (?, ?, ?, 'paid', NOW())
        ");

        $stmt->execute([$username, $amount, $method]);

        echo json_encode(["success"=>true]);

    } catch(Exception $e){
        echo json_encode(["success"=>false,"error"=>$e->getMessage()]);
    }

    exit;
}

/* =========================
   INVALID ACTION
========================= */
echo json_encode([
    "success"=>false,
    "message"=>"Invalid action"
]);