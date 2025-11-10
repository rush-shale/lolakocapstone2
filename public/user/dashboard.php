<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
	define('BASE_URL', '/lolakocapstone2/public');
}

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
	HAVING attendance_count > 0
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
	<?php 
	$cssPath = __DIR__ . '/../assets/government-portal.css';
	$cssVer = file_exists($cssPath) ? filemtime($cssPath) : time(); 
	?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Dashboard Grid Layout - Override CSS file */
		.dashboard-grid,
		.content .dashboard-grid,
		main.content .dashboard-grid {
			display: grid !important;
			grid-template-columns: 1fr 1fr 1fr !important;
			grid-template-rows: minmax(280px, 1fr) minmax(480px, 1.5fr) minmax(380px, 1fr) !important;
			grid-auto-rows: auto !important;
			gap: 0.75rem !important;
			padding: 0.75rem 0.5rem 0.75rem 0 !important;
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
			margin: 0 !important;
			max-height: none !important;
			overflow-y: visible !important;
			overflow-x: hidden !important;
		}

		.dash-stats {
			grid-column: 1 / 4;
			grid-row: 1;
			min-height: 280px;
			width: 100%;
		}

		.dash-barangay {
			grid-column: 1;
			grid-row: 2;
			min-height: 480px;
			width: 100%;
		}

		.dash-osca {
			grid-column: 2;
			grid-row: 2;
			min-height: 480px;
			width: 100%;
		}

		.dash-active-seniors {
			grid-column: 3;
			grid-row: 2;
			min-height: 480px;
			width: 100%;
		}

		.dash-past {
			grid-column: 1 / 4;
			grid-row: 3;
			min-height: 380px;
			width: 100%;
		}

		/* Card Styling */
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
			padding: 1.25rem 1.5rem;
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
		}

		/* Table Styling */
		.table-container {
			flex: 1;
			overflow: auto;
			-webkit-overflow-scrolling: touch;
			padding: 1.25rem 1.5rem;
		}

		.table-container table {
			width: 100%;
			table-layout: auto;
		}

		.table-container th,
		.table-container td {
			padding: 1.125rem 1.25rem;
			font-size: 1rem;
			white-space: nowrap;
			word-wrap: break-word;
		}

		.table-container th {
			font-weight: 600;
			font-size: 1.0625rem;
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
		.table-container td:nth-child(3),
		.table-container th:nth-child(4),
		.table-container td:nth-child(4) {
			min-width: 120px;
		}

		/* Empty State Styling */
		.empty-state {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 4rem 2rem;
			text-align: center;
			flex: 1;
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

		/* Layout Overrides */
		.content-header {
			display: none;
		}

		html, body {
			margin: 0;
			padding: 0;
			overflow-x: hidden;
			width: 100%;
			max-width: 100vw;
			box-sizing: border-box;
		}

		main.content {
			min-height: 100vh;
			overflow-y: auto;
			overflow-x: hidden;
			display: flex;
			flex-direction: column;
			padding: 0;
			margin-left: 280px;
			width: calc(100% - 280px);
			max-width: calc(100vw - 280px);
			box-sizing: border-box;
		}
		
		.dashboard-grid {
			max-width: 100%;
		}

		/* Statistics Grid */
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 2rem;
			padding: 2.5rem 1.5rem;
			align-content: center;
		}

		.stat-item {
			text-align: center;
			padding: 1.5rem 1rem;
			border-radius: 8px;
			background: #f9fafb;
			transition: transform 0.2s ease;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
		}

		.stat-item:hover {
			transform: translateY(-4px);
		}

		.stat-value {
			font-size: 5.5rem;
			font-weight: 700;
			margin-bottom: 1.5rem;
			line-height: 1;
		}

		.stat-label {
			font-size: 1.5rem;
			color: #374151;
			font-weight: 500;
		}

		/* Responsive: tablet/desktop adjustments */
		@media (max-width: 1280px) {
			.dashboard-grid,
			.content .dashboard-grid,
			main.content .dashboard-grid {
				grid-template-columns: 1fr 1fr !important;
				grid-template-rows: auto auto auto auto !important;
				gap: 1rem !important;
				padding: 0.75rem !important;
			}
			.dash-stats { grid-column: 1 / 3; grid-row: 1; min-height: 240px; }
			.dash-barangay { grid-column: 1; grid-row: 2; min-height: 420px; }
			.dash-osca { grid-column: 2; grid-row: 2; min-height: 420px; }
			.dash-active-seniors { grid-column: 1 / 3; grid-row: 3; min-height: 420px; }
			.dash-past { grid-column: 1 / 3; grid-row: 4; min-height: 320px; }
			.stats-grid { grid-template-columns: repeat(3, 1fr); gap: 1.25rem; padding: 2rem 1rem; }
			.stat-value { font-size: 4.5rem; }
			.stat-label { font-size: 1.25rem; }
		}

		/* Responsive: collapse to single column on small laptops/tablets */
		@media (max-width: 1024px) {
			main.content {
				margin-left: 0;
				width: 100%;
				max-width: 100%;
			}
			.dashboard-grid,
			.content .dashboard-grid,
			main.content .dashboard-grid {
				grid-template-columns: 1fr !important;
				grid-template-rows: auto auto auto auto auto !important;
				gap: 1rem !important;
				padding: 0.75rem !important;
			}
			.dash-stats { grid-column: 1; grid-row: 1; min-height: auto; }
			.dash-barangay { grid-column: 1; grid-row: 2; min-height: auto; }
			.dash-osca { grid-column: 1; grid-row: 3; min-height: auto; }
			.dash-active-seniors { grid-column: 1; grid-row: 4; min-height: auto; }
			.dash-past { grid-column: 1; grid-row: 5; min-height: auto; }
			.stats-grid { grid-template-columns: repeat(3, 1fr); gap: 1rem; padding: 1.5rem 1rem; }
			.card-title-section h2 { font-size: 1.25rem; }
			.card-title-section p { font-size: 0.95rem; }
		}

		/* Responsive: phones */
		@media (max-width: 768px) {
			.dashboard-grid,
			.content .dashboard-grid,
			main.content .dashboard-grid {
				gap: 0.75rem !important;
				padding: 0.75rem 0.5rem !important;
			}
			.modern-card-header {
				flex-wrap: wrap;
				gap: 0.5rem;
			}
			.card-title-section h2 { font-size: 1.125rem; }
			.card-title-section p { font-size: 0.9rem; }
			.stats-grid { grid-template-columns: 1fr; gap: 0.75rem; padding: 1rem; }
			.stat-item { padding: 1rem; }
			.stat-value { font-size: 3rem; margin-bottom: 0.5rem; }
			.stat-label { font-size: 1rem; }
			.table-container { padding: 0.75rem; }
			.table-container th, .table-container td { padding: 0.6rem 0.75rem; font-size: 0.95rem; }
		}

		/* Extra small phones */
		@media (max-width: 480px) {
			.card-title-section h2 { font-size: 1.05rem; }
			.card-title-section p { font-size: 0.85rem; }
			.stat-value { font-size: 2.4rem; }
			.stat-label { font-size: 0.95rem; }
			.table-container th, .table-container td { padding: 0.5rem 0.6rem; font-size: 0.9rem; }
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
										$attendanceCount = (int)($senior['attendance_count'] ?? 0);
										$lastAttendance = !empty($senior['last_attendance']) ? date('M d, Y', strtotime($senior['last_attendance'])) : 'Never';
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
