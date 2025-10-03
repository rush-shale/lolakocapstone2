<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

// Get comprehensive statistics
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living'")->fetchColumn();
$localSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND category = 'local'")->fetchColumn();
$nationalSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND category = 'national'")->fetchColumn();
$deceasedSeniors = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'deceased'")->fetchColumn();
$benefitsPending = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND benefits_received = 0")->fetchColumn();
$benefitsReceived = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status = 'living' AND benefits_received = 1")->fetchColumn();
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE scope = 'admin'")->fetchColumn();
$upcomingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE scope = 'admin' AND event_date >= CURDATE()")->fetchColumn();

// Get seniors data for dashboard sections
$allSeniors = $pdo->query("
    SELECT s.*, s.barangay as barangay_name 
    FROM seniors s 
    WHERE s.life_status = 'living'
    ORDER BY s.created_at DESC 
    LIMIT 10
")->fetchAll();

$localSeniorsList = $pdo->query("
    SELECT s.*, s.barangay as barangay_name 
    FROM seniors s 
    WHERE s.category = 'local' AND s.life_status = 'living'
    ORDER BY s.created_at DESC 
    LIMIT 10
")->fetchAll();

$nationalSeniorsList = $pdo->query("
    SELECT s.*, s.barangay as barangay_name 
    FROM seniors s 
    WHERE s.category = 'national' AND s.life_status = 'living'
    ORDER BY s.created_at DESC 
    LIMIT 10
")->fetchAll();

// Get upcoming events
$upcomingEventsList = $pdo->query("
    SELECT * FROM events 
    WHERE event_date >= CURDATE() AND scope = 'admin'
    ORDER BY event_date ASC 
    LIMIT 5
")->fetchAll();



$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Dashboard | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<!-- Removed welcome header and statistics overview -->

		<!-- Removed Simple Links for Local and National Seniors -->

		<!-- Senior Management Sections -->
		<div class="grid grid-2 animate-fade-in">

		<!-- Senior Management Sections -->
		<div class="grid grid-2 animate-fade-in">
			<!-- Local Seniors Card -->
			<a href="<?= BASE_URL ?>/admin/local_seniors.php" style="text-decoration: none; color: inherit;">
				<div class="card" style="background: white; border-radius: 12px; box-shadow: 0 3px 5px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; margin-bottom: 2.5rem; margin-top: 1.5rem;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 5px 10px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 5px rgba(0,0,0,0.08)';">
					<div class="card-header" style="border-bottom: 1px solid #eee; padding: 1rem;">
						<h2 style="margin: 0; color: #333; font-size: 1.2rem; display: flex; align-items: center;">
							<span style="font-size: 1.5rem; margin-right: 0.5rem;">🏘️</span>
							Local Seniors
						</h2>
						<p style="margin: 0.3rem 0 0; color: #666; font-size: 0.85rem;">Click to view detailed list</p>
					</div>
					<div class="card-body" style="padding: 1rem; text-align: center;">
						<div style="font-size: 2rem; font-weight: 700; color: #28a745; margin-bottom: 0.3rem;"><?= $localSeniors ?></div>
						<p style="margin: 0; color: #666; font-weight: 500; font-size: 0.9rem;">Registered Local Seniors</p>
					</div>
				</div>
			</a>

			<!-- National Seniors Card -->
			<a href="<?= BASE_URL ?>/admin/national_seniors.php" style="text-decoration: none; color: inherit;">
				<div class="card" style="background: white; border-radius: 12px; box-shadow: 0 3px 5px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; margin-top: 1.5rem;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 5px 10px rgba(0,0,0,0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 5px rgba(0,0,0,0.08)';">
					<div class="card-header" style="border-bottom: 1px solid #eee; padding: 1rem;">
						<h2 style="margin: 0; color: #333; font-size: 1.2rem; display: flex; align-items: center;">
							<span style="font-size: 1.5rem; margin-right: 0.5rem;">🇵🇭</span>
							National Seniors
						</h2>
						<p style="margin: 0.3rem 0 0; color: #666; font-size: 0.85rem;">Click to view detailed list</p>
					</div>
					<div class="card-body" style="padding: 1rem; text-align: center;">
						<div style="font-size: 2rem; font-weight: 700; color: #007bff; margin-bottom: 0.3rem;"><?= $nationalSeniors ?></div>
						<p style="margin: 0; color: #666; font-weight: 500; font-size: 0.9rem;">Registered National Seniors</p>
					</div>
				</div>
			</a>

			<!-- Upcoming Events -->
			<div class="card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); grid-column: span 2;">
				<div class="card-header" style="border-bottom: 1px solid #eee; padding: 1.5rem;">
					<h2 style="margin: 0; color: #333; font-size: 1.5rem; display: flex; align-items: center;">
						<span style="font-size: 2rem; margin-right: 0.5rem;">📅</span>
						Upcoming Events
					</h2>
					<p style="margin: 0.5rem 0 0; color: #666;">OSCA Head scheduled events</p>
				</div>
				<div class="card-body" style="padding: 1.5rem;">
					<?php if (!empty($upcomingEventsList)): ?>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
						<?php foreach ($upcomingEventsList as $event): ?>
						<div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #667eea;">
							<h4 style="margin: 0 0 0.5rem; color: #333; font-size: 1.1rem;"><?= htmlspecialchars($event['title']) ?></h4>
							<p style="margin: 0 0 0.5rem; font-size: 0.9rem; color: #666;">
								📅 <?= date('M d, Y', strtotime($event['event_date'])) ?>
							</p>
							<?php if ($event['description']): ?>
							<p style="margin: 0; font-size: 0.9rem; color: #555;">
								<?= htmlspecialchars(substr($event['description'], 0, 120)) ?><?= strlen($event['description']) > 120 ? '...' : '' ?>
							</p>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
					<?php else: ?>
					<div style="text-align: center; padding: 2rem; color: #666;">
						<p style="margin: 0 0 1rem; font-size: 1.1rem;">No upcoming events scheduled</p>
						<a href="<?= BASE_URL ?>/admin/events.php" class="button" style="background: #667eea; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500;">Create Event</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</main>

	<!-- Removed modal for Local Seniors as replaced by separate page -->

	<!-- Removed modal for National Seniors as replaced by separate page -->

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Modal functionality
		function openModal(modalId) {
			const modal = document.getElementById(modalId);
			modal.classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeModal(modalId) {
			const modal = document.getElementById(modalId);
			modal.classList.remove('active');
			document.body.style.overflow = '';
		}

		// Close modal when clicking outside
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('modal-overlay')) {
				e.target.classList.remove('active');
				document.body.style.overflow = '';
			}
		});

		// Close modal with Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				const activeModal = document.querySelector('.modal-overlay.active');
				if (activeModal) {
					activeModal.classList.remove('active');
					document.body.style.overflow = '';
				}
			}
		});

		// Advanced search functionality for modals
		document.addEventListener('DOMContentLoaded', function() {
			// All Seniors Modal Search
			const searchAllSeniorsModal = document.getElementById('searchAllSeniorsModal');
			const allSeniorsModalTable = document.getElementById('allSeniorsModalTable');
			
			if (searchAllSeniorsModal && allSeniorsModalTable) {
				searchAllSeniorsModal.addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					const rows = allSeniorsModalTable.querySelectorAll('tr');
					let visibleCount = 0;
					
					rows.forEach(row => {
						const text = row.textContent.toLowerCase();
						const isVisible = text.includes(searchTerm);
						row.style.display = isVisible ? '' : 'none';
						if (isVisible) visibleCount++;
					});
					
					// Update search results indicator
					updateSearchResults(searchAllSeniorsModal, visibleCount, rows.length);
				});
			}

			// Local Seniors Modal Search
			const searchLocalSeniorsModal = document.getElementById('searchLocalSeniorsModal');
			const localSeniorsModalTable = document.getElementById('localSeniorsModalTable');
			
			if (searchLocalSeniorsModal && localSeniorsModalTable) {
				searchLocalSeniorsModal.addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					const rows = localSeniorsModalTable.querySelectorAll('tr');
					let visibleCount = 0;
					
					rows.forEach(row => {
						const text = row.textContent.toLowerCase();
						const isVisible = text.includes(searchTerm);
						row.style.display = isVisible ? '' : 'none';
						if (isVisible) visibleCount++;
					});
					
					updateSearchResults(searchLocalSeniorsModal, visibleCount, rows.length);
				});
			}

			// National Seniors Modal Search
			const searchNationalSeniorsModal = document.getElementById('searchNationalSeniorsModal');
			const nationalSeniorsModalTable = document.getElementById('nationalSeniorsModalTable');
			
			if (searchNationalSeniorsModal && nationalSeniorsModalTable) {
				searchNationalSeniorsModal.addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					const rows = nationalSeniorsModalTable.querySelectorAll('tr');
					let visibleCount = 0;
					
					rows.forEach(row => {
						const text = row.textContent.toLowerCase();
						const isVisible = text.includes(searchTerm);
						row.style.display = isVisible ? '' : 'none';
						if (isVisible) visibleCount++;
					});
					
					updateSearchResults(searchNationalSeniorsModal, visibleCount, rows.length);
				});
			}
		});

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


	</script>
</body>
</html>


