<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php?role=cashier");
    exit();
}
require_once 'db_connection.php';

$success = '';
$error = '';

// Process approved refund
if(isset($_POST['process_refund'])) {
    $request_id = $_POST['request_id'];
    $receipt_number = $_POST['receipt_number'];
    
    // Get refund request details
    $stmt = $pdo->prepare("SELECT * FROM refund_requests WHERE request_id = ? AND status = 'approved'");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($request) {
        // Record refund transaction
        $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, receipt_number, refund_amount, refund_date, refund_reason, refund_status, processed_by, processed_by_user_id, request_id) VALUES (?, CURDATE(), ?, 'Refund', ?, ?, CURDATE(), ?, 'Processed', ?, ?, ?)");
        $stmt->execute([$request['enrollee_id'], -$request['refund_amount'], $receipt_number, $request['refund_amount'], $request['refund_reason'], $_SESSION['full_name'], $_SESSION['user_id'], $request_id]);
        
        // Update refund request status
        $stmt = $pdo->prepare("UPDATE refund_requests SET status = 'processed', processed_by = ?, processed_date = CURDATE() WHERE request_id = ?");
        $stmt->execute([$_SESSION['user_id'], $request_id]);
        
        $success = "Refund processed successfully! Receipt: $receipt_number";
    } else {
        $error = "Refund request not found or not approved.";
    }
}

// Fetch approved refund requests waiting for processing
$stmt = $pdo->query("
    SELECT rr.*, e.first_name, e.last_name, e.program_level
    FROM refund_requests rr
    JOIN enrollees e ON rr.enrollee_id = e.enrollee_id
    WHERE rr.status = 'approved'
    ORDER BY rr.approved_date ASC
");
$approved_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch processed refunds
$stmt = $pdo->query("
    SELECT rr.*, e.first_name, e.last_name, e.program_level, pt.receipt_number as transaction_receipt
    FROM refund_requests rr
    JOIN enrollees e ON rr.enrollee_id = e.enrollee_id
    LEFT JOIN payment_transactions pt ON rr.request_id = pt.request_id
    WHERE rr.status = 'processed'
    ORDER BY rr.processed_date DESC
");
$processed_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Refunds - Cashier</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #f39c12; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .back-btn { background: #2c3e50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .content { background: white; padding: 25px; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 40px; }
        .section h3 { color: #2c3e50; margin-bottom: 15px; border-left: 4px solid #e74c3c; padding-left: 10px; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        
        .request-card { background: #f9f9f9; border: 1px solid #ddd; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .request-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
        .badge-approved { background: #27ae60; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-processed { background: #3498db; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        
        .request-info { margin: 15px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-label { font-weight: bold; color: #555; }
        
        .btn-process { background: #27ae60; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-view { background: #3498db; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; border-radius: 10px; }
        
        @media (max-width: 768px) {
            .request-info { grid-template-columns: 1fr; }
            .request-header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>💰 Process Refunds</h2>
        <a href="cashier_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Approved Refunds Waiting for Processing -->
        <div class="section">
            <h3>✅ Approved Refunds (Ready to Process)</h3>
            <?php if(count($approved_requests) > 0): ?>
                <?php foreach($approved_requests as $req): ?>
                <div class="request-card">
                    <div class="request-header">
                        <strong>Request #<?php echo $req['request_id']; ?> - <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                        <span class="badge-approved">Approved by Registrar</span>
                    </div>
                    <div class="request-info">
                        <div><span class="info-label">Student ID:</span> <?php echo $req['enrollee_id']; ?></div>
                        <div><span class="info-label">Program:</span> <?php echo $req['program_level']; ?></div>
                        <div><span class="info-label">Request Date:</span> <?php echo date('M d, Y', strtotime($req['request_date'])); ?></div>
                        <div><span class="info-label">Refund Amount:</span> ₱<?php echo number_format($req['refund_amount'], 2); ?></div>
                        <div><span class="info-label">Reason:</span> <?php echo nl2br(htmlspecialchars($req['refund_reason'])); ?></div>
                        <div><span class="info-label">Letter:</span> 
                            <a href="<?php echo $req['letter_path']; ?>" target="_blank" class="btn-view">📄 View Letter</a>
                        </div>
                    </div>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                        <input type="hidden" name="receipt_number" value="<?php echo generateReceiptNumber($pdo); ?>">
                        <button type="submit" name="process_refund" class="btn-process" onclick="return confirm('Process this refund? This action cannot be undone.')">💰 Process Refund</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No approved refunds waiting for processing.</p>
            <?php endif; ?>
        </div>
        
        <!-- Processed Refunds History -->
        <div class="section">
            <h3>📜 Processed Refunds History</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Student</th><th>Amount</th><th>Receipt #</th><th>Processed By</th><th>Letter</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($processed_requests as $req): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($req['processed_date'])); ?></td>
                            <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                            <td>₱<?php echo number_format($req['refund_amount'], 2); ?></td>
                            <td><?php echo $req['transaction_receipt'] ?? '-'; ?></td>
                            <td><?php echo $req['processed_by_name'] ?? 'Cashier'; ?></td>
                            <td><a href="<?php echo $req['letter_path']; ?>" target="_blank" class="btn-view">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($processed_requests) == 0): ?>
                            <tr><td colspan="6" style="text-align: center;">No processed refunds</td</span>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Daily Bread Learning Center Inc. — Refund Processing | Cashier processes approved refunds</p>
    </div>
</div>
</body>
</html>