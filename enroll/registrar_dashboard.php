<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';

// Handle qualification update
if(isset($_POST['update_qualification'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $status = $_POST['qualification_status'];
    
    $stmt = $pdo->prepare("UPDATE enrollees SET qualification_status = ? WHERE enrollee_id = ?");
    $stmt->execute([$status, $enrollee_id]);
    $success = "Qualification status updated!";
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
    <title>Registrar Dashboard - Daily Bread</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        .header { background: #3498db; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .container { padding: 20px; max-width: 1300px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2980b9; color: white; }
        select, button { padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; cursor: pointer; }
        button { background: #3498db; color: white; border: none; }
        button:hover { background: #2980b9; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .qualified { background: #27ae60; color: white; }
        .pending { background: #f39c12; color: white; }
        .not-qualified { background: #e74c3c; color: white; }
    </style>
</head>
<body>
<div class="header">
    <h2>📋 Registrar Dashboard - <?php echo $_SESSION['full_name']; ?></h2>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    
    <h3>📝 Update Student Qualification Status</h3>
    <p style="margin-bottom: 15px; color: #666;">Review student documents and update their enrollment qualification.</p>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr><th>ID</th><th>Student Name</th><th>Program</th><th>Current Status</th><th>Update Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($enrollees as $e): ?>
                <tr>
                    <td><?php echo $e['enrollee_id']; ?></td>
                    <td><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></td>
                    <td><?php echo $e['program_level']; ?></td>
                    <td>
                        <span class="badge <?php echo strtolower(str_replace(' ', '', $e['qualification_status'])); ?>">
                            <?php echo $e['qualification_status']; ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="enrollee_id" value="<?php echo $e['enrollee_id']; ?>">
                            <select name="qualification_status" required>
                                <option value="Pending" <?php echo $e['qualification_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Qualified" <?php echo $e['qualification_status'] == 'Qualified' ? 'selected' : ''; ?>>Qualified</option>
                                <option value="Not Qualified" <?php echo $e['qualification_status'] == 'Not Qualified' ? 'selected' : ''; ?>>Not Qualified</option>
                            </select>
                            <button type="submit" name="update_qualification">Update</button>
                        </form>
                    </td>
                    <td>
                        <a href="view_student_details.php?id=<?php echo $e['enrollee_id']; ?>" style="color: #3498db;">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>