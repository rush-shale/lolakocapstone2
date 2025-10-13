<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

// Handle AJAX requests for getting senior data
if (isset($_GET['action']) && $_GET['action'] === 'get_senior') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid senior ID']);
        exit;
    }
    
    $stmt = $pdo->prepare('SELECT * FROM seniors WHERE id = ?');
    $stmt->execute([$id]);
    $senior = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$senior) {
        echo json_encode(['success' => false, 'message' => 'Senior not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'senior' => $senior]);
    exit;
}

$message = '';

// Success message is now set directly in the POST processing

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
		$other_skills = trim($_POST['other_skills'] ?? '') ?: '';
		$barangay = trim($_POST['barangay'] ?? '') ?: '';
		$contact = trim($_POST['contact'] ?? '') ?: '';
		$osca_id_no = trim($_POST['osca_id_no'] ?? '') ?: '';
		$remarks = trim($_POST['remarks'] ?? '') ?: '';
		$health_condition = trim($_POST['health_condition'] ?? '') ?: '';
		$purok = trim($_POST['purok'] ?? '') ?: '';
		$cellphone = trim($_POST['cellphone'] ?? '') ?: '';
		$benefits_received = isset($_POST['benefits_received']) ? 1 : 0;
        $life_status = ($_POST['life_status'] ?? '') === 'deceased' ? 'deceased' : 'living';
        // Read category strictly; don't default to local if not chosen
        $category_input = $_POST['category'] ?? '';
        if ($category_input === 'local') {
            $category = 'local';
        } elseif ($category_input === 'national') {
            $category = 'national';
        } else {
            $category = '';
        }

		// Check if waiting list checkbox is set
		if (isset($_POST['waiting_list']) && $_POST['waiting_list'] === '1') {
			$category = 'waiting';
		}

        // Set validation status and date based on category
        $validation_status = $category === 'waiting' ? 'Not Validated' : 'Validated';
        $validation_date = $category === 'waiting' ? null : date('Y-m-d H:i:s');

        // Require either a concrete category (local/national) or On Waiting List
        if ($category === '') {
            $message = 'Please select a category (Local or National) or mark On Waiting List.';
        } elseif ($first_name && $last_name && $age && $barangay && $osca_id_no) {
			try {
				// Ensure we have a fresh connection
				$pdo = get_db_connection();
				$pdo->beginTransaction();

				if ($op === 'create') {
					$stmt = $pdo->prepare('INSERT INTO seniors (first_name, middle_name, last_name, ext_name, age, date_of_birth, sex, place_of_birth, civil_status, educational_attainment, occupation, annual_income, other_skills, barangay, contact, osca_id_no, remarks, health_condition, purok, cellphone, benefits_received, life_status, category, validation_status, validation_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $ext_name ?: null, $age,
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: '', $educational_attainment ?: '',
						$occupation ?: null, $annual_income, $other_skills,
						$barangay, $contact, $osca_id_no, $remarks,
						$health_condition, $purok, $cellphone,
						$benefits_received, $life_status, $category, $validation_status, $validation_date
					]);
					$senior_id = $pdo->lastInsertId();
					$message = 'Senior added successfully';
				} else {
					// If the existing record is still in 'waiting', prevent changing category via generic update.
					// Only the validate_waiting operation should move a senior out of waiting.
					$existingCategory = null;
					try {
						$checkStmt = $pdo->prepare('SELECT category FROM seniors WHERE id = ?');
						$checkStmt->execute([$id]);
						$existingCategory = $checkStmt->fetchColumn();
					} catch (Exception $ignore) {}
					if ($existingCategory === 'waiting') {
						$category = 'waiting';
						$validation_status = 'Not Validated';
						$validation_date = null;
					}
					$stmt = $pdo->prepare('UPDATE seniors SET first_name=?, middle_name=?, last_name=?, ext_name=?, age=?, date_of_birth=?, sex=?, place_of_birth=?, civil_status=?, educational_attainment=?, occupation=?, annual_income=?, other_skills=?, barangay=?, contact=?, osca_id_no=?, remarks=?, health_condition=?, purok=?, cellphone=?, benefits_received=?, life_status=?, category=?, validation_status=?, validation_date=? WHERE id=?');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $ext_name ?: null, $age,
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: '', $educational_attainment ?: '',
						$occupation ?: null, $annual_income, $other_skills,
						$barangay, $contact, $osca_id_no, $remarks,
						$health_condition, $purok, $cellphone,
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
                
                // After write, force a full reload so the table reflects changes immediately
        if ($op === 'create') {
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                    exit;
                }
                if ($op === 'update') {
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                    exit;
                }
			} catch (Exception $e) {
				// Use safe rollback to handle connection issues
				safe_rollback($pdo);
				error_log("Senior operation failed: " . $e->getMessage());
				$message = 'Error: ' . $e->getMessage();
			}
		}
	}

		// Handle validation of waiting seniors
		if ($op === 'validate_waiting') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				try {
					$pdo = get_db_connection();
					$stmt = $pdo->prepare('UPDATE seniors SET category = ?, validation_status = ?, validation_date = NOW() WHERE id = ?');
					$stmt->execute(['local', 'Validated', $id]);
					$message = 'Senior validated successfully';
				} catch (Exception $e) {
					error_log("Validation failed: " . $e->getMessage());
					$message = 'Error validating senior: ' . $e->getMessage();
				}
			}
		}
		if ($op === 'toggle_benefits') {
			$id = (int)($_POST['id'] ?? 0);
			$to = isset($_POST['to']) && (int)$_POST['to'] === 1 ? 1 : 0;
			if ($id) {
				try {
					$pdo = get_db_connection();
					$stmt = $pdo->prepare('UPDATE seniors SET benefits_received=? WHERE id=?');
					$stmt->execute([$to, $id]);
					$message = 'Benefits status updated';
				} catch (Exception $e) {
					error_log("Benefits toggle failed: " . $e->getMessage());
					$message = 'Error updating benefits status: ' . $e->getMessage();
				}
			}
		}
		if ($op === 'toggle_life') {
			$id = (int)($_POST['id'] ?? 0);
			$to = $_POST['to'] === 'deceased' ? 'deceased' : 'living';
			if ($id) {
				try {
					$pdo = get_db_connection();
					$stmt = $pdo->prepare('UPDATE seniors SET life_status=? WHERE id=?');
					$stmt->execute([$to, $id]);
					$message = 'Life status updated';
				} catch (Exception $e) {
					error_log("Life status toggle failed: " . $e->getMessage());
					$message = 'Error updating life status: ' . $e->getMessage();
				}
			}
		}
		if ($op === 'mark_deceased') {
			$id = (int)($_POST['id'] ?? 0);
			$death_date = $_POST['death_date'] ?? '';
			$death_time = $_POST['death_time'] ?? '';
			$death_place = trim($_POST['death_place'] ?? '');
			$death_cause = trim($_POST['death_cause'] ?? '');
			
			if ($id && $death_date && $death_place && $death_cause) {
				try {
					$pdo = get_db_connection();
					
					// Create senior_deaths table if it doesn't exist
					$pdo->exec("CREATE TABLE IF NOT EXISTS senior_deaths (
						id INT AUTO_INCREMENT PRIMARY KEY,
						senior_id INT NOT NULL,
						date_of_death DATE NULL,
						time_of_death TIME NULL,
						place_of_death VARCHAR(255) NULL,
						cause_of_death VARCHAR(255) NULL,
						created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						CONSTRAINT fk_senior_deaths_senior FOREIGN KEY (senior_id) REFERENCES seniors(id) ON DELETE CASCADE
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
					
					// Update life status to deceased
					$stmt = $pdo->prepare('UPDATE seniors SET life_status = ? WHERE id = ?');
					$stmt->execute(['deceased', $id]);
					
					// Insert death information into senior_deaths table
					$stmt = $pdo->prepare('INSERT INTO senior_deaths (senior_id, date_of_death, time_of_death, place_of_death, cause_of_death) VALUES (?, ?, ?, ?, ?)');
					$stmt->execute([$id, $death_date, $death_time ?: null, $death_place, $death_cause]);
					
					// Remove any existing death information from remarks
					$stmt = $pdo->prepare('SELECT remarks FROM seniors WHERE id = ?');
					$stmt->execute([$id]);
					$current_remarks = $stmt->fetchColumn();
					
					if ($current_remarks) {
						// Remove death information section from remarks
						$clean_remarks = preg_replace('/\n\n--- DEATH INFORMATION ---.*$/s', '', $current_remarks);
						$clean_remarks = trim($clean_remarks);
						
						$stmt = $pdo->prepare('UPDATE seniors SET remarks = ? WHERE id = ?');
						$stmt->execute([$clean_remarks, $id]);
					}
					
					$message = 'Senior marked as deceased successfully.';
				} catch (Exception $e) {
					error_log("Mark deceased failed: " . $e->getMessage());
					$message = 'Error marking as deceased: ' . $e->getMessage();
				}
			} else {
				$message = 'Please fill in all required death information fields.';
			}
		}
		if ($op === 'transfer_details') {
			$id = (int)($_POST['id'] ?? 0);
			$transfer_reason = $_POST['transfer_reason'] ?? '';
			$transfer_reason_other = trim($_POST['transfer_reason_other'] ?? '');
			$new_address = trim($_POST['new_address'] ?? '');
			$effective_date = $_POST['effective_date'] ?? '';
			
			// Validate required fields
			$valid = $id && $transfer_reason && $new_address && $effective_date;
			
			// If reason is 'other', validate that other reason is provided
			if ($transfer_reason === 'other' && empty($transfer_reason_other)) {
				$valid = false;
			}
			
			if ($valid) {
				try {
					$pdo = get_db_connection();
					
					// Create senior_transfers table if it doesn't exist
					$pdo->exec("CREATE TABLE IF NOT EXISTS senior_transfers (
						id INT AUTO_INCREMENT PRIMARY KEY,
						senior_id INT NOT NULL,
						senior_name VARCHAR(255) NULL,
						transfer_reason VARCHAR(255) NOT NULL,
						new_address VARCHAR(255) NOT NULL,
						effective_date DATE NOT NULL,
						created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						CONSTRAINT fk_senior_transfers_senior FOREIGN KEY (senior_id) REFERENCES seniors(id) ON DELETE CASCADE
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

					// Ensure senior_name column exists (for older tables)
					try { $pdo->exec("ALTER TABLE senior_transfers ADD COLUMN senior_name VARCHAR(255) NULL"); } catch (Exception $ignore) {}
					
					// Update senior category to transferred
					$stmt = $pdo->prepare('UPDATE seniors SET category = ? WHERE id = ?');
					$result1 = $stmt->execute(['transferred', $id]);
					error_log("Transfer debug - Update category result: " . ($result1 ? 'success' : 'failed'));
					
					// Do NOT overwrite original barangay; store new address only in senior_transfers
					$result2 = true;

					// Clean any previously appended transfer notes from remarks
					try {
						$get = $pdo->prepare('SELECT remarks FROM seniors WHERE id = ?');
						$get->execute([$id]);
						$cur = (string)$get->fetchColumn();
						if ($cur !== '') {
							$clean = preg_replace('/(\r?\n)?Transfer Details\s*-.*$|(\r?\n)?---\s*TRANSFER INFORMATION\s*---[\s\S]*$/ims', '', $cur);
							if ($clean !== $cur) {
								$upd = $pdo->prepare('UPDATE seniors SET remarks = ? WHERE id = ?');
								$upd->execute([trim($clean), $id]);
							}
						}
					} catch (Exception $ignore) {}
					error_log("Transfer debug - Update barangay result: " . ($result2 ? 'success' : 'failed'));
					
					// Fetch senior's current name for snapshot
					$seniorName = '';
					try {
						$nm = $pdo->prepare('SELECT first_name, middle_name, last_name, ext_name FROM seniors WHERE id = ?');
						$nm->execute([$id]);
						$row = $nm->fetch(PDO::FETCH_ASSOC);
						if ($row) {
							$parts = array_filter([
								$row['first_name'] ?? '',
								$row['middle_name'] ?? '',
								$row['last_name'] ?? '',
								$row['ext_name'] ?? ''
							]);
							$seniorName = trim(implode(' ', $parts));
						}
					} catch (Exception $ignore) {}

					// Store transfer details in senior_transfers table
					$final_reason = $transfer_reason === 'other' ? $transfer_reason_other : ucfirst(str_replace('_', ' ', $transfer_reason));
					$stmt = $pdo->prepare('INSERT INTO senior_transfers (senior_id, senior_name, transfer_reason, new_address, effective_date) VALUES (?, ?, ?, ?, ?)');
					$result3 = $stmt->execute([$id, $seniorName ?: null, $final_reason, $new_address, $effective_date]);
					error_log("Transfer debug - Insert transfer record result: " . ($result3 ? 'success' : 'failed'));
					
					// Debug: Check if the update was successful
					$checkStmt = $pdo->prepare('SELECT category FROM seniors WHERE id = ?');
					$checkStmt->execute([$id]);
					$result = $checkStmt->fetchColumn();
					error_log("Transfer debug - Senior ID: $id, Category after update: $result");
					
					// Set success message
					$message = 'Senior has been successfully transferred!';
					error_log("Transfer completed successfully for senior ID: $id");
					
					// Redirect to transferred seniors page with success message
					header("Location: transferred_seniors.php?transfer_success=1");
					exit;
				} catch (Exception $e) {
					error_log("Transfer failed: " . $e->getMessage());
					error_log("Transfer error details: " . print_r($e, true));
					$message = 'Error processing transfer: ' . $e->getMessage();
				}
			} else {
				$message = 'Please fill in all required transfer information fields.';
				error_log("Transfer validation failed - ID: $id, Reason: $transfer_reason, Address: $new_address, Date: $effective_date");
			}
		}
		if ($op === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id) {
				try {
					$pdo = get_db_connection();
					$stmt = $pdo->prepare('DELETE FROM seniors WHERE id=?');
					$stmt->execute([$id]);
					$message = 'Senior deleted successfully';
				} catch (Exception $e) {
					error_log("Delete failed: " . $e->getMessage());
					$message = 'Error deleting senior: ' . $e->getMessage();
				}
			}
		}
		if ($op === 'transfer') {
			$id = (int)($_POST['id'] ?? 0);
			$to = $_POST['to'] === 'national' ? 'national' : 'local';
			if ($id) {
				try {
					$pdo = get_db_connection();
					$stmt = $pdo->prepare("UPDATE seniors SET category=? WHERE id=?");
					$stmt->execute([$to,$id]);
					$message = 'Transfer updated';
				} catch (Exception $e) {
					error_log("Transfer failed: " . $e->getMessage());
					$message = 'Error updating transfer: ' . $e->getMessage();
				}
			}
		}
	}
}

$csrf = generate_csrf_token();
try {
	$pdo = get_db_connection();
	$barangays = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll();
} catch (Exception $e) {
	error_log("Failed to load barangays: " . $e->getMessage());
	$barangays = [];
}

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
try {
	$pdo = get_db_connection();
	
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
		// Transferred seniors: moved out; use explicit transferred category
		$sql = 'SELECT s.*, validation_status, validation_date, 0 as event_count, "" as events_attended
				FROM seniors s
				WHERE s.life_status = "living" AND s.category = "transferred"
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
} catch (Exception $e) {
	error_log("Failed to load seniors: " . $e->getMessage());
	$seniors = [];
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
// Important: break the reference created by foreach to avoid accidental cross-group aliasing
unset($seniors_in_barangay);

try {
	$pdo = get_db_connection();
	$livingCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living'")->fetchColumn();
	$deceasedCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='deceased'")->fetchColumn();
	$waitingCount = (int)$pdo->query("SELECT COUNT(*) FROM seniors WHERE life_status='living' AND category='waiting'")->fetchColumn();
} catch (Exception $e) {
	error_log("Failed to load counts: " . $e->getMessage());
	$livingCount = 0;
	$deceasedCount = 0;
	$waitingCount = 0;
}

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
			backdrop-filter: blur(5px);
			-webkit-backdrop-filter: blur(5px);
			z-index: 1000;
			display: none;
			opacity: 0;
			transition: opacity 0.3s ease;
		}

		.modal-overlay.active {
			display: flex;
			align-items: center;
			justify-content: center;
			opacity: 1;
			animation: fadeInBlur 0.3s ease forwards;
		}

		.modal-overlay .modal {
			transform: scale(0.7);
			animation: zoomInModal 0.3s ease forwards;
		}

		@keyframes fadeInBlur {
			from { 
				opacity: 0;
				backdrop-filter: blur(0px);
				-\webkit-backdrop-filter: blur(0px);
			}
			to { 
				opacity: 1;
				backdrop-filter: blur(5px);
				-\webkit-backdrop-filter: blur(5px);
			}
		}

		@keyframes zoomInModal {
			from {
				transform: scale(0.7);
				opacity: 0;
			}
			to {
				transform: scale(1);
				opacity: 1;
			}
		}

		/* Senior Profile Styles for Modal */
		.senior-profile {
			font-family: 'Inter', sans-serif;
			max-width: 480px;
			margin: 0 auto;
			padding: 1rem;
			background: #fff;
			border-radius: 12px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
			color: #1f2937;
			font-size: 0.875rem;
			line-height: 1.4;
		}
		.profile-section {
			margin-bottom: 1rem;
		}
		.profile-header {
			display: flex;
			align-items: center;
			gap: 1rem;
			margin-bottom: 1rem;
		}
		.profile-avatar {
			font-size: 3rem;
			color: #6b7280;
		}
		.profile-info h2 {
			margin: 0 0 0.25rem 0;
			font-size: 1.25rem;
			font-weight: 700;
			color: #111827;
		}
		.profile-middle {
			margin: 0 0 0.5rem 0;
			font-size: 0.875rem;
			color: #6b7280;
		}
		.profile-badges {
			display: flex;
			gap: 0.5rem;
			flex-wrap: wrap;
		}
		.badge {
			padding: 0.25rem 0.5rem;
			border-radius: 4px;
			font-size: 0.75rem;
			font-weight: 500;
		}
		.badge-success {
			background-color: #d1fae5;
			color: #065f46;
		}
		.badge-danger {
			background-color: #fee2e2;
			color: #991b1b;
		}
		.badge-primary {
			background-color: #dbeafe;
			color: #1e40af;
		}
		.badge-info {
			background-color: #dbeafe;
			color: #1e40af;
		}
		.badge-warning {
			background-color: #fef3c7;
			color: #92400e;
		}
		.profile-actions {
			margin-left: auto;
		}
		.button {
			font-size: 0.875rem;
			padding: 0.375rem 0.75rem;
			border-radius: 6px;
			border: none;
			cursor: pointer;
			display: flex;
			align-items: center;
			gap: 0.5rem;
			text-decoration: none;
			pointer-events: auto !important;
			z-index: 10 !important;
			position: relative !important;
		}
		.button.primary {
			background-color: #2563eb;
			color: white;
		}
		.button.primary:hover {
			background-color: #1d4ed8;
		}
		.profile-stats {
			display: flex;
			gap: 1rem;
			flex-wrap: wrap;
			justify-content: space-between;
			margin-bottom: 1.5rem;
		}
		.stat-card {
			flex: 1;
			min-width: 120px;
			background: #f9fafb;
			border-radius: 8px;
			padding: 1rem;
			display: flex;
			align-items: center;
			gap: 0.75rem;
		}
		.stat-icon {
			font-size: 1.5rem;
			color: #2563eb;
		}
		.stat-content h3 {
			margin: 0 0 0.25rem 0;
			font-size: 0.75rem;
			font-weight: 500;
			color: #6b7280;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.stat-content .number {
			margin: 0;
			font-size: 1.125rem;
			font-weight: 700;
			color: #111827;
		}
		.stat-content .text {
			margin: 0;
			font-size: 0.875rem;
			font-weight: 500;
			color: #374151;
		}
		.profile-details {
			margin-bottom: 1.5rem;
		}
		.detail-section {
			margin-bottom: 1.5rem;
		}
		.detail-section h3 {
			margin: 0 0 1rem 0;
			font-size: 1rem;
			font-weight: 600;
			color: #111827;
			display: flex;
			align-items: center;
			gap: 0.5rem;
		}
		.detail-section h3 i {
			color: #2563eb;
		}
		.detail-grid {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0.75rem;
		}
		.detail-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0.5rem 0;
			border-bottom: 1px solid #f3f4f6;
		}
		.detail-item:last-child {
			border-bottom: none;
		}
		.detail-item .label {
			font-weight: 500;
			color: #6b7280;
			flex: 1;
		}
		.detail-item .value {
			font-weight: 400;
			color: #111827;
			text-align: right;
			flex: 1;
		}
		.remarks-content {
			background: #f9fafb;
			border-radius: 6px;
			padding: 1rem;
		}
		.remarks-content p {
			margin: 0;
			line-height: 1.5;
		}
		.attendance-list {
			display: flex;
			flex-direction: column;
			gap: 0.75rem;
		}
		.attendance-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0.75rem;
			background: #f9fafb;
			border-radius: 6px;
		}
		.event-info h4 {
			margin: 0 0 0.25rem 0;
			font-size: 0.875rem;
			font-weight: 600;
			color: #111827;
		}
		.event-date {
			margin: 0;
			font-size: 0.75rem;
			color: #6b7280;
		}
		.status-badge {
			padding: 0.25rem 0.5rem;
			border-radius: 4px;
			font-size: 0.75rem;
			font-weight: 500;
			display: flex;
			align-items: center;
			gap: 0.25rem;
		}
		.status-badge.attended {
			background-color: #d1fae5;
			color: #065f46;
		}
		.status-badge.not-attended {
			background-color: #fee2e2;
			color: #991b1b;
		}
		.no-attendance {
			text-align: center;
			padding: 2rem;
			color: #6b7280;
		}
		.no-attendance-icon {
			font-size: 2rem;
			margin-bottom: 0.5rem;
			color: #2563eb;
		}
		.no-attendance h4 {
			margin: 0.5rem 0;
			font-size: 1rem;
			font-weight: 600;
			color: #111827;
		}
		.no-attendance p {
			margin: 0;
			font-size: 0.875rem;
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
        <?php
        // Normalize success message based on query param from mark_deceased
        if (isset($_GET['success']) && $_GET['success'] === 'deceased_marked') {
            $message = 'Marked as deceased successfully';
        }
        ?>
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
										<th>LIFE STATUS</th>
										<th>CATEGORY</th>
										<th>VALIDATION STATUS</th>
										<th>VALIDATED</th>
										<th>ACTIONS</th>
									</tr>
								</thead>
								<tbody id="seniorsTableBody">
									<?php if (!empty($grouped)): ?>
									<?php foreach ($grouped as $barangay => $seniors_in_barangay): ?>
									<tr class="barangay-header"><td colspan="20" style="background: #f9fafb; font-weight: bold; padding: 1rem;">Barangay <?= htmlspecialchars($barangay) ?></td></tr>
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
											<span class="badge <?= $senior['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>">
												<?= ucfirst($senior['life_status']) ?>
											</span>
										</td>
										<td>
											<span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>">
												<?= ucfirst($senior['category']) ?>
											</span>
										</td>
										<td>
							<span class="badge <?= $senior['validation_status'] === 'Validated' ? 'badge-success' : 'badge-warning' ?>">
								<?= $senior['validation_status'] ?>
							</span>
							<?php if (($senior['validation_status'] ?? '') === 'Validated' && !empty($senior['validation_date'])): ?>
								<br><small style="color: #6b7280;"><?= date('M d, Y H:i', strtotime($senior['validation_date'])) ?></small>
							<?php endif; ?>
										</td>
										<td>
											<?= $senior['validation_date'] ? date('M d, Y H:i', strtotime($senior['validation_date'])) : '-' ?>
										</td>
										<td>
											<div class="action-buttons">
								<?php if (($senior['category'] ?? '') === 'waiting'): ?>
									<form method="post" style="display:inline" onsubmit="event.stopPropagation();">
										<input type="hidden" name="csrf" value="<?= $csrf ?>">
										<input type="hidden" name="op" value="validate_waiting">
										<input type="hidden" name="id" value="<?= (int)$senior['id'] ?>">
										<button type="submit" class="button small primary" title="Validate Senior">
											<i class="fas fa-check"></i>
										</button>
									</form>
								<?php endif; ?>
												<button class="button small primary" onclick="event.stopPropagation(); editSenior(<?= $senior['id'] ?>)" title="Edit Senior">
													<i class="fas fa-edit"></i>
												</button>
												<button class="button small secondary" onclick="event.stopPropagation(); viewSeniorDetails(<?= $senior['id'] ?>)" title="View Details">
													<i class="fas fa-eye"></i>
												</button>
												<button class="button small danger" onclick="event.stopPropagation(); deleteSenior(<?= $senior['id'] ?>, '<?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?>')" title="Delete Senior">
													<i class="fas fa-trash"></i>
												</button>
											</div>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php endforeach; ?>
									<?php else: ?>
									<tr class="no-data">
										<td colspan="20" style="text-align: center; padding: 2rem; color: var(--gov-text-muted);">
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
				<button class="modal-close" onclick="closeDeleteModal()" aria-label="Close delete confirmation">&times;</button>
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
				<button class="modal-close" onclick="closeSeniorDetailsModal()" aria-label="Close senior details">&times;</button>
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
			// Show the modal with loading state
			const modal = document.getElementById('seniorDetailsModal');
			const content = document.getElementById('seniorDetailsContent');
			
			// Show loading state
			content.innerHTML = `
				<div class="loading-state">
					<div class="loading-spinner"></div>
					<p>Loading senior details...</p>
				</div>
			`;
			
			// Show modal with blur and zoom animation
			modal.classList.add('active');
			document.body.style.overflow = 'hidden';
			
			// Load senior details via AJAX
			fetch(`senior_details.php?id=${id}&ajax=1`)
				.then(response => response.text())
				.then(html => {
					// Extract only the senior profile content from the response
					const parser = new DOMParser();
					const doc = parser.parseFromString(html, 'text/html');
					const seniorProfile = doc.querySelector('.senior-profile');
					
					if (seniorProfile) {
						content.innerHTML = seniorProfile.outerHTML;
						
						// Add event listener to Edit Profile button
						const editButton = content.querySelector('.button.primary');
						if (editButton) {
							editButton.addEventListener('click', function(e) {
								e.preventDefault();
								// Close senior details modal first
								closeSeniorDetailsModal();
								// Then open edit modal
								openEditSeniorModal(id);
							});
						}
					} else {
						content.innerHTML = '<p>Error loading senior details.</p>';
					}
				})
				.catch(error => {
					console.error('Error loading senior details:', error);
					content.innerHTML = '<p>Error loading senior details. Please try again.</p>';
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
			document.getElementById('editSeniorModal').classList.remove('active');
			document.body.style.overflow = '';
		}

		function markAsDeceased() {
			const seniorId = document.getElementById('editSeniorId').value;
			if (!seniorId) {
				alert('No senior selected');
				return;
			}
			
			// Close edit modal first
			closeEditSeniorModal();
			
			// Open deceased modal
			openDeceasedModal(seniorId);
		}

		function transferSenior() {
			const seniorId = document.getElementById('editSeniorId').value;
			if (!seniorId) {
				alert('No senior selected');
				return;
			}
			
			// Close edit modal first
			closeEditSeniorModal();
			
			// Open transfer modal
			openTransferModal(seniorId);
		}

		function openTransferModal(seniorId) {
			// Set the senior ID
			document.getElementById('transferSeniorId').value = seniorId;
			
			// Set default effective date to today
			const today = new Date().toISOString().split('T')[0];
			document.getElementById('effectiveDate').value = today;
			
			// Show the modal
			document.getElementById('transferModal').classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeTransferModal() {
			document.getElementById('transferModal').classList.remove('active');
			document.body.style.overflow = '';
			
			// Reset form
			document.getElementById('transferForm').reset();
		}

		function openDeceasedModal(seniorId) {
			// Set the senior ID
			document.getElementById('deceasedSeniorId').value = seniorId;
			
			// Set default death date to today
			const today = new Date().toISOString().split('T')[0];
			document.getElementById('deathDate').value = today;
			
			// Show the modal
			document.getElementById('deceasedModal').classList.add('active');
			document.body.style.overflow = 'hidden';
		}

		function closeDeceasedModal() {
			document.getElementById('deceasedModal').classList.remove('active');
			document.body.style.overflow = '';
			
			// Reset form
			document.getElementById('deceasedForm').reset();
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
					<button type="button" class="button secondary small" onclick="removeFamilyMember(this)" aria-label="Remove family member" title="Remove family member">
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
				// Skip closing for addSeniorModal, editSeniorModal, transferModal, and deceasedModal - only close via X button
				if (e.target.id === 'addSeniorModal' || e.target.id === 'editSeniorModal' || e.target.id === 'transferModal' || e.target.id === 'deceasedModal') return;

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
				<button class="modal-close" onclick="closeAddSeniorModal()" aria-label="Close add senior form">&times;</button>
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

						<!-- Contact field removed per request -->

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

					<!-- Status and Category Section -->
					<div class="form-section">
						<h3 class="section-title">Status & Category</h3>
						<div class="form-row">
							<div class="form-group">
								<label for="life_status" class="form-label">
									<span class="label-text">Life Status</span>
								</label>
								<select name="life_status" id="life_status" class="form-input" required>
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
									<button type="button" class="button secondary small" onclick="removeFamilyMember(this)" aria-label="Remove family member" title="Remove family member">
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
	<div id="editSeniorModal" class="modal-overlay">
		<div class="modal" style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90%; overflow-y: auto; position: relative;">
			<div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
				<h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;">Edit Senior Profile</h2>
				<button onclick="closeEditSeniorModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;" aria-label="Close edit senior form">&times;</button>
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Contact field removed per request -->
                    <div>
                        <label for="editCellphone" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Cellphone #</label>
                        <input type="text" id="editCellphone" name="cellphone" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    </div>
                </div>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
					<div>
						<label for="editOscaIdNo" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">OSCA ID Number *</label>
						<input type="text" id="editOscaIdNo" name="osca_id_no" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
					<div>
						<label for="editPurok" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Purok</label>
						<input type="text" id="editPurok" name="purok" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
					</div>
				</div>

				<div>
					<label for="editHealthCondition" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Health Condition</label>
					<input type="text" id="editHealthCondition" name="health_condition" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
				</div>

				<div>
					<label for="editRemarks" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Remarks</label>
					<textarea id="editRemarks" name="remarks" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;"></textarea>
				</div>

				<div style="display: flex; align-items: center; gap: 1rem;">
					<label for="editCategory" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
						Category:
						<select id="editCategory" name="category" style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="local">Local</option>
							<option value="national">National</option>
						</select>
					</label>
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
					<div style="display: flex; gap: 1rem;">
						<button type="button" onclick="markAsDeceased()" style="padding: 0.5rem 1rem; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
							<i class="fas fa-cross"></i>
							Mark as Deceased
						</button>
						<button type="button" onclick="transferSenior()" style="padding: 0.5rem 1rem; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
							<i class="fas fa-exchange-alt"></i>
							Transfer Senior
						</button>
					</div>
					<div style="display: flex; gap: 1rem;">
						<button type="button" onclick="closeEditSeniorModal()" style="padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">Cancel</button>
						<button type="submit" style="padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">Update Senior</button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<!-- Transfer Senior Modal -->
	<div id="transferModal" class="modal-overlay">
		<div class="modal" style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; max-height: 90%; overflow-y: auto; position: relative;">
			<div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
				<h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;">📦 Transfer Details</h2>
				<button onclick="closeTransferModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;" aria-label="Close transfer form">&times;</button>
			</div>
			<form id="transferForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="display: flex; flex-direction: column; gap: 1.5rem;">
				<input type="hidden" name="csrf" value="<?= $csrf ?>">
				<input type="hidden" name="op" value="transfer_details">
				<input type="hidden" name="id" id="transferSeniorId">
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.75rem; font-size: 1rem;">Reason for Transfer:</div>
					<div style="display: flex; flex-direction: column; gap: 0.75rem;">
						<label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
							<input type="radio" name="transfer_reason" value="change_of_residence" required style="margin-right: 0.5rem;">
							☐ Change of residence
						</label>
						<label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
							<input type="radio" name="transfer_reason" value="moved_with_family" required style="margin-right: 0.5rem;">
							☐ Moved with family
						</label>
						<label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
							<input type="radio" name="transfer_reason" value="admitted_to_care_facility" required style="margin-right: 0.5rem;">
							☐ Admitted to care facility
						</label>
						<label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; cursor: pointer;">
							<input type="radio" name="transfer_reason" value="other" required style="margin-right: 0.5rem;">
							☐ Other: <input type="text" name="transfer_reason_other" placeholder="___________________________" style="flex: 1; padding: 0.25rem; border: none; border-bottom: 1px solid #d1d5db; background: transparent; margin-left: 0.5rem; outline: none;">
						</label>
					</div>
				</div>
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">New Address / Barangay:</div>
					<input type="text" id="newAddress" name="new_address" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 1px solid #d1d5db; background: transparent; outline: none; font-size: 1rem;" placeholder="______________________________">
				</div>
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">Effective Date of Transfer:</div>
					<input type="date" id="effectiveDate" name="effective_date" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 1px solid #d1d5db; background: transparent; outline: none; font-size: 1rem;">
				</div>
				
				<div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
					<button type="button" onclick="closeTransferModal()" style="padding: 0.75rem 1.5rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancel</button>
					<button type="submit" style="padding: 0.75rem 1.5rem; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Submit Transfer</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Mark as Deceased Modal -->
	<div id="deceasedModal" class="modal-overlay">
		<div class="modal" style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; max-height: 90%; overflow-y: auto; position: relative;">
			<div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
				<h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;">💀 Death Information</h2>
				<button onclick="closeDeceasedModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;" aria-label="Close deceased form">&times;</button>
			</div>
			<form id="deceasedForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="display: flex; flex-direction: column; gap: 1.5rem;">
				<input type="hidden" name="csrf" value="<?= $csrf ?>">
				<input type="hidden" name="op" value="mark_deceased">
				<input type="hidden" name="id" id="deceasedSeniorId">
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">Date of Death:</div>
					<input type="date" id="deathDate" name="death_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
				</div>
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">Time of Death:</div>
					<input type="time" id="deathTime" name="death_time" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
				</div>
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">Place of Death:</div>
					<input type="text" id="deathPlace" name="death_place" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="e.g., Hospital, Home, etc.">
				</div>
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">Cause of Death:</div>
					<input type="text" id="deathCause" name="death_cause" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" placeholder="e.g., Natural causes, Illness, etc.">
				</div>
				
				<div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
					<button type="button" onclick="closeDeceasedModal()" style="padding: 0.75rem 1.5rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 500;">Cancel</button>
					<button type="submit" style="padding: 0.75rem 1.5rem; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Mark as Deceased</button>
				</div>
			</form>
		</div>
	</div>

	<script>
		// Populate waiting list checkbox in edit modal based on senior category
		document.getElementById('editSeniorModal').addEventListener('show', function() {
			// Waiting list functionality removed
		});

		// When loading senior data for edit, set waiting list checkbox accordingly
		function openEditSeniorModal(id) {
			fetch(`seniors.php?action=get_senior&id=${id}`)
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
					document.getElementById('editOscaIdNo').value = senior.osca_id_no || '';
					document.getElementById('editPurok').value = senior.purok || '';
					document.getElementById('editCellphone').value = senior.cellphone || '';
					document.getElementById('editHealthCondition').value = senior.health_condition || '';
					document.getElementById('editRemarks').value = senior.remarks || '';
					document.getElementById('editCategory').value = senior.category || '';

					// Show modal
					document.getElementById('editSeniorModal').classList.add('active');
					document.body.style.overflow = 'hidden';
				})
				.catch(() => {
					alert('Error loading senior data.');
				});
		}
	</script>
</body>
</html>
