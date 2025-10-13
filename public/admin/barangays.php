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
	<title>Barangays Management | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		.modal-overlay {
			position: fixed;
			top: 0; left: 0; right: 0; bottom: 0;
			background: rgba(0,0,0,0.5);
			display: none;
			justify-content: center;
			align-items: center;
			z-index: 1000;
		}
		.modal-overlay.active {
			display: flex;
			backdrop-filter: blur(5px);
			-\webkit-backdrop-filter: blur(5px);
		}
		.modal {
			background: white;
			border-radius: 8px;
			max-width: 500px;
			width: 90%;
			padding: 1rem;
			box-shadow: 0 2px 10px rgba(0,0,0,0.3);
			position: relative;
		}
		.modal-header {
			position: relative;
			padding-right: 2.5rem;
		}
		.modal-close {
			position: absolute;
			top: 0.5rem;
			right: 0.5rem;
			background: transparent;
			border: none;
			font-size: 1.5rem;
			cursor: pointer;
			color: #333;
		}
		.modal-close:hover {
			color: #000;
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="main-content">
		<header class="content-header">
			<h1 class="content-title">Barangays Management</h1>
			<p class="content-subtitle">Manage barangay information and locations</p>
		</header>
		
		<div class="content-body">

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
			<div class="stat">
				<div class="stat-icon">
					<i class="fas fa-map-marker-alt"></i>
				</div>
				<div class="stat-content">
					<h3>Total Barangays</h3>
					<p class="number"><?= count($barangays) ?></p>
				</div>
			</div>
			<div class="stat success">
				<div class="stat-icon">
					<i class="fas fa-users"></i>
				</div>
				<div class="stat-content">
					<h3>Active Locations</h3>
					<p class="number"><?= count($barangays) ?></p>
				</div>
			</div>
			<div class="stat info">
				<div class="stat-icon">
					<i class="fas fa-building"></i>
				</div>
				<div class="stat-content">
					<h3>Coverage Areas</h3>
					<p class="number">100%</p>
				</div>
			</div>
			</div>

			<!-- Barangays List -->
			<div class="card animate-fade-in">
				<div class="card-header">
					<h2 class="card-title">
						<i class="fas fa-list"></i>
						All Barangays
					</h2>
						<div class="card-actions">
							<button class="button primary" onclick="openAddModal()">
								<i class="fas fa-plus"></i>
								Add Barangay
							</button>
							<div class="search-container" style="max-width: 300px;">
								<input type="text" placeholder="Search barangays..." id="searchBarangays" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 12px; outline: none; font-size: 0.9rem;">
							</div>
						</div>
				</div>
			<div class="card-body">
				<?php if (!empty($barangays)): ?>
				<div class="table-container">
					<table>
						<thead>
							<tr>
								<th>ID</th>
								<th>Barangay Name</th>
								<th>Created</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody id="barangaysTable">
							<?php foreach ($barangays as $index => $barangay): ?>
							<tr>
								<td>
									<span class="badge badge-primary">#<?= $barangay['id'] ?></span>
								</td>
								<td>
									<div class="barangay-info">
										<div class="barangay-name">
											<i class="fas fa-map-marker-alt"></i>
											<?= htmlspecialchars($barangay['name']) ?>
										</div>
									</div>
								</td>
								<td>
									<span class="date-info">
										<?= date('M d, Y', strtotime($barangay['created_at'] ?? 'now')) ?>
									</span>
								</td>
								<td>
									<div class="action-buttons">
										<button class="button small danger" onclick="deleteBarangay(<?= $barangay['id'] ?>, '<?= htmlspecialchars($barangay['name']) ?>')">
											<i class="fas fa-trash"></i>
											Delete
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
						<i class="fas fa-map-marker-alt"></i>
					</div>
					<h3>No Barangays Found</h3>
					<p>Start by adding your first barangay to the system.</p>
					<button class="button primary" onclick="openAddModal()">
						<i class="fas fa-plus"></i>
						Add First Barangay
					</button>
				</div>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</main>

	<div class="modal-overlay" id="addBarangayModal">
		<div class="modal">
			<div class="modal-header">
				<h2 class="modal-title">
					<i class="fas fa-plus"></i>
					Add New Barangay
				</h2>
				<button class="modal-close" onclick="closeAddModal()">&times;</button>
			</div>
			<div class="modal-body">
				<form method="post" class="modern-form" id="addBarangayForm">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="create">
					
					<div class="form-group">
						<label class="form-label">
							<i class="fas fa-map-marker-alt"></i>
							Barangay Name
						</label>
						<input type="text" name="name" class="form-input" placeholder="Enter barangay name" required>
						<div class="input-focus-line"></div>
					</div>
					
					<div class="form-actions">
						<button type="button" class="button secondary" onclick="closeAddModal()">
							<i class="fas fa-times"></i>
							Cancel
						</button>
						<button type="submit" class="button primary">
							<i class="fas fa-plus"></i>
							Add Barangay
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
					<p>You are about to delete the barangay <strong id="deleteBarangayName"></strong>. This action cannot be undone.</p>
				</div>
				<form method="post" id="deleteForm">
					<input type="hidden" name="csrf" value="<?= $csrf ?>">
					<input type="hidden" name="op" value="delete">
					<input type="hidden" name="id" id="deleteBarangayId">
					
					<div class="form-actions">
						<button type="button" class="button secondary" onclick="closeDeleteModal()">
							<i class="fas fa-times"></i>
							Cancel
						</button>
						<button type="submit" class="button danger">
							<i class="fas fa-trash"></i>
							Delete Barangay
						</button>
					</div>
				</form>
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
			document.getElementById('addBarangayModal').classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeAddModal() {
			document.getElementById('addBarangayModal').classList.remove('active');
			document.body.style.overflow = '';
			document.getElementById('addBarangayForm').reset();
		}

		function openDeleteModal(barangayId, barangayName) {
			document.getElementById('deleteBarangayId').value = barangayId;
			document.getElementById('deleteBarangayName').textContent = barangayName;
			document.getElementById('deleteModal').classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeDeleteModal() {
			document.getElementById('deleteModal').classList.remove('active');
			document.body.style.overflow = '';
		}

		function deleteBarangay(id, name) {
			openDeleteModal(id, name);
		}

		// Search functionality
		function initializeSearch() {
			const searchInput = document.getElementById('searchBarangays');
			const table = document.getElementById('barangaysTable');
			
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


