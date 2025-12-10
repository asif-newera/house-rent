<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();
include_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        // Get user's favorite properties
        $sql = "SELECT p.* 
                FROM properties p
                JOIN favorites f ON p.id = f.property_id 
                WHERE f.user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $favorites = [];
        while ($row = $result->fetch_assoc()) {
            // Get property images
            $imageStmt = $conn->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
            $imageStmt->bind_param("i", $row['id']);
            $imageStmt->execute();
            $imageResult = $imageStmt->get_result();
            $images = [];
            while ($image = $imageResult->fetch_assoc()) {
                $images[] = $image['image_url'];
            }
            $row['images'] = $images;
            $favorites[] = $row;
        }
        
        $response = [
            'status' => 'success',
            'data' => $favorites
        ];
        break;
        
    case 'POST':
        // Add property to favorites
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->property_id)) {
            $property_id = intval($data->property_id);
            
            // Check if already favorited
            $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
            $check->bind_param("ii", $user_id, $property_id);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                $response = [
                    'status' => 'info',
                    'message' => 'Property already in favorites'
                ];
            } else {
                $stmt = $conn->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $property_id);
                
                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Added to favorites'
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to add to favorites'
                    ];
                }
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Property ID is required'
            ];
        }
        break;
        
    case 'DELETE':
        // Remove property from favorites
        parse_str(file_get_contents("php://input"), $delete_vars);
        $property_id = isset($delete_vars['property_id']) ? intval($delete_vars['property_id']) : 0;
        
        if ($property_id > 0) {
            $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?");
            $stmt->bind_param("ii", $user_id, $property_id);
            
            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Removed from favorites'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to remove from favorites'
                ];
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Invalid property ID'
            ];
        }
        break;
        
    default:
        http_response_code(405);
        $response = [
            'status' => 'error',
            'message' => 'Method not allowed'
        ];
}

echo json_encode($response);
?>
