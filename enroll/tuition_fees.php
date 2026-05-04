<?php
require_once 'db_connection.php';
require_once 'includes_functions.php';

// Get school info from system settings
$school_name = getSetting($pdo, 'school_name') ?: 'Daily Bread Learning Center Inc.';
$school_year = getSetting($pdo, 'school_year') ?: '2026-2027';
$school_address = getSetting($pdo, 'school_address') ?: 'Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City';
$school_phone = getSetting($pdo, 'school_phone') ?: '0923-4701532';

// Get tuition data from database
function getTuitionData($pdo, $program) {
    $stmt = $pdo->prepare("SELECT fee_type, cash, semi_annual, quarterly, monthly FROM tuition_settings WHERE program = ?");
    $stmt->execute([$program]);
    $data = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[$row['fee_type']] = $row;
    }
    return $data;
}

function getPaymentSchedule($pdo, $program) {
    $stmt = $pdo->prepare("SELECT id, payment_date, cash, semi_annual, quarterly, monthly, is_total FROM payment_schedule WHERE program = ? ORDER BY id");
    $stmt->execute([$program]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all tuition data
$nursery_data = getTuitionData($pdo, 'NURSERY');
$nursery_schedule = getPaymentSchedule($pdo, 'NURSERY');

$kinder1_data = getTuitionData($pdo, 'KINDERGARTEN 1');
$kinder1_schedule = getPaymentSchedule($pdo, 'KINDERGARTEN 1');

$kinder2_data = getTuitionData($pdo, 'KINDERGARTEN 2');
$kinder2_schedule = getPaymentSchedule($pdo, 'KINDERGARTEN 2');

// Helper function to get total from schedule
function getScheduleTotal($schedule, $column) {
    foreach($schedule as $row) {
        if($row['is_total']) {
            return $row[$column];
        }
    }
    return 0;
}

// Get totals
$nursery_totals = [
    'cash' => getScheduleTotal($nursery_schedule, 'cash'),
    'semi_annual' => getScheduleTotal($nursery_schedule, 'semi_annual'),
    'quarterly' => getScheduleTotal($nursery_schedule, 'quarterly'),
    'monthly' => getScheduleTotal($nursery_schedule, 'monthly')
];

$kinder1_totals = [
    'cash' => getScheduleTotal($kinder1_schedule, 'cash'),
    'semi_annual' => getScheduleTotal($kinder1_schedule, 'semi_annual'),
    'quarterly' => getScheduleTotal($kinder1_schedule, 'quarterly'),
    'monthly' => getScheduleTotal($kinder1_schedule, 'monthly')
];

$kinder2_totals = [
    'cash' => getScheduleTotal($kinder2_schedule, 'cash'),
    'semi_annual' => getScheduleTotal($kinder2_schedule, 'semi_annual'),
    'quarterly' => getScheduleTotal($kinder2_schedule, 'quarterly'),
    'monthly' => getScheduleTotal($kinder2_schedule, 'monthly')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition & Fees - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; }
        
        .header { background: #2c3e50; color: white; text-align: center; padding: 20px; }
        .header img { height: 60px; margin-bottom: 10px; }
        .header h1 { font-size: 24px; margin: 5px 0; }
        .header p { font-size: 12px; opacity: 0.9; margin: 3px 0; }
        
        .nav { background: #34495e; padding: 12px; display: flex; justify-content: center; gap: 25px; flex-wrap: wrap; }
        .nav a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; font-weight: 500; }
        .nav a:hover, .nav a.active { background: #e74c3c; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .page-header h2 { color: #2c3e50; }
        .back-btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .back-btn:hover { background: #2980b9; }
        
        .program-section { margin-bottom: 50px; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .program-title { background: #27ae60; color: white; padding: 15px 20px; font-size: 22px; font-weight: bold; }
        .table-container { padding: 20px; overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: center; border: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        .total-row { background: #f0f0f0; font-weight: bold; }
        .amount { font-weight: bold; color: #27ae60; }
        .schedule-title { background: #2c3e50; color: white; padding: 10px 15px; margin: 20px 0 15px; border-radius: 5px; font-size: 16px; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        @media (max-width: 768px) {
            th, td { font-size: 11px; padding: 6px; }
            .nav { gap: 10px; }
            .nav a { padding: 5px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/logo.png" alt="Logo">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p><?php echo htmlspecialchars($school_address); ?> | <?php echo htmlspecialchars($school_phone); ?></p>
        <p>Preschool Department - Academy Year <?php echo htmlspecialchars($school_year); ?></p>
    </div>
    
    <div class="nav">
        <a href="welcome.php">Home</a>
        <a href="index.php">Registration Form</a>
        <a href="view_enrollees.php">Enrolled Students</a>
        <a href="tuition_fees.php" class="active">Tuition and Fees</a>
        <a href="online_payment.php">💳 Pay Online</a>
        <a href="welcome.php#portals">Staff Portals</a>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2>TUITION FEE & MISCELLANEOUS</h2>
            <a href="welcome.php" class="back-btn">← Back to Home</a>
        </div>
        <p style="margin-bottom: 20px; color: #666;">Academic Year <?php echo htmlspecialchars($school_year); ?></p>
        
        <!-- ============================================ -->
        <!-- NURSERY SECTION -->
        <!-- ============================================ -->
        <div class="program-section">
            <div class="program-title">🏆 NURSERY</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr><th>DESCRIPTION</th><th>cash</th><th>semi annual</th><th>quarterly</th><th>monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Registration</td>
                            <td><?php echo number_format($nursery_data['Registration']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Registration']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Registration']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Registration']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Tuition fee</td>
                            <td><?php echo number_format($nursery_data['Tuition fee']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Tuition fee']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Tuition fee']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Tuition fee']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Misc. fee</td>
                            <td><?php echo number_format($nursery_data['Misc. fee']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Misc. fee']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Misc. fee']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($nursery_data['Misc. fee']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($nursery_data['Registration']['cash'] ?? 0) + 
                                ($nursery_data['Tuition fee']['cash'] ?? 0) + 
                                ($nursery_data['Misc. fee']['cash'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($nursery_data['Registration']['semi_annual'] ?? 0) + 
                                ($nursery_data['Tuition fee']['semi_annual'] ?? 0) + 
                                ($nursery_data['Misc. fee']['semi_annual'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($nursery_data['Registration']['quarterly'] ?? 0) + 
                                ($nursery_data['Tuition fee']['quarterly'] ?? 0) + 
                                ($nursery_data['Misc. fee']['quarterly'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($nursery_data['Registration']['monthly'] ?? 0) + 
                                ($nursery_data['Tuition fee']['monthly'] ?? 0) + 
                                ($nursery_data['Misc. fee']['monthly'] ?? 0), 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="schedule-title">📅 Schedule of Payment</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr><th>Months</th><th>cash</th><th>semi annual</th><th>quarterly</th><th>monthly</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($nursery_schedule as $sch): ?>
                            <tr <?php echo $sch['is_total'] ? 'class="total-row"' : ''; ?>>
                                <td><?php echo $sch['payment_date']; ?></td>
                                <td><?php echo $sch['cash'] ? '₱'.number_format($sch['cash'],2) : '-'; ?></td>
                                <td><?php echo $sch['semi_annual'] ? '₱'.number_format($sch['semi_annual'],2) : '-'; ?></td>
                                <td><?php echo $sch['quarterly'] ? '₱'.number_format($sch['quarterly'],2) : '-'; ?></td>
                                <td><?php echo $sch['monthly'] ? '₱'.number_format($sch['monthly'],2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- KINDERGARTEN 1 SECTION -->
        <!-- ============================================ -->
        <div class="program-section">
            <div class="program-title">🌟 KINDERGARTEN 1</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr><th>DESCRIPTION</th><th>cash</th><th>semi annual</th><th>quarterly</th><th>monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Registration</td>
                            <td><?php echo number_format($kinder1_data['Registration']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Registration']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Registration']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Registration']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Tuition fee</td>
                            <td><?php echo number_format($kinder1_data['Tuition fee']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Tuition fee']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Tuition fee']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Tuition fee']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Misc. fee</td>
                            <td><?php echo number_format($kinder1_data['Misc. fee']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Misc. fee']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Misc. fee']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder1_data['Misc. fee']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder1_data['Registration']['cash'] ?? 0) + 
                                ($kinder1_data['Tuition fee']['cash'] ?? 0) + 
                                ($kinder1_data['Misc. fee']['cash'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder1_data['Registration']['semi_annual'] ?? 0) + 
                                ($kinder1_data['Tuition fee']['semi_annual'] ?? 0) + 
                                ($kinder1_data['Misc. fee']['semi_annual'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder1_data['Registration']['quarterly'] ?? 0) + 
                                ($kinder1_data['Tuition fee']['quarterly'] ?? 0) + 
                                ($kinder1_data['Misc. fee']['quarterly'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder1_data['Registration']['monthly'] ?? 0) + 
                                ($kinder1_data['Tuition fee']['monthly'] ?? 0) + 
                                ($kinder1_data['Misc. fee']['monthly'] ?? 0), 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="schedule-title">📅 Schedule of Payment</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr><th>Months</th><th>cash</th><th>semi annual</th><th>quarterly</th><th>monthly</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($kinder1_schedule as $sch): ?>
                            <tr <?php echo $sch['is_total'] ? 'class="total-row"' : ''; ?>>
                                <td><?php echo $sch['payment_date']; ?></td>
                                <td><?php echo $sch['cash'] ? '₱'.number_format($sch['cash'],2) : '-'; ?></td>
                                <td><?php echo $sch['semi_annual'] ? '₱'.number_format($sch['semi_annual'],2) : '-'; ?></td>
                                <td><?php echo $sch['quarterly'] ? '₱'.number_format($sch['quarterly'],2) : '-'; ?></td>
                                <td><?php echo $sch['monthly'] ? '₱'.number_format($sch['monthly'],2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- KINDERGARTEN 2 SECTION -->
        <!-- ============================================ -->
        <div class="program-section">
            <div class="program-title">🎓 KINDERGARTEN 2</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr><th>DESCRIPTION</th><th>cash</th><th>semi annual</th><th>quarterly</th><th>monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Registration</td>
                            <td><?php echo number_format($kinder2_data['Registration']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Registration']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Registration']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Registration']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Tuition fee</td>
                            <td><?php echo number_format($kinder2_data['Tuition fee']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Tuition fee']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Tuition fee']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Tuition fee']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Misc. fee</td>
                            <td><?php echo number_format($kinder2_data['Misc. fee']['cash'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Misc. fee']['semi_annual'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Misc. fee']['quarterly'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($kinder2_data['Misc. fee']['monthly'] ?? 0, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder2_data['Registration']['cash'] ?? 0) + 
                                ($kinder2_data['Tuition fee']['cash'] ?? 0) + 
                                ($kinder2_data['Misc. fee']['cash'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder2_data['Registration']['semi_annual'] ?? 0) + 
                                ($kinder2_data['Tuition fee']['semi_annual'] ?? 0) + 
                                ($kinder2_data['Misc. fee']['semi_annual'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder2_data['Registration']['quarterly'] ?? 0) + 
                                ($kinder2_data['Tuition fee']['quarterly'] ?? 0) + 
                                ($kinder2_data['Misc. fee']['quarterly'] ?? 0), 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format(
                                ($kinder2_data['Registration']['monthly'] ?? 0) + 
                                ($kinder2_data['Tuition fee']['monthly'] ?? 0) + 
                                ($kinder2_data['Misc. fee']['monthly'] ?? 0), 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="schedule-title">📅 Schedule of Payment</div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr><th>Months</th><th>cash</th><th>semi annual</th><th>quarterly</th><th>monthly</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($kinder2_schedule as $sch): ?>
                            <tr <?php echo $sch['is_total'] ? 'class="total-row"' : ''; ?>>
                                <td><?php echo $sch['payment_date']; ?></td>
                                <td><?php echo $sch['cash'] ? '₱'.number_format($sch['cash'],2) : '-'; ?></td>
                                <td><?php echo $sch['semi_annual'] ? '₱'.number_format($sch['semi_annual'],2) : '-'; ?></td>
                                <td><?php echo $sch['quarterly'] ? '₱'.number_format($sch['quarterly'],2) : '-'; ?></td>
                                <td><?php echo $sch['monthly'] ? '₱'.number_format($sch['monthly'],2) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="note" style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
            <strong>Note:</strong> Cash payment is due upon enrollment. Installment plans follow the schedule above.<br>
            For inquiries, please call <?php echo htmlspecialchars($school_phone); ?>
        </div>
    </div>
    
    <div class="footer">
        <p>© <?php echo htmlspecialchars($school_name); ?> — Secure enrollment database</p>
    </div>
</body>
</html>