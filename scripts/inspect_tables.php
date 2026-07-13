<?php

$database = $argv[1] ?? 'onyx_db';

$pdo = new PDO(
    "mysql:host=127.0.0.1;port=3306;dbname={$database}",
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = $pdo->query(
    "SELECT TABLE_NAME, ENGINE, TABLE_TYPE
     FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = " . $pdo->quote($database) . "
     ORDER BY TABLE_NAME"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($tables as $table) {
    echo $table['TABLE_NAME'] . ' | ' . $table['ENGINE'] . ' | ' . $table['TABLE_TYPE'] . PHP_EOL;

    try {
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table['TABLE_NAME']) . '`')
            ->fetch(PDO::FETCH_ASSOC);
        echo '  SHOW CREATE: OK' . PHP_EOL;
    } catch (Throwable $e) {
        echo '  SHOW CREATE: ERROR ' . $e->getMessage() . PHP_EOL;
    }
}
