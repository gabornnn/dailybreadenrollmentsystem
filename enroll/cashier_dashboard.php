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
    // Get the last receipt number for this year
    $stmt = $pdo->prepare("SELECT receipt_number FROM payment_transactions WHERE receipt_number LIKE ? ORDER BY transaction_id DESC LIMIT 1");
    $stmt->execute(["RCP-{$year}-%"]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($last) {
        // Extract the number part and increment
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
    $payment_status = $_POST['payment_status'];
    $payment_amount = $_POST['payment_amount'];
    
    // Auto-generate receipt number
    $receipt_number = generateReceiptNumber($pdo);
    
    // Get old status
    $old = $pdo->prepare("SELECT payment_status FROM enrollees WHERE enrollee_id = ?");
    $old->execute([$enrollee_id]);
    $old_status = $old->fetchColumn();
    
    // Update status
    $stmt = $pdo->prepare("UPDATE enrollees SET payment_status = ? WHERE enrollee_id = ?");
    $stmt->execute([$payment_status, $enrollee_id]);
    
    // Record transaction with auto-generated receipt number
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, receipt_number, processed_by, processed_by_user_id) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
    $stmt->execute([$enrollee_id, $payment_amount, $payment_status, $receipt_number, $_SESSION['full_name'], $_SESSION['user_id']]);
    
    // Log the change
    $log = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, 'UPDATE', 'enrollees', ?, ?, ?)");
    $log->execute([$_SESSION['user_id'], $enrollee_id, "payment: $old_status", "payment: $payment_status"]);
    
    $success = "Payment updated! Receipt Number: $receipt_number";
}

// Fetch all enrollees
$stmt = $pdo->query("SELECT * FROM enrollees ORDER BY enrollee_id DESC");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - Daily Bread Learning Center</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        .header { background: #f39c12; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .container { padding: 20px; max-width: 1400px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .dashboard-nav { background: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 15px; }
        .dashboard-nav a { background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .dashboard-nav a:hover { background: #e67e22; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #e67e22; color: white; }
        input, select, button { padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; }
        button { background: #f39c12; color: white; border: none; cursor: pointer; }
        button:hover { background: #e67e22; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .fully-paid { background: #27ae60; color: white; }
        .partial { background: #f39c12; color: white; }
        .unpaid { background: #e74c3c; color: white; }
        .payment-form { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .receipt-link { background: #27ae60; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-size: 12px; }
        .receipt-link:hover { background: #219a52; }
        .info-note { background: #fef5e8; padding: 10px; border-radius: 5px; margin-bottom: 20px; color: #f39c12; font-size: 13px; }
        .receipt-number-display { font-family: monospace; font-weight: bold; color: #27ae60; }
    </style>
</head>
<body>
<div class="header">
    <h2>Cashier Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="dashboard-nav">
        <a href="cashier_dashboard.php">Payment Management</a>
        <a href="generate_receipt.php">Generate Receipt</a>
    </div>
    
    <div class="info-note">
        Receipt numbers are automatically generated in format: RCP-YYYY-XXXX (e.g., RCP-2024-0001)
    </div>
    
    <h3>Student Payment Records</h3>
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
                    <th>Payment Status</th>
                    <th>Update Payment</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($enrollees as $e): ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td><?php echo $e['payment_plan']; ?></td>
                    <td>₱<?php echo number_format($e['payment_amount'], 2); ?></td>
                    <td>
                        <span class="badge 
                            <?php 
                            if($e['payment_status'] == 'Fully Paid') echo 'fully-paid';
                            elseif($e['payment_status'] == 'Partial') echo 'partial';
                            else echo 'unpaid';
                            ?>">
                            <?php echo $e['payment_status']; ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="payment-form">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <select name="payment_status" required>
                                <option value="Unpaid" <?php echo $e['payment_status'] == 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="Partial" <?php echo $e['payment_status'] == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                                <option value="Fully Paid" <?php echo $e['payment_status'] == 'Fully Paid' ? 'selected' : ''; ?>>Fully Paid</option>
                            </select>
                            <input type="number" name="payment_amount" placeholder="Amount Paid" step="0.01" required>
                            <button type="submit" name="update_payment">Update & Generate Receipt</button>
                        </form>
                    </td>
                    <td>
                        <a href="generate_receipt.php?student_id=<?php echo $e['enrollee_id']; ?>" class="receipt-link">Generate Receipt</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>