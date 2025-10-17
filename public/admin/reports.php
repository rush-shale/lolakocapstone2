<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

function csv_download(string $filename, array $headers, array $rows): void {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	$out = fopen('php://output', 'w');
	fputcsv($out, $headers);
    foreach ($rows as $r) { fputcsv($out, array_values($r)); }
	fclose($out);
	exit;
}

function pdf_render(string $title, array $headers, array $rows): void {
    // Simple print-to-PDF friendly HTML. Users can Save as PDF from browser.
    echo '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($title) . '</title>';
    echo '<style>body{font-family:Inter,Segoe UI,Arial,sans-serif;padding:20px;color:#111827} h1{margin:0 0 12px} table{width:100%;border-collapse:collapse;font-size:12px} th,td{border:1px solid #e5e7eb;padding:6px 8px;text-align:left;white-space:nowrap} thead th{background:#f3f4f6} @media print{.noprint{display:none}}</style>';
    echo '</head><body>';
    echo '<div class="noprint" style="margin-bottom:10px"><button onclick="window.print()" style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;background:#1e3a8a;color:#fff">Print / Save PDF</button></div>';
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    echo '<table><thead><tr>';
    foreach ($headers as $h) { echo '<th>' . htmlspecialchars($h) . '</th>'; }
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($headers as $i => $h) { echo '<td>' . htmlspecialchars((string)array_values($r)[$i] ?? '') . '</td>'; }
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

function dataset_query(PDO $pdo, string $dataset): array {
    switch ($dataset) {
        case 'seniors_full':
            $headers = ['Last Name','First Name','Middle Name','Ext','Barangay','Age','Sex','Civil Status','Birthdate','OSCA ID','Remarks','Health Condition','Purok','Place of Birth','Cellphone','Life Status','Category','Validation Status','Validated'];
            $sql = "SELECT last_name, first_name, COALESCE(middle_name,'') AS middle_name, COALESCE(ext_name,'') AS ext_name, barangay, age,
                    sex, civil_status, date_of_birth, osca_id_no, COALESCE(remarks,'') AS remarks, COALESCE(health_condition,'') AS health_condition,
                    COALESCE(purok,'') AS purok, COALESCE(place_of_birth,'') AS place_of_birth, COALESCE(cellphone,'') AS cellphone,
                    life_status, category, COALESCE(validation_status,'') AS validation_status, COALESCE(validation_date,'') AS validation_date
                    FROM seniors ORDER BY last_name, first_name";
            break;
        case 'event_ranking':
            $headers = ['Last Name','First Name','Barangay','Events Joined'];
            $sql = "SELECT s.last_name, s.first_name, s.barangay, COUNT(a.id) AS events_joined
                    FROM seniors s LEFT JOIN attendance a ON a.senior_id=s.id
                    GROUP BY s.id ORDER BY events_joined DESC, s.last_name, s.first_name";
            break;
        case 'deceased':
            $headers = ['Last Name','First Name','Middle Name','Ext','Barangay','Age','Sex','Civil Status','Birthdate','OSCA ID','Remarks','Health Condition','Purok','Place of Birth','Cellphone','Category','Validation Status','Validated'];
            $sql = "SELECT last_name, first_name, COALESCE(middle_name,'') AS middle_name, COALESCE(ext_name,'') AS ext_name, barangay, age,
                    sex, civil_status, date_of_birth, osca_id_no, COALESCE(remarks,'') AS remarks, COALESCE(health_condition,'') AS health_condition,
                    COALESCE(purok,'') AS purok, COALESCE(place_of_birth,'') AS place_of_birth, COALESCE(cellphone,'') AS cellphone,
                    category, COALESCE(validation_status,'') AS validation_status, COALESCE(validation_date,'') AS validation_date
                    FROM seniors WHERE life_status='deceased' ORDER BY last_name, first_name";
            break;
        case 'transferred':
            $headers = ['First Name','Middle Name','Last Name','Age','New Address','Transfer Date','Transfer Reason'];
            $sql = "SELECT s.first_name, COALESCE(s.middle_name,'') AS middle_name, s.last_name, s.age,
                    COALESCE(st.new_address, s.barangay) AS new_address,
                    COALESCE(st.effective_date, s.created_at) AS transfer_date,
                    COALESCE(st.transfer_reason,'Not specified') AS transfer_reason
                    FROM seniors s
                    LEFT JOIN (
                        SELECT t.* FROM senior_transfers t
                        INNER JOIN (
                            SELECT senior_id, MAX(id) AS max_id FROM senior_transfers GROUP BY senior_id
                        ) m ON t.id = m.max_id
                    ) st ON s.id = st.senior_id
                    LEFT JOIN senior_transfers st_any ON st_any.senior_id = s.id
                    WHERE (s.category='transferred' OR st_any.id IS NOT NULL)
                    ORDER BY s.last_name, s.first_name";
            break;
        case 'waiting':
            $headers = ['Last Name','First Name','Middle Name','Ext','Barangay','Age','Sex','Civil Status','Birthdate','OSCA ID','Remarks','Health Condition','Purok','Place of Birth','Cellphone','Life Status','Category','Validation Status','Validated'];
            $sql = "SELECT last_name, first_name, COALESCE(middle_name,'') AS middle_name, COALESCE(ext_name,'') AS ext_name, barangay, age,
                    sex, COALESCE(civil_status,'') AS civil_status, date_of_birth, osca_id_no, COALESCE(remarks,'') AS remarks,
                    COALESCE(health_condition,'') AS health_condition, COALESCE(purok,'') AS purok, COALESCE(place_of_birth,'') AS place_of_birth,
                    COALESCE(cellphone,'') AS cellphone, life_status, category, COALESCE(validation_status,'') AS validation_status,
                    COALESCE(validation_date,'') AS validation_date
                    FROM seniors WHERE life_status='living' AND category='waiting' ORDER BY created_at DESC";
            break;
        case 'benefits':
            $headers = ['OSCA ID','Name','Age','Gender','Barangay','Category','SP Q1','SP Q2','SP Q3','SP Q4','Octogenarian','Nonagenarian','Centenarian','Financial Asst','Burial Asst'];
            $sql = "SELECT s.osca_id_no,
                           CONCAT(COALESCE(s.last_name,''), ', ', COALESCE(s.first_name,''), ' ', COALESCE(s.middle_name,'')) AS name,
                           s.age,
                           s.sex AS gender,
                           s.barangay,
                           s.category,
                           MAX(CASE WHEN br.benefit_type='sp_q1' THEN br.received END) AS sp_q1,
                           MAX(CASE WHEN br.benefit_type='sp_q2' THEN br.received END) AS sp_q2,
                           MAX(CASE WHEN br.benefit_type='sp_q3' THEN br.received END) AS sp_q3,
                           MAX(CASE WHEN br.benefit_type='sp_q4' THEN br.received END) AS sp_q4,
                           MAX(CASE WHEN br.benefit_type='octogenarian' THEN br.received END) AS octogenarian,
                           MAX(CASE WHEN br.benefit_type='nonagenarian' THEN br.received END) AS nonagenarian,
                           MAX(CASE WHEN br.benefit_type='centenarian' THEN br.received END) AS centenarian,
                           MAX(CASE WHEN br.benefit_type='financial_asst' THEN br.received END) AS financial_asst,
                           MAX(CASE WHEN br.benefit_type='burial_asst' THEN br.received END) AS burial_asst
                    FROM seniors s
                    LEFT JOIN benefit_records br ON br.senior_id = s.id
                    WHERE s.life_status='living'
                    GROUP BY s.id
                    ORDER BY s.barangay, s.last_name, s.first_name";
            break;
        case 'barangays':
            $headers = ['ID','Barangay Name','Created'];
            $sql = "SELECT id, name, created_at FROM barangays ORDER BY name ASC";
            break;
        case 'attendance':
            $headers = ['ID','Senior Last Name','Senior First Name','Event Title','Event Date','Marked At'];
            $sql = "SELECT a.id, s.last_name, s.first_name, e.title, e.event_date, a.marked_at
		FROM attendance a
		JOIN seniors s ON s.id = a.senior_id
		JOIN events e ON e.id = a.event_id
                    ORDER BY e.event_date DESC, a.marked_at DESC";
            break;
        default:
            $headers = ['ID','First Name','Middle Name','Last Name','Age','Barangay','Benefits Received','Life Status','Category'];
            $sql = "SELECT id, first_name, middle_name, last_name, age, barangay, benefits_received, life_status, category FROM seniors ORDER BY last_name, first_name";
    }
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    return [$headers, $rows];
}

$dataset = $_GET['dataset'] ?? '';
$format = $_GET['format'] ?? '';
if ($dataset !== '' && $format !== '') {
    list($headers, $rows) = dataset_query($pdo, $dataset);
    $basename = $dataset . '_' . date('Ymd_His');
    if ($format === 'csv') {
        csv_download($basename . '.csv', $headers, $rows);
    } elseif ($format === 'pdf') {
        pdf_render(strtoupper(str_replace('_',' ', $dataset)) . ' Report', $headers, $rows);
    }
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Reports & Analytics | SeniorCare Information System</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/government-portal.css">
</head>
<body>
	<?php include __DIR__ . '/../partials/sidebar_admin.php'; ?>
	<main class="content">
		<header class="content-header">
			<h1 class="content-title">Reports & Analytics</h1>
			<p class="content-subtitle">Generate and download system reports</p>
		</header>
		
		<div class="content-body">
			<div class="grid grid-2">
				<div class="card">
					<div class="card-header">
						<h2 class="card-title">
							<i class="fas fa-download"></i>
							Data Export
						</h2>
                        <p class="card-subtitle">Download system data as CSV or PDF</p>
					</div>
					<div class="card-body">
						<div class="report-options">
                            <div>
                                <div class="mb-2"><strong>All Seniors (complete)</strong></div>
                                <a href="?dataset=seniors_full&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=seniors_full&format=pdf" class="button secondary">PDF</a>
						</div>
                            <div>
                                <div class="mb-2"><strong>Event Participation Rankings</strong></div>
                                <a href="?dataset=event_ranking&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=event_ranking&format=pdf" class="button secondary">PDF</a>
					</div>
                            <div>
                                <div class="mb-2"><strong>Deceased Seniors</strong></div>
                                <a href="?dataset=deceased&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=deceased&format=pdf" class="button secondary">PDF</a>
				</div>
                            <div>
                                <div class="mb-2"><strong>Transferred Seniors</strong></div>
                                <a href="?dataset=transferred&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=transferred&format=pdf" class="button secondary">PDF</a>
					</div>
                            <div>
                                <div class="mb-2"><strong>Waiting Seniors</strong></div>
                                <a href="?dataset=waiting&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=waiting&format=pdf" class="button secondary">PDF</a>
								</div>
                            <div>
                                <div class="mb-2"><strong>Benefits Management</strong></div>
                                <a href="?dataset=benefits&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=benefits&format=pdf" class="button secondary">PDF</a>
								</div>
                            <div>
                                <div class="mb-2"><strong>Barangays</strong></div>
                                <a href="?dataset=barangays&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=barangays&format=pdf" class="button secondary">PDF</a>
							</div>
                            <div>
                                <div class="mb-2"><strong>Attendance</strong></div>
                                <a href="?dataset=attendance&format=csv" class="button primary">CSV</a>
                                <a href="?dataset=attendance&format=pdf" class="button secondary">PDF</a>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Quick Statistics card removed as requested -->
			</div>
		</div>
	</main>
</body>
</html>


