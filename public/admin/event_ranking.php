<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

// Month selector (defaults to current month)
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m'); // format YYYY-MM
$startOfMonth = date('Y-m-01 00:00:00', strtotime($month . '-01'));
$endOfMonth = date('Y-m-t 23:59:59', strtotime($month . '-01'));

// Build monthly participation ranking
$sql = "
    SELECT 
        s.id,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.barangay,
        COUNT(a.id) AS event_count
    FROM seniors s
    LEFT JOIN attendance a 
        ON a.senior_id = s.id 
        AND a.marked_at BETWEEN :start AND :end
    WHERE s.life_status = 'living'
    GROUP BY s.id
    HAVING event_count > 0
    ORDER BY event_count DESC, s.last_name ASC, s.first_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':start' => $startOfMonth, ':end' => $endOfMonth]);
$rankRows = $stmt->fetchAll();

// Compute dense ranks (ties share same rank)
$denseRanks = [];
$currentRank = 0;
$prevCount = null;
foreach ($rankRows as $row) {
    if ($prevCount === null || (int)$row['event_count'] < (int)$prevCount) {
        $currentRank += 1;
        $prevCount = (int)$row['event_count'];
    }
    $denseRanks[] = $currentRank;
}

// Fetch recent monthly histories (last 6 months)
$history = [];
for ($i = 0; $i < 6; $i++) {
    $m = date('Y-m', strtotime("$month-01 -$i months"));
    $hs = date('Y-m-01 00:00:00', strtotime($m . '-01'));
    $he = date('Y-m-t 23:59:59', strtotime($m . '-01'));
    $hstmt = $pdo->prepare("SELECT s.id, s.first_name, s.last_name, s.barangay, COUNT(a.id) AS event_count
                             FROM seniors s
                             LEFT JOIN attendance a ON a.senior_id = s.id AND a.marked_at BETWEEN :hs AND :he
                             WHERE s.life_status='living'
                             GROUP BY s.id
                             HAVING event_count > 0
                             ORDER BY event_count DESC, s.last_name ASC, s.first_name ASC
                             LIMIT 10");
    $hstmt->execute([':hs' => $hs, ':he' => $he]);
    $history[$m] = $hstmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Participation Ranking</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

<main class="main-content">
    <header class="content-header">
        <h1 class="content-title">Event Participation Ranking</h1>
        <p class="content-subtitle">Monthly ranking of seniors by event attendance</p>
    </header>

    <div class="content-body">
        <div class="main-content-area">
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-header">
                    <h2>Select Month</h2>
                </div>
                <div class="card-body" style="padding: 1rem;">
                    <form method="get" style="display: inline-flex; gap: .5rem; align-items: center;">
                        <label for="month">Month</label>
                        <input type="month" id="month" name="month" value="<?= htmlspecialchars($month) ?>">
                        <button class="button primary" type="submit">View</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Top Participants (<?= date('F Y', strtotime($month.'-01')) ?>)</h2>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>RANK</th>
                                    <th>LAST NAME</th>
                                    <th>FIRST NAME</th>
                                    <th>BARANGAY</th>
                                    <th>EVENTS JOINED</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($rankRows)): ?>
                                <?php foreach ($rankRows as $i => $row): ?>
                                <tr>
                                    <td><?php echo 'Top ' . (int)$denseRanks[$i]; ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['barangay']) ?></td>
                                    <td><?= (int)$row['event_count'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding:1rem;">No participation recorded this month.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <h2>Recent Monthly Records (Top 10 per month)</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($history as $m => $rows): ?>
                        <h3 style="margin: .5rem 0;"><?= date('F Y', strtotime($m.'-01')) ?></h3>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>RANK</th>
                                        <th>LAST NAME</th>
                                        <th>FIRST NAME</th>
                                        <th>BARANGAY</th>
                                        <th>EVENTS JOINED</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($rows)):
                                    // compute dense ranks for this month snapshot
                                    $current = 0; $prev = null; $ranks = [];
                                    foreach ($rows as $rr) { if ($prev===null || (int)$rr['event_count'] < (int)$prev) { $current++; $prev = (int)$rr['event_count']; } $ranks[] = $current; }
                                    foreach ($rows as $idx => $rr): ?>
                                    <tr>
                                        <td><?php echo 'Top ' . (int)$ranks[$idx]; ?></td>
                                        <td><?= htmlspecialchars($rr['last_name']) ?></td>
                                        <td><?= htmlspecialchars($rr['first_name']) ?></td>
                                        <td><?= htmlspecialchars($rr['barangay']) ?></td>
                                        <td><?= (int)$rr['event_count'] ?></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="5" style="text-align:center; padding:1rem;">No records.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


