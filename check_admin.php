<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = get_db_connection();

    // Check if admin exists
    $stmt = $pdo->prepare('SELECT id, name, email, role, active FROM users WHERE role = ? LIMIT 1');
    $stmt->execute(['admin']);
    $user = $stmt->fetch();

    if ($user) {
        echo "Admin user found:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Active: " . ($user['active'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "No admin user found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
