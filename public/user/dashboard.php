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

// Get statistics for dashboard
$stmtTotalSeniors = $pdo->prepare("SELECT COUNT(*) FROM seniors WHERE barangay = ? AND life_status = 'living'");
$stmtTotalSeniors->execute([$user['barangay']]);
$totalSeniors = (int)$stmtTotalSeniors->fetchColumn();

$stmtTotalEvents = $pdo->prepare("SELECT COUNT(*) FROM events WHERE scope = 'barangay' AND barangay = ?");
$stmtTotalEvents->execute([$user['barangay']]);
$totalEvents = (int)$stmtTotalEvents->fetchColumn();

$stmtTotalAttendances = $pdo->prepare("
	SELECT COUNT(*) 
	FROM attendance a
	JOIN events e ON a.event_id = e.id
	WHERE e.scope = 'barangay' AND e.barangay = ?
");
$stmtTotalAttendances->execute([$user['barangay']]);
$totalAttendances = (int)$stmtTotalAttendances->fetchColumn();



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
		/* Responsive Dashboard Styles - Enhanced with Bigger Cards */
		.dashboard-grid {
			display: grid;
			grid-template-columns: 1fr 1fr 1fr;
			grid-template-rows: auto auto auto;
			gap: 2rem;
			padding: 2rem;
			min-height: 100vh;
			width: 100%;
			max-width: 100%;
			box-sizing: border-box;
			margin: 0;
		}

		.dash-stats {
			grid-column: 1 / 4;
			grid-row: 1;
			min-height: 200px;
			width: 100%;
			min-width: 0;
		}

		.dash-barangay {
			grid-column: 1;
			grid-row: 2;
			min-height: 500px;
			width: 100%;
			min-width: 0;
		}

		.dash-osca {
			grid-column: 2;
			grid-row: 2;
			min-height: 500px;
			width: 100%;
			min-width: 0;
		}

		.dash-active-seniors {
			grid-column: 3;
			grid-row: 2;
			min-height: 500px;
			width: 100%;
			min-width: 0;
		}

		.dash-past {
			grid-column: 1 / 4;
			grid-row: 3;
			min-height: 400px;
			width: 100%;
			min-width: 0;
		}

		/* Modern Card Styling - Enhanced */
		.modern-card {
			background: #ffffff;
			border: 1px solid #e5e7eb;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
			border-radius: 12px;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			height: 100%;
			width: 100%;
			min-width: 0;
			transition: box-shadow 0.3s ease;
		}

		.modern-card:hover {
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
		}

		.modern-card-header {
			display: flex;
			align-items: center;
			padding: 1.75rem 2rem;
			border-bottom: 1px solid #e5e7eb;
			background: linear-gradient(to right, #f9fafb, #ffffff);
		}

		.card-title-section {
			flex: 1;
			display: flex;
			align-items: center;
			gap: 1rem;
		}

		.card-title-section::before {
			content: '';
			width: 10px;
			height: 10px;
			background: #3b82f6;
			border-radius: 3px;
			flex-shrink: 0;
		}

		.card-title-section h2 {
			font-size: 1.375rem;
			font-weight: 700;
			color: #1e3a8a;
			margin: 0 0 0.25rem 0;
		}

		.card-title-section p {
			font-size: 1rem;
			color: #6b7280;
			margin: 0;
		}

		.modern-card-body {
			padding: 0;
			flex: 1;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			width: 100%;
			min-width: 0;
		}

		.table-container {
			flex: 1;
			overflow-y: auto;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			padding: 1rem;
			min-width: 0;
		}

		.table-container table {
			width: 100%;
			table-layout: auto;
			min-width: 100%;
		}

		.table-container th,
		.table-container td {
			padding: 1.125rem 1.25rem;
			font-size: 1rem;
			word-wrap: break-word;
			overflow-wrap: break-word;
		}

		.table-container th {
			font-weight: 600;
			font-size: 1.0625rem;
		}

		.table-container th,
		.table-container td {
			white-space: nowrap;
		}

		.table-container th:nth-child(1),
		.table-container td:nth-child(1) {
			min-width: 180px;
		}

		.table-container th:nth-child(2),
		.table-container td:nth-child(2) {
			min-width: 140px;
		}

		.table-container th:nth-child(3),
		.table-container td:nth-child(3) {
			min-width: 120px;
		}

		.table-container th:nth-child(4),
		.table-container td:nth-child(4) {
			min-width: 120px;
		}

		/* Empty State Styling - Enhanced */
		.empty-state {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 4rem 2rem;
			text-align: center;
			min-height: 350px;
		}

		.empty-icon {
			font-size: 4rem;
			color: #d1d5db;
			margin-bottom: 1.5rem;
		}

		.empty-state h3 {
			font-size: 1.5rem;
			font-weight: 600;
			color: #374151;
			margin-bottom: 0.75rem;
		}

		.empty-state p {
			font-size: 1.125rem;
			color: #6b7280;
		}

		/* Hide content-header on dashboard view */
		.content-header {
			display: none;
		}

		/* Remove any default spacing */
		body {
			margin: 0;
			padding: 0;
			background: #f3f4f6;
		}

		/* Ensure main content takes full height and width */
		main.content {
			min-height: 100vh !important;
			overflow-y: auto !important;
			overflow-x: hidden !important;
			display: flex !important;
			flex-direction: column !important;
			padding: 0 !important;
			margin-left: 280px !important;
			margin-right: 0 !important;
			margin-top: 0 !important;
			margin-bottom: 0 !important;
			width: calc(100% - 280px) !important;
			max-width: none !important;
		}

		/* Override any global content-body constraints */
		main.content > * {
			width: 100% !important;
			max-width: 100% !important;
		}

		.content-body {
			flex: 1;
			overflow: visible;
			padding: 0 !important;
			margin: 0 !important;
			width: 100% !important;
			max-width: 100% !important;
		}

		/* Ensure dashboard grid extends to right edge */
		.dashboard-grid {
			margin: 0 !important;
			padding: 2rem !important;
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
			margin-right: 0 !important;
		}

		/* Override any global container constraints */
		body > main.content,
		body > main.content > .dashboard-grid {
			width: 100% !important;
			max-width: 100% !important;
		}

		/* Force full width - override any global CSS max-width constraints */
		main.content {
			margin: 0 0 0 280px !important;
		}

		/* Ensure no centering or max-width constraints from global CSS */
		.content,
		main.content,
		main.content .dashboard-grid {
			max-width: 100% !important;
			margin-left: 280px !important;
			margin-right: 0 !important;
		}

		/* Enhanced Statistics Card */
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 3rem;
			padding: 3rem 2.5rem;
		}

		.stat-item {
			text-align: center;
			padding: 1rem;
			border-radius: 8px;
			background: #f9fafb;
			transition: transform 0.2s ease;
		}

		.stat-item:hover {
			transform: translateY(-4px);
		}

		.stat-value {
			font-size: 4.5rem;
			font-weight: 700;
			margin-bottom: 1rem;
			line-height: 1;
		}

		.stat-label {
			font-size: 1.25rem;
			color: #374151;
			font-weight: 500;
		}

	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<div class="dashboard-grid">
			<!-- Statistics Summary Card -->
			<div class="card dash-stats modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<div>
							<h2 class="card-title">Barangay Overview</h2>
							<p class="card-subtitle">Summary Statistics of <?= htmlspecialchars($user['barangay']) ?></p>
						</div>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<div class="stats-grid">
						<div class="stat-item">
							<div class="stat-value" style="color: #7c3aed;"><?= $totalSeniors ?></div>
							<div class="stat-label">Total Seniors</div>
						</div>
						<div class="stat-item">
							<div class="stat-value" style="color: #10b981;"><?= $totalEvents ?></div>
							<div class="stat-label">Total Events</div>
						</div>
						<div class="stat-item">
							<div class="stat-value" style="color: #3b82f6;"><?= $totalAttendances ?></div>
							<div class="stat-label">Total Attendance</div>
						</div>
					</div>
				</div>
			</div>

			<!-- My Barangay Events Card -->
			<div class="card dash-barangay modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<div>
							<h2 class="card-title">My Barangay Events</h2>
							<p class="card-subtitle">Upcoming events for <?= strtolower(htmlspecialchars($user['barangay'])) ?></p>
						</div>
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
						<div>
							<h2 class="card-title">OSCA Head Events</h2>
							<p class="card-subtitle">Events created by OSCA Head</p>
						</div>
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

			<!-- Top Active Seniors Card -->
			<div class="card dash-active-seniors modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<div>
							<h2 class="card-title">Top Active Seniors</h2>
							<p class="card-subtitle">Most Active Senior in <?= htmlspecialchars($user['barangay']) ?></p>
						</div>
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
										<th>Attendance</th>
										<th>Last Atten.</th>
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

			<!-- Past Events Card -->
			<div class="card dash-past modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<div>
							<h2 class="card-title">Past Events</h2>
							<p class="card-subtitle">Past events for your baragay</p>
						</div>
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
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>
