<?php
// Database configuration for XAMPP (MySQL)
$host = 'localhost';
$dbname = 'schoolenrollmentdb';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional: Uncomment to test connection
    // echo "Connected successfully to database";
    
} catch(PDOException $e) {
    // If connection fails, show error and stop script
    die("Connection failed: " . $e->getMessage());
}
?>