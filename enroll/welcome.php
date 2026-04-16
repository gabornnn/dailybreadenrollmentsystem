<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Bread Learning Center - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .logo {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        /* Navigation Cards */
        .nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 22px;
        }
        
        .card p {
            color: #666;
            font-size: 14px;
        }
        
        .card.enroll { border-top: 5px solid #27ae60; }
        .card.admin { border-top: 5px solid #e74c3c; }
        .card.registrar { border-top: 5px solid #3498db; }
        .card.cashier { border-top: 5px solid #f39c12; }
        .card.view { border-top: 5px solid #9b59b6; }
        .card.tuition { border-top: 5px solid #1abc9c; }
        
        /* Footer */
        .footer {
            text-align: center;
            color: white;
            padding: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .nav-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">🏫</div>
        <h1>DAILY BREAD LEARNING CENTER INC.</h1>
        <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City | 📞 0923-4701532</p>
        <p style="margin-top: 10px;">Preschool Department - Academy Year 2026-2027</p>
    </div>
    
    <div class="nav-cards">
        <!-- Public Enrollment -->
        <a href="index.php" class="card enroll">
            <div class="card-icon">📝</div>
            <h3>Student Enrollment</h3>
            <p>New student registration form</p>
        </a>
        
        <!-- Admin Login -->
        <a href="login.php?role=admin" class="card admin">
            <div class="card-icon">👑</div>
            <h3>Admin Portal</h3>
            <p>View all student records (Read Only)</p>
        </a>
        
        <!-- Registrar Login -->
        <a href="login.php?role=registrar" class="card registrar">
            <div class="card-icon">📋</div>
            <h3>Registrar Portal</h3>
            <p>Update student qualification status</p>
        </a>
        
        <!-- Cashier Login -->
        <a href="login.php?role=cashier" class="card cashier">
            <div class="card-icon">💰</div>
            <h3>Cashier Portal</h3>
            <p>Update payment status & transactions</p>
        </a>
        
        <!-- View Enrollees -->
        <a href="view_enrollees.php" class="card view">
            <div class="card-icon">👥</div>
            <h3>Enrolled Students</h3>
            <p>View list of enrolled students</p>
        </a>
        
        <!-- Tuition & Fees -->
        <a href="tuition_fees.php" class="card tuition">
            <div class="card-icon">💵</div>
            <h3>Tuition & Fees</h3>
            <p>View fee structure and payment schedules</p>
        </a>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure enrollment management system</p>
    </div>
</div>
</body>
</html>