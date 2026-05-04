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
    $settings_keys = [
        'school_name', 'school_year', 'school_address', 'school_phone', 'school_email',
        'gcash_number', 'gcash_name', 'bank_name', 'bank_account', 'bank_account_name',
        'enrollment_fee_nursery', 'enrollment_fee_k1', 'enrollment_fee_k2',
        'backup_auto_schedule', 'maintenance_mode'
    ];
    
    foreach($settings_keys as $key) {
        if(isset($_POST[$key])) {
            updateSetting($pdo, $key, $_POST[$key]);
        }
    }
    
    $success = "Settings saved successfully!";
    header("Location: system_settings.php?success=1");
    exit();
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

// Handle tuition update
if(isset($_POST['update_tuition'])) {
    $program = $_POST['program'];
    $fee_type = $_POST['fee_type'];
    $cash = $_POST['cash'];
    $semi_annual = $_POST['semi_annual'];
    $quarterly = $_POST['quarterly'];
    $monthly = $_POST['monthly'];
    
    $stmt = $pdo->prepare("INSERT INTO tuition_settings (program, fee_type, cash, semi_annual, quarterly, monthly) 
                           VALUES (?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           cash = ?, semi_annual = ?, quarterly = ?, monthly = ?");
    $stmt->execute([$program, $fee_type, $cash, $semi_annual, $quarterly, $monthly, 
                    $cash, $semi_annual, $quarterly, $monthly]);
    $tuition_success = "Tuition settings updated!";
}

// Handle schedule update
if(isset($_POST['update_schedule'])) {
    $program = $_POST['program'];
    $payment_date = $_POST['payment_date'];
    $cash = $_POST['cash'];
    $semi_annual = $_POST['semi_annual'];
    $quarterly = $_POST['quarterly'];
    $monthly = $_POST['monthly'];
    $is_total = isset($_POST['is_total']) ? 1 : 0;
    
    // Check if entry exists
    $check = $pdo->prepare("SELECT id FROM payment_schedule WHERE program = ? AND payment_date = ?");
    $check->execute([$program, $payment_date]);
    $exists = $check->fetch();
    
    if($exists) {
        $stmt = $pdo->prepare("UPDATE payment_schedule SET cash = ?, semi_annual = ?, quarterly = ?, monthly = ?, is_total = ? WHERE program = ? AND payment_date = ?");
        $stmt->execute([$cash, $semi_annual, $quarterly, $monthly, $is_total, $program, $payment_date]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO payment_schedule (program, payment_date, cash, semi_annual, quarterly, monthly, is_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$program, $payment_date, $cash, $semi_annual, $quarterly, $monthly, $is_total]);
    }
    $schedule_success = "Payment schedule updated!";
}

// Handle delete schedule
if(isset($_POST['delete_schedule'])) {
    $id = $_POST['schedule_id'];
    $stmt = $pdo->prepare("DELETE FROM payment_schedule WHERE id = ?");
    $stmt->execute([$id]);
    $schedule_success = "Schedule entry deleted!";
}

// Get current tuition data
$tuition_data = [];
$stmt = $pdo->query("SELECT * FROM tuition_settings ORDER BY FIELD(program, 'NURSERY', 'KINDERGARTEN 1', 'KINDERGARTEN 2'), 
                     FIELD(fee_type, 'Registration', 'Tuition fee', 'Misc. fee')");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tuition_data[$row['program']][$row['fee_type']] = $row;
}

// Get payment schedules
$schedules = [];
$stmt = $pdo->query("SELECT * FROM payment_schedule ORDER BY FIELD(program, 'NURSERY', 'KINDERGARTEN 1', 'KINDERGARTEN 2'), id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $schedules[$row['program']][] = $row;
}

// Fetch all settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Set default values if not exists
$defaults = [
    'school_name' => 'Daily Bread Learning Center Inc.',
    'school_year' => '2026-2027',
    'school_address' => 'Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City',
    'school_phone' => '0923-4701532',
    'school_email' => 'info@dailybread.edu.ph',
    'gcash_number' => '0923-4701532',
    'gcash_name' => 'Daily Bread Learning Center',
    'bank_name' => 'Bank of the Philippine Islands (BPI)',
    'bank_account' => '1234-5678-90',
    'bank_account_name' => 'Daily Bread Learning Center Inc.',
    'enrollment_fee_nursery' => '17500',
    'enrollment_fee_k1' => '18300',
    'enrollment_fee_k2' => '18300',
    'backup_auto_schedule' => 'daily',
    'maintenance_mode' => '0'
];

foreach($defaults as $key => $default) {
    if(!isset($settings[$key])) {
        $settings[$key] = $default;
    }
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
        
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
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
        
        /* Tuition Management Tabs */
        .tuition-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .tuition-tabs .tab-btn { background: #f0f0f0; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-weight: bold; transition: all 0.3s; }
        .tuition-tabs .tab-btn.active { background: #27ae60; color: white; }
        .tuition-tabs .tab-btn:hover { background: #27ae60; color: white; }
        
        .tuition-tab { display: none; }
        .tuition-tab.active { display: block; }
        
        .tuition-table, .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .tuition-table th, .schedule-table th { background: #34495e; color: white; padding: 10px; text-align: center; }
        .tuition-table td, .schedule-table td { padding: 8px; text-align: center; border-bottom: 1px solid #ddd; }
        .tuition-table input, .schedule-table input { width: 100px; padding: 6px; text-align: right; border: 1px solid #ddd; border-radius: 4px; }
        .btn-sm { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; transition: opacity 0.3s; }
        .btn-sm:hover { opacity: 0.8; }
        
        .add-schedule-form { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .add-schedule-form h5 { margin-bottom: 10px; color: #2c3e50; }
        .schedule-input-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .schedule-input-group input { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .header { flex-direction: column; }
            .tuition-table input, .schedule-table input { width: 70px; font-size: 11px; }
            th, td { font-size: 11px; padding: 5px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>⚙️ System Settings</h2>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if(isset($_GET['success'])): ?>
            <div class="success">✓ Settings saved successfully!</div>
        <?php endif; ?>
        <?php if(isset($tuition_success)): ?>
            <div class="success">✓ <?php echo $tuition_success; ?></div>
        <?php endif; ?>
        <?php if(isset($schedule_success)): ?>
            <div class="success">✓ <?php echo $schedule_success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Main Settings Tabs -->
        <div class="tuition-tabs" style="margin-bottom: 20px;">
            <button class="tab-btn active" onclick="showMainTab('general')">🏫 General Settings</button>
            <button class="tab-btn" onclick="showMainTab('payment')">💰 Payment Settings</button>
            <button class="tab-btn" onclick="showMainTab('tuition')">📚 Tuition Management</button>
            <button class="tab-btn" onclick="showMainTab('backup')">💾 Backup</button>
        </div>
        
        <!-- General Settings Tab -->
        <div id="main-general" class="main-tab active">
            <form method="POST">
                <div class="section">
                    <h3>🏫 School Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>School Name</label>
                            <input type="text" name="school_name" value="<?php echo htmlspecialchars($settings['school_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>School Year</label>
                            <input type="text" name="school_year" value="<?php echo htmlspecialchars($settings['school_year']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>School Address</label>
                        <input type="text" name="school_address" value="<?php echo htmlspecialchars($settings['school_address']); ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="school_phone" value="<?php echo htmlspecialchars($settings['school_phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="school_email" value="<?php echo htmlspecialchars($settings['school_email']); ?>">
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="save_settings" class="btn-save">💾 Save General Settings</button>
            </form>
        </div>
        
        <!-- Payment Settings Tab -->
        <div id="main-payment" class="main-tab" style="display:none;">
            <form method="POST">
                <div class="section">
                    <h3>💰 GCash Settings</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>GCash Number</label>
                            <input type="text" name="gcash_number" value="<?php echo htmlspecialchars($settings['gcash_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label>GCash Account Name</label>
                            <input type="text" name="gcash_name" value="<?php echo htmlspecialchars($settings['gcash_name']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h3>🏦 Bank Transfer Settings</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Bank Account Number</label>
                            <input type="text" name="bank_account" value="<?php echo htmlspecialchars($settings['bank_account']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Bank Account Name</label>
                            <input type="text" name="bank_account_name" value="<?php echo htmlspecialchars($settings['bank_account_name']); ?>">
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="save_settings" class="btn-save">💾 Save Payment Settings</button>
            </form>
        </div>
        
        <!-- Tuition Management Tab -->
        <div id="main-tuition" class="main-tab" style="display:none;">
            <!-- Program Tabs -->
            <div class="tuition-tabs">
                <button type="button" class="tab-btn active" onclick="showTuitionTab('nursery')">🏆 NURSERY</button>
                <button type="button" class="tab-btn" onclick="showTuitionTab('kinder1')">🌟 KINDERGARTEN 1</button>
                <button type="button" class="tab-btn" onclick="showTuitionTab('kinder2')">🎓 KINDERGARTEN 2</button>
            </div>
            
            <!-- NURSERY Tab -->
            <div id="tab-nursery" class="tuition-tab active">
                <h4>NURSERY - Fee Breakdown</h4>
                <div style="overflow-x: auto;">
                    <table class="tuition-table">
                        <thead>
                            <tr><th>Fee Type</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $program = 'NURSERY';
                            $fee_types = ['Registration', 'Tuition fee', 'Misc. fee'];
                            foreach($fee_types as $fee): 
                                $data = $tuition_data[$program][$fee] ?? null;
                            ?>
                            <form method="POST">
                                <input type="hidden" name="program" value="<?php echo $program; ?>">
                                <input type="hidden" name="fee_type" value="<?php echo $fee; ?>">
                                <tr>
                                    <td><?php echo $fee; ?></td>
                                    <td><input type="number" name="cash" value="<?php echo $data['cash'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="semi_annual" value="<?php echo $data['semi_annual'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="quarterly" value="<?php echo $data['quarterly'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="monthly" value="<?php echo $data['monthly'] ?? 0; ?>" step="0.01"></td>
                                    <td><button type="submit" name="update_tuition" class="btn-sm" style="background:#27ae60; color:white;">Update</button></td>
                                </tr>
                            </form>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <h4>NURSERY - Payment Schedule</h4>
                <div style="overflow-x: auto;">
                    <table class="schedule-table">
                        <thead>
                            <tr><th>Payment Date</th><th>Cash</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th><th>Total Row?</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $program_schedules = $schedules[$program] ?? [];
                            foreach($program_schedules as $sch): 
                            ?>
                            <form method="POST">
                                <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                <input type="hidden" name="program" value="<?php echo $program; ?>">
                                <input type="hidden" name="payment_date" value="<?php echo $sch['payment_date']; ?>">
                                <tr>
                                    <td><?php echo $sch['payment_date']; ?></td>
                                    <td><input type="number" name="cash" value="<?php echo $sch['cash']; ?>" step="0.01"></td>
                                    <td><input type="number" name="semi_annual" value="<?php echo $sch['semi_annual']; ?>" step="0.01"></td>
                                    <td><input type="number" name="quarterly" value="<?php echo $sch['quarterly']; ?>" step="0.01"></td>
                                    <td><input type="number" name="monthly" value="<?php echo $sch['monthly']; ?>" step="0.01"></td>
                                    <td style="text-align:center;"><input type="checkbox" name="is_total" value="1" <?php echo $sch['is_total'] ? 'checked' : ''; ?>></td>
                                    <td>
                                        <button type="submit" name="update_schedule" class="btn-sm" style="background:#27ae60; color:white;">Update</button>
                                        <button type="submit" name="delete_schedule" class="btn-sm" style="background:#e74c3c; color:white;" onclick="return confirm('Delete this schedule entry?')">Delete</button>
                                    </td>
                                </tr>
                            </form>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Add New Schedule Row -->
                <div class="add-schedule-form">
                    <h5>➕ Add New Payment Schedule</h5>
                    <form method="POST" class="schedule-input-group">
                        <input type="hidden" name="program" value="<?php echo $program; ?>">
                        <input type="text" name="payment_date" placeholder="Date (e.g., 03/01/2027)" required style="width:120px;">
                        <input type="number" name="cash" placeholder="Cash" step="0.01" style="width:100px;">
                        <input type="number" name="semi_annual" placeholder="Semi Annual" step="0.01" style="width:100px;">
                        <input type="number" name="quarterly" placeholder="Quarterly" step="0.01" style="width:100px;">
                        <input type="number" name="monthly" placeholder="Monthly" step="0.01" style="width:100px;">
                        <label><input type="checkbox" name="is_total" value="1"> Total Row</label>
                        <button type="submit" name="update_schedule" class="btn-sm" style="background:#27ae60; color:white;">Add</button>
                    </form>
                </div>
            </div>
            
            <!-- KINDERGARTEN 1 Tab -->
            <div id="tab-kinder1" class="tuition-tab" style="display:none;">
                <?php $program = 'KINDERGARTEN 1'; ?>
                <h4>KINDERGARTEN 1 - Fee Breakdown</h4>
                <div style="overflow-x: auto;">
                    <table class="tuition-table">
                        <thead>
                            <tr><th>Fee Type</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($fee_types as $fee): 
                                $data = $tuition_data[$program][$fee] ?? null;
                            ?>
                            <form method="POST">
                                <input type="hidden" name="program" value="<?php echo $program; ?>">
                                <input type="hidden" name="fee_type" value="<?php echo $fee; ?>">
                                <tr>
                                    <td><?php echo $fee; ?></td>
                                    <td><input type="number" name="cash" value="<?php echo $data['cash'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="semi_annual" value="<?php echo $data['semi_annual'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="quarterly" value="<?php echo $data['quarterly'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="monthly" value="<?php echo $data['monthly'] ?? 0; ?>" step="0.01"></td>
                                    <td><button type="submit" name="update_tuition" class="btn-sm" style="background:#27ae60; color:white;">Update</button></td>
                                </tr>
                            </form>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <h4>KINDERGARTEN 1 - Payment Schedule</h4>
                <div style="overflow-x: auto;">
                    <table class="schedule-table">
                        <thead>
                            <tr><th>Payment Date</th><th>Cash</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th><th>Total Row?</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $program_schedules = $schedules[$program] ?? [];
                            foreach($program_schedules as $sch): 
                            ?>
                            <form method="POST">
                                <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                <input type="hidden" name="program" value="<?php echo $program; ?>">
                                <input type="hidden" name="payment_date" value="<?php echo $sch['payment_date']; ?>">
                                <tr>
                                    <td><?php echo $sch['payment_date']; ?></td>
                                    <td><input type="number" name="cash" value="<?php echo $sch['cash']; ?>" step="0.01"></td>
                                    <td><input type="number" name="semi_annual" value="<?php echo $sch['semi_annual']; ?>" step="0.01"></td>
                                    <td><input type="number" name="quarterly" value="<?php echo $sch['quarterly']; ?>" step="0.01"></td>
                                    <td><input type="number" name="monthly" value="<?php echo $sch['monthly']; ?>" step="0.01"></td>
                                    <td style="text-align:center;"><input type="checkbox" name="is_total" value="1" <?php echo $sch['is_total'] ? 'checked' : ''; ?>></td>
                                    <td>
                                        <button type="submit" name="update_schedule" class="btn-sm" style="background:#27ae60; color:white;">Update</button>
                                        <button type="submit" name="delete_schedule" class="btn-sm" style="background:#e74c3c; color:white;" onclick="return confirm('Delete this schedule entry?')">Delete</button>
                                    </td>
                                </tr>
                            </form>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="add-schedule-form">
                    <h5>➕ Add New Payment Schedule</h5>
                    <form method="POST" class="schedule-input-group">
                        <input type="hidden" name="program" value="<?php echo $program; ?>">
                        <input type="text" name="payment_date" placeholder="Date (e.g., 03/01/2027)" required style="width:120px;">
                        <input type="number" name="cash" placeholder="Cash" step="0.01" style="width:100px;">
                        <input type="number" name="semi_annual" placeholder="Semi Annual" step="0.01" style="width:100px;">
                        <input type="number" name="quarterly" placeholder="Quarterly" step="0.01" style="width:100px;">
                        <input type="number" name="monthly" placeholder="Monthly" step="0.01" style="width:100px;">
                        <label><input type="checkbox" name="is_total" value="1"> Total Row</label>
                        <button type="submit" name="update_schedule" class="btn-sm" style="background:#27ae60; color:white;">Add</button>
                    </form>
                </div>
            </div>
            
            <!-- KINDERGARTEN 2 Tab -->
            <div id="tab-kinder2" class="tuition-tab" style="display:none;">
                <?php $program = 'KINDERGARTEN 2'; ?>
                <h4>KINDERGARTEN 2 - Fee Breakdown</h4>
                <div style="overflow-x: auto;">
                    <table class="tuition-table">
                        <thead>
                            <tr><th>Fee Type</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($fee_types as $fee): 
                                $data = $tuition_data[$program][$fee] ?? null;
                            ?>
                            <form method="POST">
                                <input type="hidden" name="program" value="<?php echo $program; ?>">
                                <input type="hidden" name="fee_type" value="<?php echo $fee; ?>">
                                <tr>
                                    <td><?php echo $fee; ?></td>
                                    <td><input type="number" name="cash" value="<?php echo $data['cash'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="semi_annual" value="<?php echo $data['semi_annual'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="quarterly" value="<?php echo $data['quarterly'] ?? 0; ?>" step="0.01"></td>
                                    <td><input type="number" name="monthly" value="<?php echo $data['monthly'] ?? 0; ?>" step="0.01"></td>
                                    <td><button type="submit" name="update_tuition" class="btn-sm" style="background:#27ae60; color:white;">Update</button></td>
                                </tr>
                            </form>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <h4>KINDERGARTEN 2 - Payment Schedule</h4>
                <div style="overflow-x: auto;">
                    <table class="schedule-table">
                        <thead>
                            <tr><th>Payment Date</th><th>Cash</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th><th>Total Row?</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $program_schedules = $schedules[$program] ?? [];
                            foreach($program_schedules as $sch): 
                            ?>
                            <form method="POST">
                                <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                <input type="hidden" name="program" value="<?php echo $program; ?>">
                                <input type="hidden" name="payment_date" value="<?php echo $sch['payment_date']; ?>">
                                <tr>
                                    <td><?php echo $sch['payment_date']; ?></td>
                                    <td><input type="number" name="cash" value="<?php echo $sch['cash']; ?>" step="0.01"></td>
                                    <td><input type="number" name="semi_annual" value="<?php echo $sch['semi_annual']; ?>" step="0.01"></td>
                                    <td><input type="number" name="quarterly" value="<?php echo $sch['quarterly']; ?>" step="0.01"></td>
                                    <td><input type="number" name="monthly" value="<?php echo $sch['monthly']; ?>" step="0.01"></td>
                                    <td style="text-align:center;"><input type="checkbox" name="is_total" value="1" <?php echo $sch['is_total'] ? 'checked' : ''; ?>></td>
                                    <td>
                                        <button type="submit" name="update_schedule" class="btn-sm" style="background:#27ae60; color:white;">Update</button>
                                        <button type="submit" name="delete_schedule" class="btn-sm" style="background:#e74c3c; color:white;" onclick="return confirm('Delete this schedule entry?')">Delete</button>
                                    </td>
                                </tr>
                            </form>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="add-schedule-form">
                    <h5>➕ Add New Payment Schedule</h5>
                    <form method="POST" class="schedule-input-group">
                        <input type="hidden" name="program" value="<?php echo $program; ?>">
                        <input type="text" name="payment_date" placeholder="Date (e.g., 03/01/2027)" required style="width:120px;">
                        <input type="number" name="cash" placeholder="Cash" step="0.01" style="width:100px;">
                        <input type="number" name="semi_annual" placeholder="Semi Annual" step="0.01" style="width:100px;">
                        <input type="number" name="quarterly" placeholder="Quarterly" step="0.01" style="width:100px;">
                        <input type="number" name="monthly" placeholder="Monthly" step="0.01" style="width:100px;">
                        <label><input type="checkbox" name="is_total" value="1"> Total Row</label>
                        <button type="submit" name="update_schedule" class="btn-sm" style="background:#27ae60; color:white;">Add</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Backup Tab -->
        <div id="main-backup" class="main-tab" style="display:none;">
            <div class="section">
                <h3>💾 Database Backup</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Auto Backup Schedule</label>
                        <select name="backup_auto_schedule" form="backupForm">
                            <option value="daily" <?php echo $settings['backup_auto_schedule'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $settings['backup_auto_schedule'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo $settings['backup_auto_schedule'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Maintenance Mode</label>
                        <select name="maintenance_mode" form="backupForm">
                            <option value="0" <?php echo $settings['maintenance_mode'] == '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo $settings['maintenance_mode'] == '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                    </div>
                </div>
                <form method="POST" id="backupForm">
                    <button type="submit" name="create_backup" class="btn-backup">📥 Create Manual Backup</button>
                    <button type="submit" name="save_settings" class="btn-save" style="margin-left: 10px;">💾 Save Backup Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — System Administration | Changes apply immediately</p>
    </div>
</div>

<script>
function showMainTab(tabName) {
    // Hide all main tabs
    var tabs = document.querySelectorAll('.main-tab');
    tabs.forEach(function(tab) {
        tab.style.display = 'none';
    });
    
    // Show selected tab
    document.getElementById('main-' + tabName).style.display = 'block';
    
    // Update active button style
    var btns = document.querySelectorAll('.tuition-tabs:first-child .tab-btn');
    btns.forEach(function(btn) {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

function showTuitionTab(tabName) {
    // Hide all tuition tabs
    var tabs = document.querySelectorAll('.tuition-tab');
    tabs.forEach(function(tab) {
        tab.classList.remove('active');
        tab.style.display = 'none';
    });
    
    // Show selected tab
    var selectedTab = document.getElementById('tab-' + tabName);
    selectedTab.classList.add('active');
    selectedTab.style.display = 'block';
    
    // Update active button style within tuition tabs
    var btns = document.querySelectorAll('#main-tuition .tuition-tabs .tab-btn');
    btns.forEach(function(btn) {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}
</script>
</body>
</html>