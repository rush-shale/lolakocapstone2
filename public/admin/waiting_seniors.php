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
		if ($op === 'validate_waiting') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				try {
					$pdo = get_db_connection();
					$stmt = $pdo->prepare('UPDATE seniors SET category = ?, validation_status = ?, validation_date = NOW() WHERE id = ?');
					$stmt->execute(['local', 'Validated', $id]);
					$message = 'Senior validated successfully';
				} catch (Exception $e) {
					error_log("Validation failed: " . $e->getMessage());
					$message = 'Error validating senior: ' . $e->getMessage();
				}
			}
		}
	}
}

$csrf = generate_csrf_token();

// Load only waiting seniors
try {
	$pdo = get_db_connection();
	$sql = 'SELECT *, validation_status, validation_date, 0 as event_count, "" as events_attended
		FROM seniors WHERE life_status = "living" AND category = "waiting" ORDER BY created_at DESC';
	$stmtAll = $pdo->prepare($sql);
	$stmtAll->execute();
	$seniors = $stmtAll->fetchAll();
} catch (Exception $e) {
	error_log("Failed to load waiting seniors: " . $e->getMessage());
	$seniors = [];
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Waiting Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Waiting Seniors</h1>
			<p class="content-subtitle">Seniors pending validation</p>
		</header>

		<div class="content-body">
			<div class="main-content-area">
				<?php if ($message): ?>
				<div class="alert alert-success">
					<div class="alert-icon"><i class="fas fa-check-circle"></i></div>
					<div class="alert-content">
						<strong>Success</strong>
						<p><?= htmlspecialchars($message) ?></p>
					</div>
				</div>
				<?php endif; ?>

				<div class="card" style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: none; overflow: hidden;">
					<div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
						<div>
							<h2 class="card-title">Waiting Seniors</h2>
							<p class="card-subtitle">Pending validation</p>
						</div>
					</div>
					<div class="card-body">
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
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($seniors)): ?>
										<?php foreach ($seniors as $senior): ?>
										<tr>
											<td><?= htmlspecialchars($senior['last_name']) ?></td>
											<td><?= htmlspecialchars($senior['first_name']) ?></td>
											<td><?= htmlspecialchars($senior['middle_name'] ?: '') ?></td>
											<td><?= isset($senior['ext_name']) ? htmlspecialchars($senior['ext_name']) : '' ?></td>
											<td><?= htmlspecialchars($senior['barangay']) ?></td>
											<td><?= (int)$senior['age'] ?></td>
											<td>
												<?php
												switch ($senior['sex']) {
													case 'male': echo 'Male'; break;
													case 'female': echo 'Female'; break;
													case 'lgbtq': echo 'LGBTQ+'; break;
													default: echo 'Not specified';
												}
												?>
											</td>
											<td><?= htmlspecialchars($senior['civil_status'] ?: '') ?></td>
											<td><?= $senior['date_of_birth'] ? date('M d, Y', strtotime($senior['date_of_birth'])) : '' ?></td>
											<td><?= htmlspecialchars($senior['osca_id_no'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['remarks'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['health_condition'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['purok'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['place_of_birth'] ?: '') ?></td>
											<td><?= htmlspecialchars($senior['cellphone'] ?? '') ?></td>
											<td>
												<span class="badge <?= $senior['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($senior['life_status']) ?></span>
											</td>
											<td><span class="badge badge-info">Waiting</span></td>
                                            <td>
                                                <span class="badge badge-warning">Not Validated</span>
                                                <form method="post" style="display:inline; margin-left: 6px;">
                                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                                    <input type="hidden" name="op" value="validate_waiting">
                                                    <input type="hidden" name="id" value="<?= (int)$senior['id'] ?>">
                                                    <button type="submit" class="button small primary" style="vertical-align: middle;">
                                                        <i class="fas fa-check"></i> Validate
                                                    </button>
                                                </form>
                                            </td>
                                            <td>-</td>
										</tr>
										<?php endforeach; ?>
									<?php else: ?>
									<tr>
										<td colspan="20" style="text-align: center; padding: 2rem; color: var(--muted);">No waiting seniors found.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


