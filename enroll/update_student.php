<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';

$success = '';
$error = '';
$student = null;

// Handle update
if(isset($_POST['update_qualification'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $qualification_status = $_POST['qualification_status'];
    $requirements_status = $_POST['requirements_status'];
    $requirements_notes = $_POST['requirements_notes'];
    $enrollment_status = $_POST['enrollment_status'];
    $enrollment_status_reason = $_POST['enrollment_status_reason'];
    
    // Get old status for audit
    $old = $pdo->prepare("SELECT qualification_status, requirements_status, enrollment_status FROM enrollees WHERE enrollee_id = ?");
    $old->execute([$enrollee_id]);
    $old_data = $old->fetch(PDO::FETCH_ASSOC);
    
    // Update statuses
    $stmt = $pdo->prepare("UPDATE enrollees SET qualification_status = ?, requirements_status = ?, requirements_notes = ?, enrollment_status = ?, enrollment_status_reason = ? WHERE enrollee_id = ?");
    
    if($stmt->execute([$qualification_status, $requirements_status, $requirements_notes, $enrollment_status, $enrollment_status_reason, $enrollee_id])) {
        // Format old and new data
        $old_formatted = "Enrollment: {$old_data['enrollment_status']} | Qualification: {$old_data['qualification_status']} | Requirements: {$old_data['requirements_status']}";
        $new_formatted = "Enrollment: {$enrollment_status} | Qualification: {$qualification_status} | Requirements: {$requirements_status}";
        
        // Log the change
        $log = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, 'STATUS UPDATE', 'enrollees', ?, ?, ?)");
        $log->execute([$_SESSION['user_id'], $enrollee_id, $old_formatted, $new_formatted]);
        
        $success = "Student status updated successfully!";
    } else {
        $error = "Failed to update student status.";
    }
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
    
    header("Location: update_student.php?id=$enrollee_id&success=Requirement updated!");
    exit();
}

// Get student details with document info
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("
        SELECT e.*, 
               d.birth_certificate_path, 
               d.id_picture_path, 
               d.report_card_path,
               CASE WHEN d.birth_certificate_path IS NOT NULL AND d.birth_certificate_path != '' THEN 1 ELSE 0 END as has_birth_cert,
               CASE WHEN d.id_picture_path IS NOT NULL AND d.id_picture_path != '' THEN 1 ELSE 0 END as has_id_picture,
               CASE WHEN d.report_card_path IS NOT NULL AND d.report_card_path != '' THEN 1 ELSE 0 END as has_report_card
        FROM enrollees e
        LEFT JOIN documents d ON e.enrollee_id = d.enrollee_id
        WHERE e.enrollee_id = ?
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$student) {
        header("Location: registrar_dashboard.php");
        exit();
    }
} elseif(isset($_POST['enrollee_id'])) {
    $id = $_POST['enrollee_id'];
    $stmt = $pdo->prepare("
        SELECT e.*, 
               d.birth_certificate_path, 
               d.id_picture_path, 
               d.report_card_path,
               CASE WHEN d.birth_certificate_path IS NOT NULL AND d.birth_certificate_path != '' THEN 1 ELSE 0 END as has_birth_cert,
               CASE WHEN d.id_picture_path IS NOT NULL AND d.id_picture_path != '' THEN 1 ELSE 0 END as has_id_picture,
               CASE WHEN d.report_card_path IS NOT NULL AND d.report_card_path != '' THEN 1 ELSE 0 END as has_report_card
        FROM enrollees e
        LEFT JOIN documents d ON e.enrollee_id = d.enrollee_id
        WHERE e.enrollee_id = ?
    ");
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student - Registrar</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        
        .header { background: #3498db; color: white; padding: 20px; text-align: center; }
        .header h2 { margin: 0; }
        
        .content { padding: 25px; }
        
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px; }
        .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; }
        
        .update-btn { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; font-size: 16px; }
        .update-btn:hover { background: #219a52; }
        
        .back-btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .back-btn:hover { background: #2980b9; }
        
        .section-title { font-size: 18px; font-weight: bold; color: #2c3e50; margin: 20px 0 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        
        .student-info { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .student-info p { margin: 5px 0; }
        
        /* Requirements Checklist Styles */
        .requirements-checklist { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .requirement-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee; }
        .requirement-item:last-child { border-bottom: none; }
        .requirement-name { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .requirement-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-received { background: #27ae60; color: white; }
        .status-missing { background: #e74c3c; color: white; }
        .status-pending { background: #f39c12; color: white; }
        .toggle-btn { background: #3498db; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .toggle-btn:hover { background: #2980b9; }
        .view-link { color: #3498db; text-decoration: none; margin-left: 10px; font-size: 12px; }
        .view-link:hover { text-decoration: underline; }
        .req-badge { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        .req-received { background: #27ae60; }
        .req-missing { background: #e74c3c; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 15px; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Update Student Status</h2>
    </div>
    
    <div class="content">
        <a href="registrar_dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?> Redirecting in 2 seconds...</div>
            <script>setTimeout(function() { window.location.href = 'registrar_dashboard.php'; }, 2000);</script>
        <?php endif; ?>
        
        <?php if($success_msg): ?>
            <div class="success">✓ <?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($student && !$success): ?>
            <div class="student-info">
                <p><strong>Student ID:</strong> <?php echo $student['enrollee_id']; ?></p>
                <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                <p><strong>Program:</strong> <?php echo $student['program_level']; ?></p>
                <p><strong>Current Payment Status:</strong> <?php echo $student['payment_status']; ?></p>
            </div>
            
            <!-- Requirements Checklist Section -->
            <div class="section-title">📎 Requirements Checklist</div>
            <div class="requirements-checklist">
                
                <div class="requirement-item">
                    <div class="requirement-name">
                        <span class="req-badge <?php echo $student['has_birth_cert'] ? 'req-received' : 'req-missing'; ?>"></span>
                        📄 Birth Certificate
                    </div>
                    <div>
                        <?php if($student['has_birth_cert']): ?>
                            <span class="requirement-status status-received">✓ Uploaded</span>
                            <a href="<?php echo $student['birth_certificate_path']; ?>" target="_blank" class="view-link">View</a>
                        <?php else: ?>
                            <span class="requirement-status status-missing">✗ Not Uploaded</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="birth_cert_received">
                            <input type="hidden" name="current_value" value="<?php echo $student['birth_cert_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['birth_cert_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-name">
                        <span class="req-badge <?php echo $student['has_id_picture'] ? 'req-received' : 'req-missing'; ?>"></span>
                        🖼️ 2x2 ID Picture
                    </div>
                    <div>
                        <?php if($student['has_id_picture']): ?>
                            <span class="requirement-status status-received">✓ Uploaded</span>
                            <a href="<?php echo $student['id_picture_path']; ?>" target="_blank" class="view-link">View</a>
                        <?php else: ?>
                            <span class="requirement-status status-missing">✗ Not Uploaded</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="id_picture_received">
                            <input type="hidden" name="current_value" value="<?php echo $student['id_picture_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['id_picture_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-name">
                        <span class="req-badge <?php echo $student['has_report_card'] ? 'req-received' : 'req-missing'; ?>"></span>
                        📑 Report Card / Grades
                    </div>
                    <div>
                        <?php if($student['has_report_card']): ?>
                            <span class="requirement-status status-received">✓ Uploaded</span>
                            <a href="<?php echo $student['report_card_path']; ?>" target="_blank" class="view-link">View</a>
                        <?php else: ?>
                            <span class="requirement-status status-missing">✗ Not Uploaded</span>
                        <?php endif; ?>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="report_card_received">
                            <input type="hidden" name="current_value" value="<?php echo $student['report_card_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['report_card_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-name">
                        <span class="req-badge <?php echo $student['immunization_record_received'] ? 'req-received' : 'req-missing'; ?>"></span>
                        💉 Immunization Record
                    </div>
                    <div>
                        <span class="requirement-status <?php echo $student['immunization_record_received'] ? 'status-received' : 'status-missing'; ?>">
                            <?php echo $student['immunization_record_received'] ? '✓ Received' : '✗ Not Received'; ?>
                        </span>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="immunization_record_received">
                            <input type="hidden" name="current_value" value="<?php echo $student['immunization_record_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['immunization_record_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item">
                    <div class="requirement-name">
                        <span class="req-badge <?php echo $student['medical_cert_received'] ? 'req-received' : 'req-missing'; ?>"></span>
                        🏥 Medical Certificate
                    </div>
                    <div>
                        <span class="requirement-status <?php echo $student['medical_cert_received'] ? 'status-received' : 'status-missing'; ?>">
                            <?php echo $student['medical_cert_received'] ? '✓ Received' : '✗ Not Received'; ?>
                        </span>
                        <form method="POST" style="display: inline; margin-left: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                            <input type="hidden" name="requirement_field" value="medical_cert_received">
                            <input type="hidden" name="current_value" value="<?php echo $student['medical_cert_received']; ?>">
                            <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['medical_cert_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                        </form>
                    </div>
                </div>
                
                <div class="requirement-item" style="border-top: 2px solid #ddd; margin-top: 5px; padding-top: 12px;">
                    <div class="requirement-name">
                        <strong>Overall Requirements Status:</strong>
                    </div>
                    <div>
                        <span class="requirement-status <?php echo $student['requirements_status'] == 'Complete' ? 'status-received' : 'status-missing'; ?>">
                            <?php echo $student['requirements_status']; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Student Status Form -->
            <div class="section-title">📋 Student Status</div>
            
            <form method="POST">
                <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Qualification Status</label>
                        <select name="qualification_status" required>
                            <option value="Pending" <?php echo $student['qualification_status'] == 'Pending' ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="Qualified" <?php echo $student['qualification_status'] == 'Qualified' ? 'selected' : ''; ?>>Qualified - Approved</option>
                            <option value="Not Qualified" <?php echo $student['qualification_status'] == 'Not Qualified' ? 'selected' : ''; ?>>Not Qualified</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Requirements Status</label>
                        <select name="requirements_status" required>
                            <option value="Pending" <?php echo $student['requirements_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Complete" <?php echo $student['requirements_status'] == 'Complete' ? 'selected' : ''; ?>>Complete</option>
                            <option value="Incomplete" <?php echo $student['requirements_status'] == 'Incomplete' ? 'selected' : ''; ?>>Incomplete</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Enrollment Status</label>
                        <select name="enrollment_status" required>
                            <option value="Enrolled" <?php echo $student['enrollment_status'] == 'Enrolled' ? 'selected' : ''; ?>>Enrolled - Active Student</option>
                            <option value="Dropped" <?php echo $student['enrollment_status'] == 'Dropped' ? 'selected' : ''; ?>>Dropped - Withdrawn</option>
                            <option value="Transferred" <?php echo $student['enrollment_status'] == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                            <option value="On Leave" <?php echo $student['enrollment_status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason (if Dropped/Transferred)</label>
                        <textarea name="enrollment_status_reason" rows="2" placeholder="Enter reason..."><?php echo htmlspecialchars($student['enrollment_status_reason'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes / Comments</label>
                    <textarea name="requirements_notes" rows="3" placeholder="Add notes..."><?php echo htmlspecialchars($student['requirements_notes'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_qualification" class="update-btn">Update Status</button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>Daily Bread Learning Center Inc. — Update Student Status</p>
    </div>
</div>
</body>
</html>