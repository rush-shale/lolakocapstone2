<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$seniorId = (int)($_GET['id'] ?? 0);
if ($seniorId <= 0) {
    http_response_code(400);
    echo 'Invalid senior ID';
    exit;
}

// Ensure death table exists (view page should not fail if migrations haven't been run)
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

$stmtSenior = $pdo->prepare('SELECT * FROM seniors WHERE id = ?');
$stmtSenior->execute([$seniorId]);
$senior = $stmtSenior->fetch();
if (!$senior) {
    http_response_code(404);
    echo 'Senior not found';
    exit;
}

$stmtDeath = $pdo->prepare('SELECT * FROM senior_deaths WHERE senior_id = ?');
$stmtDeath->execute([$seniorId]);
$death = $stmtDeath->fetch();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Death Details | <?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes zoomOut { from { transform: scale(1); opacity: 1; } to { transform: scale(0.95); opacity: 0; } }
        .zoom-container { max-width: 560px; margin: 4rem auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); padding: 1.5rem; animation: zoomIn .3s ease-out both; }
        .zoom-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
        .zoom-title { font-size:1.25rem; font-weight:800; margin:0; }
        .zoom-sub { margin:0; color:#6b7280; font-size:0.875rem; }
        .detail { display:flex; align-items:center; gap:.5rem; padding:.5rem 0; border-bottom:1px solid #f3f4f6; }
        .detail:last-child { border-bottom:none; }
        .label { width:160px; color:#6b7280; font-weight:600; }
        .value { flex:1; font-weight:600; color:#111827; }
        .muted { color:#9ca3af; font-weight:500; }
        .actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }
        .btn { padding:.5rem .9rem; border-radius:8px; border:1px solid #d1d5db; cursor:pointer; font-weight:600; }
        .btn-secondary { background:#f3f4f6; }
        .btn-primary { background:#2563eb; color:#fff; border:none; }
    </style>
    <script>
        function goBack() { history.back(); }
        function editDeath() { window.location.href = 'mark_deceased.php?id=<?= (int)$seniorId ?>'; }
    </script>
    </head>
<body style="background:#f9fafb;">
    <div class="zoom-container">
        <div class="zoom-header">
            <div>
                <h1 class="zoom-title">Death Information</h1>
                <p class="zoom-sub"><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?> • <?= htmlspecialchars($senior['barangay']) ?></p>
            </div>
            <div class="actions">
                <button class="btn btn-secondary" onclick="goBack()"><i class="fas fa-arrow-left"></i> Back</button>
                <button class="btn btn-primary" onclick="editDeath()"><i class="fas fa-edit"></i> Edit</button>
            </div>
        </div>
        <div class="detail">
            <div class="label">Date of Death</div>
            <div class="value"><?= $death && $death['date_of_death'] ? date('M d, Y', strtotime($death['date_of_death'])) : '<span class="muted">Not specified</span>' ?></div>
        </div>
        <div class="detail">
            <div class="label">Time of Death</div>
            <div class="value"><?= $death && $death['time_of_death'] ? date('g:i A', strtotime($death['time_of_death'])) : '<span class="muted">Not specified</span>' ?></div>
        </div>
        <div class="detail">
            <div class="label">Place of Death</div>
            <div class="value"><?= $death && $death['place_of_death'] ? htmlspecialchars($death['place_of_death']) : '<span class="muted">Not specified</span>' ?></div>
        </div>
        <div class="detail">
            <div class="label">Cause of Death</div>
            <div class="value"><?= $death && $death['cause_of_death'] ? htmlspecialchars($death['cause_of_death']) : '<span class="muted">Not specified</span>' ?></div>
        </div>
    </div>
</body>
</html>


