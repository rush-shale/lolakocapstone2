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
$seniorsStmt = $pdo->prepare("SELECT id, first_name, middle_name, last_name, age FROM seniors WHERE barangay=? AND life_status='living' ORDER BY last_name, first_name");
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
        a.created_at as attendance_date,
        s.first_name,
        s.last_name,
        s.middle_name,
        e.title as event_title,
        e.event_date
    FROM attendance a
    JOIN seniors s ON a.senior_id = s.id
    JOIN events e ON a.event_id = e.id
    WHERE s.barangay = ? AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY a.created_at DESC
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
        MAX(a.created_at) as last_attendance
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$event_id = (int)($_POST['event_id'] ?? 0);
		$senior_id = (int)($_POST['senior_id'] ?? 0);
		if ($event_id && $senior_id) {
			try {
				$stmt = $pdo->prepare('INSERT INTO attendance (senior_id, event_id) VALUES (?,?)');
				$stmt->execute([$senior_id, $event_id]);
				$message = 'Attendance marked successfully!';
			} catch (Throwable $e) {
				$message = 'Attendance already marked or error occurred';
			}
		} else {
			$message = 'Please select both event and senior';
		}
	}
}

$csrf = generate_csrf_token();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Attendance Management | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="content">
		<!-- Page Header -->
		<div class="page-header animate-fade-in">
			<div class="page-header-content">
				<div class="page-title">
					<h1>📅 Attendance Management</h1>
					<p>Mark attendance and track active seniors in <?= htmlspecialchars($user['barangay']) ?></p>
				</div>
			</div>
		</div>

		<!-- Alert Messages -->
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

		<!-- Statistics Cards -->
		<div class="stats animate-fade-in">
			<div class="stat success">
				<div class="stat-icon">
					<i class="fas fa-users"></i>
				</div>
				<div class="stat-content">
					<h3>Total Seniors</h3>
					<p class="number"><?= count($seniors) ?></p>
				</div>
			</div>
			<div class="stat info">
				<div class="stat-icon">
					<i class="fas fa-star"></i>
				</div>
				<div class="stat-content">
					<h3>Active Seniors</h3>
					<p class="number"><?= count($activeSeniors) ?></p>
				</div>
			</div>
			<div class="stat">
				<div class="stat-icon">
					<i class="fas fa-calendar"></i>
				</div>
				<div class="stat-content">
					<h3>Upcoming Events</h3>
					<p class="number"><?= count($events) ?></p>
				</div>
			</div>
			<div class="stat warning">
				<div class="stat-icon">
					<i class="fas fa-history"></i>
				</div>
				<div class="stat-content">
					<h3>Recent Attendance</h3>
					<p class="number"><?= count($attendanceHistory) ?></p>
				</div>
			</div>
		</div>

		<!-- Main Content Grid -->
		<div class="grid grid-2 animate-fade-in">
			<!-- Mark Attendance Card -->
			<div class="card">
				<div class="card-header">
					<h2>
						<i class="fas fa-plus"></i>
						Mark New Attendance
					</h2>
					<p>Record attendance for seniors at events</p>
				</div>
				<div class="card-body">
					<form method="post" class="modern-form">
						<input type="hidden" name="csrf" value="<?= $csrf ?>">
						
						<div class="form-group">
							<label for="event_id" class="form-label">
								<i class="fas fa-calendar-alt"></i>
								Select Event
							</label>
							<select name="event_id" id="event_id" class="form-select" required>
								<option value="">Choose an event...</option>
								<?php foreach ($events as $e): ?>
									<option value="<?= (int)$e['id'] ?>">
										<?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?>
										<?php if ($e['event_time']): ?>
											(<?= htmlspecialchars($e['event_time']) ?>)
										<?php endif; ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="input-focus-line"></div>
						</div>
						
						<div class="form-group">
							<label for="senior_id" class="form-label">
								<i class="fas fa-user"></i>
								Select Senior
							</label>
							<select name="senior_id" id="senior_id" class="form-select" required>
								<option value="">Choose a senior...</option>
								<?php foreach ($seniors as $s): ?>
									<option value="<?= (int)$s['id'] ?>">
										<?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?>
										<?php if ($s['middle_name']): ?>
											<?= htmlspecialchars(' ' . $s['middle_name']) ?>
										<?php endif; ?>
										(<?= $s['age'] ?> years)
									</option>
								<?php endforeach; ?>
							</select>
							<div class="input-focus-line"></div>
						</div>
						
						<div class="form-actions">
							<button type="submit" class="button primary">
								<i class="fas fa-check"></i>
								Mark Present
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Upcoming Events Card -->
			<div class="card">
				<div class="card-header">
					<h2>
						<i class="fas fa-calendar-check"></i>
						Upcoming Events
					</h2>
					<p>Events in <?= htmlspecialchars($user['barangay']) ?></p>
				</div>
				<div class="card-body">
					<?php if (!empty($events)): ?>
					<div class="events-list">
						<?php foreach ($events as $e): ?>
						<div class="event-item">
							<div class="event-date">
								<span class="date-day"><?= date('d', strtotime($e['event_date'])) ?></span>
								<span class="date-month"><?= date('M', strtotime($e['event_date'])) ?></span>
							</div>
							<div class="event-details">
								<h4><?= htmlspecialchars($e['title']) ?></h4>
								<p>
									<i class="fas fa-calendar"></i>
									<?= date('F j, Y', strtotime($e['event_date'])) ?>
									<?php if ($e['event_time']): ?>
										at <?= htmlspecialchars($e['event_time']) ?>
									<?php endif; ?>
								</p>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php else: ?>
					<div class="empty-state">
						<div class="empty-icon">
							<i class="fas fa-calendar-times"></i>
						</div>
						<h3>No Upcoming Events</h3>
						<p>No events scheduled for your barangay.</p>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Active Seniors Section -->
		<div class="card animate-fade-in">
			<div class="card-header">
				<h2>
					<i class="fas fa-star"></i>
					Most Active Seniors
				</h2>
				<p>Seniors who attended 3+ events in the last 30 days</p>
			</div>
			<div class="card-body">
				<?php if (!empty($activeSeniors)): ?>
				<div class="active-seniors-grid">
					<?php foreach ($activeSeniors as $senior): ?>
					<div class="active-senior-card">
						<div class="senior-avatar">
							<i class="fas fa-user"></i>
						</div>
						<div class="senior-info">
							<h4><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></h4>
							<?php if ($senior['middle_name']): ?>
							<p class="senior-middle"><?= htmlspecialchars($senior['middle_name']) ?></p>
							<?php endif; ?>
							<p class="senior-age"><?= $senior['age'] ?> years old</p>
						</div>
						<div class="attendance-stats">
							<div class="attendance-count">
								<span class="count-number"><?= $senior['attendance_count'] ?></span>
								<span class="count-label">Events</span>
							</div>
							<div class="last-attendance">
								<i class="fas fa-clock"></i>
								<span><?= date('M j', strtotime($senior['last_attendance'])) ?></span>
							</div>
						</div>
						<div class="award-badge">
							<i class="fas fa-trophy"></i>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php else: ?>
				<div class="empty-state">
					<div class="empty-icon">
						<i class="fas fa-star"></i>
					</div>
					<h3>No Active Seniors Yet</h3>
					<p>Seniors need to attend 3+ events to appear here.</p>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Recent Attendance History -->
		<div class="card animate-fade-in">
			<div class="card-header">
				<h2>
					<i class="fas fa-history"></i>
					Recent Attendance History
				</h2>
				<p>Last 30 days attendance records</p>
			</div>
			<div class="card-body">
				<?php if (!empty($attendanceHistory)): ?>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>Senior</th>
								<th>Event</th>
								<th>Event Date</th>
								<th>Marked On</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($attendanceHistory as $record): ?>
							<tr>
								<td>
									<div class="senior-info">
										<div class="senior-avatar small">
											<i class="fas fa-user"></i>
										</div>
										<div class="senior-details">
											<span class="senior-name"><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></span>
											<?php if ($record['middle_name']): ?>
											<span class="senior-middle"><?= htmlspecialchars($record['middle_name']) ?></span>
											<?php endif; ?>
										</div>
									</div>
								</td>
								<td>
									<span class="event-title"><?= htmlspecialchars($record['event_title']) ?></span>
								</td>
								<td>
									<span class="event-date"><?= date('M j, Y', strtotime($record['event_date'])) ?></span>
								</td>
								<td>
									<span class="attendance-date"><?= date('M j, Y g:i A', strtotime($record['attendance_date'])) ?></span>
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
					<h3>No Attendance Records</h3>
					<p>No attendance has been marked in the last 30 days.</p>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</main>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Initialize theme and functionality on page load
		document.addEventListener('DOMContentLoaded', function() {
			initializeTheme();
		});

		// Theme functionality
		function initializeTheme() {
			const savedTheme = localStorage.getItem('theme') || 'light';
			document.documentElement.setAttribute('data-theme', savedTheme);
		}
	</script>
</body>
</html>