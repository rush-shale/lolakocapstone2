<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID provided, show selection interface
if (!$id) {
	$seniors = $pdo->query("SELECT * FROM seniors WHERE life_status='living' ORDER BY last_name, first_name")->fetchAll();
	// ... existing code ...
	if (isset($_GET['print']) && isset($_GET['ids']) && is_array($_GET['ids'])) {
		$ids = array_filter(array_map('intval', $_GET['ids']));
		if (!empty($ids)) {
			$in  = str_repeat('?,', count($ids) - 1) . '?';
			$stmt = $pdo->prepare("SELECT * FROM seniors WHERE id IN ($in) ORDER BY last_name, first_name");
			$stmt->execute($ids);
			$selected = $stmt->fetchAll();
			$side = ($_GET['side'] ?? 'front') === 'back' ? 'back' : 'front';
			$capacity = 8; // 2 columns x 4 rows per A4 (95x60mm)
			if (count($selected) > $capacity) { $selected = array_slice($selected, 0, $capacity); }
			?>
			<!doctype html>
			<html lang="en">
			<head>
				<meta charset="utf-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1" />
				<title>Print IDs (<?= htmlspecialchars(strtoupper($side)) ?>)</title>
				<style>
					@page { size: A4; margin: 5mm; }
					html, body { margin: 0; padding: 0; }
					.sheet {
						display: grid;
						grid-template-columns: repeat(2, 95mm);
						grid-auto-rows: 60mm;
						gap: 5mm;
						justify-content: center;
						align-content: start;
					}
					.sheet.front, .sheet.back {
						display: grid;
						grid-template-columns: repeat(2, 95mm);
						grid-auto-rows: 60mm;
						gap: 5mm;
						justify-content: center;
						align-content: start;
					}
					.card {
						width: 95mm; height: 60mm; border: 0.8mm solid #1e88e5; box-sizing: border-box; padding: 4mm; position: relative; font-family: Arial, sans-serif; overflow: hidden; border-radius: 2mm; background:#fff; min-width: 95mm; max-width: 95mm; min-height: 60mm; max-height: 60mm;
					}
					.sheet.back .card { padding: 0; }
                    .front .header-row { position:absolute; top:1.5mm; left:3mm; right:9mm; display:grid; grid-template-columns: 14mm 1fr 14mm; align-items:center; column-gap: 4mm; z-index:2; }
                    .front .logo { width:14mm; height:14mm; background: transparent; }
                    .front .center { position:absolute; top:18mm; left:4mm; right:30mm; z-index:2; }
                    .front .hdr { text-align:center; line-height:1.12; color:#000; }
					.front .hdr .l1 { font-weight:900; font-size: 9pt; white-space: nowrap; }
					.front .hdr .l2 { font-weight:800; font-size: 7.4pt; white-space: nowrap; }
					.front .hdr .l2-osca { font-weight:800; font-size: 7.4pt; white-space: nowrap; }
					.front .hdr .l3 { font-weight:700; font-size: 6.6pt; }
					.front .photo { position:absolute; right:4mm; top:20mm; width:25.4mm; height:25.4mm; border:0.22mm solid #cfd4da; display:flex; align-items:center; justify-content:center; font-size:6.2pt; border-radius: 2mm; background:#e6ecf2; z-index:1; }
					.front .left { width:100%; margin-top: 7mm; position:relative; z-index:2; background:#fff; }
                    .front .field { display:grid; grid-template-columns: 15mm 1fr; align-items:center; column-gap: 0.6mm; font-size: 6.4pt; margin-top: 1.8mm; color:#000; }
					.front .label { color:#000; font-weight:900; text-align:left; }
					.front .uline { width: 100%; border-bottom: 0.2mm solid #000; padding-bottom: 0.7mm; line-height: 1.14; font-weight:700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color:#000; display:block; }
                    .front .triple-topline { display:none; }
                    .front .triple-labels { display:grid; grid-template-columns: 1fr 1fr 1fr; column-gap: 5mm; margin-top: 0; margin-bottom: 0.4mm; font-size:6pt; color:#000; font-weight:900; align-items:end; }
                    .front .triple-labels span { display:block; text-align:left; }
                    .front .triple-values { display:grid; grid-template-columns: 1fr 1fr 1fr; column-gap: 5mm; margin-top: 0; margin-bottom: 2mm; font-size: 6.4pt; color:#000; align-items:end; }
                    .front .triple-values .uline { display:block; width:100%; text-align:left; padding: 0.1mm 0 0.6mm 0; min-height: 4mm; line-height: 1.2; border-bottom: 0.18mm solid #000; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight:700; }
                    .front .red-box { padding: 1.5mm 2mm 1.5mm 0; margin-top: 1mm; margin-bottom: 1mm; }
                    .front .red-box .triple-labels { font-size: 5pt; margin-bottom: 0.3mm; text-align: left; }
                    .front .red-box .triple-labels span { text-align: left; }
                    .front .red-box .triple-values { font-size: 5.5pt; margin-bottom: 0; text-align: left; border-bottom: 0.18mm solid #000; padding-bottom: 0.4mm; position: relative; }
                    .front .red-box .triple-values .uline { padding: 0.1mm 0 0; min-height: 3mm; line-height: 1.1; text-align: left; border-bottom: none; }
                    .front .sig { width: 58mm; border-bottom: none; height: 0; margin-top: 0; }
                    .front .sig-label { font-size: 5.6pt; font-weight:700; display:block; margin-top: 1mm; text-align:center; }
                    .front .sig-row { display:flex; justify-content:center; align-items:center; margin-top: 4mm; }
                    .front .sig-block { width: 58mm; text-align:center; margin-left: 20mm; }
                    .front .sig-block .sig-line { border-top: 0.16mm solid #000; margin: 0 0 1mm 0; }
                    .front .ctrl-inline { display:flex; align-items:center; gap: 2mm; font-weight:900; font-size:6.8pt; white-space: nowrap; }
                    .front .ctrl { position:absolute; right:6mm; top:47mm; display:flex; align-items:center; gap: 0.8mm; font-weight:700; font-size:5.6pt; z-index:3; background:#fff; padding: 0 0.3mm; }
                    /* removed underline next to Control No. */
                    .front .footer-note { position:absolute; left:0; right:0; bottom:2mm; text-align:center; font-size:6.4pt; font-weight:900; text-transform:none; z-index:3; background:#fff; }
					.back { padding: 0; font-family: Arial, sans-serif; overflow: hidden; display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; position: relative; box-sizing: border-box; }
					.back img { width: 100%; height: 100%; object-fit: contain; display: block; }
					.back .title { font-weight: 900; font-size: 6.4pt; text-align: left; margin-bottom: 1mm; color: #000; line-height: 1.1; padding-left: 0; }
					.back .benefits-list { flex: 1; overflow: hidden; text-align: left; font-size: 5.5pt; line-height: 1.25; color: #000; margin-bottom: 0.5mm; padding-left: 0; padding-bottom: 6mm; }
					.back .benefits-list ul { margin: 0; padding-left: 4mm; list-style-type: square; }
					.back .benefits-list li { margin-bottom: 0.35mm; }
					.back .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; margin-top: 0.5mm; margin-bottom: 0; padding-left: 0; position: absolute; bottom: 8mm; left: 4mm; right: 4mm; }
					.back .signature-block { display: flex; flex-direction: column; align-items: center; }
					.back .sig-space { height: 6mm; margin-bottom: 0.5mm; }
					.back .sig-name { font-weight: 900; font-size: 5.6pt; text-transform: uppercase; text-align: center; border-bottom: 0.18mm solid #000; padding-bottom: 0.3mm; margin-bottom: 0.3mm; width: 100%; }
					.back .sig-title { font-weight: 900; font-size: 5pt; text-transform: uppercase; text-align: center; color: #000; }
					.back .disclaimer { font-size: 5pt; font-style: italic; text-align: left; margin-top: 0; color: #000; padding: 0.5mm 0; padding-left: 0; position: absolute; bottom: 2mm; left: 4mm; right: 4mm; z-index: 100; background: #fff; }
					@media print { .noprint { display:none; } }
				</style>
			</head>
			<body>
				<div class="noprint" style="margin:10px 0; text-align:center;">
					<button onclick="window.print()">Print</button>
				</div>
				<div class="sheet <?= $side ?>">
					<?php foreach ($selected as $item): ?>
						<div class="card">
                            <?php if ($side === 'front'): ?>
                                <div class="header-row">
                                    <img class="logo" src="<?= BASE_URL ?>/images/OSCA LOGO.png" alt="OSCA">
                                    <div class="hdr">
                                        <div class="l1">Republic of the Philippines</div>
                                        <div class="l2">Office for the Senior Citizens Affairs</div>
                                        <div class="l2-osca">(OSCA)</div>
                                        <div class="l3">Municipality of Manolo Fortich Bukidnon</div>
                                    </div>
                                    <img class="logo" src="<?= BASE_URL ?>/images/MANOLO FORTICH LOGO.png" alt="MF">
                                </div>
                                <div class="center">
                                    <div class="left">
										<div class="field"><span class="label">Name:</span><span class="uline"><?= htmlspecialchars(strtoupper($item['last_name'] . ', ' . $item['first_name'] . ($item['middle_name'] ? ' ' . $item['middle_name'] : ''))) ?></span></div>
                                        <div class="field"><span class="label">Address:</span><span class="uline"><?= htmlspecialchars(ucwords(strtolower(trim(($item['barangay'] ?? '') . ', Manolo Fortich, Bukidnon.')))) ?></span></div>
                                        <?php 
                                            $dobField = $item['birthdate'] ?? ($item['date_of_birth'] ?? null);
                                            $sexField = $item['sex'] ?? ($item['gender'] ?? '');
                                        ?>
                                        <div class="red-box">
                                            <div class="triple-labels">
                                                <span>Date of Birth</span>
                                                <span>Sex</span>
                                                <span>Date Issued</span>
                                            </div>
                                            <div class="triple-values">
                                                <span class="uline"><?= $dobField ? date('m-d-Y', strtotime($dobField)) : '' ?></span>
                                                <span class="uline"><?= htmlspecialchars(strtoupper($sexField)) ?></span>
                                                <span class="uline"><?= date('m-d-Y') ?></span>
                                            </div>
                                        </div>
                                        <div class="sig-row">
                                            <div class="sig-block">
                                                <div class="sig-line"></div>
                                                <span class="sig-label">Signature/Thumbmark:</span>
                                            </div>
                                        </div>
									</div>
								</div>
                                <div class="photo">
									<?php if (!empty($item['photo'])): ?>
										<img src="<?= htmlspecialchars($item['photo']) ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 2mm;">
									<?php else: ?>
										Photo
									<?php endif; ?>
								</div>
                                <div class="ctrl"><span>Control No. </span><span><?= htmlspecialchars($item['osca_id_no'] ?? '') ?></span></div>
								<div class="footer-note">This Card is Non-Transferable</div>
							<?php else: ?>
								<div class="back">
									<img src="<?= BASE_URL ?>/images/BACK ID.png?v=<?= time() ?>" alt="Senior ID Back">
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</body>
			</html>
			<?php exit; }
	}
	?>
	<!doctype html>
	<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Generate Senior IDs | SeniorCare</title>
		<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
		<style>
			.capacity-badge { font-weight: 600; color:#111827; }
			.table td, .table th { vertical-align: middle; }
		</style>
	</head>
	<body>
		<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
		<main class="content">
			<header class="content-header">
				<h1 class="content-title">Generate Senior IDs</h1>
				<p class="content-subtitle">Select up to 8 seniors per A4 sheet (95×60mm)</p>
			</header>
			<div class="content-body">
				<div class="card">
					<div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
						<h2 class="card-title"><i class="fas fa-id-card"></i> Selection</h2>
						<div style="display:flex; gap:.5rem; align-items:center;">
							<span class="capacity-badge"><span id="selectedCount">0</span>/8 selected</span>
							<button form="printForm" type="submit" name="side" value="front" class="button primary" title="Print Front" formaction="<?= BASE_URL ?>/admin/senior_id.php">
								<i class="fas fa-print"></i> Front
							</button>
							<button form="printForm" type="submit" name="side" value="back" class="button secondary" title="Print Back" formaction="<?= BASE_URL ?>/admin/senior_id.php">
								<i class="fas fa-print"></i> Back
							</button>
						</div>
					</div>
					<div class="card-body">
						<form id="printForm" method="get" target="_blank">
							<input type="hidden" name="print" value="1">
							<div class="table-container">
								<table class="table">
									<thead>
										<tr>
											<th>Select</th>
											<th>Senior Information</th>
											<th>Age</th>
											<th>Barangay</th>
											<th>Category</th>
											<th>Benefits Status</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($seniors as $s): ?>
										<tr>
											<td><input type="checkbox" class="select-senior" name="ids[]" value="<?= (int)$s['id'] ?>"></td>
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
											<td><span class="age-badge"><?= (int)$s['age'] ?> years</span></td>
											<td><div class="barangay-info"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($s['barangay']) ?></div></td>
											<td><span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-warning' ?>"><?= $s['category'] === 'local' ? 'Local' : 'National' ?></span></td>
											<td><span class="badge <?= $s['benefits_received'] ? 'badge-success' : 'badge-warning' ?>"><i class="fas fa-<?= $s['benefits_received'] ? 'check' : 'clock' ?>"></i><?= $s['benefits_received'] ? 'Received' : 'Pending' ?></span></td>
											<td>
												<a href="<?= BASE_URL ?>/admin/senior_id.php?id=<?= (int)$s['id'] ?>" target="_blank" class="button primary"><i class="fas fa-camera"></i> Attach Photo</a>
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</form>
					</div>
				</div>
			</div>
		</main>
		<script>
			(function(){
				const capacity = 8; // A4 capacity based on 95x60mm cards
				const checkboxes = Array.from(document.querySelectorAll('.select-senior'));
				const counter = document.getElementById('selectedCount');
				function update(){
					const selected = checkboxes.filter(cb => cb.checked);
					counter.textContent = selected.length;
					checkboxes.forEach(cb => { if (!cb.checked) cb.disabled = selected.length >= capacity; });
				}
				checkboxes.forEach(cb => cb.addEventListener('change', update));
				update();
			})();
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

// Handle photo upload
$uploadSuccess = false;
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_photo'])) {
	// Delete photo
	if (!empty($s['photo'])) {
		$oldPhotoPath = __DIR__ . '/../../public/uploads/photos/' . basename($s['photo']);
		if (file_exists($oldPhotoPath)) {
			unlink($oldPhotoPath);
		}
		$stmt = $pdo->prepare("UPDATE seniors SET photo = NULL WHERE id = ?");
		$stmt->execute([$id]);
		$s['photo'] = null;
		$uploadSuccess = true;
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
	// Check if photo column exists, if not add it
	try {
		$pdo->query("SELECT photo FROM seniors LIMIT 1");
	} catch (PDOException $e) {
		// Column doesn't exist, add it
		try {
			$pdo->exec("ALTER TABLE seniors ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
		} catch (PDOException $ex) {
			// Ignore if already exists
		}
	}
	
	$uploadDir = __DIR__ . '/../../public/uploads/photos/';
	if (!is_dir($uploadDir)) {
		mkdir($uploadDir, 0755, true);
	}
	
	$file = $_FILES['photo'];
	$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
	$maxSize = 5 * 1024 * 1024; // 5MB
	
	if (!in_array($file['type'], $allowedTypes)) {
		$uploadError = 'Invalid file type. Please upload JPEG, PNG, or GIF images only.';
	} elseif ($file['size'] > $maxSize) {
		$uploadError = 'File size too large. Maximum size is 5MB.';
	} else {
		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		$filename = 'senior_' . $id . '_' . time() . '.' . $extension;
		$filepath = $uploadDir . $filename;
		
		// Delete old photo if exists
		if (!empty($s['photo'])) {
			$oldPhotoPath = __DIR__ . '/../../public/uploads/photos/' . basename($s['photo']);
			if (file_exists($oldPhotoPath)) {
				unlink($oldPhotoPath);
			}
		}
		
		if (move_uploaded_file($file['tmp_name'], $filepath)) {
			$photoUrl = BASE_URL . '/uploads/photos/' . $filename;
			$stmt = $pdo->prepare("UPDATE seniors SET photo = ? WHERE id = ?");
			$stmt->execute([$photoUrl, $id]);
			$s['photo'] = $photoUrl;
			$uploadSuccess = true;
		} else {
			$uploadError = 'Failed to upload photo. Please try again.';
		}
	}
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
            display: grid;
            grid-template-columns: 60px 1fr 60px;
            align-items: center;
            margin-bottom: 8px;
        }
		.logo {
			width: 60px;
			height: 60px;
			background: #fff;
			border-radius: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			overflow: hidden;
			user-select: none;
			padding: 4px;
		}
		.logo img {
			width: 100%;
			height: 100%;
			object-fit: contain;
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
			margin-top: 4px;
			flex: 1;
			display: flex;
			gap: 10px;
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
            display: grid;
            grid-template-columns: 78px 1fr;
            align-items: center;
            column-gap: 6px;
            border-bottom: none;
            padding: 0;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .info .field.field-spaced {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            column-gap: 6px;
            border-bottom: none;
            padding: 0;
        }
        .info .field label {
			font-weight: 600;
			font-size: 11px;
			color: #555;
			user-select: none;
			flex-shrink: 0;
            margin-right: 0;
		}
		.info .field.field-spaced label {
			margin-right: 0;
		}
        .info .field .value {
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            display: block;
        }
        .info .field.field-spaced .value {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 2px 0 1px 0;
        }
		.bottom-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-top: 8px;
			font-size: 11px;
			color: #555;
			user-select: none;
		}
		.signature-block { flex: 1; display: flex; flex-direction: column; align-items: flex-start; margin-right: 8px; }
		.signature { width: 100%; border-bottom: 1px solid #000; height: 0; margin-bottom: 4px; }
		.signature-label { font-size: 11px; font-weight: 600; }
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
				<div class="logo" title="OSCA Logo">
					<img src="<?= BASE_URL ?>/images/OSCA LOGO.png" alt="OSCA Logo">
				</div>
				<div class="title">
					<div class="line1">Republic of the Philippines</div>
					<div class="line2">Office of the Senior Citizens Affairs (OSCA)</div>
					<div class="line3">Municipality of Manolo Fortich Bukidnon</div>
				</div>
				<div class="logo" title="Manolo Fortich Logo">
					<img src="<?= BASE_URL ?>/images/MANOLO FORTICH LOGO.png" alt="Manolo Fortich Logo">
				</div>
			</div>
			<div class="content">
				<div class="info">
					<div class="field">
						<label>Name:</label>
						<div class="value"><?= h(strtoupper($s['last_name'] . ', ' . $s['first_name'] . ($s['middle_name'] ? ' ' . $s['middle_name'] : ''))) ?></div>
					</div>
					<div class="field">
						<label>Address:</label>
						<div class="value"><?= h(ucwords(strtolower(trim(($s['barangay'] ?? '') . ', Manolo Fortich, Bukidnon.')))) ?></div>
					</div>
					<div class="field field-spaced" style="margin-top: 12px;">
						<label>Date of Birth</label>
						<label>Sex</label>
						<label>Date Issued</label>
					</div>
					<div class="field field-spaced" style="font-weight: 700;">
						<div class="value"><?= h(date('m-d-Y', strtotime($s['birthdate'] ?? ''))) ?></div>
						<div class="value"><?= h(strtoupper($s['sex'] ?? '')) ?></div>
						<div class="value"><?= h(date('m-d-Y')) ?></div>
					</div>
				</div>
				<div class="photo">
					<?php if (!empty($s['photo'])): ?>
						<img src="<?= h($s['photo']) ?>" alt="Senior Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
					<?php else: ?>
						📷<br>Photo
					<?php endif; ?>
				</div>
			</div>
			<div class="bottom-row">
				<div class="signature-block">
					<div class="signature" title="Signature / Thumbmark"></div>
					<div class="signature-label">Signature/Thumbmark:</div>
				</div>
				<div class="control-number">Control No. <?= h($s['osca_id_no'] ?? 'N/A') ?></div>
			</div>
			<div class="note">This Card is Non-Transferable</div>
		</div>
		<div class="controls">
			<a href="<?= BASE_URL ?>/admin/senior_id.php" class="back-btn" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">← Back to Generate Senior IDs</a>
		</div>
		<div class="upload-section" style="margin-top: 20px; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
			<h3 style="margin-top: 0; margin-bottom: 15px;">Upload Photo for ID Card</h3>
			<?php if ($uploadSuccess): ?>
				<div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;">
					Photo uploaded successfully!
				</div>
			<?php endif; ?>
			<?php if ($uploadError): ?>
				<div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;">
					<?= h($uploadError) ?>
				</div>
			<?php endif; ?>
			<form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
				<input type="file" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
				<button type="submit" style="padding: 10px 20px; background: #1e88e5; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
					📷 Upload Photo
				</button>
			</form>
			<?php if (!empty($s['photo'])): ?>
				<form method="POST" style="margin-top: 10px;">
					<input type="hidden" name="delete_photo" value="1">
					<button type="submit" onclick="return confirm('Are you sure you want to remove this photo?')" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
						🗑️ Remove Photo
					</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>


