<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';

$success = '';
$error = '';

// Archive a student (move to archive)
if(isset($_POST['archive_student'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $archive_reason = $_POST['archive_reason'];
    
    $stmt = $pdo->prepare("UPDATE enrollees SET is_archived = 1, archived_date = CURDATE(), archive_reason = ?, enrollment_status = 'Archived' WHERE enrollee_id = ?");
    if($stmt->execute([$archive_reason, $enrollee_id])) {
        $success = "Student has been archived successfully!";
    } else {
        $error = "Failed to archive student.";
    }
}

// Restore archived student
if(isset($_POST['restore_student'])) {
    $enrollee_id = $_POST['enrollee_id'];
    
    $stmt = $pdo->prepare("UPDATE enrollees SET is_archived = 0, archived_date = NULL, archive_reason = NULL, enrollment_status = 'Enrolled' WHERE enrollee_id = ?");
    if($stmt->execute([$enrollee_id])) {
        $success = "Student has been restored successfully!";
    } else {
        $error = "Failed to restore student.";
    }
}

// Permanently delete archived student
if(isset($_POST['delete_permanent'])) {
    $enrollee_id = $_POST['enrollee_id'];
    
    $stmt = $pdo->prepare("DELETE FROM enrollees WHERE enrollee_id = ?");
    if($stmt->execute([$enrollee_id])) {
        $success = "Student has been permanently deleted!";
    } else {
        $error = "Failed to delete student.";
    }
}

// Get active students
$stmt = $pdo->query("SELECT * FROM enrollees WHERE is_archived = 0 OR is_archived IS NULL ORDER BY enrollee_id DESC");
$active_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get archived students
$stmt = $pdo->query("SELECT * FROM enrollees WHERE is_archived = 1 ORDER BY archived_date DESC");
$archived_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Management - Registrar</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .back-btn { background: #2c3e50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .content { background: white; padding: 25px; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 30px; }
        .section h3 { color: #2c3e50; margin-bottom: 15px; border-left: 4px solid #3498db; padding-left: 10px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        tr:hover { background: #f5f5f5; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-active { background: #27ae60; color: white; }
        .badge-archived { background: #e74c3c; color: white; }
        
        .btn-archive, .btn-restore, .btn-delete { padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .btn-archive { background: #e74c3c; color: white; }
        .btn-restore { background: #27ae60; color: white; }
        .btn-delete { background: #c0392b; color: white; }
        
        .archive-form { display: inline-flex; gap: 5px; align-items: center; }
        .archive-form select { padding: 5px; border-radius: 5px; border: 1px solid #ddd; }
        
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; border-radius: 10px; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .archive-form { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Archive Management</h2>
        <a href="registrar_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Active Students Section -->
        <div class="section">
            <h3>📋 Active Students</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($active_students as $s): ?>
                        <tr>
                            <td><?php echo $s['enrollee_id']; ?></td>
                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                            <td><?php echo $s['program_level']; ?></td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <form method="POST" class="archive-form" onsubmit="return confirm('Archive this student? They can be restored later.');">
                                    <input type="hidden" name="enrollee_id" value="<?php echo $s['enrollee_id']; ?>">
                                    <select name="archive_reason" required>
                                        <option value="">Select Reason</option>
                                        <option value="Dropped out">Dropped out</option>
                                        <option value="Transferred">Transferred to other school</option>
                                        <option value="Financial unable">Financial unable</option>
                                        <option value="Moved location">Moved to other location</option>
                                        <option value="Other">Other reason</option>
                                    </select>
                                    <button type="submit" name="archive_student" class="btn-archive">Archive</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($active_students) == 0): ?>
                            <tr><td colspan="5" style="text-align: center;">No active students found</td>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Archived Students Section -->
        <div class="section">
            <h3>📦 Archived Students (Dropped/Transferred)</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Archive Date</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($archived_students as $s): ?>
                        <tr>
                            <td><?php echo $s['enrollee_id']; ?></td>
                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                            <td><?php echo $s['program_level']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($s['archived_date'])); ?></td>
                            <td><?php echo htmlspecialchars($s['archive_reason']); ?></td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="enrollee_id" value="<?php echo $s['enrollee_id']; ?>">
                                    <button type="submit" name="restore_student" class="btn-restore" onclick="return confirm('Restore this student? They will become active again.');">Restore</button>
                                </form>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="enrollee_id" value="<?php echo $s['enrollee_id']; ?>">
                                    <button type="submit" name="delete_permanent" class="btn-delete" onclick="return confirm('Permanently delete this student? This cannot be undone!');">Delete Permanent</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($archived_students) == 0): ?>
                            <tr><td colspan="6" style="text-align: center;">No archived students found</td>
                            <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Daily Bread Learning Center Inc. — Archive Management for Dropped/Transferred Students</p>
    </div>
</div>
</body>
</html>