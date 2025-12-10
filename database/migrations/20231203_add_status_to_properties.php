<?php
$migration = new class {
    public function up($pdo) {
        // Skip session and CSRF initialization in CLI mode
        if (php_sapi_name() !== 'cli') {
            require_once __DIR__ . '/../../config/config.php';
        }

        // Add status column to properties table
        $pdo->exec("ALTER TABLE properties ADD COLUMN status ENUM('available', 'rented', 'maintenance') DEFAULT 'available'");
        
        return true;
    }

    public function down($pdo) {
        $pdo->exec("ALTER TABLE properties DROP COLUMN status");
        return true;
    }
};

return $migration;
