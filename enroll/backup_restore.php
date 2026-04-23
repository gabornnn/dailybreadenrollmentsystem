<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php?role=admin");
    exit();
}
require_once 'db_connection.php';

$success = '';
$error = '';
$backup_files = [];

// Create backups directory if not exists
$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Create backup
if(isset($_POST['create_backup'])) {
    $filename = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    try {
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $output = "-- Daily Bread Learning Center Database Backup\n";
        $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: schoolenrollmentdb\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Get create table syntax
            $create = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $create['Create Table'] . ";\n\n";
            
            // Get data
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        if ($value === null) return 'NULL';
                        return $pdo->quote($value);
                    }, array_values($row));
                    $output .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $output .= "\n";
            }
        }
        
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Write to file
        if (file_put_contents($filename, $output)) {
            $success = "Backup created successfully: " . basename($filename);
        } else {
            $error = "Failed to write backup file. Check folder permissions.";
        }
    } catch(Exception $e) {
        $error = "Backup failed: " . $e->getMessage();
    }
}

// Get backup files (only if directory exists and is readable)
if (is_dir($backup_dir) && is_readable($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $file_path = $backup_dir . $file;
            if (file_exists($file_path)) {
                $backup_files[] = [
                    'name' => $file,
                    'size' => filesize($file_path),
                    'modified' => filemtime($file_path)
                ];
            }
        }
    }
    // Sort by date (newest first)
    usort($backup_files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Download backup
if(isset($_GET['download'])) {
    $file = $backup_dir . $_GET['download'];
    if(file_exists($file) && is_readable($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    } else {
        $error = "File not found or not readable.";
    }
}

// Delete backup
if(isset($_GET['delete'])) {
    $file = $backup_dir . $_GET['delete'];
    if(file_exists($file)) {
        if(unlink($file)) {
            $success = "Backup deleted: " . $_GET['delete'];
        } else {
            $error = "Failed to delete backup file.";
        }
    } else {
        $error = "Backup file not found.";
    }
}

// Restore backup
if(isset($_POST['restore_backup'])) {
    $backup_file = $_POST['backup_file'];
    $file_path = $backup_dir . $backup_file;
    
    if(file_exists($file_path) && is_readable($file_path)) {
        // Read SQL file
        $sql = file_get_contents($file_path);
        if ($sql) {
            try {
                // Split SQL statements
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Execute each statement separately
                $statements = explode(";\n", $sql);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $success = "Database restored successfully from: " . $backup_file;
            } catch(PDOException $e) {
                $error = "Restore failed: " . $e->getMessage();
            }
        } else {
            $error = "Backup file is empty or corrupted.";
        }
    } else {
        $error = "Backup file not found!";
    }
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Admin</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .back-btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .content { background: white; padding: 25px; border-radius: 0 0 10px 10px; }
        .section { margin-bottom: 30px; }
        .section h3 { color: #2c3e50; margin-bottom: 15px; border-left: 4px solid #27ae60; padding-left: 10px; }
        
        .btn-backup { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-backup:hover { background: #219a52; }
        
        .btn-download { background: #3498db; color: white; padding: 5px 12px; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; }
        .btn-restore { background: #f39c12; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; margin: 0 2px; }
        .btn-delete { background: #e74c3c; color: white; padding: 5px 12px; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        
        .footer { background: #2c3e50; color: white; text-align: center; padding: 20px; margin-top: 20px; border-radius: 10px; }
        
        .note-box { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .note-box ul { margin-left: 20px; color: #555; }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .header { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>💾 Backup & Restore</h2>
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
    
    <div class="content">
        <?php if($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error">✗ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Create Backup Section -->
        <div class="section">
            <h3>📀 Create New Backup</h3>
            <form method="POST">
                <button type="submit" name="create_backup" class="btn-backup">Create Database Backup</button>
            </form>
        </div>
        
        <!-- Available Backups Section -->
        <div class="section">
            <h3>📋 Available Backups</h3>
            <?php if(count($backup_files) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Date Modified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($backup_files as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['name']); ?></td>
                                <td><?php echo formatFileSize($file['size']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
                                <td>
                                    <a href="?download=<?php echo urlencode($file['name']); ?>" class="btn-download">Download</a>
                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('WARNING: Restoring will replace ALL current data. Continue?');">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($file['name']); ?>">
                                        <button type="submit" name="restore_backup" class="btn-restore">Restore</button>
                                    </form>
                                    <a href="?delete=<?php echo urlencode($file['name']); ?>" class="btn-delete" onclick="return confirm('Delete this backup file?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 8px;">No backups found. Click "Create Database Backup" to create one.</p>
            <?php endif; ?>
        </div>
        
        <!-- Important Notes -->
        <div class="note-box">
            <h4>⚠️ Important Notes</h4>
            <ul>
                <li>Regular backups are recommended (daily or weekly)</li>
                <li>Backup files are stored in the <strong>'backups'</strong> folder</li>
                <li><strong style="color: #e74c3c;">Restoring will replace all current database data</strong> - this cannot be undone</li>
                <li>Download backups to save them externally for extra safety</li>
                <li>Make sure the 'backups' folder has write permissions</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>© Daily Bread Learning Center Inc. — Database Backup & Restore System</p>
    </div>
</div>
</body>
</html>