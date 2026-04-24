<?php
require_once 'db_connection.php';

// Only show students who are Enrolled and Qualified
$stmt = $pdo->query("
    SELECT * FROM enrollees 
    WHERE enrollment_status = 'Enrolled' 
    AND qualification_status = 'Qualified'
    AND (is_archived = 0 OR is_archived IS NULL)
    ORDER BY created_at DESC
");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolled Students - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        
        .header { background: #2c3e50; color: white; text-align: center; padding: 20px; }
        .header img { height: 60px; margin-bottom: 10px; }
        .header h1 { font-size: 24px; margin: 5px 0; }
        .header p { font-size: 12px; opacity: 0.9; margin: 3px 0; }
        
        .nav { background: #34495e; padding: 12px; display: flex; justify-content: center; gap: 25px; flex-wrap: wrap; }
        .nav a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .nav a:hover, .nav a.active { background: #e74c3c; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .page-header h2 { color: #2c3e50; }
        .back-btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; }
        tr:hover { background: #f5f5f5; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .nav { gap: 10px; }
            .nav a { padding: 5px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/logo.png" alt="Logo">
        <h1>DAILY BREAD LEARNING CENTER INC.</h1>
        <p>Preschool Department - Academy Year 2026-2027</p>
        <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City | 0923-4701532</p>
    </div>
    
    <div class="nav">
        <a href="welcome.php">Home</a>
        <a href="index.php">Registration Form</a>
        <a href="view_enrollees.php" class="active">Enrolled Students</a>
        <a href="tuition_fees.php">Tuition and Fees</a>
        <a href="welcome.php#portals">Staff Portals</a>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2>Enrolled Students</h2>
        </div>
        
        <p style="margin-bottom: 15px; color: #666;">Total Enrolled: <?php echo count($enrollees); ?> students</p>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Program</th>
                        <th>Student Type</th>
                        <th>Enrollment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($enrollees as $e): ?>
                    <tr>
                        <td><?php echo $e['enrollee_id']; ?></td>
                        <td><?php echo htmlspecialchars($e['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($e['first_name']); ?></td>
                        <td><?php echo $e['program_level']; ?></td>
                        <td><?php echo $e['student_type']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($enrollees) == 0): ?>
                        <tr><td colspan="6" style="text-align: center;">No enrolled students found. Check back later!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure enrollment database</p>
    </div>
</body>
</html>