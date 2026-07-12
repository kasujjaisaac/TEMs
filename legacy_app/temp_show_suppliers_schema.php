<?php
require_once __DIR__ . '/config.php';
try {
    $pdo = onyx_create_pdo();
    $stmt = $pdo->query('SHOW COLUMNS FROM suppliers');
    foreach ($stmt as $row) {
        echo $row['Field'] . '\t' . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
