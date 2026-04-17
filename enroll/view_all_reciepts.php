<?php
session_start();
if(!isset($_SESSION['role']) || ($_SESSION['role'] != 'cashier' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}
require_once 'db_connection.php';

$stmt = $pdo->query("
    SELECT pt.*, e.first_name, e.last_name, e.program_level 
    FROM payment_transactions pt
    JOIN enrollees e ON pt.enrollee_id = e.enrollee_id
    ORDER BY pt.created_at DESC
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Receipts - Daily Bread Learning Center</title>
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
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background: #f39c12;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-btn {
            background: #2c3e50;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
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
            background: #e67e22;
            color: white;
        }
        
        .view-btn {
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .view-btn:hover {
            background: #219a52;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>All Payment Receipts</h2>
        <a href="cashier_dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Receipt #</th>
                <th>Student Name</th>
                <th>Program</th>
                <th>Payment Type</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Processed By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($transactions as $t): ?>
            <tr>
                <td><?php echo $t['receipt_number']; ?></td>
                <td><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></td>
                <td><?php echo $t['program_level']; ?></td>
                <td><?php echo $t['payment_type']; ?></td>
                <td>₱<?php echo number_format($t['payment_amount'], 2); ?></td>
                <td><?php echo date('M d, Y', strtotime($t['payment_date'])); ?></td>
                <td><?php echo $t['processed_by']; ?></td>
                <td><a href="view_receipt.php?id=<?php echo $t['transaction_id']; ?>" class="view-btn" target="_blank">View Receipt</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>