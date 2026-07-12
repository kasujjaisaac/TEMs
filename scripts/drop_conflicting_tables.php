<?php
// Drops tables that conflict with Laravel's initial migrations.
$db = 'onyx_db';
$user = 'root';
$pass = '';
$host = '127.0.0.1';
$port = 3306;

$migrationTables = [
    'users', 'password_reset_tokens', 'sessions',
    'cache', 'cache_locks',
    'jobs', 'job_batches', 'failed_jobs'
];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $db . "'");
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Existing tables:\n";
    foreach ($existing as $t) echo " - $t\n";

    $toDrop = array_intersect($existing, $migrationTables);
    if (empty($toDrop)) {
        echo "\nNo conflicting tables found.\n";
        exit(0);
    }

    echo "\nDropping conflicting tables:\n";
    foreach ($toDrop as $t) {
        echo " - Dropping $t... ";
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
            echo "done.\n";
        } catch (Exception $e) {
            echo "failed (will retry with FK checks disabled).\n";
            // Retry with foreign key checks disabled
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            echo " - Dropped $t with FK checks disabled.\n";
        }
    }

    echo "\nDropped all conflicts.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
