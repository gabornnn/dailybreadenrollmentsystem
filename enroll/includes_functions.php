<?php
// includes/functions.php

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Generate alphanumeric reference number (Panelist Request)
 * Format: DB2026-A1B2C3
 */
function generateReferenceNumber() {
    $prefix = 'DB';
    $year = date('Y');
    $random = strtoupper(substr(uniqid(), -6));
    return $prefix . $year . '-' . $random;
}

/**
 * Log activity
 */
function logActivity($pdo, $user_id, $action, $details) {
    $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data) VALUES (?, ?, 'system', 0, ?, ?)");
    $stmt->execute([$user_id, $action, $action, $details]);
}

/**
 * Get system setting
 */
function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : null;
}

/**
 * Update system setting
 */
function updateSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Send email notification
 */
function sendNotification($to, $subject, $message) {
    // For now, just log (implement actual email later)
    error_log("EMAIL TO: $to | SUBJECT: $subject | MESSAGE: $message");
    return true;
}

/**
 * Calculate age from birthdate
 */
function calculateAge($birthdate) {
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthdate));
    return $diff->y;
}

/**
 * Validate age for preschool (3-6 years old)
 */
function isValidPreschoolAge($birthdate) {
    $age = calculateAge($birthdate);
    return ($age >= 3 && $age <= 6);
}

/**
 * Create backup of database
 */
function createBackup($pdo, $backup_path = 'backups/') {
    if (!is_dir($backup_path)) {
        mkdir($backup_path, 0777, true);
    }
    
    $filename = $backup_path . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Daily Bread Learning Center Database Backup\n";
    $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Get create table syntax
        $create = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";
        
        // Get data
        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map(function($value) use ($pdo) {
                return $value === null ? 'NULL' : $pdo->quote($value);
            }, array_values($row));
            $output .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
        }
        $output .= "\n";
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    file_put_contents($filename, $output);
    return $filename;
}
/**
 * Validate Philippine mobile number
 */
function isValidPhoneNumber($phone) {
    $pattern = '/^(09|\+639)\d{9}$/';
    return preg_match($pattern, $phone);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Get student full name
 */
function getStudentFullName($student) {
    return htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
}

/**
 * Get all system settings as array
 */
function getAllSettings($pdo) {
    $settings = [];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Validate file upload (type and size)
 */
function validatePaymentProof($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed'];
    }
    
    $file_type = mime_content_type($file['tmp_name']);
    $file_size = $file['size'];
    
    if(!in_array($file_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Only JPG, PNG, and PDF files are allowed'];
    }
    
    if($file_size > $max_size) {
        return ['valid' => false, 'error' => 'File size must be less than 5MB'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Upload payment proof
 */
function uploadPaymentProof($file, $student_id) {
    $target_dir = "uploads/payment_proofs/";
    if(!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "payment_" . $student_id . "_" . time() . "." . $file_extension;
    $filepath = $target_dir . $filename;
    
    if(move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

/**
 * Auto-archive student when dropped
 */
function autoArchiveOnDrop($pdo, $enrollee_id, $reason) {
    $stmt = $pdo->prepare("UPDATE enrollees SET is_archived = 1, archived_date = CURDATE(), archive_reason = ?, enrollment_status = 'Dropped' WHERE enrollee_id = ?");
    return $stmt->execute([$reason, $enrollee_id]);
}

/**
 * Restore archived student
 */
function restoreArchivedStudent($pdo, $enrollee_id) {
    $stmt = $pdo->prepare("UPDATE enrollees SET is_archived = 0, archived_date = NULL, archive_reason = NULL, enrollment_status = 'Pending' WHERE enrollee_id = ?");
    return $stmt->execute([$enrollee_id]);
}

/**
 * Filter students by reason (for archive page)
 */
function getArchivedByReason($pdo, $reason = null) {
    $sql = "SELECT * FROM enrollees WHERE is_archived = 1";
    if($reason) {
        $sql .= " AND archive_reason = :reason";
    }
    $sql .= " ORDER BY archived_date DESC";
    
    $stmt = $pdo->prepare($sql);
    if($reason) {
        $stmt->execute(['reason' => $reason]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>