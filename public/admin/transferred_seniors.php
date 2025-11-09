<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

// Get all transferred seniors (client-side filtering will handle search)
$whereClause = "WHERE (s.category = 'transferred' OR st_any.id IS NOT NULL)";
$params = [];

// Debug: Check all seniors with transferred category
$debugStmt = $pdo->prepare("SELECT id, first_name, last_name, category FROM seniors WHERE category = 'transferred'");
$debugStmt->execute();
$debugResults = $debugStmt->fetchAll();
error_log("All seniors with transferred category: " . json_encode($debugResults));

// Get transferred seniors with their transfer details
$stmt = $pdo->prepare("
    SELECT s.*, 
           COALESCE(st.transfer_reason, 'Not specified') as transfer_reason, 
           COALESCE(st.new_address, s.barangay) as new_address, 
           COALESCE(st.effective_date, s.created_at) as effective_date, 
           st.created_at as transfer_date
    FROM seniors s 
    LEFT JOIN (
        SELECT t.*
        FROM senior_transfers t
        INNER JOIN (
            SELECT senior_id, MAX(id) AS max_id
            FROM senior_transfers
            GROUP BY senior_id
        ) m ON t.id = m.max_id
    ) st ON s.id = st.senior_id
    LEFT JOIN senior_transfers st_any ON st_any.senior_id = s.id
    $whereClause 
    ORDER BY s.last_name, s.first_name
");
$stmt->execute($params);
$transferredSeniors = $stmt->fetchAll();

// Debug: Log the query and results
error_log("Transferred seniors query: SELECT s.*, COALESCE(st.transfer_reason, 'Not specified') as transfer_reason, COALESCE(st.new_address, s.barangay) as new_address, COALESCE(st.effective_date, s.created_at) as effective_date, st.created_at as transfer_date FROM seniors s LEFT JOIN senior_transfers st ON s.id = st.senior_id $whereClause ORDER BY s.last_name, s.first_name");
error_log("Transferred seniors count: " . count($transferredSeniors));
if (count($transferredSeniors) > 0) {
    error_log("First transferred senior: " . json_encode($transferredSeniors[0]));
}

// Get statistics
$totalTransferred = count($transferredSeniors);
$transferredThisMonth = array_filter($transferredSeniors, function($senior) {
    return $senior['transfer_date'] && date('Y-m', strtotime($senior['transfer_date'])) === date('Y-m');
});
$transferredThisMonthCount = count($transferredThisMonth);
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
		/* Search container */
		.search-container {
			display: flex;
			align-items: center;
			background: white;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			padding: 0.5rem 0.75rem;
			min-width: 250px;
		}
		
		.search-container input {
			border: none;
			outline: none;
			background: transparent;
			flex: 1;
			font-size: 0.875rem;
		}
		
		.search-icon {
			color: #6b7280;
			margin-right: 0.5rem;
		}
		
		.table-search {
			display: flex;
			align-items: center;
			background: white;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			padding: 0.5rem 0.75rem;
			min-width: 250px;
		}
		
		.table-search-icon {
			margin-right: 0.5rem;
			color: #6b7280;
		}
		
		.table-search input {
			border: none;
			outline: none;
			background: transparent;
			flex: 1;
			font-size: 0.875rem;
		}
		
		.clickable-row {
			cursor: pointer;
		}
		
		.clickable-row:hover {
			background: var(--bg-secondary);
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Transferred Seniors</h1>
			<p class="content-subtitle">Manage seniors who have been transferred to other locations</p>
		</header>

		<?php if (isset($_GET['transfer_success'])): ?>
		<div class="alert alert-success" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #d1fae5; color: #065f46; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem;">
			<i class="fas fa-check-circle"></i>
			Senior has been successfully transferred!
		</div>
		<?php endif; ?>

		<div class="content-body">
			<div class="main-content-area">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">Transferred Seniors List</h2>
						<div class="card-actions">
							<div class="table-search">
								<span class="table-search-icon">🔍</span>
								<input type="text" id="searchInput" placeholder="Search seniors...">
							</div>
						</div>
					</div>
					<div class="card-body">
						<table class="table">
							<thead>
								<tr>
									<th>Name</th>
									<th>Age</th>
									<th>Old Address</th>
									<th>New Address</th>
									<th>Transfer Date</th>
									<th>Transfer Reason</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody id="transferredSeniorsTable">
								<?php if (!empty($transferredSeniors)): ?>
									<?php foreach ($transferredSeniors as $senior): ?>
										<tr class="clickable-row" onclick="window.location.href='senior_details.php?id=<?= (int)$senior['id'] ?>&noedit=1'" style="cursor:pointer;">
											<td>
												<div class="senior-info">
													<strong><?= htmlspecialchars(ucfirst(strtolower($senior['last_name'] . ', ' . $senior['first_name']))) ?></strong>
													<?php if ($senior['middle_name']): ?>
														<br><small style="color: #6b7280;"><?= htmlspecialchars($senior['middle_name']) ?></small>
													<?php endif; ?>
													<?php if ($senior['osca_id_no']): ?>
														<br><small style="color: #6b7280; font-size: 0.75rem;">OSCA ID: <?= htmlspecialchars($senior['osca_id_no']) ?></small>
													<?php endif; ?>
												</div>
											</td>
											<td><?= (int)$senior['age'] ?></td>
											<td><?= htmlspecialchars($senior['barangay'] ?? 'N/A') ?></td>
											<td><?= htmlspecialchars($senior['new_address']) ?></td>
											<td>
												<?php if ($senior['effective_date'] && $senior['effective_date'] !== '0000-00-00'): ?>
													<?= date('M d, Y', strtotime($senior['effective_date'])) ?>
												<?php else: ?>
													<span style="color: #6b7280;">Not specified</span>
												<?php endif; ?>
											</td>
											<td>
												<?php if ($senior['transfer_reason'] && $senior['transfer_reason'] !== 'Not specified'): ?>
													<?= htmlspecialchars($senior['transfer_reason']) ?>
												<?php else: ?>
													<span style="color: #6b7280;">Not specified</span>
												<?php endif; ?>
											</td>
											<td>
												<div class="action-buttons">
													<a class="button small" href="senior_details.php?id=<?= (int)$senior['id'] ?>&noedit=1" title="View Details" onclick="event.stopPropagation();">
														<i class="fas fa-eye"></i>
													</a>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="7" style="text-align: center;">No transferred seniors found.</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</main>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Search filter for transferred seniors table (matching All Seniors functionality)
		document.getElementById('searchInput').addEventListener('input', function() {
			const filter = this.value.toLowerCase();
			const rows = document.querySelectorAll('#transferredSeniorsTable tr');
			
			rows.forEach(row => {
				const text = row.textContent.toLowerCase();
				const isVisible = text.includes(filter);
				row.style.display = isVisible ? '' : 'none';
			});
		});
	</script>
</body>
</html>
