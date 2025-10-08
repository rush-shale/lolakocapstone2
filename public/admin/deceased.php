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
		if ($op === 'toggle_life') {
			$id = (int)($_POST['id'] ?? 0);
			$to = $_POST['to'] === 'living' ? 'living' : 'deceased';
			if ($id) {
				$stmt = $pdo->prepare('UPDATE seniors SET life_status=? WHERE id=?');
				$stmt->execute([$to, $id]);
				$message = 'Life status updated successfully';
			}
		}
		if ($op === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				$stmt = $pdo->prepare('DELETE FROM seniors WHERE id=?');
				$stmt->execute([$id]);
				$message = 'Senior record deleted successfully';
			}
		}
	}
}

$csrf = generate_csrf_token();

// Get deceased seniors
$deceasedSeniors = $pdo->query("SELECT * FROM seniors WHERE life_status='deceased' ORDER BY created_at DESC")->fetchAll();

// Get statistics
$totalDeceased = count($deceasedSeniors);
$localDeceased = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased' AND category='local'")->fetchColumn();
$nationalDeceased = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased' AND category='national'")->fetchColumn();
$benefitsReceivedDeceased = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased' AND benefits_received=1")->fetchColumn();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Deceased Seniors | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<div class="page-header">
			<h1>💀 Deceased Seniors</h1>
			<p>Records of senior citizens who have passed away</p>
		</div>
		
		<?php if ($message): ?>
			<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
		<?php endif; ?>
		
		<div class="stats">
			<div class="stat danger">
				<h3>💀 Total Deceased</h3>
				<p class="number"><?= $totalDeceased ?></p>
			</div>
			<div class="stat">
				<h3>🏘️ Local Deceased</h3>
				<p class="number"><?= $localDeceased ?></p>
			</div>
			<div class="stat">
				<h3>🇵🇭 National Deceased</h3>
				<p class="number"><?= $nationalDeceased ?></p>
			</div>
			<div class="stat success">
				<h3>✅ Benefits Received</h3>
				<p class="number"><?= $benefitsReceivedDeceased ?></p>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h2>📋 Deceased Senior Citizens</h2>
				<p>Complete list of senior citizens who have passed away</p>
			</div>
			<div class="table-container">
				<table class="table">
					<thead>
						<tr>
							<th>LAST NAME</th>
							<th>FIRST NAME</th>
							<th>MIDDLE NAME</th>
							<th>EXT</th>
							<th>BARANGAY</th>
							<th>AGE</th>
							<th>SEX</th>
							<th>CIVIL STATUS</th>
							<th>BIRTHDATE</th>
							<th>OSCA ID NO.</th>
							<th>REMARKS</th>
							<th>HEALTH CONDITION</th>
							<th>PUROK</th>
							<th>PLACE OF BIRTH</th>
							<th>CELLPHONE #</th>
							<th>LIFE STATUS</th>
							<th>CATEGORY</th>
							<th>VALIDATION STATUS</th>
							<th>VALIDATED</th>
							<th>ACTIONS</th>
						</tr>
					</thead>
					<tbody>
                        <?php foreach ($deceasedSeniors as $s): ?>
                            <tr class="clickable-row" onclick="window.location.href='death_details.php?id=<?= (int)$s['id'] ?>'" style="cursor:pointer;">
                                <td><?= htmlspecialchars(ucfirst(strtolower($s['last_name']))) ?></td>
                                <td><?= htmlspecialchars(ucfirst(strtolower($s['first_name']))) ?></td>
								<td><?= htmlspecialchars($s['middle_name'] ?: '') ?></td>
								<td><?= isset($s['ext_name']) ? htmlspecialchars($s['ext_name']) : '' ?></td>
								<td><?= htmlspecialchars($s['barangay']) ?></td>
								<td><?= (int)$s['age'] ?></td>
								<td>
									<?php
									switch ($s['sex']) {
										case 'male': echo 'Male'; break;
										case 'female': echo 'Female'; break;
										case 'lgbtq': echo 'LGBTQ+'; break;
										default: echo 'Not specified';
									}
									?>
								</td>
								<td><?= htmlspecialchars($s['civil_status'] ?: '') ?></td>
								<td><?= $s['date_of_birth'] ? date('M d, Y', strtotime($s['date_of_birth'])) : '' ?></td>
								<td><?= htmlspecialchars($s['osca_id_no'] ?? '') ?></td>
								<td><?= htmlspecialchars($s['remarks'] ?? '') ?></td>
								<td><?= htmlspecialchars($s['health_condition'] ?? '') ?></td>
								<td><?= htmlspecialchars($s['purok'] ?? '') ?></td>
								<td><?= htmlspecialchars($s['place_of_birth'] ?: '') ?></td>
								<td><?= htmlspecialchars($s['cellphone'] ?? '') ?></td>
								<td>
									<span class="badge badge-danger">Deceased</span>
								</td>
								<td>
									<span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>">
										<?= ucfirst($s['category']) ?>
									</span>
								</td>
								<td>
									<span class="badge <?= ($s['validation_status'] ?? '') === 'Validated' ? 'badge-success' : 'badge-warning' ?>">
										<?= $s['validation_status'] ?? 'Not Validated' ?>
									</span>
								</td>
								<td><?= isset($s['validation_date']) && $s['validation_date'] ? date('M d, Y H:i', strtotime($s['validation_date'])) : '-' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="button small" href="death_details.php?id=<?= (int)$s['id'] ?>" title="View Death Info" onclick="event.stopPropagation();">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="post" style="display:inline" onclick="event.stopPropagation();">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="op" value="toggle_life">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="to" value="living">
                                            <button type="submit" class="button small secondary" title="Mark as Living" onclick="return confirm('Mark this senior as living? This will restore them to the active list.')">
                                                <i class="fas fa-user"></i>
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to permanently delete this senior record? This action cannot be undone.')" onclick="event.stopPropagation();">
                                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="op" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" class="button small danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
							</tr>
						<?php endforeach; ?>
						<?php if (empty($deceasedSeniors)): ?>
							<tr>
								<td colspan="20" style="text-align: center; padding: 2rem; color: var(--muted);">
									No deceased seniors found in the records.
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<div class="card" style="margin-top: 2rem;">
			<div class="card-header">
				<h2>📊 Deceased Seniors Summary</h2>
				<p>Statistical overview of deceased senior citizens</p>
			</div>
			<div class="grid grid-3">
				<div class="stat">
					<h3>🏘️ Local</h3>
					<p class="number"><?= $localDeceased ?></p>
					<small><?= $totalDeceased > 0 ? round(($localDeceased / $totalDeceased) * 100, 1) : 0 ?>% of total</small>
				</div>
				<div class="stat">
					<h3>🇵🇭 National</h3>
					<p class="number"><?= $nationalDeceased ?></p>
					<small><?= $totalDeceased > 0 ? round(($nationalDeceased / $totalDeceased) * 100, 1) : 0 ?>% of total</small>
				</div>
				<div class="stat">
					<h3>✅ Benefits Received</h3>
					<p class="number"><?= $benefitsReceivedDeceased ?></p>
					<small><?= $totalDeceased > 0 ? round(($benefitsReceivedDeceased / $totalDeceased) * 100, 1) : 0 ?>% of deceased</small>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>
