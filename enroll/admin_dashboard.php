<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$program_filter = isset($_GET['program_filter']) ? $_GET['program_filter'] : '';
$payment_filter = isset($_GET['payment_filter']) ? $_GET['payment_filter'] : '';
$qualification_filter = isset($_GET['qualification_filter']) ? $_GET['qualification_filter'] : '';

// Build SQL query with filters - Show ALL students (including pending)
$sql = "SELECT e.*, 
        COALESCE(SUM(CASE WHEN pt.payment_type = 'Payment' THEN pt.payment_amount ELSE 0 END), 0) as total_paid,
        COALESCE(SUM(CASE WHEN pt.payment_type = 'Refund' THEN pt.refund_amount ELSE 0 END), 0) as total_refunded
        FROM enrollees e
        LEFT JOIN payment_transactions pt ON e.enrollee_id = pt.enrollee_id
        WHERE 1=1";

if(!empty($search)) {
    $sql .= " AND (e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.enrollee_id LIKE '%$search%' OR e.email LIKE '%$search%')";
}
if(!empty($program_filter)) {
    $sql .= " AND e.program_level = '$program_filter'";
}
if(!empty($payment_filter)) {
    $sql .= " AND e.payment_status = '$payment_filter'";
}
if(!empty($qualification_filter)) {
    $sql .= " AND e.qualification_status = '$qualification_filter'";
}

$sql .= " GROUP BY e.enrollee_id ORDER BY e.enrollee_id DESC";

$stmt = $pdo->query($sql);
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch audit log
$stmt = $pdo->query("
    SELECT al.*, u.full_name, u.role, u.username
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 50
");
$audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payment transactions
$stmt = $pdo->query("
    SELECT pt.*, u.full_name as cashier_name, u.username
    FROM payment_transactions pt
    LEFT JOIN users u ON pt.processed_by_user_id = u.user_id
    ORDER BY pt.created_at DESC
    LIMIT 30
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$total = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE is_archived = 0 OR is_archived IS NULL")->fetchColumn();
$pending_applications = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE enrollment_status = 'Pending'")->fetchColumn();
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
        
        .search-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .filter-form { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 12px; }
        .filter-group input, .filter-group select { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-search { background: #3498db; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; }
        .btn-reset { background: #95a5a6; color: white; padding: 8px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        
        .section-title { margin: 25px 0 15px; color: #2c3e50; border-left: 4px solid #e74c3c; padding-left: 15px; }
        
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .qualified { background: #27ae60; color: white; }
        .pending { background: #f39c12; color: white; }
        .not-qualified { background: #e74c3c; color: white; }
        .fully-paid { background: #27ae60; color: white; }
        .partial { background: #f39c12; color: white; }
        .unpaid { background: #e74c3c; color: white; }
        .enrolled { background: #27ae60; color: white; }
        .pending-enrollment { background: #f39c12; color: white; }
        .dropped { background: #e74c3c; color: white; }
        
        .role-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .role-admin { background: #e74c3c; color: white; }
        .role-registrar { background: #3498db; color: white; }
        .role-cashier { background: #f39c12; color: white; }
        
        .view-link { color: #3498db; text-decoration: none; }
        .view-link:hover { text-decoration: underline; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        .net-paid-positive { color: #27ae60; font-weight: bold; }
        .net-paid-zero { color: #e74c3c; font-weight: bold; }
        
        .action-badge { background: #34495e; color: white; padding: 4px 10px; border-radius: 15px; font-size: 11px; display: inline-block; }
        .change-box { background: #f8f9fa; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 12px; }
        .from-text { color: #e74c3c; font-weight: bold; }
        .to-text { color: #27ae60; font-weight: bold; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .stats { flex-direction: column; }
            .filter-form { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="images/logo.png" alt="Logo">
        <h2>Admin Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    </div>
    <div>
        <a href="system_settings.php" style="background: #9b59b6; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;">⚙️ Settings</a>
        <a href="backup_restore.php" style="background: #e67e22; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;">💾 Backup</a>
        <a href="manage_users.php" style="background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block;">👥 Manage Users</a>
        <span class="view-only-badge">VIEW ONLY MODE</span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <div class="stats">
        <div class="stat-card"><div class="stat-number"><?php echo $total; ?></div><div class="stat-label">Total Enrollees</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#f39c12;"><?php echo $pending_applications; ?></div><div class="stat-label">Pending Applications</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#27ae60;"><?php echo $qualified; ?></div><div class="stat-label">Qualified</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#27ae60;"><?php echo $enrolled; ?></div><div class="stat-label">Enrolled</div></div>
        <div class="stat-card"><div class="stat-number" style="color:#27ae60;"><?php echo $fully_paid; ?></div><div class="stat-label">Fully Paid</div></div>
    </div>
    
    <div class="search-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>🔍 Search Student</label>
                <input type="text" name="search" placeholder="Name, ID, or Email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>📚 Program Level</label>
                <select name="program_filter">
                    <option value="">All Programs</option>
                    <option value="NURSERY" <?php echo $program_filter == 'NURSERY' ? 'selected' : ''; ?>>NURSERY</option>
                    <option value="KINDERGARTEN 1" <?php echo $program_filter == 'KINDERGARTEN 1' ? 'selected' : ''; ?>>KINDERGARTEN 1</option>
                    <option value="KINDERGARTEN 2" <?php echo $program_filter == 'KINDERGARTEN 2' ? 'selected' : ''; ?>>KINDERGARTEN 2</option>
                </select>
            </div>
            <div class="filter-group">
                <label>💰 Payment Status</label>
                <select name="payment_filter">
                    <option value="">All Status</option>
                    <option value="Unpaid" <?php echo $payment_filter == 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Partial" <?php echo $payment_filter == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Fully Paid" <?php echo $payment_filter == 'Fully Paid' ? 'selected' : ''; ?>>Fully Paid</option>
                </select>
            </div>
            <div class="filter-group">
                <label>✅ Qualification</label>
                <select name="qualification_filter">
                    <option value="">All</option>
                    <option value="Pending" <?php echo $qualification_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Qualified" <?php echo $qualification_filter == 'Qualified' ? 'selected' : ''; ?>>Qualified</option>
                    <option value="Not Qualified" <?php echo $qualification_filter == 'Not Qualified' ? 'selected' : ''; ?>>Not Qualified</option>
                </select>
            </div>
            <div class="filter-group" style="flex: 0.5;">
                <label>&nbsp;</label>
                <button type="submit" class="btn-search">🔍 Search</button>
                <a href="admin_dashboard.php" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>
    
    <h3 class="section-title">All Student Records</h3>
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
                    $status_class = $e['enrollment_status'] == 'Enrolled' ? 'enrolled' : 'pending-enrollment';
                ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $e['enrollment_status']; ?></span></td>
                    <td><span class="badge <?php echo strtolower(str_replace(' ', '', $e['qualification_status'])); ?>"><?php echo $e['qualification_status']; ?></span></td>
                    <td><span class="badge <?php echo strtolower(str_replace(' ', '-', $e['payment_status'])); ?>"><?php echo $e['payment_status']; ?></span></td>
                    <td>₱<?php echo number_format($e['total_paid'], 2); ?></td>
                    <td>₱<?php echo number_format($e['total_refunded'], 2); ?></td>
                    <td class="<?php echo $net_paid_class; ?>">₱<?php echo number_format($net_paid, 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                    <td><a href="view_student.php?id=<?php echo $e['enrollee_id']; ?>" class="view-link">View Details</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($enrollees) == 0): ?>
                    <tr><td colspan="11" style="text-align: center;">No students found</td</span>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <h3 class="section-title">Recent Payment Transactions</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Receipt</th>
                    <th>Student ID</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Refund</th>
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
                        <td><span class="badge" style="background: <?php echo $t['payment_type'] == 'Payment' ? '#27ae60' : '#e74c3c'; ?>; color:white;"><?php echo $t['payment_type']; ?></span></td>
                        <td><?php echo $t['refund_amount'] ? '₱'.number_format($t['refund_amount'],2) : '-'; ?></td>
                        <td><?php echo $t['cashier_name'] ?? 'System'; ?></td>
                        <td><span class="role-badge role-<?php echo $t['username'] ?? 'system'; ?>"><?php echo ucfirst($t['role'] ?? 'System'); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($t['payment_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center;">No transactions found</td</span>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
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
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; white-space: nowrap;"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                        <td style="padding: 12px;"><?php echo $log['full_name'] ?? 'System'; ?></td>
                        <td style="padding: 12px;">
                            <span class="role-badge role-<?php echo $log['username'] ?? 'system'; ?>">
                                <?php echo ucfirst($log['role'] ?? 'System'); ?>
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <span class="action-badge"><?php echo $log['action']; ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <div class="change-box">
                                <div><span class="from-text">FROM:</span> <?php echo htmlspecialchars($log['old_data'] ?? '-'); ?></div>
                                <div style="margin-top: 5px;"><span class="to-text">TO:</span> <?php echo htmlspecialchars($log['new_data'] ?? '-'); ?></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">No audit logs found</td</span>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="footer">
    <p>Daily Bread Learning Center Inc. — Admin Dashboard | View Only Mode | All changes are tracked in Audit Log</p>
</div>
</body>
</html>