<?php $user = current_user(); ?>
<aside class="sidebar">
	<div class="brand">
		<span style="font-size: 1.5rem; margin-right: 0.5rem;">🏛️</span>
		LoLaKo
	</div>
	<nav>
		<a href="<?= BASE_URL ?>/user/dashboard.php" class="nav-item">
			<span class="nav-icon">📊</span>
			<span class="nav-text">Dashboard</span>
		</a>
		<a href="<?= BASE_URL ?>/user/events.php" class="nav-item">
			<span class="nav-icon">📅</span>
			<span class="nav-text">My Events</span>
		</a>
		<a href="<?= BASE_URL ?>/user/osca_events.php" class="nav-item">
			<span class="nav-icon">🏛️</span>
			<span class="nav-text">OSCA Events</span>
		</a>
		<a href="<?= BASE_URL ?>/user/attendance.php" class="nav-item">
			<span class="nav-icon">✅</span>
			<span class="nav-text">Attendance</span>
		</a>
		<a href="<?= BASE_URL ?>/user/barangays.php" class="nav-item">
			<span class="nav-icon">🏘️</span>
			<span class="nav-text">Barangays</span>
		</a>
		<a href="<?= BASE_URL ?>/user/seniors.php" class="nav-item">
			<span class="nav-icon">👥</span>
			<span class="nav-text">Seniors</span>
		</a>
		<a href="<?= BASE_URL ?>/user/reports.php" class="nav-item">
			<span class="nav-icon">📈</span>
			<span class="nav-text">Reports</span>
		</a>
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


