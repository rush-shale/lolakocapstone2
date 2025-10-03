<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

$csrf = generate_csrf_token();
$barangays = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll();

// This page is a landing page for Transferred Seniors category
// You can add specific content or logic here for Transferred Seniors

// Example: Fetch all seniors with status 'transferred'
$stmt = $pdo->prepare('SELECT * FROM seniors WHERE status = ? ORDER BY created_at DESC');
$stmt->execute(['transferred']);
$seniors = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Transferred Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Add any specific styles for this page here */
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Transferred Seniors</h1>
			<p class="content-subtitle">Manage transferred senior citizen records</p>
		</header>

		<div class="content-body">
			<!-- You can add navigation or filters here -->

			<!-- Example table listing transferred seniors -->
			<div class="main-content-area">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">Transferred Seniors List</h2>
					</div>
					<div class="card-body">
						<table class="table">
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Age</th>
									<th>Barangay</th>
									<th>Validation Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($seniors)): ?>
									<?php foreach ($seniors as $senior): ?>
										<tr>
											<td><?= htmlspecialchars($senior['id']) ?></td>
											<td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
											<td><?= htmlspecialchars($senior['age']) ?></td>
											<td><?= htmlspecialchars($senior['barangay']) ?></td>
											<td><?= htmlspecialchars($senior['validation_status']) ?></td>
											<td>
												<!-- Add action buttons as needed -->
												<button class="button secondary small" onclick="editSenior(<?= $senior['id'] ?>)">Edit</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="6" style="text-align: center;">No transferred seniors found.</td>
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
		function editSenior(id) {
			// Implement edit functionality or open modal
			alert('Edit senior with ID: ' + id);
		}
	</script>
</body>
</html>
