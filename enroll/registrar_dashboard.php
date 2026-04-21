<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';

// Handle qualification update
if(isset($_POST['update_qualification'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $qualification_status = $_POST['qualification_status'];
    $requirements_status = $_POST['requirements_status'];
    $requirements_notes = $_POST['requirements_notes'];
    $enrollment_status = $_POST['enrollment_status'];
    $enrollment_status_reason = $_POST['enrollment_status_reason'];
    $enrollment_status_date = date('Y-m-d');
    
    // Get old status for audit
    $old = $pdo->prepare("SELECT qualification_status, requirements_status, enrollment_status FROM enrollees WHERE enrollee_id = ?");
    $old->execute([$enrollee_id]);
    $old_data = $old->fetch(PDO::FETCH_ASSOC);
    
    // Update statuses
    $stmt = $pdo->prepare("UPDATE enrollees SET qualification_status = ?, requirements_status = ?, requirements_notes = ?, enrollment_status = ?, enrollment_status_reason = ?, enrollment_status_date = ? WHERE enrollee_id = ?");
    $stmt->execute([$qualification_status, $requirements_status, $requirements_notes, $enrollment_status, $enrollment_status_reason, $enrollment_status_date, $enrollee_id]);
    
    // Log the change
    $log = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, 'UPDATE', 'enrollees', ?, ?, ?)");
    $log->execute([
        $_SESSION['user_id'], 
        $enrollee_id, 
        "qualification: {$old_data['qualification_status']}, requirements: {$old_data['requirements_status']}, enrollment: {$old_data['enrollment_status']}", 
        "qualification: $qualification_status, requirements: $requirements_status, enrollment: $enrollment_status"
    ]);
    
    $success = "Student status updated successfully!";
}

// Handle individual requirement toggle
if(isset($_POST['toggle_requirement'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $requirement_field = $_POST['requirement_field'];
    $current_value = $_POST['current_value'];
    $new_value = $current_value == 1 ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE enrollees SET $requirement_field = ? WHERE enrollee_id = ?");
    $stmt->execute([$new_value, $enrollee_id]);
    
    // Auto-update requirements status based on all requirements
    $check = $pdo->prepare("SELECT birth_cert_received, id_picture_received, report_card_received, immunization_record_received, medical_cert_received FROM enrollees WHERE enrollee_id = ?");
    $check->execute([$enrollee_id]);
    $reqs = $check->fetch(PDO::FETCH_ASSOC);
    
    $all_complete = ($reqs['birth_cert_received'] && $reqs['id_picture_received'] && $reqs['immunization_record_received']);
    $new_req_status = $all_complete ? 'Complete' : 'Incomplete';
    
    $update = $pdo->prepare("UPDATE enrollees SET requirements_status = ? WHERE enrollee_id = ?");
    $update->execute([$new_req_status, $enrollee_id]);
    
    $success = "Requirement updated!";
}

// Fetch all enrollees with documents info
$stmt = $pdo->query("
    SELECT e.*, 
           d.birth_certificate_path, 
           d.id_picture_path, 
           d.report_card_path,
           CASE WHEN d.birth_certificate_path IS NOT NULL AND d.birth_certificate_path != '' THEN 1 ELSE 0 END as has_birth_cert,
           CASE WHEN d.id_picture_path IS NOT NULL AND d.id_picture_path != '' THEN 1 ELSE 0 END as has_id_picture,
           CASE WHEN d.report_card_path IS NOT NULL AND d.report_card_path != '' THEN 1 ELSE 0 END as has_report_card
    FROM enrollees e
    LEFT JOIN documents d ON e.enrollee_id = d.enrollee_id
    ORDER BY e.enrollee_id DESC
");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        
        .header { background: #3498db; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-left img { height: 40px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .container { padding: 20px; max-width: 1400px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-note { background: #e8f4fd; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; color: #2980b9; font-size: 13px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        
        .student-card { background: white; border-radius: 10px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .student-header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .student-header h3 { font-size: 18px; }
        .student-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #f39c12; color: white; }
        .badge-qualified { background: #27ae60; color: white; }
        .badge-not-qualified { background: #e74c3c; color: white; }
        .badge-complete { background: #27ae60; color: white; }
        .badge-incomplete { background: #e74c3c; color: white; }
        .badge-enrolled { background: #27ae60; color: white; }
        .badge-dropped { background: #e74c3c; color: white; }
        .badge-transferred { background: #f39c12; color: white; }
        .badge-on-leave { background: #3498db; color: white; }
        
        .student-body { padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .student-body { grid-template-columns: 1fr; } }
        
        .requirements-section, .status-section { background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .requirements-section h4, .status-section h4 { color: #2c3e50; margin-bottom: 15px; border-bottom: 2px solid #3498db; padding-bottom: 8px; }
        
        .requirement-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .requirement-item:last-child { border-bottom: none; }
        .requirement-label { display: flex; align-items: center; gap: 10px; }
        .requirement-status { font-size: 12px; padding: 3px 10px; border-radius: 15px; }
        .status-received { background: #27ae60; color: white; }
        .status-missing { background: #e74c3c; color: white; }
        .toggle-btn { background: #3498db; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .toggle-btn:hover { background: #2980b9; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px; }
        .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .update-btn { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        .update-btn:hover { background: #219a52; }
        
        .view-link { color: #3498db; text-decoration: none; font-size: 12px; margin-left: 10px; }
        .view-link:hover { text-decoration: underline; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="images/logo.png" alt="Logo">
        <h2>Registrar Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="info-note">
        <span>📋 Review each student's requirements. Mark received items to track completion status.</span>
        <span>✅ Students with all requirements marked as "Received" will automatically show as "Complete".</span>
        <span>📌 Update enrollment status (Enrolled/Dropped/Transferred/On Leave) as needed.</span>
    </div>
    
    <h3>Student Requirements Review</h3>
    <p style="margin-bottom: 20px; color: #666;">Review documents, mark requirements as received, and update enrollment status.</p>
    
    <?php foreach($enrollees as $e): ?>
    <div class="student-card">
        <div class="student-header">
            <h3>#<?php echo $e['enrollee_id']; ?> - <?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></h3>
            <div>
                <span class="student-badge badge-<?php echo strtolower($e['qualification_status'] == 'Not Qualified' ? 'not-qualified' : $e['qualification_status']); ?>">
                    <?php echo $e['qualification_status']; ?>
                </span>
                <span class="student-badge badge-<?php echo strtolower($e['requirements_status'] == 'Complete' ? 'complete' : 'incomplete'); ?>" style="margin-left: 10px;">
                    Req: <?php echo $e['requirements_status']; ?>
                </span>
                <span class="student-badge badge-<?php echo strtolower($e['enrollment_status']); ?>" style="margin-left: 10px;">
                    <?php echo $e['enrollment_status']; ?>
                </span>
            </div>
        </div>
        
        <div class="student-body">
            <!-- Requirements Section -->
            <div class="requirements-section">
                <h4>📎 Requirements Checklist</h4>
                
                <div class="requirement-item">
                    <div class="requirement-label">
                        <span>📄 Birth Certificate</span>
                        <?php if($e['has_birth_cert']): ?>
                            <a href="<?php echo $e['birth_certificate_path']; ?>" target="_blank" class="view-link">View</a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if($e['has_birth_cert']): ?>
                            <span class="requirement-status status-received">Uploaded</span>
                        <?php else: ?>
                            <span class="requirement-status status-missing">Not Uploaded</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="birth_cert_received">
                            <input type="hidden" name="current_value" value="<?php echo $e['birth_cert_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn">
                                <?php echo $e['birth_cert_received'] ? '✓ Received' : '○ Mark Received'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-label">
                        <span>🖼️ 2x2 ID Picture</span>
                        <?php if($e['has_id_picture']): ?>
                            <a href="<?php echo $e['id_picture_path']; ?>" target="_blank" class="view-link">View</a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if($e['has_id_picture']): ?>
                            <span class="requirement-status status-received">Uploaded</span>
                        <?php else: ?>
                            <span class="requirement-status status-missing">Not Uploaded</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="id_picture_received">
                            <input type="hidden" name="current_value" value="<?php echo $e['id_picture_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn">
                                <?php echo $e['id_picture_received'] ? '✓ Received' : '○ Mark Received'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-label">
                        <span>📑 Report Card / Grades</span>
                        <?php if($e['has_report_card']): ?>
                            <a href="<?php echo $e['report_card_path']; ?>" target="_blank" class="view-link">View</a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if($e['has_report_card']): ?>
                            <span class="requirement-status status-received">Uploaded</span>
                        <?php else: ?>
                            <span class="requirement-status status-missing">Not Uploaded</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="report_card_received">
                            <input type="hidden" name="current_value" value="<?php echo $e['report_card_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn">
                                <?php echo $e['report_card_received'] ? '✓ Received' : '○ Mark Received'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-label">
                        <span>💉 Immunization Record</span>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="immunization_record_received">
                            <input type="hidden" name="current_value" value="<?php echo $e['immunization_record_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn">
                                <?php echo $e['immunization_record_received'] ? '✓ Received' : '○ Mark Received'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-label">
                        <span>🏥 Medical Certificate</span>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="medical_cert_received">
                            <input type="hidden" name="current_value" value="<?php echo $e['medical_cert_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn">
                                <?php echo $e['medical_cert_received'] ? '✓ Received' : '○ Mark Received'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Status Update Section -->
            <div class="status-section">
                <h4>📋 Student Status</h4>
                <form method="POST">
                    <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                    
                    <div class="form-group">
                        <label>Qualification Status</label>
                        <select name="qualification_status" required>
                            <option value="Pending" <?php echo $e['qualification_status'] == 'Pending' ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="Qualified" <?php echo $e['qualification_status'] == 'Qualified' ? 'selected' : ''; ?>>Qualified - Approved</option>
                            <option value="Not Qualified" <?php echo $e['qualification_status'] == 'Not Qualified' ? 'selected' : ''; ?>>Not Qualified - Missing Requirements</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Requirements Status</label>
                        <select name="requirements_status" required>
                            <option value="Pending" <?php echo $e['requirements_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Complete" <?php echo $e['requirements_status'] == 'Complete' ? 'selected' : ''; ?>>Complete - All Requirements Received</option>
                            <option value="Incomplete" <?php echo $e['requirements_status'] == 'Incomplete' ? 'selected' : ''; ?>>Incomplete - Missing Requirements</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Enrollment Status</label>
                        <select name="enrollment_status" required>
                            <option value="Enrolled" <?php echo $e['enrollment_status'] == 'Enrolled' ? 'selected' : ''; ?>>Enrolled - Active Student</option>
                            <option value="Dropped" <?php echo $e['enrollment_status'] == 'Dropped' ? 'selected' : ''; ?>>Dropped - Withdrawn from School</option>
                            <option value="Transferred" <?php echo $e['enrollment_status'] == 'Transferred' ? 'selected' : ''; ?>>Transferred - Moved to Other School</option>
                            <option value="On Leave" <?php echo $e['enrollment_status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave - Temporary Absence</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status Reason (if Dropped/Transferred/On Leave)</label>
                        <textarea name="enrollment_status_reason" rows="2" placeholder="Enter reason for status change..."><?php echo htmlspecialchars($e['enrollment_status_reason'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes / Comments</label>
                        <textarea name="requirements_notes" rows="3" placeholder="Add notes about missing requirements or special instructions..."><?php echo htmlspecialchars($e['requirements_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_qualification" class="update-btn">Update All Statuses</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="footer">
    <p>© Daily Bread Learning Center Inc. — Registrar Dashboard | All changes are logged and visible to Admin</p>
</div>
</body>
</html>