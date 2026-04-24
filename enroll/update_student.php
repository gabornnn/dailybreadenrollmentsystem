<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';
require_once 'includes_functions.php';

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
        
        // Check if status changed to Dropped - AUTO-ARCHIVE
        if($enrollment_status == 'Dropped' && $old_data['enrollment_status'] != 'Dropped') {
            $archive = $pdo->prepare("UPDATE enrollees SET is_archived = 1, archived_date = CURDATE(), archive_reason = ? WHERE enrollee_id = ?");
            $archive->execute([$enrollment_status_reason, $enrollee_id]);
            $success = "Student dropped and archived successfully!";
        } 
        // Check if restored from Dropped
        elseif($old_data['enrollment_status'] == 'Dropped' && $enrollment_status != 'Dropped') {
            $restore = $pdo->prepare("UPDATE enrollees SET is_archived = 0, archived_date = NULL, archive_reason = NULL WHERE enrollee_id = ?");
            $restore->execute([$enrollee_id]);
        }
        
        // Format old and new data for audit
        $old_formatted = "Status: {$old_data['enrollment_status']} | Qualification: {$old_data['qualification_status']} | Requirements: {$old_data['requirements_status']}";
        $new_formatted = "Status: {$enrollment_status} | Qualification: {$qualification_status} | Requirements: {$requirements_status}";
        
        // Log the change
        $log = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, 'STATUS UPDATE', 'enrollees', ?, ?, ?)");
        $log->execute([$_SESSION['user_id'], $enrollee_id, $old_formatted, $new_formatted]);
        
        if(empty($success)) {
            $success = "Student status updated successfully!";
        }
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
    
    $all_complete = ($reqs['birth_cert_received'] && $reqs['id_picture_received'] && 
                     $reqs['report_card_received'] && $reqs['immunization_record_received'] && 
                     $reqs['medical_cert_received']);
    $new_req_status = $all_complete ? 'Complete' : 'Incomplete';
    
    $update = $pdo->prepare("UPDATE enrollees SET requirements_status = ? WHERE enrollee_id = ?");
    $update->execute([$new_req_status, $enrollee_id]);
    
    header("Location: update_student.php?id=$enrollee_id&success=Requirement updated!");
    exit();
}

// Get student details
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
        .content { padding: 25px; }
        .back-btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .section-title { font-size: 18px; font-weight: bold; color: #2c3e50; margin: 20px 0 15px; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .student-info { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px; }
        .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-row .form-group { flex: 1; }
        .update-btn { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; font-size: 16px; }
        .update-btn:hover { background: #219a52; }
        .requirement-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .toggle-btn { background: #3498db; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .status-received { color: #27ae60; font-weight: bold; }
        .status-missing { color: #e74c3c; font-weight: bold; }
        .view-link { 
            color: #3498db; 
            text-decoration: none; 
            margin-left: 10px;
            cursor: pointer;
            display: inline-block;
            padding: 2px 8px;
            background: #e8f4fd;
            border-radius: 5px;
        }
        .view-link:hover { background: #d1ecf9; }
        .footer { background: #2c3e50; color: white; text-align: center; padding: 15px; font-size: 12px; }
        .approval-note { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .modal-header h3 {
            color: #2c3e50;
        }
        
        .close-modal {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .close-modal:hover {
            background: #c0392b;
        }
        
        .proof-image {
            max-width: 100%;
            max-height: 70vh;
            display: block;
            margin: 0 auto;
        }
        
        .proof-pdf {
            width: 100%;
            height: 80vh;
            border: none;
        }
        
        .modal-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        .btn-download {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-download:hover {
            background: #2980b9;
        }
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
                <p><strong>Current Status:</strong> 
                    <span style="background: <?php echo $student['enrollment_status'] == 'Enrolled' ? '#27ae60' : '#f39c12'; ?>; color:white; padding:3px 10px; border-radius:15px;">
                        <?php echo $student['enrollment_status']; ?>
                    </span>
                </p>
            </div>
            
            <?php if($student['enrollment_status'] == 'Pending'): ?>
            <div class="approval-note">
                <strong>📋 Pending Application</strong><br>
                Review all documents and requirements below. Once approved, the student will appear in the "Enrolled Students" page.
                <br><br>
                <strong>Note:</strong> If you set status to "Dropped", the student will be automatically archived.
            </div>
            <?php endif; ?>
            
            <!-- Requirements Checklist -->
            <div class="section-title">📎 Requirements Checklist</div>
            <div class="requirement-item">
                <span>📄 Birth Certificate</span>
                <div>
                    <?php if($student['has_birth_cert']): ?>
                        <span class="status-received">✓ Uploaded</span>
                        <button class="view-link" onclick="viewDocument('<?php echo $student['birth_certificate_path']; ?>', 'Birth Certificate')">View</button>
                    <?php else: ?>
                        <span class="status-missing">✗ Not Uploaded</span>
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
                <span>🖼️ 2x2 ID Picture</span>
                <div>
                    <?php if($student['has_id_picture']): ?>
                        <span class="status-received">✓ Uploaded</span>
                        <button class="view-link" onclick="viewDocument('<?php echo $student['id_picture_path']; ?>', '2x2 ID Picture')">View</button>
                    <?php else: ?>
                        <span class="status-missing">✗ Not Uploaded</span>
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
                <span>📑 Report Card / Grades</span>
                <div>
                    <?php if($student['has_report_card']): ?>
                        <span class="status-received">✓ Uploaded</span>
                        <button class="view-link" onclick="viewDocument('<?php echo $student['report_card_path']; ?>', 'Report Card')">View</button>
                    <?php else: ?>
                        <span class="status-missing">✗ Not Uploaded</span>
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
                <span>💉 Immunization Record</span>
                <div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                        <input type="hidden" name="requirement_field" value="immunization_record_received">
                        <input type="hidden" name="current_value" value="<?php echo $student['immunization_record_received']; ?>">
                        <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['immunization_record_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                    </form>
                </div>
            </div>
            
            <div class="requirement-item">
                <span>🏥 Medical Certificate</span>
                <div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                        <input type="hidden" name="requirement_field" value="medical_cert_received">
                        <input type="hidden" name="current_value" value="<?php echo $student['medical_cert_received']; ?>">
                        <button type="submit" name="toggle_requirement" class="toggle-btn"><?php echo $student['medical_cert_received'] ? '✓ Mark as Not Received' : '○ Mark as Received'; ?></button>
                    </form>
                </div>
            </div>
            
            <div class="section-title" style="margin-top: 20px;">📋 Student Status</div>
            
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
                            <option value="Pending" <?php echo $student['enrollment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending (Application Received)</option>
                            <option value="Enrolled" <?php echo $student['enrollment_status'] == 'Enrolled' ? 'selected' : ''; ?>>Enrolled (Fully Approved)</option>
                            <option value="Dropped" <?php echo $student['enrollment_status'] == 'Dropped' ? 'selected' : ''; ?>>Dropped - Withdrawn (Auto-Archive)</option>
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
        <p>Daily Bread Learning Center Inc. — Update Student Status | Set status to "Dropped" to auto-archive</p>
    </div>
</div>

<!-- Modal Popup for Viewing Documents -->
<div id="documentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Document Viewer</h3>
            <button class="close-modal" onclick="closeDocumentModal()">✕ Close</button>
        </div>
        <div id="modalBody" style="text-align: center;">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <a id="downloadLink" href="#" class="btn-download" download>📥 Download</a>
        </div>
    </div>
</div>

<script>
function viewDocument(filePath, documentName) {
    var modal = document.getElementById('documentModal');
    var modalTitle = document.getElementById('modalTitle');
    var modalBody = document.getElementById('modalBody');
    var downloadLink = document.getElementById('downloadLink');
    
    modalTitle.innerHTML = documentName + ' - Student Document';
    downloadLink.href = filePath;
    
    var fileExtension = filePath.split('.').pop().toLowerCase();
    
    if (fileExtension === 'pdf') {
        modalBody.innerHTML = '<iframe src="' + filePath + '" class="proof-pdf"></iframe>';
    } else if (fileExtension === 'jpg' || fileExtension === 'jpeg' || fileExtension === 'png' || fileExtension === 'gif') {
        modalBody.innerHTML = '<img src="' + filePath + '" class="proof-image" alt="Document">';
    } else {
        modalBody.innerHTML = '<p>Unable to preview this file type. <a href="' + filePath + '" target="_blank">Click here to open</a></p>';
    }
    
    modal.style.display = 'flex';
}

function closeDocumentModal() {
    document.getElementById('documentModal').style.display = 'none';
}

// Close modal when clicking outside the content
window.onclick = function(event) {
    var modal = document.getElementById('documentModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDocumentModal();
    }
});
</script>
</body>
</html>