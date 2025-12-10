<?php
// Include configuration and functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Check if user is logged in and is admin for write operations
function isAuthorized() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return true;
}

// Initialize response
$response = ['status' => 'error', 'message' => 'Invalid request'];

try {
    // Get request data
    $data = [];
    if ($method !== 'GET') {
        $input = file_get_contents("php://input");
        if ($input) {
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
        }
    }

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

// Check if user is logged in and is admin for write operations
function isAuthorized() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Get request data
$data = [];
if ($method !== 'GET') {
    $input = file_get_contents("php://input");
    if (!empty($input)) {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = [];
        }
    }
}

// Helper function to get property by ID
function getPropertyById($id) {
    global $pdo;
    
    try {
        // Get property basic info
        $sql = "SELECT p.*, 
               (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               u.name as owner_name, u.email as owner_email, u.phone as owner_phone
               FROM properties p 
               LEFT JOIN users u ON p.owner_id = u.id 
               WHERE p.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($property) {
            // Get all images
            $imageStmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = :property_id");
            $imageStmt->execute([':property_id' => $id]);
            $property['images'] = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get features
            $featureStmt = $pdo->prepare("SELECT feature FROM property_features WHERE property_id = :property_id");
            $featureStmt->execute([':property_id' => $id]);
            $property['features'] = $featureStmt->fetchAll(PDO::FETCH_COLUMN);
            
            return $property;
        }
        return null;
    } catch (PDOException $e) {
        error_log("Error getting property: " . $e->getMessage());
        return null;
    }
}

// Helper function to get properties with filters
function getProperties($filters = []) {
    global $pdo;
    
    try {
        $where = [];
        $params = [];
        
        // Build WHERE clause based on filters
        if (!empty($filters['location'])) {
            $where[] = "(p.city LIKE :location OR p.address LIKE :location OR p.state LIKE :location OR p.country LIKE :location)";
            $params[':location'] = "%" . $filters['location'] . "%";
        }
        
        if (!empty($filters['min_price'])) {
            $where[] = "p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where[] = "p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['bedrooms'])) {
            $where[] = "p.bedrooms = :bedrooms";
            $params[':bedrooms'] = $filters['bedrooms'];
        }
        
        if (!empty($filters['bathrooms'])) {
            $where[] = "p.bathrooms = :bathrooms";
            $params[':bathrooms'] = $filters['bathrooms'];
        }
        
        if (!empty($filters['property_type'])) {
            $where[] = "p.property_type = :property_type";
            $params[':property_type'] = $filters['property_type'];
        }
        
        if (isset($filters['available']) && $filters['available'] === '1') {
            $where[] = "p.available = 1";
        }
        
        // Build the query
        $sql = "SELECT p.*, 
               (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               u.name as owner_name
               FROM properties p
               LEFT JOIN users u ON p.owner_id = u.id";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        // Add sorting
        $sort = !empty($filters['sort']) ? $filters['sort'] : 'created_at';
        $order = (!empty($filters['order']) && strtoupper($filters['order']) === 'ASC') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY p." . $sort . " " . $order;
        
        // Add pagination
        $page = max(1, isset($filters['page']) ? (int)$filters['page'] : 1);
        $limit = min(20, max(1, isset($filters['limit']) ? (int)$filters['limit'] : 10));
        $offset = ($page - 1) * $limit;
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM properties p" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Add limit and offset to main query
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Execute the query
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $properties,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => $limit,
                'current_page' => $page,
                'last_page' => ceil($total / $limit)
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error getting properties: " . $e->getMessage());
        return [
            'data' => [],
            'pagination' => [
                'total' => 0,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1
            ]
        ];
    }
}

// Main request handler
switch($method) {
    case 'GET':
        try {
            // Get property by ID or list all properties with filters
            if(isset($_GET['id'])) {
                // Get single property with all details
                $id = intval($_GET['id']);
                $property = getPropertyById($id);
                
                if ($property) {
                    $response = [
                        'status' => 'success',
                        'data' => $property
                    ];
                } else {
                    http_response_code(404);
                    $response = [
                        'status' => 'error',
                        'message' => 'Property not found'
                    ];
                }
            } else {
                // Get list of properties with filters
                $filters = [
                    'location' => $_GET['location'] ?? '',
                    'min_price' => $_GET['min_price'] ?? null,
                    'max_price' => $_GET['max_price'] ?? null,
                    'bedrooms' => $_GET['bedrooms'] ?? null,
                    'bathrooms' => $_GET['bathrooms'] ?? null,
                    'property_type' => $_GET['type'] ?? null,
                    'available' => $_GET['available'] ?? null,
                    'page' => $_GET['page'] ?? 1,
                    'limit' => $_GET['limit'] ?? 10,
                    'sort' => $_GET['sort'] ?? 'created_at',
                    'order' => $_GET['order'] ?? 'DESC'
                ];
                
                $result = getProperties($filters);
                $response = [
                    'status' => 'success',
                    'data' => $result['data'],
                    'pagination' => $result['pagination']
                ];
            }
        } catch (Exception $e) {
            error_log("Error in properties API: " . $e->getMessage());
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'An error occurred while processing your request',
                'error' => APP_DEBUG ? $e->getMessage() : null
            ];
        }
        break;
        
    case 'POST':
        // Create new property
        try {
            if (!isAuthorized()) {
                http_response_code(403);
                $response = ['status' => 'error', 'message' => 'Unauthorized'];
                break;
            }
            
            // Validate required fields
            $required = ['title', 'description', 'price', 'bedrooms', 'bathrooms', 'address', 'city', 'state', 'country'];
            $missing = [];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $missing[] = $field;
                }
            }
            
            if (!empty($missing)) {
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Missing required fields',
                    'missing_fields' => $missing
                ];
                break;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert property
            $sql = "INSERT INTO properties (
                title, description, price, bedrooms, bathrooms, 
                address, city, state, country, postal_code, 
                latitude, longitude, property_type, available, 
                owner_id, created_at, updated_at
            ) VALUES (
                :title, :description, :price, :bedrooms, :bathrooms, 
                :address, :city, :state, :country, :postal_code, 
                :latitude, :longitude, :property_type, :available, 
                :owner_id, NOW(), NOW()
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':price' => $data['price'],
                ':bedrooms' => $data['bedrooms'],
                ':bathrooms' => $data['bathrooms'],
                ':address' => $data['address'],
                ':city' => $data['city'],
                ':state' => $data['state'],
                ':country' => $data['country'],
                ':postal_code' => $data['postal_code'] ?? null,
                ':latitude' => $data['latitude'] ?? null,
                ':longitude' => $data['longitude'] ?? null,
                ':property_type' => $data['property_type'] ?? 'apartment',
                ':available' => isset($data['available']) ? (int)$data['available'] : 1,
                ':owner_id' => $_SESSION['user_id']
            ]);
            
            $propertyId = $pdo->lastInsertId();
            
            // Handle features
            if (!empty($data['features']) && is_array($data['features'])) {
                $featureStmt = $pdo->prepare("INSERT INTO property_features (property_id, feature) VALUES (:property_id, :feature)");
                foreach ($data['features'] as $feature) {
                    $featureStmt->execute([
                        ':property_id' => $propertyId,
                        ':feature' => $feature
                    ]);
                }
            }
            
            // Handle images (if any)
            if (!empty($data['images']) && is_array($data['images'])) {
                $imageStmt = $pdo->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (:property_id, :image_url, :is_primary)");
                foreach ($data['images'] as $index => $imageUrl) {
                    $imageStmt->execute([
                        ':property_id' => $propertyId,
                        ':image_url' => $imageUrl,
                        ':is_primary' => $index === 0 ? 1 : 0
                    ]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Get the created property
            $property = getPropertyById($propertyId);
            
            $response = [
                'status' => 'success',
                'message' => 'Property created successfully',
                'data' => $property
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Error creating property: " . $e->getMessage());
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to create property',
                'error' => APP_DEBUG ? $e->getMessage() : null
            ];
        }
        break;
        
    case 'PUT':
        // Update property
        try {
            if (!isAuthorized()) {
                http_response_code(403);
                $response = ['status' => 'error', 'message' => 'Unauthorized'];
                break;
            }
            
            if (empty($data['id'])) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'Property ID is required'];
                break;
            }
            
            $propertyId = (int)$data['id'];
            $updates = [];
            $params = [':id' => $propertyId];
            
            // Build update query based on provided fields
            $allowedFields = [
                'title', 'description', 'price', 'bedrooms', 'bathrooms',
                'address', 'city', 'state', 'country', 'postal_code',
                'latitude', 'longitude', 'property_type', 'available'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'No fields to update'];
                break;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Update property
            $sql = "UPDATE properties SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Update features if provided
            if (isset($data['features']) && is_array($data['features'])) {
                // Delete existing features
                $pdo->prepare("DELETE FROM property_features WHERE property_id = :property_id")
                    ->execute([':property_id' => $propertyId]);
                
                // Add new features
                if (!empty($data['features'])) {
                    $featureStmt = $pdo->prepare("INSERT INTO property_features (property_id, feature) VALUES (:property_id, :feature)");
                    foreach ($data['features'] as $feature) {
                        $featureStmt->execute([
                            ':property_id' => $propertyId,
                            ':feature' => $feature
                        ]);
                    }
                }
            }
            
            // Handle new images (if any)
            if (!empty($data['new_images']) && is_array($data['new_images'])) {
                $imageStmt = $pdo->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (:property_id, :image_url, :is_primary)");
                foreach ($data['new_images'] as $imageUrl) {
                    $imageStmt->execute([
                        ':property_id' => $propertyId,
                        ':image_url' => $imageUrl,
                        ':is_primary' => 0 // Not primary by default
                    ]);
                }
            }
            
            // Handle image deletions
            if (!empty($data['deleted_images']) && is_array($data['deleted_images'])) {
                $placeholders = implode(',', array_fill(0, count($data['deleted_images']), '?'));
                $stmt = $pdo->prepare("DELETE FROM property_images WHERE id IN ($placeholders) AND property_id = ?");
                $params = array_merge($data['deleted_images'], [$propertyId]);
                $stmt->execute($params);
            }
            
            // Handle primary image update
            if (!empty($data['primary_image_id'])) {
                // First, set all images to not primary
                $pdo->prepare("UPDATE property_images SET is_primary = 0 WHERE property_id = ?")
                    ->execute([$propertyId]);
                
                // Then set the selected one as primary
                $pdo->prepare("UPDATE property_images SET is_primary = 1 WHERE id = ? AND property_id = ?")
                    ->execute([$data['primary_image_id'], $propertyId]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Get the updated property
            $property = getPropertyById($propertyId);
            
            $response = [
                'status' => 'success',
                'message' => 'Property updated successfully',
                'data' => $property
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Error updating property: " . $e->getMessage());
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to update property',
                'error' => APP_DEBUG ? $e->getMessage() : null
            ];
        }
        break;
        
    case 'DELETE':
        // Delete property
        try {
            if (!isAuthorized()) {
                http_response_code(403);
                $response = ['status' => 'error', 'message' => 'Unauthorized'];
                break;
            }
            
            $propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$propertyId) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'Property ID is required'];
                break;
            }
            
            // Verify property exists and user has permission
            $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND (owner_id = ? OR ? = 1)");
            $stmt->execute([$propertyId, $_SESSION['user_id'], $_SESSION['is_admin'] ? 1 : 0]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                $response = ['status' => 'error', 'message' => 'Property not found or access denied'];
                break;
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete related records first
            $pdo->prepare("DELETE FROM property_features WHERE property_id = ?")->execute([$propertyId]);
            
            // Get image URLs to delete files later
            $imageStmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
            $imageStmt->execute([$propertyId]);
            $images = $imageStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete property images
            $pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$propertyId]);
            
            // Delete favorites
            $pdo->prepare("DELETE FROM favorites WHERE property_id = ?")->execute([$propertyId]);
            
            // Delete bookings (or set to canceled?)
            // $pdo->prepare("DELETE FROM bookings WHERE property_id = ?")->execute([$propertyId]);
            
            // Finally, delete the property
            $pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([$propertyId]);
            
            // Commit transaction
            $pdo->commit();
            
            // Delete image files (if any)
            foreach ($images as $imageUrl) {
                $filePath = realpath(__DIR__ . '/..' . parse_url($imageUrl, PHP_URL_PATH));
                if ($filePath && file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            $response = [
                'status' => 'success',
                'message' => 'Property deleted successfully'
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log("Error deleting property: " . $e->getMessage());
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to delete property',
                'error' => APP_DEBUG ? $e->getMessage() : null
            ];
        }
        break;
        
    case 'OPTIONS':
        // Preflight request - just return 200 OK
        http_response_code(200);
        exit;
        
    default:
        http_response_code(405);
        $response = [
            'status' => 'error',
            'message' => 'Method not allowed'
        ];
                $response = [
                    'status' => 'success',
                    'data' => $property
                ];
            } else {
                http_response_code(404);
                $response = [
                    'status' => 'error',
                    'message' => 'Property not found'
                ];
            }
                ];
            }
        } else {
            // Get filtered list of properties
            $filters = [
                'location' => $_GET['location'] ?? '',
                'min_price' => isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0,
                'max_price' => isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_FLOAT_MAX,
                'bedrooms' => isset($_GET['bedrooms']) ? intval($_GET['bedrooms']) : null,
                'bathrooms' => isset($_GET['bathrooms']) ? intval($_GET['bathrooms']) : null,
                'type' => $_GET['type'] ?? '',
                'amenities' => isset($_GET['amenities']) ? explode(',', $_GET['amenities']) : [],
                'available_from' => $_GET['available_from'] ?? null,
                'available_to' => $_GET['available_to'] ?? null,
                'page' => max(1, intval($_GET['page'] ?? 1)),
                'limit' => min(20, max(1, intval($_GET['limit'] ?? 10)))
            ];
            
            // Build base query
            $query = "SELECT SQL_CALC_FOUND_ROWS p.*, 
                     (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                     FROM properties p 
                     WHERE p.status = 'available'";
            
            $params = [];
            $types = '';
            
            // Add filters
            if (!empty($filters['location'])) {
                $query .= " AND (p.location LIKE ? OR p.address LIKE ? OR p.city LIKE ? OR p.state LIKE ? OR p.country LIKE ?)";
                $locationParam = "%{$filters['location']}%";
                $params = array_merge($params, array_fill(0, 5, $locationParam));
                $types .= str_repeat('s', 5);
            }
            
            if ($filters['min_price'] > 0) {
                $query .= " AND p.price >= ?";
                $params[] = $filters['min_price'];
                $types .= 'd';
            }
            
            if ($filters['max_price'] < PHP_FLOAT_MAX) {
                $query .= " AND p.price <= ?";
                $params[] = $filters['max_price'];
                $types .= 'd';
            }
            
            if ($filters['bedrooms'] !== null) {
                $query .= " AND p.bedrooms = ?";
                $params[] = $filters['bedrooms'];
                $types .= 'i';
            }
            
            if ($filters['bathrooms'] !== null) {
                $query .= " AND p.bathrooms = ?";
                $params[] = $filters['bathrooms'];
                $types .= 'i';
            }
            
            if (!empty($filters['type'])) {
                $query .= " AND p.property_type = ?";
                $params[] = $filters['type'];
                $types .= 's';
            }
            
            // Add availability filter
            if ($filters['available_from'] && $filters['available_to']) {
                $query .= " AND p.id NOT IN (
                    SELECT property_id FROM bookings 
                    WHERE 
                        (check_in <= ? AND check_out >= ?) OR
                        (check_in <= ? AND check_out >= ?) OR
                        (check_in >= ? AND check_out <= ?)
                )";
                
                $params = array_merge($params, [
                    $filters['available_to'], $filters['available_from'],
                    $filters['available_from'], $filters['available_to'],
                    $filters['available_from'], $filters['available_to']
                ]);
                $types .= str_repeat('s', 6);
            }
            
            // Add amenities filter
            if (!empty($filters['amenities'])) {
                $amenityPlaceholders = implode(',', array_fill(0, count($filters['amenities']), '?'));
                $query .= " AND p.id IN (
                    SELECT property_id FROM property_features 
                    WHERE feature_name = 'amenity' 
                    AND feature_value IN ($amenityPlaceholders)
                    GROUP BY property_id 
                    HAVING COUNT(DISTINCT feature_value) = ?
                )";
                
                $params = array_merge($params, $filters['amenities'], [count($filters['amenities'])]);
                $types .= str_repeat('s', count($filters['amenities'])) . 'i';
            }
            
            // Add sorting
            $sort = $_GET['sort'] ?? 'created_at';
            $order = strtoupper($_GET['order'] ?? 'DESC');
            $validSorts = ['price', 'created_at', 'area', 'bedrooms', 'bathrooms'];
            $validOrders = ['ASC', 'DESC'];
            
            if (in_array($sort, $validSorts) && in_array($order, $validOrders)) {
                $query .= " ORDER BY p.$sort $order";
            } else {
                $query .= " ORDER BY p.created_at DESC";
            }
            
            // Add pagination
            $offset = ($filters['page'] - 1) * $filters['limit'];
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $filters['limit'];
            $params[] = $offset;
            $types .= 'ii';
            
            // Execute query
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $properties = $result->fetch_all(MYSQLI_ASSOC);
            
            // Get total count
            $totalResult = $conn->query("SELECT FOUND_ROWS() as total");
            $total = $totalResult->fetch_assoc()['total'];
            
            // Check favorites for each property if user is logged in
            if (isset($_SESSION['user_id'])) {
                $propertyIds = array_column($properties, 'id');
                $favorites = [];
                
                if (!empty($propertyIds)) {
                    $idList = implode(',', $propertyIds);
                    $favResult = $conn->query("SELECT property_id FROM favorites WHERE user_id = {$_SESSION['user_id']} AND property_id IN ($idList)");
                    $favorites = $favResult->fetch_all(MYSQLI_ASSOC);
                    $favorites = array_column($favorites, 'property_id');
                }
                
                foreach ($properties as &$property) {
                    $property['is_favorite'] = in_array($property['id'], $favorites);
                }
            }
            
            $response = [
                'status' => 'success',
                'data' => [
                    'properties' => $properties,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => $filters['page'],
                        'limit' => $filters['limit'],
                        'total_pages' => ceil($total / $filters['limit'])
                    ],
                    'filters' => $filters
                ]
            ];
        }
        break;
        
    case 'POST':
        // Add new property (admin only)
        if (!isAuthorized()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        // Validate required fields
        $required = ['title', 'description', 'price', 'location', 'property_type', 'bedrooms', 'bathrooms', 'area'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields',
                'missing' => $missing
            ]);
            exit();
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert property
            $sql = "INSERT INTO properties (
                title, description, price, location, address, city, state, country, 
                postal_code, property_type, bedrooms, bathrooms, area, year_built, 
                garage, garage_size, owner_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssdsssssssiiiisdis",
                $data['title'],
                $data['description'],
                $data['price'],
                $data['location'],
                $data['address'] ?? '',
                $data['city'] ?? '',
                $data['state'] ?? '',
                $data['country'] ?? '',
                $data['postal_code'] ?? '',
                $data['property_type'],
                $data['bedrooms'],
                $data['bathrooms'],
                $data['area'],
                $data['year_built'] ?? null,
                $data['garage'] ?? 0,
                $data['garage_size'] ?? 0,
                $_SESSION['user_id'],
                $data['status'] ?? 'available'
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add property: " . $stmt->error);
            }
            
            $propertyId = $conn->insert_id;
            
            // Add features/amenities
            if (!empty($data['features']) && is_array($data['features'])) {
                $featureSql = "INSERT INTO property_features (property_id, feature_name, feature_value) VALUES (?, ?, ?)";
                $featureStmt = $conn->prepare($featureSql);
                
                foreach ($data['features'] as $feature) {
                    if (!empty($feature['name'])) {
                        $featureStmt->bind_param("iss", 
                            $propertyId,
                            $feature['name'],
                            $feature['value'] ?? null
                        );
                        $featureStmt->execute();
                    }
                }
            }
            
            // Handle image uploads
            if (!empty($_FILES['images'])) {
                $uploadDir = '../uploads/properties/' . $propertyId . '/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $imageStmt = $conn->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (?, ?, ?)");
                
                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$index]);
                        $targetFile = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmpName, $targetFile)) {
                            $imageUrl = str_replace('../', '/', $targetFile);
                            $isPrimary = ($index === 0) ? 1 : 0;
                            
                            $imageStmt->bind_param("isi", $propertyId, $imageUrl, $isPrimary);
                            $imageStmt->execute();
                        }
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'Property added successfully',
                'property_id' => $propertyId
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
        break;
        
    case 'PUT':
        // Update property (admin only)
        if (!isAuthorized()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Property ID is required']);
            exit();
        }
        
        $propertyId = intval($_GET['id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update property
            $updates = [];
            $params = [];
            $types = '';
            
            $updatableFields = [
                'title' => 's', 'description' => 's', 'price' => 'd', 'location' => 's',
                'address' => 's', 'city' => 's', 'state' => 's', 'country' => 's',
                'postal_code' => 's', 'property_type' => 's', 'bedrooms' => 'i',
                'bathrooms' => 'i', 'area' => 'i', 'year_built' => 'i', 'garage' => 'i',
                'garage_size' => 'i', 'status' => 's'
            ];
            
            foreach ($updatableFields as $field => $type) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                    $types .= $type;
                }
            }
            
            if (!empty($updates)) {
                $sql = "UPDATE properties SET " . implode(', ', $updates) . " WHERE id = ?";
                $params[] = $propertyId;
                $types .= 'i';
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update property: " . $stmt->error);
                }
            }
            
            // Update features if provided
            if (isset($data['features']) && is_array($data['features'])) {
                // Delete existing features
                $deleteStmt = $conn->prepare("DELETE FROM property_features WHERE property_id = ?");
                $deleteStmt->bind_param("i", $propertyId);
                $deleteStmt->execute();
                
                // Insert updated features
                if (!empty($data['features'])) {
                    $featureSql = "INSERT INTO property_features (property_id, feature_name, feature_value) VALUES (?, ?, ?)";
                    $featureStmt = $conn->prepare($featureSql);
                    
                    foreach ($data['features'] as $feature) {
                        if (!empty($feature['name'])) {
                            $featureStmt->bind_param("iss", 
                                $propertyId,
                                $feature['name'],
                                $feature['value'] ?? null
                            );
                            $featureStmt->execute();
                        }
                    }
                }
            }
            
            // Handle image uploads if any
            if (!empty($_FILES['images'])) {
                $uploadDir = '../uploads/properties/' . $propertyId . '/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $imageStmt = $conn->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (?, ?, ?)");
                
                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                        $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$index]);
                        $targetFile = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmpName, $targetFile)) {
                            $imageUrl = str_replace('../', '/', $targetFile);
                            $isPrimary = 0; // Default to not primary
                            
                            // If this is the first image and there are no existing images, set as primary
                            if ($index === 0) {
                                $checkImages = $conn->query("SELECT COUNT(*) as count FROM property_images WHERE property_id = $propertyId");
                                $imageCount = $checkImages->fetch_assoc()['count'];
                                $isPrimary = ($imageCount === 0) ? 1 : 0;
                            }
                            
                            $imageStmt->bind_param("isi", $propertyId, $imageUrl, $isPrimary);
                            $imageStmt->execute();
                        }
                    }
                }
            }
            
            // Handle image deletions if any
            if (!empty($data['deleted_images']) && is_array($data['deleted_images'])) {
                $deleteStmt = $conn->prepare("DELETE FROM property_images WHERE id = ? AND property_id = ?");
                
                foreach ($data['deleted_images'] as $imageId) {
                    // First, get the image path to delete the file
                    $getImageStmt = $conn->prepare("SELECT image_url FROM property_images WHERE id = ? AND property_id = ?");
                    $getImageStmt->bind_param("ii", $imageId, $propertyId);
                    $getImageStmt->execute();
                    $imageResult = $getImageStmt->get_result();
                    
                    if ($imageResult->num_rows > 0) {
                        $image = $imageResult->fetch_assoc();
                        $imagePath = '../' . ltrim($image['image_url'], '/');
                        
                        // Delete the file
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                        
                        // Delete the database record
                        $deleteStmt->bind_param("ii", $imageId, $propertyId);
                        $deleteStmt->execute();
                    }
                }
                
                // If primary image was deleted, set a new one
                $checkPrimary = $conn->query("SELECT id FROM property_images WHERE property_id = $propertyId AND is_primary = 1");
                if ($checkPrimary->num_rows === 0) {
                    $newPrimary = $conn->query("SELECT id FROM property_images WHERE property_id = $propertyId LIMIT 1");
                    if ($newPrimary->num_rows > 0) {
                        $newPrimaryId = $newPrimary->fetch_assoc()['id'];
                        $conn->query("UPDATE property_images SET is_primary = 1 WHERE id = $newPrimaryId");
                    }
                }
            }
            
            // Set primary image if specified
            if (!empty($data['primary_image_id'])) {
                $primaryId = intval($data['primary_image_id']);
                $conn->query("UPDATE property_images SET is_primary = 0 WHERE property_id = $propertyId");
                $conn->query("UPDATE property_images SET is_primary = 1 WHERE id = $primaryId AND property_id = $propertyId");
            }
            
            // Commit transaction
            $conn->commit();
            
            $response = [
                'status' => 'success',
                'message' => 'Property updated successfully'
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
        break;
        
    case 'DELETE':
        // Delete property (admin only)
        if (!isAuthorized()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Property ID is required']);
            exit();
        }
        
        $propertyId = intval($_GET['id']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get images to delete files
            $images = $conn->query("SELECT image_url FROM property_images WHERE property_id = $propertyId");
            
            // Delete property (foreign key constraints will handle related records)
            $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->bind_param("i", $propertyId);
            
            if ($stmt->execute()) {
                // Delete image files
                $uploadDir = '../uploads/properties/' . $propertyId . '/';
                
                if (file_exists($uploadDir)) {
                    // Delete all files in the directory
                    array_map('unlink', glob("$uploadDir/*.*"));
                    // Remove the directory
                    rmdir($uploadDir);
                }
                
                $conn->commit();
                $response = [
                    'status' => 'success',
                    'message' => 'Property deleted successfully'
                ];
            } else {
                throw new Exception("Failed to delete property: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
        break;
        
    case 'OPTIONS':
        // Preflight request
        http_response_code(200);
        exit();
        
    default:
        http_response_code(405);
        $response = [
            'status' => 'error',
            'message' => 'Method not allowed'
        ];
}

} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ];
}

// Send JSON response
echo json_encode($response);
exit;
?>
