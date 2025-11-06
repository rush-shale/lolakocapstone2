<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (!$event_id) {
	header('Content-Type: application/json');
	echo json_encode([]);
	exit;
}

// Ensure the event belongs to the user's barangay (or is admin-scoped) for safety
$eventCheck = $pdo->prepare("SELECT id, scope, barangay, title, event_date, event_time FROM events WHERE id = ?");
$eventCheck->execute([$event_id]);
$event = $eventCheck->fetch();

if (!$event) {
	header('Content-Type: application/json');
	echo json_encode([]);
	exit;
}

if ($event['scope'] === 'barangay' && $event['barangay'] !== $user['barangay']) {
	header('HTTP/1.1 403 Forbidden');
	echo json_encode(['error' => 'Forbidden']);
	exit;
}

$stmt = $pdo->prepare("
	SELECT 
		s.id,
		s.first_name,
		s.middle_name,
		s.last_name,
		s.ext_name,
		s.age,
		s.sex,
		s.osca_id_no,
		a.marked_at
	FROM attendance a
	JOIN seniors s ON a.senior_id = s.id
	WHERE a.event_id = ?
	ORDER BY s.last_name, s.first_name
");
$stmt->execute([$event_id]);
$attendees = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode([
	'event' => [
		'id' => (int)$event['id'],
		'title' => $event['title'],
		'event_date' => $event['event_date'],
		'event_time' => $event['event_time']
	],
	'attendees' => $attendees
]);
exit;
?>


