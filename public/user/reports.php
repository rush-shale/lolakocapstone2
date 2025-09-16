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
	<title>Reports | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<h1>Reports</h1>
		<ul>
			<li><a href="?type=seniors">Download My Seniors CSV</a></li>
			<li><a href="?type=attendance">Download My Attendance CSV</a></li>
		</ul>
	</main>
</body>
</html>


