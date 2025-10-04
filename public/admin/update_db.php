<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

try {
    // List of migration files in order
    $migrations = [
        __DIR__ . '/../../database/migrations/2024_06_01_add_waiting_validation_fields_to_seniors.sql',
        __DIR__ . '/../../database/migrations/2024_06_15_add_gender_to_seniors_table.sql',
        __DIR__ . '/../../database/migrations/2024_06_20_add_ext_name_to_seniors.sql',
        __DIR__ . '/../../database/migrations/2024_12_01_update_seniors_table_for_waiting_and_sex.sql',
    ];

    foreach ($migrations as $migration) {
        if (file_exists($migration)) {
            $sql = file_get_contents($migration);
            $pdo->exec($sql);
            echo "Executed migration: " . basename($migration) . "<br>";
        } else {
            echo "Migration file not found: " . basename($migration) . "<br>";
        }
    }

    // If seniors table doesn't exist, create it from complete schema
    $stmt = $pdo->query("SHOW TABLES LIKE 'seniors'");
    if ($stmt->rowCount() == 0) {
        $sql = file_get_contents(__DIR__ . '/../../database/COMPLETE_SYSTEM_SCHEMA_WITH_EXT_NAME.sql');
        // Remove the CREATE DATABASE and USE statements
        $sql = preg_replace('/CREATE DATABASE.*;/', '', $sql);
        $sql = preg_replace('/USE.*;/', '', $sql);
        $pdo->exec($sql);
        echo "Created seniors table from complete schema.<br>";
    }

    echo "Database migrations applied successfully.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
