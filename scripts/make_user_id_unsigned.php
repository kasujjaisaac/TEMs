<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=onyx_db','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $stmts = [
        "ALTER TABLE `audit_logs` DROP FOREIGN KEY `audit_logs_ibfk_2`",
        "ALTER TABLE `journal_entries` DROP FOREIGN KEY `journal_entries_ibfk_2`",
        "ALTER TABLE `audit_logs` MODIFY `user_id` BIGINT UNSIGNED NULL",
        "ALTER TABLE `journal_entries` MODIFY `user_id` BIGINT UNSIGNED NOT NULL"
    ];
    foreach ($stmts as $s) {
        echo "Executing: $s\n";
        $pdo->exec($s);
        echo "OK\n";
    }
    echo "All alterations applied.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
