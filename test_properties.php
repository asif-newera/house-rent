<?php
// Test script for Properties API

// Test GET all properties
echo "Testing GET /api/properties.php\n";
$response = file_get_contents('http://localhost/HOUSE%20RENT/api/properties.php');
$data = json_decode($response, true);
echo "Status: " . $data['status'] . "\n";
echo "Found " . count($data['data']) . " properties\n\n";

// Test GET single property
if (!empty($data['data'])) {
    $firstId = $data['data'][0]['id'];
    echo "Testing GET /api/properties.php?id={$firstId}\n";
    $response = file_get_contents("http://localhost/HOUSE%20RENT/api/properties.php?id={$firstId}");
    $property = json_decode($response, true);
    echo "Status: " . $property['status'] . "\n";
    if ($property['status'] === 'success') {
        echo "Property found: " . $property['data']['title'] . "\n";
        echo "Price: $" . number_format($property['data']['price']) . "\n";
        echo "Bedrooms: " . $property['data']['bedrooms'] . "\n";
        echo "Bathrooms: " . $property['data']['bathrooms'] . "\n\n";
    }
}

// Test search with filters
echo "Testing search with filters (apartments under $2000)\n";
$filters = http_build_query([
    'property_type' => 'apartment',
    'max_price' => 2000,
    'available' => 1
]);
$response = file_get_contents("http://localhost/HOUSE%20RENT/api/properties.php?{$filters}");
$result = json_decode($response, true);
echo "Found " . count($result['data']) . " matching properties\n\n";

// Test error handling
echo "Testing error handling (invalid ID)\n";
$response = file_get_contents('http://localhost/HOUSE%20RENT/api/properties.php?id=999999');
$error = json_decode($response, true);
echo "Status: " . $error['status'] . "\n";
echo "Message: " . $error['message'] . "\n\n";

echo "Tests completed.\n";
?>
