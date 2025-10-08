<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$seniorId = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($seniorId <= 0) {
    http_response_code(400);
    echo 'Invalid senior ID';
    exit;
}

// Ensure death details table exists
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

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        $message = 'Invalid session token';
    } else {
        $dateOfDeath = trim($_POST['date_of_death'] ?? '');
        $timeOfDeath = trim($_POST['time_of_death'] ?? '');
        $placeOfDeath = trim($_POST['place_of_death'] ?? '');
        $causeOfDeath = trim($_POST['cause_of_death'] ?? '');

        // Upsert: if record exists for this senior, update it; else insert
        $existingId = (int)$pdo->prepare('SELECT id FROM senior_deaths WHERE senior_id = ?')->execute([$seniorId]) ? (int)$pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn() : 0;

        $stmtCheck = $pdo->prepare('SELECT id FROM senior_deaths WHERE senior_id = ?');
        $stmtCheck->execute([$seniorId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $stmt = $pdo->prepare('UPDATE senior_deaths SET date_of_death = ?, time_of_death = ?, place_of_death = ?, cause_of_death = ? WHERE id = ?');
            $stmt->execute([
                $dateOfDeath ?: null,
                $timeOfDeath ?: null,
                $placeOfDeath ?: null,
                $causeOfDeath ?: null,
                (int)$existing['id']
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO senior_deaths (senior_id, date_of_death, time_of_death, place_of_death, cause_of_death) VALUES (?,?,?,?,?)');
            $stmt->execute([
                $seniorId,
                $dateOfDeath ?: null,
                $timeOfDeath ?: null,
                $placeOfDeath ?: null,
                $causeOfDeath ?: null
            ]);
        }

        // Mark senior as deceased
        $pdo->prepare('UPDATE seniors SET life_status = ? WHERE id = ?')->execute(['deceased', $seniorId]);

        // Redirect back to All Seniors with success
        header('Location: seniors.php?success=deceased_marked');
        exit;
    }
}

// Load senior basic info
$stmtSenior = $pdo->prepare('SELECT * FROM seniors WHERE id = ?');
$stmtSenior->execute([$seniorId]);
$senior = $stmtSenior->fetch();
if (!$senior) {
    http_response_code(404);
    echo 'Senior not found';
    exit;
}

// Load existing death details if any
$stmtDeath = $pdo->prepare('SELECT * FROM senior_deaths WHERE senior_id = ?');
$stmtDeath->execute([$seniorId]);
$death = $stmtDeath->fetch();

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mark as Deceased | <?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes zoomOut { from { transform: scale(1); opacity: 1; } to { transform: scale(0.95); opacity: 0; } }
        /* Focused zoom-in layout */
        .zoom-container { max-width: 560px; margin: 4rem auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); padding: 1.5rem; animation: zoomIn .3s ease-out both; }
        .zoom-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
        .zoom-title { font-size:1.25rem; font-weight:800; margin:0; }
        .zoom-sub { margin:0; color:#6b7280; font-size:0.875rem; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        @media (max-width:640px){ .form-grid { grid-template-columns:1fr; } }
        .actions { display:flex; justify-content:flex-end; gap:.75rem; margin-top:1rem; }
        .btn { padding:.5rem .9rem; border-radius:8px; border:1px solid #d1d5db; cursor:pointer; font-weight:600; }
        .btn-primary { background:#2563eb; color:#fff; border:none; }
        .btn-secondary { background:#f3f4f6; }
        .field label { display:block; font-weight:600; margin-bottom:.25rem; }
        .field input, .field textarea { width:100%; padding:.6rem; border:1px solid #d1d5db; border-radius:8px; }
    </style>
    <script>
        function goBack() { history.back(); }
    </script>
</head>
<body style="background:#f9fafb;">
    <div class="zoom-container">
        <div class="zoom-header">
            <div>
                <h1 class="zoom-title">Mark as Deceased</h1>
                <p class="zoom-sub"><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?> • <?= htmlspecialchars($senior['barangay']) ?></p>
            </div>
            <button class="btn btn-secondary" onclick="goBack()"><i class="fas fa-arrow-left"></i> Back</button>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="id" value="<?= (int)$seniorId ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="date_of_death">Date of Death</label>
                    <input type="date" id="date_of_death" name="date_of_death" value="<?= htmlspecialchars($death['date_of_death'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="time_of_death">Time of Death</label>
                    <input type="time" id="time_of_death" name="time_of_death" value="<?= htmlspecialchars($death['time_of_death'] ?? '') ?>">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="place_of_death">Place of Death</label>
                    <input type="text" id="place_of_death" name="place_of_death" value="<?= htmlspecialchars($death['place_of_death'] ?? '') ?>">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="cause_of_death">Cause of Death</label>
                    <textarea id="cause_of_death" name="cause_of_death" rows="3"><?= htmlspecialchars($death['cause_of_death'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="goBack()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save & Mark Deceased</button>
            </div>
        </form>
    </div>
</body>
</html>


