<?php
// Start session
session_start();

// Include configuration
try {
    require_once __DIR__ . '/config/config.php';
    
    echo "<h1>Database Connection Test</h1>";
    
    // Test database connection
    if (isset($pdo)) {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";
        
        // Test query
        try {
            $stmt = $pdo->query("SELECT 1");
            $result = $stmt->fetch();
            echo "<p style='color: green;'>✅ Test query executed successfully</p>";
            
            // Check if tables exist
            $tables = ['properties', 'users', 'property_images'];
            foreach ($tables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                $exists = $stmt->rowCount() > 0;
                $status = $exists ? '✅' : '❌';
                echo "<p>{$status} Table '{$table}' " . ($exists ? 'exists' : 'does not exist') . "</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Database connection failed - $pdo is not set</p>";
    }
    
} catch (Exception $e) {
    echo "<h1>Error in config.php</h1>";
    echo "<p style='color: red;'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test session
$_SESSION['test'] = 'test';
echo "<h2>Session Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session test value: " . ($_SESSION['test'] ?? 'Not set') . "</p>";
?>

<h2>PHP Info</h2>
<p><a href="phpinfo.php">View PHP Info</a> (if available)</p>

<h2>Next Steps</h2>
<ol>
    <li>Check the <a href="/HOUSE%20RENT/">Homepage</a></li>
    <li>Check the <a href="/phpmyadmin/">phpMyAdmin</a> to verify database</li>
    <li>Check Apache error logs at: C:\xampp\apache\logs\error.log</li>
</ol>
