<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';
require_once 'includes_functions.php';

$success = '';
$error = '';

// Handle settings update
if(isset($_POST['save_settings'])) {
    $settings = [
        'school_name', 'school_year', 'school_address', 'school_phone', 'school_email',
        'gcash_number', 'gcash_name', 'bank_name', 'bank_account', 'bank_account_name',
        'enrollment_fee_nursery', 'enrollment_fee_k1', 'enrollment_fee_k2',
        'backup_auto_schedule', 'maintenance_mode'
    ];
    
    foreach($settings as $setting) {
        if(isset($_POST[$setting])) {
            updateSetting($pdo, $setting, $_POST[$setting]);
        }
    }
    
    $success = "Settings saved successfully!";
}

// Handle backup
if(isset($_POST['create_backup'])) {
    $backup_file = createBackup($pdo);
    if($backup_file) {
        $success = "Backup created successfully! File: " . basename($backup_file);
    } else {
        $error = "Backup failed!";
    }
}

// Fetch all settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .back-btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .content { background: white; padding: 25px; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .section h3 { color: #2c3e50; margin-bottom: 15px; border-left: 4px solid #27ae60; padding-left: 10px; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .btn-save { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-backup { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>System Settings</h2>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- School Information -->
            <div class="section">
                <h3>🏫 School Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>School Name</label>
                        <input type="text" name="school_name" value="<?php echo $settings['school_name'] ?? 'Daily Bread Learning Center Inc.'; ?>">
                    </div>
                    <div class="form-group">
                        <label>School Year</label>
                        <input type="text" name="school_year" value="<?php echo $settings['school_year'] ?? '2026-2027'; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>School Address</label>
                    <input type="text" name="school_address" value="<?php echo $settings['school_address'] ?? ''; ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="school_phone" value="<?php echo $settings['school_phone'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="school_email" value="<?php echo $settings['school_email'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Payment Settings -->
            <div class="section">
                <h3>💰 Payment Settings</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>GCash Number</label>
                        <input type="text" name="gcash_number" value="<?php echo $settings['gcash_number'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>GCash Account Name</label>
                        <input type="text" name="gcash_name" value="<?php echo $settings['gcash_name'] ?? ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" value="<?php echo $settings['bank_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank Account Number</label>
                        <input type="text" name="bank_account" value="<?php echo $settings['bank_account'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bank Account Name</label>
                        <input type="text" name="bank_account_name" value="<?php echo $settings['bank_account_name'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Tuition Fees -->
            <div class="section">
                <h3>📚 Tuition Fees</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nursery Fee</label>
                        <input type="number" name="enrollment_fee_nursery" value="<?php echo $settings['enrollment_fee_nursery'] ?? '17500'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Kindergarten 1 Fee</label>
                        <input type="number" name="enrollment_fee_k1" value="<?php echo $settings['enrollment_fee_k1'] ?? '18300'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Kindergarten 2 Fee</label>
                        <input type="number" name="enrollment_fee_k2" value="<?php echo $settings['enrollment_fee_k2'] ?? '18300'; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Backup Settings -->
            <div class="section">
                <h3>💾 Backup & Maintenance</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Auto Backup Schedule</label>
                        <select name="backup_auto_schedule">
                            <option value="daily" <?php echo ($settings['backup_auto_schedule'] ?? '') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo ($settings['backup_auto_schedule'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo ($settings['backup_auto_schedule'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Maintenance Mode</label>
                        <select name="maintenance_mode">
                            <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_backup" class="btn-backup" style="margin-top: 15px;">📥 Create Manual Backup</button>
            </div>
            
            <button type="submit" name="save_settings" class="btn-save">💾 Save All Settings</button>
        </form>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — System Administration</p>
    </div>
</div>
</body>
</html>