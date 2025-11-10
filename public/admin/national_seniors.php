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

// This page is a landing page for National Seniors category
// You can add specific content or logic here for National Seniors

// Example: Fetch all seniors with category 'national'
$stmt = $pdo->prepare("
    SELECT * 
    FROM seniors 
    WHERE LOWER(category) = ? 
    ORDER BY GREATEST(COALESCE(validation_date, '1970-01-01 00:00:00'), created_at) DESC
");
$stmt->execute(['national']);
$seniors = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>National Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Add any specific styles for this page here */
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">National Seniors</h1>
			<p class="content-subtitle">Manage national category senior citizen records</p>
		</header>
		
		<div class="content-body">
			<!-- You can add navigation or filters here -->

			<!-- Example table listing national seniors -->
			<div class="main-content-area">
				<div class="card">
					<div class="card-header" style="display:flex; justify-content: space-between; align-items:center;">
						<h2 class="card-title">National Seniors List</h2>
						<div>
							<button class="button secondary" onclick="handleCancel()">Close</button>
						</div>
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
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="5" style="text-align: center;">No national seniors found.</td>
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
		function handleCancel(){
			if (window.history.length > 1) {
				window.history.back();
			} else {
				window.location.href = '<?= BASE_URL ?>/admin/dashboard.php';
			}
		}

		window.addEventListener('storage', function(event) {
			if (event.key === 'senior-category-updated') {
				window.location.reload();
			}
		});
	</script>
</body>
</html>
