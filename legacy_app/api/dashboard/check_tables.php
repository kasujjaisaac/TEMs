<?php
header('Content-Type: application/json');

$root = $_SERVER['DOCUMENT_ROOT'];
$tenant_id = $_GET['tenant_id'] ?? '82';

try {
    require_once($root . '/config.php');

    $response = [
        'tables' => [],
        'errors' => []
    ];

    // Check transactions table
    try {
        $result = $pdo->query("DESCRIBE transactions");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $response['tables']['transactions'] = $columns;
    } catch (Exception $e) {
        $response['errors']['transactions'] = $e->getMessage();
    }

    // Check accounts table
    try {
        $result = $pdo->query("DESCRIBE accounts");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $response['tables']['accounts'] = $columns;
    } catch (Exception $e) {
        $response['errors']['accounts'] = $e->getMessage();
    }

    // Check routers table
    try {
        $result = $pdo->query("DESCRIBE routers");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $response['tables']['routers'] = $columns;
    } catch (Exception $e) {
        $response['errors']['routers'] = $e->getMessage();
    }

    // List all tables
    try {
        $result = $pdo->query("SHOW TABLES");
        $all_tables = $result->fetchAll(PDO::FETCH_COLUMN);
        $response['all_tables'] = $all_tables;
    } catch (Exception $e) {
        $response['errors']['all_tables'] = $e->getMessage();
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>