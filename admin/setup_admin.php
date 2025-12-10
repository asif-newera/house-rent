<?php
require_once __DIR__ . '/../config/config.php';

// Check if users table exists
$tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;

if (!$tableExists) {
    // Create users table
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
} else {
    // Check and add missing columns if table exists
    $columns = [
        'is_active' => "ADD COLUMN is_active TINYINT(1) DEFAULT 1",
        'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    foreach ($columns as $column => $alter) {
        try {
            $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'")->rowCount() === 0;
            if ($check) {
                $pdo->exec("ALTER TABLE users $alter");
                echo "Added column: $column<br>";
            }
        } catch (PDOException $e) {
            echo "Error with column $column: " . $e->getMessage() . "<br>";
        }
    }
}

// Create admin user if not exists
$email = 'admin@example.com';
$password = password_hash('admin123', PASSWORD_DEFAULT);

// First, check if the user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Update existing user - only set password and admin status
    $updateFields = [
        'password' => $password,
        'is_admin' => 1,
        'is_active' => 1
    ];
    
    // Only add updated_at if the column exists
    $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'")->rowCount() > 0;
    if ($columnCheck) {
        $updateFields['updated_at'] = 'NOW()';
    }
    
    $setClause = implode(' = ?, ', array_keys($updateFields)) . ' = ?';
    $values = array_values($updateFields);
    $values[] = $user['id'];
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET $setClause
        WHERE id = ?
    ");
    $stmt->execute($values);
} else {
    // Insert new admin user
    $columns = ['name', 'email', 'password', 'is_admin', 'is_active', 'created_at'];
    $values = ['Admin User', $email, $password, 1, 1, 'NOW()'];
    
    // Only add updated_at if the column exists
    $columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'updated_at'")->rowCount() > 0;
    if ($columnCheck) {
        $columns[] = 'updated_at';
        $values[] = 'NOW()';
    }
    
    $placeholders = str_repeat('?,', count($values) - 1) . '?';
    $columnsStr = implode(', ', $columns);
    
    $stmt = $pdo->prepare("
        INSERT INTO users ($columnsStr)
        VALUES ($placeholders)
    ");
    $stmt->execute($values);
}

echo "Admin user created/updated successfully!<br>";
echo "Email: admin@example.com<br>";
echo "Password: admin123<br>";
echo "<br>IMPORTANT: Change this password after first login!";