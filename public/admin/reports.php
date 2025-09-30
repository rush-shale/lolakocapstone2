<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

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
	$rows = $pdo->query("SELECT id, first_name, middle_name, last_name, age, barangay, benefits_received, life_status, category FROM seniors ORDER BY last_name, first_name")->fetchAll();
	csv_download('seniors.csv', ['ID','First Name','Middle Name','Last Name','Age','Barangay','Benefits Received','Life Status','Category'], $rows);
}
if ($type === 'attendance') {
	$rows = $pdo->query("SELECT a.id, s.last_name, s.first_name, e.title, e.event_date, a.marked_at
		FROM attendance a
		JOIN seniors s ON s.id = a.senior_id
		JOIN events e ON e.id = a.event_id
		ORDER BY e.event_date DESC, a.marked_at DESC")->fetchAll();
	csv_download('attendance.csv', ['ID','Senior Last Name','Senior First Name','Event Title','Event Date','Marked At'], $rows);
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Reports & Analytics | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Reports & Analytics</h1>
			<p class="content-subtitle">Generate and download system reports</p>
		</header>
		
		<div class="content-body">
			<div class="grid grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-download"></i>
							Data Export
						</h2>
						<p class="card-subtitle">Download system data in CSV format</p>
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
						<p class="card-subtitle">Overview of system data</p>
					</div>
					<div class="card-body">
						<?php
						$totalSeniors = $pdo->query("SELECT COUNT(*) FROM seniors")->fetchColumn();
						$livingSeniors = $pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living'")->fetchColumn();
						$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
						$totalAttendance = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
						?>
						<div class="stats">
							<div class="stat">
								<div class="stat-icon">
									<i class="fas fa-users"></i>
								</div>
								<div class="stat-content">
									<h3>Total Seniors</h3>
									<p class="number"><?= $totalSeniors ?></p>
								</div>
							</div>
							<div class="stat success">
								<div class="stat-icon">
									<i class="fas fa-heart"></i>
								</div>
								<div class="stat-content">
									<h3>Living Seniors</h3>
									<p class="number"><?= $livingSeniors ?></p>
								</div>
							</div>
							<div class="stat info">
								<div class="stat-icon">
									<i class="fas fa-calendar"></i>
								</div>
								<div class="stat-content">
									<h3>Total Events</h3>
									<p class="number"><?= $totalEvents ?></p>
								</div>
							</div>
							<div class="stat warning">
								<div class="stat-icon">
									<i class="fas fa-check-circle"></i>
								</div>
								<div class="stat-content">
									<h3>Attendance Records</h3>
									<p class="number"><?= $totalAttendance ?></p>
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


