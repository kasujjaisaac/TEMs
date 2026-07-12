<?php
header('Content-Type: application/json');

$root = $_SERVER['DOCUMENT_ROOT'];

try {
    require_once($root . '/config.php');

    $diagnosis = [];

    // Check transactions table
    try {
        $result = $pdo->query("DESCRIBE transactions");
        $diagnosis['transactions_columns'] = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $diagnosis['transactions_error'] = $e->getMessage();
    }

    // Check accounts table
    try {
        $result = $pdo->query("DESCRIBE accounts");
        $diagnosis['accounts_columns'] = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $diagnosis['accounts_error'] = $e->getMessage();
    }

    // Check routers table
    try {
        $result = $pdo->query("DESCRIBE routers");
        $diagnosis['routers_columns'] = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $diagnosis['routers_error'] = $e->getMessage();
    }

    // Check agent_commissions table
    try {
        $result = $pdo->query("DESCRIBE agent_commissions");
        $diagnosis['agent_commissions_columns'] = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $diagnosis['agent_commissions_error'] = $e->getMessage();
    }

    // List all tables
    $result = $pdo->query("SHOW TABLES");
    $diagnosis['all_tables'] = $result->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($diagnosis, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['fatal_error' => $e->getMessage()]);
}
?>