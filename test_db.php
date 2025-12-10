<?php
require_once 'config/config.php';

try {
    // Test database connection
    $stmt = $pdo->query('SELECT 1');
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<h2 style='color: green;'>Database connection successful!</h2>";
    } else {
        echo "<h2 style='color: orange;'>Database connection test query failed.</h2>";
    }
    
    // Test if tables exist
    $tables = ['properties', 'property_features', 'property_images'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<p style='color: green;'>All required tables exist.</p>";
    } else {
        echo "<p style='color: red;'>Missing tables: " . implode(', ', $missingTables) . "</p>";
        echo "<p>Please import the database schema from <code>database/schema.sql</code></p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database connection failed!</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in <code>config/config.php</code></p>";
}
?>

<h3>Next Steps:</h3>
<ol>
    <li>Visit <a href='/HOUSE%20RENT/'>Homepage</a></li>
    <li>Check the <a href='/HOUSE%20RENT/properties.php'>Properties Page</a></li>
    <li>Visit the <a href='/HOUSE%20RENT/admin/'>Admin Panel</a> (if admin user is set up)</li>
</ol>

<h3>If you see database errors:</h3>
<ol>
    <li>Make sure MySQL is running in XAMPP</li>
    <li>Import the database schema from <code>database/schema.sql</code> using phpMyAdmin</li>
    <li>Check your database credentials in <code>config/config.php</code></li>
</ol>
