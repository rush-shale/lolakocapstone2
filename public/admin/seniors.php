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
		$date_of_birth = $_POST['date_of_birth'] ?? null;
		$sex = $_POST['sex'] ?? null;
		$place_of_birth = trim($_POST['place_of_birth'] ?? '');
		$civil_status = $_POST['civil_status'] ?? null;
		$educational_attainment = $_POST['educational_attainment'] ?? null;
		$occupation = trim($_POST['occupation'] ?? '');
		$annual_income = $_POST['annual_income'] ? (float)$_POST['annual_income'] : null;
		$other_skills = trim($_POST['other_skills'] ?? '');
		$barangay = trim($_POST['barangay'] ?? '');
		$contact = trim($_POST['contact'] ?? '');
		$benefits_received = isset($_POST['benefits_received']) ? 1 : 0;
		$life_status = $_POST['life_status'] === 'deceased' ? 'deceased' : 'living';
		$category = $_POST['category'] === 'national' ? 'national' : 'local';
		
		if ($first_name && $last_name && $age && $barangay) {
			try {
				$pdo->beginTransaction();
				
				if ($op === 'create') {
					$stmt = $pdo->prepare('INSERT INTO seniors (first_name, middle_name, last_name, age, date_of_birth, sex, place_of_birth, civil_status, educational_attainment, occupation, annual_income, other_skills, barangay, contact, benefits_received, life_status, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $age, 
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: null, $educational_attainment ?: null,
						$occupation ?: null, $annual_income, $other_skills ?: null,
						$barangay, $contact ?: null, $benefits_received, $life_status, $category
					]);
					$senior_id = $pdo->lastInsertId();
					$message = 'Senior added successfully';
				} else {
					$stmt = $pdo->prepare('UPDATE seniors SET first_name=?, middle_name=?, last_name=?, age=?, date_of_birth=?, sex=?, place_of_birth=?, civil_status=?, educational_attainment=?, occupation=?, annual_income=?, other_skills=?, barangay=?, contact=?, benefits_received=?, life_status=?, category=? WHERE id=?');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $age,
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: null, $educational_attainment ?: null,
						$occupation ?: null, $annual_income, $other_skills ?: null,
						$barangay, $contact ?: null, $benefits_received, $life_status, $category, $id
					]);
					$senior_id = $id;
					$message = 'Senior updated successfully';
				}
				
				// Handle family composition
				if ($op === 'update') {
					// Delete existing family members
					$stmt = $pdo->prepare('DELETE FROM family_composition WHERE senior_id = ?');
					$stmt->execute([$senior_id]);
				}
				
				if (isset($_POST['family_name']) && is_array($_POST['family_name'])) {
					for ($i = 0; $i < count($_POST['family_name']); $i++) {
						$family_name = trim($_POST['family_name'][$i] ?? '');
						$family_birthday = $_POST['family_birthday'][$i] ?? null;
						$family_age = (int)($_POST['family_age'][$i] ?? 0);
						$family_relation = trim($_POST['family_relation'][$i] ?? '');
						$family_civil_status = trim($_POST['family_civil_status'][$i] ?? '');
						$family_occupation = trim($_POST['family_occupation'][$i] ?? '');
						$family_income = $_POST['family_income'][$i] ? (float)$_POST['family_income'][$i] : null;
						
						if ($family_name && $family_relation) {
							$stmt = $pdo->prepare('INSERT INTO family_composition (senior_id, name, birthday, age, relation, civil_status, occupation, income) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
							$stmt->execute([
								$senior_id, $family_name, $family_birthday ?: null, 
								$family_age ?: null, $family_relation, $family_civil_status ?: null,
								$family_occupation ?: null, $family_income
							]);
						}
					}
				}
				
				// Handle association information
				if ($op === 'update') {
					// Delete existing association info
					$stmt = $pdo->prepare('DELETE FROM association_info WHERE senior_id = ?');
					$stmt->execute([$senior_id]);
				}
				
				$association_name = trim($_POST['association_name'] ?? '');
				$association_address = trim($_POST['association_address'] ?? '');
				$membership_date = $_POST['membership_date'] ?? null;
				$is_officer = isset($_POST['is_officer']) ? 1 : 0;
				$position = trim($_POST['position'] ?? '');
				$date_elected = $_POST['date_elected'] ?? null;
				
				if ($association_name || $association_address || $membership_date || $is_officer) {
					$stmt = $pdo->prepare('INSERT INTO association_info (senior_id, association_name, association_address, membership_date, is_officer, position, date_elected) VALUES (?, ?, ?, ?, ?, ?, ?)');
					$stmt->execute([
						$senior_id, $association_name ?: null, $association_address ?: null,
						$membership_date ?: null, $is_officer, $position ?: null, $date_elected ?: null
					]);
				}
				
				$pdo->commit();
			} catch (Exception $e) {
				$pdo->rollback();
				$message = 'Error: ' . $e->getMessage();
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
				$message = 'Senior deleted successfully';
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
$barangays = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll();

// Get status from URL parameter
$status = $_GET['status'] ?? 'all';

// Map status to life_status for filtering
$life = 'all';
if ($status === 'active') {
    $life = 'living';
} elseif ($status === 'deceased') {
    $life = 'deceased';
}

// Additional filters
$benefits = $_GET['benefits'] ?? 'all'; // all|received|notyet
$category = $_GET['category'] ?? 'all'; // all|local|national

$where = [];
$params = [];

// Handle different status views
if ($status === 'active') {
    // Active seniors: those who have attended events
    $sql = 'SELECT DISTINCT s.*, COUNT(a.id) as event_count, GROUP_CONCAT(e.title SEPARATOR ", ") as events_attended
            FROM seniors s
            LEFT JOIN attendance a ON s.id = a.senior_id
            LEFT JOIN events e ON a.event_id = e.id
            WHERE s.life_status = "living"
            GROUP BY s.id
            HAVING event_count > 0
            ORDER BY event_count DESC, s.created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute();
    $seniors = $stmtAll->fetchAll();
} elseif ($status === 'inactive') {
    // Inactive seniors: those who have not attended any events
    $sql = 'SELECT s.*, 0 as event_count, "" as events_attended
            FROM seniors s
            LEFT JOIN attendance a ON s.id = a.senior_id
            WHERE s.life_status = "living" AND a.id IS NULL
            ORDER BY s.created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute();
    $seniors = $stmtAll->fetchAll();
} elseif ($status === 'transferred') {
    // Transferred seniors: those who have moved to another barangay
    $sql = 'SELECT s.*, 0 as event_count, "" as events_attended
            FROM seniors s
            WHERE s.life_status = "living" AND s.category = "national"
            ORDER BY s.created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute();
    $seniors = $stmtAll->fetchAll();
} else {
    // All seniors with regular filters
    if ($life === 'living' || $life === 'deceased') { $where[] = 'life_status = ?'; $params[] = $life; }
    if ($benefits === 'received') { $where[] = 'benefits_received = 1'; }
    if ($benefits === 'notyet') { $where[] = 'benefits_received = 0'; }
    if ($category === 'local' || $category === 'national') { $where[] = 'category = ?'; $params[] = $category; }

    $sql = 'SELECT *, 0 as event_count, "" as events_attended FROM seniors';
    if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute($params);
    $seniors = $stmtAll->fetchAll();
}

$livingCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living'")->fetchColumn();
$deceasedCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased'")->fetchColumn();

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>All Seniors | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		/* Clean, Professional Styles */
		.content-body {
			display: flex;
			gap: 1.5rem;
			align-items: flex-start;
		}
		
		.filter-sidebar {
			width: 250px;
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			position: sticky;
			top: 1rem;
		}
		
		.filter-header {
			padding: 1rem;
			border-bottom: 1px solid #e5e7eb;
			background: #f9fafb;
		}
		
		.filter-header h3 {
			margin: 0;
			font-size: 1rem;
			font-weight: 600;
			color: #374151;
		}
		
		.filter-options {
			padding: 0.5rem 0;
		}
		
		.filter-option {
			display: flex;
			align-items: center;
			padding: 0.75rem 1rem;
			text-decoration: none;
			color: #6b7280;
			border-left: 3px solid transparent;
		}
		
		.filter-option:hover {
			background: #f3f4f6;
			color: #374151;
		}
		
		.filter-option.active {
			background: #eff6ff;
			color: #2563eb;
			border-left-color: #2563eb;
			font-weight: 500;
		}
		
		.filter-option i {
			width: 16px;
			margin-right: 0.5rem;
			font-size: 0.875rem;
		}
		
		.filter-option span:first-of-type {
			flex: 1;
			font-size: 0.875rem;
		}
		
		.filter-option .count {
			background: #e5e7eb;
			color: #6b7280;
			padding: 0.125rem 0.375rem;
			border-radius: 12px;
			font-size: 0.75rem;
			font-weight: 500;
			min-width: 20px;
			text-align: center;
		}
		
		.filter-option.active .count {
			background: #2563eb;
			color: white;
		}
		
		.main-content-area {
			flex: 1;
			min-width: 0;
		}
		
		/* Simplify table styling */
		.table {
			background: white;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			overflow: hidden;
		}
		
		.table th {
			background: #f9fafb;
			color: #374151;
			font-weight: 600;
			font-size: 0.875rem;
			padding: 0.75rem 1rem;
			border-bottom: 1px solid #e5e7eb;
		}
		
		.table td {
			padding: 0.75rem 1rem;
			border-bottom: 1px solid #f3f4f6;
			font-size: 0.875rem;
		}
		
		.table tbody tr:hover {
			background: #f9fafb;
		}
		
		.table tbody tr:last-child td {
			border-bottom: none;
		}
		
		/* Simplify badges */
		.badge {
			padding: 0.25rem 0.5rem;
			border-radius: 4px;
			font-size: 0.75rem;
			font-weight: 500;
		}
		
		.badge-primary {
			background: #dbeafe;
			color: #1e40af;
		}
		
		.badge-success {
			background: #d1fae5;
			color: #065f46;
		}
		
		.badge-warning {
			background: #fef3c7;
			color: #92400e;
		}
		
		.badge-danger {
			background: #fee2e2;
			color: #991b1b;
		}
		
		/* Simplify buttons */
		.button {
			padding: 0.5rem 1rem;
			border-radius: 6px;
			font-size: 0.875rem;
			font-weight: 500;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			border: 1px solid transparent;
		}
		
		.button.primary {
			background: #2563eb;
			color: white;
		}
		
		.button.primary:hover {
			background: #1d4ed8;
		}
		
		.button.secondary {
			background: #f3f4f6;
			color: #374151;
			border-color: #d1d5db;
		}
		
		.button.secondary:hover {
			background: #e5e7eb;
		}
		
		.button.danger {
			background: #dc2626;
			color: white;
		}
		
		.button.danger:hover {
			background: #b91c1c;
		}
		
		.button.small {
			padding: 0.375rem 0.75rem;
			font-size: 0.8125rem;
		}
		
		/* Remove excessive animations */
		.animate-fade-in {
			animation: none;
		}
		
		/* Action buttons styling */
		.action-buttons {
			display: flex;
			gap: 0.25rem;
			flex-wrap: wrap;
		}
		
		.action-buttons .button {
			padding: 0.25rem 0.5rem;
			font-size: 0.75rem;
			min-width: auto;
		}
		
		/* Senior info styling */
		.senior-info {
			line-height: 1.4;
		}
		
		.senior-info small {
			color: #6b7280;
			font-size: 0.75rem;
		}
		
		/* Card styling */
		.card {
			background: white;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			overflow: hidden;
		}
		
		.card-header {
			background: #f9fafb;
			padding: 1rem 1.5rem;
			border-bottom: 1px solid #e5e7eb;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.card-header h2 {
			margin: 0;
			font-size: 1.125rem;
			font-weight: 600;
			color: #374151;
		}
		
		.card-body {
			padding: 0;
		}
		
		/* Search container */
		.search-container {
			display: flex;
			align-items: center;
			background: white;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			padding: 0.5rem 0.75rem;
			min-width: 250px;
		}
		
		.search-container input {
			border: none;
			outline: none;
			background: transparent;
			flex: 1;
			font-size: 0.875rem;
		}
		
		.search-icon {
			color: #6b7280;
			margin-right: 0.5rem;
		}
		
		/* Responsive design */
		@media (max-width: 1024px) {
			.content-body {
				flex-direction: column;
			}
			
			.filter-sidebar {
				width: 100%;
				position: static;
			}
			
			.filter-options {
				display: flex;
				overflow-x: auto;
				padding: 0.5rem;
			}
			
			.filter-option {
				flex-shrink: 0;
				white-space: nowrap;
				border-left: none;
				border-bottom: 3px solid transparent;
				padding: 0.5rem 0.75rem;
			}
			
			.filter-option.active {
				border-left-color: transparent;
				border-bottom-color: #2563eb;
			}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">All Seniors</h1>
			<p class="content-subtitle">Manage senior citizen records and information</p>
		</header>
		
		<div class="content-body">
			<!-- Filter Sidebar -->
			<div class="filter-sidebar">
				<div class="filter-header">
					<h3>Filter by Status</h3>
				</div>
				<div class="filter-options">
					<a href="<?= BASE_URL ?>/admin/seniors.php?status=all" class="filter-option <?= $status === 'all' ? 'active' : '' ?>">
						<i class="fas fa-users"></i>
						<span>All Seniors</span>
						<span class="count"><?= count($seniors) ?></span>
					</a>
					<a href="<?= BASE_URL ?>/admin/seniors.php?status=active" class="filter-option <?= $status === 'active' ? 'active' : '' ?>">
						<i class="fas fa-user-check"></i>
						<span>Active</span>
						<span class="count"><?= $livingCount ?></span>
					</a>
					<a href="<?= BASE_URL ?>/admin/seniors.php?status=inactive" class="filter-option <?= $status === 'inactive' ? 'active' : '' ?>">
						<i class="fas fa-user-times"></i>
						<span>Inactive</span>
						<span class="count">0</span>
					</a>
					<a href="<?= BASE_URL ?>/admin/seniors.php?status=deceased" class="filter-option <?= $status === 'deceased' ? 'active' : '' ?>">
						<i class="fas fa-user-slash"></i>
						<span>Deceased</span>
						<span class="count"><?= $deceasedCount ?></span>
					</a>
					<a href="<?= BASE_URL ?>/admin/seniors.php?status=transferred" class="filter-option <?= $status === 'transferred' ? 'active' : '' ?>">
						<i class="fas fa-user-arrow-right"></i>
						<span>Transferred</span>
						<span class="count">0</span>
					</a>
				</div>
			</div>
			
			<!-- Main Content Area -->
			<div class="main-content-area">
		<!-- All Seniors Section -->
		<div class="content-section">
			<div class="section-header">
				<h2 class="section-title">
					<?php
					switch($status) {
						case 'active':
							echo 'Active Seniors';
							break;
						case 'inactive':
							echo 'Inactive Seniors';
							break;
						case 'deceased':
							echo 'Deceased Seniors';
							break;
						case 'transferred':
							echo 'Transferred Seniors';
							break;
						default:
							echo 'All Seniors';
					}
					?>
				</h2>
				<div class="header-actions">
					<button class="button primary" onclick="openAddModal()">
						<i class="fas fa-plus"></i>
						Add Senior
					</button>
				</div>
			</div>

		<!-- Alert Messages -->
		<?php if ($message): ?>
		<div class="alert alert-success">
			<div class="alert-icon">
				<i class="fas fa-check-circle"></i>
			</div>
			<div class="alert-content">
				<strong>Success!</strong>
				<p><?= htmlspecialchars($message) ?></p>
			</div>
		</div>
		<?php endif; ?>



		<!-- Seniors List -->
		<div class="card">
			<div class="card-header">
				<h2>
					<i class="fas fa-list"></i>
					All Seniors (<?= count($seniors) ?>)
				</h2>
				<div class="card-actions">
					<div class="search-container">
						<span class="search-icon">🔍</span>
						<input type="text" placeholder="Search seniors..." id="searchSeniors">
					</div>
				</div>
			</div>
			<div class="card-body">
				<?php if (!empty($seniors)): ?>
				<?php
				// Group seniors by barangay
				$seniorsByBarangay = [];
				foreach ($seniors as $senior) {
					$seniorsByBarangay[$senior['barangay']][] = $senior;
				}
				ksort($seniorsByBarangay); // Sort barangays alphabetically
				?>
				
				<?php foreach ($seniorsByBarangay as $barangay => $barangaySeniors): ?>
				<div class="barangay-section">
					<div class="barangay-header">
						<h3>
							<i class="fas fa-map-marker-alt"></i>
							<?= htmlspecialchars($barangay) ?>
							<span class="barangay-count">(<?= count($barangaySeniors) ?> seniors)</span>
						</h3>
					</div>
					<div class="table-container">
						<table>
							<thead>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Age</th>
									<th>Gender</th>
									<th>Category</th>
									<th>Status</th>
									<?php if ($status === 'active'): ?>
									<th>Events Attended</th>
									<?php endif; ?>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody class="barangay-seniors" data-barangay="<?= htmlspecialchars($barangay) ?>">
								<?php foreach ($barangaySeniors as $senior): ?>
								<tr onclick="viewSeniorDetails(<?= $senior['id'] ?>)" style="cursor: pointer;">
									<td>
										<span class="badge badge-primary">#<?= $senior['id'] ?></span>
									</td>
									<td>
										<div class="senior-info">
											<div class="senior-avatar">
												<i class="fas fa-user"></i>
											</div>
											<div class="senior-details">
												<span class="senior-name"><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></span>
												<?php if ($senior['middle_name']): ?>
												<span class="senior-middle"><?= htmlspecialchars($senior['middle_name']) ?></span>
												<?php endif; ?>
											</div>
										</div>
									</td>
									<td>
										<span class="age-badge"><?= $senior['age'] ?> years</span>
									</td>
									<td>
										<?php
										$genderClass = '';
										$genderIcon = '';
										switch ($senior['sex']) {
											case 'male':
												$genderClass = 'badge-info';
												$genderIcon = '♂';
												break;
											case 'female':
												$genderClass = 'badge-pink';
												$genderIcon = '♀';
												break;
											case 'lgbtq':
												$genderClass = 'badge-rainbow';
												$genderIcon = '🏳️‍🌈';
												break;
											default:
												$genderClass = 'badge-muted';
												$genderIcon = '?';
										}
										?>
										<span class="badge <?= $genderClass ?>">
											<?= $genderIcon ?> <?= ucfirst($senior['sex'] ?: 'Not specified') ?>
										</span>
									</td>
									<td>
										<span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>">
											<?= ucfirst($senior['category']) ?>
										</span>
									</td>
									<td>
										<span class="badge <?= $senior['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>">
											<?= ucfirst($senior['life_status']) ?>
										</span>
									</td>
									<?php if ($status === 'active'): ?>
									<td>
										<span class="event-count-badge">
											<i class="fas fa-calendar-check"></i>
											<?= $senior['event_count'] ?> events
										</span>
										<?php if ($senior['events_attended']): ?>
										<div class="events-list">
											<small><?= htmlspecialchars(substr($senior['events_attended'], 0, 50)) ?><?= strlen($senior['events_attended']) > 50 ? '...' : '' ?></small>
										</div>
										<?php endif; ?>
									</td>
									<?php endif; ?>
									<td>
										<div class="action-buttons" onclick="event.stopPropagation()">
											<button class="button small primary" onclick="viewSeniorDetails(<?= $senior['id'] ?>)">
												<i class="fas fa-eye"></i>
											</button>
											<button class="button small secondary" onclick="editSenior(<?= $senior['id'] ?>)">
												<i class="fas fa-edit"></i>
											</button>
											<button class="button small danger" onclick="deleteSenior(<?= $senior['id'] ?>, '<?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?>')">
												<i class="fas fa-trash"></i>
											</button>
										</div>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endforeach; ?>
				<?php else: ?>
				<div class="empty-state">
					<div class="empty-icon">
						<i class="fas fa-users"></i>
					</div>
					<h3>No Seniors Found</h3>
					<p>Start by adding your first senior citizen to the system.</p>
					<button class="button primary" onclick="openAddModal()">
						<i class="fas fa-plus"></i>
						Add First Senior
					</button>
				</div>
				<?php endif; ?>
			</div>
		</div>
			</div> <!-- Close main-content-area -->
		</div> <!-- Close content-body -->
	</main>
							
							<div class="form-group full-width">
								<label for="other_skills" class="form-label">
									<span class="label-text">Other Skills</span>
								</label>
								<textarea 
									name="other_skills" 
									id="other_skills"
									class="form-input" 
									rows="3"
									placeholder="Enter other skills"
								></textarea>
							</div>
						</div>
						
						<!-- Hidden fields for existing functionality -->
						<div class="form-row hidden-fields">
							<div class="form-group">
								<label for="life_status" class="form-label">
									<span class="label-text">Life Status</span>
								</label>
								<select name="life_status" id="life_status" class="form-input">
									<option value="living">Living</option>
									<option value="deceased">Deceased</option>
								</select>
							</div>
							
							<div class="form-group">
								<label for="category" class="form-label">
									<span class="label-text">Category</span>
								</label>
								<select name="category" id="category" class="form-input">
									<option value="local">Local</option>
									<option value="national">National</option>
								</select>
							</div>
							
							<div class="form-group checkbox-group">
								<label class="checkbox-label">
									<input 
										type="checkbox" 
										name="benefits_received" 
										id="benefits_received"
										class="checkbox-input"
									>
									<span class="checkbox-custom"></span>
									Benefits Received
								</label>
							</div>
						</div>
					</div>
					
					<!-- Family Composition Section -->
					<div class="form-section">
						<h3 class="section-title">Family Composition</h3>
						<div id="familyCompositionContainer">
							<div class="family-member-row">
								<div class="form-row">
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Name</span>
										</label>
										<input 
											type="text" 
											name="family_name[]" 
											class="form-input" 
											placeholder="Enter name"
										>
									</div>
									
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Birthday</span>
										</label>
										<input 
											type="date" 
											name="family_birthday[]" 
											class="form-input"
										>
									</div>
									
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Age</span>
										</label>
										<input 
											type="number" 
											name="family_age[]" 
											class="form-input" 
											placeholder="Age"
										>
									</div>
									
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Relation</span>
										</label>
										<input 
											type="text" 
											name="family_relation[]" 
											class="form-input" 
											placeholder="e.g., Spouse, Child"
										>
									</div>
								</div>
								
								<div class="form-row">
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Civil Status</span>
										</label>
										<input 
											type="text" 
											name="family_civil_status[]" 
											class="form-input" 
											placeholder="Civil status"
										>
									</div>
									
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Occupation</span>
										</label>
										<input 
											type="text" 
											name="family_occupation[]" 
											class="form-input" 
											placeholder="Occupation"
										>
									</div>
									
									<div class="form-group">
										<label class="form-label">
											<span class="label-text">Income</span>
										</label>
										<input 
											type="number" 
											name="family_income[]" 
											class="form-input" 
											step="0.01"
											placeholder="Monthly income"
										>
									</div>
									
									<div class="form-group">
										<button type="button" class="button secondary small" onclick="removeFamilyMember(this)">
											<i class="fas fa-trash"></i>
										</button>
									</div>
								</div>
							</div>
						</div>
						<button type="button" class="button secondary" onclick="addFamilyMember()">
							<i class="fas fa-plus"></i>
							Add Family Member
						</button>
					</div>
					
					<!-- Association Information Section -->
					<div class="form-section">
						<h3 class="section-title">Association Information</h3>
						
						<div class="form-row">
							<div class="form-group">
								<label for="association_name" class="form-label">
									<span class="label-text">Name of Association</span>
								</label>
								<input 
									type="text" 
									name="association_name" 
									id="association_name"
									class="form-input" 
									placeholder="Enter association name"
								>
							</div>
							
							<div class="form-group">
								<label for="membership_date" class="form-label">
									<span class="label-text">Date of Membership</span>
								</label>
								<input 
									type="date" 
									name="membership_date" 
									id="membership_date"
									class="form-input"
								>
							</div>
						</div>
						
						<div class="form-row">
							<div class="form-group full-width">
								<label for="association_address" class="form-label">
									<span class="label-text">Address of Association</span>
								</label>
								<textarea 
									name="association_address" 
									id="association_address"
									class="form-input" 
									rows="2"
									placeholder="Enter association address"
								></textarea>
							</div>
						</div>
						
						<div class="form-row">
							<div class="form-group checkbox-group">
								<label class="checkbox-label">
									<input 
										type="checkbox" 
										name="is_officer" 
										id="is_officer"
										class="checkbox-input"
										onchange="toggleOfficerFields()"
									>
									<span class="checkbox-custom"></span>
									Is an Officer
								</label>
							</div>
							
							<div class="form-group" id="positionGroup" style="display: none;">
								<label for="position" class="form-label">
									<span class="label-text">Position</span>
								</label>
								<input 
									type="text" 
									name="position" 
									id="position"
									class="form-input" 
									placeholder="Enter position"
								>
							</div>
							
							<div class="form-group" id="dateElectedGroup" style="display: none;">
								<label for="date_elected" class="form-label">
									<span class="label-text">Date Elected</span>
								</label>
								<input 
									type="date" 
									name="date_elected" 
									id="date_elected"
									class="form-input"
								>
							</div>
						</div>
					</div>
					
					<div class="form-actions">
						<button type="button" class="button secondary" onclick="closeAddModal()">
							<i class="fas fa-times"></i>
							Cancel
						</button>
						<button type="submit" class="button primary">
							<i class="fas fa-save"></i>
							Add Senior
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Delete Confirmation Modal -->
	<div class="modal-overlay" id="deleteModal">
		<div class="modal">
			<div class="modal-header">
				<h2 class="modal-title">
					<i class="fas fa-exclamation-triangle"></i>
					Confirm Delete
				</h2>
				<button class="modal-close" onclick="closeDeleteModal()">&times;</button>
			</div>
			<div class="modal-body">
				<div class="delete-warning">
					<div class="warning-icon">
						<i class="fas fa-trash"></i>
					</div>
					<h3>Are you sure?</h3>
					<p>You are about to delete the senior <strong id="deleteSeniorName"></strong>. This action cannot be undone.</p>
				</div>
				<form method="post" id="deleteForm">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="delete">
					<input type="hidden" name="id" id="deleteSeniorId">
					
					<div class="form-actions">
						<button type="button" class="button secondary" onclick="closeDeleteModal()">
							<i class="fas fa-times"></i>
							Cancel
						</button>
						<button type="submit" class="button danger">
							<i class="fas fa-trash"></i>
							Delete Senior
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Senior Details Modal -->
	<div class="modal-overlay" id="seniorDetailsModal">
		<div class="modal large">
			<div class="modal-header">
				<h2 class="modal-title">
					<i class="fas fa-user"></i>
					Senior Details
				</h2>
				<button class="modal-close" onclick="closeSeniorDetailsModal()">&times;</button>
			</div>
			<div class="modal-body" id="seniorDetailsContent">
				<!-- Content will be loaded via AJAX -->
				<div class="loading-state">
					<div class="loading-spinner"></div>
					<p>Loading senior details...</p>
				</div>
			</div>
		</div>
	</div>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Initialize functionality on page load
		document.addEventListener('DOMContentLoaded', function() {
			initializeSearch();
		});

		// Modal functions
		function openAddModal() {
			document.getElementById('addSeniorModal').classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeAddModal() {
			document.getElementById('addSeniorModal').classList.remove('active');
			document.body.style.overflow = '';
			document.getElementById('addSeniorForm').reset();
		}

		function openDeleteModal(seniorId, seniorName) {
			document.getElementById('deleteSeniorId').value = seniorId;
			document.getElementById('deleteSeniorName').textContent = seniorName;
			document.getElementById('deleteModal').classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeDeleteModal() {
			document.getElementById('deleteModal').classList.remove('active');
			document.body.style.overflow = '';
		}

		function viewSeniorDetails(id) {
			document.getElementById('seniorDetailsModal').classList.add('active');
			document.body.style.overflow = 'hidden';
			
			// Load senior details via AJAX
			fetch(`senior_details.php?id=${id}`)
				.then(response => response.text())
				.then(html => {
					document.getElementById('seniorDetailsContent').innerHTML = html;
				})
				.catch(error => {
					document.getElementById('seniorDetailsContent').innerHTML = 
						'<div class="error-state"><p>Error loading senior details. Please try again.</p></div>';
				});
		}

		function closeSeniorDetailsModal() {
			document.getElementById('seniorDetailsModal').classList.remove('active');
			document.body.style.overflow = '';
		}

		function editSenior(id) {
			// TODO: Implement edit functionality
			alert('Edit functionality will be implemented soon!');
		}

		function deleteSenior(id, name) {
			openDeleteModal(id, name);
		}

		// Registration form functions
		function calculateAge() {
			const dateOfBirth = document.getElementById('date_of_birth').value;
			const ageInput = document.getElementById('age');
			
			if (dateOfBirth) {
				const today = new Date();
				const birthDate = new Date(dateOfBirth);
				let age = today.getFullYear() - birthDate.getFullYear();
				const monthDiff = today.getMonth() - birthDate.getMonth();
				
				if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
					age--;
				}
				
				ageInput.value = age;
			}
		}

		function addFamilyMember() {
			const container = document.getElementById('familyCompositionContainer');
			const newRow = document.createElement('div');
			newRow.className = 'family-member-row';
			newRow.innerHTML = `
				<div class="form-row">
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Name</span>
						</label>
						<input 
							type="text" 
							name="family_name[]" 
							class="form-input" 
							placeholder="Enter name"
						>
					</div>
					
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Birthday</span>
						</label>
						<input 
							type="date" 
							name="family_birthday[]" 
							class="form-input"
						>
					</div>
					
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Age</span>
						</label>
						<input 
							type="number" 
							name="family_age[]" 
							class="form-input" 
							placeholder="Age"
						>
					</div>
					
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Relation</span>
						</label>
						<input 
							type="text" 
							name="family_relation[]" 
							class="form-input" 
							placeholder="e.g., Spouse, Child"
						>
					</div>
				</div>
				
				<div class="form-row">
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Civil Status</span>
						</label>
						<input 
							type="text" 
							name="family_civil_status[]" 
							class="form-input" 
							placeholder="Civil status"
						>
					</div>
					
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Occupation</span>
						</label>
						<input 
							type="text" 
							name="family_occupation[]" 
							class="form-input" 
							placeholder="Occupation"
						>
					</div>
					
					<div class="form-group">
						<label class="form-label">
							<span class="label-text">Income</span>
						</label>
						<input 
							type="number" 
							name="family_income[]" 
							class="form-input" 
							step="0.01"
							placeholder="Monthly income"
						>
					</div>
					
					<div class="form-group">
						<button type="button" class="button secondary small" onclick="removeFamilyMember(this)">
							<i class="fas fa-trash"></i>
						</button>
					</div>
				</div>
			`;
			container.appendChild(newRow);
		}

		function removeFamilyMember(button) {
			const familyRow = button.closest('.family-member-row');
			const container = document.getElementById('familyCompositionContainer');
			
			// Don't remove if it's the only family member row
			if (container.children.length > 1) {
				familyRow.remove();
			}
		}

		function toggleOfficerFields() {
			const isOfficer = document.getElementById('is_officer').checked;
			const positionGroup = document.getElementById('positionGroup');
			const dateElectedGroup = document.getElementById('dateElectedGroup');
			
			if (isOfficer) {
				positionGroup.style.display = 'block';
				dateElectedGroup.style.display = 'block';
			} else {
				positionGroup.style.display = 'none';
				dateElectedGroup.style.display = 'none';
				document.getElementById('position').value = '';
				document.getElementById('date_elected').value = '';
			}
		}

		// Search functionality for barangay sections
		function initializeSearch() {
			const searchInput = document.getElementById('searchSeniors');
			
			if (searchInput) {
				searchInput.addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					const barangaySections = document.querySelectorAll('.barangay-section');
					let totalVisible = 0;
					let totalRows = 0;
					
					barangaySections.forEach(section => {
						const rows = section.querySelectorAll('.barangay-seniors tr');
						let visibleInSection = 0;
						
						rows.forEach(row => {
							totalRows++;
							const text = row.textContent.toLowerCase();
							const isVisible = text.includes(searchTerm);
							row.style.display = isVisible ? '' : 'none';
							if (isVisible) {
								visibleInSection++;
								totalVisible++;
							}
						});
						
						// Show/hide barangay section based on visible seniors
						const barangayHeader = section.querySelector('.barangay-header');
						const barangayCount = section.querySelector('.barangay-count');
						
						if (visibleInSection > 0) {
							section.style.display = '';
							barangayCount.textContent = `(${visibleInSection} seniors)`;
						} else {
							section.style.display = 'none';
						}
					});
					
					// Update search results indicator
					updateSearchResults(searchInput, totalVisible, totalRows);
				});
			}
		}

		function updateSearchResults(input, visible, total) {
			let indicator = input.parentNode.querySelector('.search-results');
			if (!indicator) {
				indicator = document.createElement('div');
				indicator.className = 'search-results';
				indicator.style.cssText = `
					position: absolute;
					right: 1rem;
					top: 50%;
					transform: translateY(-50%);
					font-size: var(--font-size-xs);
					color: var(--muted);
					font-weight: 600;
					background: var(--bg-secondary);
					padding: var(--space-xs) var(--space-sm);
					border-radius: var(--radius-sm);
				`;
				input.parentNode.style.position = 'relative';
				input.parentNode.appendChild(indicator);
			}
			
			if (total > visible) {
				indicator.textContent = `${visible} of ${total}`;
				indicator.style.color = 'var(--warning)';
			} else {
				indicator.textContent = '';
			}
		}

		// Close modals when clicking outside
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('modal-overlay')) {
				e.target.classList.remove('active');
				document.body.style.overflow = '';
			}
		});

		// Close modals with Escape key
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				const activeModal = document.querySelector('.modal-overlay.active');
				if (activeModal) {
					activeModal.classList.remove('active');
					document.body.style.overflow = '';
				}
			}
		});
	</script>
</body>
</html>