<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition & Fees - Daily Bread Learning Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .nav {
            background: #34495e;
            padding: 0;
            display: flex;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .nav a:hover, .nav a.active {
            background: #27ae60;
        }
        
        .content {
            padding: 30px;
        }
        
        .program-section {
            margin-bottom: 40px;
            background: #f9f9f9;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .program-title {
            background: #27ae60;
            color: white;
            padding: 15px 20px;
            font-size: 24px;
            font-weight: bold;
        }
        
        .table-container {
            padding: 20px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
        }
        
        td {
            padding: 10px 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        
        .section-title {
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .amount {
            font-weight: bold;
            color: #27ae60;
        }
        
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
            }
            th, td {
                font-size: 12px;
                padding: 8px;
            }
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #27ae60;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏫 DAILY BREAD LEARNING CENTER INC.</h1>
        <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City | 📞 0923-4701532</p>
        <p>📩 Preschool Department - Academy Year 2026-2027</p>
    </div>
    
    <div class="nav">
        <a href="index.php">📝 Registration Form</a>
        <a href="view_enrollees.php">📊 Enrolled Students (Database)</a>
        <a href="tuition_fees.php" class="active">💰 Tuition & Fees</a>
    </div>
    
    <div class="content">
        <h2 style="color: #2c3e50; margin-bottom: 20px;">💰 TUITION FEE & MISCELLANEOUS</h2>
        <p style="margin-bottom: 20px; color: #666;">Academic Year 2026-2027</p>
        
        <!-- NURSERY SECTION -->
        <div class="program-section">
            <div class="program-title">🏆 NURSERY</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr><th>DESCRIPTION</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Registration</td><td>500.00</td><td>500.00</td><td>500.00</td><td>500.00</td></tr>
                        <tr><td>Tuition Fee</td><td>13,500.00</td><td>5,400.00</td><td>3,600.00</td><td>2,250.00</td></tr>
                        <tr><td>Misc. Fee</td><td>3,500.00</td><td>3,000.00</td><td>2,500.00</td><td>2,500.00</td></tr>
                        <tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong>₱17,500.00</strong></td><td class="amount"><strong>₱8,900.00</strong></td><td class="amount"><strong>₱6,600.00</strong></td><td class="amount"><strong>₱5,250.00</strong></td></tr>
                    </tbody>
                </table>
                
                <h3 style="margin: 25px 0 15px; color: #2c3e50;">Schedule of Payment</h3>
                <table>
                    <thead>
                        <tr><th>Months</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Upon enrollment</td><td>17,500.00</td><td>8,900.00</td><td>6,600.00</td><td>5,250.00</td></tr>
                        <tr><td>07/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,450.00</td></tr>
                        <tr><td>08/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,450.00</td></tr>
                        <tr><td>09/01/2026</td><td>-</td><td>-</td><td>3,950.00</td><td>1,450.00</td></tr>
                        <tr><td>10/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,450.00</td></tr>
                        <tr><td>11/01/2026</td><td>-</td><td>9,000.00</td><td>-</td><td>1,450.00</td></tr>
                        <tr><td>12/01/2026</td><td>-</td><td>-</td><td>3,950.00</td><td>1,450.00</td></tr>
                        <tr><td>01/01/2027</td><td>-</td><td>-</td><td>-</td><td>1,450.00</td></tr>
                        <tr><td>02/01/2027</td><td>-</td><td>-</td><td>-</td><td>1,450.00</td></tr>
                        <tr><td>03/01/2027</td><td>-</td><td>-</td><td>3,950.00</td><td>1,450.00</td></tr>
                        <tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong>₱17,500.00</strong></td><td class="amount"><strong>₱17,900.00</strong></td><td class="amount"><strong>₱18,450.00</strong></td><td class="amount"><strong>₱18,850.00</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- KINDERGARTEN 1 SECTION -->
        <div class="program-section">
            <div class="program-title">🌟 KINDERGARTEN 1</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr><th>DESCRIPTION</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Registration</td><td>500.00</td><td>500.00</td><td>500.00</td><td>500.00</td></tr>
                        <tr><td>Tuition Fee</td><td>14,300.00</td><td>6,400.00</td><td>4,050.00</td><td>2,650.00</td></tr>
                        <tr><td>Misc. Fee</td><td>3,500.00</td><td>2,500.00</td><td>2,500.00</td><td>2,550.00</td></tr>
                        <tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong>₱18,300.00</strong></td><td class="amount"><strong>₱9,400.00</strong></td><td class="amount"><strong>₱7,050.00</strong></td><td class="amount"><strong>₱5,700.00</strong></td></tr>
                    </tbody>
                </table>
                
                <h3 style="margin: 25px 0 15px; color: #2c3e50;">Schedule of Payment</h3>
                 <table>
                    <thead>
                        <tr><th>Months</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Upon enrollment</td><td>18,300.00</td><td>9,400.00</td><td>7,050.00</td><td>5,700.00</td></tr>
                        <tr><td>07/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>08/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>09/01/2026</td><td>-</td><td>-</td><td>3,950.00</td><td>1,500.00</td></tr>
                        <tr><td>10/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>11/01/2026</td><td>-</td><td>9,300.00</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>12/01/2026</td><td>-</td><td>-</td><td>3,950.00</td><td>1,500.00</td></tr>
                        <tr><td>01/01/2027</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>02/01/2027</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>03/01/2027</td><td>-</td><td>-</td><td>3,950.00</td><td>1,500.00</td></tr>
                        <tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong>₱18,300.00</strong></td><td class="amount"><strong>₱18,700.00</strong></td><td class="amount"><strong>₱18,900.00</strong></td><td class="amount"><strong>₱19,200.00</strong></td></tr>
                    </tbody>
                 </table>
            </div>
        </div>
        
        <!-- KINDERGARTEN 2 SECTION -->
        <div class="program-section">
            <div class="program-title">🎓 KINDERGARTEN 2</div>
            <div class="table-container">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Tuition Fee Breakdown</h3>
                <table>
                    <thead>
                        <tr><th>DESCRIPTION</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Registration</td><td>500.00</td><td>500.00</td><td>500.00</td><td>500.00</td></tr>
                        <tr><td>Tuition Fee</td><td>14,300.00</td><td>6,600.00</td><td>4,550.00</td><td>3,200.00</td></tr>
                        <tr><td>Misc. Fee</td><td>3,500.00</td><td>3,000.00</td><td>2,500.00</td><td>2,500.00</td></tr>
                        <tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong>₱18,300.00</strong></td><td class="amount"><strong>₱10,100.00</strong></td><td class="amount"><strong>₱7,550.00</strong></td><td class="amount"><strong>₱6,200.00</strong></td></tr>
                    </tbody>
                 </table>
                
                <h3 style="margin: 25px 0 15px; color: #2c3e50;">Schedule of Payment</h3>
                <table>
                    <thead>
                        <tr><th>Months</th><th>Cash (Full)</th><th>Semi Annual</th><th>Quarterly</th><th>Monthly</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Upon enrollment</td><td>18,300.00</td><td>10,100.00</td><td>7,550.00</td><td>6,200.00</td></tr>
                        <tr><td>07/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>08/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>09/01/2026</td><td>-</td><td>-</td><td>3,950.00</td><td>1,500.00</td></tr>
                        <tr><td>10/01/2026</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>11/01/2026</td><td>-</td><td>9,500.00</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>12/01/2026</td><td>-</td><td>-</td><td>3,950.00</td><td>1,500.00</td></tr>
                        <tr><td>01/01/2027</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>02/01/2027</td><td>-</td><td>-</td><td>-</td><td>1,500.00</td></tr>
                        <tr><td>03/01/2027</td><td>-</td><td>-</td><td>3,950.00</td><td>1,500.00</td></tr>
                        <tr class="total-row"><td><strong>TOTAL</strong></td><td class="amount"><strong>₱18,300.00</strong></td><td class="amount"><strong>₱19,600.00</strong></td><td class="amount"><strong>₱19,400.00</strong></td><td class="amount"><strong>₱19,700.00</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <p style="color: #2c3e50; text-align: center;">
                📌 <strong>Note:</strong> Cash payment is due upon enrollment. Installment plans follow the schedule above.
                <br>For inquiries, please call 📞 0923-4701532
            </p>
        </div>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure enrollment database | For immunization, please attach physical copy.</p>
    </div>
</div>
</body>
</html>