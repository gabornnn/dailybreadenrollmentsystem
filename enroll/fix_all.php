<?php
require_once 'db_connection.php';

echo "<h1>Complete Login Fix</h1>";

try {
    // Method 1: Disable foreign key checks completely
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✅ Foreign key checks disabled<br>";
    
    // Delete audit log first
    $pdo->exec("DELETE FROM audit_log");
    echo "✅ Deleted audit_log entries<br>";
    
    // Now truncate users
    $pdo->exec("TRUNCATE TABLE users");
    echo "✅ Truncated users table<br>";
    
    // Insert admin
    $admin_hash = hash('sha256', 'Admin123');
    $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)")->execute([
        'admin', $admin_hash, 'System Administrator', 'admin@dailybread.edu.ph', 'admin'
    ]);
    echo "✅ Admin created: admin / Admin123<br>";
    
    // Insert registrar
    $registrar_hash = hash('sha256', 'Registrar123');
    $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)")->execute([
        'registrar', $registrar_hash, 'Registrar Office', 'registrar@dailybread.edu.ph', 'registrar'
    ]);
    echo "✅ Registrar created: registrar / Registrar123<br>";
    
    // Insert cashier
    $cashier_hash = hash('sha256', 'Cashier123');
    $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)")->execute([
        'cashier', $cashier_hash, 'Cashier Office', 'cashier@dailybread.edu.ph', 'cashier'
    ]);
    echo "✅ Cashier created: cashier / Cashier123<br>";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Foreign key checks re-enabled<br>";
    
    echo "<hr>";
    echo "<h2>Current Users in Database:</h2>";
    $users = $pdo->query("SELECT user_id, username, role FROM users")->fetchAll();
    if(count($users) > 0) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
        foreach($users as $u) {
            echo "<tr>";
            echo "<td>{$u['user_id']}</td>";
            echo "<td>{$u['username']}</td>";
            echo "<td>{$u['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>No users found! Something went wrong.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Test Login Now:</h3>";
    echo "<ul>";
    echo "<li><a href='login.php?role=admin'>Admin Login</a> - Username: admin, Password: Admin123</li>";
    echo "<li><a href='login.php?role=registrar'>Registrar Login</a> - Username: registrar, Password: Registrar123</li>";
    echo "<li><a href='login.php?role=cashier'>Cashier Login</a> - Username: cashier, Password: Cashier123</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Trying alternative method...<br>";
    
    // Alternative method: Drop and recreate foreign key
    try {
        // Remove foreign key constraint
        $pdo->exec("ALTER TABLE audit_log DROP FOREIGN KEY audit_log_ibfk_1");
        echo "✅ Dropped foreign key constraint<br>";
        
        // Now truncate users
        $pdo->exec("TRUNCATE TABLE users");
        echo "✅ Truncated users table<br>";
        
        // Insert users
        $admin_hash = hash('sha256', 'Admin123');
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)")->execute([
            'admin', $admin_hash, 'System Administrator', 'admin@dailybread.edu.ph', 'admin'
        ]);
        
        $registrar_hash = hash('sha256', 'Registrar123');
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)")->execute([
            'registrar', $registrar_hash, 'Registrar Office', 'registrar@dailybread.edu.ph', 'registrar'
        ]);
        
        $cashier_hash = hash('sha256', 'Cashier123');
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)")->execute([
            'cashier', $cashier_hash, 'Cashier Office', 'cashier@dailybread.edu.ph', 'cashier'
        ]);
        
        // Re-add foreign key
        $pdo->exec("ALTER TABLE audit_log ADD FOREIGN KEY (user_id) REFERENCES users(user_id)");
        echo "✅ Re-added foreign key constraint<br>";
        
        echo "✅ All fixed!<br>";
        
    } catch(PDOException $e2) {
        echo "Alternative also failed: " . $e2->getMessage();
    }
}
?>