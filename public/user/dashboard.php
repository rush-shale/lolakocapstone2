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

// Latest recent event with attendees (within last 7 days) for this barangay
$latestEventStmt = $pdo->prepare("
    SELECT * FROM events 
    WHERE scope='barangay' 
      AND barangay = ? 
      AND event_date <= CURDATE()
      AND event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY event_date DESC, id DESC
    LIMIT 1
");
$latestEventStmt->execute([$user['barangay']]);
$latestEvent = $latestEventStmt->fetch();

$latestEventAttendees = [];
if ($latestEvent) {
	$attStmt = $pdo->prepare("SELECT s.first_name, s.middle_name, s.last_name, s.ext_name, a.marked_at FROM attendance a JOIN seniors s ON a.senior_id = s.id WHERE a.event_id = ? ORDER BY s.last_name, s.first_name");
	$attStmt->execute([$latestEvent['id']]);
	$latestEventAttendees = $attStmt->fetchAll();
}

// Get top active seniors based on attendance count (last 90 days)
$topActiveSeniorsStmt = $pdo->prepare("
	SELECT 
		s.id,
		s.first_name,
		s.middle_name,
		s.last_name,
		s.ext_name,
		s.age,
		COUNT(DISTINCT a.id) AS attendance_count,
		MAX(a.marked_at) AS last_attendance
	FROM seniors s
	LEFT JOIN attendance a ON s.id = a.senior_id
	LEFT JOIN events e ON a.event_id = e.id 
		AND e.scope = 'barangay' 
		AND e.barangay = ?
		AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
	WHERE s.barangay = ? 
		AND s.life_status = 'living'
	GROUP BY s.id, s.first_name, s.middle_name, s.last_name, s.ext_name, s.age
	ORDER BY attendance_count DESC, last_attendance DESC, s.last_name ASC, s.first_name ASC
	LIMIT 10
");
$topActiveSeniorsStmt->execute([$user['barangay'], $user['barangay']]);
$topActiveSeniors = $topActiveSeniorsStmt->fetchAll();



?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Staff Dashboard | SeniorCare Information System</title>
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Responsive Dashboard Styles - Matching Admin Dashboard */
		.dashboard-grid {
			display: grid;
			grid-template-columns: 1.5fr 2.5fr;
			grid-template-rows: 1fr 1fr 1fr;
			gap: 0.5rem;
			padding: 0.5rem;
			height: calc(100vh - 40px);
			max-height: calc(100vh - 40px);
			overflow: hidden;
			width: 100%;
			box-sizing: border-box;
		}

		.dash-barangay {
			grid-column: 1;
			grid-row: 1 / 4;
			min-height: 0;
			overflow: hidden;
		}

		.dash-osca {
			grid-column: 2;
			grid-row: 1;
			min-height: 0;
			overflow: hidden;
		}

		.dash-past {
			grid-column: 2;
			grid-row: 2;
			min-height: 0;
			overflow: hidden;
		}

		.dash-active-seniors {
			grid-column: 2;
			grid-row: 3;
			min-height: 0;
			overflow: hidden;
		}

		/* Modern Card Styling */
		.modern-card {
			background: #ffffff;
			border: 1px solid rgba(30, 58, 138, 0.1);
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			border-radius: 12px;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			height: 100%;
			width: 100%;
			transition: all 0.3s ease;
		}

		.modern-card:hover {
			box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
		}

		.modern-card-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 1rem;
			border-bottom: 1px solid rgba(30, 58, 138, 0.1);
			background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
		}

		.card-title-section {
			flex: 1;
		}

		.card-title-section h2 {
			font-size: 1.1rem;
			font-weight: 600;
			color: var(--text-primary);
			margin: 0 0 0.25rem 0;
		}

		.card-title-section p {
			font-size: 0.85rem;
			color: var(--text-muted);
			margin: 0;
		}

		.modern-card-body {
			padding: 0;
			flex: 1;
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}

		.table-container {
			flex: 1;
			overflow-y: auto;
			overflow-x: hidden;
			-webkit-overflow-scrolling: touch;
		}

		.table-container table {
			width: 100%;
			table-layout: fixed;
		}

		.table-container th,
		.table-container td {
			word-wrap: break-word;
			overflow-wrap: break-word;
		}

		.table-container th:nth-child(1),
		.table-container td:nth-child(1) {
			width: 35%;
		}

		.table-container th:nth-child(2),
		.table-container td:nth-child(2) {
			width: 25%;
		}

		.table-container th:nth-child(3),
		.table-container td:nth-child(3) {
			width: 20%;
		}

		.table-container th:nth-child(4),
		.table-container td:nth-child(4) {
			width: 20%;
		}

		/* Hide content-header on dashboard view */
		.content-header {
			display: none;
		}

		/* Remove any default spacing */
		body {
			margin: 0;
			padding: 0;
		}

		/* Ensure main content takes full height */
		main.content {
			height: 100vh;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			padding: 0;
			margin: 0;
			width: 100%;
		}

		.content-body {
			flex: 1;
			overflow: hidden;
			padding: 0;
			margin: 0;
			width: 100%;
		}

		/* Responsive Design */
		@media (max-width: 1200px) {
			.dashboard-grid {
				grid-template-columns: 1.5fr 2.5fr;
				grid-template-rows: 1fr 1fr 1fr;
				gap: 0.5rem;
				padding: 0.5rem;
				height: calc(100vh - 40px);
				max-height: calc(100vh - 40px);
				width: 100%;
			}

			.dash-barangay {
				grid-column: 1;
				grid-row: 1 / 4;
			}

			.dash-osca {
				grid-column: 2;
				grid-row: 1;
				min-height: 0;
				overflow: hidden;
			}

			.dash-past {
				grid-column: 2;
				grid-row: 2;
				min-height: 0;
				overflow: hidden;
			}

			.dash-active-seniors {
				grid-column: 2;
				grid-row: 3;
				min-height: 0;
				overflow: hidden;
			}
		}

		@media (max-width: 768px) {
			.dashboard-grid {
				grid-template-columns: 1fr;
				grid-template-rows: auto auto auto auto;
				gap: 0.75rem;
				padding: 0.75rem;
				height: auto;
				max-height: none;
				overflow: visible;
			}

			.dash-barangay {
				grid-column: 1;
				grid-row: 1;
			}

			.dash-osca {
				grid-column: 1;
				grid-row: 2;
			}

			.dash-past {
				grid-column: 1;
				grid-row: 3;
			}

			.dash-active-seniors {
				grid-column: 1;
				grid-row: 4;
			}
		}

			.modern-card-header {
				padding: 1rem;
			}

			.card-title-section h2 {
				font-size: 1rem;
			}

			.card-title-section p {
				font-size: 0.8rem;
			}
		}

		@media (max-width: 480px) {
			.dashboard-grid {
				padding: 0.5rem;
				gap: 0.5rem;
			}

			.modern-card-header {
				padding: 0.75rem;
			}

			.card-title-section h2 {
				font-size: 0.9rem;
			}

			.card-title-section p {
				font-size: 0.75rem;
			}
		}

		/* Tablet landscape */
		@media (min-width: 769px) and (max-width: 1024px) {
			.dashboard-grid {
				grid-template-columns: 1.5fr 2.5fr;
				grid-template-rows: 1fr 1fr 1fr;
				gap: 0.5rem;
				padding: 0.5rem;
				height: calc(100vh - 40px);
				max-height: calc(100vh - 40px);
				width: 100%;
			}

			.dash-barangay {
				grid-column: 1;
				grid-row: 1 / 4;
			}

			.dash-osca {
				grid-column: 2;
				grid-row: 1;
			}

			.dash-past {
				grid-column: 2;
				grid-row: 2;
			}

			.dash-active-seniors {
				grid-column: 2;
				grid-row: 3;
			}
		}

		/* Large screens optimization */
		@media (min-width: 1400px) {
			.dashboard-grid {
				max-width: 1400px;
				margin: 0 auto;
			}
		}

		/* Mobile adjustments for main content */
		@media (max-width: 768px) {
			main.content {
				height: auto;
				overflow: visible;
			}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<div class="dashboard-grid">
			<!-- My Barangay Events Card -->
			<div class="card dash-barangay modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">📅 My Barangay Events</h2>
						<p class="card-subtitle">Upcoming events for <?= htmlspecialchars($user['barangay']) ?></p>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<?php if (!empty($barangayEvents)): ?>
						<div class="table-container table-scroll">
							<table class="modern-table">
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
											<td>
												<span class="badge badge-success">Upcoming</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-calendar-alt"></i>
							</div>
							<h3>No Upcoming Events</h3>
							<p>No upcoming barangay events scheduled.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- OSCA Head Events Card -->
			<div class="card dash-osca modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">🏛️ OSCA Head Events</h2>
						<p class="card-subtitle">Events created by the OSCA Head</p>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<?php if (!empty($adminEvents)): ?>
						<div class="table-container table-scroll">
							<table class="modern-table">
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
											<td>
												<span class="badge badge-success">Upcoming</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-building"></i>
							</div>
							<h3>No Upcoming Events</h3>
							<p>No upcoming OSCA events scheduled.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Past Events Card -->
			<div class="card dash-past modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">📜 Past Events</h2>
						<p class="card-subtitle">Past events for your barangay</p>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<?php if (!empty($recentPastEvents)): ?>
						<div class="table-container table-scroll">
							<table class="modern-table">
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
											<td>
												<span class="badge badge-muted">Completed</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-history"></i>
							</div>
							<h3>No Past Events</h3>
							<p>No past events found.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Top Active Seniors Card -->
			<div class="card dash-active-seniors modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">⭐ Top Active Seniors</h2>
						<p class="card-subtitle">Most active seniors in <?= htmlspecialchars($user['barangay']) ?></p>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<?php if (!empty($topActiveSeniors)): ?>
						<div class="table-container table-scroll">
							<table class="modern-table">
								<thead>
									<tr>
										<th>Rank</th>
										<th>Name</th>
										<th>Age</th>
										<th>Attendances</th>
										<th>Last Attendance</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$rank = 1;
									foreach ($topActiveSeniors as $senior): 
										$fullName = trim(htmlspecialchars($senior['first_name'] . ' ' . ($senior['middle_name'] ? $senior['middle_name'] . ' ' : '') . $senior['last_name'] . ($senior['ext_name'] ? ' ' . $senior['ext_name'] : '')));
										$attendanceCount = (int)$senior['attendance_count'];
										$lastAttendance = $senior['last_attendance'] ? date('M d, Y', strtotime($senior['last_attendance'])) : 'Never';
									?>
										<tr>
											<td>
												<?php if ($rank <= 3): ?>
													<span class="badge" style="background: <?= $rank == 1 ? '#FFD700' : ($rank == 2 ? '#C0C0C0' : '#CD7F32') ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600;">
														<?= $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉') ?>
													</span>
												<?php else: ?>
													<span style="color: var(--text-muted); font-weight: 600;">#<?= $rank ?></span>
												<?php endif; ?>
											</td>
											<td><strong><?= $fullName ?></strong></td>
											<td><?= htmlspecialchars($senior['age'] ?? 'N/A') ?></td>
											<td>
												<span class="badge badge-info"><?= $attendanceCount ?> event<?= $attendanceCount != 1 ? 's' : '' ?></span>
											</td>
											<td><?= $lastAttendance ?></td>
										</tr>
									<?php 
									$rank++;
									endforeach; 
									?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-users"></i>
							</div>
							<h3>No Active Seniors</h3>
							<p>No attendance records found for seniors in your barangay.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>
