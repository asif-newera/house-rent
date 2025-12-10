<?php
require_once 'config/config.php';

try {
    $stmt = $pdo->query("SELECT * FROM property_images LIMIT 5");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total images found: " . count($images) . "\n";
    print_r($images);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
