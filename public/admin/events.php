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
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Events | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Events Management</h1>
			<p class="content-subtitle">Manage senior citizen events and activities</p>
		</header>
		
		<div class="content-body">
		
		<?php if ($message): ?>
			<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
		<?php endif; ?>
		
		<!-- Events Card -->
		<div class="card">
			<div class="card-header">
				<h2 class="card-title">Events Management</h2>
				<p class="card-subtitle">Create and manage senior citizen events</p>
				<button class="btn btn-primary" onclick="openAddEventModal()" style="margin-top: 1rem;">
					Add New Event
				</button>
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
						<tbody>
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

		<div class="card" style="margin-top: 2rem;">
			<div class="card-header">
				<h2>📅 Event Calendar</h2>
				<p>Visual calendar view of all events</p>
			</div>
			<div class="calendar-container">
				<div class="calendar-header">
					<h3 id="calendar-month-year"></h3>
					<div style="display: flex; gap: 1rem; justify-content: center;">
						<button onclick="previousMonth()" class="small">← Previous</button>
						<button onclick="goToToday()" class="small secondary">Today</button>
						<button onclick="nextMonth()" class="small">Next →</button>
					</div>
				</div>
				<div class="calendar-grid" id="calendar-grid">
					<!-- Calendar will be generated by JavaScript -->
				</div>
			</div>
		</div>
		
		<!-- Add/Edit Event Modal -->
		<div class="modal-overlay" id="eventModal">
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
						
						<div class="form-row">
							<div class="form-group">
								<label class="form-label">Event Date</label>
								<input type="date" name="event_date" id="form-event-date" class="form-input" required>
							</div>
							<div class="form-group">
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
	</main>
	
	</div> <!-- Close dashboard-layout -->
	
	<script>
		let currentDate = new Date();
		let events = <?= json_encode($events) ?>;
		
		function openAddEventModal() {
			resetEventForm();
			document.getElementById('modal-title').textContent = 'Add Event';
			document.getElementById('eventModal').classList.add('active');
		}
		
		function closeEventModal() {
			document.getElementById('eventModal').classList.remove('active');
		}
		
		function editEvent(event) {
			// Set form operation to update
			document.getElementById('form-op').value = 'update';
			document.getElementById('form-id').value = event.id;
			
			// Populate form fields
			document.getElementById('form-title-input').value = event.title;
			document.getElementById('form-description-input').value = event.description || '';
			document.getElementById('form-event-date').value = event.event_date;
			document.getElementById('form-event-time').value = event.event_time || '';
			document.getElementById('form-contact').value = event.contact_number || '';
			document.getElementById('form-location').value = event.exact_location || '';
			
			// Update modal title and button text
			document.getElementById('modal-title').textContent = 'Edit Event';
			document.getElementById('submit-btn').textContent = 'Update Event';
			
			// Show modal
			document.getElementById('eventModal').classList.add('active');
		}
		
		function resetEventForm() {
			// Reset form operation
			document.getElementById('form-op').value = 'create';
			document.getElementById('form-id').value = '';
			
			// Reset form fields
			document.getElementById('eventForm').reset();
			
			// Reset button text
			document.getElementById('submit-btn').textContent = 'Create Event';
		}
		
		function deleteEvent(eventId) {
			if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
				// Create a form to submit the delete request
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
		
		function renderCalendar() {
			const year = currentDate.getFullYear();
			const month = currentDate.getMonth();
			
			document.getElementById('calendar-month-year').textContent = 
				currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
			
			const firstDay = new Date(year, month, 1);
			const lastDay = new Date(year, month + 1, 0);
			const startDate = new Date(firstDay);
			startDate.setDate(startDate.getDate() - firstDay.getDay());
			
			const calendarGrid = document.getElementById('calendar-grid');
			calendarGrid.innerHTML = '';
			
			// Add day headers
			const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
			dayHeaders.forEach(day => {
				const dayHeader = document.createElement('div');
				dayHeader.textContent = day;
				dayHeader.style.background = 'var(--primary)';
				dayHeader.style.color = 'white';
				dayHeader.style.padding = '0.5rem';
				dayHeader.style.textAlign = 'center';
				dayHeader.style.fontWeight = '600';
				calendarGrid.appendChild(dayHeader);
			});
			
			// Add calendar days
			for (let i = 0; i < 42; i++) {
				const date = new Date(startDate);
				date.setDate(startDate.getDate() + i);
				
				const dayDiv = document.createElement('div');
				dayDiv.className = 'calendar-day';
				dayDiv.textContent = date.getDate();
				
				if (date.getMonth() !== month) {
					dayDiv.classList.add('other-month');
				}
				
				if (date.toDateString() === new Date().toDateString()) {
					dayDiv.classList.add('today');
				}
				
				// Add events for this date
				const dayEvents = events.filter(event => {
					const eventDate = new Date(event.event_date);
					return eventDate.toDateString() === date.toDateString();
				});
				
				if (dayEvents.length > 0) {
					dayDiv.classList.add('has-event');
					dayEvents.forEach(event => {
						const eventDiv = document.createElement('div');
						eventDiv.className = 'calendar-event';
						eventDiv.textContent = event.title;
						eventDiv.title = event.description || event.title;
						dayDiv.appendChild(eventDiv);
					});
				}
				
				calendarGrid.appendChild(dayDiv);
			}
		}
		
		function previousMonth() {
			currentDate.setMonth(currentDate.getMonth() - 1);
			renderCalendar();
		}
		
		function nextMonth() {
			currentDate.setMonth(currentDate.getMonth() + 1);
			renderCalendar();
		}
		
		function goToToday() {
			currentDate = new Date();
			renderCalendar();
		}
		
		// Initialize calendar
		renderCalendar();
	</script>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


