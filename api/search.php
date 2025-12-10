<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

// Get search parameters
$location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;
$bedrooms = isset($_GET['bedrooms']) ? intval($_GET['bedrooms']) : null;
$bathrooms = isset($_GET['bathrooms']) ? intval($_GET['bathrooms']) : null;
$property_type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$sort_by = isset($_GET['sort_by']) ? $conn->real_escape_string($_GET['sort_by']) : 'created_at';
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build the base query
$query = "SELECT p.*, 
          (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
          GROUP_CONCAT(CONCAT(pf.feature_name, ':', COALESCE(pf.feature_value, '1'))) as features
          FROM properties p
          LEFT JOIN property_features pf ON p.id = pf.property_id
          WHERE 1=1";

$params = [];
$types = '';

// Add search conditions
if (!empty($location)) {
    $query .= " AND (p.location LIKE ? OR p.title LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$location%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if ($min_price > 0) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price < PHP_FLOAT_MAX) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

if ($bedrooms !== null) {
    $query .= " AND p.bedrooms = ?";
    $params[] = $bedrooms;
    $types .= 'i';
}

if ($bathrooms !== null) {
    $query .= " AND p.bathrooms = ?";
    $params[] = $bathrooms;
    $types .= 'i';
}

if (!empty($property_type)) {
    $query .= " AND p.property_type = ?";
    $params[] = $property_type;
    $types .= 's';
}

// Group by property and add order by and limit
$query .= " GROUP BY p.id";

// Add sorting
$valid_sort_columns = ['price', 'created_at', 'area'];
if (in_array($sort_by, $valid_sort_columns)) {
    $query .= " ORDER BY p.{$sort_by} {$sort_order}";
} else {
    $query .= " ORDER BY p.created_at DESC";
}

// Add pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$properties = [];
while ($row = $result->fetch_assoc()) {
    // Process features
    $features = [];
    if (!empty($row['features'])) {
        $featurePairs = explode(',', $row['features']);
        foreach ($featurePairs as $pair) {
            list($key, $value) = explode(':', $pair, 2);
            $features[$key] = $value === '1' ? true : $value;
        }
    }
    $row['features'] = $features;
    
    // Get all images for the property
    $imageStmt = $conn->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
    $imageStmt->bind_param("i", $row['id']);
    $imageStmt->execute();
    $imageResult = $imageStmt->get_result();
    $images = [];
    while ($image = $imageResult->fetch_assoc()) {
        $images[] = $image['image_url'];
    }
    $row['images'] = $images;
    
    $properties[] = $row;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(DISTINCT p.id) as total FROM properties p WHERE 1=1";
if (!empty($location)) {
    $countQuery .= " AND (p.location LIKE '%$location%' OR p.title LIKE '%$location%' OR p.description LIKE '%$location%')";
}
if ($min_price > 0) {
    $countQuery .= " AND p.price >= $min_price";
}
if ($max_price < PHP_FLOAT_MAX) {
    $countQuery .= " AND p.price <= $max_price";
}
if ($bedrooms !== null) {
    $countQuery .= " AND p.bedrooms = $bedrooms";
}
if ($bathrooms !== null) {
    $countQuery .= " AND p.bathrooms = $bathrooms";
}
if (!empty($property_type)) {
    $countQuery .= " AND p.property_type = '$property_type'";
}

$totalResult = $conn->query($countQuery);
$total = $totalResult->fetch_assoc()['total'];

// Prepare response
$response = [
    'status' => 'success',
    'data' => [
        'properties' => $properties,
        'pagination' => [
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ],
        'filters' => [
            'location' => $location,
            'min_price' => $min_price,
            'max_price' => $max_price < PHP_FLOAT_MAX ? $max_price : null,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'type' => $property_type,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ]
    ]
];

echo json_encode($response);
?>
