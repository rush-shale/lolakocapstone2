<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
	define('BASE_URL', '/lolakocapstone2/public');
}

require_role('user');
$started = function_exists('start_app_session') ? start_app_session() : null;
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

// CSRF for user actions (edit/delete/save events) - do not regenerate on every request
start_app_session();
$csrf = $_SESSION[CSRF_TOKEN_NAME] ?? generate_csrf_token();

// Provide a way for the client to refresh CSRF without reloading
if (isset($_GET['action']) && $_GET['action'] === 'csrf') {
	header('Content-Type: application/json');
	$token = $_SESSION[CSRF_TOKEN_NAME] ?? null;
	if (!$token) {
		$token = generate_csrf_token();
	}
	echo json_encode(['csrf' => $token]);
	exit;
}

// AJAX: fetch event details for modal
if (isset($_GET['action']) && $_GET['action'] === 'get_event') {
	header('Content-Type: application/json');
	$eventId = (int)($_GET['id'] ?? 0);
	if (!$eventId) { echo json_encode(['success' => false, 'message' => 'Invalid id']); exit; }
	$chk = $pdo->prepare("SELECT id, title, description, event_date, event_time, scope, barangay, created_by FROM events WHERE id = ? LIMIT 1");
	$chk->execute([$eventId]);
	$ev = $chk->fetch();
	if (!$ev || $ev['scope'] !== 'barangay' || strtolower($ev['barangay']) !== strtolower($user['barangay'])) {
		echo json_encode(['success' => false, 'message' => 'Not allowed']); exit;
	}
	echo json_encode(['success' => true, 'event' => $ev]); exit;
}

// Lightweight AJAX actions for user's own barangay events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json');
	$op = $_POST['op'] ?? '';
	$token = $_POST['csrf'] ?? '';
	if (!validate_csrf_token($token)) {
		echo json_encode(['success' => false, 'message' => 'Invalid session token. Please refresh and try again.']); exit;
	}
	if ($op === 'delete_event') {
		$eventId = (int)($_POST['event_id'] ?? 0);
		if (!$eventId) { echo json_encode(['success' => false, 'message' => 'Invalid event']); exit; }
		$chk = $pdo->prepare("SELECT id, created_by, scope, barangay FROM events WHERE id = ? LIMIT 1");
		$chk->execute([$eventId]);
		$ev = $chk->fetch();
		if (!$ev || $ev['scope'] !== 'barangay' || strtolower($ev['barangay']) !== strtolower($user['barangay'])) {
			echo json_encode(['success' => false, 'message' => 'Not allowed']); exit;
		}
		$pdo->prepare("DELETE FROM events WHERE id = ? LIMIT 1")->execute([$eventId]);
		echo json_encode(['success' => true]); exit;
	}
	if ($op === 'save_event') {
		$eventId = (int)($_POST['event_id'] ?? 0);
		$title = trim($_POST['title'] ?? '');
		$event_date = $_POST['event_date'] ?? '';
		$event_time = $_POST['event_time'] ?? null;
		$description = trim($_POST['description'] ?? '');
		if (!$eventId || $title === '' || $event_date === '') {
			echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit;
		}
		$chk = $pdo->prepare("SELECT id, created_by, scope, barangay FROM events WHERE id = ? LIMIT 1");
		$chk->execute([$eventId]);
		$ev = $chk->fetch();
		if (!$ev || $ev['scope'] !== 'barangay' || strtolower($ev['barangay']) !== strtolower($user['barangay'])) {
			echo json_encode(['success' => false, 'message' => 'Not allowed']); exit;
		}
		$pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ? WHERE id = ?")->execute([$title, $description ?: null, $event_date, $event_time ?: null, $eventId]);
		echo json_encode(['success' => true]); exit;
	}
	echo json_encode(['success' => false, 'message' => 'Invalid operation']); exit;
}

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

		/* My Barangay Events table - ensure Actions column is always accessible */
		.dash-barangay .table-container {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: thin;
			scrollbar-color: #cbd5e1 #f3f4f6;
		}

		.dash-barangay .table-container::-webkit-scrollbar {
			height: 8px;
		}

		.dash-barangay .table-container::-webkit-scrollbar-track {
			background: #f3f4f6;
			border-radius: 4px;
		}

		.dash-barangay .table-container::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 4px;
		}

		.dash-barangay .table-container::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}

		.dash-barangay .table-container table {
			min-width: 800px;
			width: 100%;
		}

		.dash-barangay .table-container th:nth-child(6),
		.dash-barangay .table-container td:nth-child(6) {
			min-width: 150px;
			white-space: nowrap;
		}

		.dash-barangay .table-container td:nth-child(6) {
			display: flex;
			gap: 0.5rem;
			align-items: center;
			justify-content: flex-start;
		}

		.dash-barangay .table-container .btn {
			min-width: 60px;
			padding: 0.5rem 0.75rem;
			font-size: 0.875rem;
			white-space: nowrap;
			touch-action: manipulation;
			cursor: pointer;
			flex-shrink: 0;
		}

		/* Mobile optimizations for My Barangay Events */
		@media (max-width: 768px) {
			.dash-barangay .table-container {
				overflow-x: scroll;
				-webkit-overflow-scrolling: touch;
				touch-action: pan-x;
				padding-bottom: 0.5rem;
			}

			.dash-barangay .table-container::-webkit-scrollbar {
				height: 10px;
			}

			.dash-barangay .table-container table {
				min-width: 700px;
			}

			.dash-barangay .table-container th:nth-child(6),
			.dash-barangay .table-container td:nth-child(6) {
				min-width: 140px;
				padding: 0.75rem 0.5rem;
			}

			.dash-barangay .table-container .btn {
				min-width: 55px;
				padding: 0.625rem 0.5rem;
				font-size: 0.8125rem;
			}
		}

		@media (max-width: 480px) {
			.dash-barangay .table-container {
				padding-bottom: 0.75rem;
			}

			.dash-barangay .table-container::-webkit-scrollbar {
				height: 12px;
			}

			.dash-barangay .table-container table {
				min-width: 650px;
			}

			.dash-barangay .table-container th:nth-child(6),
			.dash-barangay .table-container td:nth-child(6) {
				min-width: 130px;
				padding: 0.625rem 0.375rem;
			}

			.dash-barangay .table-container .btn {
				min-width: 50px;
				padding: 0.5rem 0.375rem;
				font-size: 0.75rem;
			}
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
										<th>Description</th>
										<th>Date</th>
										<th>Time</th>
										<th>Status</th>
										<th style="min-width:130px;">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($barangayEvents as $e): ?>
										<tr>
											<td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
											<td><?= $e['description'] ? nl2br(htmlspecialchars($e['description'])) : '<span style="color:#9ca3af;">No description</span>' ?></td>
											<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
											<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
											<td>
												<span class="badge badge-success">Upcoming</span>
											</td>
											<td>
												<?php if ((int)($e['created_by'] ?? 0) === (int)$user['id']): ?>
													<button class="btn btn-sm" onclick="userOpenEditEventModal(<?= (int)$e['id'] ?>)">Edit</button>
													<button class="btn btn-sm btn-danger" onclick="confirmUserDeleteEvent(<?= (int)$e['id'] ?>)">Delete</button>
												<?php else: ?>
													<small style="color:#6b7280;">—</small>
												<?php endif; ?>
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

	<!-- Modal for editing user's barangay event -->
	<div id="userEditEventModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:1000; align-items:center; justify-content:center;">
		<div style="background:#fff; width:95%; max-width:520px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.2); overflow:hidden;">
			<div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb;">
				<h3 style="margin:0; font-size:1.125rem;">Edit Event</h3>
				<button onclick="userCloseEditEventModal()" style="background:none; border:none; font-size:1.5rem; line-height:1; cursor:pointer;">&times;</button>
			</div>
			<form id="userEditEventForm" data-no-global-submit="true" method="post" action="" onsubmit="return userSaveEvent(event);" style="padding:1rem 1.25rem; display:flex; flex-direction:column; gap:0.75rem;">
				<input type="hidden" name="csrf" value="<?= $csrf ?>">
				<input type="hidden" name="event_id" id="ue_event_id">
				<div>
					<label for="ue_title" style="display:block; font-weight:600; margin-bottom:0.25rem;">Title *</label>
					<input id="ue_title" name="title" type="text" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px;">
				</div>
				<div>
					<label for="ue_description" style="display:block; font-weight:600; margin-bottom:0.25rem;">Description</label>
					<textarea id="ue_description" name="description" rows="3" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px; resize:vertical;" placeholder="Enter event description (optional)"></textarea>
				</div>
				<div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
					<div>
						<label for="ue_date" style="display:block; font-weight:600; margin-bottom:0.25rem;">Date *</label>
						<input id="ue_date" name="event_date" type="date" required style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px;">
					</div>
					<div>
						<label for="ue_time" style="display:block; font-weight:600; margin-bottom:0.25rem;">Time</label>
						<input id="ue_time" name="event_time" type="time" style="width:100%; padding:0.6rem; border:1px solid #d1d5db; border-radius:8px;">
						<small style="color:#6b7280;">Leave blank for All Day</small>
					</div>
				</div>
				<div style="display:flex; justify-content:flex-end; gap:0.5rem; padding-top:0.5rem;">
					<button type="button" class="btn" onclick="userCloseEditEventModal()">Cancel</button>
					<button type="submit" class="btn btn-primary">Update</button>
				</div>
			</form>
		</div>
	</div>

	<script>
		async function userFetchCsrf() {
			try {
				const res = await fetch('<?= BASE_URL ?>/user/dashboard.php?action=csrf&r=' + Date.now(), { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } });
				const data = await res.json();
				if (data && data.csrf) {
					const inp = document.querySelector('#userEditEventForm input[name="csrf"]');
					if (inp) inp.value = data.csrf;
					return data.csrf;
				}
			} catch (e) { /* ignore */ }
			// fallback to existing value
			const inp = document.querySelector('#userEditEventForm input[name="csrf"]');
			return inp ? inp.value : '';
		}

		function userOpenEditEventModal(id){
			fetch('<?= BASE_URL ?>/user/dashboard.php?action=get_event&id='+id, { credentials:'same-origin' })
				.then(r=>r.json()).then(resp=>{
					if(!resp || !resp.success){ alert(resp?.message || 'Failed to load event'); return; }
					const ev = resp.event;
					document.getElementById('ue_event_id').value = ev.id;
					document.getElementById('ue_title').value = ev.title || '';
					document.getElementById('ue_description').value = ev.description || '';
					document.getElementById('ue_date').value = ev.event_date || '';
					document.getElementById('ue_time').value = ev.event_time || '';
					const modal = document.getElementById('userEditEventModal');
					modal.style.display = 'flex';
					document.body.style.overflow = 'hidden';
				}).catch(()=>alert('Failed to load event'));
		}
		function userCloseEditEventModal(){
			const modal = document.getElementById('userEditEventModal');
			modal.style.display = 'none';
			document.body.style.overflow = '';
		}
		async function userSaveEvent(e){
			// Prevent native form submission; async handlers return a Promise which browsers treat as truthy
			if (e && typeof e.preventDefault === 'function') e.preventDefault();
			const token = await userFetchCsrf();
			const form = document.getElementById('userEditEventForm');
			const fd = new FormData(form);
			if (!fd.get('csrf') && token) fd.append('csrf', token);
			fd.append('op','save_event');
			// Put button in loading state, prevent double submit
			const submitBtn = form.querySelector('button[type=\"submit\"]');
			const originalText = submitBtn ? submitBtn.innerHTML : '';
			if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('loading'); submitBtn.innerHTML = '<span class=\"loading-spinner\"></span> Processing...'; }
			fetch('', { method:'POST', credentials:'same-origin', body: fd, cache: 'no-store', headers: { 'Accept': 'application/json' } })
				.then(async (r) => {
					// Try to parse JSON; if it fails but HTTP is OK, still proceed
					let data = null;
					try {
						data = await r.clone().json();
					} catch (_) {
						// Non-JSON (e.g., cached HTML) — fall back to OK status
					}
					if (!r.ok) {
						throw new Error(data?.message || 'Request failed');
					}
					if (!data || data.success !== true) {
						// If server didn't send JSON but request succeeded, treat as success
						if (!data) {
							// Bust cache on reload so UI reflects the update immediately
							window.location.href = window.location.pathname + window.location.search.replace(/([?&])r=\d+/, '') + (window.location.search ? '&' : '?') + 'r=' + Date.now();
							return;
						}
						alert(data?.message || 'Failed to update');
						return false;
					}
					// Cache-busting reload to avoid stale SW cache
					window.location.href = window.location.pathname + window.location.search.replace(/([?&])r=\d+/, '') + (window.location.search ? '&' : '?') + 'r=' + Date.now();
				})
				.catch(()=>alert('Failed to update'))
				.finally(()=>{ if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('loading'); submitBtn.innerHTML = originalText; } });
			return false;
		}

		async function confirmUserDeleteEvent(id) {
			if (!confirm('Delete this event?')) return;
			const form = new FormData();
			form.append('op','delete_event');
			const token = await userFetchCsrf();
			form.append('csrf', token || '<?= $csrf ?>');
			form.append('event_id', id);
			fetch('', { method:'POST', credentials:'same-origin', body: form })
			.then(r => r.json()).then(resp => {
				if (!resp || !resp.success) {
					alert(resp?.message || 'Failed to delete event');
					return;
				}
				location.reload();
			}).catch(() => alert('Failed to delete event.'));
		}
	</script>
</body>
</html>
