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

// This page is a landing page for Inactive Seniors category
// You can add specific content or logic here for Inactive Seniors

$selected_barangay = $_GET['barangay'] ?? '';

// Fetch seniors with 0 attended events, filtered by barangay if selected
if ($selected_barangay && $selected_barangay !== 'all') {
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.age,
            s.sex,
            COUNT(a.id) AS attended_events
        FROM seniors s
        LEFT JOIN attendance a ON s.id = a.senior_id
        LEFT JOIN events e ON a.event_id = e.id
        WHERE s.life_status = 'living' AND s.barangay = ?
        GROUP BY s.id, s.first_name, s.last_name, s.age, s.sex
        HAVING attended_events = 0
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$selected_barangay]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.age,
            s.sex,
            COUNT(a.id) AS attended_events
        FROM seniors s
        LEFT JOIN attendance a ON s.id = a.senior_id
        LEFT JOIN events e ON a.event_id = e.id
        WHERE s.life_status = 'living'
        GROUP BY s.id, s.first_name, s.last_name, s.age, s.sex
        HAVING attended_events = 0
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
}
$seniors = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Inactive Seniors | SeniorCare Information System</title>
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
			<h1 class="content-title">Inactive Seniors</h1>
			<p class="content-subtitle">Manage inactive senior citizen records</p>
		</header>

		<div class="content-body">
			<!-- Add filter by barangay -->
			<form method="GET" action="inactive_seniors.php" class="filter-form">
				<label for="barangay">Filter by Barangay:</label>
				<select name="barangay" id="barangay" onchange="this.form.submit()">
					<option value="all" <?= (!isset($_GET['barangay']) || $_GET['barangay'] === 'all') ? 'selected' : '' ?>>All</option>
					<?php foreach ($barangays as $barangay): ?>
						<option value="<?= htmlspecialchars($barangay['name']) ?>" <?= (isset($_GET['barangay']) && $_GET['barangay'] === $barangay['name']) ? 'selected' : '' ?>>
							<?= htmlspecialchars($barangay['name']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>

			<!-- Example table listing inactive seniors -->
			<div class="main-content-area">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">Inactive Seniors List</h2>
					</div>
					<div class="card-body">
						<table class="table">
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Age</th>
									<th>Gender</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($seniors)): ?>
									<?php foreach ($seniors as $senior): ?>
										<tr>
											<td><?= htmlspecialchars($senior['id']) ?></td>
											<td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
											<td><?= htmlspecialchars($senior['age']) ?></td>
											<td><?= htmlspecialchars($senior['sex'] ?? '') ?></td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="4" style="text-align: center;">No inactive seniors found.</td>
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
