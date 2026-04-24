<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

// Verify payment transaction
if(isset($_POST['verify_payment'])) {
    $transaction_id = $_POST['transaction_id'];
    $stmt = $pdo->prepare("UPDATE payment_transactions SET payment_verified = 1, processed_by = ? WHERE transaction_id = ?");
    $stmt->execute([$_SESSION['full_name'], $transaction_id]);
    $success = "Payment verified successfully!";
}

// Fetch all payment transactions
$stmt = $pdo->query("
    SELECT pt.*, e.first_name, e.last_name, e.enrollee_id
    FROM payment_transactions pt
    JOIN enrollees e ON pt.enrollee_id = e.enrollee_id
    ORDER BY pt.created_at DESC
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Log - Admin</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #2c3e50; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .back-btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .content { padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        tr:hover { background: #f5f5f5; }
        
        .proof-link { 
            color: #3498db; 
            text-decoration: none; 
            cursor: pointer;
            display: inline-block;
            padding: 4px 10px;
            background: #e8f4fd;
            border-radius: 5px;
        }
        .proof-link:hover { background: #d1ecf9; text-decoration: none; }
        
        .verified { color: #27ae60; font-weight: bold; }
        .pending { color: #e74c3c; font-weight: bold; }
        
        .btn-verify { background: #27ae60; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; }
        
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
            th, td { font-size: 12px; padding: 8px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📋 Payment Transaction Log</h2>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if(isset($success)): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Amount</th>
                        <th>Reference #</th>
                        <th>Payment Proof</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transactions as $t): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($t['created_at'])); ?></td>
                        <td><?php echo $t['enrollee_id']; ?></td>
                        <td><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></td>
                        <td>₱<?php echo number_format($t['payment_amount'], 2); ?></td>
                        <td><?php echo $t['payment_reference'] ?? '-'; ?></td>
                        <td>
                            <?php if($t['receipt_path'] && file_exists($t['receipt_path'])): ?>
                                <button class="proof-link" onclick="viewProof('<?php echo $t['receipt_path']; ?>', '<?php echo $t['first_name'] . ' ' . $t['last_name']; ?>')">📎 View Proof</button>
                            <?php else: ?>
                                No proof uploaded
                            <?php endif; ?>
                        </td>
                        <td class="<?php echo $t['payment_verified'] ? 'verified' : 'pending'; ?>">
                            <?php echo $t['payment_verified'] ? '✅ Verified' : '⏳ Pending'; ?>
                        </td>
                        <td><?php echo $t['processed_by'] ?: '-'; ?></td>
                        <td>
                            <?php if(!$t['payment_verified']): ?>
                            <form method="POST">
                                <input type="hidden" name="transaction_id" value="<?php echo $t['transaction_id']; ?>">
                                <button type="submit" name="verify_payment" class="btn-verify">Verify Payment</button>
                            </form>
                            <?php else: ?>
                            ✓
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Transaction Log | Track all payments and proofs</p>
    </div>
</div>

<!-- Modal Popup for Viewing Proofs -->
<div id="proofModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Payment Proof</h3>
            <button class="close-modal" onclick="closeModal()">✕ Close</button>
        </div>
        <div id="modalBody" style="text-align: center;">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <a id="downloadLink" href="#" class="btn-download" download>📥 Download</a>
        </div>
    </div>
</div>

<script>
function viewProof(filePath, studentName) {
    var modal = document.getElementById('proofModal');
    var modalTitle = document.getElementById('modalTitle');
    var modalBody = document.getElementById('modalBody');
    var downloadLink = document.getElementById('downloadLink');
    
    modalTitle.innerHTML = 'Payment Proof - ' + studentName;
    downloadLink.href = filePath;
    
    var fileExtension = filePath.split('.').pop().toLowerCase();
    
    if (fileExtension === 'pdf') {
        modalBody.innerHTML = '<iframe src="' + filePath + '" class="proof-pdf"></iframe>';
    } else if (fileExtension === 'jpg' || fileExtension === 'jpeg' || fileExtension === 'png' || fileExtension === 'gif') {
        modalBody.innerHTML = '<img src="' + filePath + '" class="proof-image" alt="Payment Proof">';
    } else {
        modalBody.innerHTML = '<p>Unable to preview this file type. <a href="' + filePath + '" target="_blank">Click here to open</a></p>';
    }
    
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('proofModal').style.display = 'none';
}

// Close modal when clicking outside the content
window.onclick = function(event) {
    var modal = document.getElementById('proofModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});
</script>
</body>
</html>