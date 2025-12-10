<?php
require_once 'config/config.php';

try {
    $stmt = $pdo->query("DESCRIBE property_images");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table: property_images\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
