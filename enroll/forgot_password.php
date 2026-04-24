<?php
require_once 'db_connection.php';
require_once 'includes_functions.php';

$error = '';
$success = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'request'; // request, reset

// Step 1: Request password reset
if(isset($_POST['request_reset'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE username = ? AND email = ? AND is_active = 1");
    $stmt->execute([$username, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
        $stmt->execute([$token, $expires, $user['user_id']]);
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/forgot_password.php?step=reset&token=" . $token;
        
        $success = "Password reset link generated! <br><br>";
        $success .= "<strong>Demo Link (In production, this would be emailed):</strong><br>";
        $success .= "<a href='$reset_link'>$reset_link</a><br><br>";
        $success .= "This link will expire in 1 hour.";
    } else {
        $error = "No account found with that username and email combination.";
    }
}

// Step 2: Reset password with token
if(isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif(strlen($new_password) < 4) {
        $error = "Password must be at least 4 characters!";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user) {
            $password_hash = hash('sha256', $new_password);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
            $stmt->execute([$password_hash, $user['user_id']]);
            
            $success = "Password has been reset successfully! <a href='login.php'>Click here to login</a>";
        } else {
            $error = "Invalid or expired reset link. Please request a new password reset.";
        }
    }
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        
        .container { max-width: 450px; width: 100%; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; position: relative; }
        .header img { height: 50px; margin-bottom: 10px; }
        .header h2 { font-size: 22px; }
        
    
        
        .content { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        
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
        
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        input:focus { outline: none; border-color: #27ae60; }
        
        .btn { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #219a52; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #27ae60; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        
        .info { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 12px; color: #666; text-align: center; }
        
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
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="images/logo.png" alt="Logo">
        <h2>Forgot Password</h2>
    </div>
    
    <div class="content">
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($step == 'reset' && !$success): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrapper" id="pw1">
                        <input type="password" name="new_password" id="new_password" required placeholder="Enter new password">
                        <button type="button" class="toggle-eye" onclick="togglePassword('new_password', this)">
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper" id="pw2">
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password">
                        <button type="button" class="toggle-eye" onclick="togglePassword('confirm_password', this)">
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" name="reset_password" class="btn">Reset Password</button>
            </form>
            
        <?php elseif(!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your registered email">
                </div>
                <button type="submit" name="request_reset" class="btn">Send Reset Link</button>
            </form>
        <?php endif; ?>
        
        <div class="footer-links">
            <a href="welcome.php"> Back to Home</a>
            <a href="login.php">← Back to Login</a>
        </div>
        
        <?php if(!$success && $step != 'reset'): ?>
            <div class="info">
                <strong>Note:</strong> Enter your username and registered email address. You will receive a password reset link via email.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Eye open SVG
    const eyeOpenSVG = '<svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    
    // Eye closed SVG (with slash)
    const eyeClosedSVG = '<svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle><line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2"></line></svg>';

    function togglePassword(fieldId, buttonElement) {
        var field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
            buttonElement.innerHTML = eyeClosedSVG;
        } else {
            field.type = "password";
            buttonElement.innerHTML = eyeOpenSVG;
        }
    }
</script>
</body>
</html>