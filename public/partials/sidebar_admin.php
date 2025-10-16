<?php 
$user = current_user(); 
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
	<div class="sidebar-header">
		<a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-brand">
			<div class="sidebar-logo">
				<img src="<?= BASE_URL ?>/images/dilg.png" alt="DILG Logo" style="height: 40px; width: auto; border-radius: 6px;" onerror="this.onerror=null;this.src='<?= BASE_URL ?>/images/logo.png';">
			</div>
		</a>
		<button type="button" id="header-burger" class="sidebar-toggle" aria-label="Toggle menu"></button>
	</div>
	
	<nav class="sidebar-nav">
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="3" width="7" height="7" rx="2"></rect><rect x="3" y="14" width="7" height="7" rx="2"></rect><rect x="14" y="14" width="7" height="7" rx="2"></rect></svg></span>
				<span>Dashboard</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/events.php" class="nav-link <?= $current_page === 'events.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4"></path><path d="M3 11h18"></path></svg></span>
				<span>Events Management</span>
			</a>
		</div>
		
		<?php 
        $seniors_pages = [
            'seniors.php',
            'event_ranking.php',
            'deceased_seniors.php',
            'transferred_seniors.php',
            'waiting_seniors.php'
        ];
		$is_seniors_active = in_array($current_page, $seniors_pages);
		?>
		<div class="nav-item has-submenu">
			<a href="<?= BASE_URL ?>/admin/seniors.php" class="nav-link <?= $is_seniors_active ? 'active' : '' ?>" id="seniors-link">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
				<span>All Seniors</span>
				<span class="nav-chevron" id="seniors-toggle"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg></span>
			</a>
			<div class="submenu" id="seniors-submenu">
                <div class="submenu-heading">All Seniors</div>
				<a href="<?= BASE_URL ?>/admin/seniors.php" class="submenu-link <?= $current_page === 'seniors.php' ? 'active' : '' ?>">
					<span>All Seniors</span>
				</a>
                <a href="<?= BASE_URL ?>/admin/event_ranking.php" class="submenu-link <?= $current_page === 'event_ranking.php' ? 'active' : '' ?>">
                    <span>Event Participation Ranking</span>
                </a>
                <!-- Inactive Seniors link removed per request -->
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
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8" cy="12" r="2"></circle><path d="M12 12h6M12 9h6M12 15h6"></path></svg></span>
				<span>Generate ID</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/benefits.php" class="nav-link <?= $current_page === 'benefits.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"></path><path d="M4 7h16v5H4z"></path><path d="M12 22V7"></path><path d="M12 7s-1.5-4-4-4-3 2-3 3 1 3 3 3h4"></path><path d="M12 7s1.5-4 4-4 3 2 3 3-1 3-3 3h-4"></path></svg></span>
				<span>Benefits</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/barangays.php" class="nav-link <?= $current_page === 'barangays.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12l9-9 9 9"></path><path d="M9 21V9h6v12"></path><path d="M21 10v11h-6"></path><path d="M3 10v11h6"></path></svg></span>
				<span>Barangays</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/users.php" class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7" r="4"></circle><path d="M6 21v-2a6 6 0 0 1 12 0v2"></path></svg></span>
				<span>User Management</span>
			</a>
		</div>
		
		<div class="nav-item">
			<a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
				<span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"></path><path d="M7 13l3 3 7-7"></path></svg></span>
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
