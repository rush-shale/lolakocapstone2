<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

// My barangay upcoming events
$stmt = $pdo->prepare("SELECT * FROM events WHERE scope='barangay' AND barangay = ? AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
$stmt->execute([$user['barangay']]);
$barangayEvents = $stmt->fetchAll();

// Admin events
$adminEvents = $pdo->query("SELECT * FROM events WHERE scope='admin' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5")->fetchAll();

// Recent past events created by this user
$recentPast = $pdo->prepare("SELECT * FROM events WHERE scope='barangay' AND barangay = ? AND event_date < CURDATE() ORDER BY event_date DESC LIMIT 5");
$recentPast->execute([$user['barangay']]);
$recentPastEvents = $recentPast->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Staff Dashboard | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<h1>Welcome, <?= htmlspecialchars($user['name']) ?></h1>
		<div class="grid">
			<section>
				<h2>My Barangay Upcoming Events</h2>
				<ul>
					<?php foreach ($barangayEvents as $e): ?>
						<li><?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?></li>
					<?php endforeach; ?>
					<?php if (empty($barangayEvents)): ?><li>No upcoming barangay events.</li><?php endif; ?>
				</ul>
			</section>
			<section>
				<h2>OSCA Head Events</h2>
				<ul>
					<?php foreach ($adminEvents as $e): ?>
						<li><?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?></li>
					<?php endforeach; ?>
					<?php if (empty($adminEvents)): ?><li>No upcoming admin events.</li><?php endif; ?>
				</ul>
			</section>
			<section>
				<h2>Recent Past Events I Created</h2>
				<ul>
					<?php foreach ($recentPastEvents as $e): ?>
						<li><?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?></li>
					<?php endforeach; ?>
					<?php if (empty($recentPastEvents)): ?><li>No past events.</li><?php endif; ?>
				</ul>
			</section>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


