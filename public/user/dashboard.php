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
	<style>
		/* Responsive User Dashboard Styles */
		.page-header {
			padding: 1.5rem;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			margin-bottom: 1.5rem;
			border-radius: 0 0 12px 12px;
		}

		.page-header h1 {
			margin: 0 0 0.5rem 0;
			font-size: 1.75rem;
			font-weight: 700;
		}

		.page-header p {
			margin: 0;
			opacity: 0.9;
			font-size: 1rem;
		}

		.grid-2 {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 1.5rem;
			padding: 1.5rem;
		}

		/* Mobile Responsive */
		@media (max-width: 768px) {
			.page-header {
				padding: 1rem;
				margin-bottom: 1rem;
			}

			.page-header h1 {
				font-size: 1.5rem;
			}

			.page-header p {
				font-size: 0.9rem;
			}

			.grid-2 {
				grid-template-columns: 1fr;
				gap: 1rem;
				padding: 1rem;
			}

			.card {
				margin: 0;
			}

			.card-header h2 {
				font-size: 1.1rem;
			}

			.card-header p {
				font-size: 0.85rem;
			}

			/* Table responsive */
			.table-container {
				overflow-x: auto;
			}

			table {
				min-width: 600px;
			}

			table th,
			table td {
				padding: 0.5rem 0.25rem;
				font-size: 0.8rem;
			}

			/* Quick actions mobile */
			.button {
				padding: 0.75rem 1rem;
				font-size: 0.9rem;
				text-align: center;
			}
		}

		@media (max-width: 480px) {
			.page-header {
				padding: 0.75rem;
			}

			.page-header h1 {
				font-size: 1.25rem;
			}

			.page-header p {
				font-size: 0.8rem;
			}

			.grid-2 {
				padding: 0.5rem;
				gap: 0.75rem;
			}

			.card-header {
				padding: 1rem;
			}

			.card-header h2 {
				font-size: 1rem;
			}

			.card-header p {
				font-size: 0.8rem;
			}

			.card-body {
				padding: 0.75rem;
			}

			/* Table mobile */
			table {
				min-width: 500px;
			}

			table th,
			table td {
				padding: 0.4rem 0.2rem;
				font-size: 0.75rem;
			}

			/* Badge mobile */
			.badge {
				font-size: 0.7rem;
				padding: 0.2rem 0.4rem;
			}

			/* Quick actions mobile */
			.button {
				padding: 0.6rem 0.8rem;
				font-size: 0.85rem;
			}
		}

		/* Tablet landscape */
		@media (min-width: 769px) and (max-width: 1024px) {
			.grid-2 {
				grid-template-columns: 1fr;
				gap: 1.25rem;
			}

			.card {
				margin-bottom: 0;
			}
		}

		/* Large screens optimization */
		@media (min-width: 1200px) {
			.grid-2 {
				grid-template-columns: repeat(2, 1fr);
				max-width: 1200px;
				margin: 0 auto;
			}
		}

		/* Extra large screens */
		@media (min-width: 1400px) {
			.grid-2 {
				max-width: 1400px;
			}
		}

		/* Print styles */
		@media print {
			.page-header {
				background: none !important;
				color: black !important;
			}

			.button {
				display: none;
			}

			.card {
				box-shadow: none;
				border: 1px solid #ccc;
			}
		}

		/* High contrast mode */
		@media (prefers-contrast: high) {
			.card {
				border: 2px solid #000;
			}

			.badge {
				border: 1px solid #000;
			}
		}

		/* Reduced motion */
		@media (prefers-reduced-motion: reduce) {
			* {
				animation-duration: 0.01ms !important;
				animation-iteration-count: 1 !important;
				transition-duration: 0.01ms !important;
			}
		}
	</style>
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


