<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

require_role('admin');
$pdo = get_db_connection();

$senior_id = (int)($_GET['id'] ?? 0);

if (!$senior_id) {
    echo '<div class="error-state"><p>Invalid senior ID</p></div>';
    exit;
}

// Get senior details
$seniorStmt = $pdo->prepare('SELECT * FROM seniors WHERE id = ?');
$seniorStmt->execute([$senior_id]);
$senior = $seniorStmt->fetch();

if (!$senior) {
    echo '<div class="error-state"><p>Senior not found</p></div>';
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
                    <span class="badge <?= $senior['benefits_received'] ? 'badge-success' : 'badge-warning' ?>">
                        <?= $senior['benefits_received'] ? 'Benefits Received' : 'Benefits Pending' ?>
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
        <h3><i class="fas fa-tools"></i> Quick Actions</h3>
        <div class="quick-actions">
            <button class="button secondary" onclick="toggleBenefits(<?= $senior['id'] ?>, <?= $senior['benefits_received'] ? 0 : 1 ?>)">
                <i class="fas fa-gift"></i>
                <?= $senior['benefits_received'] ? 'Mark Benefits Pending' : 'Mark Benefits Received' ?>
            </button>
            <button class="button secondary" onclick="toggleLifeStatus(<?= $senior['id'] ?>, '<?= $senior['life_status'] === 'living' ? 'deceased' : 'living' ?>')">
                <i class="fas fa-heartbeat"></i>
                <?= $senior['life_status'] === 'living' ? 'Mark as Deceased' : 'Mark as Living' ?>
            </button>
            <button class="button secondary" onclick="toggleCategory(<?= $senior['id'] ?>, '<?= $senior['category'] === 'local' ? 'national' : 'local' ?>')">
                <i class="fas fa-exchange-alt"></i>
                <?= $senior['category'] === 'local' ? 'Transfer to National' : 'Transfer to Local' ?>
            </button>
        </div>
    </div>
</div>

<script>
// Quick action functions
function toggleBenefits(id, to) {
    if (confirm('Are you sure you want to change the benefits status?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="op" value="toggle_benefits">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="to" value="${to}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleLifeStatus(id, to) {
    if (confirm('Are you sure you want to change the life status?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf" value="<?= generate_csrf_token() ?>">
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
        form.innerHTML = `
            <input type="hidden" name="csrf" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="op" value="transfer">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="to" value="${to}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
