<?php
// Debug script for login issues
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'house_rent';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check User Status
    echo "--- User Status ---\n";
    $stmt = $pdo->prepare("SELECT id, email, is_active, is_admin, password FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found: Yes\n";
        echo "Active: " . $user['is_active'] . "\n";
        echo "Is Admin: " . $user['is_admin'] . "\n";
        // Verify password again just to be 1000% sure
        echo "Password 'admin123' valid: " . (password_verify('admin123', $user['password']) ? 'YES' : 'NO') . "\n";
    } else {
        echo "User found: No\n";
    }
    
    // Check Lockouts
    echo "\n--- Lockouts ---\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute(['admin@example.com']);
    $attempts = $stmt->fetchColumn();
    echo "Recent Failed Attempts: " . $attempts . "\n";
    
    if ($attempts >= 5) {
        echo "STATUS: ACCOUNT LOCKED\n";
    } else {
        echo "STATUS: OK (Not locked)\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
