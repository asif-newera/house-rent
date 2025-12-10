<?php
// Include configuration and functions
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

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
                
                $where = [];
                $params = [];
                
                // Build WHERE clause
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
                
                if (isset($filters['available'])) {
                    $where[] = "p.available = :available";
                    $params[':available'] = (int)$filters['available'];
                }
                
                // Build query
                $sql = "SELECT p.*, 
                       (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                       u.name as owner_name
                       FROM properties p
                       LEFT JOIN users u ON p.owner_id = u.id";
                
                if (!empty($where)) {
                    $sql .= " WHERE " . implode(" AND ", $where);
                }
                
                // Add sorting
                $sort = in_array(strtolower($filters['sort']), ['price', 'bedrooms', 'bathrooms', 'created_at']) ? $filters['sort'] : 'created_at';
                $order = strtoupper($filters['order']) === 'ASC' ? 'ASC' : 'DESC';
                $sql .= " ORDER BY p." . $sort . " " . $order;
                
                // Add pagination
                $page = max(1, (int)$filters['page']);
                $limit = min(50, max(1, (int)$filters['limit']));
                $offset = ($page - 1) * $limit;
                
                // Get total count for pagination
                $countSql = "SELECT COUNT(*) as total FROM properties p" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
                $countStmt = $pdo->prepare($countSql);
                
                // Bind parameters for count query
                foreach ($params as $key => $value) {
                    $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                
                $countStmt->execute();
                $total = $countStmt->fetchColumn();
                
                // Add limit and offset to main query
                $sql .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = $limit;
                $params[':offset'] = $offset;
                
                // Execute main query
                $stmt = $pdo->prepare($sql);
                
                // Bind parameters
                foreach ($params as $key => $value) {
                    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                    $stmt->bindValue($key, $value, $paramType);
                }
                
                $stmt->execute();
                $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = [
                    'status' => 'success',
                    'data' => $properties,
                    'pagination' => [
                        'total' => (int)$total,
                        'per_page' => $limit,
                        'current_page' => $page,
                        'last_page' => ceil($total / $limit)
                    ]
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
            } else {
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
                } else {
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
                    
                    // Handle images
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
                    
                    http_response_code(201);
                    $response = [
                        'status' => 'success',
                        'message' => 'Property created successfully',
                        'data' => $property
                    ];
                }
            }
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
            } elseif (empty($data['id'])) {
                http_response_code(400);
                $response = ['status' => 'error', 'message' => 'Property ID is required'];
            } else {
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
                } else {
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
                    
                    // Handle new images
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
                        $deleteParams = array_merge($data['deleted_images'], [$propertyId]);
                        $stmt->execute($deleteParams);
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
                }
            }
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
            } else {
                $propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if (!$propertyId) {
                    http_response_code(400);
                    $response = ['status' => 'error', 'message' => 'Property ID is required'];
                } else {
                    // Verify property exists and user has permission
                    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND (owner_id = ? OR ? = 1)");
                    $stmt->execute([$propertyId, $_SESSION['user_id'], $_SESSION['is_admin'] ? 1 : 0]);
                    
                    if ($stmt->rowCount() === 0) {
                        http_response_code(404);
                        $response = ['status' => 'error', 'message' => 'Property not found or access denied'];
                    } else {
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
                        
                        // Delete the property
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
                    }
                }
            }
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
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
