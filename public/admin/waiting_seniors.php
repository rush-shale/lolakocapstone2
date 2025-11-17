<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

if (!function_exists('column_exists')) {
	function column_exists(PDO $pdo, string $table, string $column): bool {
		try {
			$stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
			$stmt->execute([$column]);
			return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			error_log("column_exists check failed for {$table}.{$column}: " . $e->getMessage());
			return false;
		}
	}
}

if (!function_exists('ensure_waiting_document_columns')) {
	function ensure_waiting_document_columns(PDO $pdo) {
		static $ensured = false;
		if ($ensured) {
			return;
		}
		try {
			if (!column_exists($pdo, 'seniors', 'waiting_birth_certificate')) {
				$pdo->exec("ALTER TABLE seniors ADD COLUMN waiting_birth_certificate TINYINT(1) NOT NULL DEFAULT 0 AFTER validation_date");
			}
			if (!column_exists($pdo, 'seniors', 'waiting_marriage_contract')) {
				$pdo->exec("ALTER TABLE seniors ADD COLUMN waiting_marriage_contract TINYINT(1) NOT NULL DEFAULT 0 AFTER waiting_birth_certificate");
			}
			if (!column_exists($pdo, 'seniors', 'waiting_valid_id')) {
				$pdo->exec("ALTER TABLE seniors ADD COLUMN waiting_valid_id TINYINT(1) NOT NULL DEFAULT 0 AFTER waiting_marriage_contract");
			}
		} catch (Exception $e) {
			error_log('Failed to ensure waiting document columns exist: ' . $e->getMessage());
		}
		$ensured = true;
	}
}

ensure_waiting_document_columns($pdo);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$op = $_POST['op'] ?? '';
		if ($op === 'validate_waiting') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				try {
					$pdo = get_db_connection();
					ensure_waiting_document_columns($pdo);
					$stmt = $pdo->prepare('UPDATE seniors SET category = ?, validation_status = ?, validation_date = NOW() WHERE id = ?');
					$stmt->execute(['local', 'Validated', $id]);
					$message = 'Senior validated successfully';
				} catch (Exception $e) {
					error_log("Validation failed: " . $e->getMessage());
					$message = 'Error validating senior: ' . $e->getMessage();
				}
			}
		}
	}
}

$csrf = generate_csrf_token();

// Load only waiting seniors
try {
	$pdo = get_db_connection();
	$sql = 'SELECT *, validation_status, validation_date, 0 as event_count, "" as events_attended
		FROM seniors WHERE life_status = "living" AND category = "waiting" ORDER BY created_at DESC';
	$stmtAll = $pdo->prepare($sql);
	$stmtAll->execute();
	$seniors = $stmtAll->fetchAll();
} catch (Exception $e) {
	error_log("Failed to load waiting seniors: " . $e->getMessage());
	$seniors = [];
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Waiting Seniors | SeniorCare Information System</title>
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		.table-container {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			max-width: 100%;
		}

		.missing-docs-list {
			margin: 0.5rem 0 0;
			padding-left: 1.25rem;
		}

		.missing-docs-list li {
			margin-bottom: 0.25rem;
		}
		
		.table-scroll {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
		}
		
		.table-scroll table {
			width: max-content;
			min-width: 100%;
			white-space: nowrap;
		}
		
		.table-scroll table th,
		.table-scroll table td {
			min-width: 120px;
			white-space: nowrap;
		}
		
		.table-search {
			display: flex;
			align-items: center;
			background: white;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			padding: 0.5rem 0.75rem;
			min-width: 250px;
		}
		
		.table-search-icon {
			margin-right: 0.5rem;
			color: #6b7280;
		}
		
		.table-search input {
			border: none;
			outline: none;
			background: transparent;
			flex: 1;
			font-size: 0.875rem;
		}
		
		/* Responsive design for tables */
		@media (min-width: 769px) and (max-width: 1024px) {
			.table-scroll table th,
			.table-scroll table td {
				min-width: 100px;
				font-size: 0.875rem;
				padding: 0.5rem 0.75rem;
			}
		}
		
		@media (min-width: 481px) and (max-width: 768px) {
			.table-scroll {
				-webkit-overflow-scrolling: touch;
				position: relative;
			}
			
			.table-scroll table {
				min-width: max-content;
			}
			
			.table-scroll table th,
			.table-scroll table td {
				min-width: 90px;
				font-size: 0.8125rem;
				padding: 0.5rem;
			}
		}
		
		@media (max-width: 480px) {
			.table-scroll {
				-webkit-overflow-scrolling: touch;
				position: relative;
				margin: 0 -0.75rem;
				padding: 0 0.75rem;
			}
			
			.table-scroll table {
				min-width: max-content;
				width: max-content;
			}
			
			.table-scroll table th,
			.table-scroll table td {
				min-width: 80px;
				font-size: 0.75rem;
				padding: 0.375rem 0.5rem;
			}
		}
	</style>
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Waiting Seniors</h1>
			<p class="content-subtitle">Seniors pending validation</p>
		</header>

		<div class="content-body">
			<div class="main-content-area">
				<?php if ($message): ?>
				<div class="alert alert-success">
					<div class="alert-icon"><i class="fas fa-check-circle"></i></div>
					<div class="alert-content">
						<strong>Success</strong>
						<p><?= htmlspecialchars($message) ?></p>
					</div>
				</div>
				<?php endif; ?>

                <div class="card" style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: none;">
					<div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
						<div>
							<h2 class="card-title">Waiting Seniors</h2>
							<p class="card-subtitle">Pending validation</p>
						</div>
						<div class="table-search">
							<span class="table-search-icon">🔍</span>
							<input type="text" id="searchInput" placeholder="Search seniors...">
						</div>
					</div>
					<div class="card-body">
						<div class="table-container table-scroll">
                            <table class="table waiting-seniors-table">
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
										<th>LIFE STATUS</th>
										<th>CATEGORY</th>
                                        <th>MISSING DOCUMENTS</th>
                                        <th>VALIDATION STATUS</th>
                                        <th>VALIDATED</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($seniors)): ?>
										<?php foreach ($seniors as $senior): ?>
										<?php
											$docStatus = [];
											$missingDocs = [];

											// In this system: 1 means MISSING, 0 means NOT missing
											$isBirthMissing = !empty($senior['waiting_birth_certificate']);
											$isMarriageMissing = !empty($senior['waiting_marriage_contract']);
											$isValidIdMissing = !empty($senior['waiting_valid_id']);

											$docStatus[] = ($isBirthMissing ? '❌ Missing: ' : '✔ Provided: ') . 'Birth Certificate';
											$docStatus[] = ($isMarriageMissing ? '❌ Missing: ' : '✔ Provided: ') . 'Marriage Contract';
											$docStatus[] = ($isValidIdMissing ? '❌ Missing: ' : '✔ Provided: ') . 'Valid ID';

											if ($isBirthMissing) $missingDocs[] = 'Birth Certificate';
											if ($isMarriageMissing) $missingDocs[] = 'Marriage Contract';
											if ($isValidIdMissing) $missingDocs[] = 'Valid ID';

											$missingDocsText = $missingDocs ? implode(', ', $missingDocs) : 'None';
											$docStatusText = implode(' • ', $docStatus);
											$seniorFullName = trim(implode(' ', array_filter([
												$senior['first_name'] ?? '',
												$senior['middle_name'] ?? '',
												$senior['last_name'] ?? '',
												$senior['ext_name'] ?? '',
											])));
										?>
										<tr class="waiting-row"
											data-senior-id="<?= (int)$senior['id'] ?>"
											data-senior-name="<?= htmlspecialchars($seniorFullName) ?>"
											data-missing-docs="<?= htmlspecialchars($missingDocsText, ENT_QUOTES) ?>"
											data-doc-status="<?= htmlspecialchars($docStatusText, ENT_QUOTES) ?>">
											<td><?= htmlspecialchars($senior['last_name']) ?></td>
											<td><?= htmlspecialchars($senior['first_name']) ?></td>
											<td><?= htmlspecialchars($senior['middle_name'] ?: '') ?></td>
											<td><?= isset($senior['ext_name']) ? htmlspecialchars($senior['ext_name']) : '' ?></td>
											<td><?= htmlspecialchars($senior['barangay']) ?></td>
											<td><?= (int)$senior['age'] ?></td>
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
												<span class="badge <?= $senior['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($senior['life_status']) ?></span>
											</td>
											<td><span class="badge badge-info">Waiting</span></td>
											<td><?= htmlspecialchars($docStatusText) ?></td>
                                            <td>
                                                <span class="badge badge-warning">Not Validated</span>
                                                <form method="post" style="display:inline; margin-left: 6px;">
                                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                                    <input type="hidden" name="op" value="validate_waiting">
                                                    <input type="hidden" name="id" value="<?= (int)$senior['id'] ?>">
                                                    <button type="submit" class="button small primary" style="vertical-align: middle;">
                                                        <i class="fas fa-check"></i> Validate
                                                    </button>
                                                </form>
                                            </td>
                                            <td>-</td>
										</tr>
										<?php endforeach; ?>
									<?php else: ?>
									<tr>
										<td colspan="20" style="text-align: center; padding: 2rem; color: var(--muted);">No waiting seniors found.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>

	<!-- Missing Documents Modal -->
	<div id="missingDocsModal" class="modal-overlay">
		<div class="modal" style="max-width: 480px;">
			<div class="modal-header">
				<h2 class="modal-title">Missing Required Documents</h2>
				<button id="closeMissingDocsModal" class="modal-close" aria-label="Close missing documents modal">&times;</button>
			</div>
			<div class="modal-body">
				<p id="missingDocsName" style="font-weight: 600; margin-bottom: 0.5rem;"></p>
				<ul id="missingDocsList" class="missing-docs-list"></ul>
				<p class="help-text" style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted);">
					These documents are required before the senior can be validated.
				</p>
			</div>
		</div>
	</div>

	<script src="<?= BASE_URL ?>/assets/app.js"></script>
	<script>
		// Search filter for waiting seniors table
		document.getElementById('searchInput').addEventListener('input', function() {
			const filter = this.value.toLowerCase();
			const rows = document.querySelectorAll('tbody tr.waiting-row');
			
			rows.forEach(row => {
				const text = row.textContent.toLowerCase();
				const isVisible = text.includes(filter);
				row.style.display = isVisible ? '' : 'none';
			});
		});

		// Missing documents modal behavior
		document.addEventListener('DOMContentLoaded', function() {
			const modal = document.getElementById('missingDocsModal');
			const nameEl = document.getElementById('missingDocsName');
			const listEl = document.getElementById('missingDocsList');
			const closeBtn = document.getElementById('closeMissingDocsModal');

			if (!modal || !nameEl || !listEl) return;

			function openMissingDocsModal(name, missingDocsText, docStatusText) {
				nameEl.textContent = name || 'Senior';
				listEl.innerHTML = '';

				const docs = (missingDocsText || '').split(',').map(d => d.trim()).filter(Boolean);

				const docStatusItems = (docStatusText || '').split('•').map(d => d.trim()).filter(Boolean);

				docStatusItems.forEach(item => {
					const li = document.createElement('li');
					li.textContent = item;
					listEl.appendChild(li);
				});

				if (!docStatusItems.length) {
					const li = document.createElement('li');
					li.textContent = 'No document info available.';
					listEl.appendChild(li);
				}

				if (!docs.length || missingDocsText === 'None') {
					const li = document.createElement('li');
					li.innerHTML = '<strong>No missing documents.</strong>';
					listEl.appendChild(li);
				}

				modal.classList.add('active');
				document.body.classList.add('modal-active');
				document.body.style.overflow = 'hidden';
			}

			function closeMissingDocsModal() {
				modal.classList.remove('active');
				document.body.classList.remove('modal-active');
				document.body.style.overflow = '';
			}

			document.querySelectorAll('.waiting-seniors-table tbody tr.waiting-row').forEach(row => {
				row.addEventListener('click', function(e) {
					// Ignore clicks on buttons or form controls (e.g., Validate button)
					if (e.target.closest('button, input, select, a')) {
						return;
					}
					const name = this.dataset.seniorName || '';
					const missingDocs = this.dataset.missingDocs || '';
					const docStatus = this.dataset.docStatus || '';
					openMissingDocsModal(name, missingDocs, docStatus);
				});
			});

			if (closeBtn) {
				closeBtn.addEventListener('click', function(e) {
					e.stopPropagation();
					closeMissingDocsModal();
				});
			}

			modal.addEventListener('click', function(e) {
				if (e.target === modal) {
					closeMissingDocsModal();
				}
			});

			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && modal.classList.contains('active')) {
					closeMissingDocsModal();
				}
			});
		});
	</script>
</body>
</html>


