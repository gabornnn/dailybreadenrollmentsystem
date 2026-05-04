<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connection.php';
require_once 'includes_functions.php';

// Get tuition fees from system settings
$nursery_fee = getSetting($pdo, 'enrollment_fee_nursery') ?: 17500;
$k1_fee = getSetting($pdo, 'enrollment_fee_k1') ?: 18300;
$k2_fee = getSetting($pdo, 'enrollment_fee_k2') ?: 18300;

// Age requirements for each program
$age_requirements = [
    'NURSERY' => ['min' => 3, 'max' => 4, 'message' => 'Nursery program is for children aged 3-4 years old.'],
    'KINDERGARTEN 1' => ['min' => 4, 'max' => 5, 'message' => 'Kindergarten 1 program is for children aged 4-5 years old.'],
    'KINDERGARTEN 2' => ['min' => 5, 'max' => 6, 'message' => 'Kindergarten 2 program is for children aged 5-6 years old.']
];

$success = '';
$error = '';
$age_error = '';

// File upload validation function
function validateFileUpload($file, $maxSize = 5 * 1024 * 1024, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    if ($file['error'] != UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'];
    }
    if ($fileSize > $maxSize) {
        $maxSizeMB = $maxSize / 1024 / 1024;
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        return ['valid' => false, 'error' => "File too large. Maximum size is {$maxSizeMB}MB. Your file is {$fileSizeMB}MB."];
    }
    
    return ['valid' => true, 'error' => null];
}

if(isset($_POST['enroll_student'])) {
    // Get form data
    $student_type = $_POST['student_type'];
    $program_level = $_POST['program_level'];
    $payment_plan = $_POST['payment_plan'];
    $birth_date = $_POST['birth_date'];
    
    // AGE VALIDATION
    $age = calculateAge($birth_date);
    $min_age = $age_requirements[$program_level]['min'];
    $max_age = $age_requirements[$program_level]['max'];
    
    if($age < $min_age || $age > $max_age) {
        $age_error = "❌ " . $age_requirements[$program_level]['message'] . "<br>Student's age: $age years old. Required: $min_age - $max_age years old.";
    } else {
        // Set payment amounts
        $payment_amounts = [
            'NURSERY' => [
                'Cash (Full)' => $nursery_fee,
                'Semi Annual' => round($nursery_fee * 0.5),
                'Quarterly' => round($nursery_fee * 0.35),
                'Monthly' => round($nursery_fee * 0.25)
            ],
            'KINDERGARTEN 1' => [
                'Cash (Full)' => $k1_fee,
                'Semi Annual' => round($k1_fee * 0.5),
                'Quarterly' => round($k1_fee * 0.35),
                'Monthly' => round($k1_fee * 0.25)
            ],
            'KINDERGARTEN 2' => [
                'Cash (Full)' => $k2_fee,
                'Semi Annual' => round($k2_fee * 0.5),
                'Quarterly' => round($k2_fee * 0.35),
                'Monthly' => round($k2_fee * 0.25)
            ]
        ];
        
        $payment_amount = $payment_amounts[$program_level][$payment_plan];
        
        $last_name = $_POST['last_name'];
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $nickname = $_POST['nickname'];
        $place_of_birth = $_POST['place_of_birth'];
        $address = $_POST['address'];
        $mother_name = $_POST['mother_name'];
        $mother_phone = $_POST['mother_phone'];
        $mother_employer = $_POST['mother_employer'];
        $father_name = $_POST['father_name'];
        $father_phone = $_POST['father_phone'];
        $father_employer = $_POST['father_employer'];
        $emergency_name = $_POST['emergency_name'];
        $emergency_phone = $_POST['emergency_phone'];
        $emergency_relationship = $_POST['emergency_relationship'];
        $authorized_pickup = $_POST['authorized_pickup'];
        $doctor_name = $_POST['doctor_name'];
        $doctor_phone = $_POST['doctor_phone'];
        $medical_problems = $_POST['medical_problems'];
        $allergies = $_POST['allergies'];
        $additional_info = $_POST['additional_info'];
        $parent_signature = $_POST['parent_signature'];
        $date_signed = $_POST['date_signed'];
        
        $siblings = isset($_POST['siblings']) ? $_POST['siblings'] : '';
        
        // Validate file uploads
        $upload_errors = [];
        
        // Validate Birth Certificate
        $birth_cert_validation = validateFileUpload($_FILES['birth_certificate']);
        if (!$birth_cert_validation['valid']) {
            $upload_errors[] = "Birth Certificate: " . $birth_cert_validation['error'];
        }
        
        // Validate ID Picture
        $id_picture_validation = validateFileUpload($_FILES['id_picture'], 2 * 1024 * 1024, ['jpg', 'jpeg', 'png']);
        if (!$id_picture_validation['valid']) {
            $upload_errors[] = "ID Picture: " . $id_picture_validation['error'];
        }
        
        // Validate Report Card (if uploaded)
        if ($_FILES['report_card']['error'] != UPLOAD_ERR_NO_FILE) {
            $report_card_validation = validateFileUpload($_FILES['report_card']);
            if (!$report_card_validation['valid']) {
                $upload_errors[] = "Report Card: " . $report_card_validation['error'];
            }
        }
        
        // Validate Proof of Certification for Kindergarten 2
        if($program_level == 'KINDERGARTEN 2') {
            if ($_FILES['proof_certification']['error'] == UPLOAD_ERR_NO_FILE) {
                $upload_errors[] = "Proof of Certification is required for Kindergarten 2 applicants.";
            } else {
                $proof_cert_validation = validateFileUpload($_FILES['proof_certification']);
                if (!$proof_cert_validation['valid']) {
                    $upload_errors[] = "Proof of Certification: " . $proof_cert_validation['error'];
                }
            }
        }
        
        if (!empty($upload_errors)) {
            $error = implode("<br>", $upload_errors);
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO enrollees (
                    student_type, program_level, payment_plan, payment_amount, 
                    last_name, first_name, middle_name, nickname, 
                    birth_date, place_of_birth, address,
                    enrollment_status, qualification_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', NOW())");
                
                $stmt->execute([
                    $student_type, $program_level, $payment_plan, $payment_amount,
                    $last_name, $first_name, $middle_name, $nickname,
                    $birth_date, $place_of_birth, $address
                ]);
                
                $enrollee_id = $pdo->lastInsertId();
                
                // Insert mother info
                $stmt = $pdo->prepare("INSERT INTO mother_info (enrollee_id, full_name, contact_number, occupation) VALUES (?, ?, ?, ?)");
                $stmt->execute([$enrollee_id, $mother_name, $mother_phone, $mother_employer]);
                
                // Insert father info
                $stmt = $pdo->prepare("INSERT INTO father_info (enrollee_id, full_name, contact_number, occupation) VALUES (?, ?, ?, ?)");
                $stmt->execute([$enrollee_id, $father_name, $father_phone, $father_employer]);
                
                // Insert siblings
                if(!empty($siblings)) {
                    $sibling_list = explode("\n", $siblings);
                    foreach($sibling_list as $sibling) {
                        if(trim($sibling) != '') {
                            preg_match('/(.*?)\s*\((\d{4}-\d{2}-\d{2})\)/', $sibling, $matches);
                            if($matches) {
                                $stmt = $pdo->prepare("INSERT INTO siblings (enrollee_id, sibling_name, sibling_birth_date) VALUES (?, ?, ?)");
                                $stmt->execute([$enrollee_id, trim($matches[1]), $matches[2]]);
                            }
                        }
                    }
                }
                
                // Insert emergency consent
                $stmt = $pdo->prepare("INSERT INTO emergency_consent (enrollee_id, parent_guardian_signature, date_signed) VALUES (?, ?, ?)");
                $stmt->execute([$enrollee_id, $parent_signature, $date_signed]);
                
                // Create uploads directory
                if(!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                // Handle file uploads
                $birth_cert_path = '';
                $id_picture_path = '';
                $report_card_path = '';
                $proof_certification_path = '';
                
                // Upload Birth Certificate
                $target_dir = "uploads/";
                $file_extension = pathinfo($_FILES['birth_certificate']['name'], PATHINFO_EXTENSION);
                $birth_cert_path = $target_dir . "birth_cert_" . $enrollee_id . "_" . time() . "." . $file_extension;
                move_uploaded_file($_FILES['birth_certificate']['tmp_name'], $birth_cert_path);
                
                // Upload ID Picture
                $file_extension = pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION);
                $id_picture_path = $target_dir . "id_picture_" . $enrollee_id . "_" . time() . "." . $file_extension;
                move_uploaded_file($_FILES['id_picture']['tmp_name'], $id_picture_path);
                
                // Upload Report Card
                if ($_FILES['report_card']['error'] != UPLOAD_ERR_NO_FILE) {
                    $file_extension = pathinfo($_FILES['report_card']['name'], PATHINFO_EXTENSION);
                    $report_card_path = $target_dir . "report_card_" . $enrollee_id . "_" . time() . "." . $file_extension;
                    move_uploaded_file($_FILES['report_card']['tmp_name'], $report_card_path);
                }
                
                // Upload Proof of Certification
                if ($program_level == 'KINDERGARTEN 2' && $_FILES['proof_certification']['error'] != UPLOAD_ERR_NO_FILE) {
                    $file_extension = pathinfo($_FILES['proof_certification']['name'], PATHINFO_EXTENSION);
                    $proof_certification_path = $target_dir . "proof_cert_" . $enrollee_id . "_" . time() . "." . $file_extension;
                    move_uploaded_file($_FILES['proof_certification']['tmp_name'], $proof_certification_path);
                }
                
                // Insert documents
                $stmt = $pdo->prepare("INSERT INTO documents (enrollee_id, birth_certificate_path, id_picture_path, report_card_path, proof_certification_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$enrollee_id, $birth_cert_path, $id_picture_path, $report_card_path, $proof_certification_path]);
                
                $pdo->commit();
                
                $success = "Application Submitted Successfully! Your application is pending review. Application ID: " . $enrollee_id;
                
                $_POST = array();
                
            } catch(Exception $e) {
                $pdo->rollBack();
                $error = "Application failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Form - Daily Bread Learning Center</title>
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
        .section { background: white; padding: 20px; margin-bottom: 30px; border-radius: 10px; border-left: 4px solid #27ae60; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section h2 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .radio-group { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .radio-group label { display: flex; align-items: center; gap: 5px; font-weight: normal; }
        .radio-group input { width: auto; }
        .file-upload { border: 2px dashed #ddd; padding: 20px; text-align: center; border-radius: 10px; background: #f9f9f9; }
        .file-upload input { margin-top: 10px; }
        .file-info { font-size: 12px; color: #999; margin-top: 5px; }
        .submit-btn { background: #27ae60; color: white; padding: 15px 40px; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; width: 100%; transition: background 0.3s; }
        .submit-btn:hover { background: #219a52; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .alert-age { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107; }
        .age-hint { font-size: 12px; color: #666; margin-top: 5px; }
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; font-size: 12px; margin-top: 30px; }
        
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .nav { gap: 10px; }
            .nav a { padding: 5px 10px; font-size: 12px; }
        }
    </style>
    <script>
        function calculateAge(birthDate) {
            var today = new Date();
            var birth = new Date(birthDate);
            var age = today.getFullYear() - birth.getFullYear();
            var m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        }

        function checkAgeQualification() {
            var birthDate = document.getElementById('birth_date').value;
            var program = document.getElementById('program_level').value;
            var ageMessage = document.getElementById('age_message');
            
            if (birthDate && program) {
                var age = calculateAge(birthDate);
                var requirements = {
                    'NURSERY': { min: 3, max: 4, text: 'Nursery: 3-4 years old' },
                    'KINDERGARTEN 1': { min: 4, max: 5, text: 'Kindergarten 1: 4-5 years old' },
                    'KINDERGARTEN 2': { min: 5, max: 6, text: 'Kindergarten 2: 5-6 years old' }
                };
                var req = requirements[program];
                if (req) {
                    if (age < req.min || age > req.max) {
                        ageMessage.innerHTML = '⚠️ Warning: Student age (' + age + ' years) does not meet the requirement for ' + req.text + '.';
                        ageMessage.style.color = '#e74c3c';
                        ageMessage.style.fontWeight = 'bold';
                        return false;
                    } else {
                        ageMessage.innerHTML = '✓ Age requirement met! Student age: ' + age + ' years (Required: ' + req.min + '-' + req.max + ' years)';
                        ageMessage.style.color = '#27ae60';
                        ageMessage.style.fontWeight = 'normal';
                        return true;
                    }
                }
            }
            return true;
        }

        function showProofCertificationField() {
            var program = document.getElementById('program_level').value;
            var proofCertGroup = document.getElementById('proof_certification_group');
            var proofCertInput = document.querySelector('input[name="proof_certification"]');
            
            if (program === 'KINDERGARTEN 2') {
                proofCertGroup.style.display = 'block';
                if (proofCertInput) proofCertInput.required = true;
            } else {
                proofCertGroup.style.display = 'none';
                if (proofCertInput) proofCertInput.required = false;
            }
        }

        function updatePaymentAmount() {
            const program = document.getElementById('program_level').value;
            const plan = document.getElementById('payment_plan').value;
            const nurseryFee = <?php echo $nursery_fee; ?>;
            const k1Fee = <?php echo $k1_fee; ?>;
            const k2Fee = <?php echo $k2_fee; ?>;
            
            const amounts = {
                'NURSERY': {
                    'Cash (Full)': nurseryFee,
                    'Semi Annual': Math.round(nurseryFee * 0.5),
                    'Quarterly': Math.round(nurseryFee * 0.35),
                    'Monthly': Math.round(nurseryFee * 0.25)
                },
                'KINDERGARTEN 1': {
                    'Cash (Full)': k1Fee,
                    'Semi Annual': Math.round(k1Fee * 0.5),
                    'Quarterly': Math.round(k1Fee * 0.35),
                    'Monthly': Math.round(k1Fee * 0.25)
                },
                'KINDERGARTEN 2': {
                    'Cash (Full)': k2Fee,
                    'Semi Annual': Math.round(k2Fee * 0.5),
                    'Quarterly': Math.round(k2Fee * 0.35),
                    'Monthly': Math.round(k2Fee * 0.25)
                }
            };
            
            if (amounts[program] && amounts[program][plan]) {
                document.getElementById('payment_amount_display').value = '₱' + amounts[program][plan].toLocaleString();
                document.getElementById('payment_amount_hidden').value = amounts[program][plan];
            }
            checkAgeQualification();
            showProofCertificationField();
        }

        function validateForm() {
            var required = document.querySelectorAll('[required]');
            for (var i = 0; i < required.length; i++) {
                if (!required[i].value) {
                    alert('Please fill in all required fields.');
                    required[i].focus();
                    return false;
                }
            }
            
            var birthDate = document.getElementById('birth_date').value;
            var program = document.getElementById('program_level').value;
            
            if (birthDate && program) {
                var age = calculateAge(birthDate);
                var requirements = {
                    'NURSERY': { min: 3, max: 4 },
                    'KINDERGARTEN 1': { min: 4, max: 5 },
                    'KINDERGARTEN 2': { min: 5, max: 6 }
                };
                var req = requirements[program];
                if (req && (age < req.min || age > req.max)) {
                    alert('Student age (' + age + ' years) does not meet the requirement for this program.\n\n' +
                          'Nursery: 3-4 years old\n' +
                          'Kindergarten 1: 4-5 years old\n' +
                          'Kindergarten 2: 5-6 years old');
                    return false;
                }
            }
            
            var phonePattern = /^(09|\+639)\d{9}$/;
            var phoneInputs = ['mother_phone', 'father_phone', 'emergency_phone'];
            for (var i = 0; i < phoneInputs.length; i++) {
                var phone = document.querySelector('[name="' + phoneInputs[i] + '"]');
                if (phone && phone.value && !phonePattern.test(phone.value)) {
                    alert('Please enter a valid Philippine mobile number (e.g., 09123456789)');
                    phone.focus();
                    return false;
                }
            }
            
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            updatePaymentAmount();
            var birthDateField = document.getElementById('birth_date');
            var programField = document.getElementById('program_level');
            if (birthDateField) birthDateField.addEventListener('change', checkAgeQualification);
            if (programField) programField.addEventListener('change', function() { checkAgeQualification(); showProofCertificationField(); });
            showProofCertificationField();
            var form = document.querySelector('form');
            if (form) form.addEventListener('submit', function(e) { if (!validateForm()) e.preventDefault(); else return confirm('Submit Application? Please review all information before submitting.'); });
        });
    </script>
</head>
<body>
    <div class="header">
        <img src="images/logo.png" alt="Logo">
        <h1>DAILY BREAD LEARNING CENTER INC.</h1>
        <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City | 0923-4701532</p>
        <p>Preschool Department - Academy Year <?php echo date('Y') . '-' . (date('Y')+1); ?></p>
    </div>
    
    <div class="nav">
        <a href="welcome.php">Home</a>
        <a href="index.php" class="active">Registration Form</a>
        <a href="view_enrollees.php">Enrolled Students</a>
        <a href="tuition_fees.php">Tuition and Fees</a>
        <a href="online_payment.php">💳 Pay Online</a>
        <a href="welcome.php#portals">Staff Portals</a>
    </div>
    
    <div class="container">
        <?php if($success): ?>
            <div class="alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert-error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($age_error): ?>
            <div class="alert-age">⚠️ <?php echo $age_error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="section">
                <h2>Student Enrollment & Medical Authorization</h2>
                <div class="form-group">
                    <label>Student Type *</label>
                    <div class="radio-group">
                        <label><input type="radio" name="student_type" value="New Student" required> New Student (No previous records)</label>
                        <label><input type="radio" name="student_type" value="Existing Student"> Existing Student (Previously enrolled)</label>
                        <label><input type="radio" name="student_type" value="Transferee"> Transferee (From other school)</label>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2>Program & Payment</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Program Level *</label>
                        <select name="program_level" id="program_level" required onchange="updatePaymentAmount()">
                            <option value="NURSERY">NURSERY (Ages 3-4)</option>
                            <option value="KINDERGARTEN 1" selected>KINDERGARTEN 1 (Ages 4-5)</option>
                            <option value="KINDERGARTEN 2">KINDERGARTEN 2 (Ages 5-6)</option>
                        </select>
                        <div class="age-hint">Please select the appropriate program based on your child's age.</div>
                    </div>
                    <div class="form-group">
                        <label>Payment Plan *</label>
                        <select name="payment_plan" id="payment_plan" required onchange="updatePaymentAmount()">
                            <option value="Cash (Full)">Cash (Full)</option>
                            <option value="Semi Annual">Semi Annual</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Total Payment Amount</label>
                        <input type="text" id="payment_amount_display" value="₱<?php echo number_format($k1_fee, 0); ?>" readonly style="background:#f0f0f0; font-weight:bold; font-size:16px;">
                        <input type="hidden" name="payment_amount" id="payment_amount_hidden" value="<?php echo $k1_fee; ?>">
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2>Student Information</h2>
                <div class="form-row">
                    <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" required></div>
                    <div class="form-group"><label>First Name *</label><input type="text" name="first_name" required></div>
                    <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Nickname</label><input type="text" name="nickname"></div>
                    <div class="form-group"><label>Birth Date *</label><input type="date" name="birth_date" id="birth_date" required><div id="age_message" class="age-hint"></div></div>
                    <div class="form-group"><label>Place of Birth</label><input type="text" name="place_of_birth"></div>
                </div>
                <div class="form-group"><label>Address *</label><input type="text" name="address" required></div>
                <div class="form-group"><label>Siblings (Name & Birthdate) - One per line</label><textarea name="siblings" rows="3" placeholder="e.g., Juan Dela Cruz (2018-05-10)&#10;Maria Dela Cruz (2020-08-15)"></textarea></div>
            </div>
            
            <div class="section">
                <h2>Parents Information</h2>
                <div class="form-row">
                    <div class="form-group"><label>Mother's Maiden Full Name</label><input type="text" name="mother_name"></div>
                    <div class="form-group"><label>Mother's Contact Number</label><input type="text" name="mother_phone" placeholder="09XXXXXXXXX"></div>
                    <div class="form-group"><label>Mother's Employer</label><input type="text" name="mother_employer"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Father's Full Name</label><input type="text" name="father_name"></div>
                    <div class="form-group"><label>Father's Contact Number</label><input type="text" name="father_phone" placeholder="09XXXXXXXXX"></div>
                    <div class="form-group"><label>Father's Employer</label><input type="text" name="father_employer"></div>
                </div>
            </div>
            
            <div class="section">
                <h2>Emergency Contact</h2>
                <div class="form-row">
                    <div class="form-group"><label>Emergency Contact Name *</label><input type="text" name="emergency_name" required></div>
                    <div class="form-group"><label>Emergency Contact Phone *</label><input type="text" name="emergency_phone" required placeholder="09XXXXXXXXX"></div>
                    <div class="form-group"><label>Relationship</label><input type="text" name="emergency_relationship" placeholder="e.g., Aunt, Grandmother"></div>
                </div>
                <div class="form-group"><label>Authorized Pickup (Name, Address, Phone)</label><input type="text" name="authorized_pickup"></div>
            </div>
            
            <div class="section">
                <h2>Medical Information</h2>
                <div class="form-row">
                    <div class="form-group"><label>Doctor's Name</label><input type="text" name="doctor_name"></div>
                    <div class="form-group"><label>Doctor's Phone</label><input type="text" name="doctor_phone"></div>
                </div>
                <div class="form-group"><label>Medical Problems</label><input type="text" name="medical_problems" placeholder="e.g., Asthma, Allergies"></div>
                <div class="form-group"><label>Allergies</label><input type="text" name="allergies" placeholder="e.g., Peanuts, Dust"></div>
                <div class="form-group"><label>Additional Info (Likes, potty training, interests)</label><textarea name="additional_info" rows="3"></textarea></div>
            </div>
            
            <div class="section">
                <h2>Required Documents</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Birth Certificate *</label>
                        <div class="file-upload">
                            <input type="file" name="birth_certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="file-info">Accepted: PDF, JPG, PNG (Max 5MB)</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>2×2 ID Picture *</label>
                        <div class="file-upload">
                            <input type="file" name="id_picture" accept=".jpg,.jpeg,.png" required>
                            <div class="file-info">Recent 2×2 colored picture (JPG/PNG, Max 2MB)</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Report Card / Grades <span style="color:orange;">(Required for Transferees)</span></label>
                    <div class="file-upload">
                        <input type="file" name="report_card" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="file-info">Optional for New/Existing students. Required for Transferees (PDF/JPG/PNG, Max 5MB)</div>
                    </div>
                </div>
                
                <div class="form-group" id="proof_certification_group" style="display: none;">
                    <label>Proof of Certification / Completion <span style="color:orange;">(Required for Kindergarten 2 applicants)</span></label>
                    <div class="file-upload">
                        <input type="file" name="proof_certification" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="file-info">Required for Kindergarten 2 students. Accepted: PDF, JPG, PNG (Max 5MB)</div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2>EMERGENCY CONSENT</h2>
                <p style="margin-bottom: 15px; color: #555;">I give consent for my/our child to be taken to the nearest emergency center by staff when parent cannot be contacted. I agree to pay transport costs.</p>
                <div class="form-row">
                    <div class="form-group"><label>Parent/Guardian Signature (Full Name) *</label><input type="text" name="parent_signature" required></div>
                    <div class="form-group"><label>Date Signed *</label><input type="date" name="date_signed" required value="<?php echo date('Y-m-d'); ?>"></div>
                </div>
                <p style="margin-top: 15px; font-size: 12px; color: #999;">Immunization record: Please submit photocopy to center. By enrolling you confirm records will be provided.</p>
            </div>
            
            <button type="submit" name="enroll_student" class="submit-btn">SUBMIT APPLICATION</button>
        </form>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure enrollment database</p>
    </div>
</body>
</html>