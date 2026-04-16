<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php?role=cashier");
    exit();
}
require_once 'db_connection.php';

// Handle payment update
if(isset($_POST['update_payment'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $payment_status = $_POST['payment_status'];
    $payment_amount = $_POST['payment_amount'];
    $receipt_number = $_POST['receipt_number'];
    
    $stmt = $pdo->prepare("UPDATE enrollees SET payment_status = ? WHERE enrollee_id = ?");
    $stmt->execute([$payment_status, $enrollee_id]);
    
    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, receipt_number, processed_by) VALUES (?, CURDATE(), ?, ?, ?, ?)");
    $stmt->execute([$enrollee_id, $payment_amount, $payment_status, $receipt_number, $_SESSION['full_name']]);
    
    $success = "Payment status updated!";
}

// Fetch all enrollees
$stmt = $pdo->query("SELECT * FROM enrollees ORDER BY created_at DESC");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - Daily Bread</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        .header { background: #f39c12; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .container { padding: 20px; max-width: 1300px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
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
        .payment-form input, .payment-form select { min-width: 100px; }
    </style>
</head>
<body>
<div class="header">
    <h2>💰 Cashier Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    
    <h3>💵 Update Payment Status</h3>
    <p style="margin-bottom: 15px; color: #666;">Record payments and update student payment status.</p>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr><th>ID</th><th>Student Name</th><th>Program</th><th>Payment Plan</th><th>Total Amount</th><th>Current Status</th><th>Update Payment</th></tr>
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
                        <span class="badge <?php echo strtolower(str_replace(' ', '-', $e['payment_status'])); ?>">
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
                            <input type="text" name="receipt_number" placeholder="Receipt #" required>
                            <button type="submit" name="update_payment">Record Payment</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>