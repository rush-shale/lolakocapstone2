<?php 
$user = current_user(); 
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
	<div class="sidebar-header">
		<a href="<?= BASE_URL ?>/user/dashboard.php" class="sidebar-brand">
			<div class="sidebar-logo">🏛️</div>
			<div class="sidebar-title">SeniorCare Staff</div>
		</a>
	</div>
	
	<nav class="sidebar-nav">
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
				<span class="nav-icon">📊</span>
				<span>Dashboard</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/events.php" class="nav-link <?= $current_page === 'events.php' ? 'active' : '' ?>">
				<span class="nav-icon">📅</span>
				<span>My Events</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/osca_events.php" class="nav-link <?= $current_page === 'osca_events.php' ? 'active' : '' ?>">
				<span class="nav-icon">🏛️</span>
				<span>OSCA Events</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/attendance.php" class="nav-link <?= $current_page === 'attendance.php' ? 'active' : '' ?>">
				<span class="nav-icon">✅</span>
				<span>Attendance</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/barangays.php" class="nav-link <?= $current_page === 'barangays.php' ? 'active' : '' ?>">
				<span class="nav-icon">🏘️</span>
				<span>Barangays</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/seniors.php" class="nav-link <?= $current_page === 'seniors.php' ? 'active' : '' ?>">
				<span class="nav-icon">👥</span>
				<span>Senior Citizens</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/user/reports.php" class="nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
				<span class="nav-icon">📈</span>
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


