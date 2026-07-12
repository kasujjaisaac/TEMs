<?php
define('ONYX_SKIP_AUTO_CONNECT', true);
require_once __DIR__ . '/config.php';
try {
    $pdo = onyx_create_pdo();
    echo "DB connect OK\n";
    foreach (['tenants','users'] as $table) {
        echo "TABLE $table\n";
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            printf("%s %s %s %s\n", $c['Field'], $c['Type'], $c['Null'], $c['Key']);
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
