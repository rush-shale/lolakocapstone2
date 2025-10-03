<?php 
$user = current_user(); 
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
	<div class="sidebar-header">
		<a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-brand">
			<div class="sidebar-logo">
				<img src="<?= BASE_URL ?>/images/logo.png" alt="SeniorCare Admin Logo" style="height: 40px; width: auto;">
			</div>
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
		
		<?php 
		$seniors_pages = [
			'seniors.php',
			'active_seniors.php',
			'inactive_seniors.php',
			'deceased_seniors.php',
			'transferred_seniors.php',
			'waiting_seniors.php'
		];
		$is_seniors_active = in_array($current_page, $seniors_pages);
		?>
		<div class="nav-item has-submenu <?= $is_seniors_active ? 'expanded' : '' ?>">
			<a href="#" class="nav-link seniors-toggle <?= $is_seniors_active ? 'active' : '' ?>" id="seniors-toggle">
				<span class="nav-icon">👥</span>
				<span>All Seniors</span>
				<span class="nav-chevron">▶</span>
			</a>
			<div class="submenu" id="seniors-submenu">
                <div class="submenu-heading">All Seniors</div>
				<a href="<?= BASE_URL ?>/admin/seniors.php" class="submenu-link <?= $current_page === 'seniors.php' ? 'active' : '' ?>">
					<span>All Seniors</span>
				</a>
				<a href="<?= BASE_URL ?>/admin/active_seniors.php" class="submenu-link <?= $current_page === 'active_seniors.php' ? 'active' : '' ?>">
					<span>Active Seniors</span>
				</a>
				<a href="<?= BASE_URL ?>/admin/inactive_seniors.php" class="submenu-link <?= $current_page === 'inactive_seniors.php' ? 'active' : '' ?>">
					<span>Inactive Seniors</span>
				</a>
				<a href="<?= BASE_URL ?>/admin/deceased_seniors.php" class="submenu-link <?= $current_page === 'deceased_seniors.php' ? 'active' : '' ?>">
					<span>Deceased Seniors</span>
				</a>
				<a href="<?= BASE_URL ?>/admin/transferred_seniors.php" class="submenu-link <?= $current_page === 'transferred_seniors.php' ? 'active' : '' ?>">
					<span>Transferred Seniors</span>
				</a>
				<a href="<?= BASE_URL ?>/admin/waiting_seniors.php" class="submenu-link <?= $current_page === 'waiting_seniors.php' ? 'active' : '' ?>">
					<span>Waiting Seniors</span>
				</a>
			</div>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/senior_id.php" class="nav-link <?= $current_page === 'senior_id.php' ? 'active' : '' ?>">
				<span class="nav-icon">🆔</span>
				<span>Generate ID</span>
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
<script src="<?= BASE_URL ?>/assets/sidebar-toggle.js"></script>
