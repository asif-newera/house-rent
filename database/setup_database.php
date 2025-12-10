<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'house_rent');

?>
<! DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        h1 { color: #4e73df; }
        .success { color: #1cc88a; padding: 10px; background: #d4edda; border-left: 4px solid #1cc88a; margin: 10px 0; }
        .error { color: #e74a3b; padding: 10px; background: #f8d7da; border-left: 4px solid #e74a3b; margin: 10px 0; }
        .info { color: #36b9cc; padding: 10px; background: #d1ecf1; border-left: 4px solid #36b9cc; margin: 10px 0; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        button { background: #4e73df; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2e59d9; }
        pre { background: #f8f9fc; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🏠 House Rent Database Setup</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<div class='box'>";
        echo "<h2>Setting up database...</h2>";
        
        try {
            // Connect without database first
            $pdo = new PDO("mysql:host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>✓ Connected to MySQL server</div>";
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_DATABASE . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<div class='success'>✓ Database '" . DB_DATABASE . "' created/verified</div>";
            
            // Connect to database
            $pdo = new PDO("mysql:host=" . DB_HOST .  ";dbname=" . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>✓ Connected to database</div>";
            
            // Read SQL file
            $sqlFile = __DIR__ . '/complete_schema.sql';
            if (! file_exists($sqlFile)) {
                throw new Exception("SQL file not found: complete_schema.sql");
            }
            
            $sql = file_get_contents($sqlFile);
            echo "<div class='success'>✓ SQL file loaded</div>";
            
            // Execute SQL
            $pdo->exec($sql);
            echo "<div class='success'>✓ Database schema created successfully! </div>";
            
            // Verify tables
            echo "<h3>Verifying Tables:</h3>";
            $tables = ['users', 'properties', 'bookings', 'payments', 'favorites', 'messages'];
            foreach ($tables as $table) {
                $result = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    echo "<div class='success'>✓ Table '$table' exists (Records: $count)</div>";
                } else {
                    echo "<div class='error'>✗ Table '$table' missing! </div>";
                }
            }
            
            // Check admin user
            echo "<h3>Admin User:</h3>";
            $stmt = $pdo->query("SELECT email, name FROM users WHERE is_admin = 1 LIMIT 1");
            $admin = $stmt->fetch();
            if ($admin) {
                echo "<div class='info'>";
                echo "<strong>Admin Login Credentials:</strong><br>";
                echo "Email: " . htmlspecialchars($admin['email']) . "<br>";
                echo "Password: admin123<br>";
                echo "<br><strong>⚠️ IMPORTANT: Change this password after first login!</strong>";
                echo "</div>";
            } else {
                echo "<div class='error'>✗ No admin user found!</div>";
            }
            
            echo "<h2 style='color: #1cc88a;'>🎉 Database Setup Complete!</h2>";
            echo "<div class='info'>";
            echo "<strong>Next Steps:</strong><br>";
            echo "1. Go to <a href='../admin/login.php'>Admin Login</a><br>";
            echo "2. Login with admin@example.com / admin123<br>";
            echo "3. Change your password immediately<br>";
            echo "4. Start managing your properties!";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>✗ Error:</strong> " .  htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        ?>
        <div class='box'>
            <h2>Ready to Setup Database</h2>
            <div class='info'>
                <strong>This script will:</strong>
                <ul>
                    <li>Create database '<?= DB_DATABASE ?>' if it doesn't exist</li>
                    <li>Create all required tables (users, properties, bookings, payments, etc.)</li>
                    <li>Set up foreign keys and indexes</li>
                    <li>Create default admin user</li>
                    <li>Add sample data (optional)</li>
                </ul>
                <p><strong>Note:</strong> Existing tables will not be dropped.  Safe to run multiple times.</p>
            </div>
            
            <form method="POST">
                <button type="submit">🚀 Setup Database Now</button>
            </form>
        </div>
        <?php
    }
    ?>
    
</body>
</html>