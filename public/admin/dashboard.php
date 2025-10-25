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
$upcomingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE scope = 'admin' AND event_date >= CURDATE()")
	->fetchColumn();

// Get seniors data for dashboard sections
$allSeniors = $pdo->query("\n    SELECT s.*, s.barangay as barangay_name \n    FROM seniors s \n    WHERE s.life_status = 'living'\n    ORDER BY s.created_at DESC \n    LIMIT 10\n")->fetchAll();

$localSeniorsList = $pdo->query("\n    SELECT s.*, s.barangay as barangay_name \n    FROM seniors s \n    WHERE s.category = 'local' AND s.life_status = 'living'\n    ORDER BY s.created_at DESC \n    LIMIT 10\n")->fetchAll();

$nationalSeniorsList = $pdo->query("\n    SELECT s.*, s.barangay as barangay_name \n    FROM seniors s \n    WHERE s.category = 'national' AND s.life_status = 'living'\n    ORDER BY s.created_at DESC \n    LIMIT 10\n")->fetchAll();

// Get upcoming events with organizer name
$upcomingEventsList = $pdo->query("\n    SELECT e.*, u.name AS organizer_name\n    FROM events e\n    LEFT JOIN users u ON e.created_by = u.id\n    WHERE e.event_date >= CURDATE()\n    ORDER BY e.event_date ASC \n    LIMIT 5\n")->fetchAll();

// Graph data for current year registrations
$year = (int)date('Y');
$stmtMonthly = $pdo->prepare('SELECT MONTH(created_at) AS m, COUNT(*) AS c FROM seniors WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)');
$stmtMonthly->execute([$year]);
$monthlyRaw = $stmtMonthly->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyCounts = [];
for ($i = 1; $i <= 12; $i++) { $monthlyCounts[] = (int)($monthlyRaw[$i] ?? 0); }
$thisMonthCount = $monthlyCounts[(int)date('n') - 1] ?? 0;
$yearTotal = array_sum($monthlyCounts) ?: 1;
$thisMonthPct = round(($thisMonthCount / $yearTotal) * 100);

// Calendar events for current month
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');
$stmtCal = $pdo->prepare("SELECT e.*, u.name AS organizer_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.event_date BETWEEN ? AND ? ORDER BY e.event_date ASC");
$stmtCal->execute([$firstDay, $lastDay]);
$calEvents = [];
foreach ($stmtCal->fetchAll() as $ev) {
    $d = (int)date('j', strtotime($ev['event_date']));
    $calEvents[$d][] = $ev;
}



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
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
	<style>
		/* Responsive Dashboard Styles */
		.dashboard-grid {
			display: grid;
			grid-template-columns: 2fr 1fr 1fr;
			grid-template-rows: auto auto auto;
			gap: 1.5rem;
			padding: 1.5rem;
		}

		.dash-graph {
			grid-column: 1;
			grid-row: 1;
		}

		.dash-local {
			grid-column: 2;
			grid-row: 1;
		}

		.dash-national {
			grid-column: 3;
			grid-row: 1;
		}

		.dash-upcoming {
			grid-column: 2 / 4;
			grid-row: 2;
		}

		.dash-calendar {
			grid-column: 1;
			grid-row: 2 / 4;
		}

		/* Mobile Responsive */
		@media (max-width: 1200px) {
			.dashboard-grid {
				grid-template-columns: 1fr 1fr;
				grid-template-rows: auto auto auto auto;
			}

			.dash-graph {
				grid-column: 1 / 3;
				grid-row: 1;
			}

			.dash-local {
				grid-column: 1;
				grid-row: 2;
			}

			.dash-national {
				grid-column: 2;
				grid-row: 2;
			}

			.dash-upcoming {
				grid-column: 1 / 3;
				grid-row: 3;
			}

			.dash-calendar {
				grid-column: 1 / 3;
				grid-row: 4;
			}
		}

		@media (max-width: 768px) {
			.dashboard-grid {
				grid-template-columns: 1fr;
				grid-template-rows: auto auto auto auto auto;
				gap: 1rem;
				padding: 1rem;
			}

			.dash-graph {
				grid-column: 1;
				grid-row: 1;
			}

			.dash-local {
				grid-column: 1;
				grid-row: 2;
			}

			.dash-national {
				grid-column: 1;
				grid-row: 3;
			}

			.dash-upcoming {
				grid-column: 1;
				grid-row: 4;
			}

			.dash-calendar {
				grid-column: 1;
				grid-row: 5;
			}

			/* Mobile card adjustments */
			.dash-mini .card-header,
			.dash-mini .card-body {
				padding: 1rem;
			}

			.dash-mini h2 {
				font-size: 0.9rem;
			}

			.dash-mini .number {
				font-size: 1.25rem;
			}

			/* Chart responsive */
			.chart-wrap {
				height: 200px;
			}

			/* Calendar responsive */
			.calendar-grid {
				grid-template-columns: repeat(7, 1fr);
				gap: 0.25rem;
			}

			.cal-day {
				min-height: 60px;
				font-size: 0.8rem;
			}

			.cal-evt {
				font-size: 0.7rem;
				padding: 0.1rem;
			}
		}

		@media (max-width: 480px) {
			.dashboard-grid {
				padding: 0.5rem;
				gap: 0.75rem;
			}

			.card {
				margin: 0;
			}

			.dash-mini .card-header,
			.dash-mini .card-body {
				padding: 0.75rem;
			}

			.dash-mini h2 {
				font-size: 0.8rem;
			}

			.dash-mini .number {
				font-size: 1.1rem;
			}

			.dash-mini p {
				font-size: 0.7rem;
			}

			/* Chart mobile */
			.chart-wrap {
				height: 150px;
			}

			/* Calendar mobile */
			.calendar-dow {
				font-size: 0.7rem;
			}

			.cal-day {
				min-height: 50px;
				font-size: 0.7rem;
			}

			.cal-evt {
				font-size: 0.6rem;
				padding: 0.05rem;
			}

			/* Upcoming events mobile */
			.upcoming-item {
				padding: 0.5rem;
			}

			.up-title {
				font-size: 0.8rem;
			}

			.up-meta {
				font-size: 0.7rem;
			}
		}

		/* Tablet landscape */
		@media (min-width: 769px) and (max-width: 1024px) {
			.dashboard-grid {
				grid-template-columns: 1fr 1fr;
				grid-template-rows: auto auto auto;
			}

			.dash-graph {
				grid-column: 1 / 3;
				grid-row: 1;
			}

			.dash-local {
				grid-column: 1;
				grid-row: 2;
			}

			.dash-national {
				grid-column: 2;
				grid-row: 2;
			}

			.dash-upcoming {
				grid-column: 1 / 3;
				grid-row: 3;
			}

			.dash-calendar {
				display: none; /* Hide calendar on tablet for better layout */
			}
		}

		/* Large screens optimization */
		@media (min-width: 1400px) {
			.dashboard-grid {
				max-width: 1400px;
				margin: 0 auto;
			}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<div class="dashboard-grid">
			<!-- Graph -->
			<div class="card dash-graph modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">📊 Registration Trends</h2>
						<p class="card-subtitle">Seniors registered in <?= $year ?> (<?= $thisMonthPct ?>% this month)</p>
					</div>
					<div class="card-actions">
						<button class="btn-icon" title="Refresh data" onclick="refreshChart()">
							<span>🔄</span>
						</button>
						<button class="btn-icon" title="Export data" onclick="exportChart()">
							<span>📥</span>
						</button>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<div class="chart-container">
						<div class="chart-wrap"><canvas id="registrationsChart"></canvas></div>
						<div class="chart-overlay" id="chartOverlay">
							<div class="loading-spinner"></div>
							<span>Loading chart data...</span>
						</div>
					</div>
					<div class="chart-stats">
						<div class="stat-item">
							<span class="stat-label">This Month</span>
							<span class="stat-value"><?= $thisMonthCount ?></span>
						</div>
						<div class="stat-item">
							<span class="stat-label">Year Total</span>
							<span class="stat-value"><?= $yearTotal ?></span>
						</div>
						<div class="stat-item">
							<span class="stat-label">Monthly Avg</span>
							<span class="stat-value"><?= round($yearTotal / 12) ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Second row: Local -->
			<a href="<?= BASE_URL ?>/admin/local_seniors.php" class="dash-local modern-stat-card" style="text-decoration: none; color: inherit;">
				<div class="card dash-mini modern-mini-card">
					<div class="card-header modern-mini-header">
						<div class="mini-card-icon local-icon">🏘️</div>
						<div class="mini-card-content">
							<h3 class="mini-card-title">Local Seniors</h3>
							<p class="mini-card-subtitle">Municipal residents</p>
						</div>
						<div class="mini-card-arrow">→</div>
					</div>
					<div class="card-body modern-mini-body">
						<div class="mini-stat-number"><?= $localSeniors ?></div>
						<div class="mini-stat-trend">
							<span class="trend-icon">📈</span>
							<span class="trend-text">Active</span>
						</div>
					</div>
				</div>
			</a>

			<!-- Second row: National -->
			<a href="<?= BASE_URL ?>/admin/national_seniors.php" class="dash-national modern-stat-card" style="text-decoration: none; color: inherit;">
				<div class="card dash-mini modern-mini-card">
					<div class="card-header modern-mini-header">
						<div class="mini-card-icon national-icon">🇵🇭</div>
						<div class="mini-card-content">
							<h3 class="mini-card-title">National Seniors</h3>
							<p class="mini-card-subtitle">Philippine citizens</p>
						</div>
						<div class="mini-card-arrow">→</div>
					</div>
					<div class="card-body modern-mini-body">
						<div class="mini-stat-number"><?= $nationalSeniors ?></div>
						<div class="mini-stat-trend">
							<span class="trend-icon">📊</span>
							<span class="trend-text">Registered</span>
						</div>
					</div>
				</div>
			</a>

			<!-- Right column: Upcoming spans rows 2-3 -->
			<div class="card dash-upcoming modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">📅 Upcoming Events</h2>
						<p class="card-subtitle">Scheduled activities and meetings</p>
					</div>
					<div class="card-actions">
						<a href="<?= BASE_URL ?>/admin/events.php" class="btn-icon" title="View all events">
							<span>📋</span>
						</a>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<?php if (!empty($upcomingEventsList)): ?>
						<div class="upcoming-list modern-event-list">
							<?php foreach ($upcomingEventsList as $event): ?>
								<div class="upcoming-item modern-event-item">
									<div class="event-date-badge">
										<span class="event-day"><?= date('d', strtotime($event['event_date'])) ?></span>
										<span class="event-month"><?= date('M', strtotime($event['event_date'])) ?></span>
									</div>
									<div class="event-details">
										<h4 class="event-title"><?= htmlspecialchars($event['title']) ?></h4>
										<div class="event-meta">
											<span class="event-organizer">👤 <?= htmlspecialchars($event['organizer_name'] ?: 'Unknown') ?></span>
											<span class="event-time">🕐 <?= $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : 'All Day' ?></span>
										</div>
									</div>
									<div class="event-status">
										<span class="status-badge upcoming">Upcoming</span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else: ?>
						<div class="empty-state modern-empty-state">
							<div class="empty-icon">📅</div>
							<h3>No Upcoming Events</h3>
							<p>No events are scheduled at the moment.</p>
							<a href="<?= BASE_URL ?>/admin/events.php" class="btn btn-primary">
								<span>Create Event</span>
								<span>+</span>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Calendar -->
			<div class="card dash-calendar modern-card">
				<div class="card-header modern-card-header">
					<div class="card-title-section">
						<h2 class="card-title">🗓️ <?= date('F Y') ?></h2>
						<p class="card-subtitle">Monthly event calendar</p>
					</div>
					<div class="card-actions">
						<button class="btn-icon" title="Previous month" onclick="changeMonth(-1)">
							<span>◀</span>
						</button>
						<button class="btn-icon" title="Next month" onclick="changeMonth(1)">
							<span>▶</span>
						</button>
					</div>
				</div>
				<div class="card-body modern-card-body">
					<div class="calendar-container">
						<div class="calendar-dow modern-calendar-dow">
							<div class="dow-item">SUN</div>
							<div class="dow-item">MON</div>
							<div class="dow-item">TUE</div>
							<div class="dow-item">WED</div>
							<div class="dow-item">THU</div>
							<div class="dow-item">FRI</div>
							<div class="dow-item">SAT</div>
						</div>
						<div class="calendar-grid modern-calendar-grid">
							<?php
								$startWeekday = (int)date('N', strtotime($firstDay));
								for ($i=1;$i<$startWeekday;$i++) echo '<div class="cal-day empty-day"></div>';
								$daysInMonth = (int)date('t');
								for ($day=1;$day<=$daysInMonth;$day++):
									$events = $calEvents[$day] ?? [];
									$isToday = $day == (int)date('j');
							?>
							<div class="cal-day modern-cal-day <?= $isToday ? 'today' : '' ?>">
								<div class="cal-day-num"><?= $day ?></div>
								<?php if (!empty($events)): ?>
									<div class="cal-events">
										<?php foreach (array_slice($events, 0, 2) as $e): ?>
											<div class="cal-evt modern-cal-evt">
												<span class="event-title"><?= htmlspecialchars($e['title'] ?: 'Event') ?></span>
												<span class="event-organizer"><?= htmlspecialchars($e['organizer_name'] ?: 'Unknown') ?></span>
											</div>
										<?php endforeach; ?>
										<?php if (count($events) > 2): ?>
											<div class="cal-evt more-events">
												+<?= count($events) - 2 ?> more
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
							<?php endfor; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>

	<!-- Removed modal for Local Seniors as replaced by separate page -->

	<!-- Removed modal for National Seniors as replaced by separate page -->

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Enhanced Chart.js with modern styling
		let chartInstance = null;
		
		function initializeChart() {
			const ctx = document.getElementById('registrationsChart');
			const overlay = document.getElementById('chartOverlay');
			
			if (!ctx) return;
			
			// Show loading overlay
			if (overlay) {
				overlay.style.display = 'flex';
			}
			
			// Simulate loading delay for better UX
			setTimeout(() => {
				chartInstance = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
						datasets: [{
							label: 'Registrations',
							data: <?= json_encode($monthlyCounts) ?>,
							backgroundColor: 'rgba(30, 58, 138, 0.8)',
							borderColor: 'rgba(30, 58, 138, 1)',
							borderWidth: 2,
							borderRadius: 6,
							borderSkipped: false,
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: { 
							legend: { display: false },
							tooltip: {
								backgroundColor: 'rgba(0, 0, 0, 0.8)',
								titleColor: '#fff',
								bodyColor: '#fff',
								borderColor: 'rgba(30, 58, 138, 1)',
								borderWidth: 1,
								cornerRadius: 8,
								displayColors: false,
								callbacks: {
									title: function(context) {
										return context[0].label + ' ' + new Date().getFullYear();
									},
									label: function(context) {
										return context.parsed.y + ' registrations';
									}
								}
							}
						},
						scales: { 
							y: { 
								beginAtZero: true, 
								ticks: { 
									precision: 0,
									color: '#6b7280',
									font: {
										size: 12,
										weight: '500'
									}
								},
								grid: {
									color: 'rgba(107, 114, 128, 0.1)',
									drawBorder: false
								}
							},
							x: {
								ticks: {
									color: '#6b7280',
									font: {
										size: 12,
										weight: '500'
									}
								},
								grid: {
									display: false
								}
							}
						},
						animation: {
							duration: 1000,
							easing: 'easeInOutQuart'
						}
					}
				});
				
				// Hide loading overlay
				if (overlay) {
					overlay.style.display = 'none';
				}
			}, 800);
		}
		
		// Chart refresh function
		function refreshChart() {
			if (chartInstance) {
				chartInstance.update('active');
			}
		}
		
		// Chart export function
		function exportChart() {
			if (chartInstance) {
				const link = document.createElement('a');
				link.download = 'senior-registrations-chart.png';
				link.href = chartInstance.toBase64Image();
				link.click();
			}
		}
		
		// Calendar navigation
		function changeMonth(direction) {
			// This would typically make an AJAX call to update the calendar
			console.log('Change month:', direction);
			// For now, just show a message
			alert('Calendar navigation will be implemented in the next update');
		}
		
		// Initialize everything when DOM is loaded
		document.addEventListener('DOMContentLoaded', function() {
			initializeChart();
			
			// Add hover effects to stat cards
			document.querySelectorAll('.modern-stat-card').forEach(card => {
				card.addEventListener('mouseenter', function() {
					this.style.transform = 'translateY(-4px)';
					this.style.boxShadow = '0 12px 24px rgba(0, 0, 0, 0.15)';
				});
				
				card.addEventListener('mouseleave', function() {
					this.style.transform = 'translateY(0)';
					this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
				});
			});
			
			// Add click effects to event items
			document.querySelectorAll('.modern-event-item').forEach(item => {
				item.addEventListener('click', function(e) {
					e.preventDefault();
					// Add ripple effect
					const ripple = document.createElement('div');
					ripple.className = 'ripple-effect';
					ripple.style.cssText = `
						position: absolute;
						border-radius: 50%;
						background: rgba(30, 58, 138, 0.3);
						transform: scale(0);
						animation: ripple 0.6s linear;
						pointer-events: none;
					`;
					
					const rect = this.getBoundingClientRect();
					const size = Math.max(rect.width, rect.height);
					ripple.style.width = ripple.style.height = size + 'px';
					ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
					ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
					
					this.style.position = 'relative';
					this.appendChild(ripple);
					
					setTimeout(() => ripple.remove(), 600);
				});
			});
		});
		
		// Add CSS for ripple effect
		const style = document.createElement('style');
		style.textContent = `
			@keyframes ripple {
				to {
					transform: scale(4);
					opacity: 0;
				}
			}
		`;
		document.head.appendChild(style);
	</script>
</body>
</html>


