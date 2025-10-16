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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$op = $_POST['op'] ?? '';
		if ($op === 'bulk_mark') {
			$event_id = (int)($_POST['event_id'] ?? 0);
			$present_ids = isset($_POST['present_ids']) && is_array($_POST['present_ids']) ? array_map('intval', $_POST['present_ids']) : [];
			if ($event_id && !empty($present_ids)) {
				$inserted = 0;
				foreach ($present_ids as $sid) {
					try {
						$stmt = $pdo->prepare('INSERT INTO attendance (senior_id, event_id) VALUES (?,?)');
						$stmt->execute([$sid, $event_id]);
						$inserted++;
					} catch (Throwable $e) {
						// ignore duplicates or errors for individual entries
					}
				}
				$message = $inserted > 0 ? "Attendance saved for $inserted senior(s)." : 'No new attendance saved (possibly already marked).';
			} else {
				$message = 'Please select an event and at least one senior';
			}
		} else {
			$message = 'Invalid operation';
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
	<title>Attendance Management | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

			<!-- Seniors Attendance List (Admin-like UI) -->
			<div class="card animate-fade-in" style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: none; overflow: hidden;">
				<form method="post">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="bulk_mark">
					<div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
						<div>
							<h2 class="card-title">All Seniors</h2>
							<p class="card-subtitle">Check attendees, then save for the selected event</p>
						</div>
						<div style="display: flex; align-items: center; gap: 1rem;">
							<input type="text" id="searchInput" placeholder="Search seniors..." style="padding: 0.5rem; width: 250px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
							<select name="event_id" id="event_id" class="form-select" required>
								<option value="">Choose event...</option>
								<?php foreach ($events as $e): ?>
									<option value="<?= (int)$e['id'] ?>">
										<?= htmlspecialchars($e['event_date']) ?> - <?= htmlspecialchars($e['title']) ?>
										<?php if ($e['event_time']): ?>(<?= htmlspecialchars($e['event_time']) ?>)<?php endif; ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="button primary"><i class="fas fa-save"></i> Save Attendance</button>
						</div>
					</div>
					<div class="card-body">
						<div class="table-container">
							<table class="table">
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
	</script>
</body>
</html>