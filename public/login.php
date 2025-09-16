<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ' . BASE_URL . '/index.php');
	exit;
}

start_app_session();
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$csrf = $_POST['csrf'] ?? '';

if (!validate_csrf_token($csrf)) {
	header('Location: ' . BASE_URL . '/index.php?error=Invalid+session');
	exit;
}

if ($email === '' || $password === '') {
	header('Location: ' . BASE_URL . '/index.php?error=Missing+credentials');
	exit;
}

if (login($email, $password)) {
	$user = current_user();
	$target = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php';
	header('Location: ' . BASE_URL . $target);
	exit;
}

header('Location: ' . BASE_URL . '/index.php?error=Invalid+email+or+password');
exit;


