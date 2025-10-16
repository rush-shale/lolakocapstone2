<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID provided, show selection interface
if (!$id) {
	$seniors = $pdo->query("SELECT * FROM seniors WHERE life_status='living' ORDER BY last_name, first_name")->fetchAll();
	?>
	<!doctype html>
	<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Generate Senior ID | SeniorCare Information System</title>
		<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
		<style>
			/* Custom styles for Generate ID page */
			.senior-info {
				display: flex;
				flex-direction: column;
				gap: 0.5rem;
			}
			
			.senior-name {
				display: flex;
				align-items: center;
				gap: 0.5rem;
				font-weight: 600;
			}
			
			.senior-name i {
				color: var(--primary);
				font-size: 0.875rem;
			}
			
			.middle-name {
				color: var(--muted);
				font-weight: 400;
			}
			
			.senior-contact {
				display: flex;
				align-items: center;
				gap: 0.5rem;
				font-size: 0.875rem;
				color: var(--muted);
			}
			
			.senior-contact i {
				color: var(--success);
				font-size: 0.75rem;
			}
			
			.age-badge {
				background: var(--primary-light);
				color: var(--primary);
				padding: 0.25rem 0.75rem;
				border-radius: 1rem;
				font-weight: 600;
				font-size: 0.875rem;
			}
			
			.barangay-info {
				display: flex;
				align-items: center;
				gap: 0.5rem;
				font-weight: 500;
			}
			
			.barangay-info i {
				color: var(--info);
				font-size: 0.875rem;
			}
			
			.action-buttons {
				display: flex;
				gap: 0.5rem;
			}
			
			.action-buttons .button {
				display: inline-flex;
				align-items: center;
				gap: 0.5rem;
				text-decoration: none;
				font-size: 0.875rem;
				padding: 0.5rem 1rem;
			}
			
			.badge i {
				margin-right: 0.25rem;
			}
			
			/* Enhanced table styling */
			.table tbody tr:hover {
				background: var(--bg-secondary);
				transform: translateY(-1px);
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}
			
			/* Search container styling */
			.search-container {
				position: relative;
				display: flex;
				align-items: center;
				background: var(--bg-secondary);
				border: 1px solid var(--border);
				border-radius: 0.5rem;
				padding: 0.5rem 1rem;
				min-width: 300px;
			}
			
			.search-icon {
				color: var(--muted);
				margin-right: 0.5rem;
			}
			
			.search-container input {
				border: none;
				background: transparent;
				outline: none;
				flex: 1;
				font-size: 0.875rem;
			}
			
			.search-container input::placeholder {
				color: var(--muted);
			}
			
			/* Animation for stats */
			.stats .stat {
				transition: all 0.3s ease;
			}
			
			.stats .stat:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
			}
		</style>
	</head>
	<body>
		<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
		
	<main class="content">
			<header class="content-header">
				<h1 class="content-title">Generate Senior ID</h1>
				<p class="content-subtitle">Select a senior citizen to generate their official ID card</p>
			</header>
			
			<div class="content-body">
				<!-- Statistics Cards -->
				<!-- Removed statistics cards as per user request -->

				<!-- Senior Selection Card -->
				<div class="card animate-fade-in">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-users"></i>
							Select Senior Citizen
						</h2>
						<div class="card-actions">
							<div class="search-container" style="max-width: 300px;">
								<input type="text" placeholder="Search seniors..." id="searchSeniors" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 12px; outline: none; font-size: 0.9rem;">
							</div>
						</div>
					</div>
					<div class="card-body">
						<?php if (!empty($seniors)): ?>
						<div class="table-container">
							<table class="table">
								<thead>
									<tr>
										<th>Senior Information</th>
										<th>Age</th>
										<th>Barangay</th>
										<th>Category</th>
										<th>Benefits Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody id="seniorsTable">
									<?php foreach ($seniors as $s): ?>
										<tr>
											<td>
												<div class="senior-info">
													<div class="senior-name">
														<i class="fas fa-user"></i>
														<strong><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></strong>
														<?php if ($s['middle_name']): ?>
															<br><small class="middle-name"><?= htmlspecialchars($s['middle_name']) ?></small>
														<?php endif; ?>
													</div>
													<?php if ($s['contact']): ?>
														<div class="senior-contact">
															<i class="fas fa-phone"></i>
															<?= htmlspecialchars($s['contact']) ?>
														</div>
													<?php endif; ?>
												</div>
											</td>
											<td>
												<span class="age-badge">
													<?= (int)$s['age'] ?> years
												</span>
											</td>
											<td>
												<div class="barangay-info">
													<i class="fas fa-map-marker-alt"></i>
													<?= htmlspecialchars($s['barangay']) ?>
												</div>
											</td>
											<td>
												<span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-warning' ?>">
													<?= $s['category'] === 'local' ? 'Local' : 'National' ?>
												</span>
											</td>
											<td>
												<span class="badge <?= $s['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
													<i class="fas fa-<?= $s['benefits_received'] ? 'check' : 'clock' ?>"></i>
													<?= $s['benefits_received'] ? 'Received' : 'Pending' ?>
												</span>
											</td>
											<td>
													<div class="action-buttons">
														<a href="<?= BASE_URL ?>/admin/senior_id.php?id=<?= (int)$s['id'] ?>" target="_blank" class="button primary">
															<i class="fas fa-id-card"></i>
															Generate ID
														</a>
													</div>
											</td>
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
							<p>No living seniors are currently registered in the system.</p>
							<a href="<?= BASE_URL ?>/admin/seniors.php" class="button primary">
								<i class="fas fa-plus"></i>
								Add Seniors
							</a>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</main>
		
		<script src="<?= BASE_URL ?>/assets/app.js"></script>
		<script>
			// Initialize functionality on page load
			document.addEventListener('DOMContentLoaded', function() {
				initializeSearch();
			});

			// Search functionality
			function initializeSearch() {
				const searchInput = document.getElementById('searchSeniors');
				const table = document.getElementById('seniorsTable');
				
				if (searchInput && table) {
					searchInput.addEventListener('input', function() {
						const searchTerm = this.value.toLowerCase();
						const rows = table.querySelectorAll('tr');
						let visibleCount = 0;
						
						rows.forEach(row => {
							const text = row.textContent.toLowerCase();
							const isVisible = text.includes(searchTerm);
							row.style.display = isVisible ? '' : 'none';
							if (isVisible) visibleCount++;
						});
						
						// Update search results indicator
						updateSearchResults(searchInput, visibleCount, rows.length);
					});
				}
			}

			function updateSearchResults(input, visible, total) {
				let indicator = input.parentNode.querySelector('.search-results');
				if (!indicator) {
					indicator = document.createElement('div');
					indicator.className = 'search-results';
					indicator.style.cssText = `
						position: absolute;
						right: 1rem;
						top: 50%;
						transform: translateY(-50%);
						font-size: var(--font-size-xs);
						color: var(--muted);
						font-weight: 600;
						background: var(--bg-secondary);
						padding: var(--space-xs) var(--space-sm);
						border-radius: var(--radius-sm);
					`;
					input.parentNode.style.position = 'relative';
					input.parentNode.appendChild(indicator);
				}
				
				if (total > visible) {
					indicator.textContent = `${visible} of ${total}`;
					indicator.style.color = 'var(--warning)';
				} else {
					indicator.textContent = '';
				}
			}
		</script>
	</body>
	</html>
	<?php
	exit;
}

// Generate ID for specific senior
$stmt = $pdo->prepare("SELECT * FROM seniors WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) {
	header('HTTP/1.1 404 Not Found');
	echo 'Senior not found';
	exit;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Senior ID | <?= h($s['last_name'].', '.$s['first_name']) ?></title>
	<style>
		body {
			margin: 0;
			background: #f8fafc;
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		}
		.wrapper {
			display: grid;
			min-height: 100vh;
			place-items: center;
			padding: 1rem;
		}
		.card {
			width: 450px;
			height: 280px;
			background: #ffffff;
			color: #1e293b;
			border-radius: 16px;
			box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
			padding: 20px 24px;
			position: relative;
			border: 1px solid #e2e8f0;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
		}
		.header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 8px;
		}
		.logo {
			width: 60px;
			height: 60px;
			background: #ddd;
			border-radius: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 12px;
			color: #666;
			user-select: none;
		}
		.title {
			flex: 1;
			text-align: center;
			font-weight: 700;
			font-size: 14px;
			line-height: 1.2;
			letter-spacing: 0.05em;
			color: #000;
		}
		.title .line1 {
			font-weight: 900;
			font-size: 16px;
			margin-bottom: 2px;
		}
		.title .line2 {
			font-weight: 700;
			font-size: 14px;
			margin-bottom: 2px;
		}
		.title .line3 {
			font-weight: 600;
			font-size: 13px;
		}
		.content {
			margin-top: 8px;
			flex: 1;
			display: flex;
			gap: 12px;
		}
		.photo {
			width: 100px;
			height: 120px;
			background: #f8fafc;
			border: 2px solid #e2e8f0;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #64748b;
			font-size: 12px;
			font-weight: 500;
			user-select: none;
		}
		.info {
			flex: 1;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			font-size: 13px;
			color: #000;
		}
		.info .field {
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-bottom: 1px solid #000;
			padding: 2px 0;
			font-weight: 700;
			letter-spacing: 0.05em;
		}
		.info .field label {
			font-weight: 600;
			font-size: 11px;
			color: #555;
			user-select: none;
		}
		.info .field .value {
			flex: 1;
			text-align: right;
			text-transform: uppercase;
		}
		.bottom-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 12px;
			font-size: 11px;
			color: #555;
			user-select: none;
		}
		.signature {
			flex: 1;
			border-bottom: 1px solid #000;
			margin-right: 12px;
			height: 20px;
		}
		.control-number {
			font-weight: 700;
			font-size: 14px;
		}
		.note {
			margin-top: 8px;
			text-align: center;
			font-size: 11px;
			color: #555;
			font-style: italic;
			user-select: none;
		}
	</style>
</head>
<body>
	<div class="wrapper">
		<div class="card" id="senior-id-card">
			<div class="header">
				<div class="logo" title="Logo 1">Logo 1</div>
				<div class="title">
					<div class="line1">Republic of the Philippines</div>
					<div class="line2">Office of the Senior Citizens Affairs (OSCA)</div>
					<div class="line3">Municipality of Manolo Fortich Bukidnon</div>
				</div>
				<div class="logo" title="Logo 2">Logo 2</div>
			</div>
			<div class="content">
				<div class="info">
					<div class="field">
						<label>Name:</label>
						<div class="value"><?= h(strtoupper($s['last_name'] . ', ' . $s['first_name'] . ($s['middle_name'] ? ' ' . $s['middle_name'] : ''))) ?></div>
					</div>
					<div class="field">
						<label>Address:</label>
						<div class="value"><?= h(strtoupper($s['barangay'])) ?></div>
					</div>
					<div class="field">
						<label>&nbsp;</label>
						<div class="value"><?= h('Manolo Fortich, Bukidnon') ?></div>
					</div>
					<div class="field" style="margin-top: 12px;">
						<label>Date of Birth</label>
						<label>Sex</label>
						<label>Date Issued</label>
					</div>
					<div class="field" style="font-weight: 700;">
						<div class="value"><?= h(date('m-d-Y', strtotime($s['birthdate'] ?? ''))) ?></div>
						<div class="value"><?= h(strtoupper($s['sex'] ?? '')) ?></div>
						<div class="value"><?= h(date('m-d-Y')) ?></div>
					</div>
				</div>
				<div class="photo">📷<br>Photo</div>
			</div>
			<div class="bottom-row">
				<div class="signature" title="Signature / Thumbmark"></div>
				<div class="control-number">Control No: 19349</div>
			</div>
			<div class="note">This Card is Non-Transferable</div>
		</div>
		<div class="controls">
			<button class="back-btn" onclick="window.close()">← Back</button>
			<button onclick="window.print()">🖨️ Print / Save PDF</button>
		</div>
	</div>
</body>
</html>


