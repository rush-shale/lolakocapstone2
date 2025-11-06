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

// Stats: totals and counts for widgets
$totalSeniors = (int)$pdo->prepare("SELECT COUNT(*) FROM seniors WHERE barangay=? AND life_status='living'")->execute([$user['barangay']]) ? (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE barangay='" . str_replace("'", "''", $user['barangay']) . "' AND life_status='living'")->fetchColumn() : 0;

$upcomingBarangayCountStmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE scope='barangay' AND barangay=? AND event_date >= CURDATE()");
$upcomingBarangayCountStmt->execute([$user['barangay']]);
$upcomingBarangayCount = (int)$upcomingBarangayCountStmt->fetchColumn();

$upcomingAdminCount = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE scope='admin' AND event_date >= CURDATE()")->fetchColumn();

$last7DaysAttendeesStmt = $pdo->prepare("SELECT COUNT(a.id) FROM attendance a JOIN events e ON a.event_id = e.id WHERE e.scope='barangay' AND e.barangay=? AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$last7DaysAttendeesStmt->execute([$user['barangay']]);
$last7DaysAttendees = (int)$last7DaysAttendeesStmt->fetchColumn();

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
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Welcome back, <?= htmlspecialchars($user['name']) ?></h1>
			<p class="content-subtitle">SeniorCare Information System - Staff Portal for <?= htmlspecialchars($user['barangay']) ?></p>
		</header>
		
		<div class="content-body">
			<!-- Stats Widgets -->
			<div class="stats" style="margin-bottom: var(--space-6);">
				<div class="card modern-stat-card">
					<div class="card-body">
						<div style="display:flex; align-items:center; justify-content:space-between; gap: 1rem;">
							<div>
								<div class="text-muted" style="font-weight:600;">Total Seniors</div>
								<div style="font-size:2rem; font-weight:800;"><?= number_format($totalSeniors) ?></div>
							</div>
							<i class="fas fa-users" style="font-size:1.75rem; color: var(--gov-primary);"></i>
						</div>
					</div>
				</div>
				<div class="card modern-stat-card">
					<div class="card-body">
						<div style="display:flex; align-items:center; justify-content:space-between; gap: 1rem;">
							<div>
								<div class="text-muted" style="font-weight:600;">Upcoming Barangay Events</div>
								<div style="font-size:2rem; font-weight:800;"><?= number_format($upcomingBarangayCount) ?></div>
							</div>
							<i class="fas fa-calendar-alt" style="font-size:1.75rem; color: #0ea5e9;"></i>
						</div>
					</div>
				</div>
				<div class="card modern-stat-card">
					<div class="card-body">
						<div style="display:flex; align-items:center; justify-content:space-between; gap: 1rem;">
							<div>
								<div class="text-muted" style="font-weight:600;">Upcoming OSCA Events</div>
								<div style="font-size:2rem; font-weight:800;"><?= number_format($upcomingAdminCount) ?></div>
							</div>
							<i class="fas fa-building" style="font-size:1.75rem; color: #10b981;"></i>
						</div>
					</div>
				</div>
				<div class="card modern-stat-card">
					<div class="card-body">
						<div style="display:flex; align-items:center; justify-content:space-between; gap: 1rem;">
							<div>
								<div class="text-muted" style="font-weight:600;">Attendees (Last 7 Days)</div>
								<div style="font-size:2rem; font-weight:800;"><?= number_format($last7DaysAttendees) ?></div>
							</div>
							<i class="fas fa-user-check" style="font-size:1.75rem; color: #f59e0b;"></i>
						</div>
					</div>
				</div>
			</div>

			<div class="grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-calendar-alt"></i>
							My Barangay Events
						</h2>
						<p class="card-subtitle">Upcoming events for <?= htmlspecialchars($user['barangay']) ?></p>
					</div>
					<div class="card-body">
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
							<tr class="clickable-event-row" data-event-id="<?= (int)$e['id'] ?>" title="View attendees">
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

				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-users"></i>
							Recent Event Attendees
						</h2>
						<p class="card-subtitle">Latest barangay event within 7 days</p>
					</div>
					<div class="card-body">
						<?php if (!empty($latestEvent) && is_array($latestEvent)): ?>
							<p style="margin-bottom: .75rem;"><strong><?= htmlspecialchars($latestEvent['title']) ?></strong> — <?= date('M d, Y', strtotime($latestEvent['event_date'])) ?><?= $latestEvent['event_time'] ? ' • ' . date('g:i A', strtotime($latestEvent['event_time'])) : '' ?></p>
							<?php if (!empty($latestEventAttendees)): ?>
								<div class="table-container table-scroll">
									<table class="modern-table">
										<thead>
											<tr>
												<th>Name</th>
												<th>Marked At</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($latestEventAttendees as $a): ?>
											<tr>
												<td><?= htmlspecialchars(trim(($a['last_name'] ?? '') . ', ' . ($a['first_name'] ?? '') . ' ' . ($a['middle_name'] ?? '') . ' ' . ($a['ext_name'] ?? ''))) ?></td>
												<td><?= htmlspecialchars($a['marked_at']) ?></td>
											</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php else: ?>
								<div class="empty-state">
									<div class="empty-icon"><i class="fas fa-user-check"></i></div>
									<h3>No Attendees Recorded</h3>
									<p>No attendance has been recorded for the latest event.</p>
								</div>
							<?php endif; ?>
						<?php else: ?>
							<div class="empty-state">
								<div class="empty-icon"><i class="fas fa-user-clock"></i></div>
								<h3>No Recent Events</h3>
								<p>No barangay events found within the last 7 days.</p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-building"></i>
							OSCA Head Events
						</h2>
						<p class="card-subtitle">Events created by the OSCA Head</p>
					</div>
					<div class="card-body">
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
							<tr class="clickable-event-row" data-event-id="<?= (int)$e['id'] ?>" title="View attendees">
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

				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-history"></i>
							Recent Past Events
						</h2>
						<p class="card-subtitle">Past events for your barangay</p>
					</div>
					<div class="card-body">
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
							<tr class="clickable-event-row" data-event-id="<?= (int)$e['id'] ?>" title="View attendees">
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

				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-bolt"></i>
							Quick Actions
						</h2>
						<p class="card-subtitle">Frequently used tasks</p>
					</div>
					<div class="card-body">
						<div style="display: flex; flex-direction: column; gap: 0.75rem;">
							<a href="<?= BASE_URL ?>/user/events.php" class="button primary">
								<i class="fas fa-plus-circle"></i>
								Create Event
							</a>
							<a href="<?= BASE_URL ?>/user/osca_events.php" class="button primary">
								<i class="fas fa-building"></i>
								View OSCA Events
							</a>
							<a href="<?= BASE_URL ?>/user/attendance.php" class="button secondary">
								<i class="fas fa-check-circle"></i>
								Mark Attendance
							</a>
							<a href="<?= BASE_URL ?>/user/seniors.php" class="button secondary">
								<i class="fas fa-users"></i>
								Manage Seniors
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	<!-- Event Attendees Modal -->
	<div id="eventAttendeesModal" class="modal-overlay" aria-hidden="true">
		<div class="modal large" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">
			<div class="modal-header">
				<h3 class="modal-title" id="eventModalTitle">Event Attendees</h3>
				<button type="button" class="modal-close" id="closeEventModal" aria-label="Close">
					<i class="fas fa-times"></i>
				</button>
			</div>
			<div class="modal-body">
				<p id="eventModalSubtitle" class="text-muted" style="margin-bottom: .75rem;"></p>
				<div class="table-container table-scroll">
					<table class="modern-table">
						<thead>
							<tr>
								<th>Last Name</th>
								<th>First Name</th>
								<th>Middle</th>
								<th>Ext</th>
								<th>Marked At</th>
							</tr>
						</thead>
						<tbody id="eventAttendeesBody">
							<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem;">No attendees yet.</td></tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		(function(){
			const modal = document.getElementById('eventAttendeesModal');
			const modalTitle = document.getElementById('eventModalTitle');
			const modalSubtitle = document.getElementById('eventModalSubtitle');
			const modalBodyTbody = document.getElementById('eventAttendeesBody');
			const closeBtn = document.getElementById('closeEventModal');

			function openModal() {
				modal.classList.add('active');
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('modal-active');
			}

			function closeModal() {
				modal.classList.remove('active');
				modal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('modal-active');
			}

			closeBtn?.addEventListener('click', closeModal);
			modal.addEventListener('click', (e) => {
				if (e.target === modal) closeModal();
			});

			async function loadEventAttendees(eventId) {
				try {
					const res = await fetch(`${BASE_URL}/user/fetch_event_attendees.php?event_id=${encodeURIComponent(eventId)}`, { credentials: 'same-origin' });
					const data = await res.json();
					const e = data.event || {};
					modalTitle.textContent = e.title ? `Event Attendees — ${e.title}` : 'Event Attendees';
					const when = e.event_time ? `${e.event_date} • ${e.event_time}` : (e.event_date || '');
					modalSubtitle.textContent = when;
					modalBodyTbody.innerHTML = '';
					const list = Array.isArray(data.attendees) ? data.attendees : [];
					if (list.length === 0) {
						modalBodyTbody.innerHTML = '<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem;">No attendees yet.</td></tr>';
						return;
					}
					for (const a of list) {
						const tr = document.createElement('tr');
						tr.innerHTML = `
							<td>${a.last_name ? a.last_name : ''}</td>
							<td>${a.first_name ? a.first_name : ''}</td>
							<td>${a.middle_name ? a.middle_name : ''}</td>
							<td>${a.ext_name ? a.ext_name : ''}</td>
							<td>${a.marked_at ? a.marked_at : ''}</td>
						`;
						modalBodyTbody.appendChild(tr);
					}
				} catch (err) {
					modalBodyTbody.innerHTML = '<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem;">Failed to load attendees.</td></tr>';
				}
			}

			function onRowClick(e) {
				const tr = e.target.closest('tr.clickable-event-row');
				if (!tr) return;
				const eventId = tr.getAttribute('data-event-id');
				if (!eventId) return;
				openModal();
				modalTitle.textContent = 'Event Attendees';
				modalSubtitle.textContent = '';
				modalBodyTbody.innerHTML = '<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem;">Loading…</td></tr>';
				loadEventAttendees(eventId);
			}

			document.addEventListener('click', function(ev) {
				if (ev.target.closest('tr.clickable-event-row')) {
					onRowClick(ev);
				}
			});
		})();
	</script>
</body>
</html>
