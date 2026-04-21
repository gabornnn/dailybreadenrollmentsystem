<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php?role=cashier");
    exit();
}
require_once 'db_connection.php';

// Function to generate receipt number
function generateReceiptNumber($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT receipt_number FROM payment_transactions WHERE receipt_number LIKE ? ORDER BY transaction_id DESC LIMIT 1");
    $stmt->execute(["RCP-{$year}-%"]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($last) {
        $parts = explode('-', $last['receipt_number']);
        $last_num = intval(end($parts));
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    
    return "RCP-{$year}-{$new_num}";
}

if(isset($_POST['update_payment'])) {
    $enrollee_id = $_POST['enrollee_id'];
    $payment_status = $_POST['payment_status'];
    $payment_amount = $_POST['payment_amount'];
    
    // Auto-generate receipt number
    $receipt_number = generateReceiptNumber($pdo);
    
    // Get current total paid amount
    $stmt = $pdo->prepare("SELECT SUM(payment_amount) as total_paid FROM payment_transactions WHERE enrollee_id = ?");
    $stmt->execute([$enrollee_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_paid = $result['total_paid'] ?? 0;
    $new_total_paid = $current_paid + $payment_amount;
    
    // Update payment status based on total paid vs total amount
    $stmt = $pdo->prepare("SELECT payment_amount as total_fee FROM enrollees WHERE enrollee_id = ?");
    $stmt->execute([$enrollee_id]);
    $enrollee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($new_total_paid >= $enrollee['total_fee']) {
        $payment_status = 'Fully Paid';
    } elseif($new_total_paid > 0) {
        $payment_status = 'Partial';
    } else {
        $payment_status = 'Unpaid';
    }
    
    // Update enrollee payment status
    $stmt = $pdo->prepare("UPDATE enrollees SET payment_status = ? WHERE enrollee_id = ?");
    $stmt->execute([$payment_status, $enrollee_id]);
    
    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (enrollee_id, payment_date, payment_amount, payment_type, receipt_number, processed_by, processed_by_user_id) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
    $stmt->execute([$enrollee_id, $payment_amount, $payment_status, $receipt_number, $_SESSION['full_name'], $_SESSION['user_id']]);
    
    // Redirect back to cashier dashboard with success message
    header("Location: cashier_dashboard.php?success=Payment of ₱" . number_format($payment_amount, 2) . " recorded! Receipt: $receipt_number");
    exit();
} else {
    header("Location: cashier_dashboard.php");
    exit();
}
?>