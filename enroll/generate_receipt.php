<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php?role=cashier");
    exit();
}
require_once 'db_connection.php';

$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : 0;

// Fetch student details
$stmt = $pdo->prepare("
    SELECT e.*, 
           m.full_name as mother_name, m.contact_number as mother_phone,
           f.full_name as father_name, f.contact_number as father_phone
    FROM enrollees e
    LEFT JOIN mother_info m ON e.enrollee_id = m.enrollee_id
    LEFT JOIN father_info f ON e.enrollee_id = f.enrollee_id
    WHERE e.enrollee_id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    die("Student not found!");
}

// Generate receipt number
function generateReceiptNumber($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT receipt_number FROM payment_transactions WHERE receipt_number LIKE ? ORDER BY transaction_id DESC LIMIT 1");
    $stmt->execute(["RCP-{$year}-%"]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($last) {
        $parts = explode('-', $last['receipt_number']);
        $last_num = intval(end($parts));
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    
    return "RCP-{$year}-{$new_num}";
}

$receipt_number = generateReceiptNumber($pdo);
$current_date = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Receipt - Daily Bread Learning Center</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .receipt-container { max-width: 500px; width: 100%; }
        .receipt { background: white; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 1px solid #ddd; }
        .receipt-header { text-align: center; border-bottom: 2px solid #2c3e50; padding-bottom: 15px; margin-bottom: 20px; }
        .receipt-header h2 { color: #2c3e50; margin-bottom: 5px; }
        .receipt-header p { color: #666; font-size: 11px; }
        .receipt-title { text-align: center; margin: 20px 0; }
        .receipt-title h3 { color: #2c3e50; letter-spacing: 2px; }
        .receipt-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dotted #ddd; }
        .receipt-label { font-weight: bold; }
        .receipt-total { margin-top: 20px; padding-top: 15px; border-top: 2px solid #2c3e50; font-size: 16px; font-weight: bold; }
        .receipt-footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666; }
        .btn-group { margin-top: 20px; display: flex; gap: 10px; justify-content: center; }
        .btn-print, .btn-back { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-print { background: #27ae60; color: white; }
        .btn-print:hover { background: #219a52; }
        .btn-back { background: #3498db; color: white; text-decoration: none; }
        .btn-back:hover { background: #2980b9; }
        @media print {
            .btn-group, .no-print { display: none; }
            body { background: white; padding: 0; }
            .receipt { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>
<div class="receipt-container">
    <div class="receipt" id="receipt">
        <div class="receipt-header">
            <h2>DAILY BREAD LEARNING CENTER INC.</h2>
            <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City</p>
            <p>Tel: 0923-4701532 | Email: info@dailybread.edu.ph</p>
        </div>
        
        <div class="receipt-title">
            <h3>OFFICIAL RECEIPT</h3>
        </div>
        
        <div class="receipt-details">
            <div class="receipt-row">
                <span class="receipt-label">Receipt Number:</span>
                <span><?php echo $receipt_number; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Date:</span>
                <span><?php echo $current_date; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Student ID:</span>
                <span><?php echo $student['enrollee_id']; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Student Name:</span>
                <span><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Program Level:</span>
                <span><?php echo $student['program_level']; ?></span>
            </div>
            <?php if($student['mother_name']): ?>
            <div class="receipt-row">
                <span class="receipt-label">Mother's Name:</span>
                <span><?php echo htmlspecialchars($student['mother_name']); ?></span>
            </div>
            <?php endif; ?>
            <?php if($student['father_name']): ?>
            <div class="receipt-row">
                <span class="receipt-label">Father's Name:</span>
                <span><?php echo htmlspecialchars($student['father_name']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="receipt-total">
            <div class="receipt-row">
                <span class="receipt-label">Payment Plan:</span>
                <span><?php echo $student['payment_plan']; ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Total Amount:</span>
                <span>₱<?php echo number_format($student['payment_amount'], 2); ?></span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>Received by: <?php echo $_SESSION['full_name']; ?></p>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Thank you for choosing Daily Bread Learning Center!</p>
        </div>
    </div>
    
    <div class="btn-group no-print">
        <button onclick="window.print()" class="btn-print">Print Receipt</button>
        <a href="cashier_dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</div>

<script>
    // Auto print dialog when page loads
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    }
</script>
</body>
</html>