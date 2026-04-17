<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

// Fetch all enrollees with their statuses (shows real-time updates)
$stmt = $pdo->query("
    SELECT e.*, 
           COUNT(pt.transaction_id) as payment_count,
           SUM(pt.payment_amount) as total_paid
    FROM enrollees e
    LEFT JOIN payment_transactions pt ON e.enrollee_id = pt.enrollee_id
    GROUP BY e.enrollee_id
    ORDER BY e.created_at DESC
");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent audit logs
$stmt = $pdo->query("
    SELECT al.*, u.full_name, u.role
    FROM audit_log al
    JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 20
");
$audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($enrollees);
$qualified = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE qualification_status = 'Qualified'")->fetchColumn();
$fully_paid = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE payment_status = 'Fully Paid'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Daily Bread Learning Center</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .container { padding: 20px; max-width: 1400px; margin: auto; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; flex: 1; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; }
        .stat-card.qualified .stat-number { color: #27ae60; }
        .stat-card.paid .stat-number { color: #27ae60; }
        .section-title { margin: 20px 0 15px; color: #2c3e50; }
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
        .audit-table { margin-top: 30px; font-size: 13px; }
        .audit-table th { background: #7f8c8d; }
        @media (max-width: 768px) { th, td { font-size: 12px; padding: 8px; } }
    </style>
</head>
<body>
<div class="header">
    <h2>Admin Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-label">Total Enrollees</div>
        </div>
        <div class="stat-card qualified">
            <div class="stat-number"><?php echo $qualified; ?></div>
            <div class="stat-label">Qualified Students</div>
        </div>
        <div class="stat-card paid">
            <div class="stat-number"><?php echo $fully_paid; ?></div>
            <div class="stat-label">Fully Paid</div>
        </div>
    </div>
    
    <h3 class="section-title">All Student Records (Real-time Updates)</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Program</th><th>Payment Plan</th><th>Qualification</th><th>Payment Status</th><th>Total Paid</th><th>Enrolled Date</th></tr>
            </thead>
            <tbody>
                <?php foreach($enrollees as $e): ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td><?php echo $e['payment_plan']; ?> (₱<?php echo number_format($e['payment_amount'], 2); ?>)</td>
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
                    <td>₱<?php echo number_format($e['total_paid'] ?? 0, 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Audit Log - Shows who made changes -->
    <h3 class="section-title">Recent Activity Log</h3>
    <div style="overflow-x: auto;">
        <table class="audit-table">
            <thead>
                <tr><th>Date</th><th>User</th><th>Role</th><th>Action</th><th>Changes</th></tr>
            </thead>
            <tbody>
                <?php foreach($audit_logs as $log): ?>
                <tr>
                    <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                    <td><?php echo $log['full_name']; ?></td>
                    <td><?php echo ucfirst($log['role']); ?></td>
                    <td><?php echo $log['action']; ?> on <?php echo $log['table_name']; ?></td>
                    <td><?php echo $log['old_data']; ?> → <?php echo $log['new_data']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>