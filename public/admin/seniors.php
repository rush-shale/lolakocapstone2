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
					$message = 'Senior added successfully';
				} else {
					$stmt = $pdo->prepare('UPDATE seniors SET first_name=?, middle_name=?, last_name=?, age=?, barangay=?, contact=?, benefits_received=?, life_status=?, category=? WHERE id=?');
					$stmt->execute([$first_name,$middle_name ?: null,$last_name,$age,$barangay,$contact ?: null,$benefits_received,$life_status,$category,$id]);
					$message = 'Senior updated successfully';
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
	<title>All Seniors | LoLaKo</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<!-- Page Header -->
		<div class="page-header animate-fade-in">
			<div class="page-header-content">
				<div class="page-title">
					<h1>👥 All Seniors</h1>
					<p>Manage senior citizen records, benefits, and categories</p>
				</div>
				<div class="page-actions">
					<button class="button primary" onclick="openAddModal()">
						<i class="fas fa-plus"></i>
						Add Senior
					</button>
				</div>
			</div>
		</div>

		<!-- Alert Messages -->
		<?php if ($message): ?>
		<div class="alert alert-success animate-fade-in">
			<div class="alert-icon">
				<i class="fas fa-check-circle"></i>
			</div>
			<div class="alert-content">
				<strong>Success!</strong>
				<p><?= htmlspecialchars($message) ?></p>
			</div>
		</div>
		<?php endif; ?>

		<!-- Statistics Cards -->
		<div class="stats animate-fade-in">
			<div class="stat success">
				<div class="stat-icon">
					<i class="fas fa-users"></i>
				</div>
				<div class="stat-content">
					<h3>Living Seniors</h3>
					<p class="number"><?= $livingCount ?></p>
				</div>
			</div>
			<div class="stat danger">
				<div class="stat-icon">
					<i class="fas fa-skull"></i>
				</div>
				<div class="stat-content">
					<h3>Deceased</h3>
					<p class="number"><?= $deceasedCount ?></p>
				</div>
			</div>
			<div class="stat">
				<div class="stat-icon">
					<i class="fas fa-home"></i>
				</div>
				<div class="stat-content">
					<h3>Local</h3>
					<p class="number"><?= (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living' AND category='local'")->fetchColumn() ?></p>
				</div>
			</div>
			<div class="stat info">
				<div class="stat-icon">
					<i class="fas fa-flag"></i>
				</div>
				<div class="stat-content">
					<h3>National</h3>
					<p class="number"><?= (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living' AND category='national'")->fetchColumn() ?></p>
				</div>
			</div>
		</div>

		<!-- Filters -->
		<div class="filters animate-fade-in">
			<form method="get" class="filters-form">
				<div class="filter-group">
					<label class="filter-label">
						<i class="fas fa-heartbeat"></i>
						Life Status
					</label>
					<select name="life" class="filter-select">
						<option value="all" <?= $life==='all'?'selected':'' ?>>All</option>
						<option value="living" <?= $life==='living'?'selected':'' ?>>Living</option>
						<option value="deceased" <?= $life==='deceased'?'selected':'' ?>>Deceased</option>
					</select>
				</div>
				<div class="filter-group">
					<label class="filter-label">
						<i class="fas fa-gift"></i>
						Benefits Status
					</label>
					<select name="benefits" class="filter-select">
						<option value="all" <?= $benefits==='all'?'selected':'' ?>>All</option>
						<option value="received" <?= $benefits==='received'?'selected':'' ?>>Received</option>
						<option value="notyet" <?= $benefits==='notyet'?'selected':'' ?>>Not Yet</option>
					</select>
				</div>
				<div class="filter-group">
					<label class="filter-label">
						<i class="fas fa-tags"></i>
						Category
					</label>
					<select name="category" class="filter-select">
						<option value="all" <?= $category==='all'?'selected':'' ?>>All</option>
						<option value="local" <?= $category==='local'?'selected':'' ?>>Local</option>
						<option value="national" <?= $category==='national'?'selected':'' ?>>National</option>
					</select>
				</div>
				<div class="filter-actions">
					<button type="submit" class="button primary">
						<i class="fas fa-filter"></i>
						Apply Filters
					</button>
					<a href="seniors.php" class="button secondary">
						<i class="fas fa-refresh"></i>
						Reset
					</a>
				</div>
			</form>
		</div>

		<!-- Seniors List -->
		<div class="card animate-fade-in">
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
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>ID</th>
								<th>Name</th>
								<th>Age</th>
								<th>Barangay</th>
								<th>Category</th>
								<th>Benefits</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="seniorsTable">
							<?php foreach ($seniors as $senior): ?>
							<tr>
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
									<span class="barangay-info">
										<i class="fas fa-map-marker-alt"></i>
										<?= htmlspecialchars($senior['barangay']) ?>
									</span>
								</td>
								<td>
									<span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>">
										<?= ucfirst($senior['category']) ?>
									</span>
								</td>
								<td>
									<span class="badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
										<?= $senior['benefits_received'] ? 'Received' : 'Pending' ?>
									</span>
								</td>
								<td>
									<span class="badge <?= $senior['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>">
										<?= ucfirst($senior['life_status']) ?>
									</span>
								</td>
								<td>
									<div class="action-buttons">
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
	</main>

	<!-- Add Senior Modal -->
	<div class="modal-overlay" id="addSeniorModal">
		<div class="modal">
			<div class="modal-header">
				<h2 class="modal-title">
					<i class="fas fa-plus"></i>
					Add New Senior
				</h2>
				<button class="modal-close" onclick="closeAddModal()">&times;</button>
			</div>
			<div class="modal-body">
				<form method="post" class="modern-form" id="addSeniorForm">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="create">
					
					<div class="form-row">
						<div class="form-group">
							<label for="first_name" class="form-label">
								<i class="fas fa-user"></i>
								First Name
							</label>
							<input 
								type="text" 
								name="first_name" 
								id="first_name"
								class="form-input" 
								required 
								placeholder="Enter first name"
								autocomplete="given-name"
							>
							<div class="input-focus-line"></div>
						</div>
						
						<div class="form-group">
							<label for="middle_name" class="form-label">
								<i class="fas fa-user-circle"></i>
								Middle Name
							</label>
							<input 
								type="text" 
								name="middle_name" 
								id="middle_name"
								class="form-input" 
								placeholder="Enter middle name (optional)"
								autocomplete="additional-name"
							>
							<div class="input-focus-line"></div>
						</div>
					</div>
					
					<div class="form-row">
						<div class="form-group">
							<label for="last_name" class="form-label">
								<i class="fas fa-user"></i>
								Last Name
							</label>
							<input 
								type="text" 
								name="last_name" 
								id="last_name"
								class="form-input" 
								required 
								placeholder="Enter last name"
								autocomplete="family-name"
							>
							<div class="input-focus-line"></div>
						</div>
						
						<div class="form-group">
							<label for="age" class="form-label">
								<i class="fas fa-birthday-cake"></i>
								Age
							</label>
							<input 
								type="number" 
								name="age" 
								id="age"
								class="form-input" 
								min="60" 
								max="120" 
								required 
								placeholder="Enter age"
							>
							<div class="input-focus-line"></div>
						</div>
					</div>
					
					<div class="form-row">
						<div class="form-group">
							<label for="barangay" class="form-label">
								<i class="fas fa-map-marker-alt"></i>
								Barangay
							</label>
							<select name="barangay" id="barangay" class="form-select" required>
								<option value="">Select Barangay</option>
								<?php
								$barangays = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll();
								foreach ($barangays as $b) {
									echo '<option value="' . htmlspecialchars($b['name']) . '">' . htmlspecialchars($b['name']) . '</option>';
								}
								?>
							</select>
							<div class="input-focus-line"></div>
						</div>
						
						<div class="form-group">
							<label for="contact" class="form-label">
								<i class="fas fa-phone"></i>
								Contact Number
							</label>
							<input 
								type="tel" 
								name="contact" 
								id="contact"
								class="form-input" 
								placeholder="Enter contact number (optional)"
								autocomplete="tel"
							>
							<div class="input-focus-line"></div>
						</div>
					</div>
					
					<div class="form-section">
						<div class="form-section-title">
							<i class="fas fa-cog"></i>
							Senior Configuration
						</div>
						
						<div class="radio-group">
							<div class="radio-wrapper">
								<input type="radio" name="life_status" value="living" id="life_living" checked>
								<label for="life_living" class="radio-label">
									<span class="radio-mark"></span>
									Living
								</label>
							</div>
							<div class="radio-wrapper">
								<input type="radio" name="life_status" value="deceased" id="life_deceased">
								<label for="life_deceased" class="radio-label">
									<span class="radio-mark"></span>
									Deceased
								</label>
							</div>
						</div>
						
						<div class="radio-group">
							<div class="radio-wrapper">
								<input type="radio" name="category" value="local" id="cat_local" checked>
								<label for="cat_local" class="radio-label">
									<span class="radio-mark"></span>
									Local Senior
								</label>
							</div>
							<div class="radio-wrapper">
								<input type="radio" name="category" value="national" id="cat_national">
								<label for="cat_national" class="radio-label">
									<span class="radio-mark"></span>
									National Senior
								</label>
							</div>
						</div>
						
						<div class="checkbox-group">
							<div class="checkbox-wrapper">
								<input type="checkbox" name="benefits_received" id="benefits_received">
								<label for="benefits_received" class="checkbox-label">
									<span class="checkmark"></span>
									Benefits Already Received
								</label>
							</div>
						</div>
					</div>
					
					<div class="form-actions">
						<button type="button" class="button secondary" onclick="closeAddModal()">
							<i class="fas fa-times"></i>
							Cancel
						</button>
						<button type="submit" class="button primary">
							<i class="fas fa-plus"></i>
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

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Initialize theme and functionality on page load
		document.addEventListener('DOMContentLoaded', function() {
			initializeTheme();
			initializeSearch();
		});

		// Theme functionality
		function initializeTheme() {
			const savedTheme = localStorage.getItem('theme') || 'light';
			document.documentElement.setAttribute('data-theme', savedTheme);
		}

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

		function editSenior(id) {
			// TODO: Implement edit functionality
			alert('Edit functionality will be implemented soon!');
		}

		function deleteSenior(id, name) {
			openDeleteModal(id, name);
		}

		// Search functionality
		function initializeSearch() {
			const searchInput = document.getElementById('searchSeniors');
			const table = document.getElementById('seniorsTable');
			
			if (searchInput && table) {
				searchInput.addEventListener('input', function() {
					const searchTerm = this.value.toLowerCase();
					const rows = table.querySelectorAll('tr');
					let visibleCount = 0;
					
					rows.forEach(row => {
						const text = row.textContent.toLowerCase();
						const isVisible = text.includes(searchTerm);
						row.style.display = isVisible ? '' : 'none';
						if (isVisible) visibleCount++;
					});
					
					// Update search results indicator
					updateSearchResults(searchInput, visibleCount, rows.length);
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