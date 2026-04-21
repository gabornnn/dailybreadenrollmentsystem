<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php?role=cashier");
    exit();
}
require_once 'db_connection.php';

// Function to generate receipt number
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

// Handle payment update
if(isset($_POST['update_payment'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $payment_amount = $_POST['payment_amount'];
    
    $receipt_number = generateReceiptNumber($pdo);
    
    $stmt = $pdo->prepare("
        SELECT e.payment_amount as total_fee,
               COALESCE(SUM(CASE WHEN pt.payment_type = 'Payment' THEN pt.payment_amount ELSE 0 END), 0) as total_paid,
               COALESCE(SUM(CASE WHEN pt.payment_type = 'Refund' THEN ABS(pt.payment_amount) ELSE 0 END), 0) as total_refunded
        FROM enrollees e
        LEFT JOIN payment_transactions pt ON e.enrollee_id = pt.enrollee_id
        WHERE e.enrollee_id = ?
        GROUP BY e.enrollee_id
    ");
    $stmt->execute([$enrollee_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_fee = $result['total_fee'];
    $total_paid = $result['total_paid'];
    $total_refunded = $result['total_refunded'];
    $net_paid = $total_paid - $total_refunded;
    $new_net_paid = $net_paid + $payment_amount;
    
    if($new_net_paid >= $total_fee) {
        $payment_status = 'Fully Paid';
    } elseif($new_net_paid > 0) {
        $payment_status = 'Partial';
    } else {
        $payment_status = 'Unpaid';
    }
    
    $stmt = $pdo->prepare("UPDATE enrollees SET payment_status = ? WHERE enrollee_id = ?");
    $stmt->execute([$payment_status, $enrollee_id]);
    
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, receipt_number, processed_by, processed_by_user_id, payment_status) VALUES (?, CURDATE(), ?, 'Payment', ?, ?, ?, ?)");
    $stmt->execute([$enrollee_id, $payment_amount, $receipt_number, $_SESSION['full_name'], $_SESSION['user_id'], $payment_status]);
    
    header("Location: cashier_dashboard.php?success=Payment of ₱" . number_format($payment_amount, 2) . " recorded! Receipt: $receipt_number");
    exit();
}

// Handle refund
if(isset($_POST['process_refund'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $refund_amount = $_POST['refund_amount'];
    $refund_reason = $_POST['refund_reason'];
    $receipt_number = generateReceiptNumber($pdo);
    
    $stmt = $pdo->prepare("
        SELECT e.payment_amount as total_fee,
               COALESCE(SUM(CASE WHEN pt.payment_type = 'Payment' THEN pt.payment_amount ELSE 0 END), 0) as total_paid,
               COALESCE(SUM(CASE WHEN pt.payment_type = 'Refund' THEN ABS(pt.payment_amount) ELSE 0 END), 0) as total_refunded
        FROM enrollees e
        LEFT JOIN payment_transactions pt ON e.enrollee_id = pt.enrollee_id
        WHERE e.enrollee_id = ?
        GROUP BY e.enrollee_id
    ");
    $stmt->execute([$enrollee_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_fee = $result['total_fee'];
    $total_paid = $result['total_paid'];
    $total_refunded = $result['total_refunded'];
    $net_paid = $total_paid - $total_refunded;
    $new_net_paid = $net_paid - $refund_amount;
    
    if($new_net_paid <= 0) {
        $payment_status = 'Unpaid';
    } elseif($new_net_paid >= $total_fee) {
        $payment_status = 'Fully Paid';
    } else {
        $payment_status = 'Partial';
    }
    
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, receipt_number, refund_amount, refund_date, refund_reason, refund_status, processed_by, processed_by_user_id, payment_status) VALUES (?, CURDATE(), ?, 'Refund', ?, ?, CURDATE(), ?, 'Processed', ?, ?, ?)");
    $stmt->execute([$enrollee_id, -$refund_amount, $receipt_number, $refund_amount, $refund_reason, $_SESSION['full_name'], $_SESSION['user_id'], $payment_status]);
    
    $stmt = $pdo->prepare("UPDATE enrollees SET payment_status = ? WHERE enrollee_id = ?");
    $stmt->execute([$payment_status, $enrollee_id]);
    
    header("Location: cashier_dashboard.php?success=Refund of ₱" . number_format($refund_amount, 2) . " processed! New Status: $payment_status. Receipt: $receipt_number");
    exit();
}

// Fetch all enrollees
$stmt = $pdo->query("
    SELECT e.*, 
           COALESCE(SUM(CASE WHEN pt.payment_type = 'Payment' THEN pt.payment_amount ELSE 0 END), 0) as total_paid,
           COALESCE(SUM(CASE WHEN pt.payment_type = 'Refund' THEN ABS(pt.payment_amount) ELSE 0 END), 0) as total_refunded
    FROM enrollees e
    LEFT JOIN payment_transactions pt ON e.enrollee_id = pt.enrollee_id
    GROUP BY e.enrollee_id
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
    <title>Cashier Dashboard - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-left img { height: 40px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .container { padding: 20px; max-width: 1400px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-note { background: #fef5e8; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; color: #f39c12; font-size: 13px; }
        
        .generate-manual-btn {
            background: #27ae60;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .generate-manual-btn:hover { background: #219a52; }
        
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: middle; }
        th { background: #e67e22; color: white; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .fully-paid { background: #27ae60; color: white; }
        .partial { background: #f39c12; color: white; }
        .unpaid { background: #e74c3c; color: white; }
        
        .payment-form { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .payment-form input { padding: 6px 10px; border-radius: 5px; border: 1px solid #ddd; width: 120px; }
        .payment-form button { background: #27ae60; color: white; border: none; padding: 6px 15px; border-radius: 5px; cursor: pointer; }
        .payment-form button:hover { background: #219a52; }
        
        .refund-toggle { background: #e74c3c; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; margin-top: 5px; }
        .refund-toggle:hover { background: #c0392b; }
        
        .receipt-btn { background: #27ae60; color: white; padding: 6px 12px; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; }
        .receipt-btn:hover { background: #219a52; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        .refund-row { background: #fff5f5; }
        .refund-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 10px; }
        .refund-form input { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .refund-form button { background: #e74c3c; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; }
        .refund-form .cancel-btn { background: #95a5a6; }
        
        .net-paid-positive { color: #27ae60; font-weight: bold; }
        .net-paid-zero { color: #e74c3c; font-weight: bold; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .payment-form { flex-direction: column; }
            .payment-form input, .payment-form button { width: 100%; }
        }
    </style>
    <script>
        function toggleRefund(id) {
            var refundRow = document.getElementById('refund-' + id);
            if (refundRow.style.display === 'none' || refundRow.style.display === '') {
                refundRow.style.display = 'table-row';
            } else {
                refundRow.style.display = 'none';
            }
        }
    </script>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="images/logo.png" alt="Logo">
        <h2>Cashier Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <?php if($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="info-note">
        Receipt numbers are automatically generated in format: RCP-YYYY-XXXX (e.g., RCP-2024-0001)
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h3>Student Payment Records</h3>
    </div>
    <p style="margin-bottom: 15px; color: #666;">Update payment status - Receipt number is auto-generated.</p>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Program</th>
                    <th>Payment Plan</th>
                    <th>Total Amount</th>
                    <th>Net Paid</th>
                    <th>Refunded</th>
                    <th>Payment Status</th>
                    <th>Update Payment</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($enrollees as $e): 
                    $net_paid = $e['total_paid'] - $e['total_refunded'];
                    $net_paid_class = $net_paid <= 0 ? 'net-paid-zero' : ($net_paid >= $e['payment_amount'] ? 'net-paid-positive' : '');
                ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td><?php echo $e['payment_plan']; ?></td>
                    <td>₱<?php echo number_format($e['payment_amount'], 2); ?></td>
                    <td class="<?php echo $net_paid_class; ?>">₱<?php echo number_format($net_paid, 2); ?></td>
                    <td>₱<?php echo number_format($e['total_refunded'], 2); ?></td>
                    <td>
                        <span class="badge 
                            <?php 
                            if($net_paid <= 0) echo 'unpaid';
                            elseif($net_paid >= $e['payment_amount']) echo 'fully-paid';
                            else echo 'partial';
                            ?>">
                            <?php 
                            if($net_paid <= 0) echo 'Unpaid';
                            elseif($net_paid >= $e['payment_amount']) echo 'Fully Paid';
                            else echo 'Partial';
                            ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="payment-form">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="number" name="payment_amount" placeholder="Amount Paid" step="0.01" required>
                            <button type="submit" name="update_payment">Update & Generate Receipt</button>
                        </form>
                        <button type="button" class="refund-toggle" onclick="toggleRefund(<?php echo $e['enrollee_id']; ?>)">Process Refund</button>
                    </td>
                    <td>
                        <a href="generate_receipt.php?student_id=<?php echo $e['enrollee_id']; ?>" class="receipt-btn">Generate Receipt</a>
                    </td>
                </tr>
                <tr id="refund-<?php echo $e['enrollee_id']; ?>" class="refund-row" style="display: none;">
                    <td colspan="10" style="padding: 15px;">
                        <strong style="color: #e74c3c;">Process Refund for <?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></strong>
                        <form method="POST" class="refund-form">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <input type="number" name="refund_amount" placeholder="Refund Amount" step="0.01" required style="width: 150px;">
                            <input type="text" name="refund_reason" placeholder="Reason for Refund" required style="width: 250px;">
                            <button type="submit" name="process_refund">Process Refund</button>
                            <button type="button" class="cancel-btn" onclick="toggleRefund(<?php echo $e['enrollee_id']; ?>)">Cancel</button>
                        </form>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">Note: Maximum refund amount is ₱<?php echo number_format($net_paid, 2); ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="footer">
    <p>© Daily Bread Learning Center Inc. — Cashier Dashboard | All changes are logged and visible to Admin</p>
</div>
</body>
</html>