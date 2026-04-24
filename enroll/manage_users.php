<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

$success = '';
$error = '';

// Handle add new user
if(isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    if(empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $error = "All fields are required!";
    } elseif(strlen($password) < 4) {
        $error = "Password must be at least 4 characters!";
    } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check->execute([$username]);
        $exists = $check->fetchColumn();
        
        if($exists) {
            $error = "Username already exists!";
        } else {
            $password_hash = hash('sha256', $password);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$username, $password_hash, $full_name, $email, $role]);
            $success = "User '$username' added successfully! Password: $password";
        }
    }
}

// Handle reset password
if(isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    $password_hash = hash('sha256', $new_password);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$password_hash, $user_id]);
    $success = "Password reset successfully! New password: $new_password";
}

// Handle edit user
if(isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE user_id = ?");
    $stmt->execute([$full_name, $email, $role, $user_id]);
    $success = "User updated successfully!";
}

// Handle toggle user status
if(isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status == 1 ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->execute([$new_status, $user_id]);
    $success = "User status updated!";
}

// Handle delete user
if(isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    
    if($username == $_SESSION['username']) {
        $error = "You cannot delete your own account!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "User '$username' deleted successfully!";
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY user_id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        
        .header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-left img { height: 40px; }
        .back-btn, .logout-btn { padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .back-btn { background: #3498db; color: white; }
        .logout-btn { background: #e74c3c; color: white; }
        
        .container { padding: 20px; max-width: 1200px; margin: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        
        .form-card { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-card h3 { color: #2c3e50; margin-bottom: 20px; border-left: 4px solid #27ae60; padding-left: 15px; }
        
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .btn-primary { background: #27ae60; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        
        .user-table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .user-table th, .user-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .user-table th { background: #34495e; color: white; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-active { background: #27ae60; color: white; }
        .badge-inactive { background: #e74c3c; color: white; }
        .badge-admin { background: #e74c3c; color: white; }
        .badge-registrar { background: #3498db; color: white; }
        .badge-cashier { background: #f39c12; color: white; }
        
        .btn-sm { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin: 2px; }
        .btn-reset { background: #e67e22; color: white; }
        .btn-view { background: #1abc9c; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-toggle { background: #3498db; color: white; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 50px auto; padding: 0; width: 90%; max-width: 500px; border-radius: 10px; }
        .modal-header { background: #e67e22; color: white; padding: 15px 20px; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; }
        .modal-body { padding: 20px; }
        .close { color: white; font-size: 28px; cursor: pointer; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .user-table { font-size: 12px; }
            .user-table th, .user-table td { padding: 8px; }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        <img src="images/logo.png" alt="Logo">
        <h2>User Management - Admin</h2>
    </div>
    <div>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="container">
    <?php if($success): ?>
        <div class="success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="error">❌ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Add New User Form -->
    <div class="form-card">
        <h3>➕ Add New Staff User</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required placeholder="e.g., juandelacruz">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="text" name="password" required placeholder="Enter password">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g., Juan Dela Cruz">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required placeholder="juan@dailybread.edu.ph">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="admin">Admin</option>
                        <option value="registrar">Registrar</option>
                        <option value="cashier">Cashier</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_user" class="btn-primary">➕ Add User</button>
        </form>
    </div>
    
    <!-- Users List -->
    <h3 style="margin-bottom: 15px;">📋 Existing Users</h3>
    <div style="overflow-x: auto;">
        <table class="user-table">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?php echo $u['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                    <td><span class="badge <?php echo $u['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td><?php echo $u['last_login'] ? date('M d, Y', strtotime($u['last_login'])) : 'Never'; ?></td>
                    <td>
                        <button class="btn-sm btn-reset" onclick="openResetModal(<?php echo $u['user_id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">Reset</button>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                            <button type="submit" name="toggle_status" class="btn-sm btn-toggle"><?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                        </form>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this user?');">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <input type="hidden" name="username" value="<?php echo $u['username']; ?>">
                            <button type="submit" name="delete_user" class="btn-sm btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset Password</h3>
            <span class="close" onclick="closeResetModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="form-group">
                    <label>Username: <span id="resetUsername"></span></label>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="text" name="new_password" id="newPassword" required class="form-control" style="width:100%; padding:10px;">
                </div>
                <button type="submit" name="reset_password" class="btn-primary" style="margin-top: 10px; width:100%;">Reset Password</button>
            </form>
        </div>
    </div>
</div>

<script>
function openResetModal(id, username) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUsername').innerText = username;
    document.getElementById('resetModal').style.display = 'block';
}
function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
}
window.onclick = function(event) {
    var modal = document.getElementById('resetModal');
    if (event.target == modal) modal.style.display = 'none';
}
</script>

<div class="footer">
    <p>© Daily Bread Learning Center Inc. — User Management | Admin can reset passwords for staff</p>
</div>
</body>
</html>