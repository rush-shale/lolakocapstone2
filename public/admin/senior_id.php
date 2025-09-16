<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Senior ID | <?= h($s['last_name'].', '.$s['first_name']) ?></title>
	<style>
		body{margin:0;background:#0f172a;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
		.wrapper{display:grid;min-height:100vh;place-items:center;padding:1rem}
		.card{width:336px;height:210px;background:#111827;color:#e5e7eb;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.4);padding:16px;position:relative}
		.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
		.brand{font-weight:800;letter-spacing:.5px;color:#22d3ee}
		.meta{font-size:12px;color:#94a3b8}
		.hr{height:1px;background:#1f2937;margin:8px 0}
		.row{display:flex;gap:10px}
		.photo{width:80px;height:100px;background:#0b1220;border:1px solid #334155;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px}
		.info{flex:1}
		.label{font-size:11px;color:#94a3b8;margin-top:6px}
		.value{font-size:14px}
		.footer{position:absolute;left:16px;right:16px;bottom:12px;display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#94a3b8}
		.controls{margin-top:16px;text-align:center}
		button{padding:.6rem 1rem;border:none;border-radius:8px;background:linear-gradient(135deg,#22d3ee,#3b82f6);color:#00111a;font-weight:700;cursor:pointer}
		@media print{.controls{display:none}body{background:#fff}.card{box-shadow:none}}
	</style>
</head>
<body>
	<div class="wrapper">
		<div class="card">
			<div class="header">
				<div class="brand">LoLaKo — Senior ID</div>
				<div class="meta">ID: #<?= (int)$s['id'] ?></div>
			</div>
			<div class="hr"></div>
			<div class="row">
				<div class="photo">Photo</div>
				<div class="info">
					<div class="label">Name</div>
					<div class="value"><?= h($s['last_name'] . ', ' . $s['first_name'] . ($s['middle_name']? ' ' . $s['middle_name'] : '')) ?></div>
					<div class="label">Age</div>
					<div class="value"><?= (int)$s['age'] ?></div>
					<div class="label">Barangay</div>
					<div class="value"><?= h($s['barangay']) ?></div>
					<div class="label">Category / Life</div>
					<div class="value"><?= h(strtoupper($s['category'])) ?> • <?= h(ucfirst($s['life_status'])) ?></div>
				</div>
			</div>
			<div class="footer">
				<div>Benefits: <?= $s['benefits_received'] ? 'Received' : 'Not Yet' ?></div>
				<div>Issued: <?= date('Y-m-d') ?></div>
			</div>
		</div>
		<div class="controls">
			<button onclick="window.print()">Print / Save as PDF</button>
		</div>
	</div>
</body>
</html>


