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
	<title>User Management | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">User Management</h1>
			<p class="content-subtitle">Manage system users and staff accounts</p>
		</header>
		
		<div class="content-body">
			<?php if ($message): ?>
			<div class="alert alert-success animate-fade-in">
				<div class="alert-icon">
					<i class="fas fa-check-circle"></i>
				</div>
				<div class="alert-content">
					<strong>Success!</strong>
					<p><?= htmlspecialchars($message) ?></p>
				</div>
			</div>
			<?php endif; ?>
			
			<div class="grid grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-user-plus"></i>
							<?= $editUser ? 'Edit User' : 'Add New User' ?>
						</h2>
						<p class="card-subtitle"><?= $editUser ? 'Update user information' : 'Create a new user account' ?></p>
					</div>
					<div class="card-body">
						<form method="post" class="form">
							<input type="hidden" name="csrf" value="<?= $csrf ?>">
							<input type="hidden" name="op" value="<?= $editUser ? 'update' : 'create' ?>">
							<?php if ($editUser): ?><input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>
							
							<div class="form-group">
								<label class="form-label">Full Name</label>
								<input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" placeholder="Enter full name">
							</div>
							
							<div class="form-group">
								<label class="form-label">Email Address</label>
								<input type="email" name="email" class="form-input" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" placeholder="Enter email address">
							</div>
							
							<div class="form-group">
								<label class="form-label">Password <?= $editUser ? '(leave blank to keep current)' : '' ?></label>
								<input type="password" name="password" class="form-input" <?= $editUser ? '' : 'required' ?> placeholder="Enter password">
							</div>
							
							<div class="form-group">
								<label class="form-label">Role</label>
								<select name="role" id="roleSelect" class="form-input">
									<option value="user" <?= ($editUser['role'] ?? '')==='user' ? 'selected' : '' ?>>User (Barangay Staff)</option>
									<option value="admin" <?= ($editUser['role'] ?? '')==='admin' ? 'selected' : '' ?>>Admin (OSCA Head)</option>
								</select>
							</div>
							
							<div class="form-group" id="barangayGroup" style="<?= ($editUser['role'] ?? '') === 'admin' ? 'display: none;' : '' ?>">
								<label class="form-label">Barangay Assignment</label>
								<input type="text" name="barangay" class="form-input" value="<?= htmlspecialchars($editUser['barangay'] ?? '') ?>" placeholder="Enter barangay name">
							</div>
							
							<?php if ($editUser): ?>
							<div class="form-group">
								<label class="checkbox-label">
									<input type="checkbox" name="active" <?= $editUser['active'] ? 'checked' : '' ?> class="checkbox-input">
									<span class="checkbox-custom"></span>
									Active Account
								</label>
							</div>
							<?php endif; ?>
							
							<div class="form-actions">
								<?php if ($editUser): ?>
								<a href="users.php" class="button secondary">
									<i class="fas fa-times"></i>
									Cancel
								</a>
								<?php endif; ?>
								<button type="submit" class="button primary">
									<i class="fas fa-save"></i>
									<?= $editUser ? 'Update User' : 'Create User' ?>
								</button>
							</div>
						</form>
					</div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-users"></i>
							All Users
						</h2>
						<p class="card-subtitle">Manage existing user accounts</p>
					</div>
					<div class="card-body">
						<?php if (!empty($users)): ?>
						<div class="table-container">
							<table class="table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Email</th>
										<th>Role</th>
										<th>Barangay</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($users as $u): ?>
									<tr>
										<td>
											<div class="user-info">
												<div class="user-avatar">
													<i class="fas fa-user"></i>
												</div>
												<div class="user-details">
													<span class="user-name"><?= htmlspecialchars($u['name']) ?></span>
												</div>
											</div>
										</td>
										<td><?= htmlspecialchars($u['email']) ?></td>
										<td>
											<span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-info' ?>">
												<?= ucfirst($u['role']) ?>
											</span>
										</td>
										<td><?= htmlspecialchars($u['barangay'] ?? 'N/A') ?></td>
										<td>
											<span class="badge <?= $u['active'] ? 'badge-success' : 'badge-muted' ?>">
												<?= $u['active'] ? 'Active' : 'Inactive' ?>
											</span>
										</td>
										<td>
											<div class="action-buttons">
												<a href="?action=edit&id=<?= (int)$u['id'] ?>" class="button small secondary">
													<i class="fas fa-edit"></i>
													Edit
												</a>
												<form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
													<input type="hidden" name="csrf" value="<?= $csrf ?>">
													<input type="hidden" name="op" value="delete">
													<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
													<button type="submit" class="button small danger">
														<i class="fas fa-trash"></i>
														Delete
													</button>
												</form>
											</div>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-users"></i>
							</div>
							<h3>No Users Found</h3>
							<p>No users have been created yet.</p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Handle role selection
		document.getElementById('roleSelect').addEventListener('change', function() {
			const barangayGroup = document.getElementById('barangayGroup');
			if (this.value === 'admin') {
				barangayGroup.style.display = 'none';
			} else {
				barangayGroup.style.display = 'block';
			}
		});
	</script>
</body>
</html>


