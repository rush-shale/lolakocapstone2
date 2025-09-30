<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
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
		if ($title && $event_date) {
			$stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, event_time, scope, barangay, created_by) VALUES (?,?,?,?,"barangay",?,?)');
			$stmt->execute([$title,$description ?: null,$event_date,$event_time ?: null,$user['barangay'],$user['id']]);
			$message = 'Event created';
		}
	}
}

$csrf = generate_csrf_token();
$events = $pdo->prepare("SELECT * FROM events WHERE scope='barangay' AND barangay=? ORDER BY event_date DESC, id DESC");
$events->execute([$user['barangay']]);
$events = $events->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Barangay Events | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">My Barangay Events</h1>
			<p class="content-subtitle">Manage events for your barangay</p>
		</header>
		
		<div class="content-body">
			<?php if ($message): ?>
			<div class="alert alert-success animate-fade-in">
				<div class="alert-icon">
					<i class="fas fa-check-circle"></i>
				</div>
				<div class="alert-content">
					<strong>Success!</strong>
					<p><?= htmlspecialchars($message) ?></p>
				</div>
			</div>
			<?php endif; ?>
			
			<div class="grid">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-plus-circle"></i>
							Create New Event
						</h2>
						<p class="card-subtitle">Add a new event for your barangay</p>
					</div>
					<div class="card-body">
						<form method="post" class="form">
							<input type="hidden" name="csrf" value="<?= $csrf ?>">
							
							<div class="form-group">
								<label class="form-label">Event Title</label>
								<input type="text" name="title" class="form-input" required placeholder="Enter event title">
							</div>
							
							<div class="form-group">
								<label class="form-label">Description</label>
								<textarea name="description" class="form-input" rows="3" placeholder="Enter event description (optional)"></textarea>
							</div>
							
							<div class="form-row">
								<div class="form-group">
									<label class="form-label">Event Date</label>
									<input type="date" name="event_date" class="form-input" required>
								</div>
								<div class="form-group">
									<label class="form-label">Event Time</label>
									<input type="time" name="event_time" class="form-input" placeholder="Optional time">
								</div>
							</div>
							
							<div class="form-actions">
								<button type="submit" class="button primary">
									<i class="fas fa-save"></i>
									Create Event
								</button>
							</div>
						</form>
					</div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-calendar-alt"></i>
							All Barangay Events
						</h2>
						<p class="card-subtitle">Events created for your barangay</p>
					</div>
					<div class="card-body">
						<?php if (!empty($events)): ?>
						<div class="table-container">
							<table class="table">
								<thead>
									<tr>
										<th>Event Title</th>
										<th>Date</th>
										<th>Time</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($events as $e): ?>
									<tr>
										<td>
											<strong><?= htmlspecialchars($e['title']) ?></strong>
											<?php if ($e['description']): ?>
												<br><small class="text-muted"><?= htmlspecialchars($e['description']) ?></small>
											<?php endif; ?>
										</td>
										<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
										<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
										<td>
											<span class="badge <?= strtotime($e['event_date']) >= strtotime('today') ? 'badge-info' : 'badge-muted' ?>">
												<?= strtotime($e['event_date']) >= strtotime('today') ? 'Upcoming' : 'Past' ?>
											</span>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-calendar-plus"></i>
							</div>
							<h3>No Events Yet</h3>
							<p>Create your first event for the barangay using the form above.</p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


