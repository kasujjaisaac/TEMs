<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$ok = Auth::attempt([
    'email' => 'superadmin@clinic.test',
    'password' => 'password',
]);

echo $ok ? "Auth OK\n" : "Auth failed\n";
