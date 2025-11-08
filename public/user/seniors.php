<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

// Seniors in my barangay (all life statuses)
$seniorsStmt = $pdo->prepare("SELECT id, first_name, middle_name, last_name, ext_name, age, life_status, benefits_received, barangay, cellphone FROM seniors WHERE barangay=? ORDER BY last_name, first_name");
$seniorsStmt->execute([$user['barangay']]);
$seniors = $seniorsStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Barangay Seniors | SeniorCare Information System</title>
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Table scroll styling for user seniors */
		.seniors-table-scroll {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			max-width: 100%;
		}
		
		.seniors-table-scroll table {
			width: max-content;
			min-width: 100%;
			white-space: nowrap;
		}
		
		.seniors-table-scroll table th,
		.seniors-table-scroll table td {
			min-width: 120px;
			white-space: nowrap;
		}
		
		@media (max-width: 480px) {
			.seniors-table-scroll table th,
			.seniors-table-scroll table td {
				min-width: 100px;
				font-size: 0.875rem;
			}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">My Barangay Seniors</h1>
			<p class="content-subtitle">View senior citizens in your barangay (<?= htmlspecialchars($user['barangay']) ?>)</p>
		</header>
		
		<div class="content-body">
			<div class="grid">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-users"></i>
							All Seniors
						</h2>
						<p class="card-subtitle">Complete list of senior citizens in your barangay</p>
					</div>
					<div class="card-body">
						<?php if (!empty($seniors)): ?>
						<div class="table-container table-scroll seniors-table-scroll">
							<table class="modern-table">
								<thead>
									<tr>
										<th>Last Name</th>
										<th>First Name</th>
										<th>Middle Name</th>
										<th>Extension</th>
										<th>Age</th>
										<th>Life Status</th>
										<th>Benefits Status</th>
										<th>Cellphone</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($seniors as $s): ?>
									<tr>
										<td><?= htmlspecialchars($s['last_name']) ?></td>
										<td><?= htmlspecialchars($s['first_name']) ?></td>
										<td><?= htmlspecialchars($s['middle_name'] ?: '') ?></td>
										<td><?= htmlspecialchars($s['ext_name'] ?: '') ?></td>
										<td><?= (int)$s['age'] ?> years</td>
										<td>
											<span class="badge <?= $s['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>">
												<?= ucfirst($s['life_status']) ?>
											</span>
										</td>
										<td>
											<span class="badge <?= $s['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
												<?= $s['benefits_received'] ? 'Received' : 'Not Yet' ?>
											</span>
										</td>
										<td><?= htmlspecialchars($s['cellphone'] ?: '-') ?></td>
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
							<h3>No Seniors Found</h3>
							<p>No senior citizens are currently registered in your barangay.</p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>
