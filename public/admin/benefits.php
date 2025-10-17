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
	$pdo->exec("CREATE TABLE IF NOT EXISTS benefit_records (
		id INT AUTO_INCREMENT PRIMARY KEY,
		senior_id INT NOT NULL,
		benefit_type VARCHAR(64) NOT NULL,
		received TINYINT(1) NOT NULL DEFAULT 0,
		remarks VARCHAR(255) NULL,
		updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY uniq_senior_type (senior_id, benefit_type),
		CONSTRAINT fk_benefit_records_senior FOREIGN KEY (senior_id) REFERENCES seniors(id) ON DELETE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci);");
} catch (Exception $e) {
	// swallow - page should still load; log for debugging
	error_log('Failed ensuring benefit_records table: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf_token($_POST['csrf'] ?? '')) {
		$message = 'Invalid session token';
	} else {
		$op = $_POST['op'] ?? '';
		if ($op === 'toggle_benefit') {
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
					echo json_encode(['success' => false, 'message' => $e->getMessage()]);
					exit;
    }
}
			echo json_encode(['success' => false, 'message' => 'Invalid payload']);
			exit;
		}
        // Old mark_benefits/bulk_mark logic removed in favor of per-benefit toggles
	}
}

$csrf = generate_csrf_token();

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
                                    <th colspan="5" style="text-align:center;">Other Benefits</th>
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
                                    <th>Financial Asst.</th>
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
                                        $types = ['sp_q1','sp_q2','sp_q3','sp_q4','octogenarian','nonagenarian','centenarian','financial_asst','burial_asst'];
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
	}).then(r => r.json()).then(resp => {
		if (!resp || !resp.success) {
			if (mark) { mark.classList.toggle('on', !received); }
			el.checked = !received;
			alert('Failed to save. Please try again.');
		}
	}).catch(()=>{
		if (mark) { mark.classList.toggle('on', !received); }
		el.checked = !received;
		alert('Network error. Please try again.');
	});
});
</script>
</body>
</html>
