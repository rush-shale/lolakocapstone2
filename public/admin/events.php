<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();
$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$op = $_POST['op'] ?? 'create';
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$event_date = $_POST['event_date'] ?? '';
		$event_time = $_POST['event_time'] ?? null;
		$contact_number = trim($_POST['contact_number'] ?? '');
		$exact_location = trim($_POST['exact_location'] ?? '');
		$scope = 'admin';
		
		if ($title && $event_date) {
			if ($op === 'create') {
				$stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, event_time, contact_number, exact_location, scope, created_by) VALUES (?,?,?,?,?,?,?,?)');
				$stmt->execute([$title, $description ?: null, $event_date, $event_time ?: null, $contact_number ?: null, $exact_location ?: null, $scope, $user['id']]);
				$message = 'Event created successfully';
			} elseif ($op === 'update') {
				$id = (int)($_POST['id'] ?? 0);
				if ($id > 0) {
					$stmt = $pdo->prepare('UPDATE events SET title=?, description=?, event_date=?, event_time=?, contact_number=?, exact_location=? WHERE id=? AND scope="admin"');
					$stmt->execute([$title, $description ?: null, $event_date, $event_time ?: null, $contact_number ?: null, $exact_location ?: null, $id]);
					$message = 'Event updated successfully';
				}
			} elseif ($op === 'delete') {
				$id = (int)($_POST['id'] ?? 0);
				if ($id > 0) {
					$stmt = $pdo->prepare('DELETE FROM events WHERE id=? AND scope="admin"');
					$stmt->execute([$id]);
					$message = 'Event deleted successfully';
				}
			}
		}
	}
}

$csrf = generate_csrf_token();
$events = $pdo->query("SELECT * FROM events WHERE scope='admin' ORDER BY event_date DESC, id DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Events | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css" />
	<style>
		/* Modal zoom animations */
		@keyframes zoomIn {
			0% {
				transform: scale(0.5);
				opacity: 0;
			}
			100% {
				transform: scale(1);
				opacity: 1;
			}
		}
		@keyframes zoomOut {
			0% {
				transform: scale(1);
				opacity: 1;
			}
			100% {
				transform: scale(0.7);
				opacity: 0;
			}
		}
	.modal-overlay.active {
		display: flex;
		justify-content: center;
		align-items: center;
		position: fixed;
		inset: 0;
		background: transparent;
		backdrop-filter: blur(5px);
		-webkit-backdrop-filter: blur(5px);
		z-index: 1000;
	}
	.modal {
		background: white;
		border-radius: 8px;
		width: 95%;
		max-width: 800px;
		padding: 1.5rem;
		box-shadow: 0 2px 15px rgba(0,0,0,0.3);
		animation-fill-mode: forwards;
		position: fixed;
		top: 5%;
		left: 50%;
		transform: translate(-50%, 0);
	}
		.modal-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.modal-close {
			background: none;
			border: none;
			font-size: 1.5rem;
			cursor: pointer;
		}
		.form-group {
			margin-bottom: 1rem;
		}
		.form-label {
			display: block;
			margin-bottom: 0.3rem;
			font-weight: 600;
		}
		.form-input, textarea {
			width: 100%;
			padding: 0.5rem;
			border: 1px solid #ccc;
			border-radius: 4px;
			font-size: 1rem;
		}
		.form-actions {
			display: flex;
			justify-content: flex-end;
			gap: 1rem;
		}
		.btn {
			padding: 0.5rem 1rem;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 1rem;
		}
		.btn-primary {
			background-color: #007bff;
			color: white;
		}
		.btn-secondary {
			background-color: #6c757d;
			color: white;
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Events Management</h1>
			<p class="content-subtitle">Manage senior citizen events and activities</p>
		</header>
		<div class="content-body">
			<?php if ($message): ?>
				<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
			<?php endif; ?>
			<div class="card">
				<div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
					<div>
						<h2 class="card-title">Events Management</h2>
						<p class="card-subtitle">Create and manage senior citizen events</p>
					</div>
					<div style="display: flex; align-items: center; gap: 1rem;">
						<input type="text" id="searchInput" placeholder="Search events..." style="padding: 0.5rem; width: 250px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
						<button class="btn btn-primary" onclick="openAddEventModal()">
							Add New Event
						</button>
					</div>
				</div>
				<div class="card-body">
				<div class="table-container">
					<table class="table">
						<thead>
							<tr>
								<th>Event Title</th>
								<th>Date</th>
								<th>Time</th>
								<th>Contact</th>
								<th>Location</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="eventsTableBody">
							<?php foreach ($events as $e): ?>
								<tr>
									<td>
										<strong><?= htmlspecialchars($e['title']) ?></strong>
										<?php if ($e['description']): ?>
											<br><small style="color: var(--gov-text-muted);"><?= htmlspecialchars($e['description']) ?></small>
										<?php endif; ?>
									</td>
									<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
									<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
									<td><?= htmlspecialchars($e['contact_number'] ?: 'N/A') ?></td>
									<td><?= htmlspecialchars($e['exact_location'] ?: 'TBA') ?></td>
									<td>
										<span class="badge <?= strtotime($e['event_date']) >= strtotime('today') ? 'badge-info' : 'badge-muted' ?>">
											<?= strtotime($e['event_date']) >= strtotime('today') ? 'Upcoming' : 'Past' ?>
										</span>
									</td>
									<td>
										<div style="display: flex; gap: 0.5rem;">
											<button class="btn btn-secondary btn-sm" onclick="editEvent(<?= htmlspecialchars(json_encode($e)) ?>)">
												Edit
											</button>
											<button class="btn btn-secondary btn-sm" onclick="deleteEvent(<?= $e['id'] ?>)" style="background-color: var(--gov-danger); color: white; border-color: var(--gov-danger);">
												Delete
											</button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($events)): ?>
								<tr>
									<td colspan="7" style="text-align: center; padding: 2rem; color: var(--gov-text-muted);">
										No events created yet. Click "Add New Event" to get started.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
				</div>
			</div>
		</div>
	</main>

	<!-- Add/Edit Event Modal -->
	<div class="modal-overlay" id="eventModal" style="display:none;">
		<div class="modal">
			<div class="modal-header">
				<h2 class="modal-title" id="modal-title">Add Event</h2>
				<button class="modal-close" onclick="closeEventModal()">&times;</button>
			</div>
			<div class="modal-body">
				<form method="post" id="eventForm">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="create" id="form-op">
					<input type="hidden" name="id" value="" id="form-id">
					
					<div class="form-group">
						<label class="form-label">Event Title</label>
						<input type="text" name="title" id="form-title-input" class="form-input" required placeholder="Enter event title">
					</div>
					
					<div class="form-group">
						<label class="form-label">Description</label>
						<textarea name="description" id="form-description-input" class="form-input" rows="3" placeholder="Enter event description (optional)"></textarea>
					</div>
					
					<div class="form-row" style="display:flex; gap:1rem;">
						<div class="form-group" style="flex:1;">
							<label class="form-label">Event Date</label>
							<input type="date" name="event_date" id="form-event-date" class="form-input" required>
						</div>
						<div class="form-group" style="flex:1;">
							<label class="form-label">Event Time</label>
							<input type="time" name="event_time" id="form-event-time" class="form-input" placeholder="Optional time">
						</div>
					</div>
					
					<div class="form-group">
						<label class="form-label">Contact Number</label>
						<input type="tel" name="contact_number" id="form-contact" class="form-input" placeholder="Enter contact number for inquiries">
					</div>
					
					<div class="form-group">
						<label class="form-label">Exact Location</label>
						<textarea name="exact_location" id="form-location" class="form-input" rows="2" placeholder="Enter exact location or venue details"></textarea>
					</div>
					
					<div class="form-actions">
						<button type="button" class="btn btn-secondary" onclick="closeEventModal()">Cancel</button>
						<button type="submit" class="btn btn-primary" id="submit-btn">Create Event</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script>
		let currentDate = new Date();
		let events = <?= json_encode($events) ?>;

		function openAddEventModal() {
			resetEventForm();
			document.getElementById('modal-title').textContent = 'Add Event';
			const modal = document.getElementById('eventModal');
			modal.classList.add('active');
			modal.style.display = 'flex';
			modal.style.animation = 'zoomIn 0.3s forwards';
			document.body.style.overflow = 'hidden'; // prevent background scroll
			document.querySelector('.content-body').style.filter = 'blur(5px)'; // blur background content
		}

		function closeEventModal() {
			const modal = document.getElementById('eventModal');
			modal.style.animation = 'zoomOut 0.3s forwards';
			setTimeout(() => {
				modal.style.display = 'none';
				document.body.style.overflow = ''; // restore scroll
				document.querySelector('.content-body').style.filter = ''; // remove blur
			}, 300);
		}

		function editEvent(event) {
			document.getElementById('form-op').value = 'update';
			document.getElementById('form-id').value = event.id;
			document.getElementById('form-title-input').value = event.title;
			document.getElementById('form-description-input').value = event.description || '';
			document.getElementById('form-event-date').value = event.event_date;
			document.getElementById('form-event-time').value = event.event_time || '';
			document.getElementById('form-contact').value = event.contact_number || '';
			document.getElementById('form-location').value = event.exact_location || '';
			document.getElementById('modal-title').textContent = 'Edit Event';
			document.getElementById('submit-btn').textContent = 'Update Event';
			const modal = document.getElementById('eventModal');
			modal.classList.add('active');
			modal.style.display = 'flex';
			modal.style.animation = 'zoomIn 0.3s forwards';
			document.body.style.overflow = 'hidden'; // prevent background scroll
			document.querySelector('.content-body').style.filter = 'blur(5px)'; // blur background content
		}

		function resetEventForm() {
			document.getElementById('form-op').value = 'create';
			document.getElementById('form-id').value = '';
			document.getElementById('eventForm').reset();
			document.getElementById('submit-btn').textContent = 'Create Event';
		}

		function deleteEvent(eventId) {
			if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
				const form = document.createElement('form');
				form.method = 'POST';
				form.innerHTML = `
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="delete">
					<input type="hidden" name="id" value="${eventId}">
				`;
				document.body.appendChild(form);
				form.submit();
			}
		}
		// Search filter for events table
		document.getElementById('searchInput').addEventListener('input', function() {
			const filter = this.value.toLowerCase();
			const rows = document.querySelectorAll('#eventsTableBody tr');
			rows.forEach(row => {
				const title = row.querySelector('td strong').textContent.toLowerCase();
				const descriptionElem = row.querySelector('td small');
				const description = descriptionElem ? descriptionElem.textContent.toLowerCase() : '';
				if (title.includes(filter) || description.includes(filter)) {
					row.style.display = '';
				} else {
					row.style.display = 'none';
				}
			});
		});
	</script>
</body>
</html>


