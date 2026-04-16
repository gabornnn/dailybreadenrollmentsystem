<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

// Fetch all enrollees with their statuses
$stmt = $pdo->query("SELECT * FROM enrollees ORDER BY created_at DESC");
$enrollees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($enrollees);
$qualified = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE qualification_status = 'Qualified'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE qualification_status = 'Pending'")->fetchColumn();
$fully_paid = $pdo->query("SELECT COUNT(*) FROM enrollees WHERE payment_status = 'Fully Paid'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - View Only</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .container { padding: 20px; max-width: 1300px; margin: auto; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; flex: 1; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; display: block; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; position: sticky; top: 0; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .qualified { background: #27ae60; color: white; }
        .pending { background: #f39c12; color: white; }
        .not-qualified { background: #e74c3c; color: white; }
        .paid { background: #27ae60; color: white; }
        .partial { background: #f39c12; color: white; }
        .unpaid { background: #e74c3c; color: white; }
        .view-only-badge { background: #e74c3c; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        @media (max-width: 768px) { th, td { font-size: 12px; padding: 8px; } }
    </style>
</head>
<body>
<div class="header">
    <h2>👑 Admin Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    <div><span class="view-only-badge">🔒 VIEW ONLY MODE</span> <a href="logout.php" class="logout-btn">Logout</a></div>
</div>

<div class="container">
    <div class="stats">
        <div class="stat-card"><div class="stat-number"><?php echo $total; ?></div><div class="stat-label">Total Enrollees</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $qualified; ?></div><div class="stat-label">Qualified</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $pending; ?></div><div class="stat-label">Pending Review</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $fully_paid; ?></div><div class="stat-label">Fully Paid</div></div>
    </div>
    
    <h3>📋 All Student Records (Read Only)</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Program</th><th>Payment Plan</th><th>Qualification</th><th>Payment Status</th><th>Enrolled Date</th></tr>
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
                        <span class="badge <?php echo strtolower(str_replace(' ', '', $e['payment_status'])); ?>">
                            <?php echo $e['payment_status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($e['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>