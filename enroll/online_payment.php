<?php
session_start();
require_once 'db_connection.php';
require_once 'includes_functions.php';

$error = '';
$success = '';
$payment_method = isset($_GET['method']) ? $_GET['method'] : 'gcash';
$payment_completed = isset($_GET['completed']) ? $_GET['completed'] : false;

// Get student info if logged in as student, or from URL
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

if($student_id) {
    $stmt = $pdo->prepare("SELECT * FROM enrollees WHERE enrollee_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle payment submission with screenshot
if(isset($_POST['submit_payment'])) {
    $student_id = $_POST['student_id'];
    $payment_amount = $_POST['payment_amount'];
    $payment_method = $_POST['payment_method'];
    $payment_reference = generateReferenceNumber();
    $notes = $_POST['notes'];
    
    // Allowed file types and size
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $screenshot_path = '';
    if(isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == 0) {
        $file_type = mime_content_type($_FILES['payment_proof']['tmp_name']);
        $file_size = $_FILES['payment_proof']['size'];
        
        if(!in_array($file_type, $allowed_types)) {
            $error = "Only JPG, PNG, and PDF files are allowed!";
        } elseif($file_size > $max_size) {
            $error = "File size must be less than 5MB!";
        } else {
            $target_dir = "uploads/payment_proofs/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $screenshot_path = $target_dir . "payment_" . $student_id . "_" . time() . "." . $file_extension;
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $screenshot_path);
        }
    } else {
        $error = "Please upload a payment proof/screenshot.";
    }
    
    if(empty($error)) {
        // Insert pending payment transaction
        $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, payment_method, payment_reference, notes, processed_by, payment_verified, receipt_path) VALUES (?, CURDATE(), ?, 'Online Payment', ?, ?, ?, 'System', 0, ?)");
        
        if($stmt->execute([$student_id, $payment_amount, $payment_method, $payment_reference, $notes, $screenshot_path])) {
            $success = "Payment reference generated! Please complete the payment using the instructions below.";
            $payment_completed = true;
        } else {
            $error = "Failed to process payment. Please try again.";
        }
    }
}

// Get system settings
$gcash_number = getSetting($pdo, 'gcash_number') ?: '0923-4701532';
$gcash_name = getSetting($pdo, 'gcash_name') ?: 'Daily Bread Learning Center';
$bank_name = getSetting($pdo, 'bank_name') ?: 'Bank of the Philippine Islands (BPI)';
$bank_account = getSetting($pdo, 'bank_account') ?: '1234-5678-90';
$bank_account_name = getSetting($pdo, 'bank_account_name') ?: 'Daily Bread Learning Center Inc.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Payment - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header h2 { margin: 0; }
        .home-btn { background: #3498db; color: white; padding: 8px 20px; text-decoration: none; border-radius: 5px; }
        .home-btn:hover { background: #2980b9; }
        
        .payment-container { display: flex; gap: 25px; flex-wrap: wrap; margin-top: 20px; }
        .payment-form { flex: 1; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .payment-instructions { flex: 1; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group input[type="file"] { padding: 5px; }
        .btn-pay { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-pay:hover { background: #219a52; }
        
        .method-tab { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .method-tab a { padding: 10px 20px; text-decoration: none; color: #666; }
        .method-tab a.active { color: #27ae60; border-bottom: 2px solid #27ae60; margin-bottom: -2px; }
        
        .instruction-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .instruction-box h4 { color: #2c3e50; margin-bottom: 10px; }
        .instruction-box p { margin: 5px 0; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .success h3 { margin-bottom: 10px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        
        .payment-completed { text-align: center; padding: 40px; background: white; border-radius: 10px; margin-top: 20px; }
        .payment-completed .checkmark { font-size: 80px; color: #27ae60; }
        .payment-completed h2 { color: #27ae60; margin: 20px 0; }
        .payment-completed p { margin: 10px 0; color: #666; }
        .btn-home { background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
        .btn-home:hover { background: #2980b9; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; border-radius: 10px; }
        
        @media (max-width: 768px) {
            .payment-container { flex-direction: column; }
            .method-tab { justify-content: center; }
            .header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>💳 Online Payment Portal</h2>
        <a href="welcome.php" class="home-btn">Back to Home</a>
    </div>
    
    <?php if($payment_completed): ?>
        <div class="payment-completed">
            <div class="checkmark">✓</div>
            <h2>Payment Reference Generated!</h2>
            <p>Your payment reference number: <strong><?php echo $payment_reference ?? 'N/A'; ?></strong></p>
            <p>Please complete your payment using the instructions below.</p>
            <p>After payment, our cashier will verify your transaction within 24 hours.</p>
            <a href="welcome.php" class="btn-home">Return to Homepage</a>
        </div>
    <?php elseif($success): ?>
        <div class="success">
            <h3>✓ Payment Reference Generated Successfully!</h3>
            <p>Please follow the payment instructions below to complete your payment.</p>
            <p>Our cashier will verify your payment within 24 hours.</p>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="error">✗ <?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(!$payment_completed && !$success): ?>
    <div class="payment-container">
        <div class="payment-form">
            <div class="method-tab">
                <a href="?method=gcash" class="<?php echo $payment_method == 'gcash' ? 'active' : ''; ?>">GCash</a>
                <a href="?method=bank" class="<?php echo $payment_method == 'bank' ? 'active' : ''; ?>">Bank Transfer</a>
                <a href="?method=cash" class="<?php echo $payment_method == 'cash' ? 'active' : ''; ?>">Over the Counter</a>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                <input type="hidden" name="payment_method" value="<?php echo $payment_method; ?>">
                
                <div class="form-group">
                    <label>Student Name </label>
                    <input type="text" name="student_name" placeholder="Enter student name">
                </div>
                
                <div class="form-group">
                    <label>Payment Amount (PHP) *</label>
                    <input type="number" name="payment_amount" step="0.01" required placeholder="Enter amount to pay">
                </div>
                
                <div class="form-group">
                    <label>Upload Payment Proof/Screenshot *</label>
                    <input type="file" name="payment_proof" accept="image/jpeg,image/png,application/pdf" required>
                    <small style="color: #666;">Accepted formats: JPG, PNG, PDF (Max 5MB)</small>
                </div>
                
                <div class="form-group">
                    <label>Reference Number (Optional)</label>
                    <input type="text" name="notes" placeholder="Enter GCash or bank reference number">
                </div>
                
                <button type="submit" name="submit_payment" class="btn-pay">Generate Payment Reference</button>
            </form>
        </div>
        
        <div class="payment-instructions">
            <h3>Payment Instructions</h3>
            
            <?php if($payment_method == 'gcash'): ?>
                <div class="instruction-box">
                    <h4>📱 GCash Payment</h4>
                    <p><strong>GCash Number:</strong> <?php echo $gcash_number; ?></p>
                    <p><strong>Account Name:</strong> <?php echo $gcash_name; ?></p>
                    <p><strong>Steps:</strong></p>
                    <ol style="margin-left: 20px; margin-top: 10px;">
                        <li>Open GCash App</li>
                        <li>Click "Send Money" &gt; "Express Send"</li>
                        <li>Enter the GCash number: <strong><?php echo $gcash_number; ?></strong></li>
                        <li>Enter the exact amount</li>
                        <li>Enter your email address as reference</li>
                        <li>Take a screenshot of the transaction confirmation</li>
                        <li>Upload the screenshot above and submit</li>
                    </ol>
                </div>
            <?php elseif($payment_method == 'bank'): ?>
                <div class="instruction-box">
                    <h4>🏦 Bank Transfer</h4>
                    <p><strong>Bank:</strong> <?php echo $bank_name; ?></p>
                    <p><strong>Account Number:</strong> <?php echo $bank_account; ?></p>
                    <p><strong>Account Name:</strong> <?php echo $bank_account_name; ?></p>
                    <p><strong>Steps:</strong></p>
                    <ol style="margin-left: 20px; margin-top: 10px;">
                        <li>Log in to your online banking app</li>
                        <li>Transfer to the account above</li>
                        <li>Use your child's name as reference</li>
                        <li>Save the transaction reference number</li>
                        <li>Take a screenshot of the transaction confirmation</li>
                        <li>Upload the screenshot above and submit</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="instruction-box">
                    <h4>🏢 Over the Counter Payment</h4>
                    <p><strong>Location:</strong> Daily Bread Learning Center Inc.</p>
                    <p><strong>Address:</strong> Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City</p>
                    <p><strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 4:00 PM</p>
                    <p><strong>Steps:</strong></p>
                    <ol style="margin-left: 20px; margin-top: 10px;">
                        <li>Visit our school office</li>
                        <li>Provide your child's name</li>
                        <li>Pay the amount to the cashier</li>
                        <li>Get your official receipt</li>
                        <li>Your payment will be updated immediately</li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div class="instruction-box" style="background: #fff3cd; margin-top: 15px;">
                <h4>⚠️ Important Notes</h4>
                <p>• Only JPG, PNG, and PDF files are accepted for payment proof (Max 5MB)</p>
                <p>• Payment will be verified by our cashier within 24 hours</p>
                <p>• Please keep your reference number for verification</p>
                <p>• For concerns, contact us at 0923-4701532</p>
                <p>• Once verified, your payment status will be updated</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure Online Payment | For concerns, call 0923-4701532</p>
    </div>
</div>
</body>
</html>