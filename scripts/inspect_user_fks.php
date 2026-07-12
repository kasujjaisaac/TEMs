<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=onyx_db','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $sql = "SELECT TABLE_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME,COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA='onyx_db' AND REFERENCED_TABLE_NAME='users'";
    $q = $pdo->query($sql);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No FK referencing users found\n";
        exit(0);
    }
    foreach ($rows as $r) {
        echo "FK: " . $r['CONSTRAINT_NAME'] . " on table " . $r['TABLE_NAME'] . " (column " . $r['COLUMN_NAME'] . ") references users." . $r['REFERENCED_COLUMN_NAME'] . "\n";
        $c = $pdo->query("SHOW CREATE TABLE `" . $r['TABLE_NAME'] . "`")->fetch(PDO::FETCH_ASSOC);
        echo "--- SHOW CREATE TABLE for " . $r['TABLE_NAME'] . " ---\n" . $c['Create Table'] . "\n\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
