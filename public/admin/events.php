<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();
$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$event_date = $_POST['event_date'] ?? '';
		$event_time = $_POST['event_time'] ?? null;
		$scope = 'admin';
		if ($title && $event_date) {
			$stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, event_time, scope, created_by) VALUES (?,?,?,?,?,?)');
			$stmt->execute([$title,$description ?: null,$event_date,$event_time ?: null,$scope,$user['id']]);
			$message = 'Event created';
		}
	}
}

$csrf = generate_csrf_token();
$events = $pdo->query("SELECT * FROM events WHERE scope='admin' ORDER BY event_date DESC, id DESC")->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Events | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<h1>OSCA Head Events</h1>
		<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
		<div class="grid">
			<section>
				<h2>Create Event</h2>
				<form method="post">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<label>Title</label>
					<input name="title" required>
					<label>Description</label>
					<input name="description">
					<label>Date</label>
					<input type="date" name="event_date" required>
					<label>Time</label>
					<input type="time" name="event_time">
					<button type="submit">Save</button>
				</form>
			</section>
			<section>
				<h2>All Admin Events</h2>
				<ul>
					<?php foreach ($events as $e): ?>
						<li><?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?></li>
					<?php endforeach; ?>
					<?php if (empty($events)): ?><li>No events.</li><?php endif; ?>
				</ul>
			</section>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


