<?php
session_start();
require_once 'db_connection.php';
require_once 'includes_functions.php';

if(!isset($_SESSION['user_type']) && !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$enrollee_id = $_GET['id'];

// Fetch student basic info
$stmt = $pdo->prepare("SELECT * FROM enrollees WHERE enrollee_id = ?");
$stmt->execute([$enrollee_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch mother info
$stmt = $pdo->prepare("SELECT * FROM mother_info WHERE enrollee_id = ?");
$stmt->execute([$enrollee_id]);
$mother = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch father info
$stmt = $pdo->prepare("SELECT * FROM father_info WHERE enrollee_id = ?");
$stmt->execute([$enrollee_id]);
$father = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch siblings
$stmt = $pdo->prepare("SELECT * FROM siblings WHERE enrollee_id = ?");
$stmt->execute([$enrollee_id]);
$siblings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE enrollee_id = ?");
$stmt->execute([$enrollee_id]);
$documents = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch consent
$stmt = $pdo->prepare("SELECT * FROM emergency_consent WHERE enrollee_id = ?");
$stmt->execute([$enrollee_id]);
$consent = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch payment transactions
$stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE enrollee_id = ? ORDER BY payment_date DESC");
$stmt->execute([$enrollee_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_paid = 0;
$total_refunded = 0;
foreach($payments as $payment) {
    if($payment['payment_type'] == 'Payment') {
        $total_paid += $payment['payment_amount'];
    } elseif($payment['payment_type'] == 'Refund') {
        $total_refunded += abs($payment['payment_amount']);
    }
}
$net_paid = $total_paid - $total_refunded;

// Check if student is Kindergarten 2
$is_kinder2 = ($student['program_level'] == 'KINDERGARTEN 2');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .header { background: #2c3e50; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 22px; }
        .back-btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        .back-btn:hover { background: #2980b9; }
        
        .nav-tabs { background: #34495e; display: flex; flex-wrap: wrap; gap: 5px; padding: 10px 20px 0 20px; }
        .tab-btn { background: #2c3e50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px 5px 0 0; transition: background 0.3s; }
        .tab-btn:hover { background: #1abc9c; }
        .tab-btn.active { background: #1abc9c; }
        
        .tab-content { display: none; padding: 30px; }
        .tab-content.active { display: block; }
        
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 200px; color: #555; }
        .value { flex: 1; color: #333; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .qualified { background: #27ae60; color: white; }
        .pending { background: #f39c12; color: white; }
        .not-qualified { background: #e74c3c; color: white; }
        .fully-paid { background: #27ae60; color: white; }
        .partial { background: #f39c12; color: white; }
        .unpaid { background: #e74c3c; color: white; }
        .enrolled { background: #27ae60; color: white; }
        .dropped { background: #e74c3c; color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 15px; font-size: 12px; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.85);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            position: relative;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .modal-header h3 { color: #2c3e50; }
        .close-modal { background: #e74c3c; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .close-modal:hover { background: #c0392b; }
        .proof-image { max-width: 100%; max-height: 70vh; display: block; margin: 0 auto; }
        .proof-pdf { width: 100%; height: 80vh; border: none; }
        .modal-footer { margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; text-align: right; }
        .btn-download { background: #3498db; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .view-link { color: #3498db; text-decoration: none; cursor: pointer; display: inline-block; padding: 2px 8px; background: #e8f4fd; border-radius: 5px; }
        .view-link:hover { background: #d1ecf9; }
        
        @media (max-width: 768px) {
            .info-row { flex-direction: column; }
            .label { width: 100%; margin-bottom: 5px; }
            .nav-tabs { justify-content: center; }
            .tab-btn { padding: 8px 12px; font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Student Details: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="nav-tabs">
        <button class="tab-btn active" onclick="showTab('info')">📋 Student Info</button>
        <button class="tab-btn" onclick="showTab('status')">📊 Status</button>
        <button class="tab-btn" onclick="showTab('parents')">👨‍👩‍👧 Parents & Siblings</button>
        <button class="tab-btn" onclick="showTab('payment')">💰 Payment History</button>
        <button class="tab-btn" onclick="showTab('documents')">📎 Documents</button>
        <button class="tab-btn" onclick="showTab('consent')">⚠️ Emergency Consent</button>
    </div>
    
    <div id="tab-info" class="tab-content active">
        <div class="info-row"><div class="label">Student ID:</div><div class="value"><?php echo $student['enrollee_id']; ?></div></div>
        <div class="info-row"><div class="label">Student Type:</div><div class="value"><?php echo $student['student_type']; ?></div></div>
        <div class="info-row"><div class="label">Full Name:</div><div class="value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?></div></div>
        <div class="info-row"><div class="label">Nickname:</div><div class="value"><?php echo htmlspecialchars($student['nickname']); ?></div></div>
        <div class="info-row"><div class="label">Birth Date:</div><div class="value"><?php echo date('F d, Y', strtotime($student['birth_date'])); ?></div></div>
        <div class="info-row"><div class="label">Place of Birth:</div><div class="value"><?php echo htmlspecialchars($student['place_of_birth']); ?></div></div>
        <div class="info-row"><div class="label">Address:</div><div class="value"><?php echo htmlspecialchars($student['address']); ?></div></div>
        <div class="info-row"><div class="label">Program Level:</div><div class="value"><?php echo $student['program_level']; ?></div></div>
        <div class="info-row"><div class="label">Payment Plan:</div><div class="value"><?php echo $student['payment_plan']; ?> - ₱<?php echo number_format($student['payment_amount'], 2); ?></div></div>
        <div class="info-row"><div class="label">Email:</div><div class="value"><?php echo htmlspecialchars($student['email']); ?></div></div>
        <div class="info-row"><div class="label">Enrolled On:</div><div class="value"><?php echo date('F d, Y h:i A', strtotime($student['created_at'])); ?></div></div>
    </div>
    
    <div id="tab-status" class="tab-content">
        <div class="info-row"><div class="label">Enrollment Status:</div><div class="value"><span class="badge <?php echo strtolower($student['enrollment_status']); ?>"><?php echo $student['enrollment_status']; ?></span></div></div>
        <div class="info-row"><div class="label">Qualification Status:</div><div class="value"><span class="badge <?php echo strtolower(str_replace(' ', '', $student['qualification_status'])); ?>"><?php echo $student['qualification_status']; ?></span></div></div>
        <div class="info-row"><div class="label">Requirements Status:</div><div class="value"><span class="badge <?php echo strtolower($student['requirements_status']); ?>"><?php echo $student['requirements_status']; ?></span></div></div>
        <div class="info-row"><div class="label">Payment Status:</div><div class="value"><span class="badge <?php echo strtolower(str_replace(' ', '-', $student['payment_status'])); ?>"><?php echo $student['payment_status']; ?></span></div></div>
        <div class="info-row"><div class="label">Total Paid:</div><div class="value">₱<?php echo number_format($total_paid, 2); ?></div></div>
        <div class="info-row"><div class="label">Total Refunded:</div><div class="value">₱<?php echo number_format($total_refunded, 2); ?></div></div>
        <div class="info-row"><div class="label">Net Paid:</div><div class="value" style="font-weight: bold; color: <?php echo $net_paid <= 0 ? '#e74c3c' : '#27ae60'; ?>;">₱<?php echo number_format($net_paid, 2); ?></div></div>
        <?php if($student['enrollment_status_reason']): ?>
        <div class="info-row"><div class="label">Status Reason:</div><div class="value"><?php echo htmlspecialchars($student['enrollment_status_reason']); ?></div></div>
        <?php endif; ?>
    </div>
    
    <div id="tab-parents" class="tab-content">
        <?php if($mother): ?>
        <h3 style="margin-bottom: 15px;">Mother's Information</h3>
        <div class="info-row"><div class="label">Full Name:</div><div class="value"><?php echo htmlspecialchars($mother['full_name']); ?></div></div>
        <div class="info-row"><div class="label">Contact Number:</div><div class="value"><?php echo $mother['contact_number']; ?></div></div>
        <div class="info-row"><div class="label">Occupation:</div><div class="value"><?php echo htmlspecialchars($mother['occupation']); ?></div></div>
        <?php endif; ?>
        
        <?php if($father): ?>
        <h3 style="margin: 20px 0 15px;">Father's Information</h3>
        <div class="info-row"><div class="label">Full Name:</div><div class="value"><?php echo htmlspecialchars($father['full_name']); ?></div></div>
        <div class="info-row"><div class="label">Contact Number:</div><div class="value"><?php echo $father['contact_number']; ?></div></div>
        <div class="info-row"><div class="label">Occupation:</div><div class="value"><?php echo htmlspecialchars($father['occupation']); ?></div></div>
        <?php endif; ?>
        
        <h3 style="margin: 20px 0 15px;">Siblings</h3>
        <?php if(count($siblings) > 0): ?>
            <?php foreach($siblings as $sibling): ?>
            <div class="info-row"><div class="label">Name:</div><div class="value"><?php echo htmlspecialchars($sibling['sibling_name']); ?></div></div>
            <div class="info-row"><div class="label">Birth Date:</div><div class="value"><?php echo date('F d, Y', strtotime($sibling['sibling_birth_date'])); ?></div></div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No siblings listed.</p>
        <?php endif; ?>
    </div>
    
    <div id="tab-payment" class="tab-content">
        <?php if(count($payments) > 0): ?>
        <table>
            <thead><tr><th>Date</th><th>Receipt #</th><th>Type</th><th>Amount</th><th>Refund</th><th>Processed By</th></tr></thead>
            <tbody>
                <?php foreach($payments as $payment): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                    <td><?php echo $payment['receipt_number']; ?></td>
                    <td><?php echo $payment['payment_type']; ?></td>
                    <td>₱<?php echo number_format(abs($payment['payment_amount']), 2); ?></td>
                    <td><?php echo $payment['refund_amount'] ? '₱'.number_format($payment['refund_amount'],2) : '-'; ?></td>
                    <td><?php echo $payment['processed_by']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No payment transactions found.</p>
        <?php endif; ?>
    </div>
    
    <div id="tab-documents" class="tab-content">
        <?php if($documents): ?>
            <div class="info-row"><div class="label">Birth Certificate:</div><div class="value">
                <?php if($documents['birth_certificate_path']): ?>
                    <button class="view-link" onclick="viewDocument('<?php echo $documents['birth_certificate_path']; ?>', 'Birth Certificate')">📄 View Birth Certificate</button>
                <?php else: ?>
                    Not uploaded
                <?php endif; ?>
            </div></div>
            <div class="info-row"><div class="label">2x2 ID Picture:</div><div class="value">
                <?php if($documents['id_picture_path']): ?>
                    <button class="view-link" onclick="viewDocument('<?php echo $documents['id_picture_path']; ?>', 'ID Picture')">🖼️ View ID Picture</button>
                <?php else: ?>
                    Not uploaded
                <?php endif; ?>
            </div></div>
            <div class="info-row"><div class="label">Report Card:</div><div class="value">
                <?php if($documents['report_card_path']): ?>
                    <button class="view-link" onclick="viewDocument('<?php echo $documents['report_card_path']; ?>', 'Report Card')">📑 View Report Card</button>
                <?php else: ?>
                    Not uploaded
                <?php endif; ?>
            </div></div>
            <?php if($is_kinder2): ?>
            <div class="info-row"><div class="label">Proof of Certification:</div><div class="value">
                <?php if($documents['proof_certification_path'] ?? ''): ?>
                    <button class="view-link" onclick="viewDocument('<?php echo $documents['proof_certification_path']; ?>', 'Proof of Certification')">📜 View Proof of Certification</button>
                <?php else: ?>
                    Not uploaded (Required for Kinder 2)
                <?php endif; ?>
            </div></div>
            <?php endif; ?>
        <?php else: ?>
            <p>No documents uploaded yet.</p>
        <?php endif; ?>
    </div>
    
    <div id="tab-consent" class="tab-content">
        <?php if($consent): ?>
            <div class="info-row"><div class="label">Parent/Guardian Signature:</div><div class="value"><?php echo htmlspecialchars($consent['parent_guardian_signature']); ?></div></div>
            <div class="info-row"><div class="label">Date Signed:</div><div class="value"><?php echo date('F d, Y', strtotime($consent['date_signed'])); ?></div></div>
        <?php else: ?>
            <p>No consent form submitted yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Student Details</p>
    </div>
</div>

<!-- Modal Popup for Viewing Documents -->
<div id="documentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Document Viewer</h3>
            <button class="close-modal" onclick="closeDocumentModal()">✕ Close</button>
        </div>
        <div id="modalBody" style="text-align: center;"></div>
        <div class="modal-footer">
            <a id="downloadLink" href="#" class="btn-download" download>📥 Download</a>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    var tabs = ['info', 'status', 'parents', 'payment', 'documents', 'consent'];
    tabs.forEach(function(tab) {
        var el = document.getElementById('tab-' + tab);
        if(el) el.classList.remove('active');
    });
    document.getElementById('tab-' + tabName).classList.add('active');
    
    var btns = document.querySelectorAll('.tab-btn');
    btns.forEach(function(btn) {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

function viewDocument(filePath, documentName) {
    var modal = document.getElementById('documentModal');
    var modalTitle = document.getElementById('modalTitle');
    var modalBody = document.getElementById('modalBody');
    var downloadLink = document.getElementById('downloadLink');
    
    modalTitle.innerHTML = documentName + ' - Student Document';
    downloadLink.href = filePath;
    
    var fileExtension = filePath.split('.').pop().toLowerCase();
    
    if (fileExtension === 'pdf') {
        modalBody.innerHTML = '<iframe src="' + filePath + '" class="proof-pdf"></iframe>';
    } else if (fileExtension === 'jpg' || fileExtension === 'jpeg' || fileExtension === 'png' || fileExtension === 'gif') {
        modalBody.innerHTML = '<img src="' + filePath + '" class="proof-image" alt="Document">';
    } else {
        modalBody.innerHTML = '<p>Unable to preview this file type. <a href="' + filePath + '" target="_blank">Click here to open</a></p>';
    }
    
    modal.style.display = 'flex';
}

function closeDocumentModal() {
    document.getElementById('documentModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('documentModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDocumentModal();
    }
});
</script>
</body>
</html>