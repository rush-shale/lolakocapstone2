<?php
require_once __DIR__ . '/../config/config.php';

function start_app_session(): void {
	if (session_status() === PHP_SESSION_NONE) {
		session_name(SESSION_NAME);
		session_start();
	}
}

function generate_csrf_token(): string {
	start_app_session();
	$token = bin2hex(random_bytes(32));
	$_SESSION[CSRF_TOKEN_NAME] = $token;
	return $token;
}

function validate_csrf_token(?string $token): bool {
	start_app_session();
	return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], (string)$token);
}
?>


