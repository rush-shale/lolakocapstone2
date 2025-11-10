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
							Barangay Events
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
				<div class="table-container table-scroll modal-table">
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

			function formatDateTime(dateTimeString) {
				if (!dateTimeString) return '—';
				try {
					const date = new Date(dateTimeString);
					if (isNaN(date.getTime())) return dateTimeString;
					const dateStr = date.toLocaleDateString('en-US', { 
						year: 'numeric', 
						month: 'short', 
						day: 'numeric' 
					});
					const timeStr = date.toLocaleTimeString('en-US', { 
						hour: '2-digit', 
						minute: '2-digit',
						hour12: true 
					});
					return `${dateStr} at ${timeStr}`;
				} catch (e) {
					return dateTimeString;
				}
			}

			async function loadEventAttendees(eventId) {
				try {
					const res = await fetch(`fetch_event_attendees.php?event_id=${encodeURIComponent(eventId)}`, { credentials: 'same-origin' });
					if (!res.ok) {
						throw new Error('Failed to fetch attendees');
					}
					const data = await res.json();
					
					if (!data || typeof data !== 'object') {
						throw new Error('Invalid response format');
					}
					
					if (data.success === false) {
						throw new Error(data.message || 'Failed to load attendees');
					}
					
					const e = data.event || {};
					modalTitle.textContent = e.title ? `Event Attendees — ${e.title}` : 'Event Attendees';
					
					// Format event date and time
					let when = '';
					if (e.event_date) {
						const eventDate = new Date(e.event_date);
						when = eventDate.toLocaleDateString('en-US', { 
							year: 'numeric', 
							month: 'long', 
							day: 'numeric' 
						});
						if (e.event_time) {
							const timeParts = e.event_time.split(':');
							if (timeParts.length >= 2) {
								const hours = parseInt(timeParts[0]);
								const minutes = timeParts[1];
								const ampm = hours >= 12 ? 'PM' : 'AM';
								const displayHours = hours % 12 || 12;
								when += ` at ${displayHours}:${minutes} ${ampm}`;
							}
						}
					}
					const list = Array.isArray(data.attendees) ? data.attendees : [];
					const attendeeCount = list.length;
					
					// Update subtitle with event details and attendee count
					let subtitleText = when || 'Event details not available';
					if (attendeeCount > 0) {
						subtitleText += ` • ${attendeeCount} attendee${attendeeCount !== 1 ? 's' : ''}`;
					}
					modalSubtitle.textContent = subtitleText;
					
					modalBodyTbody.innerHTML = '';
					
					if (list.length === 0) {
						modalBodyTbody.innerHTML = '<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem; color:#6b7280;">No attendees have been marked for this event yet.<br><small style="font-size:0.875rem; margin-top:0.5rem; display:block;">Mark attendance in the Attendance Management page.</small></td></tr>';
						return;
					}
					
					for (const a of list) {
						const tr = document.createElement('tr');
						tr.innerHTML = `
							<td>${escapeHtml(a.last_name || '—')}</td>
							<td>${escapeHtml(a.first_name || '—')}</td>
							<td>${escapeHtml(a.middle_name || '—')}</td>
							<td>${escapeHtml(a.ext_name || '—')}</td>
							<td>${formatDateTime(a.marked_at)}</td>
						`;
						modalBodyTbody.appendChild(tr);
					}
				} catch (err) {
					console.error('Error loading attendees:', err);
					modalBodyTbody.innerHTML = '<tr class="no-data"><td colspan="5" style="text-align:center; padding:1rem; color:#dc2626;">Failed to load attendees. Please try again.</td></tr>';
				}
			}
			
			function escapeHtml(text) {
				if (!text) return '—';
				const div = document.createElement('div');
				div.textContent = text;
				return div.innerHTML;
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

			// Store current event ID when modal is open
			let currentEventId = null;
			
			// Wrap loadEventAttendees to track current event
			const originalLoadEventAttendees = loadEventAttendees;
			loadEventAttendees = async function(eventId) {
				currentEventId = eventId;
				return await originalLoadEventAttendees(eventId);
			};

			// Listen for real-time attendance updates
			window.addEventListener('storage', function(event) {
				if (event.key && event.key.startsWith('attendance-updated-')) {
					const eventId = event.key.replace('attendance-updated-', '');
					// If modal is open and showing this event, refresh the attendees list
					if (modal.classList.contains('active') && currentEventId === eventId) {
						// Show a brief notification
						const notification = document.createElement('div');
						notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 0.75rem 1rem; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10000; font-size: 0.875rem;';
						notification.textContent = '✓ Attendance updated! Refreshing list...';
						document.body.appendChild(notification);
						
						setTimeout(() => {
							notification.style.opacity = '0';
							notification.style.transition = 'opacity 0.3s';
							setTimeout(() => notification.remove(), 300);
						}, 2000);
						
						loadEventAttendees(eventId);
					}
				} else if (event.key === 'attendance-updated') {
					try {
						const updateData = JSON.parse(event.newValue || '{}');
						if (updateData.event_id && modal.classList.contains('active') && currentEventId === updateData.event_id) {
							// Show a brief notification
							const notification = document.createElement('div');
							notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 0.75rem 1rem; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10000; font-size: 0.875rem;';
							notification.textContent = '✓ Attendance updated! Refreshing list...';
							document.body.appendChild(notification);
							
							setTimeout(() => {
								notification.style.opacity = '0';
								notification.style.transition = 'opacity 0.3s';
								setTimeout(() => notification.remove(), 300);
							}, 2000);
							
							loadEventAttendees(updateData.event_id);
						}
					} catch (e) {
						// Ignore parse errors
					}
				}
			});
		})();
	</script>
	<style>
		/* Ensure modal table is not blurred by global modal-active rule */
		body.modal-active #eventAttendeesModal .modal-table { opacity: 1 !important; filter: none !important; pointer-events: auto !important; }
	</style>
</body>
</html>
