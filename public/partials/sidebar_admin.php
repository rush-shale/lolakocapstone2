<?php 
$user = current_user(); 
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
	<div class="sidebar-header">
		<a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-brand">
			<div class="sidebar-logo">🏛️</div>
			<div class="sidebar-title">SeniorCare Admin</div>
		</a>
	</div>
	
	<nav class="sidebar-nav">
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
				<span class="nav-icon">📊</span>
				<span>Dashboard</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/events.php" class="nav-link <?= $current_page === 'events.php' ? 'active' : '' ?>">
				<span class="nav-icon">📅</span>
				<span>Events Management</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/seniors.php" class="nav-link <?= $current_page === 'seniors.php' ? 'active' : '' ?>">
				<span class="nav-icon">👥</span>
				<span>Senior Citizens</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="#" class="nav-link" onclick="openAddModal()">
				<span class="nav-icon">📝</span>
				<span>Registration</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/benefits.php" class="nav-link <?= $current_page === 'benefits.php' ? 'active' : '' ?>">
				<span class="nav-icon">🎁</span>
				<span>Benefits</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/barangays.php" class="nav-link <?= $current_page === 'barangays.php' ? 'active' : '' ?>">
				<span class="nav-icon">🏘️</span>
				<span>Barangays</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/users.php" class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
				<span class="nav-icon">👤</span>
				<span>User Management</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
				<span class="nav-icon">📈</span>
				<span>Reports & Analytics</span>
			</a>
		</div>
	</nav>
	
	<div class="user">
		<div class="user-info">
			<div class="user-avatar">👨‍💼</div>
			<div class="user-details">
				<span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
				<span class="user-role">System Administrator</span>
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
