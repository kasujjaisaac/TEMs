<?php

header('Content-Type: application/json');

ini_set('display_errors', 1);

error_reporting(E_ALL);

require_once($_SERVER['DOCUMENT_ROOT'].'/db.php');

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

$response = [
    'success' => false,
    'users' => []
];

/*
|--------------------------------------------------------------------------
| INPUTS
|--------------------------------------------------------------------------
*/

$tenant_id = isset($_GET['tenant_id'])
? (int)$_GET['tenant_id']
: 0;

$router_id = isset($_GET['router_id'])
? (int)$_GET['router_id']
: 0;

if(!$tenant_id){

    echo json_encode([
        'success' => false,
        'message' => 'Missing tenant_id'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET ROUTERS
|--------------------------------------------------------------------------
*/

try{

    $sql = "
    SELECT
    id,
    router_name,
    wireguard_ip
    FROM routers
    WHERE tenant_id = :tenant_id
    AND status='online'
    ";

    if($router_id > 0){

        $sql .= " AND id = :router_id";

    }

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(
        ':tenant_id',
        $tenant_id,
        PDO::PARAM_INT
    );

    if($router_id > 0){

        $stmt->bindValue(
            ':router_id',
            $router_id,
            PDO::PARAM_INT
        );

    }

    $stmt->execute();

    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | LOOP ROUTERS
    |--------------------------------------------------------------------------
    */

    foreach($routers as $router){

        /*
        |--------------------------------------------------------------------------
        | AUTOMATION URL
        |--------------------------------------------------------------------------
        */

        $url =
        "https://onyxhotspot.com/automation/listen.php?action=get_hotspot_active"
        .
        "&wireguard_ip="
        .
        urlencode($router['wireguard_ip'])
        .
        "&key=Onyx_Automate_2026";

        /*
        |--------------------------------------------------------------------------
        | FETCH ROUTER DATA
        |--------------------------------------------------------------------------
        */

        $context = stream_context_create([

            'http' => [

                'timeout' => 15

            ]

        ]);

        $result =
        @file_get_contents(
            $url,
            false,
            $context
        );

        if(!$result){

            continue;

        }

        $json =
        json_decode($result, true);

        if(
            !$json
            ||
            empty($json['success'])
        ){

            continue;

        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE USERS
        |--------------------------------------------------------------------------
        */

        foreach($json['active_users'] as $u){

            /*
            |--------------------------------------------------------------------------
            | DEVICE DETECTION
            |--------------------------------------------------------------------------
            */

            $host =
            strtolower(
                $u['host-name'] ?? ''
            );

            $user_agent = 'unknown';

            if(str_contains($host, 'android')){

                $user_agent = 'android';

            }
            elseif(str_contains($host, 'iphone')){

                $user_agent = 'iphone';

            }
            elseif(str_contains($host, 'windows')){

                $user_agent = 'windows';

            }
            elseif(str_contains($host, 'mac')){

                $user_agent = 'mac';

            }
            elseif(str_contains($host, 'linux')){

                $user_agent = 'linux';

            }

            /*
            |--------------------------------------------------------------------------
            | PUSH USER
            |--------------------------------------------------------------------------
            */

            $response['users'][] = [

                'username' =>
                $u['user'] ?? 'Unknown',

                'ip' =>
                $u['address'] ?? '-',

                'uptime' =>
                $u['uptime'] ?? '0',

                'bytes_in' =>
                (int)($u['bytes-in'] ?? 0),

                'bytes_out' =>
                (int)($u['bytes-out'] ?? 0),

                'router_name' =>
                $router['router_name'],

                'router_id' =>
                $router['id'],

                'user_agent' =>
                $user_agent

            ];

        }

    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    $response['success'] = true;

    echo json_encode($response);

}catch(Throwable $e){

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}