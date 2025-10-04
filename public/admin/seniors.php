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
		$ext_name = trim($_POST['ext_name'] ?? '');  // Added extension field
		$age = (int)($_POST['age'] ?? 0);
		$date_of_birth = $_POST['date_of_birth'] ?? null;
		$sex = $_POST['sex'] ?? null;
		$place_of_birth = trim($_POST['place_of_birth'] ?? '');
		$civil_status = $_POST['civil_status'] ?? '';
		$educational_attainment = $_POST['educational_attainment'] ?? '';
		$occupation = trim($_POST['occupation'] ?? '');
		$annual_income = $_POST['annual_income'] ? (float)$_POST['annual_income'] : null;
		$other_skills = trim($_POST['other_skills'] ?? '');
		$barangay = trim($_POST['barangay'] ?? '');
		$contact = trim($_POST['contact'] ?? '');
		$osca_id_no = trim($_POST['osca_id_no'] ?? '');
		$remarks = trim($_POST['remarks'] ?? '');
		$health_condition = trim($_POST['health_condition'] ?? '');
		$purok = trim($_POST['purok'] ?? '');
		$cellphone = trim($_POST['cellphone'] ?? '');
		$benefits_received = isset($_POST['benefits_received']) ? 1 : 0;
		$life_status = ($_POST['life_status'] ?? '') === 'deceased' ? 'deceased' : 'living';
		$category = $_POST['category'] === 'national' ? 'national' : 'local';

		// Check if waiting list checkbox is set
		if (isset($_POST['waiting_list']) && $_POST['waiting_list'] === '1') {
			$category = 'waiting';
		}

		// Set validation status and date based on category
		$validation_status = $category === 'waiting' ? 'Not Validated' : 'Validated';
		$validation_date = $category === 'waiting' ? null : date('Y-m-d H:i:s');

		if ($first_name && $last_name && $age && $barangay && $osca_id_no) {
			try {
				$pdo->beginTransaction();

				if ($op === 'create') {
					$stmt = $pdo->prepare('INSERT INTO seniors (first_name, middle_name, last_name, ext_name, age, date_of_birth, sex, place_of_birth, civil_status, educational_attainment, occupation, annual_income, other_skills, barangay, contact, osca_id_no, remarks, health_condition, purok, cellphone, benefits_received, life_status, category, validation_status, validation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $ext_name ?: null, $age,
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: '', $educational_attainment ?: '',
						$occupation ?: null, $annual_income, $other_skills,
						$barangay, $contact ?: null, $osca_id_no, $remarks ?: null,
						$health_condition ?: null, $purok ?: null, $cellphone ?: null,
						$benefits_received, $life_status, $category, $validation_status, $validation_date
					]);
					$senior_id = $pdo->lastInsertId();
					$message = 'Senior added successfully';
				} else {
					$stmt = $pdo->prepare('UPDATE seniors SET first_name=?, middle_name=?, last_name=?, ext_name=?, age=?, date_of_birth=?, sex=?, place_of_birth=?, civil_status=?, educational_attainment=?, occupation=?, annual_income=?, other_skills=?, barangay=?, contact=?, osca_id_no=?, remarks=?, health_condition=?, purok=?, cellphone=?, benefits_received=?, life_status=?, category=?, validation_status=?, validation_date=? WHERE id=?');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $ext_name ?: null, $age,
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: '', $educational_attainment ?: '',
						$occupation ?: null, $annual_income, $other_skills,
						$barangay, $contact ?: null, $osca_id_no, $remarks ?: null,
						$health_condition ?: null, $purok ?: null, $cellphone ?: null,
						$benefits_received, $life_status, $category, $validation_status, $validation_date, $id
					]);
					$senior_id = $id;
					$message = 'Senior updated successfully';
				}
				
				// Handle family composition
				if ($op === 'update' || $op === 'create') {
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
				}
				
				$pdo->commit();
			} catch (Exception $e) {
				$pdo->rollback();
				$message = 'Error: ' . $e->getMessage();
			}
		}

		// Handle validation of waiting seniors
		if ($op === 'validate_waiting') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				// Update category from 'waiting' to 'local', set validation_status to 'Validated' and validation_date to now
				$stmt = $pdo->prepare('UPDATE seniors SET category = ?, validation_status = ?, validation_date = NOW() WHERE id = ?');
				$stmt->execute(['local', 'Validated', $id]);
				$message = 'Senior validated successfully';
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
    $sql = 'SELECT DISTINCT s.*, validation_status, validation_date, COUNT(a.id) as event_count, GROUP_CONCAT(e.title SEPARATOR ", ") as events_attended
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
    $sql = 'SELECT s.*, validation_status, validation_date, 0 as event_count, "" as events_attended
            FROM seniors s
            LEFT JOIN attendance a ON s.id = a.senior_id
            WHERE s.life_status = "living" AND a.id IS NULL
            ORDER BY s.created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute();
    $seniors = $stmtAll->fetchAll();
} elseif ($status === 'transferred') {
    // Transferred seniors: those who have moved to another barangay
    $sql = 'SELECT s.*, validation_status, validation_date, 0 as event_count, "" as events_attended
            FROM seniors s
            WHERE s.life_status = "living" AND s.category = "national"
            ORDER BY s.created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute();
    $seniors = $stmtAll->fetchAll();
} elseif ($status === 'waiting') {
    // Waiting seniors: example filter, adjust as needed
    $sql = 'SELECT s.*, validation_status, validation_date, 0 as event_count, "" as events_attended
            FROM seniors s
            WHERE s.life_status = "living" AND s.category = "waiting"
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

    $sql = 'SELECT *, validation_status, validation_date, 0 as event_count, "" as events_attended FROM seniors';
    if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY created_at DESC';
    $stmtAll = $pdo->prepare($sql);
    $stmtAll->execute($params);
    $seniors = $stmtAll->fetchAll();
}

$grouped = [];
foreach ($seniors as $senior) {
    $grouped[$senior['barangay']][] = $senior;
}
ksort($grouped);

foreach ($grouped as $barangay => &$seniors_in_barangay) {
    usort($seniors_in_barangay, function($a, $b) {
        $cmp = strcmp($a['last_name'], $b['last_name']);
        if ($cmp === 0) $cmp = strcmp($a['first_name'], $b['first_name']);
        return $cmp;
    });
}

$livingCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living'")->fetchColumn();
$deceasedCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased'")->fetchColumn();
$waitingCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living' AND category='waiting'")->fetchColumn();

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

		.badge-info {
			background: #dbeafe;
			color: #1e40af;
		}

		.badge-pink {
			background: #fce7f3;
			color: #be185d;
		}

		.badge-rainbow {
			background: linear-gradient(45deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #9400d3);
			color: white;
		}

		.badge-muted {
			background: #f3f4f6;
			color: #6b7280;
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
		
		/* Modal styles */
		.modal-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0,0,0,0.5);
			z-index: 1000;
			display: none;
		}

		.modal-overlay.active {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.modal {
			background: #fff;
			border-radius: 8px;
			padding: 1.5rem;
			box-shadow: 0 10px 25px rgba(0,0,0,0.2);
			max-height: 90vh;
			overflow-y: auto;
			width: 600px;
			max-width: 95%;
			animation: zoomIn 0.3s forwards;
		}

		.modal-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 1rem;
		}

		.modal-close {
			background: none;
			border: none;
			font-size: 1.5rem;
			cursor: pointer;
			color: #6b7280;
			padding: 0;
			width: auto;
			height: auto;
		}

		.modal-close:hover {
			color: #374151;
		}

		/* Nested Navigation Styles */
		.nav-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.nav-item {
			margin: 0;
		}

		.nav-item > .status-nav-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			width: 100%;
			cursor: pointer;
			transition: background-color 0.2s ease;
		}

		.nav-item > .status-nav-item:hover {
			background-color: #e5e7eb;
		}

		.toggle-icon {
			transition: transform 0.3s ease;
			font-size: 0.875rem;
		}

		.nav-item.expanded .toggle-icon {
			transform: rotate(180deg);
		}

		.sub-nav {
			list-style: none;
			padding: 0;
			margin: 0;
			margin-left: 1rem;
			max-height: 0;
			overflow: hidden;
			transition: max-height 0.3s ease;
		}

		.sub-nav.show {
			max-height: 500px;
		}

		.sub-nav .nav-item {
			margin-bottom: 0.25rem;
		}

		.sub-nav .status-nav-item {
			padding: 0.5rem 0.75rem;
			font-size: 0.875rem;
			border-radius: 4px;
		}

		.sub-nav .status-nav-item:hover {
			background-color: #e5e7eb;
		}

		/* Responsive design */
		@media (max-width: 1024px) {
			.content-body {
				flex-direction: column;
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
			
			<!-- Main Content Area -->
			<div class="main-content-area">


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
				<div class="card" style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: none; overflow: hidden;">
					<div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
						<div>
							<h2 class="card-title">All Seniors</h2>
							<p class="card-subtitle">Manage senior citizen records and information</p>
						</div>
<div style="display: flex; align-items: center; gap: 1rem;">
	<input type="text" id="searchInput" placeholder="Search seniors..." style="padding: 0.5rem; width: 250px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem;">
	<?php if ($status !== 'waiting'): ?>
	<button class="btn btn-primary" onclick="openAddSeniorModal()">
		Add New Senior
	</button>
	<?php endif; ?>
</div>
					</div>
					<div class="card-body">
						<div class="table-container">
							<table class="table">
								<thead>
									<tr>
										<th>LAST NAME</th>
										<th>FIRST NAME</th>
										<th>MIDDLE NAME</th>
										<th>EXT</th>
										<th>BARANGAY</th>
										<th>AGE</th>
										<th>SEX</th>
										<th>CIVIL STATUS</th>
										<th>BIRTHDATE</th>
										<th>OSCA ID NO.</th>
										<th>REMARKS</th>
										<th>HEALTH CONDITION</th>
										<th>PUROK</th>
										<th>PLACE OF BIRTH</th>
										<th>CELLPHONE #</th>
										<th>CATEGORY</th>
										<th>VALIDATION STATUS</th>
										<th>VALIDATED</th>
									</tr>
								</thead>
								<tbody id="seniorsTableBody">
									<?php if (!empty($grouped)): ?>
									<?php foreach ($grouped as $barangay => $seniors_in_barangay): ?>
									<tr class="barangay-header"><td colspan="18" style="background: #f9fafb; font-weight: bold; padding: 1rem;">Barangay <?= htmlspecialchars($barangay) ?></td></tr>
									<?php foreach ($seniors_in_barangay as $senior): ?>
									<tr onclick="viewSeniorDetails(<?= $senior['id'] ?>)" style="cursor: pointer;">
										<td><?= htmlspecialchars($senior['last_name']) ?></td>
										<td><?= htmlspecialchars($senior['first_name']) ?></td>
										<td><?= htmlspecialchars($senior['middle_name'] ?: '') ?></td>
<td><?= isset($senior['ext_name']) ? htmlspecialchars($senior['ext_name']) : '' ?></td> <!-- EXT -->
										<td><?= htmlspecialchars($senior['barangay']) ?></td>
										<td><?= $senior['age'] ?></td>
										<td>
											<?php
											switch ($senior['sex']) {
											case 'male': echo 'Male'; break;
											case 'female': echo 'Female'; break;
											case 'lgbtq': echo 'LGBTQ+'; break;
											default: echo 'Not specified';
											}
											?>
										</td>
										<td><?= htmlspecialchars($senior['civil_status'] ?: '') ?></td>
										<td><?= $senior['date_of_birth'] ? date('M d, Y', strtotime($senior['date_of_birth'])) : '' ?></td>
										<td><?= htmlspecialchars($senior['osca_id_no'] ?? '') ?></td>
										<td><?= htmlspecialchars($senior['remarks'] ?? '') ?></td>
										<td><?= htmlspecialchars($senior['health_condition'] ?? '') ?></td>
										<td><?= htmlspecialchars($senior['purok'] ?? '') ?></td>
										<td><?= htmlspecialchars($senior['place_of_birth'] ?: '') ?></td>
										<td><?= htmlspecialchars($senior['cellphone'] ?? '') ?></td>
										<td>
											<span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>">
												<?= ucfirst($senior['category']) ?>
											</span>
										</td>
										<td>
											<span class="badge <?= $senior['validation_status'] === 'Validated' ? 'badge-success' : 'badge-warning' ?>">
												<?= $senior['validation_status'] ?>
											</span>
										</td>
										<td>
											<?= $senior['validation_date'] ? date('M d, Y H:i', strtotime($senior['validation_date'])) : '-' ?>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php endforeach; ?>
									<?php else: ?>
									<tr class="no-data">
										<td colspan="18" style="text-align: center; padding: 2rem; color: var(--gov-text-muted);">
											No seniors found. Click "Add New Senior" to get started.
										</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div> <!-- Close main-content-area -->
		</div> <!-- Close content-body -->
	</main>

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
			// Open the edit modal and populate with senior data
			openEditSeniorModal(id);
		}

		function closeEditSeniorModal() {
			document.getElementById('editSeniorModal').style.display = 'none';
			document.body.style.overflow = '';
		}

		function deleteSenior(id, name) {
			openDeleteModal(id, name);
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

		// Search filter for seniors table
		document.getElementById('searchInput').addEventListener('input', function() {
			const filter = this.value.toLowerCase();
			const rows = document.querySelectorAll('#seniorsTableBody tr');
			let showGroup = false;
			rows.forEach(row => {
				if (row.classList.contains('no-data')) {
					row.style.display = 'none'; // hide no-data row during search
					return;
				}
				if (row.classList.contains('barangay-header')) {
					showGroup = false; // reset for new group
					const barangay = row.cells[0].textContent.toLowerCase().replace('barangay ', '');
					if (barangay.includes(filter)) {
						showGroup = true;
						row.style.display = '';
					} else {
						row.style.display = 'none';
					}
				} else {
					// senior row
					const lastName = row.cells[0].textContent.toLowerCase();
					const firstName = row.cells[1].textContent.toLowerCase();
					const middleName = row.cells[2].textContent.toLowerCase();
					const barangay = row.cells[4].textContent.toLowerCase();
					if (showGroup || lastName.includes(filter) || firstName.includes(filter) || middleName.includes(filter) || barangay.includes(filter)) {
						row.style.display = '';
						if (!showGroup) {
							// if this senior matches, show the header too
							let prevRow = row.previousElementSibling;
							while (prevRow && !prevRow.classList.contains('barangay-header')) {
								prevRow = prevRow.previousElementSibling;
							}
							if (prevRow) prevRow.style.display = '';
						}
					} else {
						row.style.display = 'none';
					}
				}
			});
		});

		// Close modals when clicking outside
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('modal-overlay')) {
				// Skip closing for addSeniorModal - only close via X button
				if (e.target.id === 'addSeniorModal') return;

				// Directly close modal without animation to avoid movement
				e.target.classList.remove('active');
				e.target.style.display = 'none'; // Hide overlay on close
				document.body.style.overflow = '';
				const modal = e.target.querySelector('.modal');
				if (modal) {
					modal.style.animation = '';
					modal.style.transform = '';
					modal.style.left = '';
					modal.style.top = '';
					modal.style.position = '';
					modal.style.zIndex = '';
					// Force reset all transform-related properties
					modal.style.setProperty('transform', 'none', 'important');
					modal.style.setProperty('animation', 'none', 'important');
				}
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

	<!-- Add Senior Modal -->
	<div class="modal-overlay" id="addSeniorModal" style="display:none;">
		<div class="modal" style="width: 600px; max-width: 95%; animation: zoomIn 0.3s forwards;">
			<div class="modal-header">
				<h2 class="modal-title">Add Senior</h2>
				<button class="modal-close" onclick="closeAddSeniorModal()">&times;</button>
			</div>
			<div class="modal-body">
				<form id="addSeniorForm" method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="create">

					<!-- Basic Information Section -->
					<div class="form-section">
						<h3 class="section-title">Basic Information</h3>
						<div class="form-row">
							<div class="form-group">
								<label for="first_name" class="form-label">
									<span class="label-text">First Name</span>
								</label>
								<input
									type="text"
									name="first_name"
									id="first_name"
									class="form-input"
									placeholder="Enter first name"
									required
								>
							</div>
							

							<div class="form-group">
								<label for="middle_name" class="form-label">
									<span class="label-text">Middle Name</span>
								</label>
								<input
									type="text"
									name="middle_name"
									id="middle_name"
									class="form-input"
									placeholder="Enter middle name"
								>
							</div>

							<div class="form-group">
								<label for="last_name" class="form-label">
									<span class="label-text">Last Name</span>
								</label>
								<input
									type="text"
									name="last_name"
									id="last_name"
									class="form-input"
									placeholder="Enter last name"
									required
								>
							</div>

							<div class="form-group">
								<label for="ext_name" class="form-label">
									<span class="label-text">Extension</span>
								</label>
								<input
									type="text"
									name="ext_name"
									id="ext_name"
									class="form-input"
									placeholder="Enter extension (e.g., Jr., Sr.)"
								>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="age" class="form-label">
									<span class="label-text">Age</span>
								</label>
								<input
									type="number"
									name="age"
									id="age"
									class="form-input"
									placeholder="Age"
									min="0"
									max="150"
									required
								>
							</div>

							<div class="form-group">
								<label for="date_of_birth" class="form-label">
									<span class="label-text">Date of Birth</span>
								</label>
								<input
									type="date"
									name="date_of_birth"
									id="date_of_birth"
									class="form-input"
									onchange="calculateAge()"
								>
							</div>

							<div class="form-group">
								<label for="sex" class="form-label">
									<span class="label-text">Sex</span>
								</label>
								<select name="sex" id="sex" class="form-input" required>
									<option value="">Select sex</option>
									<option value="male">Male</option>
									<option value="female">Female</option>
									<option value="lgbtq">LGBTQ+</option>
								</select>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="place_of_birth" class="form-label">
									<span class="label-text">Place of Birth</span>
								</label>
								<input
									type="text"
									name="place_of_birth"
									id="place_of_birth"
									class="form-input"
									placeholder="Enter place of birth"
								>
							</div>

							<div class="form-group">
								<label for="civil_status" class="form-label">
									<span class="label-text">Civil Status</span>
								</label>
								<select name="civil_status" id="civil_status" class="form-input" required>
									<option value="">Select civil status</option>
									<option value="single">Single</option>
									<option value="married">Married</option>
									<option value="widowed">Widowed</option>
									<option value="separated">Separated</option>
									<option value="divorced">Divorced</option>
								</select>
							</div>

							<div class="form-group">
								<label for="educational_attainment" class="form-label">
									<span class="label-text">Educational Attainment</span>
								</label>
								<select name="educational_attainment" id="educational_attainment" class="form-input" required>
									<option value="">Select educational attainment</option>
									<option value="no_formal_education">None</option>
									<option value="elementary">Elementary</option>
									<option value="high_school">High School</option>
									<option value="college">College</option>
									<option value="vocational">Vocational</option>
									<option value="graduate">Graduate</option>
									<option value="post_graduate">Post Graduate</option>
								</select>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="occupation" class="form-label">
									<span class="label-text">Occupation</span>
								</label>
								<input
									type="text"
									name="occupation"
									id="occupation"
									class="form-input"
									placeholder="Enter occupation"
								>
							</div>

							<div class="form-group">
								<label for="annual_income" class="form-label">
									<span class="label-text">Annual Income</span>
								</label>
								<input
									type="number"
									name="annual_income"
									id="annual_income"
									class="form-input"
									step="0.01"
									min="0"
									placeholder="Annual income"
								>
							</div>

							<div class="form-group">
								<label for="barangay" class="form-label">
									<span class="label-text">Barangay</span>
								</label>
								<select name="barangay" id="barangay" class="form-input" required>
									<option value="">Select barangay</option>
									<?php foreach ($barangays as $b): ?>
										<option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="contact" class="form-label">
									<span class="label-text">Contact</span>
								</label>
								<input
									type="text"
									name="contact"
									id="contact"
									class="form-input"
									placeholder="Enter contact number"
								>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="osca_id_no" class="form-label">
									<span class="label-text">OSCA ID NO.</span>
								</label>
								<input
									type="text"
									name="osca_id_no"
									id="osca_id_no"
									class="form-input"
									placeholder="Enter OSCA ID Number"
									required
								>
							</div>

							<div class="form-group">
								<label for="remarks" class="form-label">
									<span class="label-text">Remarks</span>
								</label>
								<input
									type="text"
									name="remarks"
									id="remarks"
									class="form-input"
									placeholder="Enter remarks"
								>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group">
								<label for="health_condition" class="form-label">
									<span class="label-text">Health Condition</span>
								</label>
								<input
									type="text"
									name="health_condition"
									id="health_condition"
									class="form-input"
									placeholder="Enter health condition"
								>
							</div>

							<div class="form-group">
								<label for="purok" class="form-label">
									<span class="label-text">Purok</span>
								</label>
								<input
									type="text"
									name="purok"
									id="purok"
									class="form-input"
									placeholder="Enter purok"
								>
							</div>

							<div class="form-group">
								<label for="cellphone" class="form-label">
									<span class="label-text">Cellphone #</span>
								</label>
								<input
									type="text"
									name="cellphone"
									id="cellphone"
									class="form-input"
									placeholder="Enter cellphone number"
								>
							</div>
						</div>
					</div>

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

					<!-- Hidden fields for existing functionality -->
					<div class="form-row hidden-fields">
						<div class="form-group">
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
							name="waiting_list"
							id="waiting_list"
							class="checkbox-input"
							value="1"
						>
						<span class="checkbox-custom"></span>
						On Waiting List
					</label>
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
						<button type="submit" class="button primary">
							<i class="fas fa-save"></i>
							Add Senior
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script>
		function openAddSeniorModal() {
			const modal = document.getElementById('addSeniorModal');
			modal.style.display = 'flex';
			modal.classList.add('active');
			document.body.style.overflow = 'hidden';
			// Remove any transform or position styles to prevent movement
			const modalContent = modal.querySelector('.modal');
			if (modalContent) {
				modalContent.style.transform = '';
				modalContent.style.left = '';
				modalContent.style.top = '';
			}
			// Apply blur to the main content area, not just content-body
			const mainContent = document.querySelector('main.main-content');
			if (mainContent) {
				mainContent.style.filter = 'blur(0)'; // Remove blur on open modal to show modal clearly
			}
		}

		function closeAddSeniorModal() {
			const modal = document.getElementById('addSeniorModal');
			modal.style.animation = 'zoomOut 0.3s forwards';
			setTimeout(() => {
				modal.style.display = 'none';
				modal.classList.remove('active');
				document.body.style.overflow = '';
				const mainContent = document.querySelector('main.main-content');
				if (mainContent) {
					mainContent.style.filter = ''; // Reset filter on close modal
				}
				document.getElementById('addSeniorForm').reset();
			}, 300);
		}

		function calculateAge() {
			const dobInput = document.getElementById('date_of_birth');
			const ageInput = document.getElementById('age');
			if (dobInput.value) {
				const dob = new Date(dobInput.value);
				const today = new Date();
				let age = today.getFullYear() - dob.getFullYear();
				const m = today.getMonth() - dob.getMonth();
				if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
					age--;
				}
				ageInput.value = age;
			}
		}
	</script>

	<!-- Edit Senior Modal -->
	<div id="editSeniorModal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
		<div class="modal" style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90%; overflow-y: auto; position: relative;">
			<div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
				<h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;">Edit Senior Profile</h2>
				<button onclick="closeEditSeniorModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
			</div>
			<form id="editSeniorForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="display: flex; flex-direction: column; gap: 1rem;">
				<input type="hidden" name="csrf" value="<?= $csrf ?>">
				<input type="hidden" name="op" value="update">
				<input type="hidden" name="id" id="editSeniorId">

				<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
					<div>
						<label for="editFirstName" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">First Name *</label>
						<input type="text" id="editFirstName" name="first_name" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
					<div>
						<label for="editMiddleName" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Middle Name</label>
						<input type="text" id="editMiddleName" name="middle_name" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
					<div>
						<label for="editExtName" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Extension</label>
						<input type="text" id="editExtName" name="ext_name" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="e.g., Jr., Sr.">
					</div>
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
					<div>
						<label for="editLastName" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Last Name *</label>
						<input type="text" id="editLastName" name="last_name" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
					<div>
						<label for="editAge" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Age *</label>
						<input type="number" id="editAge" name="age" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
					<div>
						<label for="editDateOfBirth" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Date of Birth</label>
						<input type="date" id="editDateOfBirth" name="date_of_birth" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
					<div>
						<label for="editSex" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Sex</label>
						<select id="editSex" name="sex" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="">Select Sex</option>
							<option value="male">Male</option>
							<option value="female">Female</option>
							<option value="lgbtq">LGBTQ+</option>
						</select>
					</div>
				</div>

				<div>
					<label for="editPlaceOfBirth" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Place of Birth</label>
					<input type="text" id="editPlaceOfBirth" name="place_of_birth" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
					<div>
						<label for="editCivilStatus" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Civil Status</label>
						<select id="editCivilStatus" name="civil_status" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="">Select Status</option>
							<option value="single">Single</option>
							<option value="married">Married</option>
							<option value="widowed">Widowed</option>
							<option value="separated">Separated</option>
							<option value="divorced">Divorced</option>
						</select>
					</div>
					<div>
						<label for="editEducationalAttainment" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Educational Attainment</label>
						<select id="editEducationalAttainment" name="educational_attainment" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="">Select Education</option>
							<option value="no_formal_education">None</option>
							<option value="elementary">Elementary</option>
							<option value="high_school">High School</option>
							<option value="college">College</option>
							<option value="post_graduate">Post Graduate</option>
						</select>
					</div>
				</div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
					<div>
						<label for="editOccupation" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Occupation</label>
						<input type="text" id="editOccupation" name="occupation" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
					<div>
						<label for="editAnnualIncome" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Annual Income</label>
						<input type="number" id="editAnnualIncome" name="annual_income" step="0.01" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
				</div>

				<div>
					<label for="editOtherSkills" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Other Skills</label>
					<textarea id="editOtherSkills" name="other_skills" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;"></textarea>
				</div>

				<div>
					<label for="editBarangay" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Barangay *</label>
					<select id="editBarangay" name="barangay" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
						<option value="">Select Barangay</option>
						<?php foreach ($barangays as $b): ?>
							<option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div>
					<label for="editContact" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Contact Number</label>
					<input type="text" id="editContact" name="contact" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
				</div>

				<div style="display: flex; align-items: center; gap: 1rem;">
					<label for="editBenefitsReceived" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
						<input type="checkbox" id="editBenefitsReceived" name="benefits_received" style="width: auto;">
						Benefits Received
					</label>
					<label for="editLifeStatus" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
						Life Status:
						<select id="editLifeStatus" name="life_status" style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="living">Living</option>
							<option value="deceased">Deceased</option>
						</select>
					</label>
					<label for="editCategory" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
						Category:
						<select id="editCategory" name="category" style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="local">Local</option>
							<option value="national">National</option>
						</select>
					</label>

					<label for="editWaitingList" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
						<input
							type="checkbox"
							id="editWaitingList"
							name="waiting_list"
							value="1"
							style="width: auto;"
						>
						On Waiting List
					</label>
				</div>

				<div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
					<button type="button" onclick="closeEditSeniorModal()" style="padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">Cancel</button>
					<button type="submit" style="padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">Update Senior</button>
				</div>
			</form>
		</div>
	</div>

	<script>
		// Populate waiting list checkbox in edit modal based on senior category
		document.getElementById('editSeniorModal').addEventListener('show', function() {
			const waitingListCheckbox = document.getElementById('editWaitingList');
			const categorySelect = document.getElementById('editCategory');
			waitingListCheckbox.checked = categorySelect.value === 'waiting';
		});

		// When loading senior data for edit, set waiting list checkbox accordingly
		function openEditSeniorModal(id) {
			fetch(`senior_details.php?id=${id}&edit=1`)
				.then(response => response.json())
				.then(data => {
					if (!data.success) {
						alert('Failed to load senior data for editing.');
						return;
					}
					const senior = data.senior;

					// Populate form fields
					document.getElementById('editSeniorId').value = senior.id;
					document.getElementById('editFirstName').value = senior.first_name;
					document.getElementById('editMiddleName').value = senior.middle_name || '';
					document.getElementById('editExtName').value = senior.ext_name || ''; // Added extension populate
					document.getElementById('editLastName').value = senior.last_name;
					document.getElementById('editAge').value = senior.age;
					document.getElementById('editDateOfBirth').value = senior.date_of_birth || '';
					document.getElementById('editSex').value = senior.sex || '';
					document.getElementById('editPlaceOfBirth').value = senior.place_of_birth || '';
					document.getElementById('editCivilStatus').value = senior.civil_status || '';
					document.getElementById('editEducationalAttainment').value = senior.educational_attainment || '';
					document.getElementById('editOccupation').value = senior.occupation || '';
					document.getElementById('editAnnualIncome').value = senior.annual_income || '';
					document.getElementById('editOtherSkills').value = senior.other_skills || '';
					document.getElementById('editBarangay').value = senior.barangay;
					document.getElementById('editContact').value = senior.contact || '';
					document.getElementById('editBenefitsReceived').checked = senior.benefits_received == 1;
					document.getElementById('editLifeStatus').value = senior.life_status;
					document.getElementById('editCategory').value = senior.category;

					// Set waiting list checkbox
					document.getElementById('editWaitingList').checked = senior.category === 'waiting';

					// Show modal
					document.getElementById('editSeniorModal').style.display = 'flex';
					document.body.style.overflow = 'hidden';
				})
				.catch(() => {
					alert('Error loading senior data.');
				});
		}
	</script>
</body>
</html>
