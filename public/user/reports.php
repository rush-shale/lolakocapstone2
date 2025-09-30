<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

function csv_download(string $filename, array $headers, array $rows): void {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	$out = fopen('php://output', 'w');
	fputcsv($out, $headers);
	foreach ($rows as $r) { fputcsv($out, $r); }
	fclose($out);
	exit;
}

$type = $_GET['type'] ?? '';
if ($type === 'seniors') {
	$stmt = $pdo->prepare("SELECT id, first_name, middle_name, last_name, age, barangay, benefits_received, life_status, category FROM seniors WHERE barangay=? ORDER BY last_name, first_name");
	$stmt->execute([$user['barangay']]);
	$rows = $stmt->fetchAll();
	csv_download('seniors_'.strtolower($user['barangay']).'.csv', ['ID','First Name','Middle Name','Last Name','Age','Barangay','Benefits Received','Life Status','Category'], $rows);
}
if ($type === 'attendance') {
	$stmt = $pdo->prepare("SELECT a.id, s.last_name, s.first_name, e.title, e.event_date, a.marked_at
		FROM attendance a
		JOIN seniors s ON s.id = a.senior_id
		JOIN events e ON e.id = a.event_id
		WHERE e.scope='barangay' AND e.barangay=?
		ORDER BY e.event_date DESC, a.marked_at DESC");
	$stmt->execute([$user['barangay']]);
	$rows = $stmt->fetchAll();
	csv_download('attendance_'.strtolower($user['barangay']).'.csv', ['ID','Senior Last Name','Senior First Name','Event Title','Event Date','Marked At'], $rows);
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Reports | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Reports</h1>
			<p class="content-subtitle">Generate and download reports for <?= htmlspecialchars($user['barangay']) ?></p>
		</header>
		
		<div class="content-body">
			<div class="grid grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-download"></i>
							Data Export
						</h2>
						<p class="card-subtitle">Download your barangay data in CSV format</p>
					</div>
					<div class="card-body">
						<div class="report-options">
							<a href="?type=seniors" class="button primary">
								<i class="fas fa-users"></i>
								Download Seniors Data
							</a>
							<a href="?type=attendance" class="button secondary">
								<i class="fas fa-calendar-check"></i>
								Download Attendance Data
							</a>
						</div>
					</div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-chart-bar"></i>
							Quick Statistics
						</h2>
						<p class="card-subtitle">Overview of your barangay data</p>
					</div>
					<div class="card-body">
						<?php
						$totalSeniors = $pdo->prepare("SELECT COUNT(*) FROM seniors WHERE barangay=? AND life_status='living'");
						$totalSeniors->execute([$user['barangay']]);
						$totalSeniorsCount = $totalSeniors->fetchColumn();
						
						$totalEvents = $pdo->prepare("SELECT COUNT(*) FROM events WHERE scope='barangay' AND barangay=?");
						$totalEvents->execute([$user['barangay']]);
						$totalEventsCount = $totalEvents->fetchColumn();
						
						$totalAttendance = $pdo->prepare("
							SELECT COUNT(*) FROM attendance a
							JOIN events e ON a.event_id = e.id
							WHERE e.scope='barangay' AND e.barangay=?
						");
						$totalAttendance->execute([$user['barangay']]);
						$totalAttendanceCount = $totalAttendance->fetchColumn();
						?>
						<div class="stats">
							<div class="stat">
								<div class="stat-icon">
									<i class="fas fa-users"></i>
								</div>
								<div class="stat-content">
									<h3>Total Seniors</h3>
									<p class="number"><?= $totalSeniorsCount ?></p>
								</div>
							</div>
							<div class="stat success">
								<div class="stat-icon">
									<i class="fas fa-calendar"></i>
								</div>
								<div class="stat-content">
									<h3>Total Events</h3>
									<p class="number"><?= $totalEventsCount ?></p>
								</div>
							</div>
							<div class="stat info">
								<div class="stat-icon">
									<i class="fas fa-check-circle"></i>
								</div>
								<div class="stat-content">
									<h3>Attendance Records</h3>
									<p class="number"><?= $totalAttendanceCount ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
</body>
</html>


