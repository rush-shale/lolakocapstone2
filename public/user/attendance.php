<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
start_app_session();
$user = current_user();
$message = '';

// Load seniors in my barangay (living only)
$seniorsStmt = $pdo->prepare("SELECT id, first_name, middle_name, last_name, ext_name, barangay, age, sex, date_of_birth, osca_id_no, remarks, health_condition, purok, place_of_birth, cellphone, validation_status, validation_date, category, life_status FROM seniors WHERE barangay=? AND life_status='living' ORDER BY last_name, first_name");
$seniorsStmt->execute([$user['barangay']]);
$seniors = $seniorsStmt->fetchAll();

// Load my future or today events to mark attendance
$eventsStmt = $pdo->prepare("SELECT id, title, event_date, event_time FROM events WHERE scope='barangay' AND barangay=? AND event_date >= CURDATE() ORDER BY event_date ASC");
$eventsStmt->execute([$user['barangay']]);
$events = $eventsStmt->fetchAll();

// Load attendance history for the last 30 days
$attendanceHistoryStmt = $pdo->prepare("
    SELECT 
        a.id,
        a.marked_at as attendance_date,
        s.first_name,
        s.last_name,
        s.middle_name,
        e.title as event_title,
        e.event_date
    FROM attendance a
    JOIN seniors s ON a.senior_id = s.id
    JOIN events e ON a.event_id = e.id
    WHERE s.barangay = ? AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY a.marked_at DESC
    LIMIT 50
");
$attendanceHistoryStmt->execute([$user['barangay']]);
$attendanceHistory = $attendanceHistoryStmt->fetchAll();

// Get active seniors (those who attended 3+ events in last 30 days)
$activeSeniorsStmt = $pdo->prepare("
    SELECT 
        s.id,
        s.first_name,
        s.last_name,
        s.middle_name,
        s.age,
        COUNT(a.id) as attendance_count,
        MAX(a.marked_at) as last_attendance
    FROM seniors s
    LEFT JOIN attendance a ON s.id = a.senior_id
    LEFT JOIN events e ON a.event_id = e.id
    WHERE s.barangay = ? 
    AND s.life_status = 'living'
    AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY s.id, s.first_name, s.last_name, s.middle_name, s.age
    HAVING attendance_count >= 3
    ORDER BY attendance_count DESC, last_attendance DESC
");
$activeSeniorsStmt->execute([$user['barangay']]);
$activeSeniors = $activeSeniorsStmt->fetchAll();

// Handle AJAX requests for real-time attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json');
	
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		echo json_encode(['success' => false, 'message' => 'Invalid session token']);
		exit;
	}
	
	$op = $_POST['op'] ?? '';
	if ($op === 'bulk_mark') {
		$event_id = (int)($_POST['event_id'] ?? 0);
		$present_ids = isset($_POST['present_ids']) && is_array($_POST['present_ids']) ? array_map('intval', $_POST['present_ids']) : [];
		
		if ($event_id && !empty($present_ids)) {
			$inserted = 0;
			$errors = [];
			foreach ($present_ids as $sid) {
				try {
					$stmt = $pdo->prepare('INSERT INTO attendance (senior_id, event_id) VALUES (?,?)');
					$stmt->execute([$sid, $event_id]);
					$inserted++;
				} catch (Throwable $e) {
					// ignore duplicates or errors for individual entries
					$errors[] = $e->getMessage();
				}
			}
			
			if ($inserted > 0) {
				// Broadcast update to other tabs/pages
				$updateData = [
					'event_id' => $event_id,
					'count' => $inserted,
					'timestamp' => time()
				];
				
				echo json_encode([
					'success' => true,
					'message' => "Attendance saved for $inserted senior(s).",
					'inserted' => $inserted,
					'event_id' => $event_id,
					'update_key' => 'attendance-updated-' . $event_id
				]);
			} else {
				echo json_encode([
					'success' => false,
					'message' => 'No new attendance saved (possibly already marked).'
				]);
			}
		} else {
			echo json_encode([
				'success' => false,
				'message' => 'Please select an event and at least one senior'
			]);
		}
		exit;
	} else {
		echo json_encode(['success' => false, 'message' => 'Invalid operation']);
		exit;
	}
}

// Regular page load - show message if redirected after POST
if (isset($_GET['success']) && $_GET['success'] === '1') {
	$message = isset($_GET['message']) ? urldecode($_GET['message']) : 'Attendance saved successfully';
}

$csrf = generate_csrf_token();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Attendance Management | SeniorCare Information System</title>
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Attendance table scroll styling */
		.attendance-table-scroll {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			max-width: 100%;
		}
		
		.attendance-table-scroll table {
			width: max-content;
			min-width: 100%;
			white-space: nowrap;
		}
		
		.attendance-table-scroll table th,
		.attendance-table-scroll table td {
			min-width: 120px;
			white-space: nowrap;
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Attendance Management</h1>
			<p class="content-subtitle">Mark attendance and track active seniors in <?= htmlspecialchars($user['barangay']) ?></p>
		</header>
		
		<div class="content-body">

			<?php if ($message): ?>
			<div class="alert alert-success animate-fade-in">
				<div class="alert-icon">
					<i class="fas fa-check-circle"></i>
				</div>
				<div class="alert-content">
					<strong>Success!</strong>
					<p><?= htmlspecialchars($message) ?></p>
				</div>
			</div>
			<?php endif; ?>


			<!-- Seniors Attendance List (Admin-like UI) -->
			<div class="card animate-fade-in">
				<form method="post" id="attendanceForm">
					<input type="hidden" name="csrf" value="<?= $csrf ?>" id="attendanceCsrf">
					<input type="hidden" name="op" value="bulk_mark">
					<div class="card-header">
						<h2 class="card-title">All Seniors</h2>
						<p class="card-subtitle">Check attendees, then save for the selected event</p>
					</div>
					<div id="attendanceMessage" style="display:none; margin: 1rem;" class="alert alert-success">
						<div class="alert-icon">
							<i class="fas fa-check-circle"></i>
						</div>
						<div class="alert-content">
							<strong>Success!</strong>
							<p id="attendanceMessageText"></p>
						</div>
					</div>
					<div class="table-controls">
						<input type="text" id="searchInput" placeholder="Search seniors..." class="form-input">
						<select name="event_id" id="event_id" class="form-select" required>
							<option value="">Choose event...</option>
							<?php foreach ($events as $e): ?>
								<option value="<?= (int)$e['id'] ?>">
									<?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?>
									<?php if ($e['event_time']): ?>(<?= htmlspecialchars($e['event_time']) ?>)<?php endif; ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button primary" id="saveAttendanceBtn">
							<i class="fas fa-save"></i> <span id="saveBtnText">Save Attendance</span>
						</button>
					</div>
					<div class="card-body">
						<div class="table-container table-scroll attendance-table-scroll">
							<table class="modern-table">
								<thead>
									<tr>
										<th>Present</th>
										<th>LAST NAME</th>
										<th>FIRST NAME</th>
										<th>MIDDLE NAME</th>
										<th>EXT</th>
										<th>AGE</th>
										<th>SEX</th>
										<th>BIRTHDATE</th>
										<th>OSCA ID NO.</th>
										<th>REMARKS</th>
										<th>HEALTH CONDITION</th>
										<th>PUROK</th>
										<th>PLACE OF BIRTH</th>
										<th>CELLPHONE #</th>
									</tr>
								</thead>
								<tbody id="seniorsTableBody">
									<?php if (!empty($seniors)): ?>
										<?php foreach ($seniors as $senior): ?>
										<tr>
											<td><input type="checkbox" name="present_ids[]" value="<?= (int)$senior['id'] ?>" /></td>
											<td><?= htmlspecialchars($senior['last_name']) ?></td>
											<td><?= htmlspecialchars($senior['first_name']) ?></td>
											<td><?= htmlspecialchars($senior['middle_name'] ?: '') ?></td>
											<td><?= isset($senior['ext_name']) ? htmlspecialchars($senior['ext_name']) : '' ?></td>
											<td><?= (int)$senior['age'] ?></td>
											<td>
												<?php
												switch ($senior['sex']) {
													case 'male': echo 'Male'; break;
													case 'female': echo 'Female'; break;
													case 'lgbtq': echo 'LGBTQ+'; break;
													default: echo 'Not specified';
												}
												?>
											</td>
											<td><?= $senior['date_of_birth'] ? date('M d, Y', strtotime($senior['date_of_birth'])) : '' ?></td>
											<td><?= htmlspecialchars($senior['osca_id_no'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['remarks'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['health_condition'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['purok'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['place_of_birth'] ?: '') ?></td>
											<td><?= htmlspecialchars($senior['cellphone'] ?? '') ?></td>
										</tr>
										<?php endforeach; ?>
									<?php else: ?>
									<tr class="no-data">
										<td colspan="14" style="text-align: center; padding: 2rem; color: var(--gov-text-muted);">
											No seniors found in your barangay.
										</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</form>
			</div>

			<!-- Attendees for Selected Event -->
			<div class="card animate-fade-in" id="attendeesCard" style="display:none;">
				<div class="card-header">
					<h2 class="card-title">Attendees for Selected Event</h2>
					<p class="card-subtitle" id="attendeesEventSubtitle"></p>
				</div>
				<div class="card-body">
					<div class="table-container table-scroll">
						<table class="modern-table">
							<thead>
								<tr>
									<th>Last Name</th>
									<th>First Name</th>
									<th>Middle</th>
									<th>Ext</th>
									<th>Age</th>
									<th>Sex</th>
									<th>OSCA ID</th>
									<th>Marked At</th>
								</tr>
							</thead>
							<tbody id="attendeesTableBody">
								<tr class="no-data"><td colspan="8" style="text-align:center; padding: 1rem;">No attendees yet.</td></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			</div>


		</div>
	</main>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Search filter similar to admin All Seniors
		document.getElementById('searchInput')?.addEventListener('input', function() {
			const filter = this.value.toLowerCase();
			const rows = document.querySelectorAll('#seniorsTableBody tr');
			rows.forEach(row => {
				if (row.classList.contains('no-data')) return;
				const lastName = row.cells[1]?.textContent.toLowerCase() || '';
				const firstName = row.cells[2]?.textContent.toLowerCase() || '';
				const middleName = row.cells[3]?.textContent.toLowerCase() || '';
				if (lastName.includes(filter) || firstName.includes(filter) || middleName.includes(filter)) {
					row.style.display = '';
				} else {
					row.style.display = 'none';
				}
			});
		});

		// Load attendees for selected event
		const eventSelect = document.getElementById('event_id');
		const attendeesCard = document.getElementById('attendeesCard');
		const attendeesSubtitle = document.getElementById('attendeesEventSubtitle');
		const attendeesTableBody = document.getElementById('attendeesTableBody');

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

		function escapeHtml(text) {
			if (!text) return '—';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}

		function renderAttendees(data) {
			if (!data || !Array.isArray(data.attendees)) {
				attendeesCard.style.display = 'none';
				return;
			}
			attendeesCard.style.display = '';
			const e = data.event || {};
			
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
			
			attendeesSubtitle.textContent = `${e.title || ''}${when ? ' — ' + when : ''} • ${data.attendees.length} attendee${data.attendees.length !== 1 ? 's' : ''}`;
			attendeesTableBody.innerHTML = '';
			if (data.attendees.length === 0) {
				attendeesTableBody.innerHTML = '<tr class="no-data"><td colspan="8" style="text-align:center; padding: 1rem; color:#6b7280;">No attendees yet.</td></tr>';
				return;
			}
			for (const a of data.attendees) {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${escapeHtml(a.last_name || '—')}</td>
					<td>${escapeHtml(a.first_name || '—')}</td>
					<td>${escapeHtml(a.middle_name || '—')}</td>
					<td>${escapeHtml(a.ext_name || '—')}</td>
					<td>${a.age ? a.age : '—'}</td>
					<td>${a.sex ? (a.sex.charAt(0).toUpperCase() + a.sex.slice(1)) : '—'}</td>
					<td>${escapeHtml(a.osca_id_no || '—')}</td>
					<td>${formatDateTime(a.marked_at)}</td>
				`;
				attendeesTableBody.appendChild(tr);
			}
		}

		async function loadAttendeesByEventId(eventId) {
			if (!eventId) {
				attendeesCard.style.display = 'none';
				return;
			}
			try {
				const res = await fetch(`fetch_event_attendees.php?event_id=${encodeURIComponent(eventId)}`, {
					credentials: 'same-origin'
				});
				const data = await res.json();
				renderAttendees(data);
			} catch (e) {
				attendeesCard.style.display = 'none';
			}
		}

		eventSelect?.addEventListener('change', function() {
			loadAttendeesByEventId(this.value);
		});

		// If a value is pre-selected (e.g., after postback if preserved in future), load attendees
		if (eventSelect && eventSelect.value) {
			loadAttendeesByEventId(eventSelect.value);
		}

		// Real-time attendance submission via AJAX
		const attendanceForm = document.getElementById('attendanceForm');
		const saveBtn = document.getElementById('saveAttendanceBtn');
		const saveBtnText = document.getElementById('saveBtnText');
		const attendanceMessage = document.getElementById('attendanceMessage');
		const attendanceMessageText = document.getElementById('attendanceMessageText');
		const attendanceCsrf = document.getElementById('attendanceCsrf');

		if (attendanceForm) {
			attendanceForm.addEventListener('submit', async function(e) {
				e.preventDefault();
				
				const eventId = eventSelect.value;
				const checkboxes = document.querySelectorAll('input[name="present_ids[]"]:checked');
				const presentIds = Array.from(checkboxes).map(cb => cb.value);
				
				if (!eventId || presentIds.length === 0) {
					showMessage('Please select an event and at least one senior', 'error');
					return;
				}

				// Disable button and show loading
				saveBtn.disabled = true;
				saveBtnText.textContent = 'Saving...';
				
				const formData = new FormData(attendanceForm);
				
				try {
					const response = await fetch(window.location.href, {
						method: 'POST',
						body: formData,
						credentials: 'same-origin'
					});
					
					const data = await response.json();
					
					if (data.success) {
						showMessage(data.message || 'Attendance saved successfully!', 'success');
						
						// Refresh attendees list immediately
						if (eventId) {
							await loadAttendeesByEventId(eventId);
						}
						
						// Broadcast update to other tabs/pages
						if (data.event_id) {
							window.localStorage.setItem('attendance-updated-' + data.event_id, Date.now().toString());
							window.localStorage.setItem('attendance-updated', JSON.stringify({
								event_id: data.event_id,
								timestamp: Date.now()
							}));
						}
						
						// Uncheck all checkboxes after successful save
						checkboxes.forEach(cb => cb.checked = false);
						
						// Refresh CSRF token
						if (data.csrf) {
							attendanceCsrf.value = data.csrf;
						}
					} else {
						showMessage(data.message || 'Failed to save attendance', 'error');
					}
				} catch (error) {
					console.error('Error saving attendance:', error);
					showMessage('An error occurred while saving attendance. Please try again.', 'error');
				} finally {
					saveBtn.disabled = false;
					saveBtnText.textContent = 'Save Attendance';
				}
			});
		}

		function showMessage(text, type) {
			attendanceMessageText.textContent = text;
			attendanceMessage.className = 'alert ' + (type === 'error' ? 'alert-error' : 'alert-success');
			attendanceMessage.style.display = 'flex';
			attendanceMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			
			// Auto-hide after 5 seconds
			setTimeout(() => {
				attendanceMessage.style.display = 'none';
			}, 5000);
		}

		// Listen for attendance updates from other tabs/pages
		window.addEventListener('storage', function(event) {
			if (event.key && event.key.startsWith('attendance-updated-')) {
				const eventId = event.key.replace('attendance-updated-', '');
				if (eventSelect && eventSelect.value === eventId) {
					loadAttendeesByEventId(eventId);
				}
			}
		});
	</script>
</body>
</html>