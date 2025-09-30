<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

// Get all OSCA Head events
$events = $pdo->query("SELECT * FROM events WHERE scope='admin' ORDER BY event_date DESC, id DESC")->fetchAll();

// Separate upcoming and past events
$upcomingEvents = array_filter($events, function($event) {
    return strtotime($event['event_date']) >= strtotime('today');
});

$pastEvents = array_filter($events, function($event) {
    return strtotime($event['event_date']) < strtotime('today');
});

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>OSCA Events | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">OSCA Events</h1>
			<p class="content-subtitle">View all events created by the OSCA Head for senior citizens</p>
		</header>
		
		<div class="content-body">
		
			<div class="stats animate-fade-in">
				<div class="stat success">
					<div class="stat-icon">
						<i class="fas fa-calendar-plus"></i>
					</div>
					<div class="stat-content">
						<h3>Upcoming Events</h3>
						<p class="number"><?= count($upcomingEvents) ?></p>
					</div>
				</div>
				<div class="stat">
					<div class="stat-icon">
						<i class="fas fa-calendar-alt"></i>
					</div>
					<div class="stat-content">
						<h3>Total Events</h3>
						<p class="number"><?= count($events) ?></p>
					</div>
				</div>
				<div class="stat warning">
					<div class="stat-icon">
						<i class="fas fa-history"></i>
					</div>
					<div class="stat-content">
						<h3>Past Events</h3>
						<p class="number"><?= count($pastEvents) ?></p>
					</div>
				</div>
			</div>

			<div class="grid grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-calendar-plus"></i>
							Upcoming Events
						</h2>
						<p class="card-subtitle">Events scheduled for the future</p>
					</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Event</th>
								<th>Date</th>
								<th>Time</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($upcomingEvents as $e): ?>
								<tr>
									<td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
									<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
									<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
									<td><?= htmlspecialchars($e['description'] ?: 'No description') ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($upcomingEvents)): ?>
								<tr>
									<td colspan="4">
										<div class="empty-state">
											<div class="empty-icon">
												<i class="fas fa-calendar-plus"></i>
											</div>
											<h3>No Upcoming Events</h3>
											<p>No upcoming OSCA events scheduled.</p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-history"></i>
							Past Events
						</h2>
						<p class="card-subtitle">Previously held events</p>
					</div>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Event</th>
								<th>Date</th>
								<th>Time</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($pastEvents as $e): ?>
								<tr>
									<td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
									<td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
									<td><?= $e['event_time'] ? date('g:i A', strtotime($e['event_time'])) : 'All Day' ?></td>
									<td><?= htmlspecialchars($e['description'] ?: 'No description') ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (empty($pastEvents)): ?>
								<tr>
									<td colspan="4">
										<div class="empty-state">
											<div class="empty-icon">
												<i class="fas fa-history"></i>
											</div>
											<h3>No Past Events</h3>
											<p>No past OSCA events found.</p>
										</div>
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
					<h2 class="card-title">
						<i class="fas fa-calendar-alt"></i>
						Event Calendar
					</h2>
					<p class="card-subtitle">Visual calendar view of all OSCA Head events</p>
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
	</main>
	
	<script>
		let currentDate = new Date();
		let events = <?= json_encode($events) ?>;
		
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
