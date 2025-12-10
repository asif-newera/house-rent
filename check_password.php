<?php
$hash = '$2y$10$4ID0jw27NFKYT...'; // The one from output, wait, I need the FULL hash from DB to check properly.
// Correct approach: Fetch it again and check in the same script.

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'house_rent';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT password FROM users WHERE email = 'admin@example.com'");
    $stored_hash = $stmt->fetchColumn();
    
    $candidates = ['password', '123456', 'admin', '12345678', 'admin123'];
    $found = false;
    
    foreach ($candidates as $candidate) {
        if (password_verify($candidate, $stored_hash)) {
            echo "MATCH_FOUND: " . $candidate;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "NO_MATCH";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
