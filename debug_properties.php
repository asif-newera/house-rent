<?php
require_once 'config/config.php';

echo "<h2>Database Debug - Properties</h2>";

// Check if is_featured column exists
$columnCheck = $pdo->query("SHOW COLUMNS FROM properties LIKE 'is_featured'");
$hasFeaturedColumn = $columnCheck->rowCount() > 0;

echo "<p><strong>Has is_featured column:</strong> " . ($hasFeaturedColumn ? 'YES' : 'NO') . "</p>";

// Check total properties
$total = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
echo "<p><strong>Total properties:</strong> $total</p>";

// Check available properties
$available = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'available'")->fetchColumn();
echo "<p><strong>Available properties:</strong> $available</p>";

if ($hasFeaturedColumn) {
    $featured = $pdo->query("SELECT COUNT(*) FROM properties WHERE is_featured = 1")->fetchColumn();
    echo "<p><strong>Featured properties:</strong> $featured</p>";
}

// Show actual query being used
$query = "SELECT p.*, 
         (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as image_url
         FROM properties p ";
$query .= $hasFeaturedColumn ? "WHERE p.is_featured = 1 " : "WHERE p.status = 'available' ";
$query .= "ORDER BY p.created_at DESC LIMIT 6";

echo "<h3>Query being used:</h3>";
echo "<pre>" . htmlspecialchars($query) . "</pre>";

// Execute and show results
$stmt = $pdo->query($query);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Results:</h3>";
echo "<p><strong>Properties found:</strong> " . count($properties) . "</p>";

if (!empty($properties)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Price</th><th>Image URL</th></tr>";
    foreach ($properties as $prop) {
        echo "<tr>";
        echo "<td>" . $prop['id'] . "</td>";
        echo "<td>" . htmlspecialchars($prop['title']) . "</td>";
        echo "<td>" . $prop['status'] . "</td>";
        echo "<td>" . $prop['price'] . "</td>";
        echo "<td>" . htmlspecialchars($prop['image_url'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
