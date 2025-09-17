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
		<title>Generate Senior ID | LoLaKo</title>
		<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
	</head>
	<body>
		<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
		<main class="content">
			<div class="page-header">
				<h1>🆔 Generate Senior ID</h1>
				<p>Select a senior citizen to generate their ID card</p>
			</div>
			
			<div class="card">
				<div class="card-header">
					<h2>👥 Select Senior Citizen</h2>
					<p>Choose a senior to generate their ID card</p>
				</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Age</th>
								<th>Barangay</th>
								<th>Category</th>
								<th>Benefits</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($seniors as $s): ?>
								<tr>
									<td>
										<strong><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></strong>
										<?php if ($s['middle_name']): ?>
											<br><small><?= htmlspecialchars($s['middle_name']) ?></small>
										<?php endif; ?>
									</td>
									<td><?= (int)$s['age'] ?></td>
									<td><?= htmlspecialchars($s['barangay']) ?></td>
									<td>
										<span class="badge <?= $s['category'] === 'local' ? 'badge-primary' : 'badge-warning' ?>">
											<?= $s['category'] === 'local' ? 'Local' : 'National' ?>
										</span>
									</td>
									<td>
										<span class="badge <?= $s['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
											<?= $s['benefits_received'] ? 'Received' : 'Pending' ?>
										</span>
									</td>
									<td>
										<a href="<?= BASE_URL ?>/admin/senior_id.php?id=<?= (int)$s['id'] ?>" target="_blank" style="text-decoration:none">
											<button class="small">🆔 Generate ID</button>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($seniors)): ?>
								<tr>
									<td colspan="6" style="text-align: center; padding: 2rem; color: var(--muted);">
										No living seniors found.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</main>
		<script src="<?= BASE_URL ?>/assets/app.js"></script>
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
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Senior ID | <?= h($s['last_name'].', '.$s['first_name']) ?></title>
	<style>
		body{margin:0;background:#f8fafc;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
		.wrapper{display:grid;min-height:100vh;place-items:center;padding:1rem}
		.card{width:400px;height:250px;background:#ffffff;color:#1e293b;border-radius:16px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05);padding:24px;position:relative;border:1px solid #e2e8f0}
		.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
		.brand{font-weight:800;font-size:18px;color:#3b82f6;letter-spacing:-0.025em}
		.meta{font-size:12px;color:#64748b;background:#f1f5f9;padding:4px 8px;border-radius:6px}
		.hr{height:2px;background:linear-gradient(90deg,#3b82f6,#22d3ee);margin:16px 0;border-radius:1px}
		.row{display:flex;gap:16px}
		.photo{width:100px;height:120px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:12px;font-weight:500}
		.info{flex:1}
		.label{font-size:11px;color:#64748b;margin-top:8px;font-weight:500;text-transform:uppercase;letter-spacing:0.05em}
		.value{font-size:14px;font-weight:600;margin-bottom:4px}
		.footer{position:absolute;left:24px;right:24px;bottom:16px;display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#64748b;background:#f8fafc;padding:8px 12px;border-radius:8px}
		.controls{margin-top:24px;text-align:center}
		button{padding:12px 24px;border:none;border-radius:8px;background:#3b82f6;color:white;font-weight:600;cursor:pointer;font-size:14px;transition:all 0.2s ease}
		button:hover{background:#2563eb;transform:translateY(-1px);box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)}
		.back-btn{background:#64748b;margin-right:12px}
		.back-btn:hover{background:#475569}
		@media print{.controls{display:none}body{background:#fff}.card{box-shadow:none;border:1px solid #000}}
	</style>
</head>
<body>
	<div class="wrapper">
		<div class="card">
			<div class="header">
				<div class="brand">LoLaKo Senior ID</div>
				<div class="meta">ID: #<?= (int)$s['id'] ?></div>
			</div>
			<div class="hr"></div>
			<div class="row">
				<div class="photo">📷<br>Photo</div>
				<div class="info">
					<div class="label">Full Name</div>
					<div class="value"><?= h($s['last_name'] . ', ' . $s['first_name'] . ($s['middle_name']? ' ' . $s['middle_name'] : '')) ?></div>
					<div class="label">Age</div>
					<div class="value"><?= (int)$s['age'] ?> years old</div>
					<div class="label">Barangay</div>
					<div class="value"><?= h($s['barangay']) ?></div>
					<div class="label">Category & Status</div>
					<div class="value"><?= h(strtoupper($s['category'])) ?> • <?= h(ucfirst($s['life_status'])) ?></div>
				</div>
			</div>
			<div class="footer">
				<div>Benefits: <?= $s['benefits_received'] ? '✅ Received' : '⏳ Pending' ?></div>
				<div>Issued: <?= date('M d, Y') ?></div>
			</div>
		</div>
		<div class="controls">
			<button class="back-btn" onclick="window.close()">← Back</button>
			<button onclick="window.print()">🖨️ Print / Save PDF</button>
		</div>
	</div>
</body>
</html>


