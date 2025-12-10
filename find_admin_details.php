<?php
// Simple script to find admin users
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'house_rent';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, email, password FROM users WHERE is_admin = 1");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "No admin users found.\n";
    } else {
        echo "Found " . count($admins) . " admin(s):\n";
        foreach ($admins as $admin) {
            echo "ID: " . $admin['id'] . "\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Password Hash: " . substr($admin['password'], 0, 20) . "...\n";
            echo "-------------------\n";
        }
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
