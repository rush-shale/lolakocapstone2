<?php
require_once __DIR__ . '/config/db.php';

$pdo = get_db_connection();

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'seniors'");
    echo "Tables found: " . $stmt->rowCount() . "\n";

    if ($stmt->rowCount() > 0) {
        echo "Seniors table exists.\n";
        $stmt = $pdo->query("DESCRIBE seniors");
        $columns = $stmt->fetchAll();
        echo "Columns:\n";
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    } else {
        echo "Seniors table does not exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
