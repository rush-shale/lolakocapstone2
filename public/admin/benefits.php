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
	<title>Benefits Management | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<div class="page-header">
			<h1>🎁 Benefits Management</h1>
			<p>Mark seniors as having received their benefits</p>
		</div>
		
		<?php if ($message): ?>
			<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
		<?php endif; ?>
		
		<div class="stats">
			<div class="stat warning">
				<h3>⏳ Pending Benefits</h3>
				<p class="number"><?= $totalPending ?></p>
			</div>
			<div class="stat success">
				<h3>✅ Benefits Received</h3>
				<p class="number"><?= $totalReceived ?></p>
			</div>
			<div class="stat">
				<h3>📊 Total Living</h3>
				<p class="number"><?= $totalPending + $totalReceived ?></p>
			</div>
		</div>

		<div class="grid grid-2">
			<div class="card">
				<div class="card-header">
					<h2>⏳ Seniors Pending Benefits</h2>
					<p>Mark these seniors as having received their benefits</p>
				</div>
				<form method="post" id="bulk-form">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="bulk_mark">
					<input type="hidden" name="bulk_benefits" value="1">
					
					<div style="margin-bottom: 1rem;">
						<button type="submit" class="success" onclick="return confirm('Mark all selected seniors as having received benefits?')">
							✅ Mark All Selected as Received
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
											<strong><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></strong>
											<?php if ($s['middle_name']): ?>
												<br><small><?= htmlspecialchars($s['middle_name']) ?></small>
											<?php endif; ?>
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
												<button type="submit" class="small success">✅ Mark Received</button>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
								<?php if (empty($pendingSeniors)): ?>
									<tr>
										<td colspan="6" style="text-align: center; padding: 2rem; color: var(--muted);">
											🎉 All seniors have received their benefits!
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
					<h2>✅ Seniors Who Received Benefits</h2>
					<p>Seniors who have already received their benefits</p>
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
										<strong><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></strong>
										<?php if ($s['middle_name']): ?>
											<br><small><?= htmlspecialchars($s['middle_name']) ?></small>
										<?php endif; ?>
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
											<button type="submit" class="small warning">⏳ Mark Pending</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($receivedSeniors)): ?>
								<tr>
									<td colspan="5" style="text-align: center; padding: 2rem; color: var(--muted);">
										No seniors have received benefits yet.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
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
