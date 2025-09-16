<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
start_app_session();
$user = current_user();
$message = '';

// Load seniors in my barangay (living only)
$seniorsStmt = $pdo->prepare("SELECT id, first_name, last_name FROM seniors WHERE barangay=? AND life_status='living' ORDER BY last_name, first_name");
$seniorsStmt->execute([$user['barangay']]);
$seniors = $seniorsStmt->fetchAll();

// Load my future or today events to mark attendance
$eventsStmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE scope='barangay' AND barangay=? AND event_date >= CURDATE() ORDER BY event_date ASC");
$eventsStmt->execute([$user['barangay']]);
$events = $eventsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$event_id = (int)($_POST['event_id'] ?? 0);
		$senior_id = (int)($_POST['senior_id'] ?? 0);
		if ($event_id && $senior_id) {
			try {
				$stmt = $pdo->prepare('INSERT INTO attendance (senior_id, event_id) VALUES (?,?)');
				$stmt->execute([$senior_id, $event_id]);
				$message = 'Attendance marked';
			} catch (Throwable $e) {
				$message = 'Already marked or error';
			}
		}
	}
}

$csrf = generate_csrf_token();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Attendance | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<h1>Mark Attendance</h1>
		<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
		<div class="grid">
			<section>
				<h2>New Attendance</h2>
				<form method="post">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<label>Event</label>
					<select name="event_id" required>
						<option value="">Select event</option>
						<?php foreach ($events as $e): ?>
							<option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['event_date'].' - '.$e['title']) ?></option>
						<?php endforeach; ?>
					</select>
					<label>Senior</label>
					<select name="senior_id" required>
						<option value="">Select senior</option>
						<?php foreach ($seniors as $s): ?>
							<option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['last_name'].', '.$s['first_name']) ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit">Mark Present</button>
				</form>
			</section>
			<section>
				<h2>My Upcoming Events</h2>
				<ul>
					<?php foreach ($events as $e): ?>
						<li><?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?></li>
					<?php endforeach; ?>
					<?php if (empty($events)): ?><li>No upcoming events.</li><?php endif; ?>
				</ul>
			</section>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


