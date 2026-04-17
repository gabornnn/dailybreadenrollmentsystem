<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['role']) || ($_SESSION['role'] != 'cashier' && $_SESSION['role'] != 'admin' && $_SESSION['role'] != 'registrar')) {
    header("Location: login.php");
    exit();
}

$transaction_id = isset($_GET['id']) ? $_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT pt.*, e.first_name, e.last_name, e.program_level, e.payment_plan,
           m.full_name as mother_name, f.full_name as father_name
    FROM payment_transactions pt
    JOIN enrollees e ON pt.enrollee_id = e.enrollee_id
    LEFT JOIN mother_info m ON e.enrollee_id = m.enrollee_id
    LEFT JOIN father_info f ON e.enrollee_id = f.enrollee_id
    WHERE pt.transaction_id = ?
");
$stmt->execute([$transaction_id]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$receipt) {
    die("Receipt not found!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Receipt - Daily Bread Learning Center</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .receipt {
            border: 2px solid #2c3e50;
            padding: 30px;
            max-width: 800px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .receipt-header h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .receipt-header p {
            color: #666;
            font-size: 12px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        .receipt-label {
            font-weight: bold;
        }
        .receipt-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #2c3e50;
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .btn-print {
            display: block;
            width: 200px;
            margin: 20px auto 0;
            padding: 10px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                background: white;
                padding: 0;
            }
            .receipt {
                border: none;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
<div>
    <div class="receipt" id="receipt">
        <div class="receipt-header">
            <h2>DAILY BREAD LEARNING CENTER INC.</h2>
            <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City</p>
            <p>Tel: 0923-4701532 | Email: info@dailybread.edu.ph</p>
            <h3>OFFICIAL RECEIPT</h3>
        </div>
        
        <div class="receipt-details">
            <div class="receipt-row">
                <span class="receipt-label">Receipt Number:</span>
                <span><?php echo $receipt['receipt_number']; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Date:</span>
                <span><?php echo date('F d, Y', strtotime($receipt['payment_date'])); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Student Name:</span>
                <span><?php echo $receipt['first_name'] . ' ' . $receipt['last_name']; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Program Level:</span>
                <span><?php echo $receipt['program_level']; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Payment Type:</span>
                <span><?php echo $receipt['payment_type']; ?></span>
            </div>
            <?php if($receipt['mother_name']): ?>
            <div class="receipt-row">
                <span class="receipt-label">Mother's Name:</span>
                <span><?php echo $receipt['mother_name']; ?></span>
            </div>
            <?php endif; ?>
            <?php if($receipt['father_name']): ?>
            <div class="receipt-row">
                <span class="receipt-label">Father's Name:</span>
                <span><?php echo $receipt['father_name']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="receipt-total">
            <div class="receipt-row">
                <span class="receipt-label">Amount Paid:</span>
                <span>PHP <?php echo number_format($receipt['payment_amount'], 2); ?></span>
            </div>
            <?php if($receipt['notes']): ?>
            <div class="receipt-row">
                <span class="receipt-label">Notes:</span>
                <span><?php echo $receipt['notes']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="receipt-footer">
            <p>Received by: <?php echo $receipt['processed_by']; ?></p>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Thank you for choosing Daily Bread Learning Center!</p>
        </div>
    </div>
    
    <button onclick="window.print()" class="btn-print">Print Receipt</button>
    <button onclick="window.close()" class="btn-print" style="background: #e74c3c; margin-top: 10px;">Close</button>
</div>
</body>
</html>