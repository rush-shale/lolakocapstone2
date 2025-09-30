<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

// Seniors in my barangay (living only)
$seniorsStmt = $pdo->prepare("SELECT id, first_name, last_name, age, benefits_received FROM seniors WHERE barangay=? AND life_status='living' ORDER BY last_name, first_name");
$seniorsStmt->execute([$user['barangay']]);
$seniors = $seniorsStmt->fetchAll();

// Active seniors based on attendance count (last 90 days by default)
$activeStmt = $pdo->prepare(
	"SELECT s.id, s.first_name, s.last_name, COUNT(a.id) AS attendances
	 FROM seniors s
	 JOIN attendance a ON a.senior_id = s.id
	 JOIN events e ON e.id = a.event_id
	 WHERE s.barangay = ? AND s.life_status = 'living' AND e.scope='barangay' AND e.barangay = ? AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
	 GROUP BY s.id, s.first_name, s.last_name
	 HAVING COUNT(a.id) >= 3
	 ORDER BY attendances DESC, s.last_name, s.first_name"
);
$activeStmt->execute([$user['barangay'], $user['barangay']]);
$active = $activeStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">My Barangay Seniors</h1>
			<p class="content-subtitle">View senior citizens in your barangay</p>
		</header>
		
		<div class="content-body">
			<div class="grid">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-users"></i>
							All Living Seniors
						</h2>
						<p class="card-subtitle">Complete list of senior citizens in your barangay</p>
					</div>
					<div class="card-body">
						<?php if (!empty($seniors)): ?>
						<div class="table-container">
							<table class="table">
								<thead>
									<tr>
										<th>Name</th>
										<th>Age</th>
										<th>Benefits Status</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($seniors as $s): ?>
									<tr>
										<td>
											<div class="senior-info">
												<div class="senior-avatar">
													<i class="fas fa-user"></i>
												</div>
												<div class="senior-details">
													<span class="senior-name"><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></span>
												</div>
											</div>
										</td>
										<td>
											<span class="age-badge"><?= (int)$s['age'] ?> years</span>
										</td>
										<td>
											<span class="badge <?= $s['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
												<?= $s['benefits_received'] ? 'Received' : 'Not Yet' ?>
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
								<i class="fas fa-users"></i>
							</div>
							<h3>No Seniors Found</h3>
							<p>No senior citizens are currently registered in your barangay.</p>
						</div>
						<?php endif; ?>
					</div>
				</div>
				
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-star"></i>
							Active Seniors
						</h2>
						<p class="card-subtitle">Seniors with 3+ attendances in the last 90 days</p>
					</div>
					<div class="card-body">
						<?php if (!empty($active)): ?>
						<div class="active-seniors-list">
							<?php foreach ($active as $a): ?>
							<div class="active-senior-item">
								<div class="senior-info">
									<div class="senior-avatar">
										<i class="fas fa-star"></i>
									</div>
									<div class="senior-details">
										<span class="senior-name"><?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?></span>
										<span class="attendance-count"><?= (int)$a['attendances'] ?> attendances</span>
									</div>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
						<?php else: ?>
						<div class="empty-state">
							<div class="empty-icon">
								<i class="fas fa-star"></i>
							</div>
							<h3>No Active Seniors</h3>
							<p>No seniors have attended 3 or more events in the last 90 days.</p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


