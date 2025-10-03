<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

// Fetch all local seniors who are living
$localSeniorsList = $pdo->query("
    SELECT s.*, s.barangay as barangay_name 
    FROM seniors s 
    WHERE s.category = 'local' AND s.life_status = 'living'
    ORDER BY s.created_at DESC
")->fetchAll();

$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Local Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css" />
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<div class="page-header animate-fade-in">
			<h1>Local Seniors</h1>
			<p>List of Local Seniors in the system</p>
			<a href="<?= BASE_URL ?>/admin/dashboard.php" class="button small">Back to Dashboard</a>
			<button onclick="window.history.back()" class="button small" style="margin-left: 1rem;">Close</button>
		</div>
		<div class="card animate-fade-in" style="margin-top: var(--space-lg);">
			<div class="card-body">
				<div class="table-responsive">
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Age</th>
								<th>Barangay</th>
								<th>Benefits</th>
								<th>Contact</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($localSeniorsList as $senior): ?>
							<tr>
								<td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
								<td><?= $senior['age'] ?></td>
								<td><?= htmlspecialchars($senior['barangay_name'] ?? 'N/A') ?></td>
								<td>
									<span class="badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
										<?= $senior['benefits_received'] ? 'Received' : 'Pending' ?>
									</span>
								</td>
								<td><?= htmlspecialchars($senior['contact'] ?? 'N/A') ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</main>
</body>
</html>
