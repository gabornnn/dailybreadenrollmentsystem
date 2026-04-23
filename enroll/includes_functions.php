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
 * Generate unique reference number
 */
function generateReferenceNumber() {
    return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
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
?>