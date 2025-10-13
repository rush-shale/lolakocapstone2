<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

// Fetch all local seniors who are living
$localSeniorsList = $pdo->query("
    SELECT s.*, s.barangay as barangay_name 
    FROM seniors s 
    WHERE s.category = 'local' AND s.life_status = 'living'
    ORDER BY s.created_at DESC
")->fetchAll();

$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Local Seniors | SeniorCare Information System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
</head>
<body>
    <?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1 class="content-title">Local Seniors</h1>
            <p class="content-subtitle">Manage local category senior citizen records</p>
        </header>

        <div class="content-body">
            <div class="main-content-area">
                <div class="card">
                    <div class="card-header" style="display:flex; justify-content: space-between; align-items:center;">
                        <h2 class="card-title">Local Seniors List</h2>
                        <div>
                            <button class="button secondary" onclick="handleCancel()">Close</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Barangay</th>
                                    <th>Validation Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($localSeniorsList as $senior): ?>
                                <tr>
                                    <td><?= (int)$senior['id'] ?></td>
                                    <td><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></td>
                                    <td><?= (int)$senior['age'] ?></td>
                                    <td><?= htmlspecialchars($senior['barangay_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($senior['validation_status'] ?? '') ?></td>
                                    <td>
                                        <button class="button secondary small" onclick="editSenior(<?= (int)$senior['id'] ?>)">Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function handleCancel(){
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?= BASE_URL ?>/admin/dashboard.php';
            }
        }
        function editSenior(id){
            alert('Edit senior with ID: ' + id);
        }
    </script>
</body>
</html>
