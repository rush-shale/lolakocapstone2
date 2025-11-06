<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
start_app_session();
$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$event_date = $_POST['event_date'] ?? '';
		$event_time = $_POST['event_time'] ?? null;
		if ($title && $event_date) {
			$stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, event_time, scope, barangay, created_by) VALUES (?,?,?,?,"barangay",?,?)');
			$stmt->execute([$title,$description ?: null,$event_date,$event_time ?: null,$user['barangay'],$user['id']]);
			$message = 'Event created successfully';
			
			// Redirect to avoid resubmission
			header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
			exit;
		}
	}
}

// Check for success message
if (isset($_GET['success']) && $_GET['success'] === '1') {
	$message = 'Event created successfully';
}

$csrf = generate_csrf_token();
$events = $pdo->prepare("SELECT * FROM events WHERE scope='barangay' AND barangay=? ORDER BY event_date DESC, id DESC");
$events->execute([$user['barangay']]);
$events = $events->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Barangay Events | SeniorCare Information System</title>
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Table scroll styling for user events */
		.events-table-scroll {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			max-width: 100%;
		}
		
		.events-table-scroll table {
			width: max-content;
			min-width: 100%;
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">My Barangay Events</h1>
			<p class="content-subtitle">Manage events for your barangay (<?= htmlspecialchars($user['barangay']) ?>)</p>
		</header>
		
		<div class="content-body">
			<?php if ($message): ?>
			<div class="alert alert-success">
				<div class="alert-icon">
					<i class="fas fa-check-circle"></i>
				</div>
				<div class="alert-content">
					<strong>Success!</strong>
					<p><?= htmlspecialchars($message) ?></p>
				</div>
			</div>
			<?php endif; ?>
			
			<div class="grid">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-plus-circle"></i>
							Create New Event
						</h2>
						<p class="card-subtitle">Add a new event for your barangay</p>
					</div>
					<div class="card-body">
						<form method="post" class="form">
							<input type="hidden" name="csrf" value="<?= $csrf ?>">
							
							<div class="form-group">
								<label class="form-label">Event Title</label>
								<input type="text" name="title" class="form-input" required placeholder="Enter event title">
							</div>
							
							<div class="form-group">
								<label class="form-label">Description</label>
								<textarea name="description" class="form-input" rows="3" placeholder="Enter event description (optional)"></textarea>
							</div>
							
							<div class="form-row">
								<div class="form-group">
									<label class="form-label">Event Date</label>
									<input type="date" name="event_date" class="form-input" required>
								</div>
								<div class="form-group">
									<label class="form-label">Event Time</label>
									<input type="time" name="event_time" class="form-input" placeholder="Optional time">
								</div>
							</div>
							
							<div class="form-actions">
								<button type="submit" class="button primary">
									<i class="fas fa-save"></i>
									Create Event
								</button>
							</div>
						</form>
					</div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-calendar-alt"></i>
							All Barangay Events
						</h2>
						<p class="card-subtitle">Events created for your barangay</p>
					</div>
					<div class="card-body">
						<?php if (!empty($events)): ?>
						<div class="table-container table-scroll events-table-scroll">
							<table class="modern-table">
								<thead>
									<tr>
										<th>Event Title</th>
										<th>Date</th>
										<th>Time</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
						<?php foreach ($events as $e): ?>
						<tr class="clickable-event-row" data-event-id="<?= (int)$e['id'] ?>" title="View attendees">
										<td>
											<strong><?= htmlspecialchars($e['title']) ?></strong>
											<?php if ($e['description']): ?>
												<br><small class="text-muted"><?= htmlspecialchars($e['description']) ?></small>
											<?php endif; ?>
										</td>
										<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
										<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
										<td>
											<span class="badge <?= strtotime($e['event_date']) >= strtotime('today') ? 'badge-info' : 'badge-muted' ?>">
												<?= strtotime($e['event_date']) >= strtotime('today') ? 'Upcoming' : 'Past' ?>
											</span>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-calendar-plus"></i>
							</div>
							<h3>No Events Yet</h3>
							<p>Create your first event for the barangay using the form above.</p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</main>

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
					const res = await fetch(`fetch_event_attendees.php?event_id=${encodeURIComponent(eventId)}`, { credentials: 'same-origin' });
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

			document.addEventListener('click', function(ev) {
				const tr = ev.target.closest('tr.clickable-event-row');
				if (!tr) return;
				const eventId = tr.getAttribute('data-event-id');
				if (!eventId) return;
				openModal();
				modalTitle.textContent = 'Event Attendees';
				modalSubtitle.textContent = '';
				modalBodyTbody.innerHTML = '<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem;">Loading…</td></tr>';
				loadEventAttendees(eventId);
			});
		})();
	</script>
</body>
</html>
