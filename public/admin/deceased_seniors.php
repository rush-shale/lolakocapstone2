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

// This page is a landing page for Deceased Seniors category
// You can add specific content or logic here for Deceased Seniors

// Example: Fetch all seniors with life_status 'deceased'
$stmt = $pdo->prepare('SELECT * FROM seniors WHERE life_status = ? ORDER BY created_at DESC');
$stmt->execute(['deceased']);
$seniors = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Deceased Seniors | SeniorCare Information System</title>
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
			<h1 class="content-title">Deceased Seniors</h1>
			<p class="content-subtitle">Manage deceased senior citizen records</p>
		</header>

		<div class="content-body">
			<!-- You can add navigation or filters here -->

			<!-- Example table listing deceased seniors -->
			<div class="main-content-area">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">Deceased Seniors List</h2>
					</div>
					<div class="card-body">
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
							<?php if (!empty($seniors)): ?>
                                <?php foreach ($seniors as $senior): ?>
                                    <tr class="clickable-row" onclick="window.location.href='death_details.php?id=<?= (int)$senior['id'] ?>'" style="cursor:pointer;">
                                        <td><?= htmlspecialchars(ucfirst(strtolower($senior['last_name']))) ?></td>
                                        <td><?= htmlspecialchars(ucfirst(strtolower($senior['first_name']))) ?></td>
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
                                        <td><span class="badge badge-danger">Deceased</span></td>
										<td>
											<span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>"><?= ucfirst($senior['category']) ?></span>
										</td>
										<td>
											<span class="badge <?= ($senior['validation_status'] ?? '') === 'Validated' ? 'badge-success' : 'badge-warning' ?>">
												<?= $senior['validation_status'] ?? 'Not Validated' ?>
											</span>
										</td>
										<td><?= isset($senior['validation_date']) && $senior['validation_date'] ? date('M d, Y H:i', strtotime($senior['validation_date'])) : '-' ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a class="button small" href="death_details.php?id=<?= (int)$senior['id'] ?>" title="View Death Info" onclick="event.stopPropagation();">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
									</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr>
									<td colspan="20" style="text-align: center;">No deceased seniors found.</td>
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
