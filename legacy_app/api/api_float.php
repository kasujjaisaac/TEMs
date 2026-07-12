<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/session_handler.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

$file = $_SERVER['DOCUMENT_ROOT'].'/float_config.json';

/* 🔐 BASIC AUTH CHECK */
if(empty($_SESSION['username'])){
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]);
    exit;
}

/* DEFAULT CONFIG */
$default = [
    "min_balance" => 50000,
    "max_balance" => 5000000,
    "auto_topup" => false,
    "topup_amount" => 100000,
    "low_alert" => true,
    "alert_threshold" => 80000,
    "allow_negative" => false,
    "currency" => "UGX",
    "last_updated" => date("Y-m-d H:i:s"),
    "last_topup" => null
];

/* ENSURE FILE EXISTS */
if(!file_exists($file)){
    file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
}

/* LOAD CONFIG */
function loadConfig($file, $default){
    $data = json_decode(file_get_contents($file), true);
    return (is_array($data)) ? $data : $default;
}

/* SAVE CONFIG */
function saveConfig($file, $data){
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

/* SAFE BALANCE */
function getBalance($pdo){
    $bal = $pdo->query("
        SELECT SUM(amount) 
        FROM billing_transactions 
        WHERE status='paid'
    ")->fetchColumn();

    return $bal ? (int)$bal : 0;
}

/* =========================
   🔹 GET CONFIG
========================= */
if($action === 'get'){

    echo json_encode([
        "success" => true,
        "data" => loadConfig($file, $default)
    ]);
    exit;
}


/* =========================
   🔹 SAVE CONFIG
========================= */
if($action === 'save'){

    $data = json_decode(file_get_contents("php://input"), true);

    if(!$data){
        echo json_encode(["success"=>false,"message"=>"Invalid input"]);
        exit;
    }

    $min   = intval($data['min_balance'] ?? 0);
    $max   = intval($data['max_balance'] ?? 0);
    $topup = intval($data['topup_amount'] ?? 0);
    $alert = intval($data['alert_threshold'] ?? 0);

    if($min < 0 || $max <= $min){
        echo json_encode(["success"=>false,"message"=>"Invalid balance range"]);
        exit;
    }

    $config = [
        "min_balance"   => $min,
        "max_balance"   => $max,
        "auto_topup"    => !empty($data['auto_topup']),
        "topup_amount"  => $topup,
        "low_alert"     => !empty($data['low_alert']),
        "alert_threshold"=> $alert,
        "allow_negative"=> !empty($data['allow_negative']),
        "currency"      => ($data['currency'] === 'USD') ? 'USD' : 'UGX',
        "last_updated"  => date("Y-m-d H:i:s"),
        "last_topup"    => null
    ];

    saveConfig($file, $config);

    echo json_encode(["success"=>true]);
    exit;
}


/* =========================
   🔹 STATUS
========================= */
if($action === 'status'){

    try {

        $balance = getBalance($pdo);
        $config  = loadConfig($file, $default);

        $status = "normal";

        if($balance <= $config['alert_threshold']){
            $status = "critical";
        } elseif($balance < $config['min_balance']){
            $status = "low";
        }

        echo json_encode([
            "success" => true,
            "balance" => $balance,
            "status"  => $status,
            "currency"=> $config['currency']
        ]);

    } catch(Exception $e){
        echo json_encode(["success"=>false]);
    }

    exit;
}


/* =========================
   🔹 AUTO TOP-UP (SAFE)
========================= */
if($action === 'auto_topup'){

    $config = loadConfig($file, $default);

    if(!$config['auto_topup']){
        echo json_encode(["success"=>false,"message"=>"Disabled"]);
        exit;
    }

    try {

        $balance = getBalance($pdo);

        /* 🔥 prevent repeated topups within 60 seconds */
        if($config['last_topup'] && strtotime($config['last_topup']) > time() - 60){
            echo json_encode(["success"=>false,"message"=>"Cooldown active"]);
            exit;
        }

        if($balance < $config['min_balance']){

            $stmt = $pdo->prepare("
                INSERT INTO billing_transactions
                (username, amount, method, status, created_at)
                VALUES ('SYSTEM', ?, 'auto-topup', 'paid', NOW())
            ");

            $stmt->execute([$config['topup_amount']]);

            /* update config */
            $config['last_topup'] = date("Y-m-d H:i:s");
            saveConfig($file, $config);

            echo json_encode([
                "success"=>true,
                "message"=>"Top-up executed"
            ]);

        } else {
            echo json_encode([
                "success"=>true,
                "message"=>"Balance OK"
            ]);
        }

    } catch(Exception $e){
        echo json_encode(["success"=>false]);
    }

    exit;
}


/* =========================
   🔹 RESET
========================= */
if($action === 'reset'){
    saveConfig($file, $default);
    echo json_encode(["success"=>true]);
    exit;
}


/* =========================
   🔹 INVALID
========================= */
echo json_encode([
    "success"=>false,
    "message"=>"Invalid action"
]);