<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

// Handle search
$search = $_GET['search'] ?? '';
$whereClause = "WHERE (s.category = 'transferred' OR st_any.id IS NOT NULL)";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.barangay LIKE ? OR s.osca_id_no LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Debug: Check all seniors with transferred category
$debugStmt = $pdo->prepare("SELECT id, first_name, last_name, category FROM seniors WHERE category = 'transferred'");
$debugStmt->execute();
$debugResults = $debugStmt->fetchAll();
error_log("All seniors with transferred category: " . json_encode($debugResults));

// Get transferred seniors with their transfer details
$stmt = $pdo->prepare("
    SELECT s.*, 
           COALESCE(st.transfer_reason, 'Not specified') as transfer_reason, 
           COALESCE(st.new_address, s.barangay) as new_address, 
           COALESCE(st.effective_date, s.created_at) as effective_date, 
           st.created_at as transfer_date
    FROM seniors s 
    LEFT JOIN (
        SELECT t.*
        FROM senior_transfers t
        INNER JOIN (
            SELECT senior_id, MAX(id) AS max_id
            FROM senior_transfers
            GROUP BY senior_id
        ) m ON t.id = m.max_id
    ) st ON s.id = st.senior_id
    LEFT JOIN senior_transfers st_any ON st_any.senior_id = s.id
    $whereClause 
    ORDER BY s.last_name, s.first_name
");
$stmt->execute($params);
$transferredSeniors = $stmt->fetchAll();

// Debug: Log the query and results
error_log("Transferred seniors query: SELECT s.*, COALESCE(st.transfer_reason, 'Not specified') as transfer_reason, COALESCE(st.new_address, s.barangay) as new_address, COALESCE(st.effective_date, s.created_at) as effective_date, st.created_at as transfer_date FROM seniors s LEFT JOIN senior_transfers st ON s.id = st.senior_id $whereClause ORDER BY s.last_name, s.first_name");
error_log("Transferred seniors count: " . count($transferredSeniors));
if (count($transferredSeniors) > 0) {
    error_log("First transferred senior: " . json_encode($transferredSeniors[0]));
}

// Get statistics
$totalTransferred = count($transferredSeniors);
$transferredThisMonth = array_filter($transferredSeniors, function($senior) {
    return $senior['transfer_date'] && date('Y-m', strtotime($senior['transfer_date'])) === date('Y-m');
});
$transferredThisMonthCount = count($transferredThisMonth);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferred Seniors - OSCA Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/government-portal.css">
</head>
<body>
    <?php include '../partials/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="header-content">
                <h1><i class="fas fa-exchange-alt"></i> Transferred Seniors</h1>
                <p>Manage seniors who have been transferred to other locations</p>
            </div>
        </div>

        <?php if (isset($_GET['transfer_success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Senior has been successfully transferred!
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Transferred</h3>
                    <p class="stat-number"><?= $totalTransferred ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>This Month</h3>
                    <p class="stat-number"><?= $transferredThisMonthCount ?></p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Search Transferred Seniors</h2>
            </div>
            <div class="card-content">
                <form method="GET" class="search-form">
                    <div class="search-group">
                        <input type="text" name="search" placeholder="Search by name, barangay, or OSCA ID..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                        <button type="submit" class="search-btn" aria-label="Search transferred seniors" title="Search">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="transferred_seniors.php" class="clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transferred Seniors List -->
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Transferred Seniors List</h2>
                <div class="card-actions">
                    <span class="result-count"><?= $totalTransferred ?> transferred senior<?= $totalTransferred !== 1 ? 's' : '' ?></span>
                </div>
            </div>
            <div class="card-content">
                <?php if (empty($transferredSeniors)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>No Transferred Seniors</h3>
                    <p><?= !empty($search) ? 'No transferred seniors found matching your search criteria.' : 'No seniors have been transferred yet.' ?></p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>New Address</th>
                                <th>Transfer Date</th>
                                <th>Transfer Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transferredSeniors as $senior): ?>
                            <tr>
                                <td>
                                    <div class="senior-info">
                                        <div class="senior-name">
                                            <?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?>
                                            <?php if ($senior['middle_name']): ?>
                                            <span class="middle-name"><?= htmlspecialchars($senior['middle_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($senior['osca_id_no']): ?>
                                        <div class="senior-id">OSCA ID: <?= htmlspecialchars($senior['osca_id_no']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= $senior['age'] ?> years</td>
                                <td><?= htmlspecialchars($senior['new_address']) ?></td>
                                <td>
                                    <?php if ($senior['effective_date'] && $senior['effective_date'] !== '0000-00-00'): ?>
                                        <?= date('M d, Y', strtotime($senior['effective_date'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($senior['transfer_reason'] && $senior['transfer_reason'] !== 'Not specified'): ?>
                                        <?= htmlspecialchars($senior['transfer_reason']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="senior_details.php?id=<?= $senior['id'] ?>&noedit=1" class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/app.js"></script>
</body>
</html>
