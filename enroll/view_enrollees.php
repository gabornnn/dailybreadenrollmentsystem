<?php
require_once 'db_connection.php';

// Get all enrollees
$stmt = $pdo->query("SELECT * FROM enrollees ORDER BY created_at DESC");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolled Students - Daily Bread Learning Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .nav {
            background: #34495e;
            padding: 0;
            display: flex;
        }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            display: inline-block;
        }
        .nav a:hover, .nav a.active {
            background: #27ae60;
        }
        .content {
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #27ae60;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .back-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Enrolled Students Database</h1>
        <p>Daily Bread Learning Center Inc.</p>
    </div>
    
    <div class="nav">
    <a href="welcome.php">Home</a>
    <a href="index.php" >Registration Form</a>
    <a href="view_enrollees.php" class="active">Enrolled Students</a>
    <a href="tuition_fees.php">Tuition and Fees</a>
    <a href="welcome.php#portals">Staff Portals</a>
</div>
    
    <div class="content">
        <a href="index.php" class="back-btn">← Back to Registration</a>
        
        <h2>Total Enrolled: <?php echo count($enrollees); ?> students</h2>
        
        <table>
            <thead>
                <tr><th>ID</th><th>Last Name</th><th>First Name</th><th>Program</th><th>Student Type</th><th>Payment</th><th>Enrollment Date</th></tr>
            </thead>
            <tbody>
                <?php foreach($enrollees as $e): ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($e['first_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td><?php echo $e['student_type']; ?></td>
                    <td>₱<?php echo number_format($e['payment_amount'], 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure enrollment database</p>
    </div>
</div>
</body>
</html>