<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

$senior_id = isset($_GET['senior_id']) ? (int)$_GET['senior_id'] : 0;

if (!$senior_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.title, e.event_date
    FROM attendance a
    JOIN events e ON a.event_id = e.id
    WHERE a.senior_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$senior_id]);
$events = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($events);
exit;
?>
