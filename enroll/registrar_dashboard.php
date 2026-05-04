<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';

// Fetch all enrollees (active only, not archived)
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
    WHERE e.is_archived = 0 OR e.is_archived IS NULL
    ORDER BY e.enrollee_id DESC
");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);
$success = isset($_GET['success']) ? $_GET['success'] : '';
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
        
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-left img { height: 40px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .container { padding: 20px; max-width: 1200px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-note { background: #e8f4fd; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; color: #2980b9; font-size: 13px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        
        .student-table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        .student-table th, .student-table td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .student-table th { background: #2c3e50; color: white; font-weight: 600; }
        .student-table tr:hover { background: #f5f5f5; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-qualified { background: #27ae60; color: white; }
        .badge-pending { background: #f39c12; color: white; }
        .badge-not-qualified { background: #e74c3c; color: white; }
        .badge-enrolled { background: #27ae60; color: white; }
        .badge-pending-enrollment { background: #f39c12; color: white; }
        
        .btn-view { 
            background: #3498db; 
            color: white; 
            border: none; 
            padding: 6px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 12px; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-view:hover { background: #2980b9; }
        .btn-archive { background: #e74c3c; color: white; padding: 6px 15px; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; margin-left: 10px; }
        .btn-archive:hover { background: #c0392b; }
        .btn-refund { background: #e74c3c; color: white; padding: 6px 15px; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; margin-left: 10px; }
        .btn-refund:hover { background: #c0392b; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .info-note { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="images/logo.png" alt="Logo">
        <h2>Registrar Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    </div>
    <div>
        <a href="archive_students.php" class="btn-archive">📦 Manage Archive</a>
        <a href="refund_approval.php" class="btn-refund">💰 Refund Approvals</a>
        <a href="logout.php" class="logout-btn" style="margin-left: 10px;">Logout</a>
    </div>
</div>

<div class="container">
    <?php if($success): ?>
        <div class="success">✓ <?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="info-note">
        <span>📋 Click "View Details" to review requirements and update student status.</span>
        <span>💰 Refund requests need to be reviewed in "Refund Approvals".</span>
    </div>
    
    <table class="student-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Program</th>
                <th>Qualification</th>
                <th>Enrollment Status</th>
                <th style="text-align: center;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($enrollees as $e): ?>
            <tr>
                <td><?php echo $e['enrollee_id']; ?></td>
                <td><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></td>
                <td><?php echo $e['program_level']; ?></td>
                <td>
                    <span class="badge badge-<?php echo strtolower($e['qualification_status'] == 'Not Qualified' ? 'not-qualified' : $e['qualification_status']); ?>">
                        <?php echo $e['qualification_status']; ?>
                    </span>
                </td>
                <td>
                    <span class="badge badge-<?php echo strtolower($e['enrollment_status']); ?>">
                        <?php echo $e['enrollment_status']; ?>
                    </span>
                </td>
                <td style="text-align: center;">
                    <a href="update_student.php?id=<?php echo $e['enrollee_id']; ?>" class="btn-view">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(count($enrollees) == 0): ?>
                <tr><td colspan="6" style="text-align: center;">No active students found</td</span>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="footer">
    <p>Daily Bread Learning Center Inc. — Registrar Dashboard | Approve students to mark them as Enrolled</p>
</div>
</body>
</html>