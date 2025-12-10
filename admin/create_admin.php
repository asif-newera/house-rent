<?php
require_once __DIR__ . '/../config/config.php';

// Only allow this script to run from command line or localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied');
}

try {
    // Check if users table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create users table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                is_admin TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        echo "Created users table\n";
    }
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    
    if ($stmt->rowCount() === 0) {
        // Create admin user
        $name = 'Admin User';
        $email = 'admin@example.com';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $is_admin = 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, is_admin, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([$name, $email, $password, $is_admin]);
        
        echo "Admin user created successfully!\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
        echo "\nIMPORTANT: Change this password after first login!\n";
    } else {
        // Update existing admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, is_admin = 1, updated_at = NOW() 
            WHERE email = ?
        ");
        
        $stmt->execute([$password, 'admin@example.com']);
        
        echo "Admin user updated successfully!\n";
        echo "Email: admin@example.com\n";
        echo "Password: admin123\n";
        echo "\nIMPORTANT: Change this password after login!\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nAdmin setup completed. You can now log in to the admin panel.\n";
