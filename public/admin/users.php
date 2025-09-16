<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		if (($_POST['op'] ?? '') === 'create') {
			$name = trim($_POST['name'] ?? '');
			$email = trim($_POST['email'] ?? '');
			$password = $_POST['password'] ?? '';
			$role = $_POST['role'] === 'admin' ? 'admin' : 'user';
			$barangay = $role === 'user' ? trim($_POST['barangay'] ?? '') : null;
			if ($name && $email && ($password || $role === 'admin')) {
				$hash = $password ? password_hash($password, PASSWORD_BCRYPT) : password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
				$stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,barangay,active) VALUES (?,?,?,?,?,1)');
				try {
					$stmt->execute([$name,$email,$hash,$role,$barangay]);
					$message = 'User created';
				} catch (Throwable $e) {
					$message = 'Error: ' . $e->getMessage();
				}
			}
		}
		if (($_POST['op'] ?? '') === 'update') {
			$uid = (int)($_POST['id'] ?? 0);
			$name = trim($_POST['name'] ?? '');
			$email = trim($_POST['email'] ?? '');
			$role = $_POST['role'] === 'admin' ? 'admin' : 'user';
			$barangay = $role === 'user' ? trim($_POST['barangay'] ?? '') : null;
			$active = isset($_POST['active']) ? 1 : 0;
			if ($uid && $name && $email) {
				if (!empty($_POST['password'])) {
					$hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
					$stmt = $pdo->prepare('UPDATE users SET name=?, email=?, password_hash=?, role=?, barangay=?, active=? WHERE id=?');
					$stmt->execute([$name,$email,$hash,$role,$barangay,$active,$uid]);
				} else {
					$stmt = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, barangay=?, active=? WHERE id=?');
					$stmt->execute([$name,$email,$role,$barangay,$active,$uid]);
				}
				$message = 'User updated';
			}
		}
		if (($_POST['op'] ?? '') === 'delete') {
			$uid = (int)($_POST['id'] ?? 0);
			if ($uid) {
				$stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
				$stmt->execute([$uid]);
				$message = 'User deleted';
			}
		}
	}
}

$csrf = generate_csrf_token();

// Fetch for list/edit
$users = $pdo->query('SELECT id,name,email,role,barangay,active,created_at FROM users ORDER BY created_at DESC')->fetchAll();
$editUser = null;
if ($action === 'edit' && $id) {
	$stmt = $pdo->prepare('SELECT id,name,email,role,barangay,active FROM users WHERE id=?');
	$stmt->execute([$id]);
	$editUser = $stmt->fetch();
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Users | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<h1>Barangay Staff Users</h1>
		<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
		<div class="grid">
			<section>
				<h2><?= $editUser ? 'Edit User' : 'Add User' ?></h2>
				<form method="post">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="<?= $editUser ? 'update' : 'create' ?>">
					<?php if ($editUser): ?><input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>
					<label>Name</label>
					<input name="name" required value="<?= htmlspecialchars($editUser['name'] ?? '') ?>">
					<label>Email</label>
					<input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
					<label>Password <?= $editUser ? '(leave blank to keep)' : '' ?></label>
					<input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
					<label>Role</label>
					<select name="role" id="roleSelect">
						<option value="user" <?= ($editUser['role'] ?? '')==='user' ? 'selected' : '' ?>>User (Barangay Staff)</option>
						<option value="admin" <?= ($editUser['role'] ?? '')==='admin' ? 'selected' : '' ?>>Admin (OSCA Head)</option>
					</select>
					<label>Barangay (for User role)</label>
					<input name="barangay" value="<?= htmlspecialchars($editUser['barangay'] ?? '') ?>">
					<?php if ($editUser): ?>
						<label><input type="checkbox" name="active" <?= $editUser['active'] ? 'checked' : '' ?>> Active</label>
					<?php endif; ?>
					<button type="submit">Save</button>
				</form>
			</section>
			<section>
				<h2>All Users</h2>
				<table style="width:100%">
					<tr><th>Name</th><th>Email</th><th>Role</th><th>Barangay</th><th>Status</th><th></th></tr>
					<?php foreach ($users as $u): ?>
						<tr>
							<td><?= htmlspecialchars($u['name']) ?></td>
							<td><?= htmlspecialchars($u['email']) ?></td>
							<td><?= htmlspecialchars($u['role']) ?></td>
							<td><?= htmlspecialchars($u['barangay'] ?? '') ?></td>
							<td><?= $u['active'] ? 'Active' : 'Inactive' ?></td>
							<td>
								<a href="?action=edit&id=<?= (int)$u['id'] ?>">Edit</a>
								<form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
									<input type="hidden" name="csrf" value="<?= $csrf ?>">
									<input type="hidden" name="op" value="delete">
									<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
									<button type="submit">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</section>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


