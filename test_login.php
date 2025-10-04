<?php
require_once __DIR__ . '/includes/auth.php';

$email = 'admin@lolako.com';
$password = 'admin123';

if (login($email, $password)) {
    $user = current_user();
    echo "Login successful!\n";
    echo "User: " . $user['name'] . "\n";
    echo "Role: " . $user['role'] . "\n";
} else {
    echo "Login failed.\n";
}
?>
