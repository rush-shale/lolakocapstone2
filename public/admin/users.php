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
					$message = 'Staff created';
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
				$message = 'Staff updated';
			}
		}
		if (($_POST['op'] ?? '') === 'delete') {
			$uid = (int)($_POST['id'] ?? 0);
			if ($uid) {
				$stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
				$stmt->execute([$uid]);
				$message = 'Staff deleted';
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
	<title>Staff Management | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Staff Management</h1>
			<p class="content-subtitle">Manage barangay staff accounts</p>
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
							<?= $editUser ? 'Edit Staff' : 'Add New Staff' ?>
						</h2>
						<p class="card-subtitle"><?= $editUser ? 'Update staff information' : 'Create a new staff account' ?></p>
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
								<select name="role" id="roleSelect" class="form-input" required>
									<option value="user" <?= ($editUser['role'] ?? '')==='user' ? 'selected' : '' ?>>User (Barangay Staff)</option>
									<option value="admin" <?= ($editUser['role'] ?? '')==='admin' ? 'selected' : '' ?>>Admin (OSCA Head)</option>
								</select>
							</div>
							
							<div class="form-group" id="barangayGroup" style="<?= ($editUser['role'] ?? '') === 'admin' ? 'display: none;' : '' ?>">
								<label class="form-label">Barangay Assignment</label>
								<input type="text" name="barangay" class="form-input" value="<?= htmlspecialchars($editUser['barangay'] ?? '') ?>" placeholder="Enter barangay name" <?= ($editUser['role'] ?? '') === 'admin' ? 'disabled' : 'required' ?>>
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
									<?= $editUser ? 'Update Staff' : 'Create Staff' ?>
								</button>
							</div>
						</form>
					</div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-users"></i>
							All Staff Accounts
						</h2>
						<p class="card-subtitle">Manage existing staff accounts</p>
					</div>
					<div class="card-body">
						<?php if (!empty($users)): ?>
						<div class="table-container">
							<table class="table" style="font-size: 0.85rem; table-layout: fixed; width: 100%;">
								<thead>
									<tr>
										<th style="width: 15%;">Name</th>
										<th style="width: 20%;">Email</th>
										<th style="width: 10%;">Role</th>
										<th style="width: 15%;">Barangay</th>
										<th style="width: 10%;">Status</th>
										<th style="width: 15%;">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($users as $u): ?>
									<tr>
										<td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
											<div class="user-info">
												<div class="user-avatar" style="width: 30px; height: 30px;">
													<i class="fas fa-user"></i>
												</div>
												<div class="user-details" style="overflow: hidden; text-overflow: ellipsis;">
													<span class="user-name"><?= htmlspecialchars($u['name']) ?></span>
												</div>
											</div>
										</td>
										<td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($u['email']) ?></td>
										<td style="text-align: center;">
											<span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-info' ?>" style="padding: 0.25em 0.5em; font-size: 0.75rem;">
												<?= ucfirst($u['role']) ?>
											</span>
										</td>
										<td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($u['barangay'] ?? 'N/A') ?></td>
										<td style="text-align: center;">
											<span class="badge <?= $u['active'] ? 'badge-success' : 'badge-muted' ?>" style="padding: 0.25em 0.5em; font-size: 0.75rem;">
												<?= $u['active'] ? 'Active' : 'Inactive' ?>
											</span>
										</td>
										<td style="text-align: center;">
											<div class="action-buttons" style="display:flex; gap: 0.5rem; justify-content: center;">
												<?php if ($u['role'] !== 'admin'): ?>
												<a href="?action=edit&id=<?= (int)$u['id'] ?>" class="button small secondary" style="display:inline-flex; align-items:center; gap: 0.1rem; font-size: 0.55rem; padding: 0.15em 0.4em; border-radius: 0.2rem; box-shadow: 0 1px 2px rgba(0,0,0,0.08); transition: background-color 0.3s ease; background-color: #e0e7ff; width: 90px; height: 28px; justify-content: center;" onmouseover="this.style.backgroundColor='#c7d2fe'" onmouseout="this.style.backgroundColor='#e0e7ff'">
													<i class="fas fa-edit" style="color: #3730a3; font-size: 0.7rem;"></i>
													<span style="color: #3730a3; font-weight: 600;">Edit</span>
												</a>
												<form method="post" style="display:inline" onsubmit="return confirm('Delete this staff?')">
													<input type="hidden" name="csrf" value="<?= $csrf ?>">
													<input type="hidden" name="op" value="delete">
													<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
													<button type="submit" class="button small danger" style="display:inline-flex; align-items:center; gap: 0.2rem; font-size: 0.55rem; padding: 0.15em 0.4em; border-radius: 0.2rem; box-shadow: 0 1px 2px rgba(0,0,0,0.08); transition: background-color 0.3s ease; background-color: #fee2e2; width: 65px; height: 28px; justify-content: center;" onmouseover="this.style.backgroundColor='#fecaca'" onmouseout="this.style.backgroundColor='#fee2e2'">
														<i class="fas fa-trash" style="color: #b91c1c;"></i>
														<span style="color: white; font-weight: 600;">Delete</span>
													</button>
												</form>
												<?php endif; ?>
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


