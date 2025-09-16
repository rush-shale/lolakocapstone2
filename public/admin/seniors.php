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
		if ($op === 'create' || $op === 'update') {
			$id = (int)($_POST['id'] ?? 0);
			$first_name = trim($_POST['first_name'] ?? '');
			$middle_name = trim($_POST['middle_name'] ?? '');
			$last_name = trim($_POST['last_name'] ?? '');
			$age = (int)($_POST['age'] ?? 0);
			$barangay = trim($_POST['barangay'] ?? '');
			$contact = trim($_POST['contact'] ?? '');
			$benefits_received = isset($_POST['benefits_received']) ? 1 : 0;
			$life_status = $_POST['life_status'] === 'deceased' ? 'deceased' : 'living';
			$category = $_POST['category'] === 'national' ? 'national' : 'local';
			if ($first_name && $last_name && $age && $barangay) {
				if ($op === 'create') {
					$stmt = $pdo->prepare('INSERT INTO seniors (first_name,middle_name,last_name,age,barangay,contact,benefits_received,life_status,category) VALUES (?,?,?,?,?,?,?,?,?)');
					$stmt->execute([$first_name,$middle_name ?: null,$last_name,$age,$barangay,$contact ?: null,$benefits_received,$life_status,$category]);
					$message = 'Senior added';
				} else {
					$stmt = $pdo->prepare('UPDATE seniors SET first_name=?, middle_name=?, last_name=?, age=?, barangay=?, contact=?, benefits_received=?, life_status=?, category=? WHERE id=?');
					$stmt->execute([$first_name,$middle_name ?: null,$last_name,$age,$barangay,$contact ?: null,$benefits_received,$life_status,$category,$id]);
					$message = 'Senior updated';
				}
			}
		}
		if ($op === 'toggle_benefits') {
			$id = (int)($_POST['id'] ?? 0);
			$to = isset($_POST['to']) && (int)$_POST['to'] === 1 ? 1 : 0;
			if ($id) {
				$stmt = $pdo->prepare('UPDATE seniors SET benefits_received=? WHERE id=?');
				$stmt->execute([$to, $id]);
				$message = 'Benefits status updated';
			}
		}
		if ($op === 'toggle_life') {
			$id = (int)($_POST['id'] ?? 0);
			$to = $_POST['to'] === 'deceased' ? 'deceased' : 'living';
			if ($id) {
				$stmt = $pdo->prepare('UPDATE seniors SET life_status=? WHERE id=?');
				$stmt->execute([$to, $id]);
				$message = 'Life status updated';
			}
		}
		if ($op === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				$stmt = $pdo->prepare('DELETE FROM seniors WHERE id=?');
				$stmt->execute([$id]);
				$message = 'Senior deleted';
			}
		}
		if ($op === 'transfer') {
			$id = (int)($_POST['id'] ?? 0);
			$to = $_POST['to'] === 'national' ? 'national' : 'local';
			if ($id) {
				$stmt = $pdo->prepare("UPDATE seniors SET category=? WHERE id=?");
				$stmt->execute([$to,$id]);
				$message = 'Transfer updated';
			}
		}
	}
}

$csrf = generate_csrf_token();

// Filters
$life = $_GET['life'] ?? 'all'; // all|living|deceased
$benefits = $_GET['benefits'] ?? 'all'; // all|received|notyet
$category = $_GET['category'] ?? 'all'; // all|local|national

$where = [];
$params = [];
if ($life === 'living' || $life === 'deceased') { $where[] = 'life_status = ?'; $params[] = $life; }
if ($benefits === 'received') { $where[] = 'benefits_received = 1'; }
if ($benefits === 'notyet') { $where[] = 'benefits_received = 0'; }
if ($category === 'local' || $category === 'national') { $where[] = 'category = ?'; $params[] = $category; }

$sql = 'SELECT * FROM seniors';
if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY created_at DESC';
$stmtAll = $pdo->prepare($sql);
$stmtAll->execute($params);
$seniors = $stmtAll->fetchAll();

$livingCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living'")->fetchColumn();
$deceasedCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased'")->fetchColumn();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Seniors | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<h1>Seniors</h1>
		<?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
		<div class="stats">
			<div class="stat">Living <strong><?= $livingCount ?></strong></div>
			<div class="stat">Deceased <strong><?= $deceasedCount ?></strong></div>
		</div>
		<p>
			<a href="seniors.php?category=local" style="margin-right:.5rem">View Local</a>
			<a href="seniors.php?category=national">View National</a>
		</p>
		<section style="margin: 1rem 0; background: transparent; padding: 0;">
			<form method="get" style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:end;">
				<div>
					<label>Life</label>
					<select name="life">
						<option value="all" <?= $life==='all'?'selected':'' ?>>All</option>
						<option value="living" <?= $life==='living'?'selected':'' ?>>Living</option>
						<option value="deceased" <?= $life==='deceased'?'selected':'' ?>>Deceased</option>
					</select>
				</div>
				<div>
					<label>Benefits</label>
					<select name="benefits">
						<option value="all" <?= $benefits==='all'?'selected':'' ?>>All</option>
						<option value="received" <?= $benefits==='received'?'selected':'' ?>>Received</option>
						<option value="notyet" <?= $benefits==='notyet'?'selected':'' ?>>Not Yet</option>
					</select>
				</div>
				<div>
					<label>Category</label>
					<select name="category">
						<option value="all" <?= $category==='all'?'selected':'' ?>>All</option>
						<option value="local" <?= $category==='local'?'selected':'' ?>>Local</option>
						<option value="national" <?= $category==='national'?'selected':'' ?>>National</option>
					</select>
				</div>
				<button type="submit">Apply Filters</button>
				<a href="seniors.php" style="text-decoration:none"><button type="button">Reset</button></a>
			</form>
		</section>
		<div class="grid">
			<section>
				<h2>Add / Edit Senior</h2>
				<form method="post">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="create">
					<label>First Name</label>
					<input name="first_name" required>
					<label>Middle Name</label>
					<input name="middle_name">
					<label>Last Name</label>
					<input name="last_name" required>
					<label>Age</label>
					<input type="number" name="age" min="1" required>
					<label>Barangay</label>
					<select name="barangay" required>
						<?php $bgs = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll(); foreach ($bgs as $bg): ?>
							<option value="<?= htmlspecialchars($bg['name']) ?>"><?= htmlspecialchars($bg['name']) ?></option>
						<?php endforeach; ?>
					</select>
					<label>Contact</label>
					<input name="contact">
					<label><input type="checkbox" name="benefits_received"> Benefits Received</label>
					<label>Life Status</label>
					<select name="life_status">
						<option value="living">Living</option>
						<option value="deceased">Deceased</option>
					</select>
					<label>Category</label>
					<select name="category">
						<option value="local">Local</option>
						<option value="national">National</option>
					</select>
					<button type="submit">Save</button>
				</form>
			</section>
			<section>
				<h2>All Seniors</h2>
				<table style="width:100%">
					<tr><th>Name</th><th>Age</th><th>Barangay</th><th>Life</th><th>Category</th><th>Benefits</th><th></th></tr>
					<?php foreach ($seniors as $s): ?>
						<tr>
							<td><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></td>
							<td><?= (int)$s['age'] ?></td>
							<td><?= htmlspecialchars($s['barangay']) ?></td>
							<td><?= htmlspecialchars($s['life_status']) ?></td>
							<td><?= htmlspecialchars($s['category']) ?></td>
							<td><?= $s['benefits_received'] ? 'Received' : 'Not Yet' ?></td>
							<td>
								<form method="post" style="display:inline">
									<input type="hidden" name="csrf" value="<?= $csrf ?>">
									<input type="hidden" name="op" value="toggle_benefits">
									<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
									<input type="hidden" name="to" value="<?= $s['benefits_received']?0:1 ?>">
									<button type="submit"><?= $s['benefits_received']?'Mark Not Yet':'Mark Received' ?></button>
								</form>
								<form method="post" style="display:inline">
									<input type="hidden" name="csrf" value="<?= $csrf ?>">
									<input type="hidden" name="op" value="toggle_life">
									<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
									<input type="hidden" name="to" value="<?= $s['life_status']==='living'?'deceased':'living' ?>">
									<button type="submit">Mark <?= $s['life_status']==='living'?'Deceased':'Living' ?></button>
								</form>
								<form method="post" style="display:inline">
									<input type="hidden" name="csrf" value="<?= $csrf ?>">
									<input type="hidden" name="op" value="transfer">
									<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
									<input type="hidden" name="to" value="<?= $s['category']==='local'?'national':'local' ?>">
									<button type="submit">Move to <?= $s['category']==='local'?'National':'Local' ?></button>
								</form>
								<form method="post" style="display:inline" onsubmit="return confirm('Delete this senior?')">
									<input type="hidden" name="csrf" value="<?= $csrf ?>">
									<input type="hidden" name="op" value="delete">
									<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
									<button type="submit">Delete</button>
								</form>
								<a href="<?= BASE_URL ?>/admin/senior_id.php?id=<?= (int)$s['id'] ?>" target="_blank" style="margin-left:.25rem; display:inline-block; text-decoration:none"><button type="button">Print ID</button></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</section>
		</div>
	</main>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


