<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();
start_app_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        echo '<div class="error-state"><p>Invalid session token</p></div>';
        exit;
    }
    $op = $_POST['op'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $to = $_POST['to'] ?? '';

    if ($op === 'toggle_life' && $id && in_array($to, ['living', 'deceased'])) {
        $stmt = $pdo->prepare('UPDATE seniors SET life_status = ? WHERE id = ?');
        $stmt->execute([$to, $id]);
        header("Location: senior_details.php?id=$id");
        exit;
    }

    if ($op === 'transfer' && $id && in_array($to, ['local', 'national'])) {
        $stmt = $pdo->prepare('UPDATE seniors SET category = ? WHERE id = ?');
        $stmt->execute([$to, $id]);
        header("Location: senior_details.php?id=$id");
        exit;
    }
}

$senior_id = (int)($_GET['id'] ?? 0);

if (!$senior_id) {
    if (isset($_GET['edit'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid senior ID']);
        exit;
    }
    echo '<div class="error-state"><p>Invalid senior ID</p></div>';
    exit;
}

// Get senior details
$seniorStmt = $pdo->prepare('SELECT * FROM seniors WHERE id = ?');
$seniorStmt->execute([$senior_id]);
$senior = $seniorStmt->fetch();

if (!$senior) {
    if (isset($_GET['edit'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Senior not found']);
        exit;
    }
    echo '<div class="error-state"><p>Senior not found</p></div>';
    exit;
}

// Handle JSON response for edit modal
if (isset($_GET['edit'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'senior' => $senior
    ]);
    exit;
}

// Get attendance history
$attendanceStmt = $pdo->prepare('
    SELECT 
        a.marked_at,
        e.title as event_title,
        e.event_date,
        e.event_time,
        e.description,
        u.name as organizer_name,
        u.barangay as organizer_barangay
    FROM attendance a
    JOIN events e ON a.event_id = e.id
    JOIN users u ON e.created_by = u.id
    WHERE a.senior_id = ?
    ORDER BY a.marked_at DESC
');
$attendanceStmt->execute([$senior_id]);
$attendanceHistory = $attendanceStmt->fetchAll();

// Get event statistics
$totalEvents = count($attendanceHistory);
$thisYearEvents = array_filter($attendanceHistory, function($att) {
    return date('Y', strtotime($att['event_date'])) === date('Y');
});
$thisYearCount = count($thisYearEvents);

$lastEvent = !empty($attendanceHistory) ? $attendanceHistory[0] : null;
?>

<style>
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
    }
    .profile-avatar {
        font-size: 3.5rem;
        color: #2563eb;
        flex-shrink: 0;
    }
    .profile-info h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
    }
    .profile-middle {
        margin: 0;
        font-size: 0.875rem;
        color: #6b7280;
        font-style: italic;
    }
    .profile-badges {
        margin-top: 0.25rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        white-space: nowrap;
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
        background-color: #bfdbfe;
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
    .button.primary {
        background-color: #2563eb;
        color: white;
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .button.primary i {
        font-size: 1rem;
    }
    .button.primary:hover {
        background-color: #1d4ed8;
    }
    .profile-stats {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    .stat-card {
        flex: 1 1 45%;
        background: #f9fafb;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .stat-icon {
        font-size: 1.5rem;
        color: #2563eb;
        flex-shrink: 0;
    }
    .stat-content h3 {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
    }
    .stat-content .number {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: #111827;
    }
    .profile-section h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .contact-info, .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .contact-item, .no-contact {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #4b5563;
    }
    .contact-item i, .no-contact i {
        color: #2563eb;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .attendance-timeline {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.5rem;
        background: #f9fafb;
    }
    .timeline-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .timeline-item:last-child {
        border-bottom: none;
    }
    .timeline-marker {
        font-size: 1.25rem;
        color: #2563eb;
        flex-shrink: 0;
    }
    .timeline-content {
        flex: 1;
    }
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.875rem;
    }
    .timeline-date {
        font-size: 0.75rem;
        color: #6b7280;
        white-space: nowrap;
    }
    .timeline-details {
        font-size: 0.75rem;
        color: #4b5563;
        margin-top: 0.25rem;
    }
    .timeline-organizer, .timeline-attendance {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.25rem;
    }
    .timeline-description {
        margin-top: 0.25rem;
        font-style: italic;
    }
    .timeline-organizer i, .timeline-attendance i {
        color: #2563eb;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .empty-attendance {
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
        padding: 1rem 0;
    }
    .empty-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #2563eb;
    }
    .quick-actions {
        gap: 0.5rem;
    }
    .quick-actions .button.secondary {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .quick-actions .button.secondary i {
        font-size: 1rem;
    }
    .quick-actions .button.secondary:hover {
        background-color: #e5e7eb;
    }
</style>

<input type="hidden" id="csrf_token" value="<?= generate_csrf_token() ?>">

<div class="senior-profile">
    <!-- Personal Information -->
    <div class="profile-section">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']) ?></h2>
                <?php if ($senior['middle_name']): ?>
                <p class="profile-middle"><?= htmlspecialchars($senior['middle_name']) ?></p>
                <?php endif; ?>
                <div class="profile-badges">
                    <span class="badge <?= $senior['life_status'] === 'living' ? 'badge-success' : 'badge-danger' ?>">
                        <?= ucfirst($senior['life_status']) ?>
                    </span>
                    <span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : 'badge-info' ?>">
                        <?= ucfirst($senior['category']) ?>
                    </span>
                    
                </div>
            </div>
            <div class="profile-actions">
                <button class="button primary" onclick="editSenior(<?= $senior['id'] ?>)">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="profile-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-birthday-cake"></i>
            </div>
            <div class="stat-content">
                <h3>Age</h3>
                <p class="number"><?= $senior['age'] ?> years</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Barangay</h3>
                <p class="number"><?= htmlspecialchars($senior['barangay']) ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3>Total Events</h3>
                <p class="number"><?= $totalEvents ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>This Year</h3>
                <p class="number"><?= $thisYearCount ?></p>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="profile-section">
        <h3><i class="fas fa-phone"></i> Contact Information</h3>
        <div class="contact-info">
            <?php if ($senior['contact']): ?>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span><?= htmlspecialchars($senior['contact']) ?></span>
            </div>
            <?php else: ?>
            <div class="no-contact">
                <i class="fas fa-phone-slash"></i>
                <span>No contact information available</span>
            </div>
            <?php endif; ?>
            <?php if ($senior['cellphone']): ?>
            <div class="contact-item">
                <i class="fas fa-mobile-alt"></i>
                <span><?= htmlspecialchars($senior['cellphone']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="profile-section">
        <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
        <div class="contact-info">
            <?php if ($senior['osca_id_no']): ?>
            <div class="contact-item">
                <i class="fas fa-id-card"></i>
                <span>OSCA ID: <?= htmlspecialchars($senior['osca_id_no']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($senior['purok']): ?>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>Purok: <?= htmlspecialchars($senior['purok']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($senior['health_condition']): ?>
            <div class="contact-item">
                <i class="fas fa-heartbeat"></i>
                <span>Health Condition: <?= htmlspecialchars($senior['health_condition']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($senior['remarks']): ?>
            <div class="contact-item">
                <i class="fas fa-sticky-note"></i>
                <span>Remarks: <?= htmlspecialchars($senior['remarks']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Event Attendance History -->
    <div class="profile-section">
        <h3><i class="fas fa-history"></i> Event Attendance History</h3>
        <?php if (!empty($attendanceHistory)): ?>
        <div class="attendance-timeline">
            <?php foreach ($attendanceHistory as $attendance): ?>
            <div class="timeline-item">
                <div class="timeline-marker">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <h4><?= htmlspecialchars($attendance['event_title']) ?></h4>
                        <span class="timeline-date">
                            <?= date('M d, Y', strtotime($attendance['event_date'])) ?>
                            <?php if ($attendance['event_time']): ?>
                            at <?= date('g:i A', strtotime($attendance['event_time'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="timeline-details">
                        <div class="timeline-organizer">
                            <i class="fas fa-user-tie"></i>
                            <span>Organized by: <?= htmlspecialchars($attendance['organizer_name']) ?> (<?= htmlspecialchars($attendance['organizer_barangay']) ?>)</span>
                        </div>
                        <?php if ($attendance['description']): ?>
                        <div class="timeline-description">
                            <p><?= htmlspecialchars($attendance['description']) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="timeline-attendance">
                            <i class="fas fa-clock"></i>
                            <span>Marked attendance: <?= date('M d, Y g:i A', strtotime($attendance['marked_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-attendance">
            <div class="empty-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h4>No Event Attendance</h4>
            <p>This senior has not attended any events yet.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="profile-section">
        <!-- Quick Actions removed as per request -->
    </div>
</div>

<script>
// Quick action functions
function toggleLifeStatus(id, to) {
    if (confirm('Are you sure you want to change the life status?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'senior_details.php';
        form.innerHTML = `
            <input type="hidden" name="csrf" value="${document.getElementById('csrf_token').value}">
            <input type="hidden" name="op" value="toggle_life">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="to" value="${to}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleCategory(id, to) {
    if (confirm('Are you sure you want to change the category?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'senior_details.php';
        form.innerHTML = `
            <input type="hidden" name="csrf" value="${document.getElementById('csrf_token').value}">
            <input type="hidden" name="op" value="transfer">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="to" value="${to}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- Edit Senior Modal -->
<div id="editSeniorModal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal" style="background: white; border-radius: 12px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90%; overflow-y: auto; position: relative;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;">Edit Senior Profile</h2>
            <button onclick="closeEditSeniorModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        <form id="editSeniorForm" method="POST" action="../admin/seniors.php" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" name="csrf" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="op" value="update">
            <input type="hidden" name="id" id="editSeniorId">
            <!-- Preserve benefits_received without exposing checkbox -->
            <input type="hidden" id="editBenefitsReceivedHidden" name="benefits_received" value="1">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="editFirstName" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">First Name *</label>
                    <input type="text" id="editFirstName" name="first_name" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label for="editMiddleName" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Middle Name</label>
                    <input type="text" id="editMiddleName" name="middle_name" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
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
                    <select id="editCivilStatus" name="civil_status" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="">Select Status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="widowed">Widowed</option>
                        <option value="divorced">Divorced</option>
                    </select>
                </div>
                <div>
                    <label for="editEducationalAttainment" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Educational Attainment</label>
                    <select id="editEducationalAttainment" name="educational_attainment" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="">Select Education</option>
                        <option value="none">None</option>
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
                <input type="text" id="editBarangay" name="barangay" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="editContact" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Contact Number</label>
                    <input type="text" id="editContact" name="contact" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                <div>
                    <label for="editCellphone" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">Cellphone #</label>
                    <input type="text" id="editCellphone" name="cellphone" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="editOscaIdNo" style="font-weight: 600; margin-bottom: 0.25rem; display: block;">OSCA ID NO.</label>
                    <input type="text" id="editOscaIdNo" name="osca_id_no" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
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
                <label for="editLifeStatus" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    Life Status:
                    <select id="editLifeStatus" name="life_status" style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="living">Living</option>
                        <option value="deceased">Deceased</option>
                    </select>
                </label>
                <label for="editCategory" style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                    Category:
                    <select id="editCategory" name="category" style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="local">Local</option>
                        <option value="national">National</option>
                    </select>
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                <button type="button" onclick="closeEditSeniorModal()" style="padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer;">Update Senior</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSenior(id) {
    // Open the edit modal and populate with senior data
    openEditSeniorModal(id);
}

function openEditSeniorModal(id) {
    fetch(`senior_details.php?id=${id}&edit=1`)
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
            document.getElementById('editContact').value = senior.contact || '';
            document.getElementById('editCellphone').value = senior.cellphone || '';
            document.getElementById('editOscaIdNo').value = senior.osca_id_no || '';
            document.getElementById('editPurok').value = senior.purok || '';
            document.getElementById('editHealthCondition').value = senior.health_condition || '';
            document.getElementById('editRemarks').value = senior.remarks || '';
            // Keep hidden benefits value synced if needed (not editable)
            const benefitsHidden = document.getElementById('editBenefitsReceivedHidden');
            if (benefitsHidden) { benefitsHidden.value = senior.benefits_received ? '1' : '0'; }
            document.getElementById('editLifeStatus').value = senior.life_status;
            document.getElementById('editCategory').value = senior.category;

            // Show modal
            document.getElementById('editSeniorModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        })
        .catch(() => {
            alert('Error loading senior data.');
        });
}

function closeEditSeniorModal() {
    document.getElementById('editSeniorModal').style.display = 'none';
    document.body.style.overflow = '';
}
</script>
