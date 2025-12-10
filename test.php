<?php
// Test PHP is working
echo "<h1>PHP is working!</h1>";

// Test database connection
try {
    require_once 'config/config.php';
    
    // Test database connection
    $stmt = $pdo->query('SELECT 1');
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Database test query failed.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check your database configuration in <code>config/config.php</code></p>";
}

// Test session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['test'] = 'test';
if (isset($_SESSION['test'])) {
    echo "<p style='color: green;'>✅ Sessions are working!</p>";
} else {
    echo "<p style='color: red;'>❌ Sessions are not working.</p>";
}
?>

<h2>Next Steps:</h2>
<ol>
    <li>Check the <a href='test_db.php'>Database Test Page</a></li>
    <li>Visit the <a href='index.php'>Homepage</a></li>
    <li>Check the <a href='properties.php'>Properties Page</a></li>
</ol>

<h3>If you see database errors:</h3>
<ol>
    <li>Make sure MySQL is running in XAMPP</li>
    <li>Import the database schema from <code>database/schema.sql</code> using phpMyAdmin</li>
    <li>Check your database credentials in <code>config/config.php</code></li>
</ol>
