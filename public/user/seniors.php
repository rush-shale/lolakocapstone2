<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

// Seniors in my barangay (living only)
$seniorsStmt = $pdo->prepare("SELECT id, first_name, last_name, age, benefits_received FROM seniors WHERE barangay=? AND life_status='living' ORDER BY last_name, first_name");
$seniorsStmt->execute([$user['barangay']]);
$seniors = $seniorsStmt->fetchAll();

// Active seniors based on attendance count (last 90 days by default)
$activeStmt = $pdo->prepare(
	"SELECT s.id, s.first_name, s.last_name, COUNT(a.id) AS attendances
	 FROM seniors s
	 JOIN attendance a ON a.senior_id = s.id
	 JOIN events e ON e.id = a.event_id
	 WHERE s.barangay = ? AND s.life_status = 'living' AND e.scope='barangay' AND e.barangay = ? AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
	 GROUP BY s.id, s.first_name, s.last_name
	 HAVING COUNT(a.id) >= 3
	 ORDER BY attendances DESC, s.last_name, s.first_name"
);
$activeStmt->execute([$user['barangay'], $user['barangay']]);
$active = $activeStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Seniors | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<h1>My Barangay Seniors</h1>
		<div class="grid">
			<section>
				<h2>All Living Seniors</h2>
				<table style="width:100%">
					<tr><th>Name</th><th>Age</th><th>Benefits</th></tr>
					<?php foreach ($seniors as $s): ?>
						<tr>
							<td><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></td>
							<td><?= (int)$s['age'] ?></td>
							<td><?= $s['benefits_received'] ? 'Received' : 'Not Yet' ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if (empty($seniors)): ?><tr><td colspan="3">No seniors found.</td></tr><?php endif; ?>
				</table>
			</section>
			<section>
				<h2>Active Seniors (last 90 days, 3+ attendances)</h2>
				<ul>
					<?php foreach ($active as $a): ?>
						<li><?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?> — <?= (int)$a['attendances'] ?> attendances</li>
					<?php endforeach; ?>
					<?php if (empty($active)): ?><li>No active seniors yet.</li><?php endif; ?>
				</ul>
			</section>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


