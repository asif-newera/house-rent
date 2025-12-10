<?php
/**
 * Database Schema Update for Admin Panel
 * Run this file once to create/update all necessary tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once __DIR__ . '/../config/config.php';

echo "<h2>Admin Panel Schema Update</h2>";
echo "<p>Starting database schema update...</p>";

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/admin_panel_schema.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^USE/', $stmt);
        }
    );
    
    echo "<h3>Executing SQL Statements...</h3>";
    echo "<ul>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            // Extract first 50 characters for display
            $displayStmt = substr(preg_replace('/\s+/', ' ', $statement), 0, 80) . '...';
            
            $pdo->exec($statement);
            echo "<li style='color: green;'>✓ Statement " . ($index + 1) . ": $displayStmt</li>";
            $successCount++;
        } catch (PDOException $e) {
            // Ignore "Duplicate column" and "Duplicate key" errors as they're expected
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<li style='color: orange;'>⚠ Statement " . ($index + 1) . ": Already exists (skipped)</li>";
            } else {
                echo "<li style='color: red;'>✗ Statement " . ($index + 1) . ": Error - " . htmlspecialchars($e->getMessage()) . "</li>";
                $errorCount++;
            }
        }
    }
    
    echo "</ul>";
    
    echo "<h3>Summary</h3>";
    echo "<p>✓ Successful: $successCount<br>";
    echo "✗ Errors: $errorCount</p>";
    
    // Verify tables exist
    echo "<h3>Verifying Tables...</h3>";
    $tables = ['users', 'properties', 'bookings', 'payments', 'favorites', 'login_attempts', 'activity_logs'];
    echo "<ul>";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color: green;'>✓ Table '$table' exists</li>";
        } else {
            echo "<li style='color: red;'>✗ Table '$table' is missing!</li>";
        }
    }
    
    echo "</ul>";
    
    // Check for admin user
    echo "<h3>Checking Admin User...</h3>";
    $stmt = $pdo->query("SELECT id, email, is_admin FROM users WHERE is_admin = 1 LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin user exists: " . htmlspecialchars($admin['email']) . "</p>";
        echo "<div style='background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<p><strong style='color: #856404;'>⚠️ SECURITY WARNING - Default Credentials:</strong><br>";
        echo "<strong>Email:</strong> admin@houserent.com<br>";
        echo "<strong>Password:</strong> password<br></p>";
        echo "<p style='color: #721c24; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "🔒 THIS IS A WEAK DEFAULT PASSWORD!<br>";
        echo "YOU MUST CHANGE IT IMMEDIATELY AFTER FIRST LOGIN!<br>";
        echo "Failure to change this password poses a serious security risk!";
        echo "</p></div>";
    } else {
        echo "<p style='color: red;'>✗ No admin user found. Creating one...</p>";
        $pdo->exec("INSERT INTO users (name, email, password, is_admin, is_active, role) VALUES ('Admin', 'admin@houserent.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 'admin')");
        echo "<p style='color: green;'>✓ Admin user created successfully</p>";
        echo "<div style='background-color: #f8d7da; border: 2px solid #dc3545; border-radius: 5px; padding: 15px; margin: 10px 0;'>";
        echo "<p style='color: #721c24; font-weight: bold;'>🔒 IMPORTANT: Change the default password 'password' immediately after first login!</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Database Schema Update Completed!</h2>";
    echo "<p><a href='../admin/login.php'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
