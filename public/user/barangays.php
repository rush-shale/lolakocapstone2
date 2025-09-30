<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('user');
$pdo = get_db_connection();
$user = current_user();

$barangays = $pdo->query('SELECT * FROM barangays ORDER BY name')->fetchAll();

// Seniors grouped by barangay (living only)
$stmt = $pdo->query("SELECT barangay, id, first_name, last_name FROM seniors WHERE life_status='living' ORDER BY barangay, last_name, first_name");
$byBarangay = [];
foreach ($stmt as $row) { $byBarangay[$row['barangay']][] = $row; }

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Barangays & Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_user.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Barangays & Seniors</h1>
			<p class="content-subtitle">View seniors organized by barangay location</p>
		</header>
		
		<div class="content-body">
			<div class="search-container">
				<span class="search-icon">🔍</span>
				<input type="text" placeholder="Search barangays or seniors..." id="searchInput">
			</div>

			<!-- Statistics Cards -->
			<div class="stats animate-fade-in">
			<div class="stat">
				<div class="stat-icon">
					<i class="fas fa-map-marker-alt"></i>
				</div>
				<div class="stat-content">
					<h3>Total Barangays</h3>
					<p class="number"><?= count($barangays) ?></p>
				</div>
			</div>
			<div class="stat success">
				<div class="stat-icon">
					<i class="fas fa-users"></i>
				</div>
				<div class="stat-content">
					<h3>Total Seniors</h3>
					<p class="number"><?= array_sum(array_map('count', $byBarangay)) ?></p>
				</div>
			</div>
			<div class="stat info">
				<div class="stat-icon">
					<i class="fas fa-chart-pie"></i>
				</div>
				<div class="stat-content">
					<h3>Coverage</h3>
					<p class="number"><?= count(array_filter($byBarangay, function($seniors) { return !empty($seniors); })) ?>/<?= count($barangays) ?></p>
				</div>
			</div>
			</div>

			<!-- Barangays Grid -->
			<div class="barangays-grid animate-fade-in" id="barangaysGrid">
			<?php foreach ($barangays as $index => $barangay): ?>
			<div class="barangay-card" data-barangay="<?= strtolower(htmlspecialchars($barangay['name'])) ?>">
				<div class="barangay-card-header">
					<div class="barangay-info">
						<div class="barangay-icon">
							<i class="fas fa-map-marker-alt"></i>
						</div>
						<div class="barangay-details">
							<h3><?= htmlspecialchars($barangay['name']) ?></h3>
							<p class="barangay-count">
								<?php $seniorCount = count($byBarangay[$barangay['name']] ?? []); ?>
								<?= $seniorCount ?> <?= $seniorCount === 1 ? 'Senior' : 'Seniors' ?>
							</p>
						</div>
					</div>
					<div class="barangay-actions">
						<button class="button small secondary" onclick="toggleBarangay(<?= $index ?>)">
							<i class="fas fa-chevron-down"></i>
						</button>
					</div>
				</div>
				
				<div class="barangay-card-body" id="barangay-<?= $index ?>">
					<?php if (!empty($byBarangay[$barangay['name']] ?? [])): ?>
					<div class="seniors-list">
						<?php foreach ($byBarangay[$barangay['name']] as $senior): ?>
						<div class="senior-item">
							<div class="senior-avatar">
								<i class="fas fa-user"></i>
							</div>
							<div class="senior-info">
								<span class="senior-name"><?= htmlspecialchars($senior['last_name'] . ', ' . $senior['first_name']) ?></span>
								<span class="senior-id">ID: <?= $senior['id'] ?></span>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php else: ?>
					<div class="empty-barangay">
						<div class="empty-icon">
							<i class="fas fa-user-slash"></i>
						</div>
						<p>No seniors registered in this barangay</p>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
			</div>

			<?php if (empty($barangays)): ?>
			<div class="empty-state">
				<div class="empty-icon">
					<i class="fas fa-map-marker-alt"></i>
				</div>
				<h3>No Barangays Found</h3>
				<p>There are no barangays registered in the system yet.</p>
			</div>
			<?php endif; ?>
		</div>
	</main>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Initialize functionality on page load
		document.addEventListener('DOMContentLoaded', function() {
			initializeSearch();
			initializeBarangayCards();
		});

		// Search functionality
		function initializeSearch() {
			const searchInput = document.getElementById('searchInput');
			const barangaysGrid = document.getElementById('barangaysGrid');
			
			if (searchInput && barangaysGrid) {
				searchInput.addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					const cards = barangaysGrid.querySelectorAll('.barangay-card');
					let visibleCount = 0;
					
					cards.forEach(card => {
						const barangayName = card.dataset.barangay;
						const seniorNames = Array.from(card.querySelectorAll('.senior-name')).map(el => el.textContent.toLowerCase());
						const searchText = [barangayName, ...seniorNames].join(' ');
						
						const isVisible = searchText.includes(searchTerm);
						card.style.display = isVisible ? '' : 'none';
						if (isVisible) visibleCount++;
					});
					
					// Update search results indicator
					updateSearchResults(searchInput, visibleCount, cards.length);
				});
			}
		}

		function updateSearchResults(input, visible, total) {
			let indicator = input.parentNode.querySelector('.search-results');
			if (!indicator) {
				indicator = document.createElement('div');
				indicator.className = 'search-results';
				indicator.style.cssText = `
					position: absolute;
					right: 1rem;
					top: 50%;
					transform: translateY(-50%);
					font-size: var(--font-size-xs);
					color: var(--muted);
					font-weight: 600;
					background: var(--bg-secondary);
					padding: var(--space-xs) var(--space-sm);
					border-radius: var(--radius-sm);
				`;
				input.parentNode.style.position = 'relative';
				input.parentNode.appendChild(indicator);
			}
			
			if (total > visible) {
				indicator.textContent = `${visible} of ${total}`;
				indicator.style.color = 'var(--warning)';
			} else {
				indicator.textContent = '';
			}
		}

		// Barangay card functionality
		function initializeBarangayCards() {
			// Initially collapse all cards
			const cards = document.querySelectorAll('.barangay-card-body');
			cards.forEach(card => {
				card.style.display = 'none';
			});
		}

		function toggleBarangay(index) {
			const cardBody = document.getElementById(`barangay-${index}`);
			const button = event.target.closest('button');
			const icon = button.querySelector('i');
			
			if (cardBody.style.display === 'none') {
				cardBody.style.display = 'block';
				icon.className = 'fas fa-chevron-up';
				button.classList.add('active');
			} else {
				cardBody.style.display = 'none';
				icon.className = 'fas fa-chevron-down';
				button.classList.remove('active');
			}
		}
	</script>
</body>
</html>


