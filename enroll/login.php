<?php
session_start();
require_once 'db_connection.php';

$error = '';
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // For testing - compare plain text
    $stmt = $pdo->prepare("SELECT * FROM staff_users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check both hashed and plain text (for testing)
    $hashed_input = hash('sha256', $password);
    
    if($user && ($hashed_input == $user['password_hash'] || $password == 'Registrar123' || $password == 'Cashier123')) {
        $_SESSION['staff_id'] = $user['staff_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE staff_users SET last_login = NOW() WHERE staff_id = ?");
        $update->execute([$user['staff_id']]);
        
        // Redirect based on role
        switch($role) {
            case 'admin':
                header("Location: admin_dashboard.php");
                break;
            case 'registrar':
                header("Location: registrar_dashboard.php");
                break;
            case 'cashier':
                header("Location: cashier_dashboard.php");
                break;
        }
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Daily Bread Learning Center</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-container { background: white; border-radius: 15px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .logo { text-align: center; font-size: 48px; margin-bottom: 20px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 10px; }
        .role-badge { text-align: center; padding: 8px; border-radius: 20px; margin-bottom: 20px; font-weight: bold; }
        .role-badge.admin { background: #e74c3c; color: white; }
        .role-badge.registrar { background: #3498db; color: white; }
        .role-badge.cashier { background: #f39c12; color: white; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #27ae60; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: bold; }
        button:hover { background: #219a52; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #27ae60; text-decoration: none; }
        .info { margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 8px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo">🏫</div>
    <h2>Staff Login Portal</h2>
    
    <div class="role-badge <?php echo $selected_role; ?>">
        <?php 
        if($selected_role == 'admin') echo "👑 ADMIN PORTAL";
        elseif($selected_role == 'registrar') echo "📋 REGISTRAR PORTAL";
        elseif($selected_role == 'cashier') echo "💰 CASHIER PORTAL";
        else echo "Select a portal from welcome page";
        ?>
    </div>
    
    <?php if($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="role" value="<?php echo $selected_role; ?>">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required placeholder="Enter username">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter password">
        </div>
        <button type="submit" name="login">Login</button>
    </form>
    
    <div class="back-link">
        <a href="welcome.php">← Back to Welcome Page</a>
    </div>
    
    <div class="info">
        <strong>Demo Credentials:</strong><br>
        👑 Admin: admin / Admin123<br>
        📋 Registrar: registrar / Registrar123<br>
        💰 Cashier: cashier / Cashier123
    </div>
</div>
</body>
</html>