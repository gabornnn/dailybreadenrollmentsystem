<?php
require_once 'db_connection.php';
require_once 'includes_functions.php';

// Get all settings from database
$school_name = getSetting($pdo, 'school_name') ?: 'Daily Bread Learning Center Inc.';
$school_year = getSetting($pdo, 'school_year') ?: '2026-2027';
$school_address = getSetting($pdo, 'school_address') ?: 'Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City';
$school_phone = getSetting($pdo, 'school_phone') ?: '0923-4701532';
$school_email = getSetting($pdo, 'school_email') ?: 'info@dailybread.edu.ph';

// Get tuition fees from system settings
$nursery_fee = getSetting($pdo, 'enrollment_fee_nursery') ?: 17500;
$k1_fee = getSetting($pdo, 'enrollment_fee_k1') ?: 18300;
$k2_fee = getSetting($pdo, 'enrollment_fee_k2') ?: 18300;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($school_name); ?> | Christian Preschool in Kalookan City</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="shortcut icon" href="images/logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .logo-text h1 {
            font-size: 20px;
            color: #2c3e50;
            letter-spacing: 1px;
        }

        .logo-text p {
            font-size: 10px;
            color: #7f8c8d;
            letter-spacing: 2px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #e74c3c;
        }

        .hero {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 160px 30px 100px;
            text-align: center;
        }

        .hero h2 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 300;
        }

        .hero h2 span {
            font-weight: 700;
        }

        .hero p {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .btn-primary {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 12px 35px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #c0392b;
        }

        .section {
            padding: 80px 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 34px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .section-subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 50px;
            font-size: 18px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .about-text h3 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .about-text p {
            color: #666;
            margin-bottom: 20px;
        }

        .about-image {
            height: 300px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-image img {
            max-width: 80%;
            max-height: 80%;
            opacity: 1;
        }

        .programs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .program-card {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s;
        }

        .program-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .program-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 22px;
        }

        .program-card .price {
            color: #e74c3c;
            font-size: 28px;
            font-weight: bold;
            margin: 15px 0;
        }

        .program-card p {
            color: #666;
            margin-bottom: 20px;
        }

        .btn-outline {
            display: inline-block;
            background: transparent;
            border: 2px solid #2c3e50;
            color: #2c3e50;
            padding: 8px 25px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline:hover {
            background: #2c3e50;
            color: white;
        }

        .portals-section {
            background: #f5f6fa;
            padding: 60px 30px;
        }

        .portals-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .portals-row {
            display: flex;
            gap: 25px;
            justify-content: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .portal-btn {
            display: block;
            background: white;
            padding: 30px 20px;
            text-decoration: none;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
            flex: 1;
            min-width: 200px;
        }

        .portal-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .portal-btn h3 {
            font-size: 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .portal-btn p {
            font-size: 13px;
            color: #7f8c8d;
        }

        .portal-btn.enroll h3 { color: #27ae60; }
        .portal-btn.admin h3 { color: #e74c3c; }
        .portal-btn.registrar h3 { color: #3498db; }
        .portal-btn.cashier h3 { color: #f39c12; }
        .portal-btn.view h3 { color: #9b59b6; }
        .portal-btn.tuition h3 { color: #1abc9c; }
        .portal-btn.payment h3 { color: #f39c12; }
        .portal-btn.refund h3 { color: #e74c3c; }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 30px 20px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-col h4 {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .footer-col p, .footer-col a {
            color: #bdc3c7;
            text-decoration: none;
            line-height: 1.8;
            display: block;
        }

        .footer-col a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid #445566;
            color: #bdc3c7;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 20px;
            }
            .hero h2 {
                font-size: 28px;
            }
            .about-grid {
                grid-template-columns: 1fr;
            }
            .section {
                padding: 50px 20px;
            }
            .portals-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <img src="images/logo.png" alt="Daily Bread Learning Center Logo">
                <div class="logo-text">
                    <h1><?php echo htmlspecialchars($school_name); ?></h1>
                    <p>LEARNING CENTER INC.</p>
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#programs">Programs</a></li>
                <li><a href="#portals">Portals</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
    </header>

    <section id="home" class="hero">
        <h2>Child-Driven Learning in a <span>Joyful Environment</span></h2>
        <p>A preschool where children are encouraged to be curious, explore their world, and develop a love for learning in a Christ-centered environment.</p>
        <a href="index.php" class="btn-primary">Apply Online</a>
    </section>

    <section id="about" class="section">
        <h2 class="section-title">About Daily Bread</h2>
        <p class="section-subtitle">Nurturing young minds since 2010</p>
        <div class="about-grid">
            <div class="about-text">
                <h3>Where Faith and Learning Grow Together</h3>
                <p>At Daily Bread Learning Center, we believe that all young learners should be respected, valued, and encouraged to investigate the world around them. We provide a private preschool education rich in experiential learning, intentional exploration, and meaningful social-emotional development.</p>
                <p>Our Christ-centered curriculum integrates academic excellence with Christian values, helping children develop a strong moral foundation while building essential skills for lifelong learning.</p>
            </div>
            <div class="about-image">
                <img src="images/logo.png" alt="Daily Bread Logo">
            </div>
        </div>
    </section>

    <section id="programs" class="section" style="background: #f5f6fa;">
        <h2 class="section-title">Our Programs</h2>
        <p class="section-subtitle">Developmentally appropriate curriculum for every stage</p>
        <div class="programs-grid">
            <div class="program-card">
                <h3>Nursery</h3>
                <p>Ages 3-4 years</p>
                <div class="price">PHP <?php echo number_format($nursery_fee, 0); ?></div>
                <p>Full school year including registration, tuition, and miscellaneous fees</p>
                <a href="index.php" class="btn-outline">Enroll Now</a>
            </div>
            <div class="program-card">
                <h3>Kindergarten 1</h3>
                <p>Ages 4-5 years</p>
                <div class="price">PHP <?php echo number_format($k1_fee, 0); ?></div>
                <p>Full school year including registration, tuition, and miscellaneous fees</p>
                <a href="index.php" class="btn-outline">Enroll Now</a>
            </div>
            <div class="program-card">
                <h3>Kindergarten 2</h3>
                <p>Ages 5-6 years</p>
                <div class="price">PHP <?php echo number_format($k2_fee, 0); ?></div>
                <p>Full school year including registration, tuition, and miscellaneous fees</p>
                <a href="index.php" class="btn-outline">Enroll Now</a>
            </div>
        </div>
    </section>

    <section id="portals" class="portals-section">
        <div class="portals-container">
            <h2 class="section-title">Portal Access</h2>
            <p class="section-subtitle">Secure access for staff and parents</p>
            
            <div class="portals-row">
                <a href="index.php" class="portal-btn enroll">
                    <h3>Student Enrollment</h3>
                    <p>New student registration form</p>
                </a>
                <a href="login.php?role=admin" class="portal-btn admin">
                    <h3>Admin Portal</h3>
                    <p>View all student records</p>
                </a>
                <a href="login.php?role=registrar" class="portal-btn registrar">
                    <h3>Registrar Portal</h3>
                    <p>Update student qualification status</p>
                </a>
            </div>
            
            <div class="portals-row">
                <a href="login.php?role=cashier" class="portal-btn cashier">
                    <h3>Cashier Portal</h3>
                    <p>Update payment status and transactions</p>
                </a>
                <a href="view_enrollees.php" class="portal-btn view">
                    <h3>Enrolled Students</h3>
                    <p>View list of enrolled students</p>
                </a>
                <a href="tuition_fees.php" class="portal-btn tuition">
                    <h3>Tuition and Fees</h3>
                    <p>View fee structure and payment schedules</p>
                </a>
            </div>
            
            <div class="portals-row">
                <a href="online_payment.php" class="portal-btn payment">
                    <h3>💳 Pay Online</h3>
                    <p>GCash, Bank Transfer, Over the Counter</p>
                </a>
                <a href="request_refund.php" class="portal-btn refund">
                    <h3>📄 Request Refund</h3>
                    <p>Submit refund request with letter</p>
                </a>
            </div>
        </div>
    </section>

    <section id="contact" class="section" style="background: #f5f6fa;">
        <h2 class="section-title">Contact Us</h2>
        <p class="section-subtitle">Visit us or get in touch</p>
        
        <div style="text-align: center; max-width: 500px; margin: 0 auto;">
            <img src="images/logo.png" alt="Logo" style="height: 60px; margin-bottom: 20px;">
            <h3 style="color: #2c3e50; margin-bottom: 20px;"><?php echo htmlspecialchars($school_name); ?></h3>
            <p><?php echo htmlspecialchars($school_address); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($school_phone); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($school_email); ?></p>
            <p><strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 4:00 PM</p>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <img src="images/logo.png" alt="Logo" style="height: 40px; margin-bottom: 15px;">
                <h4><?php echo htmlspecialchars($school_name); ?></h4>
                <p><?php echo htmlspecialchars($school_address); ?></p>
                <p><?php echo htmlspecialchars($school_phone); ?></p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <a href="index.php">Enrollment Form</a>
                <a href="view_enrollees.php">Enrolled Students</a>
                <a href="tuition_fees.php">Tuition and Fees</a>
                <a href="online_payment.php">Pay Online</a>
                <a href="request_refund.php">Request Refund</a>
            </div>
            <div class="footer-col">
                <h4>Staff Portals</h4>
                <a href="login.php?role=admin">Admin Portal</a>
                <a href="login.php?role=registrar">Registrar Portal</a>
                <a href="login.php?role=cashier">Cashier Portal</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($school_name); ?> — All rights reserved.</p>
        </div>
    </footer>
</body>
</html>