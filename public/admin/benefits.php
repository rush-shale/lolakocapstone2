<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$op = $_POST['op'] ?? '';
		if ($op === 'mark_benefits') {
			$id = (int)($_POST['id'] ?? 0);
			$benefits_status = isset($_POST['benefits_received']) ? 1 : 0;
			if ($id) {
				$stmt = $pdo->prepare('UPDATE seniors SET benefits_received=? WHERE id=?');
				$stmt->execute([$benefits_status, $id]);
				$message = 'Benefits status updated successfully';
			}
		}
		if ($op === 'bulk_mark') {
			$senior_ids = $_POST['senior_ids'] ?? [];
			$benefits_status = isset($_POST['bulk_benefits']) ? 1 : 0;
			if (!empty($senior_ids)) {
				$placeholders = str_repeat('?,', count($senior_ids) - 1) . '?';
				$stmt = $pdo->prepare("UPDATE seniors SET benefits_received=? WHERE id IN ($placeholders)");
				$stmt->execute(array_merge([$benefits_status], $senior_ids));
				$message = 'Bulk benefits status updated successfully';
			}
		}
	}
}

$csrf = generate_csrf_token();

// Get seniors who haven't received benefits yet
$pendingSeniors = $pdo->query("SELECT * FROM seniors WHERE life_status='living' AND benefits_received=0 ORDER BY last_name, first_name")->fetchAll();

// Get seniors who have received benefits
$receivedSeniors = $pdo->query("SELECT * FROM seniors WHERE life_status='living' AND benefits_received=1 ORDER BY last_name, first_name")->fetchAll();

$totalPending = count($pendingSeniors);
$totalReceived = count($receivedSeniors);

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Benefits Management | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Benefits Management</h1>
			<p class="content-subtitle">Mark seniors as having received their benefits</p>
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
		
			<div class="stats animate-fade-in">
				<div class="stat warning">
					<div class="stat-icon">
						<i class="fas fa-clock"></i>
					</div>
					<div class="stat-content">
						<h3>Pending Benefits</h3>
						<p class="number"><?= $totalPending ?></p>
					</div>
				</div>
				<div class="stat success">
					<div class="stat-icon">
						<i class="fas fa-check-circle"></i>
					</div>
					<div class="stat-content">
						<h3>Benefits Received</h3>
						<p class="number"><?= $totalReceived ?></p>
					</div>
				</div>
				<div class="stat">
					<div class="stat-icon">
						<i class="fas fa-users"></i>
					</div>
					<div class="stat-content">
						<h3>Total Living</h3>
						<p class="number"><?= $totalPending + $totalReceived ?></p>
					</div>
				</div>
			</div>

			<div class="grid grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-clock"></i>
							Seniors Pending Benefits
						</h2>
						<p class="card-subtitle">Mark these seniors as having received their benefits</p>
					</div>
				<form method="post" id="bulk-form">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="bulk_mark">
					<input type="hidden" name="bulk_benefits" value="1">
					
					<div class="form-actions">
						<button type="submit" class="button success" onclick="return confirm('Mark all selected seniors as having received benefits?')">
							<i class="fas fa-check-double"></i>
							Mark All Selected as Received
						</button>
					</div>
					
					<div class="table-container">
						<table>
							<thead>
								<tr>
									<th><input type="checkbox" id="select-all-pending" onchange="toggleAllPending(this)"></th>
									<th>Name</th>
									<th>Age</th>
									<th>Barangay</th>
									<th>Category</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($pendingSeniors as $s): ?>
									<tr>
										<td>
											<input type="checkbox" name="senior_ids[]" value="<?= (int)$s['id'] ?>" class="pending-checkbox">
										</td>
										<td>
											<div class="senior-info">
												<div class="senior-avatar">
													<i class="fas fa-user"></i>
												</div>
												<div class="senior-details">
													<span class="senior-name"><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></span>
													<?php if ($s['middle_name']): ?>
														<span class="senior-middle"><?= htmlspecialchars($s['middle_name']) ?></span>
													<?php endif; ?>
												</div>
											</div>
										</td>
										<td><?= (int)$s['age'] ?></td>
										<td><?= htmlspecialchars($s['barangay']) ?></td>
										<td>
											<span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-warning' ?>">
												<?= $s['category'] === 'local' ? 'Local' : 'National' ?>
											</span>
										</td>
										<td>
											<form method="post" style="display:inline">
												<input type="hidden" name="csrf" value="<?= $csrf ?>">
												<input type="hidden" name="op" value="mark_benefits">
												<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
												<input type="hidden" name="benefits_received" value="1">
												<button type="submit" class="button small success">
													<i class="fas fa-check"></i>
													Mark Received
												</button>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
								<?php if (empty($pendingSeniors)): ?>
									<tr>
										<td colspan="6">
											<div class="empty-state">
												<div class="empty-icon">
													<i class="fas fa-check-circle"></i>
												</div>
												<h3>All Benefits Distributed!</h3>
												<p>All seniors have received their benefits.</p>
											</div>
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</form>
			</div>

				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-check-circle"></i>
							Seniors Who Received Benefits
						</h2>
						<p class="card-subtitle">Seniors who have already received their benefits</p>
					</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Age</th>
								<th>Barangay</th>
								<th>Category</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($receivedSeniors as $s): ?>
								<tr>
									<td>
										<div class="senior-info">
											<div class="senior-avatar">
												<i class="fas fa-user"></i>
											</div>
											<div class="senior-details">
												<span class="senior-name"><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></span>
												<?php if ($s['middle_name']): ?>
													<span class="senior-middle"><?= htmlspecialchars($s['middle_name']) ?></span>
												<?php endif; ?>
											</div>
										</div>
									</td>
									<td><?= (int)$s['age'] ?></td>
									<td><?= htmlspecialchars($s['barangay']) ?></td>
									<td>
										<span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-warning' ?>">
											<?= $s['category'] === 'local' ? 'Local' : 'National' ?>
										</span>
									</td>
									<td>
										<form method="post" style="display:inline">
											<input type="hidden" name="csrf" value="<?= $csrf ?>">
											<input type="hidden" name="op" value="mark_benefits">
											<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
											<button type="submit" class="button small warning">
												<i class="fas fa-clock"></i>
												Mark Pending
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($receivedSeniors)): ?>
								<tr>
									<td colspan="5">
										<div class="empty-state">
											<div class="empty-icon">
												<i class="fas fa-gift"></i>
											</div>
											<h3>No Benefits Distributed Yet</h3>
											<p>No seniors have received benefits yet.</p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			</div>
		</div>
	</main>
	
	<script>
		function toggleAllPending(checkbox) {
			const checkboxes = document.querySelectorAll('.pending-checkbox');
			checkboxes.forEach(cb => cb.checked = checkbox.checked);
		}
		
		// Update select all checkbox when individual checkboxes change
		document.querySelectorAll('.pending-checkbox').forEach(checkbox => {
			checkbox.addEventListener('change', function() {
				const allCheckboxes = document.querySelectorAll('.pending-checkbox');
				const checkedCheckboxes = document.querySelectorAll('.pending-checkbox:checked');
				const selectAllCheckbox = document.getElementById('select-all-pending');
				
				selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
				selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
			});
		});
	</script>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>
