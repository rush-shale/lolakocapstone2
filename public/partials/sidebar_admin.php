<?php $user = current_user(); ?>
<aside class="sidebar">
	<div class="brand">LoLaKo</div>
	<nav>
		<a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
		<a href="<?= BASE_URL ?>/admin/barangays.php">Barangays</a>
		<a href="<?= BASE_URL ?>/admin/users.php">Users</a>
		<a href="<?= BASE_URL ?>/admin/seniors.php">Seniors</a>
		<a href="<?= BASE_URL ?>/admin/events.php">Events</a>
		<a href="<?= BASE_URL ?>/admin/reports.php">Reports</a>
	</nav>
	<div class="user">
		<span><?= htmlspecialchars($user['name']) ?></span>
		<a class="logout" href="<?= BASE_URL ?>/logout.php">Logout</a>
	</div>
</aside>


