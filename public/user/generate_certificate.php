<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If certificate generation requested
if ($id) {
	$stmt = $pdo->prepare("SELECT * FROM seniors WHERE id=? AND barangay=? LIMIT 1");
	$stmt->execute([$id, $user['barangay']]);
	$senior = $stmt->fetch();
	if (!$senior) {
		header('HTTP/1.1 404 Not Found');
		echo 'Senior not found';
		exit;
	}
	
	// Generate certificate
	$fullName = trim($senior['first_name'] . ' ' . ($senior['middle_name'] ? $senior['middle_name'] . ' ' : '') . $senior['last_name'] . ($senior['ext_name'] ? ' ' . $senior['ext_name'] : ''));
	?>
	<!doctype html>
	<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Certificate - <?= htmlspecialchars($fullName) ?></title>
		<style>
			* {
				margin: 0;
				padding: 0;
				box-sizing: border-box;
			}
			
			@page { 
				size: A4 landscape;
				margin: 0;
			}
			
			html, body { 
				margin: 0; 
				padding: 0; 
				font-family: 'Times New Roman', serif;
				background: #f0f0f0;
			}
			
			body {
				display: flex;
				justify-content: center;
				align-items: center;
				min-height: 100vh;
				padding: 20px;
			}
			
			.certificate {
				width: 297mm;
				height: 210mm;
				background: white;
				position: relative;
				overflow: hidden;
				margin: 0 auto;
			}
			
			/* Decorative triangles - top left */
			.triangle-top-left {
				position: absolute;
				top: 0;
				left: 0;
				width: 0;
				height: 0;
				border-left: 180px solid #2d1b4e;
				border-bottom: 180px solid transparent;
				z-index: 1;
			}
			
			.triangle-top-left-inner {
				position: absolute;
				top: 0;
				left: 0;
				width: 0;
				height: 0;
				border-left: 140px solid #a8d5e2;
				border-bottom: 140px solid transparent;
				z-index: 2;
			}
			
			/* Decorative triangles - bottom right */
			.triangle-bottom-right {
				position: absolute;
				bottom: 0;
				right: 0;
				width: 0;
				height: 0;
				border-right: 180px solid #2d1b4e;
				border-top: 180px solid transparent;
				z-index: 1;
			}
			
			.triangle-bottom-right-inner {
				position: absolute;
				bottom: 0;
				right: 0;
				width: 0;
				height: 0;
				border-right: 140px solid #a8d5e2;
				border-top: 140px solid transparent;
				z-index: 2;
			}
			
			/* Main border */
			.certificate-border {
				position: absolute;
				top: 50px;
				left: 50px;
				right: 50px;
				bottom: 50px;
				border: 3px solid black;
				z-index: 3;
			}
			
			/* Logo/Seal */
			.logo-seal {
				position: absolute;
				top: 80px;
				right: 100px;
				width: 160px;
				height: 160px;
				z-index: 10;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.logo-seal img {
				width: 100%;
				height: 100%;
				object-fit: contain;
			}
			
			/* Certificate Content */
			.certificate-content {
				position: relative;
				z-index: 10;
				padding: 70px;
				text-align: center;
			}
			
			.certificate-title {
				font-size: 64px;
				font-weight: bold;
				letter-spacing: 8px;
				margin-bottom: 10px;
				margin-top: 40px;
				color: #000000;
			}
			
			.certificate-subtitle {
				font-size: 20px;
				letter-spacing: 6px;
				margin-bottom: 60px;
				font-weight: normal;
				color: #000000;
				text-transform: uppercase;
			}
			
			.award-statement {
				font-size: 16px;
				margin-bottom: 40px;
				color: #000000;
			}
			
			.recipient-name-container {
				margin: 0 auto 30px;
				text-align: center;
			}
			
			.recipient-name {
				font-size: 18px;
				font-weight: bold;
				color: #000000;
				margin-bottom: 10px;
				display: block;
				text-transform: uppercase;
			}
			
			.name-line {
				width: 500px;
				height: 2px;
				background: black;
				margin: 0 auto;
			}
			
			.certificate-body {
				font-size: 15px;
				line-height: 1.8;
				max-width: 600px;
				margin: 0 auto 30px;
				text-align: center;
				color: #000000;
			}
			
			.certificate-body p {
				margin: 0 0 5px 0;
			}
			
			.certificate-body strong {
				font-weight: bold;
			}
			
			/* Signatures */
			.signatures {
				display: flex;
				justify-content: space-between;
				max-width: 600px;
				margin: 0 auto;
				padding-top: 0;
			}
			
			.signature-block {
				text-align: center;
			}
			
			.signature-name {
				font-weight: bold;
				font-size: 16px;
				margin-bottom: 5px;
				border-bottom: 2px solid black;
				padding-bottom: 5px;
				display: inline-block;
				min-width: 200px;
				color: #000000;
			}
			
			.signature-title {
				font-size: 14px;
				color: #000000;
			}
			
			@media print {
				.noprint { display: none; }
				body { 
					background: white; 
					padding: 0;
				}
				.certificate {
					margin: 0;
					box-shadow: none;
				}
			}
		</style>
	</head>
	<body>
		<div class="noprint" style="margin: 10px; text-align: center;">
			<button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #1e3a8a; color: white; border: none; border-radius: 4px; cursor: pointer;">
				🖨️ Print Certificate
			</button>
			<a href="<?= BASE_URL ?>/user/generate_certificate.php" style="display: inline-block; margin-left: 10px; padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">
				← Back to Certificate Generation
			</a>
		</div>
		<div class="certificate">
			<!-- Decorative Triangles -->
			<div class="triangle-top-left"></div>
			<div class="triangle-top-left-inner"></div>
			<div class="triangle-bottom-right"></div>
			<div class="triangle-bottom-right-inner"></div>
			
			<!-- Main Border -->
			<div class="certificate-border"></div>
			
			<!-- Logo/Seal -->
			<div class="logo-seal">
				<img src="<?= BASE_URL ?>/images/OSCA MAIN LOGO.png" alt="OSCA Main Logo">
			</div>
			
			<!-- Certificate Content -->
			<div class="certificate-content">
				<h1 class="certificate-title">CERTIFICATE</h1>
				<div class="certificate-subtitle">OF BEST ACTIVE SENIOR CITIZEN</div>
				
				<div class="award-statement">THIS CERTIFICATE IS AWARDED TO</div>
				
				<div class="recipient-name-container">
					<span class="recipient-name"><?= htmlspecialchars(strtoupper($fullName)) ?></span>
					<div class="name-line"></div>
				</div>
				
				<div class="certificate-body">
					<p>This certificate is proudly presented to <strong><?= htmlspecialchars($fullName) ?></strong></p>
					<p>In recognition of being the "Best in Active Senior Citizen,"</p>
					<p>for your outstanding participation, dedication, and positive contributions</p>
					<p>to community programs and activities.</p>
					<p>Your enthusiasm, commitment, and active involvement serve as an</p>
					<p>inspiration to your peers.</p>
					<p>This award honors your remarkable efforts and lasting impact on the</p>
					<p>senior community.</p>
				</div>
				
				<div class="signatures">
					<div class="signature-block">
						<div class="signature-name">PHOEBE O. CAU</div>
						<div class="signature-title">OSCA Head</div>
					</div>
					<div class="signature-block">
						<div class="signature-name">ROGELIO N. QUIÑO</div>
						<div class="signature-title">Municipal Mayor</div>
					</div>
				</div>
			</div>
		</div>
	</body>
	</html>
	<?php
	exit;
}

// Get seniors from user's barangay
$seniors = $pdo->prepare("SELECT * FROM seniors WHERE barangay=? AND life_status='living' ORDER BY last_name, first_name");
$seniors->execute([$user['barangay']]);
$seniorsList = $seniors->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Generate Certificate | SeniorCare Information System</title>
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		.table td, .table th { 
			vertical-align: middle; 
		}
		.generate-btn {
			padding: 0.5rem 1rem;
			font-size: 0.875rem;
			background: #1e3a8a;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			transition: background 0.2s;
		}
		.generate-btn:hover {
			background: #1e40af;
		}
		.generate-btn i {
			font-size: 0.875rem;
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Generate Certificate</h1>
			<p class="content-subtitle">Generate certificate of recognition for active senior citizens</p>
		</header>
		<div class="content-body">
			<div class="card">
				<div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 1rem;">
					<h2 class="card-title"><i class="fas fa-certificate"></i> Senior Selection</h2>
					<div class="table-search" style="flex: 1; min-width: 250px; max-width: 400px;">
						<span class="table-search-icon"><i class="fas fa-search"></i></span>
						<input type="text" id="searchInput" placeholder="Search seniors...">
					</div>
				</div>
				<div class="card-body">
					<div class="table-container">
						<table class="table" id="seniorsTable">
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
						<tbody id="seniorsTableBody">
								<?php if (!empty($seniorsList)): ?>
									<?php foreach ($seniorsList as $s): ?>
										<tr>
											<td>
												<div class="senior-info">
													<div class="senior-name">
														<i class="fas fa-user"></i>
														<strong><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></strong>
														<?php if ($s['middle_name']): ?>
															<br><small class="middle-name"><?= htmlspecialchars($s['middle_name']) ?></small>
														<?php endif; ?>
														<?php if ($s['ext_name']): ?>
															<br><small class="ext-name"><?= htmlspecialchars($s['ext_name']) ?></small>
														<?php endif; ?>
													</div>
													<?php if ($s['cellphone']): ?>
														<div class="senior-contact">
															<i class="fas fa-phone"></i>
															<?= htmlspecialchars($s['cellphone']) ?>
											</div>
													<?php endif; ?>
										</div>
									</td>
											<td><span class="age-badge"><?= (int)$s['age'] ?> years</span></td>
											<td><div class="barangay-info"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($s['barangay']) ?></div></td>
											<td><span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-warning' ?>"><?= $s['category'] === 'local' ? 'Local' : 'National' ?></span></td>
											<td><span class="badge <?= $s['benefits_received'] ? 'badge-success' : 'badge-warning' ?>"><i class="fas fa-<?= $s['benefits_received'] ? 'check' : 'clock' ?>"></i><?= $s['benefits_received'] ? 'Received' : 'Pending' ?></span></td>
											<td>
												<a href="<?= BASE_URL ?>/user/generate_certificate.php?id=<?= (int)$s['id'] ?>" target="_blank" class="generate-btn">
													<i class="fas fa-certificate"></i> Generate Certificate
												</a>
											</td>
								</tr>
							<?php endforeach; ?>
								<?php else: ?>
								<tr>
										<td colspan="6">
										<div class="empty-state">
											<div class="empty-icon">
													<i class="fas fa-users"></i>
											</div>
												<h3>No Seniors Found</h3>
												<p>No seniors registered in your barangay.</p>
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
						if (row.classList.contains('no-data')) {
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
						row.style.display !== 'none' && !row.classList.contains('no-data')
					);
					
					// Find empty state row (more compatible approach)
					let emptyStateRow = null;
					rows.forEach(row => {
						if (row.querySelector('.empty-state')) {
							emptyStateRow = row;
						}
					});
					
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
							if (p) p.textContent = 'No seniors registered in your barangay.';
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
