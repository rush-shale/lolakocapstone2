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
	<title>Staff Dashboard | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<div class="page-header">
			<h1>Welcome back, <?= htmlspecialchars($user['name']) ?></h1>
			<p>SeniorCare Information System - Staff Portal for <?= htmlspecialchars($user['barangay']) ?></p>
		</div>
		


		<div class="grid grid-2">
			<div class="card">
				<div class="card-header">
					<h2>📅 My Barangay Events</h2>
					<p>Events I created for <?= htmlspecialchars($user['barangay']) ?></p>
				</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Event</th>
								<th>Date</th>
								<th>Time</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($barangayEvents as $e): ?>
								<tr>
									<td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
									<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
									<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
									<td><span class="badge badge-success">Upcoming</span></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($barangayEvents)): ?>
								<tr>
									<td colspan="4" style="text-align: center; padding: 2rem; color: var(--muted);">
										No upcoming barangay events scheduled.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h2>🏛️ OSCA Head Events</h2>
					<p>Events created by the OSCA Head</p>
				</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Event</th>
								<th>Date</th>
								<th>Time</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($adminEvents as $e): ?>
								<tr>
									<td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
									<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
									<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
									<td><span class="badge badge-success">Upcoming</span></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($adminEvents)): ?>
								<tr>
									<td colspan="4" style="text-align: center; padding: 2rem; color: var(--muted);">
										No upcoming OSCA events scheduled.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h2>📜 Recent Past Events</h2>
					<p>Events I created that have already occurred</p>
				</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Event</th>
								<th>Date</th>
								<th>Time</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($recentPastEvents as $e): ?>
								<tr>
									<td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
									<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
									<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
									<td><span class="badge badge-muted">Completed</span></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($recentPastEvents)): ?>
								<tr>
									<td colspan="4" style="text-align: center; padding: 2rem; color: var(--muted);">
										No past events found.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h2>⚡ Quick Actions</h2>
					<p>Frequently used tasks</p>
				</div>
				<div style="display: flex; flex-direction: column; gap: 0.75rem;">
					<a href="<?= BASE_URL ?>/user/events.php" class="button">📅 Create Event</a>
					<a href="<?= BASE_URL ?>/user/osca_events.php" class="button">🏛️ View OSCA Events</a>
					<a href="<?= BASE_URL ?>/user/attendance.php" class="button secondary">✅ Mark Attendance</a>
					<a href="<?= BASE_URL ?>/user/seniors.php" class="button secondary">👥 Manage Seniors</a>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


