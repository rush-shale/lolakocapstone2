<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

$message = '';

// Define PDF render function if not already defined (to avoid conflicts with seniors.php)
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
            echo '<button class="back-btn" onclick="window.location.href=\'' . htmlspecialchars($back_url, ENT_QUOTES) . '\'">← Back to Event Ranking</button>';
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
            elseif ($h === 'Rank' || $h === 'Year') $class = 'number-col';
            elseif ($h === 'Total Events' || $h === 'Total Events Logged' || $h === 'Active Participants' || $h === 'Top Performer Events') $class = 'number-col';
            echo '<th class="' . $class . '">' . htmlspecialchars($h) . '</th>'; 
        }
        echo '</tr></thead><tbody>';
        
        $rowNum = 1;
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td class="number-col">' . $rowNum . '</td>';
            foreach ($headers as $i => $h) { 
                $value = '';
                if (isset($r[$h])) {
                    $value = (string)$r[$h];
                } else {
                    // Try to get value by index if key doesn't match
                    $values = array_values($r);
                    $value = isset($values[$i]) ? (string)$values[$i] : '';
                }
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
                elseif ($h === 'Rank' || $h === 'Year') $class = 'number-col';
                elseif ($h === 'Total Events' || $h === 'Total Events Logged' || $h === 'Active Participants' || $h === 'Top Performer Events') $class = 'number-col';
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
    $reportType = $_GET['report_type'] ?? 'ranking'; // ranking, snapshot, summary
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    if ($year < 2000 || $year > (int)date('Y') + 1) {
        $year = (int)date('Y');
    }
    $startOfYear = date('Y-01-01 00:00:00', strtotime($year . '-01-01'));
    $endOfYear = date('Y-12-31 23:59:59', strtotime($year . '-01-01'));
    
    $headers = [];
    $rows = [];
    $reportTitle = '';
    
    if ($reportType === 'ranking') {
        // Top Participants (per year) - Full ranking
        $sql = "
            SELECT 
                s.id,
                s.first_name,
                COALESCE(s.middle_name, '') AS middle_name,
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
        
        // Compute ranks
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
        
        // Format rows with ranks
        $rows = [];
        foreach ($rankRows as $i => $row) {
            $rank = (int)$denseRanks[$i];
            $rows[] = [
                'Rank' => 'Top ' . $rank,
                'Last Name' => $row['last_name'],
                'First Name' => $row['first_name'],
                'Middle Name' => $row['middle_name'],
                'Barangay' => $row['barangay'],
                'Total Events' => (int)$row['event_count']
            ];
        }
        
        $headers = ['Rank', 'Last Name', 'First Name', 'Middle Name', 'Barangay', 'Total Events'];
        $reportTitle = 'EVENT PARTICIPATION RANKING REPORT - ' . $year;
        
    } elseif ($reportType === 'snapshot') {
        // Yearly Snapshot (Top 8 per year) - Last 5 years
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
            $yearRows = $hstmt->fetchAll();
            
            // Compute ranks for this year
            $current = 0;
            $prev = null;
            $ranks = [];
            foreach ($yearRows as $rr) {
                if ($prev === null || (int)$rr['event_count'] < (int)$prev) {
                    $current++;
                    $prev = (int)$rr['event_count'];
                }
                $ranks[] = $current;
            }
            
            foreach ($yearRows as $idx => $rr) {
                $rows[] = [
                    'Year' => $targetYear,
                    'Rank' => 'Top ' . $ranks[$idx],
                    'Last Name' => $rr['last_name'],
                    'First Name' => $rr['first_name'],
                    'Barangay' => $rr['barangay'],
                    'Total Events' => (int)$rr['event_count']
                ];
            }
        }
        
        $headers = ['Year', 'Rank', 'Last Name', 'First Name', 'Barangay', 'Total Events'];
        $reportTitle = 'YEARLY SNAPSHOT REPORT - TOP 8 PER YEAR';
        
    } elseif ($reportType === 'summary') {
        // Summary report with stats
        $totalEventsStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE marked_at BETWEEN :start AND :end");
        $totalEventsStmt->execute([':start' => $startOfYear, ':end' => $endOfYear]);
        $totalEventsYear = (int)$totalEventsStmt->fetchColumn();
        
        $uniqueParticipantStmt = $pdo->prepare("SELECT COUNT(DISTINCT senior_id) FROM attendance WHERE marked_at BETWEEN :start AND :end");
        $uniqueParticipantStmt->execute([':start' => $startOfYear, ':end' => $endOfYear]);
        $uniqueParticipantsYear = (int)$uniqueParticipantStmt->fetchColumn();
        
        $topSeniorStmt = $pdo->prepare("
            SELECT s.first_name, s.last_name, COUNT(a.id) AS event_count
            FROM seniors s
            LEFT JOIN attendance a ON a.senior_id = s.id AND a.marked_at BETWEEN :start AND :end
            WHERE s.life_status = 'living'
            GROUP BY s.id
            HAVING event_count > 0
            ORDER BY event_count DESC
            LIMIT 1
        ");
        $topSeniorStmt->execute([':start' => $startOfYear, ':end' => $endOfYear]);
        $topSenior = $topSeniorStmt->fetch();
        
        $rows = [
            [
                'Year' => $year,
                'Total Events Logged' => number_format($totalEventsYear),
                'Active Participants' => number_format($uniqueParticipantsYear),
                'Top Performer' => $topSenior ? htmlspecialchars($topSenior['first_name'] . ' ' . $topSenior['last_name']) : 'N/A',
                'Top Performer Events' => $topSenior ? (int)$topSenior['event_count'] : 0
            ]
        ];
        
        $headers = ['Year', 'Total Events Logged', 'Active Participants', 'Top Performer', 'Top Performer Events'];
        $reportTitle = 'EVENT PARTICIPATION SUMMARY REPORT - ' . $year;
    }
    
    // Build back URL
    $backUrl = 'event_ranking.php?year=' . $year;
    
    // Render PDF
    pdf_render($reportTitle, $headers, $rows, 'event_ranking', $backUrl);
    exit;
}

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
                <div class="stat-card" style="display:flex;flex-direction:column;justify-content:center;align-items:center;">
                    <button class="button secondary" onclick="exportReport('summary')" style="display:flex;align-items:center;gap:0.5rem;width:100%;justify-content:center;">
                        <span>📥</span>
                        <span>Export Summary PDF</span>
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h2>Top Participants (<?= $year ?>)</h2>
                        <p style="margin:0; color:#64748b;">Dense ranking — ties share the same rank.</p>
                    </div>
                    <button class="button secondary" onclick="exportReport('ranking')" style="display:flex;align-items:center;gap:0.5rem;">
                        <span>📥</span>
                        <span>Export PDF</span>
                    </button>
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
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h2>Yearly Snapshot (Top 8 per year)</h2>
                    <button class="button secondary" onclick="exportReport('snapshot')" style="display:flex;align-items:center;gap:0.5rem;">
                        <span>📥</span>
                        <span>Export PDF</span>
                    </button>
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
<script>
    function exportReport(reportType) {
        const url = new URL(window.location.href);
        url.searchParams.set('export', 'pdf');
        url.searchParams.set('report_type', reportType);
        // Preserve year parameter
        window.location.href = url.toString();
    }
</script>
</body>
</html>


