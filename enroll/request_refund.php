<?php
session_start();
require_once 'db_connection.php';

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';
$student = null;
$show_form = false;

// Handle student verification
if(isset($_POST['verify_student'])) {
    $student_id = $_POST['student_id'];
    $last_name = trim($_POST['last_name']);
    
    $stmt = $pdo->prepare("SELECT * FROM enrollees WHERE enrollee_id = ? AND last_name = ?");
    $stmt->execute([$student_id, $last_name]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($student) {
        $show_form = true;
    } else {
        $error = "❌ Student not found. Please check your Student ID and Last Name.";
    }
}

// Handle refund submission
if(isset($_POST['submit_refund'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $refund_amount = $_POST['refund_amount'];
    $refund_reason = trim($_POST['refund_reason']);
    $student_name = $_POST['student_name'];
    
    // Validate amount
    if($refund_amount <= 0) {
        $error = "❌ Please enter a valid refund amount.";
    } elseif(empty($refund_reason)) {
        $error = "❌ Please provide a reason for the refund.";
    } elseif(!isset($_FILES['refund_letter']) || $_FILES['refund_letter']['error'] != 0) {
        $error = "❌ Please upload a refund request letter.";
    } else {
        // Handle file upload
        $file = $_FILES['refund_letter'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if(!in_array($file_ext, $allowed)) {
            $error = "❌ Invalid file type. Only PDF, JPG, and PNG files are allowed.";
        } elseif($file['size'] > 5 * 1024 * 1024) {
            $error = "❌ File is too large. Maximum size is 5MB.";
        } else {
            // Create directory
            $target_dir = "uploads/refund_letters/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Save file
            $filename = "refund_" . $enrollee_id . "_" . time() . "." . $file_ext;
            $filepath = $target_dir . $filename;
            
            if(move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database
                try {
                    $stmt = $pdo->prepare("INSERT INTO refund_requests (enrollee_id, request_date, refund_amount, refund_reason, letter_path, status) VALUES (?, CURDATE(), ?, ?, ?, 'pending')");
                    if($stmt->execute([$enrollee_id, $refund_amount, $refund_reason, $filepath])) {
                        $success = "✅ Refund request submitted successfully! Redirecting to homepage...";
                        // Redirect after 3 seconds
                        header("refresh:3;url=welcome.php");
                    } else {
                        $error = "❌ Database error: " . implode(", ", $stmt->errorInfo());
                    }
                } catch(PDOException $e) {
                    $error = "❌ Database error: " . $e->getMessage();
                }
            } else {
                $error = "❌ Failed to upload file. Please check folder permissions.";
            }
        }
    }
    
    // If error occurred, show form again with the student data
    if($error) {
        $student = $pdo->prepare("SELECT * FROM enrollees WHERE enrollee_id = ?")->execute([$enrollee_id]);
        $show_form = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Refund - Daily Bread Learning Center</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #e74c3c; color: white; padding: 25px; text-align: center; }
        .header h2 { margin: 0; }
        .content { padding: 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #e74c3c; }
        .form-group input[type="file"] { padding: 10px; }
        
        .btn-submit { background: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-submit:hover { background: #c0392b; }
        .btn-verify { background: #3498db; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-verify:hover { background: #2980b9; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745; text-align: center; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545; text-align: center; }
        
        .info-box { background: #fef5e8; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #e74c3c; }
        .student-info { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #e74c3c; text-decoration: none; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; }
        .note { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📄 Request Refund</h2>
    </div>
    
    <div class="content">
        <?php if($success): ?>
            <div class="success">
                <strong>✓ <?php echo $success; ?></strong>
                <p style="margin-top: 10px; font-size: 13px;">You will be redirected to the homepage in 3 seconds...</p>
            </div>
            <div class="back-link">
                <a href="welcome.php">Click here if not redirected</a>
            </div>
            
        <?php elseif($show_form && $student): ?>
            <!-- Student Info Display -->
            <div class="student-info">
                <strong>📋 Student Information</strong><br>
                Student ID: <?php echo $student['enrollee_id']; ?><br>
                Name: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                Program: <?php echo $student['program_level']; ?>
            </div>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                
                <div class="form-group">
                    <label>Refund Amount (PHP) *</label>
                    <input type="number" name="refund_amount" step="0.01" required placeholder="Enter amount to refund">
                    <div class="note">Enter the amount you want to refund.</div>
                </div>
                
                <div class="form-group">
                    <label>Reason for Refund *</label>
                    <textarea name="refund_reason" rows="4" required placeholder="Please explain why you are requesting a refund..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Upload Refund Request Letter *</label>
                    <input type="file" name="refund_letter" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="note">Accepted formats: PDF, JPG, PNG (Max 5MB)</div>
                </div>
                
                <button type="submit" name="submit_refund" class="btn-submit">Submit Refund Request</button>
            </form>
            
            <div class="info-box">
                <strong>⚠️ Important Notes:</strong><br>
                • Refund requests require a written letter explaining the reason<br>
                • Your request will be reviewed by the Registrar<br>
                • Processing may take 3-5 business days
            </div>
            
        <?php else: ?>
            <!-- Student Verification Form -->
            <div class="info-box" style="margin-bottom: 20px;">
                <strong>📌 How to Request a Refund:</strong><br>
                1. Enter your Student ID and Last Name below<br>
                2. Fill out the refund request form<br>
                3. Upload your refund request letter<br>
                4. Submit for review
            </div>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Student ID *</label>
                    <input type="number" name="student_id" required placeholder="Enter your Student ID">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required placeholder="Enter your Last Name">
                </div>
                <button type="submit" name="verify_student" class="btn-verify">🔍 Verify Student</button>
            </form>
            
            <div class="back-link">
                <a href="welcome.php">← Back to Homepage</a>
            </div>
            
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Refund Request System</p>
    </div>
</div>
</body>
</html>