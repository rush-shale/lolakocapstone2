<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$op = $_POST['op'] ?? '';
		if ($op === 'create') {
			$name = trim($_POST['name'] ?? '');
			if ($name) {
				$stmt = $pdo->prepare('INSERT INTO barangays (name) VALUES (?)');
				try { $stmt->execute([$name]); $message = 'Barangay added'; } catch (Throwable $e) { $message = 'Error: duplicate or invalid'; }
			}
		}
		if ($op === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				$stmt = $pdo->prepare('DELETE FROM barangays WHERE id=?');
				$stmt->execute([$id]);
				$message = 'Barangay deleted';
			}
		}
	}
}

$csrf = generate_csrf_token();
$barangays = $pdo->query('SELECT * FROM barangays ORDER BY name ASC')->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Barangays | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<h1>Barangays</h1>
		<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
		<div class="grid">
			<section>
				<h2>Add Barangay</h2>
				<form method="post">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="create">
					<label>Name</label>
					<input name="name" required>
					<button type="submit">Add</button>
				</form>
			</section>
			<section>
				<h2>All Barangays</h2>
				<ul>
					<?php foreach ($barangays as $b): ?>
						<li>
							<?= htmlspecialchars($b['name']) ?>
							<form method="post" style="display:inline" onsubmit="return confirm('Delete this barangay?')">
								<input type="hidden" name="csrf" value="<?= $csrf ?>">
								<input type="hidden" name="op" value="delete">
								<input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
								<button type="submit">Delete</button>
							</form>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		</div>
	</main>
</body>
</html>


