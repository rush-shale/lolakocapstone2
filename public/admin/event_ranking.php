<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

// Year selector (defaults to current year)
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 2000 || $year > (int)date('Y') + 1) {
	$year = (int)date('Y');
}
$startOfYear = date('Y-01-01 00:00:00', strtotime($year . '-01-01'));
$endOfYear = date('Y-12-31 23:59:59', strtotime($year . '-01-01'));

// Build yearly participation ranking
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
$stmt->execute([':start' => $startOfYear, ':end' => $endOfYear]);
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

// Fetch yearly histories (last 5 years including current year)
$history = [];
for ($i = 0; $i < 5; $i++) {
    $targetYear = $year - $i;
    $hs = $targetYear . '-01-01 00:00:00';
    $he = $targetYear . '-12-31 23:59:59';
    $hstmt = $pdo->prepare("SELECT s.id, s.first_name, s.last_name, s.barangay, COUNT(a.id) AS event_count
                             FROM seniors s
                             LEFT JOIN attendance a ON a.senior_id = s.id AND a.marked_at BETWEEN :hs AND :he
                             WHERE s.life_status='living'
                             GROUP BY s.id
                             HAVING event_count > 0
                             ORDER BY event_count DESC, s.last_name ASC, s.first_name ASC
                             LIMIT 8");
    $hstmt->execute([':hs' => $hs, ':he' => $he]);
    $history[$targetYear] = $hstmt->fetchAll();
}

// Summary stats for selected year
$totalEventsStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE marked_at BETWEEN :start AND :end");
$totalEventsStmt->execute([':start' => $startOfYear, ':end' => $endOfYear]);
$totalEventsYear = (int)$totalEventsStmt->fetchColumn();

$uniqueParticipantStmt = $pdo->prepare("SELECT COUNT(DISTINCT senior_id) FROM attendance WHERE marked_at BETWEEN :start AND :end");
$uniqueParticipantStmt->execute([':start' => $startOfYear, ':end' => $endOfYear]);
$uniqueParticipantsYear = (int)$uniqueParticipantStmt->fetchColumn();

$topSenior = $rankRows[0] ?? null;
$topCount = $topSenior['event_count'] ?? 0;

// Year options for dropdown (current year down to current-10)
$currentYear = (int)date('Y');
$yearOptions = [];
for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
	$yearOptions[] = $y;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Participation Ranking</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
	<style>
		.stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 1rem;
			margin-bottom: 1.5rem;
		}
		.stat-card {
			background: #fff;
			border-radius: 16px;
			padding: 1.3rem;
			box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
			border: 1px solid rgba(15, 23, 42, 0.06);
		}
		.stat-label {
			font-size: 0.8rem;
			text-transform: uppercase;
			letter-spacing: 0.08em;
			color: #64748b;
		}
		.stat-value {
			font-size: 2rem;
			font-weight: 700;
			color: #0f172a;
			margin: 0.15rem 0;
		}
		.ranking-table {
			width: 100%;
			border-collapse: collapse;
			min-width: 600px;
		}
		.ranking-table thead {
			background: linear-gradient(90deg, #1e3a8a, #2563eb);
			color: white;
		}
		.ranking-table thead th {
			padding: 0.95rem;
			font-size: 0.85rem;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			font-weight: 600;
		}
		.ranking-table tbody tr {
			background: white;
			border-bottom: 1px solid #e2e8f0;
		}
		.ranking-table tbody tr:nth-child(odd) {
			background: #f8fafc;
		}
		.ranking-table td {
			padding: 0.9rem;
			font-size: 0.95rem;
			color: #0f172a;
		}
		.badge-rank {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			padding: 0.35rem 0.7rem;
			border-radius: 999px;
			font-weight: 600;
			background: rgba(37, 99, 235, 0.12);
			color: #1d4ed8;
		}
		.badge-rank.top1 { background: rgba(250, 204, 21, 0.2); color: #92400e; }
		.badge-rank.top2 { background: rgba(148, 163, 184, 0.2); color: #475569; }
		.badge-rank.top3 { background: rgba(248, 113, 113, 0.2); color: #b91c1c; }
		.year-picker {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 0.75rem;
		}
		.year-select {
			padding: 0.55rem 0.8rem;
			border-radius: 10px;
			border: 1px solid #cbd5f5;
			font-weight: 600;
			color: #0f172a;
		}
		.history-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 1rem;
		}
		.history-card {
			background: #fff;
			border-radius: 14px;
			padding: 1rem;
			border: 1px solid rgba(15, 23, 42, 0.08);
			box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
		}
		.history-card h3 {
			margin: 0 0 0.7rem;
			font-size: 1.05rem;
			color: #1e3a8a;
		}
		.history-card ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}
		.history-card li {
			display: flex;
			justify-content: space-between;
			font-size: 0.9rem;
			padding: 0.35rem 0;
			border-bottom: 1px dashed rgba(148, 163, 184, 0.5);
		}
		.history-card li:last-child {
			border-bottom: none;
		}
		@media (max-width: 768px) {
			.year-picker {
				flex-direction: column;
				align-items: flex-start;
			}
			.ranking-table {
				min-width: 100%;
			}
		}
	</style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>

	<main class="content">
    <header class="content-header">
        <h1 class="content-title">Yearly Event Participation Ranking</h1>
        <p class="content-subtitle">Top seniors based on total event attendance per year</p>
    </header>

    <div class="content-body">
        <div class="main-content-area">
            <div class="card" style="margin-bottom: 1.25rem;">
                <div class="card-header">
                    <h2>Select Year</h2>
                </div>
                <div class="card-body">
                    <form method="get" class="year-picker">
                        <label for="year" style="font-weight:600; color:#334155;">Ranking Year</label>
                        <select id="year" name="year" class="year-select">
                            <?php foreach ($yearOptions as $optionYear): ?>
                                <option value="<?= $optionYear ?>" <?= $optionYear === $year ? 'selected' : '' ?>>
                                    <?= $optionYear ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button primary" type="submit">View Rankings</button>
                    </form>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <p class="stat-label">Total Events Logged</p>
                    <p class="stat-value"><?= number_format($totalEventsYear) ?></p>
                    <p style="margin:0; color:#64748b;">Across <?= $year ?></p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Active Participants</p>
                    <p class="stat-value"><?= number_format($uniqueParticipantsYear) ?></p>
                    <p style="margin:0; color:#64748b;">Unique seniors</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Top Performer</p>
                    <?php if ($topSenior): ?>
                        <p class="stat-value" style="font-size:1.5rem;">
                            <?= htmlspecialchars($topSenior['first_name'] . ' ' . $topSenior['last_name']) ?>
                        </p>
                        <p style="margin:0; color:#64748b;"><?= (int)$topCount ?> events joined</p>
                    <?php else: ?>
                        <p class="stat-value" style="font-size:1.5rem;">—</p>
                        <p style="margin:0; color:#64748b;">No data</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h2>Top Participants (<?= $year ?>)</h2>
                        <p style="margin:0; color:#64748b;">Dense ranking — ties share the same rank.</p>
                    </div>
                </div>
                <div class="card-body" style="overflow-x:auto;">
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Senior</th>
                                <th>Barangay</th>
                                <th>Total Events</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($rankRows)): ?>
                            <?php foreach ($rankRows as $i => $row): 
                                $rank = (int)$denseRanks[$i];
                                $rankClass = $rank === 1 ? 'top1' : ($rank === 2 ? 'top2' : ($rank === 3 ? 'top3' : ''));
                            ?>
                            <tr>
                                <td><span class="badge-rank <?= $rankClass ?>">Top <?= $rank ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong><br>
                                    <span style="color:#64748b; font-size:0.85rem;">ID: <?= (int)$row['id'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['barangay']) ?></td>
                                <td><strong><?= (int)$row['event_count'] ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding:1.25rem;">No participation recorded this year.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-top: 1.25rem;">
                <div class="card-header">
                    <h2>Yearly Snapshot (Top 8 per year)</h2>
                </div>
                <div class="card-body">
                    <div class="history-grid">
                        <?php foreach ($history as $histYear => $rows): ?>
                            <div class="history-card">
                                <h3><?= $histYear ?></h3>
                                <?php if (!empty($rows)): ?>
                                    <ul>
                                    <?php
                                        $current = 0; $prev = null; $ranks = [];
                                        foreach ($rows as $rr) { if ($prev===null || (int)$rr['event_count'] < (int)$prev) { $current++; $prev = (int)$rr['event_count']; } $ranks[] = $current; }
                                        foreach ($rows as $idx => $rr):
                                    ?>
                                        <li>
                                            <span><?= 'Top ' . $ranks[$idx] ?> · <?= htmlspecialchars($rr['first_name'] . ' ' . $rr['last_name']) ?></span>
                                            <strong><?= (int)$rr['event_count'] ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="margin:0; color:#94a3b8;">No attendance recorded.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<script src="<?= BASE_URL ?>/assets/app.js"></script>
</body>
</html>


