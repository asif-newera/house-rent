<?php
require_once 'config/config.php';

echo "<h1>Cleaning Bad Image Paths</h1>";

try {
    // Select bad paths (those not starting with assets/images/properties/ or just general check)
    // Actually, we can check file existence.
    $stmt = $pdo->query("SELECT id, image_url FROM properties");
    $properties = $stmt->fetchAll();
    
    $count = 0;
    foreach ($properties as $p) {
        $path = $p['image_url'];
        if (empty($path)) continue;
        
        $fullPath = __DIR__ . '/' . $path;
        if (!is_file($fullPath)) {
            // Found bad path
            $update = $pdo->prepare("UPDATE properties SET image_url = NULL WHERE id = ?");
            $update->execute([$p['id']]);
            echo "Cleared invalid path for Property {$p['id']}: '$path'<br>";
            $count++;
        }
    }
    
    echo "<h2>Cleaned $count properties.</h2>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
