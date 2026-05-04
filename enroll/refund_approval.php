<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'registrar') {
    header("Location: login.php?role=registrar");
    exit();
}
require_once 'db_connection.php';

$success = '';
$error = '';

// Handle approval/denial
if(isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $approval_notes = $_POST['approval_notes'] ?? '';
    
    if($action == 'approve') {
        $status = 'approved';
        $stmt = $pdo->prepare("UPDATE refund_requests SET status = 'approved', approved_by = ?, approved_date = CURDATE(), approval_notes = ? WHERE request_id = ?");
        $stmt->execute([$_SESSION['user_id'], $approval_notes, $request_id]);
        $success = "Refund request #$request_id has been APPROVED!";
    } elseif($action == 'deny') {
        $status = 'denied';
        $stmt = $pdo->prepare("UPDATE refund_requests SET status = 'denied', approved_by = ?, approved_date = CURDATE(), approval_notes = ? WHERE request_id = ?");
        $stmt->execute([$_SESSION['user_id'], $approval_notes, $request_id]);
        $success = "Refund request #$request_id has been DENIED.";
    }
}

// Fetch pending refund requests
$stmt = $pdo->query("
    SELECT rr.*, e.first_name, e.last_name, e.program_level, e.email
    FROM refund_requests rr
    JOIN enrollees e ON rr.enrollee_id = e.enrollee_id
    WHERE rr.status = 'pending'
    ORDER BY rr.request_date DESC
");
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved/denied requests
$stmt = $pdo->query("
    SELECT rr.*, e.first_name, e.last_name, e.program_level, u.full_name as approver_name
    FROM refund_requests rr
    JOIN enrollees e ON rr.enrollee_id = e.enrollee_id
    LEFT JOIN users u ON rr.approved_by = u.user_id
    WHERE rr.status IN ('approved', 'denied', 'processed')
    ORDER BY rr.approved_date DESC
");
$processed_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Approval - Registrar</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .back-btn { background: #2c3e50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .content { background: white; padding: 25px; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 40px; }
        .section h3 { color: #2c3e50; margin-bottom: 15px; border-left: 4px solid #e74c3c; padding-left: 10px; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        
        .request-card { 
            background: #fff; 
            border: 1px solid #e0e0e0; 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .request-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            margin-bottom: 15px; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #f0f0f0;
        }
        .badge-pending { background: #f39c12; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-approved { background: #27ae60; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-denied { background: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .badge-processed { background: #3498db; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        
        .request-info { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 12px; 
            margin: 15px 0;
        }
        .info-label { font-weight: bold; color: #555; width: 120px; display: inline-block; }
        .info-value { color: #333; }
        
        .btn-approve { background: #27ae60; color: white; border: none; padding: 8px 25px; border-radius: 5px; cursor: pointer; margin-right: 10px; font-size: 14px; }
        .btn-approve:hover { background: #219a52; }
        .btn-deny { background: #e74c3c; color: white; border: none; padding: 8px 25px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-deny:hover { background: #c0392b; }
        .btn-view { 
            background: #3498db; 
            color: white; 
            padding: 5px 12px; 
            text-decoration: none; 
            border-radius: 5px; 
            font-size: 12px; 
            display: inline-block;
            cursor: pointer;
            border: none;
        }
        .btn-view:hover { background: #2980b9; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; border-radius: 10px; margin-top: 20px; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 90%;
            width: 90%;
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
        
        .modal-header h3 {
            color: #2c3e50;
        }
        
        .close-modal {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .close-modal:hover {
            background: #c0392b;
        }
        
        .proof-image {
            max-width: 100%;
            max-height: 70vh;
            display: block;
            margin: 0 auto;
        }
        
        .proof-pdf {
            width: 100%;
            height: 80vh;
            border: none;
        }
        
        .modal-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        .btn-download {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-download:hover {
            background: #2980b9;
        }
        
        @media (max-width: 768px) {
            .request-info { grid-template-columns: 1fr; }
            .request-header { flex-direction: column; gap: 10px; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>💰 Refund Request Management</h2>
        <a href="registrar_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Pending Refund Requests -->
        <div class="section">
            <h3>⏳ Pending Refund Requests</h3>
            <?php if(count($pending_requests) > 0): ?>
                <?php foreach($pending_requests as $req): ?>
                <div class="request-card">
                    <div class="request-header">
                        <strong>Request #<?php echo $req['request_id']; ?> - <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></strong>
                        <span class="badge-pending">Pending</span>
                    </div>
                    <div class="request-info">
                        <div><span class="info-label">Student ID:</span> <?php echo $req['enrollee_id']; ?></div>
                        <div><span class="info-label">Program:</span> <?php echo $req['program_level']; ?></div>
                        <div><span class="info-label">Request Date:</span> <?php echo date('M d, Y', strtotime($req['request_date'])); ?></div>
                        <div><span class="info-label">Refund Amount:</span> <strong style="color:#e74c3c;">₱<?php echo number_format($req['refund_amount'], 2); ?></strong></div>
                        <div><span class="info-label">Reason:</span> <?php echo nl2br(htmlspecialchars($req['refund_reason'])); ?></div>
                        <div><span class="info-label">Letter:</span> 
                            <button class="btn-view" onclick="openLetterModal('<?php echo $req['letter_path']; ?>', '<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>')">📄 View Letter</button>
                        </div>
                    </div>
                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                        <textarea name="approval_notes" rows="2" placeholder="Add notes (optional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;"></textarea>
                        <div>
                            <button type="submit" name="action" value="approve" class="btn-approve" onclick="return confirm('Approve this refund request?')">✅ Approve Refund</button>
                            <button type="submit" name="action" value="deny" class="btn-deny" onclick="return confirm('Deny this refund request?')">❌ Deny Refund</button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px;">No pending refund requests.</p>
            <?php endif; ?>
        </div>
        
        <!-- Processed Requests -->
        <div class="section">
            <h3>📜 Processed Refund Requests</h3>
            <?php if(count($processed_requests) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Letter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($processed_requests as $req): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($req['approved_date'] ?: $req['request_date'])); ?></td>
                                <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                <td>₱<?php echo number_format($req['refund_amount'], 2); ?></td>
                                <td>
                                    <?php if($req['status'] == 'approved'): ?>
                                        <span class="badge-approved">Approved</span>
                                    <?php elseif($req['status'] == 'denied'): ?>
                                        <span class="badge-denied">Denied</span>
                                    <?php else: ?>
                                        <span class="badge-processed">Processed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $req['approver_name'] ?? '-'; ?></td>
                                <td><button class="btn-view" onclick="openLetterModal('<?php echo $req['letter_path']; ?>', '<?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>')">📄 View</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px;">No processed refund requests yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <p>Daily Bread Learning Center Inc. — Refund Approval System | Review and approve/deny refund requests</p>
    </div>
</div>

<!-- Modal Popup for Viewing Letter -->
<div id="letterModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Refund Letter</h3>
            <button class="close-modal" onclick="closeLetterModal()">✕ Close</button>
        </div>
        <div id="modalBody" style="text-align: center;">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <a id="downloadLink" href="#" class="btn-download" download>📥 Download Letter</a>
        </div>
    </div>
</div>

<script>
function openLetterModal(filePath, studentName) {
    var modal = document.getElementById('letterModal');
    var modalTitle = document.getElementById('modalTitle');
    var modalBody = document.getElementById('modalBody');
    var downloadLink = document.getElementById('downloadLink');
    
    modalTitle.innerHTML = 'Refund Letter - ' + studentName;
    downloadLink.href = filePath;
    
    var fileExtension = filePath.split('.').pop().toLowerCase();
    
    if (fileExtension === 'pdf') {
        modalBody.innerHTML = '<iframe src="' + filePath + '" class="proof-pdf"></iframe>';
    } else if (fileExtension === 'jpg' || fileExtension === 'jpeg' || fileExtension === 'png' || fileExtension === 'gif') {
        modalBody.innerHTML = '<img src="' + filePath + '" class="proof-image" alt="Refund Letter">';
    } else {
        modalBody.innerHTML = '<p>Unable to preview this file type. <a href="' + filePath + '" target="_blank">Click here to open</a></p>';
    }
    
    modal.style.display = 'flex';
}

function closeLetterModal() {
    document.getElementById('letterModal').style.display = 'none';
}

// Close modal when clicking outside the content
window.onclick = function(event) {
    var modal = document.getElementById('letterModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLetterModal();
    }
});
</script>
</body>
</html>