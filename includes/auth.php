<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

function sanitize_string(string $value): string {
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function login(string $email, string $password): bool {
	start_app_session();
	$pdo = get_db_connection();
	$stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, barangay, active FROM users WHERE email = ? LIMIT 1');
	$stmt->execute([$email]);
	$user = $stmt->fetch();
	if (!$user) {
		return false;
	}
	if (!(bool)$user['active']) {
		return false;
	}
	if (!password_verify($password, $user['password_hash'])) {
		return false;
	}
	$_SESSION['user'] = [
		'id' => (int)$user['id'],
		'name' => $user['name'],
		'email' => $user['email'],
		'role' => $user['role'],
		'barangay' => $user['barangay'],
	];
	return true;
}

function logout(): void {
	start_app_session();
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	session_destroy();
}

function current_user(): ?array {
	start_app_session();
	return $_SESSION['user'] ?? null;
}

function require_login(): void {
	if (!current_user()) {
		header('Location: ' . BASE_URL . '/index.php');
		exit;
	}
}

function require_role(string $role): void {
	require_login();
	$user = current_user();
	if (!$user || $user['role'] !== $role) {
		header('HTTP/1.1 403 Forbidden');
		echo 'Forbidden';
		exit;
	}
}
?>


