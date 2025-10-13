<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

// Get comprehensive statistics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living'")->fetchColumn();
$localSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND category = 'local'")->fetchColumn();
$nationalSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND category = 'national'")->fetchColumn();
$deceasedSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'deceased'")->fetchColumn();
$benefitsPending = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND benefits_received = 0")->fetchColumn();
$benefitsReceived = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND benefits_received = 1")->fetchColumn();
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE scope = 'admin'")->fetchColumn();
$upcomingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE scope = 'admin' AND event_date >= CURDATE()")
	->fetchColumn();

// Get seniors data for dashboard sections
$allSeniors = $pdo->query("\n    SELECT s.*, s.barangay as barangay_name \n    FROM seniors s \n    WHERE s.life_status = 'living'\n    ORDER BY s.created_at DESC \n    LIMIT 10\n")->fetchAll();

$localSeniorsList = $pdo->query("\n    SELECT s.*, s.barangay as barangay_name \n    FROM seniors s \n    WHERE s.category = 'local' AND s.life_status = 'living'\n    ORDER BY s.created_at DESC \n    LIMIT 10\n")->fetchAll();

$nationalSeniorsList = $pdo->query("\n    SELECT s.*, s.barangay as barangay_name \n    FROM seniors s \n    WHERE s.category = 'national' AND s.life_status = 'living'\n    ORDER BY s.created_at DESC \n    LIMIT 10\n")->fetchAll();

// Get upcoming events with organizer name
$upcomingEventsList = $pdo->query("\n    SELECT e.*, u.name AS organizer_name\n    FROM events e\n    LEFT JOIN users u ON e.created_by = u.id\n    WHERE e.event_date >= CURDATE()\n    ORDER BY e.event_date ASC \n    LIMIT 5\n")->fetchAll();

// Graph data for current year registrations
$year = (int)date('Y');
$stmtMonthly = $pdo->prepare('SELECT MONTH(created_at) AS m, COUNT(*) AS c FROM seniors WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)');
$stmtMonthly->execute([$year]);
$monthlyRaw = $stmtMonthly->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyCounts = [];
for ($i = 1; $i <= 12; $i++) { $monthlyCounts[] = (int)($monthlyRaw[$i] ?? 0); }
$thisMonthCount = $monthlyCounts[(int)date('n') - 1] ?? 0;
$yearTotal = array_sum($monthlyCounts) ?: 1;
$thisMonthPct = round(($thisMonthCount / $yearTotal) * 100);

// Calendar events for current month
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');
$stmtCal = $pdo->prepare("SELECT e.*, u.name AS organizer_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.event_date BETWEEN ? AND ? ORDER BY e.event_date ASC");
$stmtCal->execute([$firstDay, $lastDay]);
$calEvents = [];
foreach ($stmtCal->fetchAll() as $ev) {
    $d = (int)date('j', strtotime($ev['event_date']));
    $calEvents[$d][] = $ev;
}



$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Dashboard | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<!-- Graph Card -->
		<div class="card" style="margin: 1.5rem;">
			<div class="card-body">
				<h2 class="card-title" style="margin:0 0 .5rem;">Seniors Registered in <?= $year ?> (<?= $thisMonthPct ?>% this month)</h2>
				<canvas id="registrationsChart" height="110"></canvas>
			</div>
		</div>

		<!-- Stats and Upcoming -->
		<div class="grid grid-2 animate-fade-in" style="margin: 0 1.5rem;">
			<!-- Local Seniors Card -->
			<a href="<?= BASE_URL ?>/admin/local_seniors.php" style="text-decoration: none; color: inherit;">
				<div class="card" style="background: white; border-radius: 12px; box-shadow: 0 3px 5px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; margin-bottom: 2.5rem; margin-top: 1.5rem;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 5px 10px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 5px rgba(0,0,0,0.08)';">
					<div class="card-header" style="border-bottom: 1px solid #eee; padding: 1rem;">
						<h2 style="margin: 0; color: #333; font-size: 1.2rem; display: flex; align-items: center;">
							<span style="font-size: 1.5rem; margin-right: 0.5rem;">🏘️</span>
							Local Seniors
						</h2>
						<p style="margin: 0.3rem 0 0; color: #666; font-size: 0.85rem;">Click to view detailed list</p>
					</div>
					<div class="card-body" style="padding: 1rem; text-align: center;">
						<div style="font-size: 2rem; font-weight: 700; color: #28a745; margin-bottom: 0.3rem;"><?= $localSeniors ?></div>
						<p style="margin: 0; color: #666; font-weight: 500; font-size: 0.9rem;">Registered Local Seniors</p>
					</div>
				</div>
			</a>

			<!-- National Seniors Card -->
			<a href="<?= BASE_URL ?>/admin/national_seniors.php" style="text-decoration: none; color: inherit;">
				<div class="card" style="background: white; border-radius: 12px; box-shadow: 0 3px 5px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; margin-top: 1.5rem;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 5px 10px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 5px rgba(0,0,0,0.08)';">
					<div class="card-header" style="border-bottom: 1px solid #eee; padding: 1rem;">
						<h2 style="margin: 0; color: #333; font-size: 1.2rem; display: flex; align-items: center;">
							<span style="font-size: 1.5rem; margin-right: 0.5rem;">🇵🇭</span>
							National Seniors
						</h2>
						<p style="margin: 0.3rem 0 0; color: #666; font-size: 0.85rem;">Click to view detailed list</p>
					</div>
					<div class="card-body" style="padding: 1rem; text-align: center;">
						<div style="font-size: 2rem; font-weight: 700; color: #007bff; margin-bottom: 0.3rem;"><?= $nationalSeniors ?></div>
						<p style="margin: 0; color: #666; font-weight: 500; font-size: 0.9rem;">Registered National Seniors</p>
					</div>
				</div>
			</a>

			<!-- Upcoming Events -->
			<div class="card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); grid-column: span 2;">
				<div class="card-header" style="border-bottom: 1px solid #eee; padding: 1.5rem;">
					<h2 style="margin: 0; color: #333; font-size: 1.5rem; display: flex; align-items: center;">
						<span style="font-size: 2rem; margin-right: 0.5rem;">📅</span>
						Upcoming Events
					</h2>
					<p style="margin: 0.5rem 0 0; color: #666;">OSCA Head scheduled events</p>
				</div>
				<div class="card-body" style="padding: 1.5rem;">
					<?php if (!empty($upcomingEventsList)): ?>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
						<?php foreach ($upcomingEventsList as $event): ?>
						<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #667eea;">
							<h4 style="margin: 0 0 0.5rem; color: #333; font-size: 1.1rem;"><?= htmlspecialchars($event['title']) ?></h4>
							<p style="margin: 0 0 0.25rem; font-size: 0.9rem; color: #666;">📅 <?= date('M d, Y', strtotime($event['event_date'])) ?></p>
							<p style="margin: 0 0 0.25rem; font-size: 0.9rem; color: #444;">👤 <?= htmlspecialchars($event['organizer_name'] ?: 'Unknown') ?></p>
							<?php if ($event['description']): ?>
							<p style="margin: 0; font-size: 0.9rem; color: #555;">
								<?= htmlspecialchars(substr($event['description'], 0, 120)) ?><?= strlen($event['description']) > 120 ? '...' : '' ?>
							</p>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
					<?php else: ?>
					<div style="text-align: center; padding: 2rem; color: #666;">
						<p style="margin: 0 0 1rem; font-size: 1.1rem;">No upcoming events scheduled</p>
						<a href="<?= BASE_URL ?>/admin/events.php" class="button" style="background: #667eea; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500;">Create Event</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Calendar -->
		<div class="card" style="margin: 1.5rem;">
			<div class="card-header">
				<h2 style="margin:0; display:flex; align-items:center; gap:.5rem;">
					<span>🗓️</span> <?= date('F Y') ?>
				</h2>
			</div>
			<div class="card-body">
				<div style="display:grid; grid-template-columns: repeat(7, 1fr); gap:.5rem;">
					<?php
						$startWeekday = (int)date('N', strtotime($firstDay));
						for ($i=1;$i<$startWeekday;$i++) echo '<div></div>';
						$daysInMonth = (int)date('t');
						for ($day=1;$day<=$daysInMonth;$day++):
							$events = $calEvents[$day] ?? [];
					?>
					<div style="border:1px solid var(--border-light); border-radius:8px; min-height:90px; padding:.5rem; background:#fff;">
						<div style="font-weight:700; color:#374151; margin-bottom:.25rem;"><?= $day ?></div>
						<?php foreach ($events as $e): ?>
							<div style="font-size:.75rem; color:#111827; line-height:1.2; margin-bottom:.25rem;">
								<strong><?= htmlspecialchars($e['organizer_name'] ?: 'Unknown') ?></strong><br>
								<?= htmlspecialchars($e['title'] ?: 'Event') ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php endfor; ?>
				</div>
			</div>
		</div>
	</main>

	<!-- Removed modal for Local Seniors as replaced by separate page -->

	<!-- Removed modal for National Seniors as replaced by separate page -->

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Chart.js - monthly registrations
		const ctx = document.getElementById('registrationsChart');
		if (ctx) {
			new Chart(ctx, {
				type: 'bar',
				data: {
					labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
					datasets: [{
						label: 'Registrations',
						data: <?= json_encode($monthlyCounts) ?>,
						backgroundColor: 'rgba(37, 99, 235, 0.6)'
					}]
				},
				options: {
					plugins: { legend: { display: false } },
					scales: { y: { beginAtZero: true } }
				}
			});
		}
	</script>
</body>
</html>


