<?php
require_once __DIR__ . '/config/db.php';

$pdo = get_db_connection();

$migrations = [
    'database/migrations/2024_06_01_add_waiting_validation_fields_to_seniors.sql',
    'database/migrations/2024_06_15_add_gender_to_seniors_table.sql',
    'database/migrations/2024_06_20_add_ext_name_to_seniors.sql',
    'database/migrations/2024_12_01_update_seniors_table_for_waiting_and_sex.sql',
    'database/migrations/2024_12_02_add_missing_columns_to_seniors.sql',
];

foreach ($migrations as $file) {
    if (file_exists($file)) {
        echo "Running $file<br>";
        $sql = file_get_contents($file);
        try {
            $pdo->exec($sql);
            echo "Success<br>";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "File not found: $file<br>";
    }
}
?>
