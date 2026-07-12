<?php
session_start();

require_once('../config/db.php');

/*
|--------------------------------------------------------------------------
| DEVICE DETECTION
|--------------------------------------------------------------------------
*/

function detectDevice($user_agent){

    $user_agent = strtolower($user_agent);

    if(strpos($user_agent, 'android') !== false){
        return 'Android Phone';
    }

    if(strpos($user_agent, 'iphone') !== false){
        return 'iPhone';
    }

    if(strpos($user_agent, 'ipad') !== false){
        return 'iPad';
    }

    if(strpos($user_agent, 'windows') !== false){
        return 'Windows PC';
    }

    if(strpos($user_agent, 'mac os') !== false){
        return 'MacBook';
    }

    if(strpos($user_agent, 'linux') !== false){
        return 'Linux Device';
    }

    return 'Unknown Device';
}

/*
|--------------------------------------------------------------------------
| CREATE TABLES IF NOT EXISTS
|--------------------------------------------------------------------------
*/

$conn->query("
CREATE TABLE IF NOT EXISTS hotspot_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    username VARCHAR(100),
    mac_address VARCHAR(100),
    ip_address VARCHAR(100),
    user_agent TEXT,
    device_type VARCHAR(100),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

$conn->query("
CREATE TABLE IF NOT EXISTS hotspot_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    username VARCHAR(100),
    password VARCHAR(100),
    mac_address VARCHAR(100),
    ip_address VARCHAR(100),
    device_type VARCHAR(100),
    status VARCHAR(50) DEFAULT 'active',
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/

$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| HANDLE LOGIN
|--------------------------------------------------------------------------
*/

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $mac_address = $_POST['mac'] ?? 'Unknown';

    $ip_address = $_SERVER['REMOTE_ADDR'];

    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $device_type = detectDevice($user_agent);

    /*
    |--------------------------------------------------------------------------
    | DEMO AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    if(!empty($username) && !empty($password)){

        /*
        |--------------------------------------------------------------------------
        | SAVE DEVICE
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            INSERT INTO hotspot_devices
            (
                tenant_id,
                username,
                mac_address,
                ip_address,
                user_agent,
                device_type
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->bind_param(
            "isssss",
            $tenant_id,
            $username,
            $mac_address,
            $ip_address,
            $user_agent,
            $device_type
        );

        $stmt->execute();

        /*
        |--------------------------------------------------------------------------
        | CREATE ACTIVE SESSION
        |--------------------------------------------------------------------------
        */

        $session = $conn->prepare("
            INSERT INTO hotspot_sessions
            (
                tenant_id,
                username,
                password,
                mac_address,
                ip_address,
                device_type,
                status
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, 'active'
            )
        ");

        $session->bind_param(
            "isssss",
            $tenant_id,
            $username,
            $password,
            $mac_address,
            $ip_address,
            $device_type
        );

        $session->execute();

        /*
        |--------------------------------------------------------------------------
        | SESSION
        |--------------------------------------------------------------------------
        */

        $_SESSION['hotspot_user'] = $username;

        $success = "Login successful";

        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        header("refresh:2;url=https://google.com");

    }else{

        $error = "Invalid hotspot credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Onyx Hotspot Login</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, sans-serif;
}

body{
    background:#081018;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.login-container{
    width:100%;
    max-width:420px;
    background:#111b21;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 10px 40px rgba(0,0,0,0.5);
}

.top-banner{
    background:linear-gradient(135deg,#25d366,#128c7e);
    padding:40px 30px;
    text-align:center;
    color:white;
}

.top-banner h1{
    margin-top:15px;
    font-size:28px;
}

.top-banner p{
    margin-top:8px;
    opacity:0.9;
}

.logo{
    width:80px;
    height:80px;
    border-radius:50%;
    background:white;
    margin:auto;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:36px;
    color:#25d366;
}

.form-area{
    padding:30px;
}

.alert{
    padding:14px;
    border-radius:12px;
    margin-bottom:18px;
    font-size:14px;
}

.error{
    background:#dc2626;
    color:white;
}

.success{
    background:#16a34a;
    color:white;
}

.form-group{
    margin-bottom:20px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    color:#ccc;
}

.input-box{
    background:#1d2a33;
    border-radius:12px;
    display:flex;
    align-items:center;
    padding:0 15px;
}

.input-box i{
    color:#25d366;
}

.input-box input{
    width:100%;
    border:none;
    outline:none;
    background:none;
    color:white;
    padding:16px 14px;
}

.login-btn{
    width:100%;
    border:none;
    background:#25d366;
    color:white;
    padding:16px;
    border-radius:14px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:0.3s;
}

.login-btn:hover{
    background:#1ebc59;
}

.device-box{
    margin-top:20px;
    background:#1d2a33;
    border-radius:14px;
    padding:15px;
    color:#9ca3af;
    font-size:14px;
}

.footer{
    margin-top:20px;
    text-align:center;
    color:#9ca3af;
    font-size:13px;
}

.footer a{
    color:#25d366;
    text-decoration:none;
}

</style>

</head>

<body>

<div class="login-container">

<div class="top-banner">

<div class="logo">

<i class="fas fa-wifi"></i>

</div>

<h1>Onyx Hotspot</h1>

<p>Secure Internet Access Portal</p>

</div>

<div class="form-area">

<?php if($error != ''): ?>

<div class="alert error">

<?php echo $error; ?>

</div>

<?php endif; ?>

<?php if($success != ''): ?>

<div class="alert success">

<?php echo $success; ?>

</div>

<?php endif; ?>

<form method="POST">

<input
type="hidden"
name="mac"
id="mac_address"
value="Unknown">

<div class="form-group">

<label>Username</label>

<div class="input-box">

<i class="fas fa-user"></i>

<input
type="text"
name="username"
placeholder="Enter hotspot username"
required>

</div>

</div>

<div class="form-group">

<label>Password</label>

<div class="input-box">

<i class="fas fa-lock"></i>

<input
type="password"
name="password"
placeholder="Enter hotspot password"
required>

</div>

</div>

<button
type="submit"
name="login"
class="login-btn">

<i class="fas fa-sign-in-alt"></i>
Connect Internet

</button>

</form>

<div class="device-box">

<div style="margin-bottom:8px;">

<i class="fas fa-microchip"></i>
Device Detection Enabled

</div>

<div>

<?php echo detectDevice($_SERVER['HTTP_USER_AGENT']); ?>

</div>

</div>

<div class="footer">

Powered by
<a href="#">
Onyx Hub
</a>

</div>

</div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| FAKE MAC ADDRESS GENERATOR
|--------------------------------------------------------------------------
*/

function generateMac(){

    return 'XX:XX:XX:XX:XX:XX';

}

document.getElementById('mac_address').value = generateMac();

</script>

</body>
</html>