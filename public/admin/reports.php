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
	<title>Reports | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<h1>Reports</h1>
		<ul>
			<li><a href="?type=seniors">Download Seniors CSV</a></li>
			<li><a href="?type=attendance">Download Attendance CSV</a></li>
		</ul>
	</main>
</body>
</html>


