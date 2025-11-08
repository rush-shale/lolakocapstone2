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
			@page { 
				size: A4 landscape;
				margin: 0;
			}
			html, body { 
				margin: 0; 
				padding: 0; 
				font-family: Arial, sans-serif;
				background: #f5f5f5;
			}
			.certificate {
				width: 297mm;
				height: 210mm;
				background: #ffffff;
				border: 6mm solid #1e3a8a;
				box-sizing: border-box;
				position: relative;
				overflow: hidden;
				padding: 20mm;
				margin: 0 auto;
			}
			
			/* Decorative triangles - top left */
			.triangle-top-left {
				position: absolute;
				top: 0;
				left: 0;
				width: 0;
				height: 0;
				border-style: solid;
				border-width: 50mm 50mm 0 0;
				border-color: #1e3a8a transparent transparent transparent;
				z-index: 1;
			}
			.triangle-top-left-inner {
				position: absolute;
				top: 8mm;
				left: 8mm;
				width: 0;
				height: 0;
				border-style: solid;
				border-width: 35mm 35mm 0 0;
				border-color: #3b82f6 transparent transparent transparent;
				z-index: 2;
			}
			
			/* Decorative triangles - bottom right */
			.triangle-bottom-right {
				position: absolute;
				bottom: 0;
				right: 0;
				width: 0;
				height: 0;
				border-style: solid;
				border-width: 0 0 50mm 50mm;
				border-color: transparent transparent #1e3a8a transparent;
				z-index: 1;
			}
			.triangle-bottom-right-inner {
				position: absolute;
				bottom: 8mm;
				right: 8mm;
				width: 0;
				height: 0;
				border-style: solid;
				border-width: 0 0 35mm 35mm;
				border-color: transparent transparent #3b82f6 transparent;
				z-index: 2;
			}
			
			/* Logo/Seal */
			.logo-seal {
				position: absolute;
				top: 15mm;
				right: 15mm;
				width: 70mm;
				height: 70mm;
				z-index: 3;
			}
			.logo-outer-ring {
				width: 70mm;
				height: 70mm;
				border-radius: 50%;
				border: 2.5mm solid #065f46;
				background: #ffffff;
				display: flex;
				align-items: center;
				justify-content: center;
				position: relative;
				overflow: hidden;
			}
			.logo-text-top {
				position: absolute;
				top: 3mm;
				left: 50%;
				transform: translateX(-50%);
				font-size: 3.8pt;
				font-weight: 700;
				color: #ffffff;
				white-space: nowrap;
				text-align: center;
				z-index: 4;
				width: 100%;
			}
			.logo-text-bottom {
				position: absolute;
				bottom: 3mm;
				left: 50%;
				transform: translateX(-50%);
				font-size: 3.2pt;
				font-weight: 700;
				color: #ffffff;
				white-space: nowrap;
				z-index: 4;
				width: 100%;
			}
			.logo-inner-circle {
				width: 55mm;
				height: 55mm;
				border-radius: 50%;
				background: #d1fae5;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				position: relative;
				margin-top: 2mm;
			}
			.logo-figures {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 2mm;
				margin-top: 5mm;
			}
			.logo-figure {
				font-size: 18pt;
			}
			.logo-hands {
				position: absolute;
				bottom: 5mm;
				left: 50%;
				transform: translateX(-50%);
				font-size: 14pt;
			}
			
			/* Certificate Content */
			.certificate-content {
				position: relative;
				z-index: 2;
				text-align: center;
				padding-top: 5mm;
				padding-right: 80mm;
			}
			.certificate-title {
				font-size: 36pt;
				font-weight: 900;
				color: #000000;
				margin-bottom: 8mm;
				letter-spacing: 3pt;
				font-family: Arial, sans-serif;
			}
			.certificate-subtitle {
				font-size: 16pt;
				font-weight: 700;
				color: #000000;
				margin-bottom: 20mm;
				letter-spacing: 1.5pt;
				text-transform: uppercase;
			}
			.award-statement {
				font-size: 11pt;
				color: #000000;
				margin-bottom: 5mm;
				font-weight: 400;
			}
			.name-line {
				width: 180mm;
				height: 0.5mm;
				background: #000000;
				margin: 3mm auto 20mm;
			}
			.certificate-body {
				text-align: left;
				font-size: 10pt;
				line-height: 1.7;
				color: #000000;
				max-width: 200mm;
				margin: 0 auto 25mm;
			}
			.certificate-body p {
				margin: 0 0 10mm 0;
			}
			.certificate-body strong {
				font-weight: 700;
			}
			
			/* Signatures */
			.signatures {
				display: flex;
				justify-content: space-between;
				margin-top: 25mm;
				padding: 0 15mm;
			}
			.signature-block {
				display: flex;
				flex-direction: column;
				align-items: flex-start;
			}
			.signature-name {
				font-size: 11pt;
				font-weight: 700;
				color: #000000;
				text-decoration: underline;
				margin-bottom: 2mm;
			}
			.signature-title {
				font-size: 9pt;
				color: #000000;
			}
			
			@media print {
				.noprint { display: none; }
				body { 
					background: white; 
					margin: 0;
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
			<a href="<?= BASE_URL ?>/user/osca_events.php" style="display: inline-block; margin-left: 10px; padding: 10px 20px; font-size: 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">
				← Back to Certificate Generation
			</a>
		</div>
		<div class="certificate">
			<!-- Decorative Triangles -->
			<div class="triangle-top-left"></div>
			<div class="triangle-top-left-inner"></div>
			<div class="triangle-bottom-right"></div>
			<div class="triangle-bottom-right-inner"></div>
			
			<!-- Logo/Seal -->
			<div class="logo-seal">
				<div class="logo-outer-ring">
					<div class="logo-text-top">SENIOR CITIZEN FEDERATION, INCORPORATED</div>
					<div class="logo-text-bottom">• 1990 • MANOLO FORTICH • 1990 •</div>
					<div class="logo-inner-circle">
						<div class="logo-figures">
							<div class="logo-figure">👵</div>
							<div class="logo-figure">👴</div>
						</div>
						<div class="logo-hands">🤲</div>
					</div>
				</div>
			</div>
			
			<!-- Certificate Content -->
			<div class="certificate-content">
				<div class="certificate-title">CERTIFICATE</div>
				<div class="certificate-subtitle">OF BEST ACTIVE SENIOR CITIZEN</div>
				
				<div class="award-statement">THIS CERTIFICATE IS AWARDED TO</div>
				<div style="width: 180mm; margin: 3mm auto 20mm; text-align: center; position: relative;">
					<div style="font-size: 13pt; font-weight: 700; color: #000000; padding-bottom: 2mm; border-bottom: 0.5mm solid #000000; display: inline-block; min-width: 150mm;"><?= htmlspecialchars(strtoupper($fullName)) ?></div>
				</div>
				
				<div class="certificate-body">
					<p>This certificate is proudly presented to <strong><?= htmlspecialchars($fullName) ?></strong></p>
					<p>In recognition of being the "Best in Active Senior Citizen," for your outstanding participation, dedication, and positive contributions to community programs and activities. Your enthusiasm, commitment, and active involvement serve as an inspiration to your peers. This award honors your remarkable efforts and lasting impact on the senior community.</p>
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
				<div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
					<h2 class="card-title"><i class="fas fa-certificate"></i> Senior Selection</h2>
				</div>
				<div class="card-body">
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
						<tbody>
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
												<a href="<?= BASE_URL ?>/user/osca_events.php?id=<?= (int)$s['id'] ?>" target="_blank" class="generate-btn">
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
</body>
</html>
