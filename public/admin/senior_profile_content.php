<?php
// This file contains only the senior profile content for AJAX loading
// It should be included by senior_details.php when handling AJAX requests
?>

<div class="senior-profile" data-senior-id="<?= $senior['id'] ?>">
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
                    <span class="badge <?= $senior['category'] === 'local' ? 'badge-primary' : ($senior['category'] === 'national' ? 'badge-info' : 'badge-warning') ?>">
                        <?= ucfirst($senior['category']) ?>
                    </span>
                </div>
            </div>
            <div class="profile-actions">
                <?php if (($_GET['noedit'] ?? '') !== '1'): ?>
                <button class="button primary" data-senior-id="<?= $senior['id'] ?>">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
                <?php endif; ?>
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
                <h3>Location</h3>
                <p class="text"><?= htmlspecialchars($senior['barangay']) ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-content">
                <h3>OSCA ID</h3>
                <p class="text"><?= htmlspecialchars($senior['osca_id_no'] ?: 'Not assigned') ?></p>
            </div>
        </div>
    </div>

    <!-- Detailed Information -->
    <div class="profile-details">
        <div class="detail-section">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Full Name:</span>
                    <span class="value"><?= htmlspecialchars($senior['first_name'] . ' ' . ($senior['middle_name'] ? $senior['middle_name'] . ' ' : '') . $senior['last_name'] . ($senior['ext_name'] ? ' ' . $senior['ext_name'] : '')) ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Date of Birth:</span>
                    <span class="value"><?= $senior['date_of_birth'] ? date('F j, Y', strtotime($senior['date_of_birth'])) : 'Not specified' ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Sex:</span>
                    <span class="value"><?= ucfirst($senior['sex'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Civil Status:</span>
                    <span class="value"><?= ucfirst($senior['civil_status'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Place of Birth:</span>
                    <span class="value"><?= htmlspecialchars($senior['place_of_birth'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Educational Attainment:</span>
                    <span class="value"><?= htmlspecialchars($senior['educational_attainment'] ?: 'Not specified') ?></span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-briefcase"></i> Employment & Income</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Occupation:</span>
                    <span class="value"><?= htmlspecialchars($senior['occupation'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Annual Income:</span>
                    <span class="value"><?= $senior['annual_income'] ? '₱' . number_format($senior['annual_income'], 2) : 'Not specified' ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Other Skills:</span>
                    <span class="value"><?= htmlspecialchars($senior['other_skills'] ?: 'None') ?></span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-home"></i> Contact & Location</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Barangay:</span>
                    <span class="value"><?= htmlspecialchars($senior['barangay'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Purok:</span>
                    <span class="value"><?= htmlspecialchars($senior['purok'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Contact Number:</span>
                    <span class="value"><?= htmlspecialchars($senior['contact'] ?: 'Not provided') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Cellphone:</span>
                    <span class="value"><?= htmlspecialchars($senior['cellphone'] ?: 'Not provided') ?></span>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-heart"></i> Health & Benefits</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Health Condition:</span>
                    <span class="value"><?= htmlspecialchars($senior['health_condition'] ?: 'Not specified') ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Benefits Received:</span>
                    <span class="value"><?= $senior['benefits_received'] ? 'Yes' : 'No' ?></span>
                </div>
            </div>
        </div>

        <?php if ($senior['remarks']): ?>
        <div class="detail-section">
            <h3><i class="fas fa-sticky-note"></i> Remarks</h3>
            <div class="remarks-content">
                <p><?= nl2br(htmlspecialchars($senior['remarks'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Event Attendance -->
    <div class="profile-section">
        <h3><i class="fas fa-calendar-check"></i> Recent Event Attendance</h3>
        <?php
        // Try to fetch recent event attendance for this senior
        $attendance = [];
        try {
            // Check if event_attendance table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'event_attendance'");
            if ($tableCheck->rowCount() > 0) {
                $attendanceStmt = $pdo->prepare('
                    SELECT e.name, e.date, ea.attended_at 
                    FROM events e 
                    LEFT JOIN event_attendance ea ON e.id = ea.event_id AND ea.senior_id = ? 
                    WHERE e.date <= CURDATE() 
                    ORDER BY e.date DESC 
                    LIMIT 5
                ');
                $attendanceStmt->execute([$senior['id']]);
                $attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // Table doesn't exist or other error - just show empty state
            $attendance = [];
        }
        ?>
        
        <?php if ($attendance): ?>
        <div class="attendance-list">
            <?php foreach ($attendance as $event): ?>
            <div class="attendance-item">
                <div class="event-info">
                    <h4><?= htmlspecialchars($event['name']) ?></h4>
                    <p class="event-date"><?= date('F j, Y', strtotime($event['date'])) ?></p>
                </div>
                <div class="attendance-status">
                    <?php if ($event['attended_at']): ?>
                    <span class="status-badge attended">
                        <i class="fas fa-check"></i>
                        Attended
                    </span>
                    <?php else: ?>
                    <span class="status-badge not-attended">
                        <i class="fas fa-times"></i>
                        Not Attended
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-attendance">
            <div class="no-attendance-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h4>No Event Attendance</h4>
            <p>This senior has not attended any events yet.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
