<?php $user = current_user(); ?>
<aside class="sidebar">
	<div class="brand">
		<span style="font-size: 1.5rem; margin-right: 0.5rem;">🏛️</span>
		LoLaKo
	</div>
	<nav>
		<a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item">
			<span class="nav-icon">📊</span>
			<span class="nav-text">Dashboard</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/seniors.php" class="nav-item">
			<span class="nav-icon">👥</span>
			<span class="nav-text">All Seniors</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/deceased.php" class="nav-item">
			<span class="nav-icon">💀</span>
			<span class="nav-text">Deceased Seniors</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/benefits.php" class="nav-item">
			<span class="nav-icon">🎁</span>
			<span class="nav-text">Benefits Management</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/events.php" class="nav-item">
			<span class="nav-icon">📅</span>
			<span class="nav-text">Events</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/senior_id.php" class="nav-item">
			<span class="nav-icon">🆔</span>
			<span class="nav-text">Generate ID</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/barangays.php" class="nav-item">
			<span class="nav-icon">🏘️</span>
			<span class="nav-text">Barangays</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/users.php" class="nav-item">
			<span class="nav-icon">👤</span>
			<span class="nav-text">Users</span>
		</a>
		<a href="<?= BASE_URL ?>/admin/reports.php" class="nav-item">
			<span class="nav-icon">📈</span>
			<span class="nav-text">Reports</span>
		</a>
	</nav>
	<div class="user">
		<div class="user-info">
			<div class="user-avatar">👨‍💼</div>
			<div class="user-details">
				<span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
				<span class="user-role">Administrator</span>
			</div>
		</div>
		<div class="user-actions">
			<button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
				<span class="theme-icon">🌙</span>
				<span class="theme-text">Dark Mode</span>
			</button>
			<a class="logout" href="<?= BASE_URL ?>/logout.php">
				<span>🚪</span>
				<span>Logout</span>
			</a>
		</div>
	</div>
</aside>


