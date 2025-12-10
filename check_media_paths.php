<?php
require_once 'config/config.php';

echo "<h1>Property Images Table Debugger</h1>";

try {
    $stmt = $pdo->query("SELECT * FROM property_images ORDER BY id DESC");
    $images = $stmt->fetchAll();
    
    echo "<table border='1' cellspacing='0' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Prop ID</th><th>Image URL</th><th>Is File?</th></tr>";
    
    $badCount = 0;
    foreach ($images as $img) {
        $path = $img['image_url'];
        $fullPath = __DIR__ . '/' . $path;
        $isFile = (!empty($path) && is_file($fullPath)) ? "YES" : "NO";
        
        $color = ($isFile == "YES") ? "green" : "red";
        if ($isFile == "NO") $badCount++;
        
        echo "<tr>";
        echo "<td>{$img['id']}</td>";
        echo "<td>{$img['property_id']}</td>";
        echo "<td>'" . htmlspecialchars($path) . "'</td>";
        echo "<td style='color:$color'>$isFile</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<h2>Found $badCount invalid entries.</h2>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
