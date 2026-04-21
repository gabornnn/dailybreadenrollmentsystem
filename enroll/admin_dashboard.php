<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

// Fix: Get total paid from payments, total refunded from refunds
$stmt = $pdo->query("
    SELECT 
        e.*,
        COALESCE((SELECT SUM(payment_amount) FROM payment_transactions WHERE enrollee_id = e.enrollee_id AND payment_type = 'Payment'), 0) as total_paid,
        COALESCE((SELECT SUM(refund_amount) FROM payment_transactions WHERE enrollee_id = e.enrollee_id AND payment_type = 'Refund'), 0) as total_refunded
    FROM enrollees e
    ORDER BY e.enrollee_id DESC
");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch audit log with user relations
$stmt = $pdo->query("
    SELECT al.*, u.full_name, u.role, u.username
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 30
");
$audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payment transactions with user relations
$stmt = $pdo->query("
    SELECT pt.*, u.full_name as cashier_name, u.username
    FROM payment_transactions pt
    LEFT JOIN users u ON pt.processed_by_user_id = u.user_id
    ORDER BY pt.created_at DESC
    LIMIT 20
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$total = count($enrollees);
$qualified = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE qualification_status = 'Qualified'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE qualification_status = 'Pending'")->fetchColumn();
$not_qualified = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE qualification_status = 'Not Qualified'")->fetchColumn();
$fully_paid = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE payment_status = 'Fully Paid'")->fetchColumn();
$partial = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE payment_status = 'Partial'")->fetchColumn();
$unpaid = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE payment_status = 'Unpaid'")->fetchColumn();
$enrolled = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE enrollment_status = 'Enrolled'")->fetchColumn();
$dropped = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE enrollment_status = 'Dropped'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-left img { height: 40px; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .view-only-badge { background: #e74c3c; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        
        .container { padding: 20px; max-width: 1400px; margin: auto; }
        
        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; flex: 1; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); min-width: 140px; }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; font-size: 13px; }
        
        .section-title { margin: 25px 0 15px; color: #2c3e50; border-left: 4px solid #e74c3c; padding-left: 15px; }
        
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; position: sticky; top: 0; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .qualified { background: #27ae60; color: white; }
        .pending { background: #f39c12; color: white; }
        .not-qualified { background: #e74c3c; color: white; }
        .fully-paid { background: #27ae60; color: white; }
        .partial { background: #f39c12; color: white; }
        .unpaid { background: #e74c3c; color: white; }
        .enrolled { background: #27ae60; color: white; }
        .dropped { background: #e74c3c; color: white; }
        .transferred { background: #f39c12; color: white; }
        .on-leave { background: #3498db; color: white; }
        
        .role-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .role-admin { background: #e74c3c; color: white; }
        .role-registrar { background: #3498db; color: white; }
        .role-cashier { background: #f39c12; color: white; }
        
        .view-link { color: #3498db; text-decoration: none; }
        .view-link:hover { text-decoration: underline; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        .net-paid-positive { color: #27ae60; font-weight: bold; }
        .net-paid-zero { color: #e74c3c; font-weight: bold; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .stats { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="images/logo.png" alt="Logo">
        <h2>Admin Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    </div>
    <div><span class="view-only-badge">🔒 VIEW ONLY MODE</span> <a href="logout.php" class="logout-btn">Logout</a></div>
</div>

<div class="container">
    <!-- Statistics Cards -->
    <div class="stats">
        <div class="stat-card"><div class="stat-number"><?php echo $total; ?></div><div class="stat-label">Total Enrollees</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#27ae60;"><?php echo $qualified; ?></div><div class="stat-label">Qualified</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#f39c12;"><?php echo $pending; ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#e74c3c;"><?php echo $not_qualified; ?></div><div class="stat-label">Not Qualified</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#27ae60;"><?php echo $fully_paid; ?></div><div class="stat-label">Fully Paid</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#f39c12;"><?php echo $partial; ?></div><div class="stat-label">Partial</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#e74c3c;"><?php echo $unpaid; ?></div><div class="stat-label">Unpaid</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#27ae60;"><?php echo $enrolled; ?></div><div class="stat-label">Enrolled</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#e74c3c;"><?php echo $dropped; ?></div><div class="stat-label">Dropped</div></div>
    </div>
    
    <!-- Enrollees Table -->
    <h3 class="section-title">All Student Records </h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th>Enrollment Status</th>
                    <th>Qualification</th>
                    <th>Payment Status</th>
                    <th>Total Paid</th>
                    <th>Refunded</th>
                    <th>Net Paid</th>
                    <th>Enrolled Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($enrollees as $e): 
                    $net_paid = $e['total_paid'] - $e['total_refunded'];
                    $net_paid_class = $net_paid <= 0 ? 'net-paid-zero' : ($net_paid >= $e['payment_amount'] ? 'net-paid-positive' : '');
                ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td>
                        <span class="badge <?php echo strtolower($e['enrollment_status']); ?>">
                            <?php echo $e['enrollment_status']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo strtolower(str_replace(' ', '', $e['qualification_status'])); ?>">
                            <?php echo $e['qualification_status']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo strtolower(str_replace(' ', '-', $e['payment_status'])); ?>">
                            <?php echo $e['payment_status']; ?>
                        </span>
                    </td>
                    <td>₱<?php echo number_format($e['total_paid'], 2); ?></td>
                    <td>₱<?php echo number_format($e['total_refunded'], 2); ?></td>
                    <td class="<?php echo $net_paid_class; ?>">₱<?php echo number_format($net_paid, 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                    <td><a href="view_student.php?id=<?php echo $e['enrollee_id']; ?>" class="view-link">View Details</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Recent Payment Transactions -->
    <h3 class="section-title">Recent Payment Transactions</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Student ID</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Refund Amount</th>
                    <th>Processed By</th>
                    <th>Role</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($transactions) > 0): ?>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td><?php echo $t['receipt_number'] ?? 'N/A'; ?></td>
                        <td><?php echo $t['enrollee_id']; ?></td>
                        <td>₱<?php echo number_format(abs($t['payment_amount']), 2); ?></td>
                        <td>
                            <span class="badge" style="background: <?php echo $t['payment_type'] == 'Payment' ? '#27ae60' : '#e74c3c'; ?>; color:white;">
                                <?php echo $t['payment_type']; ?>
                            </span>
                        </td>
                        <td><?php echo $t['refund_amount'] ? '₱'.number_format($t['refund_amount'],2) : '-'; ?></td>
                        <td><?php echo $t['cashier_name'] ?? 'System'; ?></td>
                        <td><span class="role-badge role-<?php echo $t['username'] ?? 'system'; ?>"><?php echo ucfirst($t['role'] ?? 'System'); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($t['payment_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No transactions found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Audit Log -->
    <h3 class="section-title">Activity Audit Log</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Changes</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($audit_logs) > 0): ?>
                    <?php foreach($audit_logs as $log): ?>
                    <tr>
                        <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                        <td><?php echo $log['full_name'] ?? 'System'; ?></td>
                        <td><span class="role-badge role-<?php echo $log['username'] ?? 'system'; ?>"><?php echo ucfirst($log['role'] ?? 'System'); ?></span></td>
                        <td><?php echo $log['action']; ?> on <?php echo $log['table_name']; ?> #<?php echo $log['record_id']; ?></td>
                        <td><?php echo htmlspecialchars($log['old_data'] ?? '-'); ?> → <?php echo htmlspecialchars($log['new_data'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No audit logs found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="footer">
    <p>© Daily Bread Learning Center Inc.</p>
</div>
</body>
</html>