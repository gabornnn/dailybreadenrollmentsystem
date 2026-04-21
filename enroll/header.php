<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Bread Learning Center</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="shortcut icon" href="images/logo.png">
    <style>
        /* Global Header Styles */
        .site-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .site-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .site-logo img {
            height: 45px;
            width: auto;
        }
        .site-logo h1 {
            font-size: 18px;
            color: #2c3e50;
        }
        .site-logo p {
            font-size: 10px;
            color: #7f8c8d;
        }
        .site-nav {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        .site-nav a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            padding: 8px 0;
        }
        .site-nav a:hover, .site-nav a.active {
            color: #e74c3c;
            border-bottom: 2px solid #e74c3c;
        }
        @media (max-width: 768px) {
            .site-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .site-nav {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="site-logo">
        <img src="images/logo.png" alt="Daily Bread Learning Center Logo">
        <div>
            <h1>DAILY BREAD LEARNING CENTER</h1>
            <p>Preschool Department - Academy Year 2026-2027</p>
        </div>
    </div>
    <nav class="site-nav">
        <a href="welcome.php">Home</a>
        <a href="index.php">Enrollment</a>
        <a href="view_enrollees.php">Students</a>
        <a href="tuition_fees.php">Tuition</a>
        <?php if(isset($_SESSION['role'])): ?>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="admin_dashboard.php">Dashboard</a>
            <?php elseif($_SESSION['role'] == 'registrar'): ?>
                <a href="registrar_dashboard.php">Dashboard</a>
            <?php elseif($_SESSION['role'] == 'cashier'): ?>
                <a href="cashier_dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="welcome.php#portals">Staff Login</a>
        <?php endif; ?>
    </nav>
</header>