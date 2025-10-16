<?php 
$user = current_user(); 
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
	<div class="sidebar-header">
		<a href="<?= BASE_URL ?>/user/dashboard.php" class="sidebar-brand">
			<div class="sidebar-logo">🏛️</div>
		</a>
		<button type="button" id="header-burger" class="sidebar-toggle" aria-label="Toggle menu"></button>
	</div>
	
	<nav class="sidebar-nav">
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="3" width="7" height="7" rx="2"></rect><rect x="3" y="14" width="7" height="7" rx="2"></rect><rect x="14" y="14" width="7" height="7" rx="2"></rect></svg></span>
				<span>Dashboard</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/events.php" class="nav-link <?= $current_page === 'events.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4"></path><path d="M3 11h18"></path></svg></span>
				<span>My Events</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/osca_events.php" class="nav-link <?= $current_page === 'osca_events.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10l9-7 9 7"></path><path d="M5 10v10h14V10"></path></svg></span>
				<span>OSCA Events</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/attendance.php" class="nav-link <?= $current_page === 'attendance.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg></span>
				<span>Attendance</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/seniors.php" class="nav-link <?= $current_page === 'seniors.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
				<span>Senior Citizens</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/reports.php" class="nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"></path><path d="M7 13l3 3 7-7"></path></svg></span>
				<span>Reports</span>
			</a>
		</div>
	</nav>
	
	<div class="user">
		<div class="user-info">
			<div class="user-avatar">👨‍💻</div>
			<div class="user-details">
				<span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
				<span class="user-role">Staff User</span>
			</div>
		</div>
		<div class="user-actions">
			<a class="logout" href="<?= BASE_URL ?>/logout.php">
				<span>🚪</span>
				<span>Logout</span>
			</a>
		</div>
	</div>
</aside>

<script src="<?= BASE_URL ?>/assets/sidebar-toggle.js"></script>


