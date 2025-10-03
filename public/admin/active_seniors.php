<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

$csrf = generate_csrf_token();
$barangays = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll();

$selected_barangay = $_GET['barangay'] ?? '';

// Fetch seniors with attended events count, filtered by barangay if selected
if ($selected_barangay && $selected_barangay !== 'all') {
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.age,
            s.sex,
            COUNT(a.id) AS attended_events
        FROM seniors s
        LEFT JOIN attendance a ON s.id = a.senior_id
        LEFT JOIN events e ON a.event_id = e.id
        WHERE s.life_status = 'living' AND s.barangay = ?
        GROUP BY s.id, s.first_name, s.last_name, s.age, s.sex
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$selected_barangay]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.age,
            s.sex,
            COUNT(a.id) AS attended_events
        FROM seniors s
        LEFT JOIN attendance a ON s.id = a.senior_id
        LEFT JOIN events e ON a.event_id = e.id
        WHERE s.life_status = 'living'
        GROUP BY s.id, s.first_name, s.last_name, s.age, s.sex
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute();
}
$seniors = $stmt->fetchAll();


?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Active Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Add any specific styles for this page here */
		body {
			font-family: 'Inter', sans-serif;
			background-color: #f8f9fa;
			color: #333;
		}
		.content-body {
			max-width: 1000px;
			margin: 0 auto;
			padding: 1rem;
		}
		label[for="barangayFilter"] {
			font-weight: 600;
			margin-right: 0.5rem;
		}
		select#barangayFilter {
			padding: 0.4rem 0.6rem;
			border-radius: 4px;
			border: 1px solid #ccc;
			font-size: 1rem;
			min-width: 180px;
			transition: border-color 0.3s ease;
		}
		select#barangayFilter:hover, select#barangayFilter:focus {
			border-color: #007bff;
			outline: none;
		}
		.card {
			background: white;
			border-radius: 8px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			padding: 1rem 1.5rem;
			margin-top: 1rem;
		}
		.card-header {
			border-bottom: 1px solid #e9ecef;
			padding-bottom: 0.5rem;
			margin-bottom: 1rem;
		}
		.card-title {
			font-weight: 700;
			font-size: 1.25rem;
			color: #212529;
		}
		table.table {
			width: 100%;
			border-collapse: collapse;
		}
		table.table thead tr {
			background-color: #f1f3f5;
		}
		table.table thead th {
			text-align: left;
			padding: 0.75rem 1rem;
			font-weight: 600;
			color: #495057;
			border-bottom: 2px solid #dee2e6;
		}
		table.table tbody tr {
			border-bottom: 1px solid #dee2e6;
			transition: background-color 0.2s ease;
		}
		table.table tbody tr:hover {
			background-color: #e9f5ff;
		}
		table.table tbody td {
			padding: 0.75rem 1rem;
			vertical-align: middle;
		}
		table.table tbody td a.senior-name-link {
			color: #007bff;
			text-decoration: none;
			font-weight: 500;
			transition: color 0.2s ease;
		}
		table.table tbody td a.senior-name-link:hover {
			color: #0056b3;
			text-decoration: underline;
		}
		.modal-overlay {
			display: none;
			position: fixed;
			inset: 0;
			background: rgba(0,0,0,0.5);
			backdrop-filter: blur(5px);
			justify-content: center;
			align-items: center;
			z-index: 1000;
		}
		.modal-overlay.active {
			display: flex;
		}
		.modal {
			background: white;
			border-radius: 8px;
			width: 90%;
			max-width: 600px;
			padding: 1.5rem;
			box-shadow: 0 2px 15px rgba(0,0,0,0.3);
			position: relative;
			animation: zoomIn 0.3s forwards;
		}
		.modal-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 1rem;
		}
		.modal-close {
			background: none;
			border: none;
			font-size: 1.5rem;
			cursor: pointer;
		}
		@keyframes zoomIn {
			from {
				transform: scale(0.5);
				opacity: 0;
			}
			to {
				transform: scale(1);
				opacity: 1;
			}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Active Seniors</h1>
			<p class="content-subtitle">Manage active senior citizen records</p>
		</header>

		<div class="content-body">
			<!-- Add filter by barangay -->
			<div style="margin-bottom: 1rem;">
				<label for="barangayFilter">Filter by Barangay:</label>
				<select id="barangayFilter" name="barangayFilter">
					<option value="all" <?= ($selected_barangay === 'all' || !$selected_barangay) ? 'selected' : '' ?>>All Barangays</option>
					<?php foreach ($barangays as $barangay): ?>
						<option value="<?= htmlspecialchars($barangay['name']) ?>" <?= ($selected_barangay === $barangay['name']) ? 'selected' : '' ?>>
							<?= htmlspecialchars($barangay['name']) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Example table listing active seniors -->
			<div class="main-content-area">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">Active Seniors List</h2>
					</div>
					<div class="card-body">
						<table class="table" id="seniorsTable">
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Age</th>
									<th>Gender</th>
									<th>Attended Events</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($seniors)): ?>
									<?php foreach ($seniors as $senior): ?>
										<tr>
											<td><?= htmlspecialchars($senior['id']) ?></td>
											<td>
												<a href="#" class="senior-name-link" data-senior-id="<?= $senior['id'] ?>">
													<?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?>
												</a>
											</td>
											<td><?= htmlspecialchars($senior['age']) ?></td>
											<td><?= htmlspecialchars($senior['sex'] ?? '') ?></td>
											<td><?= htmlspecialchars($senior['attended_events']) ?></td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr>
										<td colspan="5" style="text-align: center;">No active seniors found.</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</main>

	<!-- Modal for attended events -->
	<div class="modal-overlay" id="eventsModal">
		<div class="modal">
			<div class="modal-header">
				<h2 id="modalTitle">Attended Events</h2>
				<button class="modal-close" id="modalCloseBtn">&times;</button>
			</div>
			<div class="modal-body" id="modalBody">
				<p>Loading events...</p>
			</div>
		</div>
	</div>

	<script>
		document.getElementById('barangayFilter').addEventListener('change', function() {
			const selectedBarangay = this.value;
			const url = new URL(window.location.href);
			if (selectedBarangay === 'all') {
				url.searchParams.delete('barangay');
			} else {
				url.searchParams.set('barangay', selectedBarangay);
			}
			window.location.href = url.toString();
		});

		const modal = document.getElementById('eventsModal');
		const modalCloseBtn = document.getElementById('modalCloseBtn');
		const modalBody = document.getElementById('modalBody');
		const modalTitle = document.getElementById('modalTitle');

		modalCloseBtn.addEventListener('click', () => {
			modal.classList.remove('active');
			modalBody.innerHTML = '<p>Loading events...</p>';
		});

		document.querySelectorAll('.senior-name-link').forEach(link => {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				const seniorId = this.getAttribute('data-senior-id');
				modal.classList.add('active');
				modalTitle.textContent = 'Attended Events for ' + this.textContent;

				// Fetch attended events via AJAX
				fetch(`fetch_senior_events.php?senior_id=${seniorId}`)
					.then(response => response.json())
					.then(data => {
						if (data.length === 0) {
							modalBody.innerHTML = '<p>No attended events found.</p>';
						} else {
							let html = '<ul>';
							data.forEach(event => {
								html += `<li>${event.title} - ${event.event_date}</li>`;
							});
							html += '</ul>';
							modalBody.innerHTML = html;
						}
					})
					.catch(() => {
						modalBody.innerHTML = '<p>Error loading events.</p>';
					});
			});
		});
	</script>
</body>
</html>
