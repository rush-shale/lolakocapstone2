<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

$barangays = $pdo->query('SELECT * FROM barangays ORDER BY name')->fetchAll();

// Seniors grouped by barangay (living only)
$stmt = $pdo->query("SELECT barangay, id, first_name, last_name FROM seniors WHERE life_status='living' ORDER BY barangay, last_name, first_name");
$byBarangay = [];
foreach ($stmt as $row) { $byBarangay[$row['barangay']][] = $row; }

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Barangays | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<h1>Barangays and Seniors</h1>
		<div class="grid">
			<?php foreach ($barangays as $b): ?>
				<section>
					<h2><?= htmlspecialchars($b['name']) ?></h2>
					<ul>
						<?php foreach ($byBarangay[$b['name']] ?? [] as $s): ?>
							<li><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></li>
						<?php endforeach; ?>
						<?php if (empty($byBarangay[$b['name']] ?? [])): ?><li>No seniors listed.</li><?php endif; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	</main>
</body>
</html>


