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

// Get new seniors statistics for graphs
$currentMonth = date('Y-m');
$currentYear = date('Y');

// Monthly new seniors data
$monthlyNewSeniors = $pdo->query("
    SELECT COUNT(*) as count 
    FROM seniors 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'
")->fetchColumn();

$monthlyTotalSeniors = $pdo->query("
    SELECT COUNT(*) as count 
    FROM seniors 
    WHERE DATE_FORMAT(created_at, '%Y-%m') <= '$currentMonth'
")->fetchColumn();

// Yearly new seniors data
$yearlyNewSeniors = $pdo->query("
    SELECT COUNT(*) as count 
    FROM seniors 
    WHERE YEAR(created_at) = '$currentYear'
")->fetchColumn();

$yearlyTotalSeniors = $pdo->query("
    SELECT COUNT(*) as count 
    FROM seniors 
    WHERE YEAR(created_at) <= '$currentYear'
")->fetchColumn();

// Calculate percentages
$monthlyPercentage = $monthlyTotalSeniors > 0 ? round(($monthlyNewSeniors / $monthlyTotalSeniors) * 100, 1) : 0;
$yearlyPercentage = $yearlyTotalSeniors > 0 ? round(($yearlyNewSeniors / $yearlyTotalSeniors) * 100, 1) : 0;

// Get monthly data for the last 12 months for chart
$monthlyChartData = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_seniors,
        (SELECT COUNT(*) FROM seniors WHERE DATE_FORMAT(created_at, '%Y-%m') <= DATE_FORMAT(s.created_at, '%Y-%m')) as total_seniors
    FROM seniors s
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

// Get yearly data for the last 5 years for chart
$yearlyChartData = $pdo->query("
    SELECT 
        YEAR(created_at) as year,
        COUNT(*) as new_seniors,
        (SELECT COUNT(*) FROM seniors WHERE YEAR(created_at) <= YEAR(s.created_at)) as total_seniors
    FROM seniors s
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
    GROUP BY YEAR(created_at)
    ORDER BY year ASC
")->fetchAll();

$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Dashboard | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<div class="page-header animate-fade-in">
			<h1>Welcome back, <?= htmlspecialchars($user['name']) ?></h1>
			<p>Here's an overview of your senior citizen management system</p>
		</div>
		

		<!-- Senior Management Sections -->
		<div class="grid grid-2 animate-fade-in">
			<!-- Local Seniors Compact Card -->
			<div class="compact-card" onclick="openModal('localSeniorsModal')">
				<div class="compact-card-header">
					<h3 class="compact-card-title">
						<span>🏘️</span>
						Local Seniors
					</h3>
					<span class="compact-card-count"><?= $localSeniors ?></span>
				</div>
				<div class="compact-card-preview">
					<?php foreach (array_slice($localSeniorsList, 0, 3) as $senior): ?>
					<div class="compact-card-item">
						<span class="compact-card-item-name"><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></span>
						<span class="compact-card-item-badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
							<?= $senior['benefits_received'] ? 'Received' : 'Pending' ?>
						</span>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="compact-card-footer">
					<button class="compact-card-action">View All</button>
					<span class="compact-card-more">Click to expand</span>
				</div>
			</div>

			<!-- National Seniors Compact Card -->
			<div class="compact-card" onclick="openModal('nationalSeniorsModal')">
				<div class="compact-card-header">
					<h3 class="compact-card-title">
						<span>🇵🇭</span>
						National Seniors
					</h3>
					<span class="compact-card-count"><?= $nationalSeniors ?></span>
				</div>
				<div class="compact-card-preview">
					<?php foreach (array_slice($nationalSeniorsList, 0, 3) as $senior): ?>
					<div class="compact-card-item">
						<span class="compact-card-item-name"><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></span>
						<span class="compact-card-item-badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
							<?= $senior['benefits_received'] ? 'Received' : 'Pending' ?>
						</span>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="compact-card-footer">
					<button class="compact-card-action">View All</button>
					<span class="compact-card-more">Click to expand</span>
				</div>
			</div>

			<!-- Upcoming Events -->
			<div class="card">
				<div class="card-header">
					<h2>📅 Upcoming Events</h2>
					<p>OSCA Head scheduled events</p>
				</div>
				<?php if (!empty($upcomingEventsList)): ?>
				<div style="display: flex; flex-direction: column; gap: var(--space-md);">
					<?php foreach ($upcomingEventsList as $event): ?>
					<div style="padding: var(--space-md); background: var(--bg-secondary); border-radius: var(--radius-lg); border-left: 4px solid var(--primary);">
						<h4 style="margin: 0 0 var(--space-xs); color: var(--text);"><?= htmlspecialchars($event['title']) ?></h4>
						<p style="margin: 0; font-size: var(--font-size-sm); color: var(--muted);">
							📅 <?= date('M d, Y', strtotime($event['event_date'])) ?>
						</p>
						<?php if ($event['description']): ?>
						<p style="margin: var(--space-xs) 0 0; font-size: var(--font-size-sm); color: var(--text-secondary);">
							<?= htmlspecialchars(substr($event['description'], 0, 100)) ?><?= strlen($event['description']) > 100 ? '...' : '' ?>
						</p>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<?php else: ?>
				<div style="text-align: center; padding: var(--space-xl); color: var(--muted);">
					<p>No upcoming events scheduled</p>
					<a href="<?= BASE_URL ?>/admin/events.php" class="button small">Create Event</a>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- New Seniors Analytics Section -->
		<div class="grid grid-2 animate-fade-in" style="margin-top: var(--space-xl);">
			<!-- Monthly New Seniors Chart -->
			<div class="card">
				<div class="card-header">
					<h2>📊 Monthly New Seniors</h2>
					<p>Percentage of new seniors added this month</p>
				</div>
				<div class="chart-container">
					<div class="chart-stat">
						<div class="chart-stat-number"><?= $monthlyPercentage ?>%</div>
						<div class="chart-stat-label">This Month</div>
						<div class="chart-stat-detail"><?= $monthlyNewSeniors ?> new seniors</div>
					</div>
					<canvas id="monthlyChart" width="400" height="200"></canvas>
				</div>
			</div>

			<!-- Yearly New Seniors Chart -->
			<div class="card">
				<div class="card-header">
					<h2>📈 Yearly New Seniors</h2>
					<p>Percentage of new seniors added this year</p>
				</div>
				<div class="chart-container">
					<div class="chart-stat">
						<div class="chart-stat-number"><?= $yearlyPercentage ?>%</div>
						<div class="chart-stat-label">This Year</div>
						<div class="chart-stat-detail"><?= $yearlyNewSeniors ?> new seniors</div>
					</div>
					<canvas id="yearlyChart" width="400" height="200"></canvas>
				</div>
			</div>
		</div>
	</main>

	<!-- Modal for Local Seniors -->
	<div class="modal-overlay" id="localSeniorsModal">
		<div class="modal">
			<div class="modal-header">
				<h2 class="modal-title">
					<span>🏘️</span>
					Local Seniors (<?= $localSeniors ?>)
				</h2>
				<button class="modal-close" onclick="closeModal('localSeniorsModal')">&times;</button>
			</div>
			<div class="modal-body">
				<div class="modal-search">
					<span class="modal-search-icon">🔍</span>
					<input type="text" placeholder="Search local seniors..." id="searchLocalSeniorsModal">
				</div>
				<div class="modal-table">
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Age</th>
								<th>Barangay</th>
								<th>Benefits</th>
								<th>Contact</th>
							</tr>
						</thead>
						<tbody id="localSeniorsModalTable">
							<?php foreach ($localSeniorsList as $senior): ?>
							<tr>
								<td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
								<td><?= $senior['age'] ?></td>
								<td><?= htmlspecialchars($senior['barangay_name'] ?? 'N/A') ?></td>
								<td>
									<span class="badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
										<?= $senior['benefits_received'] ? 'Received' : 'Pending' ?>
									</span>
								</td>
								<td><?= htmlspecialchars($senior['contact'] ?? 'N/A') ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal for National Seniors -->
	<div class="modal-overlay" id="nationalSeniorsModal">
		<div class="modal">
			<div class="modal-header">
				<h2 class="modal-title">
					<span>🇵🇭</span>
					National Seniors (<?= $nationalSeniors ?>)
				</h2>
				<button class="modal-close" onclick="closeModal('nationalSeniorsModal')">&times;</button>
			</div>
			<div class="modal-body">
				<div class="modal-search">
					<span class="modal-search-icon">🔍</span>
					<input type="text" placeholder="Search national seniors..." id="searchNationalSeniorsModal">
				</div>
				<div class="modal-table">
					<table>
						<thead>
							<tr>
								<th>Name</th>
								<th>Age</th>
								<th>Barangay</th>
								<th>Benefits</th>
								<th>Contact</th>
							</tr>
						</thead>
						<tbody id="nationalSeniorsModalTable">
							<?php foreach ($nationalSeniorsList as $senior): ?>
							<tr>
								<td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
								<td><?= $senior['age'] ?></td>
								<td><?= htmlspecialchars($senior['barangay_name'] ?? 'N/A') ?></td>
								<td>
									<span class="badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
										<?= $senior['benefits_received'] ? 'Received' : 'Pending' ?>
									</span>
								</td>
								<td><?= htmlspecialchars($senior['contact'] ?? 'N/A') ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

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

		// Initialize Charts
		document.addEventListener('DOMContentLoaded', function() {
			// Monthly Chart Data
			const monthlyData = <?= json_encode($monthlyChartData) ?>;
			const monthlyLabels = monthlyData.map(item => {
				const date = new Date(item.month + '-01');
				return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
			});
			const monthlyNewSeniors = monthlyData.map(item => item.new_seniors);
			const monthlyPercentages = monthlyData.map(item => {
				return item.total_seniors > 0 ? ((item.new_seniors / item.total_seniors) * 100).toFixed(1) : 0;
			});

			// Yearly Chart Data
			const yearlyData = <?= json_encode($yearlyChartData) ?>;
			const yearlyLabels = yearlyData.map(item => item.year.toString());
			const yearlyNewSeniors = yearlyData.map(item => item.new_seniors);
			const yearlyPercentages = yearlyData.map(item => {
				return item.total_seniors > 0 ? ((item.new_seniors / item.total_seniors) * 100).toFixed(1) : 0;
			});

			// Monthly Chart
			const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
			new Chart(monthlyCtx, {
				type: 'line',
				data: {
					labels: monthlyLabels,
					datasets: [{
						label: 'New Seniors (%)',
						data: monthlyPercentages,
						borderColor: '#6366f1',
						backgroundColor: 'rgba(99, 102, 241, 0.1)',
						borderWidth: 3,
						fill: true,
						tension: 0.4,
						pointBackgroundColor: '#6366f1',
						pointBorderColor: '#ffffff',
						pointBorderWidth: 2,
						pointRadius: 6,
						pointHoverRadius: 8
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							titleColor: '#ffffff',
							bodyColor: '#ffffff',
							borderColor: '#6366f1',
							borderWidth: 1,
							callbacks: {
								label: function(context) {
									return 'New Seniors: ' + context.parsed.y + '%';
								}
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							max: 100,
							ticks: {
								callback: function(value) {
									return value + '%';
								},
								color: '#6b7280',
								font: {
									size: 12
								}
							},
							grid: {
								color: 'rgba(107, 114, 128, 0.1)'
							}
						},
						x: {
							ticks: {
								color: '#6b7280',
								font: {
									size: 12
								}
							},
							grid: {
								color: 'rgba(107, 114, 128, 0.1)'
							}
						}
					},
					interaction: {
						intersect: false,
						mode: 'index'
					}
				}
			});

			// Yearly Chart
			const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
			new Chart(yearlyCtx, {
				type: 'bar',
				data: {
					labels: yearlyLabels,
					datasets: [{
						label: 'New Seniors (%)',
						data: yearlyPercentages,
						backgroundColor: 'rgba(139, 92, 246, 0.8)',
						borderColor: '#8b5cf6',
						borderWidth: 2,
						borderRadius: 6,
						borderSkipped: false,
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							titleColor: '#ffffff',
							bodyColor: '#ffffff',
							borderColor: '#8b5cf6',
							borderWidth: 1,
							callbacks: {
								label: function(context) {
									return 'New Seniors: ' + context.parsed.y + '%';
								}
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							max: 100,
							ticks: {
								callback: function(value) {
									return value + '%';
								},
								color: '#6b7280',
								font: {
									size: 12
								}
							},
							grid: {
								color: 'rgba(107, 114, 128, 0.1)'
							}
						},
						x: {
							ticks: {
								color: '#6b7280',
								font: {
									size: 12
								}
							},
							grid: {
								color: 'rgba(107, 114, 128, 0.1)'
							}
						}
					}
				}
			});
		});
	</script>
</body>
</html>


