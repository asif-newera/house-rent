<?php

class AddIsFeaturedToProperties {
    public function up($pdo) {
        try {
            // Add is_featured column
            $sql = "ALTER TABLE properties ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER status";
            $pdo->exec($sql);
            
            // Add index for better query performance
            $pdo->exec("CREATE INDEX idx_is_featured ON properties(is_featured)");
            
            // Mark some existing properties as featured (optional)
            $pdo->exec("UPDATE properties SET is_featured = 1 WHERE id % 3 = 0"); // Every 3rd property
            
            return true;
        } catch (PDOException $e) {
            // Check if column already exists
            if (strpos($e->getMessage(), 'duplicate column name') === false) {
                throw $e;
            }
            return true;
        }
    }
    
    public function down($pdo) {
        // Remove the column if needed
        try {
            $pdo->exec("ALTER TABLE properties DROP COLUMN is_featured");
        } catch (PDOException $e) {
            // Column might not exist
            if (strpos($e->getMessage(), 'check that column/key exists') === false) {
                throw $e;
            }
        }
        return true;
    }
}

// Run migration if executed directly
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'run') {
    require_once __DIR__ . '/../../config/config.php';
    $migration = new AddIsFeaturedToProperties();
    $migration->up($pdo);
    echo "Migration completed successfully.\n";
}
