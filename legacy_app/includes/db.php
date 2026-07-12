<?php

define('ONYX_SKIP_AUTO_CONNECT', true);
require_once __DIR__ . '/../config.php';

try {
    $pdo = onyx_create_pdo();
} catch (Exception $e) {
    die("DB ERROR: Database connection failed. Check config.php credentials.");
}
