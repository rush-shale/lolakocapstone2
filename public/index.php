<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

start_app_session();
$user = current_user();
if ($user) {
	if ($user['role'] === 'admin') {
		header('Location: ' . BASE_URL . '/admin/dashboard.php');
		exit;
	} else {
		header('Location: ' . BASE_URL . '/user/dashboard.php');
		exit;
	}
}

$error = $_GET['error'] ?? '';
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>LoLaKo | Login</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body class="auth">
	<div class="auth-card">
		<h1>LoLaKo</h1>
		<?php if ($error): ?>
			<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>
		<form method="post" action="<?= BASE_URL ?>/login.php">
			<input type="hidden" name="csrf" value="<?= $csrf ?>">
			<label>Email</label>
			<input type="email" name="email" required>
			<label>Password</label>
			<input type="password" name="password" required>
			<button type="submit">Login</button>
		</form>
	</div>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


