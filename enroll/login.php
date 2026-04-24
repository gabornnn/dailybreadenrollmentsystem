<?php
session_start();
require_once 'db_connection.php';

$error = '';
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';

if(isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    
    $hashed_password = hash('sha256', $password);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ? AND is_active = 1");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && $hashed_password == $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update->execute([$user['user_id']]);
        
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        
        .login-container { background: white; border-radius: 15px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; }
        
        
        
        .logo { text-align: center; margin-bottom: 20px; margin-top: 10px; }
        .logo img { height: 60px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 10px; font-size: 24px; }
        
        .role-badge { text-align: center; padding: 10px; border-radius: 25px; margin-bottom: 25px; font-weight: bold; font-size: 14px; letter-spacing: 1px; }
        .role-badge.admin { background: #e74c3c; color: white; }
        .role-badge.registrar { background: #3498db; color: white; }
        .role-badge.cashier { background: #f39c12; color: white; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px; }
        
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-wrapper input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .password-wrapper input:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .toggle-eye {
            position: absolute;
            right: 12px;
            cursor: pointer;
            font-size: 20px;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            transition: color 0.2s;
        }
        
        .toggle-eye:hover {
            color: #27ae60;
        }
        
        .eye-icon {
            width: 20px;
            height: 20px;
            display: block;
        }
        
        input[type="text"] { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #27ae60; }
        
        button[type="submit"] { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
        button[type="submit"]:hover { background: #219a52; }
        
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #27ae60; text-decoration: none; font-size: 13px; }
        .links a:hover { text-decoration: underline; }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            font-size: 13px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .info { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 12px; color: #666; text-align: center; border: 1px solid #eee; }
        hr { margin: 15px 0; border: none; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="login-container">
    
    <div class="logo">
        <img src="images/logo.png" alt="Logo">
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
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required placeholder="Enter password">
                <button type="button" class="toggle-eye" id="togglePassword">
                    <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>
        <button type="submit" name="login">Login</button>
    </form>
    
    <div class="links">
        <a href="forgot_password.php">Forgot Password?</a>
    </div>
    
    <div class="footer-links">
        <a href="welcome.php"> Back to Home</a>
    </div>
    
    <div class="info">
        <strong>Demo Credentials:</strong><br>
        👑 Admin: admin / Admin123<br>
        📋 Registrar: registrar / Registrar123<br>
        💰 Cashier: cashier / Cashier123
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    // Eye open SVG
    const eyeOpenSVG = '<svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    
    // Eye closed SVG (with slash)
    const eyeClosedSVG = '<svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle><line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2"></line></svg>';

    togglePassword.addEventListener('click', function() {
        // Toggle the type attribute
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        // Toggle the eye icon
        if (type === 'password') {
            togglePassword.innerHTML = eyeOpenSVG;
        } else {
            togglePassword.innerHTML = eyeClosedSVG;
        }
    });
</script>
</body>
</html>