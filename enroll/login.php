<?php
session_start();
require_once 'db_connection.php';

$error = '';
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';

if(isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    
    // Hash the password using SHA256
    $hashed_password = hash('sha256', $password);
    
    // First, check if user exists in users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user doesn't exist, try to insert default account
    if(!$user) {
        if($role == 'registrar') {
            $default_hash = hash('sha256', 'Registrar123');
            $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $insert->execute(['registrar', $default_hash, 'Registrar Office', 'registrar@dailybread.edu.ph', 'registrar']);
            $user = ['username' => 'registrar', 'password_hash' => $default_hash];
        } elseif($role == 'cashier') {
            $default_hash = hash('sha256', 'Cashier123');
            $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $insert->execute(['cashier', $default_hash, 'Cashier Office', 'cashier@dailybread.edu.ph', 'cashier']);
            $user = ['username' => 'cashier', 'password_hash' => $default_hash];
        } elseif($role == 'admin') {
            $default_hash = hash('sha256', 'Admin123');
            $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $insert->execute(['admin', $default_hash, 'System Administrator', 'admin@dailybread.edu.ph', 'admin']);
            $user = ['username' => 'admin', 'password_hash' => $default_hash];
        }
    }
    
    // Verify password
    if($user && $hashed_password == $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'] ?? null;
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'] ?? ucfirst($user['username']) . ' Office';
        $_SESSION['role'] = $role;
        
        // Update last login
        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE username = ?");
        $update->execute([$username]);
        
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
            default:
                header("Location: welcome.php");
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
    <link rel="icon" type="image/png" href="images/logo.png">
<link rel="shortcut icon" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-container { background: white; border-radius: 15px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .logo { text-align: center; font-size: 48px; margin-bottom: 20px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 10px; font-size: 24px; }
        .role-badge { text-align: center; padding: 10px; border-radius: 25px; margin-bottom: 25px; font-weight: bold; font-size: 14px; letter-spacing: 1px; }
        .role-badge.admin { background: #e74c3c; color: white; }
        .role-badge.registrar { background: #3498db; color: white; }
        .role-badge.cashier { background: #f39c12; color: white; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px; }
        input { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        input:focus { outline: none; border-color: #27ae60; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
        button:hover { background: #219a52; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #27ae60; text-decoration: none; font-size: 14px; }
        .back-link a:hover { text-decoration: underline; }
        .info { margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 12px; color: #666; text-align: center; border: 1px solid #eee; }
        .info strong { color: #2c3e50; }
        .info p { margin: 5px 0; }
        hr { margin: 15px 0; border: none; border-top: 1px solid #eee; }
        .logo { text-align: center;}
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo">
    <img src="images/logo.png" alt="Logo" style="height: 60px; margin-bottom: 10px;">
</div>
     <h2>Staff Login Portal</h2>
     
    <div class="role-badge <?php echo $selected_role; ?>">
        <?php 
        if($selected_role == 'admin') echo "ADMIN PORTAL";
        elseif($selected_role == 'registrar') echo "REGISTRAR PORTAL";
        elseif($selected_role == 'cashier') echo "CASHIER PORTAL";
        else echo "SELECT A PORTAL";
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
    
    <hr>
    
    <div class="info">
        <strong>Demo Credentials:</strong><br>
        👑 Admin: admin / Admin123<br>
        📋 Registrar: registrar / Registrar123<br>
        💰 Cashier: cashier / Cashier123
    </div>
</div>
</body>
</html>