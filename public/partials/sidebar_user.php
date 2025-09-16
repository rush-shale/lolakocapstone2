<?php $user = current_user(); ?>
<aside class="sidebar">
	<div class="brand">LoLaKo</div>
	<nav>
		<a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a>
		<a href="<?= BASE_URL ?>/user/events.php">My Events</a>
		<a href="<?= BASE_URL ?>/user/attendance.php">Attendance</a>
		<a href="<?= BASE_URL ?>/user/barangays.php">Barangays</a>
		<a href="<?= BASE_URL ?>/user/seniors.php">Seniors</a>
		<a href="<?= BASE_URL ?>/user/reports.php">Reports</a>
	</nav>
	<div class="user">
		<span><?= htmlspecialchars($user['name']) ?></span>
		<a class="logout" href="<?= BASE_URL ?>/logout.php">Logout</a>
	</div>
</aside>


