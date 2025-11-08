<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

// Seniors in my barangay (all life statuses) - matching admin columns
$seniorsStmt = $pdo->prepare("SELECT id, first_name, middle_name, last_name, ext_name, age, life_status, benefits_received, barangay, cellphone, sex, civil_status, date_of_birth, osca_id_no, remarks, health_condition, purok, place_of_birth, category, validation_status, validation_date FROM seniors WHERE barangay=? ORDER BY last_name, first_name");
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
					<div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 1rem;">
						<div>
							<h2 class="card-title">
								<i class="fas fa-users"></i>
								All Seniors
							</h2>
							<p class="card-subtitle">Complete list of senior citizens in your barangay</p>
						</div>
						<div class="table-search" style="flex: 1; min-width: 250px; max-width: 400px;">
							<span class="table-search-icon"><i class="fas fa-search"></i></span>
							<input type="text" id="searchInput" placeholder="Search seniors...">
						</div>
					</div>
					<div class="card-body">
						<div class="table-container table-scroll seniors-table-scroll">
							<table class="modern-table">
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
								<tbody id="seniorsTableBody">
									<?php if (!empty($seniors)): ?>
										<?php foreach ($seniors as $s): ?>
										<tr>
											<td><?= htmlspecialchars($s['last_name']) ?></td>
											<td><?= htmlspecialchars($s['first_name']) ?></td>
											<td><?= htmlspecialchars($s['middle_name'] ?: '') ?></td>
											<td><?= htmlspecialchars($s['ext_name'] ?: '') ?></td>
											<td><?= htmlspecialchars($s['barangay']) ?></td>
											<td><?= (int)$s['age'] ?></td>
											<td><?= htmlspecialchars($s['sex'] ?: '-') ?></td>
											<td><?= htmlspecialchars($s['civil_status'] ?: '-') ?></td>
											<td><?= $s['date_of_birth'] ? date('M d, Y', strtotime($s['date_of_birth'])) : '-' ?></td>
											<td><?= htmlspecialchars($s['osca_id_no'] ?: '-') ?></td>
											<td><?= htmlspecialchars($s['remarks'] ?: '-') ?></td>
											<td><?= htmlspecialchars($s['health_condition'] ?: '-') ?></td>
											<td><?= htmlspecialchars($s['purok'] ?: '-') ?></td>
											<td><?= htmlspecialchars($s['place_of_birth'] ?: '-') ?></td>
											<td><?= htmlspecialchars($s['cellphone'] ?: '-') ?></td>
											<td>
												<span class="badge <?= $s['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>">
													<?= ucfirst($s['life_status']) ?>
												</span>
											</td>
											<td>
												<span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : ($s['category'] === 'national' ? 'badge-warning' : 'badge-secondary') ?>">
													<?= ucfirst($s['category'] ?: '-') ?>
												</span>
											</td>
											<td><?= htmlspecialchars($s['validation_status'] ?: '-') ?></td>
											<td><?= $s['validation_date'] ? date('M d, Y', strtotime($s['validation_date'])) : '-' ?></td>
										</tr>
										<?php endforeach; ?>
									<?php else: ?>
										<tr id="emptyStateRow">
											<td colspan="19">
												<div class="empty-state">
													<div class="empty-icon">
														<i class="fas fa-users"></i>
													</div>
													<h3>No Seniors Found</h3>
													<p>No senior citizens are currently registered in your barangay.</p>
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
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Search filter for seniors table
		document.addEventListener('DOMContentLoaded', function() {
			const searchInput = document.getElementById('searchInput');
			if (searchInput) {
				searchInput.addEventListener('input', function() {
					const filter = this.value.toLowerCase();
					const rows = document.querySelectorAll('#seniorsTableBody tr');
					
					rows.forEach(row => {
						if (row.id === 'emptyStateRow') {
							row.style.display = filter === '' ? '' : 'none';
							return;
						}
						
						// Get text content from all cells
						const cells = row.querySelectorAll('td');
						let textContent = '';
						cells.forEach(cell => {
							textContent += cell.textContent.toLowerCase() + ' ';
						});
						
						// Check if any cell content matches the filter
						if (textContent.includes(filter)) {
							row.style.display = '';
						} else {
							row.style.display = 'none';
						}
					});
					
					// Show/hide empty state
					const visibleRows = Array.from(rows).filter(row => 
						row.style.display !== 'none' && row.id !== 'emptyStateRow'
					);
					
					// Find empty state row
					const emptyStateRow = document.getElementById('emptyStateRow');
					
					if (emptyStateRow) {
						if (visibleRows.length === 0 && filter !== '') {
							emptyStateRow.style.display = '';
							const h3 = emptyStateRow.querySelector('.empty-state h3');
							const p = emptyStateRow.querySelector('.empty-state p');
							if (h3) h3.textContent = 'No Results Found';
							if (p) p.textContent = 'No seniors match your search criteria.';
						} else if (visibleRows.length === 0 && filter === '') {
							emptyStateRow.style.display = '';
							const h3 = emptyStateRow.querySelector('.empty-state h3');
							const p = emptyStateRow.querySelector('.empty-state p');
							if (h3) h3.textContent = 'No Seniors Found';
							if (p) p.textContent = 'No senior citizens are currently registered in your barangay.';
						} else {
							emptyStateRow.style.display = 'none';
						}
					}
				});
			}
		});
	</script>
</body>
</html>
