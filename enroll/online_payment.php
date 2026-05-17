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

// Fetch enrolled students for autocomplete - ONLY students with 'Enrolled' status
$enrolled_students = [];
$stmt = $pdo->query("SELECT enrollee_id, first_name, last_name, program_level FROM enrollees WHERE enrollment_status = 'Enrolled' OR enrollment_status = 'Pending' ORDER BY last_name ASC");
$enrolled_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug - uncomment to see if students are fetched
// echo "<!-- Students found: " . count($enrolled_students) . " -->";

if($student_id && $student_id != 0) {
    $stmt = $pdo->prepare("SELECT * FROM enrollees WHERE enrollee_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle student selection from autocomplete
if(isset($_POST['select_student'])) {
    $student_id = $_POST['student_id'];
    $stmt = $pdo->prepare("SELECT * FROM enrollees WHERE enrollee_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// STRICT FILE VALIDATION FUNCTION
function validatePaymentProofStrict($file) {
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];
    
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => '❌ Please upload a payment proof/screenshot.'];
    }
    
    if ($file['error'] != UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
        ];
        return ['valid' => false, 'error' => '❌ Upload failed: ' . ($upload_errors[$file['error']] ?? 'Unknown error.')];
    }
    
    $fileName = $file['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileMime = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    if (!in_array($fileExt, $allowedExt)) {
        return ['valid' => false, 'error' => "❌ Invalid file type: .$fileExt\n\nOnly JPG, PNG, and PDF files are allowed."];
    }
    
    if (!in_array($fileMime, $allowedMime)) {
        return ['valid' => false, 'error' => "❌ Invalid file format.\n\nOnly JPG, PNG, and PDF files are allowed."];
    }
    
    if ($fileSize > $maxSize) {
        return ['valid' => false, 'error' => "❌ File too large: {$fileSizeMB}MB\n\nMaximum file size is 5MB. Please compress your file and try again."];
    }
    
    return ['valid' => true, 'error' => null, 'extension' => $fileExt, 'size' => $fileSizeMB];
}

// Handle payment submission
if(isset($_POST['submit_payment'])) {
    $student_id = $_POST['student_id'];
    $payment_amount = $_POST['payment_amount'];
    $payment_method = $_POST['payment_method'];
    $payment_reference = generateReferenceNumber();
    $notes = $_POST['notes'];
    
    // Validate that a student was selected
    if(!$student_id || $student_id == 0) {
        $error = "❌ Please select a valid student from the suggestions.";
    } else {
        $proof_validation = validatePaymentProofStrict($_FILES['payment_proof']);
        
        if (!$proof_validation['valid']) {
            $error = $proof_validation['error'];
        } else {
            $target_dir = "uploads/payment_proofs/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = $proof_validation['extension'];
            $screenshot_path = $target_dir . "payment_" . $student_id . "_" . time() . "." . $file_extension;
            
            if(move_uploaded_file($_FILES['payment_proof']['tmp_name'], $screenshot_path)) {
                $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, payment_method, payment_reference, notes, processed_by, payment_verified, receipt_path) VALUES (?, CURDATE(), ?, 'Online Payment', ?, ?, ?, 'System', 0, ?)");
                if($stmt->execute([$student_id, $payment_amount, $payment_method, $payment_reference, $notes, $screenshot_path])) {
                    $success = "✅ Payment reference generated! Please complete the payment using the instructions below.";
                    $payment_completed = true;
                } else {
                    $error = "❌ Failed to process payment. Please try again.";
                }
            } else {
                $error = "❌ Failed to upload payment proof. Please check folder permissions.";
            }
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
    <link rel="icon" type="image/png" href="images/logo.png">;
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
        .form-group input[type="file"] { padding: 8px; }
        .btn-pay { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        .btn-pay:hover { background: #219a52; }
        
        .method-tab { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .method-tab a { padding: 10px 20px; text-decoration: none; color: #666; }
        .method-tab a.active { color: #27ae60; border-bottom: 2px solid #27ae60; margin-bottom: -2px; }
        
        .instruction-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .instruction-box h4 { color: #2c3e50; margin-bottom: 10px; }
        .instruction-box p { margin: 5px 0; }
        .qr-code { text-align: center; padding: 20px; background: white; border: 1px solid #ddd; border-radius: 10px; margin: 15px 0; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .success h3 { margin-bottom: 10px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; white-space: pre-line; }
        
        .payment-completed { text-align: center; padding: 40px; background: white; border-radius: 10px; margin-top: 20px; }
        .payment-completed .checkmark { font-size: 80px; color: #27ae60; }
        .payment-completed h2 { color: #27ae60; margin: 20px 0; }
        .payment-completed p { margin: 10px 0; color: #666; }
        .btn-home { background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
        
        .file-info { font-size: 12px; color: #666; margin-top: 5px; }
        .file-requirements { background: #f0f0f0; padding: 8px; border-radius: 5px; margin-bottom: 10px; font-size: 12px; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; border-radius: 10px; }
        
        /* Autocomplete Styles */
        .autocomplete-container { position: relative; width: 100%; }
        .autocomplete-input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 14px;
        }
        .autocomplete-input:focus { outline: none; border-color: #27ae60; }
        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .autocomplete-list div {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .autocomplete-list div:hover {
            background-color: #e8f4fd;
        }
        .autocomplete-list div strong {
            color: #2c3e50;
        }
        .autocomplete-list div small {
            color: #7f8c8d;
            font-size: 11px;
        }
        .selected-student-info {
            background: #e8f4fd;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 10px 0;
            display: none;
            border-left: 4px solid #27ae60;
        }
        
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
        <a href="welcome.php" class="home-btn">🏠 Back to Home</a>
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
        <div class="error"><?php echo nl2br(htmlspecialchars($error)); ?></div>
    <?php endif; ?>
    
    <?php if(!$payment_completed && !$success): ?>
    <div class="payment-container">
        <div class="payment-form">
            <div class="method-tab">
                <a href="?method=gcash" class="<?php echo $payment_method == 'gcash' ? 'active' : ''; ?>">GCash</a>
                <a href="?method=bank" class="<?php echo $payment_method == 'bank' ? 'active' : ''; ?>">Bank Transfer</a>
                <a href="?method=cash" class="<?php echo $payment_method == 'cash' ? 'active' : ''; ?>">Over the Counter</a>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="paymentForm">
                <input type="hidden" name="student_id" id="selected_student_id" value="<?php echo $student_id; ?>">
                <input type="hidden" name="payment_method" value="<?php echo $payment_method; ?>">
                
                <div class="form-group">
                    <label>Search Student Name *</label>
                    <div class="autocomplete-container">
                        <input type="text" id="student_name_input" class="autocomplete-input" placeholder="Type student name (e.g., Juan, Maria, Dela Cruz)" autocomplete="off" value="<?php echo isset($student) ? htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) : ''; ?>">
                        <div id="autocomplete-list" class="autocomplete-list"></div>
                    </div>
                    <small style="color: #666; font-size: 12px;">Start typing student's first or last name</small>
                </div>
                
                <div id="selectedStudentInfo" class="selected-student-info" <?php echo isset($student) ? 'style="display:block;"' : ''; ?>>
                    <?php if(isset($student)): ?>
                        <strong>✓ Selected Student:</strong><br>
                        ID: <?php echo $student['enrollee_id']; ?><br>
                        Name: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                        Program: <?php echo $student['program_level']; ?>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Payment Amount (PHP) *</label>
                    <input type="number" name="payment_amount" step="0.01" required placeholder="Enter amount to pay">
                </div>
                
                <div class="form-group">
                    <label>Upload Payment Proof/Screenshot *</label>
                    <div class="file-requirements">
                        <strong>📋 File Requirements:</strong><br>
                        • Allowed formats: JPG, PNG, PDF<br>
                        • Maximum size: 5MB
                    </div>
                    <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required onchange="validateFileSize(this)">
                    <div id="fileInfo" class="file-info"></div>
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
                    <div class="qr-code">
                        <p>Scan QR Code to Pay</p>
                        <div style="width: 150px; height: 150px; background: #eee; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                            [GCash QR Code]
                        </div>
                    </div>
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
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure Online Payment | For concerns, call 0923-4701532</p>
    </div>
</div>

<script>
// Student data for autocomplete - PHP data embedded
const students = <?php echo json_encode($enrolled_students); ?>;

console.log("Students loaded:", students.length); // Debug - shows in console

const studentInput = document.getElementById('student_name_input');
const autocompleteList = document.getElementById('autocomplete-list');
const selectedStudentId = document.getElementById('selected_student_id');
const selectedStudentInfo = document.getElementById('selectedStudentInfo');

function showSuggestions() {
    const inputValue = studentInput.value.toLowerCase().trim();
    autocompleteList.innerHTML = '';
    autocompleteList.style.display = 'none';
    
    if (inputValue.length < 1) return;
    
    const matches = students.filter(student => 
        student.first_name.toLowerCase().includes(inputValue) || 
        student.last_name.toLowerCase().includes(inputValue) ||
        (student.first_name + ' ' + student.last_name).toLowerCase().includes(inputValue)
    );
    
    console.log("Matches found:", matches.length); // Debug
    
    if (matches.length > 0) {
        autocompleteList.style.display = 'block';
        matches.forEach(match => {
            const div = document.createElement('div');
            div.innerHTML = `<strong>${match.first_name} ${match.last_name}</strong><br>
                            <small>ID: ${match.enrollee_id} | Program: ${match.program_level}</small>`;
            div.onclick = () => selectStudent(match);
            autocompleteList.appendChild(div);
        });
    }
}

function selectStudent(student) {
    studentInput.value = student.first_name + ' ' + student.last_name;
    selectedStudentId.value = student.enrollee_id;
    selectedStudentInfo.innerHTML = `
        <strong>✓ Selected Student:</strong><br>
        ID: ${student.enrollee_id}<br>
        Name: ${student.first_name} ${student.last_name}<br>
        Program: ${student.program_level}
    `;
    selectedStudentInfo.style.display = 'block';
    autocompleteList.style.display = 'none';
}

// Event listeners
studentInput.addEventListener('input', showSuggestions);
studentInput.addEventListener('focus', function() {
    if (studentInput.value.length > 0) {
        showSuggestions();
    }
});

// Close autocomplete when clicking outside
document.addEventListener('click', function(e) {
    if (e.target !== studentInput && !autocompleteList.contains(e.target)) {
        autocompleteList.style.display = 'none';
    }
});

function validateFileSize(input) {
    const maxSizeMB = 5;
    const maxSizeBytes = maxSizeMB * 1024 * 1024;
    const file = input.files[0];
    const fileInfo = document.getElementById('fileInfo');
    
    if (file) {
        const fileSizeMB = file.size / 1024 / 1024;
        const fileExt = file.name.split('.').pop().toLowerCase();
        const allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!allowedExt.includes(fileExt)) {
            alert('❌ Invalid file type: .' + fileExt + '\n\nOnly JPG, PNG, and PDF files are allowed.');
            input.value = '';
            fileInfo.innerHTML = '';
            return false;
        }
        
        if (file.size > maxSizeBytes) {
            alert('❌ File too large: ' + fileSizeMB.toFixed(2) + 'MB\n\nMaximum file size is ' + maxSizeMB + 'MB. Please compress your file and try again.');
            input.value = '';
            fileInfo.innerHTML = '';
            return false;
        }
        
        fileInfo.innerHTML = '✓ File selected: ' + file.name + ' (' + fileSizeMB.toFixed(2) + ' MB)';
        fileInfo.style.color = '#27ae60';
    }
    return true;
}

// Form validation before submit
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    if (!selectedStudentId.value || selectedStudentId.value == 0) {
        alert('❌ Please select a student from the suggestions.');
        e.preventDefault();
        return false;
    }
});

// If there's already a student ID pre-selected, show the info
<?php if(isset($student) && $student_id && $student_id != 0): ?>
// Already have student info displayed
<?php endif; ?>
</script>
</body>
</html>