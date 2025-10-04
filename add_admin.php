<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = get_db_connection();

    // Check if admin already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE role = ? LIMIT 1');
    $stmt->execute(['admin']);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "Admin user already exists.\n";
        exit;
    }

    // Create admin user
    $name = 'Administrator';
    $email = 'admin@lolako.com';
    $password = 'admin123'; // Change this to a secure password
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)');
    $stmt->execute([$name, $email, $hash, 'admin']);

    echo "Admin user created successfully!\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Please change the password after first login.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
