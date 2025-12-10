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
    <title>Fix Missing Columns</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        h1 { color: #4e73df; }
        .success { color: #1cc88a; padding: 10px; background: #d4edda; border-left: 4px solid #1cc88a; margin: 10px 0; }
        .error { color: #e74a3b; padding: 10px; background: #f8d7da; border-left: 4px solid #e74a3b; margin: 10px 0; }
        . info { color: #36b9cc; padding: 10px; background: #d1ecf1; border-left: 4px solid #36b9cc; margin: 10px 0; }
        .warning { color: #f6c23e; padding: 10px; background: #fff3cd; border-left: 4px solid #f6c23e; margin: 10px 0; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        button { background: #4e73df; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 5px; }
        button:hover { background: #2e59d9; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4e73df; color: white; }
    </style>
</head>
<body>
    <h1>🔧 Fix Missing Database Columns</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<div class='box'>";
        echo "<h2>Fixing database structure...</h2>";
        
        try {
            // Connect to database
            $pdo = new PDO("mysql:host=" .  DB_HOST . ";dbname=" . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>✓ Connected to database</div>";
            
            // Get existing columns for properties table
            $stmt = $pdo->query("DESCRIBE properties");
            $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            echo "<h3>Properties Table - Adding Missing Columns:</h3>";
            
            // Define all required columns with their SQL
            $propertyColumns = [
                'property_type' => "ALTER TABLE properties ADD COLUMN property_type ENUM('apartment','house','villa','studio','commercial') DEFAULT 'apartment' AFTER description",
                'address' => "ALTER TABLE properties ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER property_type",
                'city' => "ALTER TABLE properties ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER location",
                'state' => "ALTER TABLE properties ADD COLUMN state VARCHAR(100) DEFAULT NULL AFTER city",
                'zip_code' => "ALTER TABLE properties ADD COLUMN zip_code VARCHAR(20) DEFAULT NULL AFTER state",
                'bedrooms' => "ALTER TABLE properties ADD COLUMN bedrooms INT(11) DEFAULT 0 AFTER price",
                'bathrooms' => "ALTER TABLE properties ADD COLUMN bathrooms INT(11) DEFAULT 0 AFTER bedrooms",
                'area' => "ALTER TABLE properties ADD COLUMN area INT(11) DEFAULT NULL COMMENT 'in square feet' AFTER bathrooms",
                'status' => "ALTER TABLE properties ADD COLUMN status ENUM('available','rented','maintenance') DEFAULT 'available' AFTER area",
                'is_featured' => "ALTER TABLE properties ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER status",
                'amenities' => "ALTER TABLE properties ADD COLUMN amenities TEXT COMMENT 'JSON format' AFTER is_featured",
                'images' => "ALTER TABLE properties ADD COLUMN images TEXT COMMENT 'JSON format' AFTER amenities",
            ];
            
            $addedCount = 0;
            $skippedCount = 0;
            
            foreach ($propertyColumns as $column => $sql) {
                if (! in_array($column, $existingColumns)) {
                    try {
                        $pdo->exec($sql);
                        echo "<div class='success'>✓ Added column: $column</div>";
                        $addedCount++;
                    } catch (PDOException $e) {
                        echo "<div class='error'>✗ Failed to add $column: " . $e->getMessage() . "</div>";
                    }
                } else {
                    echo "<div class='info'>⊙ Column '$column' already exists</div>";
                    $skippedCount++;
                }
            }
            
            // Add indexes
            echo "<h3>Adding Indexes:</h3>";
            $indexes = [
                "CREATE INDEX idx_properties_status ON properties(status)",
                "CREATE INDEX idx_properties_featured ON properties(is_featured)",
                "CREATE INDEX idx_properties_type ON properties(property_type)",
                "CREATE INDEX idx_properties_city ON properties(city)",
            ];
            
            foreach ($indexes as $indexSql) {
                try {
                    $pdo->exec($indexSql);
                    echo "<div class='success'>✓ Index added</div>";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        echo "<div class='info'>⊙ Index already exists</div>";
                    } else {
                        echo "<div class='warning'>⚠ Index issue: " . $e->getMessage() . "</div>";
                    }
                }
            }
            
            // Check if bookings table exists
            echo "<h3>Checking Required Tables:</h3>";
            $requiredTables = [
                'bookings' => "CREATE TABLE IF NOT EXISTS bookings (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    property_id INT(11) NOT NULL,
                    user_id INT(11) NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    duration_months INT(11) DEFAULT 1,
                    total_amount DECIMAL(10,2) NOT NULL,
                    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
                    notes TEXT,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_bookings_property (property_id),
                    KEY idx_bookings_user (user_id),
                    KEY idx_bookings_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'payments' => "CREATE TABLE IF NOT EXISTS payments (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    booking_id INT(11) NOT NULL,
                    user_id INT(11) NOT NULL,
                    amount_paid DECIMAL(10,2) NOT NULL,
                    payment_method ENUM('cash','card','bank_transfer','online') DEFAULT 'cash',
                    payment_date DATETIME NOT NULL,
                    transaction_id VARCHAR(100) DEFAULT NULL,
                    status ENUM('pending','completed','failed') DEFAULT 'pending',
                    notes TEXT,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_payments_booking (booking_id),
                    KEY idx_payments_user (user_id),
                    KEY idx_payments_date (payment_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'favorites' => "CREATE TABLE IF NOT EXISTS favorites (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    user_id INT(11) NOT NULL,
                    property_id INT(11) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_favorite (user_id, property_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'messages' => "CREATE TABLE IF NOT EXISTS messages (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    phone VARCHAR(20) DEFAULT NULL,
                    property_id INT(11) DEFAULT NULL,
                    message TEXT NOT NULL,
                    status ENUM('new','read','replied') DEFAULT 'new',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_messages_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            foreach ($requiredTables as $table => $sql) {
                $result = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    echo "<div class='success'>✓ Table '$table' exists (Records: $count)</div>";
                } else {
                    try {
                        $pdo->exec($sql);
                        echo "<div class='success'>✓ Created table: $table</div>";
                    } catch (PDOException $e) {
                        echo "<div class='error'>✗ Failed to create $table: " . $e->getMessage() . "</div>";
                    }
                }
            }
            
            // Show final structure
            echo "<h3>Final Properties Table Structure:</h3>";
            $stmt = $pdo->query("DESCRIBE properties");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h2 style='color: #1cc88a;'>🎉 Database Structure Fixed!</h2>";
            echo "<div class='info'>";
            echo "<strong>Summary:</strong><br>";
            echo "• Columns added: $addedCount<br>";
            echo "• Columns already existed: $skippedCount<br>";
            echo "<br><strong>Next Steps:</strong><br>";
            echo "1. <a href='../admin/login. php'>Go to Admin Login</a><br>";
            echo "2. Login with: admin@example.com / admin123<br>";
            echo "3. Your dashboard should now load properly!";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>✗ Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        // Show current status
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<div class='box'>";
            echo "<h2>Current Database Status</h2>";
            
            // Check properties table
            $stmt = $pdo->query("DESCRIBE properties");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            echo "<h3>Properties Table - Current Columns:</h3>";
            echo "<table>";
            echo "<tr><th>Column</th><th>Status</th></tr>";
            
            $requiredColumns = ['property_type', 'address', 'city', 'bedrooms', 'bathrooms', 'area', 'status', 'is_featured', 'amenities', 'images'];
            
            $missingCount = 0;
            foreach ($requiredColumns as $col) {
                if (in_array($col, $columnNames)) {
                    echo "<tr><td>$col</td><td style='color:#1cc88a;'>✓ Exists</td></tr>";
                } else {
                    echo "<tr><td>$col</td><td style='color:#e74a3b;'>✗ Missing</td></tr>";
                    $missingCount++;
                }
            }
            echo "</table>";
            
            if ($missingCount > 0) {
                echo "<div class='warning'>⚠ Found $missingCount missing columns. Click below to fix.</div>";
            } else {
                echo "<div class='success'>✓ All required columns exist! </div>";
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>Could not check database: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        <div class='box'>
            <h2>Ready to Fix Database</h2>
            <div class='info'>
                <strong>This script will:</strong>
                <ul>
                    <li>Add all missing columns to properties table</li>
                    <li>Create missing tables (bookings, payments, favorites, messages)</li>
                    <li>Add necessary indexes</li>
                    <li>Keep all your existing data safe</li>
                </ul>
                <p><strong>Safe to run:</strong> Only adds missing columns/tables, doesn't delete anything.</p>
            </div>
            
            <form method="POST">
                <button type="submit">🔧 Fix Database Structure Now</button>
            </form>
        </div>
        <? php
    }
    ?>
    
</body>
</html>