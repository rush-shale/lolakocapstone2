<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

if (php_sapi_name() !== 'cli') {
    require_role('admin');
}
$pdo = get_db_connection();

function tableExists($pdo, $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    return $stmt->rowCount() > 0;
}

function columnExists($pdo, $table, $column) {
    if (!tableExists($pdo, $table)) {
        return false;
    }
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

try {
    // List of migration files in order
    $migrations = [
        [
            'file' => __DIR__ . '/../../database/migrations/2024_06_01_add_waiting_validation_fields_to_seniors.sql',
            'check' => function() use ($pdo) { return !columnExists($pdo, 'seniors', 'validation_status'); }
        ],
        [
            'file' => __DIR__ . '/../../database/migrations/2024_06_15_add_gender_to_seniors_table.sql',
            'check' => function() use ($pdo) { return !columnExists($pdo, 'seniors', 'gender'); }
        ],
        [
            'file' => __DIR__ . '/../../database/migrations/2024_06_20_add_ext_name_to_seniors.sql',
            'check' => function() use ($pdo) { return !columnExists($pdo, 'seniors', 'ext_name'); }
        ],
        [
            'file' => __DIR__ . '/../../database/migrations/2024_12_01_update_seniors_table_for_waiting_and_sex.sql',
            'check' => function() use ($pdo) { return !columnExists($pdo, 'seniors', 'sex'); }
        ],
        [
            'file' => __DIR__ . '/../../database/migrations/2024_12_02_add_missing_columns_to_seniors.sql',
            'check' => function() use ($pdo) { return !columnExists($pdo, 'seniors', 'osca_id_no'); }
        ],
    ];

    foreach ($migrations as $migration) {
        if (file_exists($migration['file'])) {
            if ($migration['check']()) {
                $sql = file_get_contents($migration['file']);
                $pdo->exec($sql);
                echo "Executed migration: " . basename($migration['file']) . "<br>";
            } else {
                echo "Migration already applied: " . basename($migration['file']) . "<br>";
            }
        } else {
            echo "Migration file not found: " . basename($migration['file']) . "<br>";
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
