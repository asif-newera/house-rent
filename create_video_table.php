<?php
require_once 'config/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS property_videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        video_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "Table property_videos created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
