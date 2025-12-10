<?php
// Load configuration
require_once __DIR__ . '/config/config.php';

// Include the migration file
require_once __DIR__ . '/database/migrations/20231202_add_is_featured_to_properties.php';

// Run the migration
$migration = new AddIsFeaturedToProperties();
try {
    if ($migration->up($pdo)) {
        echo "Migration completed successfully.\n";
    } else {
        echo "Migration failed.\n";
    }
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
