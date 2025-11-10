<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

// Ensure benefit_records table exists for per-period/benefit tracking
try {
	// Check if table exists first
	$tableExists = $pdo->query("SHOW TABLES LIKE 'benefit_records'")->rowCount() > 0;
	
	if (!$tableExists) {
		// Create table without foreign key constraint first (in case seniors table doesn't exist yet or has issues)
		$pdo->exec("CREATE TABLE IF NOT EXISTS benefit_records (
			id INT AUTO_INCREMENT PRIMARY KEY,
			senior_id INT NOT NULL,
			benefit_type VARCHAR(64) NOT NULL,
			received TINYINT(1) NOT NULL DEFAULT 0,
			remarks VARCHAR(255) NULL,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY uniq_senior_type (senior_id, benefit_type),
			INDEX idx_senior_id (senior_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		
		// Try to add foreign key constraint if seniors table exists
		try {
			$seniorsTableExists = $pdo->query("SHOW TABLES LIKE 'seniors'")->rowCount() > 0;
			if ($seniorsTableExists) {
				// Check if foreign key already exists
				$fkExists = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
					WHERE CONSTRAINT_SCHEMA = DATABASE() 
					AND TABLE_NAME = 'benefit_records' 
					AND CONSTRAINT_NAME = 'fk_benefit_records_senior'")->fetchColumn();
				
				if (!$fkExists) {
					$pdo->exec("ALTER TABLE benefit_records 
						ADD CONSTRAINT fk_benefit_records_senior 
						FOREIGN KEY (senior_id) REFERENCES seniors(id) ON DELETE CASCADE");
				}
			}
		} catch (Exception $fkError) {
			// Foreign key constraint failed, but table exists - log and continue
			error_log('Failed to add foreign key constraint: ' . $fkError->getMessage());
		}
	}
} catch (Exception $e) {
	// Log error but don't stop page load
	error_log('Failed ensuring benefit_records table: ' . $e->getMessage());
	// Try to create table without foreign key as fallback
	try {
		$pdo->exec("CREATE TABLE IF NOT EXISTS benefit_records (
			id INT AUTO_INCREMENT PRIMARY KEY,
			senior_id INT NOT NULL,
			benefit_type VARCHAR(64) NOT NULL,
			received TINYINT(1) NOT NULL DEFAULT 0,
			remarks VARCHAR(255) NULL,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY uniq_senior_type (senior_id, benefit_type)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	} catch (Exception $e2) {
		error_log('Failed to create benefit_records table (fallback): ' . $e2->getMessage());
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json');
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		echo json_encode(['success' => false, 'message' => 'Invalid session token']);
		exit;
	}
	$op = $_POST['op'] ?? '';
	if ($op === 'toggle_benefit') {
		// Ensure table exists before trying to insert
		try {
			$tableExists = $pdo->query("SHOW TABLES LIKE 'benefit_records'")->rowCount() > 0;
			if (!$tableExists) {
				$pdo->exec("CREATE TABLE IF NOT EXISTS benefit_records (
					id INT AUTO_INCREMENT PRIMARY KEY,
					senior_id INT NOT NULL,
					benefit_type VARCHAR(64) NOT NULL,
					received TINYINT(1) NOT NULL DEFAULT 0,
					remarks VARCHAR(255) NULL,
					updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					UNIQUE KEY uniq_senior_type (senior_id, benefit_type),
					INDEX idx_senior_id (senior_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			}
		} catch (Exception $tableError) {
			error_log('Failed to ensure benefit_records table in POST: ' . $tableError->getMessage());
		}
		
		$seniorId = (int)($_POST['senior_id'] ?? 0);
		$type = trim($_POST['benefit_type'] ?? '');
		$received = (int)($_POST['received'] ?? 0) ? 1 : 0;
		$remarks = trim($_POST['remarks'] ?? '');
		if ($seniorId && $type !== '') {
			try {
				$pdo = get_db_connection();
				$stmt = $pdo->prepare('INSERT INTO benefit_records (senior_id, benefit_type, received, remarks) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE received=VALUES(received), remarks=VALUES(remarks)');
				$stmt->execute([$seniorId, $type, $received, $remarks !== '' ? $remarks : null]);
				echo json_encode(['success' => true]);
				exit;
			} catch (Exception $e) {
				error_log('Benefit toggle error: ' . $e->getMessage());
				echo json_encode(['success' => false, 'message' => $e->getMessage()]);
				exit;
			}
		}
		echo json_encode(['success' => false, 'message' => 'Invalid payload: senior_id or benefit_type missing']);
		exit;
	}
	echo json_encode(['success' => false, 'message' => 'Invalid operation']);
	exit;
}

$csrf = generate_csrf_token();

// Load existing benefit records for all seniors
$benefitMap = [];
try {
	$benefitStmt = $pdo->query("SELECT senior_id, benefit_type, received FROM benefit_records");
	foreach ($benefitStmt->fetchAll() as $rec) {
		$sid = (int)$rec['senior_id'];
		$type = $rec['benefit_type'];
		if (!isset($benefitMap[$sid])) {
			$benefitMap[$sid] = [];
		}
		$benefitMap[$sid][$type] = ['received' => (int)$rec['received']];
	}
} catch (Exception $e) {
	error_log('Failed to load benefit records: ' . $e->getMessage());
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Benefits Management | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Benefits Management</h1>
			<p class="content-subtitle">Mark seniors as having received their benefits</p>
		</header>
		
		<div class="content-body">
            <!-- New Responsive Benefit Management Table -->
            <div class="card" style="margin-bottom: 1.25rem;">
                <div class="card-header">
                    <h2 class="card-title">Benefit Management</h2>
                    <p class="card-subtitle">Mark seniors as having received their benefits</p>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-container table-scroll">
                        <table class="table benefits-wide">
                            <thead>
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Barangay</th>
                                    <th>Category</th>
                                    <th colspan="4" style="text-align:center;">Social Pension</th>
                                    <th colspan="4" style="text-align:center;">Other Benefits</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>(Local/National)</th>
                                    <th>Jan–Mar</th>
                                    <th>Apr–Jun</th>
                                    <th>Jul–Sep</th>
                                    <th>Oct–Dec</th>
                                    <th>Octogenarian</th>
                                    <th>Nonagenarian</th>
                                    <th>Centenarian</th>
                                    <th>Burial Asst.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rows = $pdo->query("SELECT id, osca_id_no, first_name, middle_name, last_name, sex AS gender, age, barangay, category FROM seniors WHERE life_status='living' ORDER BY barangay, last_name, first_name")->fetchAll();
                                foreach ($rows as $row):
                                    $name = trim(($row['last_name'] ?: '') . ', ' . ($row['first_name'] ?: '') . ' ' . ($row['middle_name'] ?: ''));
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['osca_id_no'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($name) ?></td>
                                    <td><?= (int)($row['age'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($row['gender'] === 'male' ? 'Male' : ($row['gender'] === 'female' ? 'Female' : '')) ?></td>
                                    <td><?= htmlspecialchars($row['barangay'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['category'] === 'local' ? 'Local' : 'National') ?></td>
                                    <?php
                                        $types = ['sp_q1','sp_q2','sp_q3','sp_q4','octogenarian','nonagenarian','centenarian','burial_asst'];
                                        $sid = (int)$row['id'];
                                        $current = [];
                                        // Use preloaded map if available, else empty state
                                        if (isset($benefitMap[$sid])) { $current = $benefitMap[$sid]; }
                                    ?>
                                    <?php foreach ($types as $t): $on = !empty($current[$t]['received']); ?>
                                        <td>
                                            <label style="display:flex; align-items:center; gap:.35rem;">
                                                <input type="checkbox" class="benefit-toggle" data-senior-id="<?= (int)$row['id'] ?>" data-type="<?= $t ?>" <?= $on ? 'checked' : '' ?>>
                                                <span class="benefit-mark <?= $on ? 'on' : '' ?>"></span>
                                            </label>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Old summary cards and lists removed -->
            </div>
		</div>
	</main>
	
	<script>
		function toggleAllPending(checkbox) {
			const checkboxes = document.querySelectorAll('.pending-checkbox');
			checkboxes.forEach(cb => cb.checked = checkbox.checked);
		}
		
		// Update select all checkbox when individual checkboxes change
		document.querySelectorAll('.pending-checkbox').forEach(checkbox => {
			checkbox.addEventListener('change', function() {
				const allCheckboxes = document.querySelectorAll('.pending-checkbox');
				const checkedCheckboxes = document.querySelectorAll('.pending-checkbox:checked');
				const selectAllCheckbox = document.getElementById('select-all-pending');
				
				selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
				selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
			});
		});
	</script>
	<script src="<?= BASE_URL ?>/assets/app.js"></script>
<script>
// Auto-save benefit toggles
document.addEventListener('change', function(e){
	const el = e.target;
	if (!el.classList || !el.classList.contains('benefit-toggle')) return;
	const seniorId = el.getAttribute('data-senior-id');
	const type = el.getAttribute('data-type');
	const received = el.checked ? 1 : 0;
	const mark = el.parentElement.querySelector('.benefit-mark');
	if (mark) { mark.classList.toggle('on', !!received); }
	fetch('', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams({
			op: 'toggle_benefit',
			senior_id: seniorId,
			benefit_type: type,
			received: received,
			csrf: '<?= $csrf ?>'
		}).toString()
	}).then(r => {
		if (!r.ok) {
			throw new Error('HTTP error: ' + r.status);
		}
		return r.json();
	}).then(resp => {
		if (!resp || !resp.success) {
			if (mark) { mark.classList.toggle('on', !received); }
			el.checked = !received;
			alert('Failed to save: ' + (resp?.message || 'Please try again.'));
		}
	}).catch(err => {
		console.error('Error saving benefit:', err);
		if (mark) { mark.classList.toggle('on', !received); }
		el.checked = !received;
		alert('Error saving: ' + (err.message || 'Please try again.'));
	});
});
</script>
</body>
</html>
