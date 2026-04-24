<?php
require_once 'db_connection.php';
require_once 'includes_functions.php';

// Get tuition fees from system settings
$nursery_fee = getSetting($pdo, 'enrollment_fee_nursery') ?: 17500;
$k1_fee = getSetting($pdo, 'enrollment_fee_k1') ?: 18300;
$k2_fee = getSetting($pdo, 'enrollment_fee_k2') ?: 18300;
$school_name = getSetting($pdo, 'school_name') ?: 'Daily Bread Learning Center Inc.';
$school_year = getSetting($pdo, 'school_year') ?: '2026-2027';
$school_address = getSetting($pdo, 'school_address') ?: 'Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City';
$school_phone = getSetting($pdo, 'school_phone') ?: '0923-4701532';

// Calculate payment schedule amounts
function calculatePaymentSchedule($total_fee) {
    $registration = 500;
    $tuition = $total_fee - $registration;
    
    return [
        'cash' => $total_fee,
        'semi_annual' => round($tuition * 0.4 + $registration),
        'quarterly' => round($tuition * 0.25 + $registration),
        'monthly' => round($tuition * 0.15 + $registration)
    ];
}

$nursery_schedule = calculatePaymentSchedule($nursery_fee);
$k1_schedule = calculatePaymentSchedule($k1_fee);
$k2_schedule = calculatePaymentSchedule($k2_fee);

// Monthly payment amounts for schedules
$monthly_amounts = [
    'NURSERY' => round(($nursery_fee - 500) * 0.15),
    'KINDERGARTEN 1' => round(($k1_fee - 500) * 0.15),
    'KINDERGARTEN 2' => round(($k2_fee - 500) * 0.15)
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
        }
        
        .header img {
            height: 60px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            font-size: 24px;
            margin: 5px 0;
        }
        
        .header p {
            font-size: 12px;
            opacity: 0.9;
            margin: 3px 0;
        }
        
        .nav {
            background: #34495e;
            padding: 12px;
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
            font-weight: 500;
        }
        
        .nav a:hover, .nav a.active {
            background: #e74c3c;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h2 {
            color: #2c3e50;
        }
        
        .back-btn {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .back-btn:hover {
            background: #2980b9;
        }
        
        .program-section {
            margin-bottom: 50px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .program-title {
            background: #27ae60;
            color: white;
            padding: 15px 20px;
            font-size: 22px;
            font-weight: bold;
        }
        
        .table-container {
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #34495e;
            color: white;
        }
        
        .schedule-title {
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            margin: 20px 0 15px;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .amount {
            font-weight: bold;
            color: #27ae60;
        }
        
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .note {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            color: #2c3e50;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 12px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            th, td {
                font-size: 11px;
                padding: 6px;
            }
            .nav {
                gap: 10px;
            }
            .nav a {
                padding: 5px 10px;
                font-size: 12px;
            }
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
        </div>
        <p style="margin-bottom: 20px; color: #666;">Academic Year <?php echo htmlspecialchars($school_year); ?></p>
        
        <!-- ============================================ -->
        <!-- NURSERY SECTION                              -->
        <!-- ============================================ -->
        <div class="program-section">
            <div class="program-title">🏆 NURSERY</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>DESCRIPTION</th>
                            <th>Cash (Full)</th>
                            <th>Semi Annual</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Registration</td>
                            <td>500.00</td>
                            <td>500.00</td>
                            <td>500.00</td>
                            <td>500.00</td>
                         </tr>
                        <tr>
                            <td>Tuition Fee</td>
                            <td><?php echo number_format($nursery_fee - 500, 2); ?></td>
                            <td><?php echo number_format(($nursery_fee - 500) * 0.4, 2); ?></td>
                            <td><?php echo number_format(($nursery_fee - 500) * 0.25, 2); ?></td>
                            <td><?php echo number_format(($nursery_fee - 500) * 0.15, 2); ?></td>
                         </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['cash'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['semi_annual'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['quarterly'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['monthly'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- NURSERY Payment Schedule -->
                <div class="schedule-title">📅 Schedule of Payment - NURSERY</div>
                <table>
                    <thead>
                        <tr>
                            <th>Months</th>
                            <th>Cash (Full)</th>
                            <th>Semi Annual</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Upon enrollment</td>
                            <td class="amount">₱<?php echo number_format($nursery_schedule['cash'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($nursery_schedule['semi_annual'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($nursery_schedule['quarterly'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td>
                        </tr>
                        <tr><td>July 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>August 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>September 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($nursery_schedule['quarterly'] - $monthly_amounts['NURSERY'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>October 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>November 1, <?php echo date('Y'); ?></td><td>-</td><td>₱<?php echo number_format($nursery_schedule['semi_annual'] - $monthly_amounts['NURSERY'] * 2, 2); ?></td><td>-</td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>December 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($nursery_schedule['quarterly'] - $monthly_amounts['NURSERY'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>January 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>February 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr><td>March 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($nursery_schedule['quarterly'] - $monthly_amounts['NURSERY'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['NURSERY'], 2); ?></td></tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['cash'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['semi_annual'] + $monthly_amounts['NURSERY'] * 2, 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['quarterly'] * 3, 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($nursery_schedule['monthly'] * 10, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- KINDERGARTEN 1 SECTION                        -->
        <!-- ============================================ -->
        <div class="program-section">
            <div class="program-title">🌟 KINDERGARTEN 1</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>DESCRIPTION</th>
                            <th>Cash (Full)</th>
                            <th>Semi Annual</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Registration</td><td>500.00</td><td>500.00</td><td>500.00</td><td>500.00</td></tr>
                        <tr><td>Tuition Fee</td>
                            <td><?php echo number_format($k1_fee - 500, 2); ?></td>
                            <td><?php echo number_format(($k1_fee - 500) * 0.4, 2); ?></td>
                            <td><?php echo number_format(($k1_fee - 500) * 0.25, 2); ?></td>
                            <td><?php echo number_format(($k1_fee - 500) * 0.15, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['cash'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['semi_annual'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['quarterly'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['monthly'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- KINDERGARTEN 1 Payment Schedule -->
                <div class="schedule-title">📅 Schedule of Payment - KINDERGARTEN 1</div>
                <table>
                    <thead>
                        <tr>
                            <th>Months</th>
                            <th>Cash (Full)</th>
                            <th>Semi Annual</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Upon enrollment</td>
                            <td class="amount">₱<?php echo number_format($k1_schedule['cash'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($k1_schedule['semi_annual'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($k1_schedule['quarterly'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td>
                        </tr>
                        <tr><td>July 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>August 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>September 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($k1_schedule['quarterly'] - $monthly_amounts['KINDERGARTEN 1'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>October 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>November 1, <?php echo date('Y'); ?></td><td>-</td><td>₱<?php echo number_format($k1_schedule['semi_annual'] - $monthly_amounts['KINDERGARTEN 1'] * 2, 2); ?></td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>December 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($k1_schedule['quarterly'] - $monthly_amounts['KINDERGARTEN 1'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>January 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>February 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr><td>March 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($k1_schedule['quarterly'] - $monthly_amounts['KINDERGARTEN 1'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 1'], 2); ?></td></tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['cash'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['semi_annual'] + $monthly_amounts['KINDERGARTEN 1'] * 2, 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['quarterly'] * 3, 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k1_schedule['monthly'] * 10, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- KINDERGARTEN 2 SECTION                        -->
        <!-- ============================================ -->
        <div class="program-section">
            <div class="program-title">🎓 KINDERGARTEN 2</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>DESCRIPTION</th>
                            <th>Cash (Full)</th>
                            <th>Semi Annual</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Registration</td><td>500.00</td><td>500.00</td><td>500.00</td><td>500.00</td></tr>
                        <tr><td>Tuition Fee</td>
                            <td><?php echo number_format($k2_fee - 500, 2); ?></td>
                            <td><?php echo number_format(($k2_fee - 500) * 0.4, 2); ?></td>
                            <td><?php echo number_format(($k2_fee - 500) * 0.25, 2); ?></td>
                            <td><?php echo number_format(($k2_fee - 500) * 0.15, 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['cash'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['semi_annual'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['quarterly'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['monthly'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- KINDERGARTEN 2 Payment Schedule -->
                <div class="schedule-title">📅 Schedule of Payment - KINDERGARTEN 2</div>
                <table>
                    <thead>
                        <tr>
                            <th>Months</th>
                            <th>Cash (Full)</th>
                            <th>Semi Annual</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Upon enrollment</td>
                            <td class="amount">₱<?php echo number_format($k2_schedule['cash'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($k2_schedule['semi_annual'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($k2_schedule['quarterly'], 2); ?></td>
                            <td class="amount">₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td>
                        </tr>
                        <tr><td>July 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>August 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>September 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($k2_schedule['quarterly'] - $monthly_amounts['KINDERGARTEN 2'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <td><td>October 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>November 1, <?php echo date('Y'); ?></td><td>-</td><td>₱<?php echo number_format($k2_schedule['semi_annual'] - $monthly_amounts['KINDERGARTEN 2'] * 2, 2); ?></td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>December 1, <?php echo date('Y'); ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($k2_schedule['quarterly'] - $monthly_amounts['KINDERGARTEN 2'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>January 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>February 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>-</td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr><td>March 1, <?php echo date('Y')+1; ?></td><td>-</td><td>-</td><td>₱<?php echo number_format($k2_schedule['quarterly'] - $monthly_amounts['KINDERGARTEN 2'], 2); ?></td><td>₱<?php echo number_format($monthly_amounts['KINDERGARTEN 2'], 2); ?></td></tr>
                        <tr class="total-row">
                            <td><strong>TOTAL</strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['cash'], 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['semi_annual'] + $monthly_amounts['KINDERGARTEN 2'] * 2, 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['quarterly'] * 3, 2); ?></strong></td>
                            <td class="amount"><strong>₱<?php echo number_format($k2_schedule['monthly'] * 10, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="note">
            <strong>Note:</strong> Cash payment is due upon enrollment. Installment plans follow the schedule above.<br>
            Tuition fees shown are for the full academic year. For inquiries, please call <?php echo htmlspecialchars($school_phone); ?>
        </div>
    </div>
    
    <div class="footer">
        <p>© <?php echo htmlspecialchars($school_name); ?> — Secure enrollment database</p>
    </div>
</body>
</html>