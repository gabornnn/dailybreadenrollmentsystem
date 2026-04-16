<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connection.php';

$success = '';
$error = '';

if(isset($_POST['enroll_student'])) {
    // Get form data
    $student_type = $_POST['student_type'];
    $program_level = $_POST['program_level'];
    $payment_plan = $_POST['payment_plan'];
    
    // Set payment amounts based on program and plan
    $payment_amounts = [
        'NURSERY' => ['Cash (Full)' => 17500, 'Semi Annual' => 8900, 'Quarterly' => 6600, 'Monthly' => 5250],
        'KINDERGARTEN 1' => ['Cash (Full)' => 18300, 'Semi Annual' => 9400, 'Quarterly' => 7050, 'Monthly' => 5700],
        'KINDERGARTEN 2' => ['Cash (Full)' => 18300, 'Semi Annual' => 10100, 'Quarterly' => 7550, 'Monthly' => 6200]
    ];
    
    $payment_amount = $payment_amounts[$program_level][$payment_plan];
    
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $nickname = $_POST['nickname'];
    $birth_date = $_POST['birth_date'];
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
    
    // Handle siblings
    $siblings = isset($_POST['siblings']) ? $_POST['siblings'] : '';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert into enrollees
        $stmt = $pdo->prepare("INSERT INTO enrollees (
            student_type, program_level, payment_plan, payment_amount, 
            last_name, first_name, middle_name, nickname, 
            birth_date, place_of_birth, address,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
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
        
        // Insert siblings (if any)
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
        
        // Create uploads directory if not exists
        if(!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Handle file uploads
        $birth_cert_path = '';
        $id_picture_path = '';
        $report_card_path = '';
        
        // Upload Birth Certificate
        if(isset($_FILES['birth_certificate']) && $_FILES['birth_certificate']['error'] == 0) {
            $target_dir = "uploads/";
            $file_extension = pathinfo($_FILES['birth_certificate']['name'], PATHINFO_EXTENSION);
            $birth_cert_path = $target_dir . "birth_cert_" . $enrollee_id . "_" . time() . "." . $file_extension;
            move_uploaded_file($_FILES['birth_certificate']['tmp_name'], $birth_cert_path);
        }
        
        // Upload ID Picture
        if(isset($_FILES['id_picture']) && $_FILES['id_picture']['error'] == 0) {
            $target_dir = "uploads/";
            $file_extension = pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION);
            $id_picture_path = $target_dir . "id_picture_" . $enrollee_id . "_" . time() . "." . $file_extension;
            move_uploaded_file($_FILES['id_picture']['tmp_name'], $id_picture_path);
        }
        
        // Upload Report Card
        if(isset($_FILES['report_card']) && $_FILES['report_card']['error'] == 0) {
            $target_dir = "uploads/";
            $file_extension = pathinfo($_FILES['report_card']['name'], PATHINFO_EXTENSION);
            $report_card_path = $target_dir . "report_card_" . $enrollee_id . "_" . time() . "." . $file_extension;
            move_uploaded_file($_FILES['report_card']['tmp_name'], $report_card_path);
        }
        
        // Insert documents
        $stmt = $pdo->prepare("INSERT INTO documents (enrollee_id, birth_certificate_path, id_picture_path, report_card_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$enrollee_id, $birth_cert_path, $id_picture_path, $report_card_path]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "✅ Student successfully enrolled! Enrollment ID: " . $enrollee_id;
        
        // Clear form data after success
        $_POST = array();
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "❌ Enrollment failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Bread Learning Center - Enrollment System</title>
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
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Header */
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
        
        /* Navigation */
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
        
        /* Form Content */
        .form-content {
            padding: 30px;
        }
        
        .section {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            border-left: 4px solid #27ae60;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: red;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
        }
        
        .radio-group input {
            width: auto;
        }
        
        /* File Upload */
        .file-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            background: white;
        }
        
        .file-upload input {
            margin-top: 10px;
        }
        
        .file-info {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        /* Submit Button */
        .submit-btn {
            background: #27ae60;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        .submit-btn:hover {
            background: #219a52;
        }
        
        /* Alert Messages */
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            .nav {
                flex-direction: column;
            }
        }
    </style>
    <script>
        function updatePaymentAmount() {
            const program = document.getElementById('program_level').value;
            const plan = document.getElementById('payment_plan').value;
            
            const amounts = {
                'NURSERY': {'Cash (Full)': 17500, 'Semi Annual': 8900, 'Quarterly': 6600, 'Monthly': 5250},
                'KINDERGARTEN 1': {'Cash (Full)': 18300, 'Semi Annual': 9400, 'Quarterly': 7050, 'Monthly': 5700},
                'KINDERGARTEN 2': {'Cash (Full)': 18300, 'Semi Annual': 10100, 'Quarterly': 7550, 'Monthly': 6200}
            };
            
            if(amounts[program] && amounts[program][plan]) {
                document.getElementById('payment_amount_display').value = '₱' + amounts[program][plan].toLocaleString();
                document.getElementById('payment_amount_hidden').value = amounts[program][plan];
            }
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏫 DAILY BREAD LEARNING CENTER INC.</h1>
        <p>Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City | 📞 0923-4701532</p>
        <p>📩 Preschool Department - Academy Year 2026-2027</p>
    </div>
    
    <div class="nav">
        <a href="index.php" class="active">📝 Registration Form</a>
        <a href="view_enrollees.php">📊 Enrolled Students (Database)</a>
        <a href="tuition_fees.php">💰 Tuition & Fees</a>
    </div>
    
    <div class="form-content">
        <?php if($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <!-- Student Type -->
            <div class="section">
                <h2>📋 Student Enrollment & Medical Authorization</h2>
                <div class="form-group">
                    <label>Student Type *</label>
                    <div class="radio-group">
                        <label><input type="radio" name="student_type" value="New Student" required> New Student (No previous records)</label>
                        <label><input type="radio" name="student_type" value="Existing Student"> Existing Student (Previously enrolled)</label>
                        <label><input type="radio" name="student_type" value="Transferee"> Transferee (From other school)</label>
                    </div>
                </div>
            </div>
            
            <!-- Program & Payment -->
            <div class="section">
                <h2>💰 Program & Payment</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Program Level *</label>
                        <select name="program_level" id="program_level" required onchange="updatePaymentAmount()">
                            <option value="NURSERY">NURSERY</option>
                            <option value="KINDERGARTEN 1" selected>KINDERGARTEN 1</option>
                            <option value="KINDERGARTEN 2">KINDERGARTEN 2</option>
                        </select>
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
                        <input type="text" id="payment_amount_display" value="₱18,300" readonly style="background:#f0f0f0; font-weight:bold; font-size:16px;">
                        <input type="hidden" name="payment_amount" id="payment_amount_hidden" value="18300">
                    </div>
                </div>
            </div>
            
            <!-- Student Personal Information -->
            <div class="section">
                <h2>👤 Student Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nickname</label>
                        <input type="text" name="nickname" value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Birth Date *</label>
                        <input type="date" name="birth_date" required value="<?php echo isset($_POST['birth_date']) ? $_POST['birth_date'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Place of Birth</label>
                        <input type="text" name="place_of_birth" value="<?php echo isset($_POST['place_of_birth']) ? htmlspecialchars($_POST['place_of_birth']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address *</label>
                    <input type="text" name="address" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Siblings (Name & Birthdate) - One per line</label>
                    <textarea name="siblings" rows="3" placeholder="e.g., Juan Dela Cruz (2018-05-10)&#10;Maria Dela Cruz (2020-08-15)"><?php echo isset($_POST['siblings']) ? htmlspecialchars($_POST['siblings']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Parents Information -->
            <div class="section">
                <h2>👨‍👩‍👧 Parents Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mother's Maiden Full Name</label>
                        <input type="text" name="mother_name" value="<?php echo isset($_POST['mother_name']) ? htmlspecialchars($_POST['mother_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Mother's Contact Number</label>
                        <input type="text" name="mother_phone" value="<?php echo isset($_POST['mother_phone']) ? htmlspecialchars($_POST['mother_phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Mother's Employer</label>
                        <input type="text" name="mother_employer" value="<?php echo isset($_POST['mother_employer']) ? htmlspecialchars($_POST['mother_employer']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Father's Full Name</label>
                        <input type="text" name="father_name" value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Father's Contact Number</label>
                        <input type="text" name="father_phone" value="<?php echo isset($_POST['father_phone']) ? htmlspecialchars($_POST['father_phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Father's Employer</label>
                        <input type="text" name="father_employer" value="<?php echo isset($_POST['father_employer']) ? htmlspecialchars($_POST['father_employer']) : ''; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Emergency Contact -->
            <div class="section">
                <h2>🚨 Emergency Contact</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact Name *</label>
                        <input type="text" name="emergency_name" required value="<?php echo isset($_POST['emergency_name']) ? htmlspecialchars($_POST['emergency_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Phone *</label>
                        <input type="text" name="emergency_phone" required value="<?php echo isset($_POST['emergency_phone']) ? htmlspecialchars($_POST['emergency_phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Relationship</label>
                        <input type="text" name="emergency_relationship" placeholder="e.g., Aunt, Grandmother" value="<?php echo isset($_POST['emergency_relationship']) ? htmlspecialchars($_POST['emergency_relationship']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Authorized Pickup (Name, Address, Phone)</label>
                    <input type="text" name="authorized_pickup" value="<?php echo isset($_POST['authorized_pickup']) ? htmlspecialchars($_POST['authorized_pickup']) : ''; ?>">
                </div>
            </div>
            
            <!-- Medical Information -->
            <div class="section">
                <h2>🏥 Medical Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Doctor's Name</label>
                        <input type="text" name="doctor_name" value="<?php echo isset($_POST['doctor_name']) ? htmlspecialchars($_POST['doctor_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Doctor's Phone</label>
                        <input type="text" name="doctor_phone" value="<?php echo isset($_POST['doctor_phone']) ? htmlspecialchars($_POST['doctor_phone']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Medical Problems</label>
                    <input type="text" name="medical_problems" placeholder="e.g., Asthma, Allergies" value="<?php echo isset($_POST['medical_problems']) ? htmlspecialchars($_POST['medical_problems']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Allergies</label>
                    <input type="text" name="allergies" placeholder="e.g., Peanuts, Dust" value="<?php echo isset($_POST['allergies']) ? htmlspecialchars($_POST['allergies']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Additional Info (Likes, potty training, interests)</label>
                    <textarea name="additional_info" rows="3"><?php echo isset($_POST['additional_info']) ? htmlspecialchars($_POST['additional_info']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Required Documents -->
            <div class="section">
                <h2>📎 Required Documents</h2>
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
                            <div class="file-info">Recent 2×2 colored picture with white background (JPG/PNG, Max 2MB)</div>
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
            </div>
            
            <!-- Emergency Consent -->
            <div class="section">
                <h2>⚠️ EMERGENCY CONSENT</h2>
                <p style="margin-bottom: 15px; color: #555;">I give consent for my/our child to be taken to the nearest emergency center by staff when parent cannot be contacted. I agree to pay transport costs.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent/Guardian Signature (Full Name) *</label>
                        <input type="text" name="parent_signature" required value="<?php echo isset($_POST['parent_signature']) ? htmlspecialchars($_POST['parent_signature']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Date Signed *</label>
                        <input type="date" name="date_signed" required value="<?php echo isset($_POST['date_signed']) ? $_POST['date_signed'] : date('Y-m-d'); ?>">
                    </div>
                </div>
                <p style="margin-top: 15px; font-size: 12px; color: #999;">📋 Immunization record: Please submit photocopy to center. By enrolling you confirm records will be provided.</p>
            </div>
            
            <button type="submit" name="enroll_student" class="submit-btn">✅ ENROLL STUDENT & SAVE TO DATABASE</button>
        </form>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Secure enrollment database | For immunization, please attach physical copy.</p>
    </div>
</div>

<script>
    // Set initial payment amount
    updatePaymentAmount();
</script>
</body>
</html>