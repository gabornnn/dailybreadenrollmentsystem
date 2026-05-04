<?php
session_start();
require_once 'db_connection.php';
require_once 'includes_functions.php';

$error = '';
$success = '';
$student = null;
$show_form = false;
$submitted = false;

// File upload validation function
function validateRefundLetter($file) {
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'Please upload a refund request letter.'];
    }
    if ($file['error'] != UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error. Please try again.'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    
    if (!in_array($fileExt, $allowedExt)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only PDF, JPG, and PNG files are allowed.'];
    }
    if ($fileSize > $maxSize) {
        $maxSizeMB = $maxSize / 1024 / 1024;
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        return ['valid' => false, 'error' => "File too large. Maximum size is {$maxSizeMB}MB. Your file is {$fileSizeMB}MB."];
    }
    
    return ['valid' => true, 'error' => null];
}

if(isset($_POST['search_student'])) {
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

if(isset($_POST['submit_request']) && $student) {
    $enrollee_id = $student['enrollee_id'];
    $refund_amount = $_POST['refund_amount'];
    $refund_reason = trim($_POST['refund_reason']);
    
    // Validate file
    $letter_validation = validateRefundLetter($_FILES['refund_letter']);
    
    if(!$letter_validation['valid']) {
        $error = $letter_validation['error'];
    } elseif($refund_amount <= 0) {
        $error = "❌ Please enter a valid refund amount greater than zero.";
    } elseif(empty($refund_reason)) {
        $error = "❌ Please provide a reason for the refund.";
    } else {
        $target_dir = "uploads/refund_letters/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = pathinfo($_FILES['refund_letter']['name'], PATHINFO_EXTENSION);
        $letter_path = $target_dir . "refund_" . $enrollee_id . "_" . time() . "." . $file_extension;
        
        if(move_uploaded_file($_FILES['refund_letter']['tmp_name'], $letter_path)) {
            $stmt = $pdo->prepare("INSERT INTO refund_requests (enrollee_id, request_date, refund_amount, refund_reason, letter_path, status) VALUES (?, CURDATE(), ?, ?, ?, 'pending')");
            if($stmt->execute([$enrollee_id, $refund_amount, $refund_reason, $letter_path])) {
                $success = "✅ Refund request submitted successfully! Redirecting to homepage...";
                $submitted = true;
                header("refresh:3;url=welcome.php");
            } else {
                $error = "❌ Failed to submit refund request.";
                if(file_exists($letter_path)) unlink($letter_path);
            }
        } else {
            $error = "❌ Failed to upload file. Please check folder permissions.";
        }
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
        .content { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .form-group input[type="file"] { padding: 10px; border: 1px dashed #ddd; background: #fafafa; }
        .btn-submit { background: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-search { background: #3498db; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .info-box { background: #fef5e8; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #e74c3c; }
        .student-info { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .back-link { text-align: center; margin-top: 20px; }
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; }
        .note { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header"><h2>📄 Request Refund</h2></div>
    <div class="content">
        <?php if($success): ?>
            <div class="success"><strong>✓ <?php echo $success; ?></strong></div>
            <div class="back-link"><a href="welcome.php">Click here if not redirected</a></div>
        <?php elseif($show_form && $student): ?>
            <div class="student-info">
                <strong>📋 Student Information Verified</strong><br>
                Student ID: <?php echo $student['enrollee_id']; ?><br>
                Name: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                Program: <?php echo $student['program_level']; ?>
            </div>
            <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="enrollee_id" value="<?php echo $student['enrollee_id']; ?>">
                <div class="form-group"><label>Refund Amount (PHP) *</label><input type="number" name="refund_amount" step="0.01" required placeholder="Enter amount to refund"></div>
                <div class="form-group"><label>Reason for Refund *</label><textarea name="refund_reason" rows="4" required placeholder="Please explain why you are requesting a refund..."></textarea></div>
                <div class="form-group"><label>Upload Refund Request Letter *</label><input type="file" name="refund_letter" accept=".pdf,.jpg,.jpeg,.png" required><div class="note">Accepted formats: PDF, JPG, PNG (Max 5MB)</div></div>
                <button type="submit" name="submit_request" class="btn-submit">Submit Refund Request</button>
            </form>
            <div class="info-box"><strong>⚠️ Important Notes:</strong><br>• Refund requests require a written letter<br>• Your request will be reviewed by the Registrar<br>• Processing may take 3-5 business days</div>
        <?php elseif(!$show_form && !$submitted): ?>
            <div class="info-box" style="margin-bottom:20px;"><strong>📌 How to Request a Refund:</strong><br>1. Enter your Student ID and Last Name<br>2. Fill out the refund request form<br>3. Upload your refund request letter (PDF/JPG/PNG, Max 5MB)<br>4. Submit for review</div>
            <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST"><div class="form-group"><label>Student ID *</label><input type="number" name="student_id" required placeholder="Enter your Student ID"></div>
            <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" required placeholder="Enter your Last Name"></div>
            <button type="submit" name="search_student" class="btn-search">🔍 Verify Student</button></form>
        <?php endif; ?>
        <div class="back-link"><a href="welcome.php">← Back to Homepage</a></div>
    </div>
    <div class="footer"><p>© Daily Bread Learning Center Inc. — Refund Request System</p></div>
</div>
</body>
</html>
