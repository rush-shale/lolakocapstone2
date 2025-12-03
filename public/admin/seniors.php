<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
start_app_session();
$todayDate = date('Y-m-d');

if (!function_exists('is_ajax_request')) {
	/**
	 * Determine whether the current request is an AJAX request.
	 */
	function is_ajax_request(): bool {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
	}
}

// Ensure session is writable and active
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
error_log("Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));
error_log("Session ID: " . session_id());

$action = $_GET['action'] ?? null;
$csrf = null;

// Generate CSRF token - FIXED APPROACH
// The key issue: Token must be generated BEFORE POST processing starts
// and must persist in session between GET and POST requests

// For GET requests: Generate a fresh token for full page loads.
// For lightweight AJAX reads (e.g., get_senior, csrf), reuse the existing token to avoid invalidating open forms.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$ajaxReadActions = ['get_senior', 'csrf'];
	if ($action && in_array($action, $ajaxReadActions, true)) {
		if (isset($_SESSION[CSRF_TOKEN_NAME]) && !empty($_SESSION[CSRF_TOKEN_NAME])) {
			$csrf = $_SESSION[CSRF_TOKEN_NAME];
			error_log("AJAX GET ({$action}) - Reusing existing CSRF token: " . substr($csrf, 0, 20) . '...');
		} else {
			$csrf = generate_csrf_token();
			error_log("AJAX GET ({$action}) - Generated CSRF token because none existed: " . substr($csrf, 0, 20) . '...');
		}
	} else {
		// Full GET request - generate fresh token and store in session
		$csrf = generate_csrf_token();
		error_log("GET request - Generated NEW CSRF token: " . substr($csrf, 0, 20) . '...');
		error_log("Token stored in session: " . (isset($_SESSION[CSRF_TOKEN_NAME]) && $_SESSION[CSRF_TOKEN_NAME] === $csrf ? 'YES (verified)' : 'NO/DIFFERENT'));
	}
} else {
	// POST request - DO NOT generate new token, use existing one from session
	// This is critical: if we generate a new token here, validation will always fail
	if (isset($_SESSION[CSRF_TOKEN_NAME]) && !empty($_SESSION[CSRF_TOKEN_NAME])) {
		$csrf = $_SESSION[CSRF_TOKEN_NAME];
		error_log("POST request - Using EXISTING token from session: " . substr($csrf, 0, 20) . '...');
	} else {
		// No token in session - this means session was lost or cleared
		// Generate a new one, but validation will fail (which is expected)
		$csrf = generate_csrf_token();
		error_log("ERROR: POST request but NO token in session - Session may have been lost");
		error_log("Full session contents: " . print_r($_SESSION, true));
	}
}

$pdo = get_db_connection();

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

			// Ensure category column can store transferred state
	if (!column_exists($pdo, 'seniors', 'category')) {
		$pdo->exec("ALTER TABLE seniors ADD COLUMN category ENUM('local','national','waiting','transferred') NOT NULL DEFAULT 'local'");
	} else {
		$column = $pdo->query("SHOW COLUMNS FROM seniors LIKE 'category'")->fetch(PDO::FETCH_ASSOC);
		if ($column && isset($column['Type']) && stripos($column['Type'], 'transferred') === false) {
			$pdo->exec("ALTER TABLE seniors MODIFY COLUMN category ENUM('local','national','waiting','transferred') NOT NULL DEFAULT 'local'");
		}
	}
	if (!column_exists($pdo, 'seniors', 'life_status')) {
		$pdo->exec("ALTER TABLE seniors ADD COLUMN life_status ENUM('living','deceased') NOT NULL DEFAULT 'living'");
	}
		} catch (Exception $e) {
			error_log('Failed to ensure waiting document columns exist: ' . $e->getMessage());
		}
		$ensured = true;
	}
}

if (!function_exists('message_is_error')) {
	function message_is_error(string $message = null): bool {
		if (!$message) {
			return false;
		}
		$lower = strtolower($message);
		$keywords = ['error', 'duplicate', 'failed', 'invalid', 'required', 'cannot', 'denied'];
		foreach ($keywords as $keyword) {
			if (strpos($lower, $keyword) !== false) {
				return true;
			}
		}
		return false;
	}
}

ensure_waiting_document_columns($pdo);

// Handle AJAX requests for getting senior data
if ($action === 'get_senior') {
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
// Lightweight endpoint to fetch current CSRF token for modals/forms
if ($action === 'csrf') {
	start_app_session();
	$token = $_SESSION[CSRF_TOKEN_NAME] ?? null;
	if (!$token) {
		$token = generate_csrf_token();
	}
	header('Content-Type: application/json');
	echo json_encode(['csrf' => $token]);
	exit;
}

$message = '';

// Handle success message from redirect (only if not processing POST and no error)
// This prevents showing old success messages when there's a new error
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	if (isset($_GET['success']) && $_GET['success'] === '1' && empty($message)) {
		$message = 'Senior added successfully';
		if (isset($_GET['new_senior_id'])) {
			$message .= ' (ID: ' . htmlspecialchars($_GET['new_senior_id']) . ')';
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$update_success = false;
	$updated_senior_id = null;
	$submitted_token = $_POST['csrf'] ?? '';
	$session_token = $_SESSION[CSRF_TOKEN_NAME] ?? '';
	$op = $_POST['op'] ?? '';
	
	// Debug logging
	error_log("=== POST Request ===");
	error_log("Operation: " . $op);
	error_log("CSRF Token - Submitted: " . (empty($submitted_token) ? 'EMPTY' : substr($submitted_token, 0, 20) . '...'));
	error_log("CSRF Token - Session: " . (empty($session_token) ? 'EMPTY' : substr($session_token, 0, 20) . '...'));
	
	// Debug: Log session info
	error_log("Session ID: " . session_id());
	error_log("Session data: " . print_r($_SESSION, true));
	
	// Validate CSRF token
	$token_valid = validate_csrf_token($submitted_token);
	
	if (!$token_valid) {
		// Require valid CSRF token for all modifying operations
		$message = 'Invalid session token. Please refresh the page and try again.';
		$csrf = generate_csrf_token();
		$token_valid = false;
	}
	
	if ($token_valid) {
		error_log("=== CSRF VALIDATION PASSED (or bypassed for create/update) ===");
		error_log("Operation: " . $op);
		
		// Proceed with the operation
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
		// Clean up placeholder or incomplete health condition entries
		if (in_array(strtolower($health_condition), ['iwan', 'none', 'n/a', 'na', 'not specified', 'unknown', ''])) {
			$health_condition = '';
		}
		$purok = trim($_POST['purok'] ?? '') ?: '';
		$cellphone = trim($_POST['cellphone'] ?? '') ?: '';
		$benefits_received = isset($_POST['benefits_received']) ? 1 : 0;
		// Waiting list documents flags (1 = missing, 0 = provided)
		$waiting_birth_certificate = isset($_POST['doc_birth_certificate']) ? 1 : 0;
		$waiting_marriage_contract = isset($_POST['doc_marriage_contract']) ? 1 : 0;
		$waiting_valid_id = isset($_POST['doc_valid_id']) ? 1 : 0;
        // Preserve existing life_status on update if not provided by the form (edit modal may omit it)
        $life_status_input = $_POST['life_status'] ?? null;
        // Normalize explicit inputs; otherwise leave null for preservation on update
        if ($life_status_input === 'deceased') {
        	$life_status = 'deceased';
        } elseif ($life_status_input === 'living') {
        	$life_status = 'living';
        } else {
        	$life_status = null; // defer resolution; preserve existing value on update
        }
			// Read category - default to local if select has a value, otherwise check waiting list
        $category_input = strtolower(trim($_POST['category'] ?? ''));
			$allowed_categories = ['local', 'national'];
			
			// Check if waiting list checkbox is set first (it overrides category)
			if (isset($_POST['waiting_list']) && $_POST['waiting_list'] === '1') {
				$category = 'waiting';
			} elseif (in_array($category_input, $allowed_categories, true)) {
				$category = $category_input;
        } else {
				// Default to local if category select has a default value but wasn't explicitly set
				// This handles cases where the form might not properly submit the select value
				$category = 'local';
		}

        // Set validation status and date based on category
        $validation_status = $category === 'waiting' ? 'Not Validated' : 'Validated';
        $validation_date = $category === 'waiting' ? null : date('Y-m-d H:i:s');

			// Validate required fields
			if (empty($first_name)) {
				$message = 'First name is required.';
			} elseif (empty($last_name)) {
				$message = 'Last name is required.';
			} elseif (empty($age) || $age < 60) {
				$message = 'Age is required and must be at least 60.';
			} elseif (empty($barangay)) {
				$message = 'Barangay is required.';
			} elseif (empty($sex)) {
				$message = 'Sex is required.';
			} elseif (empty($civil_status)) {
				$message = 'Civil status is required.';
			} elseif (empty($educational_attainment)) {
				$message = 'Educational attainment is required.';
			} else {
			// All validations passed, proceed with database operation
			error_log("All validations passed for operation: " . $op);
			try {
				// Ensure we have a fresh connection
				$pdo = get_db_connection();
				ensure_waiting_document_columns($pdo);
				error_log("Database connection established");
				$pdo->beginTransaction();
				error_log("Transaction started");
				
				// Initialize duplicate check flag
				$duplicateFound = false;

				if ($op === 'create') {
					error_log("Processing CREATE operation");
					
					// Comprehensive duplicate checking before insertion
					$duplicateMessage = '';
					
					// Check 1: OSCA ID number duplicate (if provided)
					if (!empty($osca_id_no)) {
						$oscaCheck = $pdo->prepare('SELECT id, first_name, middle_name, last_name, ext_name, barangay, osca_id_no FROM seniors WHERE osca_id_no = ?');
						$oscaCheck->execute([$osca_id_no]);
						if ($oscaCheck->rowCount() > 0) {
							$existing = $oscaCheck->fetch(PDO::FETCH_ASSOC);
							$existingName = trim($existing['first_name'] . ' ' . ($existing['middle_name'] ? $existing['middle_name'] . ' ' : '') . $existing['last_name'] . ($existing['ext_name'] ? ' ' . $existing['ext_name'] : ''));
							$duplicateFound = true;
							$duplicateMessage = "Duplicate OSCA ID detected! A senior with OSCA ID '{$existing['osca_id_no']}' (Name: {$existingName}) already exists in {$existing['barangay']} barangay.";
						}
					}
					
					// Check 2: Name-based duplicate (only if OSCA ID check didn't find a duplicate)
					if (!$duplicateFound) {
						$duplicateCheck = $pdo->prepare('
							SELECT id, first_name, last_name, middle_name, ext_name, date_of_birth, barangay, osca_id_no 
							FROM seniors 
							WHERE first_name = ? AND last_name = ? 
							AND (middle_name = ? OR (middle_name IS NULL AND ? IS NULL))
							AND (ext_name = ? OR (ext_name IS NULL AND ? IS NULL))
							AND (date_of_birth = ? OR (date_of_birth IS NULL AND ? IS NULL))
							AND barangay = ?
						');
						$duplicateCheck->execute([
							$first_name, $last_name, 
							$middle_name ?: null, $middle_name ?: null,
							$ext_name ?: null, $ext_name ?: null,
							$date_of_birth ?: null, $date_of_birth ?: null,
							$barangay
						]);
						
						if ($duplicateCheck->rowCount() > 0) {
							$existing = $duplicateCheck->fetch(PDO::FETCH_ASSOC);
							$existingName = trim($existing['first_name'] . ' ' . ($existing['middle_name'] ? $existing['middle_name'] . ' ' : '') . $existing['last_name'] . ($existing['ext_name'] ? ' ' . $existing['ext_name'] : ''));
							$duplicateFound = true;
							$oscaInfo = !empty($existing['osca_id_no']) ? " (OSCA ID: {$existing['osca_id_no']})" : '';
							$duplicateMessage = "Duplicate entry detected! A senior with the name '{$existingName}'{$oscaInfo} already exists in {$existing['barangay']} barangay.";
						}
					}
					
					if ($duplicateFound) {
						$message = $duplicateMessage;
						$pdo->rollback();
						error_log("Duplicate detected: " . $message);
					} else {
						error_log("No duplicates found. Executing INSERT query for senior: $first_name $last_name");
						// For creation, default to 'living' unless explicitly submitted as 'deceased'
						$life_status_create = ($life_status_input === 'deceased') ? 'deceased' : 'living';
						// Auto-assign next OSCA ID if not provided
						if ($osca_id_no === '') {
							try {
								$nextIdStmt = $pdo->query("SELECT COALESCE(MAX(CAST(osca_id_no AS UNSIGNED)),0)+1 FROM seniors");
								$osca_id_no = (string)((int)$nextIdStmt->fetchColumn() ?: 1);
							} catch (Exception $ignore) {
								$osca_id_no = '1';
							}
						}
						$stmt = $pdo->prepare('INSERT INTO seniors (first_name, middle_name, last_name, ext_name, age, date_of_birth, sex, place_of_birth, civil_status, educational_attainment, occupation, annual_income, other_skills, barangay, contact, osca_id_no, remarks, health_condition, purok, cellphone, benefits_received, life_status, category, validation_status, validation_date, waiting_birth_certificate, waiting_marriage_contract, waiting_valid_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
					$stmt->execute([
						$first_name, $middle_name ?: null, $last_name, $ext_name ?: null, $age,
						$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
						$civil_status ?: '', $educational_attainment ?: '',
						$occupation ?: null, $annual_income, $other_skills,
						$barangay, $contact, $osca_id_no, $remarks,
						$health_condition, $purok, $cellphone,
						$benefits_received, $life_status_create, $category, $validation_status, $validation_date,
						$waiting_birth_certificate, $waiting_marriage_contract, $waiting_valid_id
					]);
						$senior_id = $pdo->lastInsertId();
					
					// If created as deceased, ensure a corresponding basic record exists
					if ($life_status_create === 'deceased') {
						try {
							$pdo->exec("CREATE TABLE IF NOT EXISTS senior_deaths (
								id INT AUTO_INCREMENT PRIMARY KEY,
								senior_id INT NOT NULL,
								death_date DATE NULL,
								place_of_death VARCHAR(255) NULL,
								cause_of_death VARCHAR(255) NULL,
								remarks TEXT NULL,
								created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
								updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
								INDEX idx_senior_death_senior_id (senior_id)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
							$exists = $pdo->prepare("SELECT id FROM senior_deaths WHERE senior_id = ? LIMIT 1");
							$exists->execute([$senior_id]);
							if (!$exists->fetch()) {
								$ins = $pdo->prepare("INSERT INTO senior_deaths (senior_id) VALUES (?)");
								$ins->execute([$senior_id]);
							}
						} catch (Exception $e) {
							error_log("Failed to ensure senior_deaths record for newly created deceased senior {$senior_id}: " . $e->getMessage());
						}
					}
					error_log("Senior inserted successfully with ID: $senior_id");
						
						// If benefits_received is checked, create benefit_records entries for all benefit types
						if ($benefits_received == 1) {
							try {
								// Ensure benefit_records table exists
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
								
								// All benefit types from the Benefits section
								$benefitTypes = ['sp_q1', 'sp_q2', 'sp_q3', 'sp_q4', 'octogenarian', 'nonagenarian', 'centenarian', 'financial_asst', 'burial_asst'];
								
								// Insert benefit records for all types with received = 1
								$benefitStmt = $pdo->prepare('INSERT INTO benefit_records (senior_id, benefit_type, received) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE received=1');
								foreach ($benefitTypes as $type) {
									$benefitStmt->execute([$senior_id, $type]);
								}
							} catch (Exception $benefitError) {
								// Log error but don't fail the senior creation
								error_log('Failed to create benefit records for senior ' . $senior_id . ': ' . $benefitError->getMessage());
							}
						}
						
						$message = 'Senior added successfully';
					}
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
						if ($category === 'waiting') {
							// Remain in waiting category until explicitly validated
							$validation_status = 'Not Validated';
							$validation_date = null;
						} else {
							// Allow transition out of waiting when user updates category
							// Ensure validation metadata reflects the change
							if ($validation_status === 'Not Validated') {
								$validation_status = 'Validated';
							}
							if (!$validation_date) {
								$validation_date = date('Y-m-d H:i:s');
							}
						}
					}
					// Resolve life_status: preserve current if not provided in form
					if ($life_status === null) {
						try {
							$cur = $pdo->prepare('SELECT life_status FROM seniors WHERE id = ?');
							$cur->execute([$id]);
							$life_status = $cur->fetchColumn() ?: 'living';
						} catch (Exception $ignore) {
							$life_status = 'living';
						}
					}
					
					// Check for duplicates on update (excluding the current senior being updated)
					$duplicateFound = false;
					$duplicateMessage = '';
					
					// Check 1: OSCA ID number duplicate (if provided and different from current)
					if (!empty($osca_id_no)) {
						$oscaCheck = $pdo->prepare('SELECT id, first_name, middle_name, last_name, ext_name, barangay, osca_id_no FROM seniors WHERE osca_id_no = ? AND id != ?');
						$oscaCheck->execute([$osca_id_no, $id]);
						if ($oscaCheck->rowCount() > 0) {
							$existing = $oscaCheck->fetch(PDO::FETCH_ASSOC);
							$existingName = trim($existing['first_name'] . ' ' . ($existing['middle_name'] ? $existing['middle_name'] . ' ' : '') . $existing['last_name'] . ($existing['ext_name'] ? ' ' . $existing['ext_name'] : ''));
							$duplicateFound = true;
							$duplicateMessage = "Duplicate OSCA ID detected! Another senior with OSCA ID '{$existing['osca_id_no']}' (Name: {$existingName}) already exists in {$existing['barangay']} barangay.";
						}
					}
					
					// Check 2: Name-based duplicate (only if OSCA ID check didn't find a duplicate)
					if (!$duplicateFound) {
						$duplicateCheck = $pdo->prepare('
							SELECT id, first_name, last_name, middle_name, ext_name, date_of_birth, barangay, osca_id_no 
							FROM seniors 
							WHERE first_name = ? AND last_name = ? 
							AND (middle_name = ? OR (middle_name IS NULL AND ? IS NULL))
							AND (ext_name = ? OR (ext_name IS NULL AND ? IS NULL))
							AND (date_of_birth = ? OR (date_of_birth IS NULL AND ? IS NULL))
							AND barangay = ?
							AND id != ?
						');
						$duplicateCheck->execute([
							$first_name, $last_name, 
							$middle_name ?: null, $middle_name ?: null,
							$ext_name ?: null, $ext_name ?: null,
							$date_of_birth ?: null, $date_of_birth ?: null,
							$barangay, $id
						]);
						
						if ($duplicateCheck->rowCount() > 0) {
							$existing = $duplicateCheck->fetch(PDO::FETCH_ASSOC);
							$existingName = trim($existing['first_name'] . ' ' . ($existing['middle_name'] ? $existing['middle_name'] . ' ' : '') . $existing['last_name'] . ($existing['ext_name'] ? ' ' . $existing['ext_name'] : ''));
							$duplicateFound = true;
							$oscaInfo = !empty($existing['osca_id_no']) ? " (OSCA ID: {$existing['osca_id_no']})" : '';
							$duplicateMessage = "Duplicate entry detected! Another senior with the name '{$existingName}'{$oscaInfo} already exists in {$existing['barangay']} barangay.";
						}
					}
					
					if ($duplicateFound) {
						$message = $duplicateMessage;
						$pdo->rollback();
						error_log("Duplicate detected on update: " . $message);
					} else {
						$stmt = $pdo->prepare('UPDATE seniors SET first_name=?, middle_name=?, last_name=?, ext_name=?, age=?, date_of_birth=?, sex=?, place_of_birth=?, civil_status=?, educational_attainment=?, occupation=?, annual_income=?, other_skills=?, barangay=?, contact=?, osca_id_no=?, remarks=?, health_condition=?, purok=?, cellphone=?, benefits_received=?, life_status=?, category=?, validation_status=?, validation_date=?, waiting_birth_certificate=?, waiting_marriage_contract=?, waiting_valid_id=? WHERE id=?');
						$stmt->execute([
							$first_name, $middle_name ?: null, $last_name, $ext_name ?: null, $age,
							$date_of_birth ?: null, $sex ?: null, $place_of_birth ?: null,
							$civil_status ?: '', $educational_attainment ?: '',
							$occupation ?: null, $annual_income, $other_skills,
							$barangay, $contact, $osca_id_no, $remarks,
							$health_condition, $purok, $cellphone,
							$benefits_received, $life_status, $category, $validation_status, $validation_date,
							$waiting_birth_certificate, $waiting_marriage_contract, $waiting_valid_id,
							$id
						]);
						$senior_id = $id;
						
						// Ensure a basic death record exists when marking as deceased
						if ($life_status === 'deceased') {
							try {
								$pdo->exec("CREATE TABLE IF NOT EXISTS senior_deaths (
									id INT AUTO_INCREMENT PRIMARY KEY,
									senior_id INT NOT NULL,
									death_date DATE NULL,
									place_of_death VARCHAR(255) NULL,
									cause_of_death VARCHAR(255) NULL,
									remarks TEXT NULL,
									created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
									updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
									INDEX idx_senior_death_senior_id (senior_id)
								) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
								
								$exists = $pdo->prepare("SELECT id FROM senior_deaths WHERE senior_id = ? LIMIT 1");
								$exists->execute([$senior_id]);
								if (!$exists->fetch()) {
									$ins = $pdo->prepare("INSERT INTO senior_deaths (senior_id) VALUES (?)");
									$ins->execute([$senior_id]);
								}
							} catch (Exception $e) {
								error_log("Failed to ensure senior_deaths record for senior {$senior_id}: " . $e->getMessage());
							}
						}
						$message = 'Senior updated successfully';
					}
				}
				
				// Handle family composition (only if no duplicate was found)
				if (($op === 'update' || $op === 'create') && !$duplicateFound && isset($senior_id)) {
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
				
				// Only commit if no duplicate was found
				if (!$duplicateFound) {
					error_log("About to commit transaction");
					$pdo->commit();
					error_log("Transaction committed successfully");
					
					// After write, force a full reload so the table reflects changes immediately
					if ($op === 'create') {
						error_log("Redirecting to success page with senior_id: $senior_id");
						header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1&new_senior_id=' . $senior_id);
						exit;
					}
					if ($op === 'update') {
						error_log("Redirecting to success page after update");
						$update_success = true;
						$updated_senior_id = $senior_id;
						if (!is_ajax_request()) {
							header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
							exit;
						}
					}
				}
			} catch (Exception $e) {
				// Use safe rollback to handle connection issues
				error_log("EXCEPTION in senior operation: " . $e->getMessage());
				error_log("Stack trace: " . $e->getTraceAsString());
				safe_rollback($pdo);
				$message = 'Error: ' . $e->getMessage();
				error_log("Error message set: " . $message);
			}
		}
	}

		// Handle validation of waiting seniors (inside POST and CSRF validation block)
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
		if ($op === 'toggle_benefits') {
			$id = (int)($_POST['id'] ?? 0);
			$to = isset($_POST['to']) && (int)$_POST['to'] === 1 ? 1 : 0;
			if ($id) {
				try {
					$pdo = get_db_connection();
					ensure_waiting_document_columns($pdo);
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
					ensure_waiting_document_columns($pdo);
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
			$deathDateValid = true;
			if ($death_date) {
				$todayStr = date('Y-m-d');
				if ($death_date > $todayStr) {
					$deathDateValid = false;
				}
			}
			
			if ($id && $death_date && $death_place && $death_cause && $deathDateValid) {
				try {
					$pdo = get_db_connection();
					ensure_waiting_document_columns($pdo);
					$pdo->beginTransaction();
					
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
					$stmt = $pdo->prepare('INSERT INTO senior_deaths (senior_id, date_of_death, time_of_death, place_of_death, cause_of_death) VALUES (?, ?, ?, ?, ?)
						ON DUPLICATE KEY UPDATE date_of_death = VALUES(date_of_death), time_of_death = VALUES(time_of_death), place_of_death = VALUES(place_of_death), cause_of_death = VALUES(cause_of_death)');
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
					
					// Commit transaction
					$pdo->commit();
					
					$message = 'Senior marked as deceased successfully.';
					
					// Redirect to deceased seniors page with success message
					header("Location: deceased_seniors.php?deceased_success=1");
					exit;
				} catch (Exception $e) {
					// Rollback on error
					if ($pdo->inTransaction()) {
						$pdo->rollback();
					}
					error_log("Mark deceased failed: " . $e->getMessage());
					$message = 'Error marking as deceased: ' . $e->getMessage();
				}
			} else {
				if (!$deathDateValid) {
					$message = 'Date of death cannot be in the future.';
				} else {
					$message = 'Please fill in all required death information fields.';
				}
			}
		}
		if ($op === 'transfer_details') {
			$id = (int)($_POST['id'] ?? 0);
			$transfer_reason = $_POST['transfer_reason'] ?? '';
			$transfer_reason_other = trim($_POST['transfer_reason_other'] ?? '');
			$new_address = trim($_POST['new_address'] ?? '');
			$effective_date = $_POST['effective_date'] ?? '';
			$dateError = '';
			$effectiveDateValid = true;
			if ($effective_date) {
				$todayStr = date('Y-m-d');
				if ($effective_date > $todayStr) {
					$effectiveDateValid = false;
					$dateError = 'Effective transfer date cannot be in the future.';
				}
			}
			
			// Validate required fields
			$valid = $id && $transfer_reason && $new_address && $effective_date && $effectiveDateValid;
			
			// If reason is 'other', validate that other reason is provided
			if ($transfer_reason === 'other' && empty($transfer_reason_other)) {
				$valid = false;
			}
			
			// Check if senior is deceased - deceased seniors cannot be transferred
			if ($valid && $id) {
				try {
					$pdo = get_db_connection();
					$lifeStatusCheck = $pdo->prepare('SELECT life_status FROM seniors WHERE id = ?');
					$lifeStatusCheck->execute([$id]);
					$life_status = $lifeStatusCheck->fetchColumn();
					
					if ($life_status === 'deceased') {
						$valid = false;
						$message = 'Cannot transfer a deceased senior. Only living seniors can be transferred.';
						error_log("Transfer blocked - Senior ID $id is deceased");
					}
				} catch (Exception $e) {
					error_log("Error checking life status for transfer: " . $e->getMessage());
					// If we can't check, err on the side of caution and block the transfer
					$valid = false;
					$message = 'Error verifying senior status. Transfer cannot be processed.';
				}
			}
			
			if ($valid) {
				try {
					$pdo = get_db_connection();
					ensure_waiting_document_columns($pdo);
					$pdo->beginTransaction();
					
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
					
					// Commit transaction
					$pdo->commit();
					
					// Set success message
					$message = 'Senior has been successfully transferred!';
					error_log("Transfer completed successfully for senior ID: $id");
					
					// Redirect to transferred seniors page with success message
					header("Location: transferred_seniors.php?transfer_success=1");
					exit;
				} catch (Exception $e) {
					// Rollback on error
					if ($pdo->inTransaction()) {
						$pdo->rollback();
					}
					error_log("Transfer failed: " . $e->getMessage());
					error_log("Transfer error details: " . print_r($e, true));
					$message = 'Error processing transfer: ' . $e->getMessage();
				}
			} else {
				$message = $dateError ?: 'Please fill in all required transfer information fields.';
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

	if (is_ajax_request() && $op === 'update') {
		header('Content-Type: application/json');
		echo json_encode([
			'success' => $update_success,
			'id' => $update_success ? (int)$updated_senior_id : null,
			'message' => $update_success ? ($message ?: 'Senior updated successfully') : ($message ?: 'Failed to update senior.')
		]);
		exit;
	}
}

try {
	$pdo = get_db_connection();
	ensure_waiting_document_columns($pdo);
	$barangays = $pdo->query('SELECT name FROM barangays ORDER BY name')->fetchAll();
} catch (Exception $e) {
	error_log("Failed to load barangays: " . $e->getMessage());
	$barangays = [];
}

// PDF render function (same as in reports.php)
if (!function_exists('pdf_render')) {
    function pdf_render(string $title, array $headers, array $rows, string $report_type = 'seniors', string $back_url = ''): void {
        // Enhanced PDF-friendly HTML with A4 print optimization and professional styling
        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
        echo '<style>
        @page {
            size: A4;
            margin: 1.5cm 1cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "Times New Roman", serif;
            margin: 0;
            padding: 0;
            color: #000;
            background: white;
            font-size: 11px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
        }
        .header-bottom {
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        .logos {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            gap: 20px;
        }
        .logo {
            width: 60px;
            height: 60px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: white;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }
        .header-text {
            text-align: center;
            margin: 0 20px;
        }
        .header-text h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text h2 {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .header-text h3 {
            font-size: 16px;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
            color: #1e40af;
            letter-spacing: 0.5px;
        }
        .datetime-display {
            text-align: left;
            margin: 0 0 5px 0;
            font-size: 11px;
            font-weight: bold;
            min-height: 20px;
        }
        .signature-section {
            margin-top: 25px;
            display: flex;
            align-items: flex-end;
            page-break-inside: avoid;
        }
        .signature-field {
            display: inline-block;
            margin-right: 30px;
        }
        .signature-label {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 40px;
        }
        .noprint {
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .noprint button {
            padding: 12px 24px;
            border: 2px solid #1e40af;
            border-radius: 6px;
            background: #1e40af;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .noprint button:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
        }
        .noprint .back-btn {
            background: #6b7280;
            border-color: #6b7280;
        }
        .noprint .back-btn:hover {
            background: #4b5563;
            border-color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6px;
            margin-top: 10px;
            page-break-inside: auto;
            table-layout: fixed;
        }
        thead {
            display: table-header-group;
        }
        tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            border: 1px solid #000;
            padding: 1px 2px;
            text-align: left;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
        }
        td {
            font-size: 6px;
        }
        .number-col {
            text-align: center;
            width: 15px;
            font-weight: bold;
        }
        .name-col {
            width: 35px;
            font-weight: 500;
        }
        .barangay-col {
            width: 30px;
        }
        .age-col, .sex-col {
            text-align: center;
            width: 18px;
        }
        .osca-col {
            text-align: center;
            width: 30px;
            font-weight: bold;
        }
        .ext-col {
            width: 15px;
            text-align: center;
        }
        .civil-col {
            width: 25px;
        }
        .birthdate-col {
            width: 35px;
            text-align: center;
        }
        .remarks-col {
            width: 40px;
        }
        .health-col {
            width: 40px;
        }
        .purok-col {
            width: 25px;
        }
        .place-col {
            width: 40px;
        }
        .cellphone-col {
            width: 35px;
        }
        .life-col {
            width: 25px;
            text-align: center;
        }
        .category-col {
            width: 25px;
        }
        @media print {
            .noprint { display: none; }
            body { 
                padding: 0;
                font-size: 8px;
            }
            .header { 
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            table {
                font-size: 5px;
                width: 100%;
            }
            th, td {
                padding: 1px 2px;
                font-size: 5px;
            }
        }
        @media screen {
            body {
                padding: 20px;
                max-width: 210mm;
                margin: 0 auto;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
    </style>';
        echo '</head><body>';
        
        echo '<div class="noprint">';
        echo '<button onclick="window.print()">🖨️ Print / Save as PDF</button>';
        if (!empty($back_url)) {
            echo '<button class="back-btn" onclick="window.location.href=\'' . htmlspecialchars($back_url, ENT_QUOTES) . '\'">← Back to All Seniors</button>';
        }
        echo '</div>';
        
        // Header with logos and official format
        echo '<div class="header">';
        echo '<div class="logos">';
        echo '<div class="logo"><img src="' . BASE_URL . '/images/OSCA MAIN LOGO.png" alt="OSCA Logo" /></div>';
        echo '<div class="header-text">';
        echo '<h1>Republic of the Philippines</h1>';
        echo '<h2>Province of Bukidnon</h2>';
        echo '<h2>Municipality of Manolo Fortich</h2>';
        echo '<h3>' . htmlspecialchars($title) . '</h3>';
        echo '</div>';
        echo '<div class="logo"><img src="' . BASE_URL . '/images/MANOLO FORTICH LOGO.png" alt="Manolo Fortich Logo" /></div>';
        echo '</div>';
        echo '<div class="datetime-display" id="datetimeDisplay"></div>';
        echo '<div class="header-bottom"></div>';
        echo '</div>';
        
        // Data table
        echo '<table>';
        echo '<thead><tr>';
        echo '<th class="number-col">No.</th>';
        foreach ($headers as $h) { 
            $class = '';
            if (in_array($h, ['Last Name', 'First Name', 'Middle Name'])) $class = 'name-col';
            elseif ($h === 'Barangay') $class = 'barangay-col';
            elseif (in_array($h, ['Age', 'Sex'])) $class = 'age-col';
            elseif ($h === 'OSCA ID No') $class = 'osca-col';
            elseif ($h === 'Ext') $class = 'ext-col';
            elseif ($h === 'Civil Status') $class = 'civil-col';
            elseif ($h === 'Birthdate') $class = 'birthdate-col';
            elseif ($h === 'Remarks') $class = 'remarks-col';
            elseif ($h === 'Health Condition') $class = 'health-col';
            elseif ($h === 'Purok') $class = 'purok-col';
            elseif ($h === 'Place of Birth') $class = 'place-col';
            elseif ($h === 'Cellphone #') $class = 'cellphone-col';
            elseif ($h === 'Life Status') $class = 'life-col';
            elseif ($h === 'Category') $class = 'category-col';
            echo '<th class="' . $class . '">' . htmlspecialchars($h) . '</th>'; 
        }
        echo '</tr></thead><tbody>';
        
        $rowNum = 1;
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td class="number-col">' . $rowNum . '</td>';
            foreach ($headers as $i => $h) { 
                $value = (string)array_values($r)[$i] ?? '';
                $class = '';
                if (in_array($h, ['Last Name', 'First Name', 'Middle Name'])) $class = 'name-col';
                elseif ($h === 'Barangay') $class = 'barangay-col';
                elseif (in_array($h, ['Age', 'Sex'])) $class = 'age-col';
                elseif ($h === 'OSCA ID No') $class = 'osca-col';
                elseif ($h === 'Ext') $class = 'ext-col';
                elseif ($h === 'Civil Status') $class = 'civil-col';
                elseif ($h === 'Birthdate') $class = 'birthdate-col';
                elseif ($h === 'Remarks') $class = 'remarks-col';
                elseif ($h === 'Health Condition') $class = 'health-col';
                elseif ($h === 'Purok') $class = 'purok-col';
                elseif ($h === 'Place of Birth') $class = 'place-col';
                elseif ($h === 'Cellphone #') $class = 'cellphone-col';
                elseif ($h === 'Life Status') $class = 'life-col';
                elseif ($h === 'Category') $class = 'category-col';
                echo '<td class="' . $class . '">' . htmlspecialchars($value) . '</td>'; 
            }
            echo '</tr>';
            $rowNum++;
        }
        echo '</tbody></table>';
        
        // Signature section
        echo '<div class="signature-section">';
        echo '<div class="signature-field">';
        echo '<span class="signature-label">Printed Name:</span>';
        echo '<div class="signature-line"></div>';
        echo '</div>';
        echo '<div class="signature-field">';
        echo '<span class="signature-label">Signature:</span>';
        echo '<div class="signature-line"></div>';
        echo '</div>';
        echo '</div>';
        
        // JavaScript for real-time date/time
        echo '<script>
            function updateDateTime() {
                const now = new Date();
                const months = ["January", "February", "March", "April", "May", "June", 
                              "July", "August", "September", "October", "November", "December"];
                const month = months[now.getMonth()];
                const day = now.getDate();
                const year = now.getFullYear();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, "0");
                const seconds = String(now.getSeconds()).padStart(2, "0");
                const ampm = hours >= 12 ? "PM" : "AM";
                hours = hours % 12;
                hours = hours ? hours : 12;
                hours = String(hours).padStart(2, "0");
                const dateTimeStr = month + " " + day + ", " + year + " at " + hours + ":" + minutes + ":" + seconds + " " + ampm;
                document.getElementById("datetimeDisplay").textContent = dateTimeStr;
            }
            updateDateTime();
            setInterval(updateDateTime, 1000);
        </script>';
        
        echo '</body></html>';
        exit;
    }
}

// Handle PDF export before processing filters
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $pdo = get_db_connection();
    
    // Get current filter parameters
    $status = $_GET['status'] ?? 'all';
    $category = $_GET['category'] ?? 'all';
    $barangayFilter = $_GET['barangay'] ?? 'all';
    
    // Build query based on filters
    $where = [];
    $params = [];
    
    // Life status filter
    if ($status === 'deceased') {
        $where[] = 'life_status = ?';
        $params[] = 'deceased';
    } elseif ($status === 'active') {
        $where[] = 'life_status = ?';
        $params[] = 'living';
    } elseif ($status === 'inactive') {
        $where[] = 'life_status = ?';
        $params[] = 'living';
    }
    
    // Category filter
    if ($category === 'local' || $category === 'national') {
        $where[] = 'category = ?';
        $params[] = $category;
    } elseif ($category === 'waiting') {
        $where[] = 'category = ?';
        $params[] = 'waiting';
        if (empty($where) || !in_array('life_status = ?', $where)) {
            $where[] = 'life_status = ?';
            $params[] = 'living';
        }
    }
    
    // Barangay filter
    if ($barangayFilter && $barangayFilter !== 'all') {
        $where[] = 'barangay = ?';
        $params[] = $barangayFilter;
    }
    
    // Build SQL query with formatted date
    $sql = "SELECT last_name, first_name, COALESCE(middle_name,'') AS middle_name, COALESCE(ext_name,'') AS ext_name, barangay, age,
            sex, civil_status, 
            CASE WHEN date_of_birth IS NOT NULL AND date_of_birth != '' THEN DATE_FORMAT(date_of_birth, '%M %d, %Y') ELSE '' END AS date_of_birth,
            osca_id_no, COALESCE(remarks,'') AS remarks, COALESCE(health_condition,'') AS health_condition,
            COALESCE(purok,'') AS purok, COALESCE(place_of_birth,'') AS place_of_birth, COALESCE(cellphone,'') AS cellphone,
            life_status, category
            FROM seniors";
    
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    
    // Determine report title based on filters
    $reportTitle = 'SENIORS DATA REPORT';
    $reportType = 'seniors_official';
    
    if ($status === 'deceased') {
        $reportTitle = 'DECEASED SENIORS REPORT';
        $reportType = 'deceased';
    } elseif ($category === 'waiting') {
        $reportTitle = 'WAITING SENIORS REPORT';
        $reportType = 'waiting';
    } elseif ($category === 'local') {
        $reportTitle = 'LOCAL SENIORS REPORT';
        $reportType = 'seniors_official';
    } elseif ($category === 'national') {
        $reportTitle = 'NATIONAL SENIORS REPORT';
        $reportType = 'seniors_official';
    } elseif ($barangayFilter && $barangayFilter !== 'all') {
        $reportTitle = 'SENIORS DATA REPORT - ' . strtoupper($barangayFilter);
        $reportType = 'seniors_official';
    }
    
    // Add ordering
    $sql .= ' ORDER BY barangay, last_name, first_name';
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Headers matching the reports format
    $headers = ['Last Name','First Name','Middle Name','Ext','Barangay','Age','Sex','Civil Status','Birthdate','OSCA ID No','Remarks','Health Condition','Purok','Place of Birth','Cellphone #','Life Status','Category'];
    
    // Build back URL with preserved filters (excluding export parameter)
    $backUrl = 'seniors.php';
    $backParams = [];
    if ($status && $status !== 'all') {
        $backParams['status'] = $status;
    }
    if ($category && $category !== 'all') {
        $backParams['category'] = $category;
    }
    if ($barangayFilter && $barangayFilter !== 'all') {
        $backParams['barangay'] = $barangayFilter;
    }
    if (!empty($backParams)) {
        $backUrl .= '?' . http_build_query($backParams);
    }
    
    // Render PDF
    pdf_render($reportTitle, $headers, $rows, $reportType, $backUrl);
    exit;
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
$category = $_GET['category'] ?? 'all'; // all|local|national|waiting

$where = [];
$params = [];

// Handle different status views
try {
	$pdo = get_db_connection();
	ensure_waiting_document_columns($pdo);
	
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
		
		// Handle barangay filter from URL
		$barangayFilter = $_GET['barangay'] ?? null;
		if ($barangayFilter && $barangayFilter !== 'all') {
			$where[] = 'barangay = ?';
			$params[] = $barangayFilter;
		}
		if ($category === 'waiting') {
			$where[] = 'category = ?';
			$params[] = 'waiting';
		}

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
	ensure_waiting_document_columns($pdo);
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
	<?php $cssVer = @filemtime(__DIR__ . '/../assets/government-portal.css') ?: time(); ?>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css?v=<?= $cssVer ?>">
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

		/* Filter dropdown styling */
		.table-barangay-filter {
			margin-left: 0.5rem;
		}
		
		.filter-select {
			padding: 0.5rem 1rem;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			font-size: 0.875rem;
			background: white;
			cursor: pointer;
			min-width: 150px;
			transition: all 0.2s ease;
		}
		
		.filter-select:hover {
			border-color: #2563eb;
		}
		
		.filter-select:focus {
			outline: none;
			border-color: #2563eb;
			box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
		}
		
		.filter-btn {
			padding: 0.5rem 1rem;
			border: 1px solid #d1d5db;
			border-radius: 6px;
			font-size: 0.875rem;
			background: white;
			cursor: pointer;
			transition: all 0.2s ease;
			color: #374151;
		}
		
		.filter-btn:hover {
			background: #f3f4f6;
			border-color: #9ca3af;
		}
		
		.filter-btn.active {
			background: #2563eb;
			color: white;
			border-color: #2563eb;
		}
		
		.filter-btn.active:hover {
			background: #1d4ed8;
		}
		
		/* Table scroll styling */
		.table-container {
			overflow-x: auto;
			overflow-y: visible;
			-webkit-overflow-scrolling: touch;
			max-width: 100%;
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
		
		/* Ensure seniors table is wide enough to show all columns */
		.seniors-table {
			width: max-content !important;
			min-width: 2500px;
		}
		
		/* Responsive design for tables */
		@media (min-width: 769px) and (max-width: 1024px) {
			.seniors-table {
				min-width: 2000px !important;
			}
			
			.table-scroll table th,
			.table-scroll table td {
				min-width: 100px;
				font-size: 0.875rem;
				padding: 0.5rem 0.75rem;
			}
			
			.content-body {
				flex-direction: column;
			}
		}
		
		@media (min-width: 481px) and (max-width: 768px) {
			.table-scroll {
				-webkit-overflow-scrolling: touch;
				position: relative;
			}
			
			.seniors-table {
				min-width: 1800px !important;
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
			
			.content-body {
				flex-direction: column;
			}
		}
		
		@media (max-width: 480px) {
			.table-scroll {
				-webkit-overflow-scrolling: touch;
				position: relative;
				margin: 0 -0.75rem;
				padding: 0 0.75rem;
			}
			
			.seniors-table {
				min-width: 1600px !important;
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
			
			.content-body {
				flex-direction: column;
				padding: 0.75rem;
			}
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
	
	<main class="content">
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
		<div class="alert <?= message_is_error($message) ? 'alert-error' : 'alert-success' ?>">
			<div class="alert-icon">
				<i class="fas fa-<?= message_is_error($message) ? 'exclamation-circle' : 'check-circle' ?>"></i>
			</div>
			<div class="alert-content">
				<strong><?= message_is_error($message) ? 'Error!' : 'Success!' ?></strong>
				<p><?= htmlspecialchars($message) ?></p>
			</div>
		</div>
		<?php endif; ?>




				<!-- Seniors List -->
				<div class="modern-table-container">
                    <div class="modern-table-header">
						<div class="table-title-section">
							<h2>All Seniors</h2>
							<p>Manage senior citizen records and information</p>
						</div>
						<div class="table-controls">
							<div class="table-search">
								<span class="table-search-icon">🔍</span>
								<input type="text" id="searchInput" placeholder="Search seniors...">
							</div>
							<div class="table-filters">
								<button class="filter-btn <?= (($status === 'all' || !isset($_GET['status'])) && (!isset($_GET['category']) || $_GET['category'] === 'all')) ? 'active' : '' ?>" onclick="filterByCategory('all')" data-filter="all">All</button>
								<button class="filter-btn <?= (isset($_GET['category']) && $_GET['category'] === 'local') ? 'active' : '' ?>" onclick="filterByCategory('local')" data-filter="local">Local</button>
								<button class="filter-btn <?= (isset($_GET['category']) && $_GET['category'] === 'national') ? 'active' : '' ?>" onclick="filterByCategory('national')" data-filter="national">National</button>
								<button class="filter-btn <?= (isset($_GET['category']) && $_GET['category'] === 'waiting') ? 'active' : '' ?>" onclick="filterByCategory('waiting')" data-filter="waiting">Waiting</button>
								<button class="filter-btn <?= ($status === 'deceased') ? 'active' : '' ?>" onclick="filterByStatus('deceased')" data-filter="deceased">Deceased</button>
							</div>
							<div class="table-barangay-filter">
								<select id="barangayFilter" class="filter-select" onchange="filterByBarangay(this.value)">
									<option value="all">All Barangays</option>
									<?php foreach ($barangays as $b): ?>
										<option value="<?= htmlspecialchars($b['name']) ?>" <?= (isset($_GET['barangay']) && $_GET['barangay'] === $b['name']) ? 'selected' : '' ?>>
											<?= htmlspecialchars($b['name']) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="table-actions">
								<?php if ($status !== 'waiting'): ?>
								<button class="table-btn" onclick="openAddSeniorModal()">
									<span>➕</span>
									<span>Add Senior</span>
								</button>
								<?php endif; ?>
								<button class="table-btn" onclick="exportTable()">
									<span>📥</span>
									<span>Export</span>
								</button>
							</div>
						</div>
					</div>
					<div class="table-container table-scroll seniors-scroll">
                            <table class="modern-table seniors-table">
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
										<th>REMARKS<br><small>(SSS, GSIS, PENSION FROM PRIVATE COMPARISON)</small></th>
										<th>HEALTH CONDITION</th>
										<th>PUROK</th>
										<th>PLACE OF BIRTH</th>
										<th>CELLPHONE #</th>
										<th>LIFE STATUS</th>
										<th>CATEGORY</th>
										<th>VALIDATION STATUS</th>
										<th>VALIDATED</th>
									</tr>
								</thead>
								<tbody id="seniorsTableBody">
									<?php if (!empty($grouped)): ?>
									<?php foreach ($grouped as $barangay => $seniors_in_barangay): ?>
									<tr class="barangay-header"><td colspan="20" style="background: #f9fafb; font-weight: bold; padding: 1rem;">Barangay <?= htmlspecialchars($barangay) ?></td></tr>
									<?php foreach ($seniors_in_barangay as $senior): ?>
									<tr onclick="viewSeniorDetails(<?= $senior['id'] ?>)" style="cursor: pointer;" data-senior-id="<?= $senior['id'] ?>">
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
										<td><?= htmlspecialchars(($senior['health_condition'] && !in_array(strtolower($senior['health_condition']), ['iwan', 'none', 'n/a', 'na', 'not specified', 'unknown', ''])) ? $senior['health_condition'] : 'Not specified') ?></td>
										<td><?= htmlspecialchars($senior['purok'] ?? '') ?></td>
										<td><?= htmlspecialchars($senior['place_of_birth'] ?: '') ?></td>
										<td><?= htmlspecialchars($senior['cellphone'] ?? '') ?></td>
										<td>
											<span class="status-badge <?= $senior['life_status'] === 'living' ? 'validated' : 'deceased' ?>">
												<?= $senior['life_status'] === 'living' ? '👤' : '💀' ?>
												<?= ucfirst($senior['life_status']) ?>
											</span>
										</td>
										<td>
											<span class="status-badge <?= $senior['category'] === 'local' ? 'local' : 'national' ?>">
												<?= $senior['category'] === 'local' ? '🏘️' : '🏛️' ?>
												<?= ucfirst($senior['category']) ?>
											</span>
										</td>
										<td>
							<span class="status-badge <?= $senior['validation_status'] === 'Validated' ? 'validated' : 'pending' ?>">
								<?= $senior['validation_status'] === 'Validated' ? '✅' : '⏳' ?>
								<?= $senior['validation_status'] ?>
							</span>
							<?php if (($senior['validation_status'] ?? '') === 'Validated' && !empty($senior['validation_date'])): ?>
								<br><small style="color: var(--text-secondary); font-size: 0.75rem;"><?= date('M d, Y H:i', strtotime($senior['validation_date'])) ?></small>
							<?php endif; ?>
										</td>
										<td>
											<?= $senior['validation_date'] ? date('M d, Y H:i', strtotime($senior['validation_date'])) : '-' ?>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php endforeach; ?>
									<?php else: ?>
									<tr class="no-data">
										<td colspan="19" style="text-align: center; padding: 2rem; color: var(--gov-text-muted);">
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
		// Handle new senior highlighting and reordering
		document.addEventListener('DOMContentLoaded', function() {
			const urlParams = new URLSearchParams(window.location.search);
			const newSeniorId = urlParams.get('new_senior_id');
			
			if (newSeniorId) {
				highlightAndReorderNewSenior(newSeniorId);
			}
		});

		function highlightAndReorderNewSenior(seniorId) {
			// Find the senior row by data attribute
			const seniorRow = document.querySelector(`tr[data-senior-id="${seniorId}"]`);
			
			if (seniorRow) {
				// Add highlighting class
				seniorRow.classList.add('new-senior-highlight');
				
				// Find the barangay header for this senior
				let barangayHeader = seniorRow.previousElementSibling;
				while (barangayHeader && !barangayHeader.classList.contains('barangay-header')) {
					barangayHeader = barangayHeader.previousElementSibling;
				}
				
				if (barangayHeader) {
					// Find all senior rows in this barangay section
					const barangaySection = barangayHeader.parentNode;
					const allRows = Array.from(barangaySection.children);
					const barangayRows = allRows.filter(row => 
						row.classList.contains('barangay-header') || 
						row.hasAttribute('data-senior-id')
					);
					
					// Find the index of the current senior row
					const currentIndex = barangayRows.indexOf(seniorRow);
					
					if (currentIndex > 0) {
						// Move the senior row to the top of its barangay section (after the header)
						barangaySection.insertBefore(seniorRow, barangayRows[1]);
					}
					
					// Scroll to the highlighted senior
					seniorRow.scrollIntoView({ 
						behavior: 'smooth', 
						block: 'center' 
					});
					
					// Remove highlighting after 5 seconds
					setTimeout(() => {
						seniorRow.classList.remove('new-senior-highlight');
					}, 5000);
				}
			}
		}

		function viewSeniorDetails(id) {
			console.log('Loading senior details for ID:', id);
			// Show the modal with loading state
			const modal = document.getElementById('seniorDetailsModal');
			const content = document.getElementById('seniorDetailsContent');
			
			console.log('Modal element found:', modal);
			console.log('Content element found:', content);
			
			// Show loading state
			content.innerHTML = `
				<div class="loading-state">
					<div class="loading-spinner"></div>
					<p>Loading senior details...</p>
				</div>
			`;
			
			// Show modal with blur and zoom animation
			modal.classList.add('active');
			document.body.classList.add('modal-active');
			document.body.style.overflow = 'hidden';
			
			console.log('Modal classes after adding active:', modal.className);
			console.log('Modal display style:', window.getComputedStyle(modal).display);
			
			// Add click handler to close modal when clicking backdrop
			modal.addEventListener('click', function(e) {
				if (e.target === modal) {
					closeSeniorDetailsModal();
				}
			});
			
			// Add keyboard handler to close modal with Escape key
			const handleKeyDown = function(e) {
				if (e.key === 'Escape') {
					closeSeniorDetailsModal();
					document.removeEventListener('keydown', handleKeyDown);
				}
			};
			document.addEventListener('keydown', handleKeyDown);
			
			// Load senior details via AJAX
			fetch(`senior_details.php?id=${id}&ajax=1&t=${Date.now()}`)
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
			const modal = document.getElementById('seniorDetailsModal');
			modal.classList.remove('active');
			document.body.classList.remove('modal-active');
			document.body.style.overflow = '';
			
			// Remove event listeners
			modal.removeEventListener('click', function(e) {
				if (e.target === modal) {
					closeSeniorDetailsModal();
				}
			});
		}

		function editSenior(id) {
			// Open the edit modal and populate with senior data
			openEditSeniorModal(id);
		}

		function closeEditSeniorModal() {
			document.getElementById('editSeniorModal').classList.remove('active');
			document.body.classList.remove('modal-active');
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
			
			// Check if senior is deceased - prevent transfer
			const lifeStatus = document.getElementById('editLifeStatus').value;
			if (lifeStatus === 'deceased') {
				alert('Cannot transfer a deceased senior. Only living seniors can be transferred.');
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
			// Refresh CSRF before showing modal
			refreshCsrfInputs();
			
			// Reset radio/other input requirements
			const otherRadio = document.querySelector('input[name="transfer_reason"][value="other"]');
			const otherInput = document.querySelector('input[name="transfer_reason_other"]');
			if (otherInput) {
				otherInput.required = false;
				otherInput.disabled = true;
				otherInput.value = '';
			}
			// Wire change handlers once
			if (openTransferModal._wired !== true) {
				document.querySelectorAll('input[name="transfer_reason"]').forEach(r => {
					r.addEventListener('change', function() {
						if (this.value === 'other') {
							if (otherInput) { otherInput.disabled = false; otherInput.required = true; otherInput.focus(); }
						} else {
							if (otherInput) { otherInput.required = false; otherInput.disabled = true; otherInput.value = ''; }
						}
					});
				});
				// Client-side validation for required fields
				const form = document.getElementById('transferForm');
				if (form && !form.dataset.validationWired) {
					form.dataset.validationWired = 'true';
					form.addEventListener('submit', function(e) {
						const reason = (document.querySelector('input[name="transfer_reason"]:checked') || {}).value || '';
						const otherVal = (document.querySelector('input[name="transfer_reason_other"]') || {}).value || '';
						const addr = (document.getElementById('newAddress') || {}).value || '';
						const date = (document.getElementById('effectiveDate') || {}).value || '';
						if (!reason || !addr || !date || (reason === 'other' && !otherVal.trim())) {
							e.preventDefault();
							alert('Please fill in all required transfer information fields.');
							return false;
						}
					});
				}
				openTransferModal._wired = true;
			}
			
			// Set default effective date to today
			const today = new Date().toISOString().split('T')[0];
			document.getElementById('effectiveDate').value = today;
			
			// Show the modal
			document.getElementById('transferModal').classList.add('active');
			document.body.classList.add('modal-active');
			document.body.style.overflow = 'hidden';
		}

		function closeTransferModal() {
			document.getElementById('transferModal').classList.remove('active');
			document.body.classList.remove('modal-active');
			document.body.style.overflow = '';
			
			// Reset form
			document.getElementById('transferForm').reset();
		}

		function openDeceasedModal(seniorId) {
			// Set the senior ID
			document.getElementById('deceasedSeniorId').value = seniorId;
			// Refresh CSRF before showing modal
			refreshCsrfInputs();
			
			// Set default death date to today
			const today = new Date().toISOString().split('T')[0];
			document.getElementById('deathDate').value = today;
			
			// Show the modal
			document.getElementById('deceasedModal').classList.add('active');
			document.body.classList.add('modal-active');
			document.body.style.overflow = 'hidden';
		}

		function closeDeceasedModal() {
			document.getElementById('deceasedModal').classList.remove('active');
			document.body.classList.remove('modal-active');
			document.body.style.overflow = '';
			
			// Reset form
			document.getElementById('deceasedForm').reset();
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

		// Filter by category (Local, National, Waiting)
		function filterByCategory(category) {
			const url = new URL(window.location.href);
			
			if (category === 'all') {
				url.searchParams.delete('category');
				// Clear status filter when showing all
				url.searchParams.delete('status');
			} else {
				url.searchParams.set('category', category);
				// Clear status filter when filtering by category
				url.searchParams.delete('status');
			}
			
			window.location.href = url.toString();
		}
		
		// Filter by life status (deceased)
		function filterByStatus(status) {
			const url = new URL(window.location.href);
			
			if (status === 'deceased') {
				url.searchParams.set('status', 'deceased');
			} else {
				url.searchParams.delete('status');
			}
			
			// Clear category filter when filtering by status
			url.searchParams.delete('category');
			
			window.location.href = url.toString();
		}
		
		// Filter by barangay
		function filterByBarangay(barangay) {
			const url = new URL(window.location.href);
			
			if (barangay === 'all') {
				url.searchParams.delete('barangay');
			} else {
				url.searchParams.set('barangay', barangay);
			}
			
			window.location.href = url.toString();
		}
		
		// Export table as PDF
		function exportTable() {
			const url = new URL(window.location.href);
			url.searchParams.set('export', 'pdf');
			// Preserve all current filter parameters
			window.location.href = url.toString();
		}

	</script>

	<!-- Add Senior Modal -->
	<div class="modal-overlay" id="addSeniorModal" style="display:none;">
		<div class="modal" style="width: 1000px; max-width: 95%; max-height: 95vh; overflow-y: auto; animation: zoomIn 0.3s forwards;">
			<div class="modal-header">
				<h2 class="modal-title">Add Senior</h2>
				<button class="modal-close" onclick="closeAddSeniorModal()" aria-label="Close add senior form">&times;</button>
			</div>
			<div class="modal-body">
				<?php if ($message && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'create'): ?>
				<div class="alert <?= message_is_error($message) ? 'alert-error' : 'alert-success' ?>" style="margin-bottom: 1rem;">
					<div class="alert-icon">
						<i class="fas fa-<?= message_is_error($message) ? 'exclamation-circle' : 'check-circle' ?>"></i>
					</div>
					<div class="alert-content">
						<strong><?= message_is_error($message) ? 'Error!' : 'Success!' ?></strong>
						<p><?= htmlspecialchars($message) ?></p>
					</div>
				</div>
				<?php endif; ?>
				<form id="addSeniorForm" method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
					<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
					<input type="hidden" name="op" value="create">
					<input type="hidden" name="has_waiting_documents_fields" value="1">

					<!-- Form Progress -->
					<div class="form-progress">
						<div class="progress-bar">
							<div class="progress-fill"></div>
						</div>
						<div class="progress-text">0 of 0 required fields completed</div>
					</div>

					<!-- Basic Information Section -->
					<div class="form-section">
						<h3 class="section-title">Basic Information</h3>
						<div class="form-row">
							<div class="form-group modern-form-group">
								<label for="first_name" class="form-label modern-label">
									<span class="label-icon">👤</span>
									<span class="label-text">First Name</span>
								</label>
								<input
									type="text"
									name="first_name"
									id="first_name"
									class="form-input modern-input"
									placeholder="Enter first name"
									required
								>
								<div class="input-focus-border"></div>
							</div>
							

							<div class="form-group modern-form-group">
								<label for="middle_name" class="form-label modern-label">
									<span class="label-icon">👤</span>
									<span class="label-text">Middle Name</span>
								</label>
								<input
									type="text"
									name="middle_name"
									id="middle_name"
									class="form-input modern-input"
									placeholder="Enter middle name"
								>
								<div class="input-focus-border"></div>
							</div>

							<div class="form-group modern-form-group">
								<label for="last_name" class="form-label modern-label">
									<span class="label-icon">👤</span>
									<span class="label-text">Last Name</span>
								</label>
								<input
									type="text"
									name="last_name"
									id="last_name"
									class="form-input modern-input"
									placeholder="Enter last name"
									required
								>
								<div class="input-focus-border"></div>
							</div>

							<div class="form-group modern-form-group">
								<label for="ext_name" class="form-label modern-label">
									<span class="label-icon">🏷️</span>
									<span class="label-text">Extension</span>
								</label>
								<input
									type="text"
									name="ext_name"
									id="ext_name"
									class="form-input modern-input"
									placeholder="Enter extension (e.g., Jr., Sr.)"
								>
								<div class="input-focus-border"></div>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group modern-form-group">
								<label for="age" class="form-label modern-label">
									<span class="label-icon">🎂</span>
									<span class="label-text">Age</span>
								</label>
								<input
									type="number"
									name="age"
									id="age"
									class="form-input modern-input"
									placeholder="Age"
									min="60"
									max="120"
									required
								>
								<div class="input-focus-border"></div>
							</div>

							<div class="form-group modern-form-group">
								<label for="date_of_birth" class="form-label modern-label">
									<span class="label-icon">📅</span>
									<span class="label-text">Date of Birth</span>
								</label>
								<input
									type="date"
									name="date_of_birth"
									id="date_of_birth"
									class="form-input modern-input"
									onchange="calculateAge()"
								>
								<div class="input-focus-border"></div>
							</div>

							<div class="form-group modern-form-group">
								<label for="sex" class="form-label modern-label">
									<span class="label-icon">⚥</span>
									<span class="label-text">Sex</span>
								</label>
								<select name="sex" id="sex" class="form-input modern-input" required>
									<option value="">Select sex</option>
									<option value="male">Male</option>
									<option value="female">Female</option>
									<option value="lgbtq">LGBTQ+</option>
								</select>
								<div class="input-focus-border"></div>
							</div>
						</div>

						<div class="form-row">
							<div class="form-group modern-form-group">
								<label for="place_of_birth" class="form-label modern-label">
									<span class="label-icon">🏠</span>
									<span class="label-text">Place of Birth</span>
								</label>
								<input
									type="text"
									name="place_of_birth"
									id="place_of_birth"
									class="form-input modern-input"
									placeholder="Enter place of birth"
								>
								<div class="input-focus-border"></div>
							</div>

							<div class="form-group modern-form-group">
								<label for="civil_status" class="form-label modern-label">
									<span class="label-icon">💍</span>
									<span class="label-text">Civil Status</span>
								</label>
								<select name="civil_status" id="civil_status" class="form-input modern-input" required>
									<option value="">Select civil status</option>
									<option value="single">Single</option>
									<option value="married">Married</option>
									<option value="widowed">Widowed</option>
									<option value="separated">Separated</option>
									<option value="divorced">Divorced</option>
								</select>
								<div class="input-focus-border"></div>
							</div>

							<div class="form-group modern-form-group">
								<label for="educational_attainment" class="form-label modern-label">
									<span class="label-icon">🎓</span>
									<span class="label-text">Educational Attainment</span>
								</label>
								<select name="educational_attainment" id="educational_attainment" class="form-input modern-input" required>
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
							<!-- OSCA ID is now auto-assigned on create; field removed -->
							
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

						<div id="waitingDocumentsWrapper" class="form-row waiting-documents">
							<div class="form-group document-group">
								<span class="form-label" style="font-weight: 600;">Required Documents</span>
								<div class="document-checkboxes">
									<label class="checkbox-label">
										<input type="checkbox" name="doc_birth_certificate" id="doc_birth_certificate" class="checkbox-input waiting-doc-checkbox" value="1" disabled>
										<span class="checkbox-custom"></span>
										Birth Certificate
									</label>
									<label class="checkbox-label">
										<input type="checkbox" name="doc_marriage_contract" id="doc_marriage_contract" class="checkbox-input waiting-doc-checkbox" value="1" disabled>
										<span class="checkbox-custom"></span>
										Marriage Contract
									</label>
									<label class="checkbox-label">
										<input type="checkbox" name="doc_valid_id" id="doc_valid_id" class="checkbox-input waiting-doc-checkbox" value="1" disabled>
										<span class="checkbox-custom"></span>
										Valid ID
									</label>
								</div>
								<small class="help-text">Enable the waiting list checkbox first to mark missing documents.</small>
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

					<!-- Benefits Section -->
					<div class="form-section">
						<h3 class="section-title">Benefits</h3>
						<div class="form-row">
							<div class="form-group checkbox-group">
								<label class="checkbox-label">
									<input
										type="checkbox"
										name="benefits_received"
										id="benefits_received"
										class="checkbox-input"
										value="1"
										checked
									>
									<span class="checkbox-custom"></span>
									Benefits Received
								</label>
							</div>
						</div>
					</div>

					<div class="form-actions">
						<button type="submit" class="btn btn-primary modern-btn">
							<span class="btn-text">Add Senior</span>
							<span class="btn-icon">💾</span>
							<div class="btn-loading">
								<div class="loading-spinner"></div>
								<span>Adding Senior...</span>
							</div>
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
			document.body.classList.add('modal-active');
			document.body.style.overflow = 'hidden';
			// Remove any transform or position styles to prevent movement
			const modalContent = modal.querySelector('.modal');
			if (modalContent) {
				modalContent.style.transform = '';
				modalContent.style.left = '';
				modalContent.style.top = '';
			}
			// Apply blur to the main content area, not just content-body
			const mainContent = document.querySelector('main.content');
			if (mainContent) {
				mainContent.style.filter = 'blur(0)'; // Remove blur on open modal to show modal clearly
			}
			// Set default values for checkboxes
			const benefitsCheckbox = document.getElementById('benefits_received');
			if (benefitsCheckbox) {
				benefitsCheckbox.checked = true;
			}
			// If there's an error message, keep modal open and scroll to top to show error
			const errorAlert = modal.querySelector('.alert-error, .alert');
			if (errorAlert) {
				errorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}
		
		// Keep modal open if there's an error after form submission
		<?php if ($message && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['op'] ?? '') === 'create' && (strpos(strtolower($message), 'error') !== false || strpos(strtolower($message), 'duplicate') !== false || strpos(strtolower($message), 'failed') !== false)): ?>
		document.addEventListener('DOMContentLoaded', function() {
			// Open modal if there's an error
			openAddSeniorModal();
		});
		<?php endif; ?>
		
		// Custom form validation for add senior form
		function validateAddSeniorForm(form, event) {
			// Prevent app.js from interfering
			if (event) {
				event.stopPropagation();
			}
			
			// Get required fields
			const requiredFields = {
				'first_name': form.querySelector('[name="first_name"]'),
				'last_name': form.querySelector('[name="last_name"]'),
				'age': form.querySelector('[name="age"]'),
				'sex': form.querySelector('[name="sex"]'),
				'civil_status': form.querySelector('[name="civil_status"]'),
				'educational_attainment': form.querySelector('[name="educational_attainment"]'),
				'barangay': form.querySelector('[name="barangay"]')
			};
			
			let isValid = true;
			let firstErrorField = null;
			
			// Check each required field
			for (const [fieldName, field] of Object.entries(requiredFields)) {
				if (!field) continue;
				
				const value = field.value ? field.value.trim() : '';
				if (!value) {
					isValid = false;
					if (!firstErrorField) {
						firstErrorField = field;
					}
					// Highlight error
					const group = field.closest('.form-group');
					if (group) {
						group.classList.add('error');
					}
				} else {
					// Remove error highlighting
					const group = field.closest('.form-group');
					if (group) {
						group.classList.remove('error');
					}
				}
			}
			
			// Check age is at least 60
			const ageField = requiredFields['age'];
			if (ageField && ageField.value) {
				const age = parseInt(ageField.value);
				if (age < 60) {
					isValid = false;
					if (!firstErrorField) {
						firstErrorField = ageField;
					}
					alert('Age must be at least 60.');
					if (event) event.preventDefault();
				return false;
				}
			}
			
			// Ensure category or waiting list is selected
			const categoryField = form.querySelector('[name="category"]');
			const waitingListField = form.querySelector('[name="waiting_list"]');
			const hasCategory = categoryField && categoryField.value;
			const hasWaitingList = waitingListField && waitingListField.checked;
			
			if (!hasCategory && !hasWaitingList) {
				// Default to local if neither is set
				if (categoryField) {
					categoryField.value = 'local';
				}
			}
			
			if (!isValid) {
				if (firstErrorField) {
					firstErrorField.focus();
					firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
				alert('Please fill in all required fields.');
				if (event) event.preventDefault();
				return false;
			}
			
			// Remove any error classes that might have been added
			form.querySelectorAll('.form-group.error').forEach(group => {
				group.classList.remove('error');
			});
			
			// Log form submission for debugging
			console.log('Form validation passed, submitting form...');
			console.log('CSRF token in form:', form.querySelector('[name="csrf"]')?.value?.substring(0, 20) + '...');
			
			// Allow form to submit - return true to proceed with normal submission
			return true;
		}
		
		// Override app.js form handler for add senior form and wire waiting-list documents
		document.addEventListener('DOMContentLoaded', function() {
			const addSeniorForm = document.getElementById('addSeniorForm');
			if (addSeniorForm) {
				// Remove any existing submit handlers from app.js
				const newForm = addSeniorForm.cloneNode(true);
				addSeniorForm.parentNode.replaceChild(newForm, addSeniorForm);
				
				// Add our own submit handler
				const form = document.getElementById('addSeniorForm');
				form.addEventListener('submit', function(e) {
					const result = validateAddSeniorForm(this, e);
					if (!result) {
						e.preventDefault();
						e.stopPropagation();
						return false;
					}
					// Allow form to submit normally
					return true;
				}, true); // Use capture phase to run before other handlers

				// Hook up waiting list documents enabling logic
				const waitingCheckbox = form.querySelector('#waiting_list');
				const docCheckboxes = form.querySelectorAll('.waiting-doc-checkbox');
				const wrapper = document.getElementById('waitingDocumentsWrapper');

				function updateWaitingDocumentsState() {
					const enabled = !!(waitingCheckbox && waitingCheckbox.checked);
					docCheckboxes.forEach(cb => {
						cb.disabled = !enabled;
						if (!enabled) {
							cb.checked = false;
						}
					});
					if (wrapper) {
						wrapper.style.opacity = enabled ? '1' : '0.6';
					}
				}

				if (waitingCheckbox) {
					waitingCheckbox.addEventListener('change', updateWaitingDocumentsState);
					// Initialize on load
					updateWaitingDocumentsState();
				}
			}
		});

		function closeAddSeniorModal() {
			const modal = document.getElementById('addSeniorModal');
			modal.style.animation = 'zoomOut 0.3s forwards';
			setTimeout(() => {
				modal.style.display = 'none';
				modal.classList.remove('active');
				document.body.classList.remove('modal-active');
				document.body.style.overflow = '';
                const mainContent = document.querySelector('main.content');
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
				<h2 class="modal-title">Edit Senior Profile</h2>
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
						<label for="editOscaIdNo" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">OSCA ID Number</label>
						<input type="text" id="editOscaIdNo" name="osca_id_no" readonly style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;">
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

				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: center;">
					<div style="display: flex; flex-direction: column; gap: 0.5rem;">
						<label for="editLifeStatus" style="font-weight: 600;">Life Status</label>
						<select id="editLifeStatus" name="life_status" style="padding: 0.35rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="living">Living</option>
							<option value="deceased">Deceased</option>
						</select>
					</div>
					<div style="display: flex; flex-direction: column; gap: 0.5rem;">
						<label for="editCategory" style="font-weight: 600;">Category</label>
						<select id="editCategory" name="category" style="padding: 0.35rem; border: 1px solid #d1d5db; border-radius: 6px;">
							<option value="local">Local</option>
							<option value="national">National</option>
						</select>
					</div>
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
					<div style="display: flex; gap: 1rem;">
						<button type="button" onclick="markAsDeceased()" style="padding: 0.5rem 1rem; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
							<i class="fas fa-cross"></i>
							Mark as Deceased
						</button>
						<button type="button" id="transferSeniorBtn" onclick="transferSenior()" style="padding: 0.5rem 1rem; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
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
				<h2 class="modal-title">📦 Transfer Details</h2>
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
					<input type="date" id="effectiveDate" name="effective_date" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 1px solid #d1d5db; background: transparent; outline: none; font-size: 1rem;" max="<?= $todayDate ?>">
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
				<h2 class="modal-title">💀 Death Information</h2>
				<button onclick="closeDeceasedModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;" aria-label="Close deceased form">&times;</button>
			</div>
			<form id="deceasedForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="display: flex; flex-direction: column; gap: 1.5rem;">
				<input type="hidden" name="csrf" value="<?= $csrf ?>">
				<input type="hidden" name="op" value="mark_deceased">
				<input type="hidden" name="id" id="deceasedSeniorId">
				
				<div>
					<div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 1rem;">Date of Death:</div>
					<input type="date" id="deathDate" name="death_date" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" max="<?= $todayDate ?>">
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
			// Refresh CSRF token for safety before opening modal
			refreshCsrfInputs();
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
					const categorySelect = document.getElementById('editCategory');
					if (categorySelect) {
						const normalizedCategory = (senior.category || '').toLowerCase();
						const categoryOption = Array.from(categorySelect.options).find(option => option.value === normalizedCategory);
						categorySelect.value = categoryOption ? categoryOption.value : 'local';
					}
					document.getElementById('editLifeStatus').value = senior.life_status || 'living';

					// Disable transfer button if senior is deceased
					const transferBtn = document.getElementById('transferSeniorBtn');
					if (transferBtn) {
						if (senior.life_status === 'deceased') {
							transferBtn.disabled = true;
							transferBtn.style.opacity = '0.5';
							transferBtn.style.cursor = 'not-allowed';
							transferBtn.title = 'Cannot transfer a deceased senior';
						} else {
							transferBtn.disabled = false;
							transferBtn.style.opacity = '1';
							transferBtn.style.cursor = 'pointer';
							transferBtn.title = '';
						}
					}

					// Show modal
					document.getElementById('editSeniorModal').classList.add('active');
					document.body.classList.add('modal-active');
					document.body.style.overflow = 'hidden';
				})
				.catch(() => {
					alert('Error loading senior data.');
				});
		}
		
		// Refresh CSRF helper to keep hidden inputs current for all forms on this page
		async function refreshCsrfInputs() {
			try {
				const res = await fetch('seniors.php?action=csrf', { credentials: 'same-origin' });
				const data = await res.json();
				if (data && data.csrf) {
					document.querySelectorAll('input[name="csrf"]').forEach(inp => { inp.value = data.csrf; });
				}
			} catch (_) {}
		}

		async function handleEditSeniorSubmit(event) {
			event.preventDefault();
			const form = event.target;
			const submitButton = form.querySelector('button[type="submit"]');
			let originalButtonHtml = '';
			
			if (submitButton) {
				originalButtonHtml = submitButton.innerHTML;
				submitButton.disabled = true;
				submitButton.innerHTML = '<span class="loading-spinner"></span> Updating...';
			}

			try {
				const formData = new FormData(form);
				const response = await fetch(form.action, {
					method: 'POST',
					body: formData,
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
					credentials: 'same-origin'
				});

				const contentType = response.headers.get('content-type') || '';
				if (!contentType.includes('application/json')) {
					window.location.reload();
					return;
				}

				const payload = await response.json();

				if (!payload.success) {
					showInlineAlert(payload.message || 'Failed to update senior.', 'error');
					await refreshCsrfInputs();
					return;
				}

				await refreshCsrfInputs();
				if (payload.id) {
					await updateSeniorRowDisplay(payload.id);
				}
				window.localStorage.setItem('senior-category-updated', Date.now().toString());
				closeEditSeniorModal();
				showInlineAlert(payload.message || 'Senior updated successfully.', 'success');
			} catch (error) {
				console.error('Update senior failed', error);
				showInlineAlert('An unexpected error occurred while updating the senior.', 'error');
			} finally {
				if (submitButton) {
					submitButton.disabled = false;
					submitButton.innerHTML = originalButtonHtml;
				}
			}
		}

		async function updateSeniorRowDisplay(seniorId) {
			const row = document.querySelector(`tr[data-senior-id="${seniorId}"]`);
			if (!row) {
				return;
			}

			try {
				const response = await fetch(`seniors.php?action=get_senior&id=${encodeURIComponent(seniorId)}&t=${Date.now()}`, { credentials: 'same-origin' });
				const data = await response.json();
				if (!data.success || !data.senior) {
					return;
				}

				const senior = data.senior;
				const cells = row.cells;
				if (!cells || cells.length < 19) {
					return;
				}

				cells[0].textContent = senior.last_name || '';
				cells[1].textContent = senior.first_name || '';
				cells[2].textContent = senior.middle_name || '';
				cells[3].textContent = senior.ext_name || '';
				cells[4].textContent = senior.barangay || '';
				cells[5].textContent = senior.age ? parseInt(senior.age, 10) : '';
				cells[6].textContent = mapSexDisplay(senior.sex);
				cells[7].textContent = senior.civil_status || '';
				cells[8].textContent = formatDateOnly(senior.date_of_birth);
				cells[9].textContent = senior.osca_id_no || '';
				cells[10].textContent = senior.remarks || '';
				cells[11].textContent = formatHealthCondition(senior.health_condition);
				cells[12].textContent = senior.purok || '';
				cells[13].textContent = senior.place_of_birth || '';
				cells[14].textContent = senior.cellphone || '';
				cells[15].innerHTML = renderLifeStatusBadge(senior.life_status);
				cells[16].innerHTML = renderCategoryBadge(senior.category);
				cells[17].innerHTML = renderValidationStatus(senior.validation_status, senior.validation_date);
				cells[18].textContent = formatDateTime(senior.validation_date) || '-';

				row.classList.add('new-senior-highlight');
				setTimeout(() => row.classList.remove('new-senior-highlight'), 4000);
				row.scrollIntoView({ behavior: 'smooth', block: 'center' });
			} catch (error) {
				console.error('Failed to refresh senior row', error);
			}
		}

		function mapSexDisplay(sex) {
			switch ((sex || '').toLowerCase()) {
				case 'male':
					return 'Male';
				case 'female':
					return 'Female';
				case 'lgbtq':
					return 'LGBTQ+';
				default:
					return 'Not specified';
			}
		}

		function formatHealthCondition(value) {
			const normalized = (value || '').trim();
			if (!normalized) {
				return 'Not specified';
			}
			const lower = normalized.toLowerCase();
			const placeholders = ['iwan', 'none', 'n/a', 'na', 'not specified', 'unknown'];
			return placeholders.includes(lower) ? 'Not specified' : normalized;
		}

		function formatDateOnly(value) {
			if (!value) return '';
			const safeValue = value.includes('T') ? value : `${value}T00:00:00`;
			const date = new Date(safeValue);
			if (Number.isNaN(date.getTime())) {
				return '';
			}
			return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
		}

		function formatDateTime(value) {
			if (!value) return '';
			const safeValue = value.replace(' ', 'T');
			const date = new Date(safeValue);
			if (Number.isNaN(date.getTime())) {
				return '';
			}
			const datePart = date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
			const timePart = date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
			return `${datePart} ${timePart}`;
		}

		function renderLifeStatusBadge(status) {
			const normalized = (status || 'living').toLowerCase();
			if (normalized === 'deceased') {
				return `<span class="status-badge deceased">💀 Deceased</span>`;
			}
			return `<span class="status-badge validated">👤 Living</span>`;
		}

		function renderCategoryBadge(category) {
			const normalized = (category || '').toLowerCase();
			switch (normalized) {
				case 'national':
					return `<span class="status-badge national">🏛️ National</span>`;
				case 'waiting':
					return `<span class="status-badge pending">⏳ Waiting</span>`;
				case 'transferred':
					return `<span class="status-badge pending">🚚 Transferred</span>`;
				default:
					return `<span class="status-badge local">🏘️ Local</span>`;
			}
		}

		function renderValidationStatus(status, validationDate) {
			const isValidated = (status || '').toLowerCase() === 'validated';
			const label = isValidated ? 'Validated' : (status || 'Not Validated');
			const emoji = isValidated ? '✅' : '⏳';
			let html = `<span class="status-badge ${isValidated ? 'validated' : 'pending'}">${emoji} ${label}</span>`;
			if (isValidated && validationDate) {
				const formatted = formatDateTime(validationDate);
				if (formatted) {
					html += `<br><small style="color: var(--text-secondary); font-size: 0.75rem;">${formatted}</small>`;
				}
			}
			return html;
		}

		function showInlineAlert(message, type = 'success') {
			const container = document.querySelector('.main-content-area');
			if (!container) return;

			const existing = container.querySelector('.alert.dynamic-alert');
			if (existing) {
				existing.remove();
			}

			const wrapper = document.createElement('div');
			wrapper.className = `alert ${type === 'success' ? 'alert-success' : 'alert-error'} dynamic-alert`;
			wrapper.innerHTML = `
				<div class="alert-icon">
					<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
				</div>
				<div class="alert-content">
					<strong>${type === 'success' ? 'Success!' : 'Error!'}</strong>
					<p>${escapeHtml(message || '')}</p>
				</div>
			`;

			container.insertBefore(wrapper, container.firstChild);

			setTimeout(() => {
				if (wrapper && wrapper.parentNode) {
					wrapper.parentNode.removeChild(wrapper);
				}
			}, 5000);
		}

		function escapeHtml(value) {
			return (value || '').toString().replace(/[&<>"']/g, match => {
				const replacements = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					'\'': '&#39;'
				};
				return replacements[match] || match;
			});
		}

		// Refresh CSRF on page load and wire up AJAX form submission
		document.addEventListener('DOMContentLoaded', function() {
			refreshCsrfInputs();
			// Refresh every 5 minutes to keep session fresh for long-lived pages
			setInterval(refreshCsrfInputs, 5 * 60 * 1000);

			const editForm = document.getElementById('editSeniorForm');
			if (editForm && !editForm.dataset.ajaxBound) {
				editForm.dataset.ajaxBound = 'true';
				editForm.addEventListener('submit', handleEditSeniorSubmit);
			}
		});
	</script>
</body>
</html>

