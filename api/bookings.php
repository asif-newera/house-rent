<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

session_start();
include_once '../config/database.php';
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get request data
$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = [];
}

switch($method) {
    case 'GET':
        // Get booking by ID or list bookings with filters
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        if (isset($_GET['id'])) {
            // Get single booking with details
            $bookingId = intval($_GET['id']);
            $userId = $_SESSION['user_id'];
            
            // Check if user is admin or the booking owner
            $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
            $sql = "SELECT b.*, 
                           p.title as property_title, p.price as property_price, 
                           p.location as property_location, p.image_url as property_image,
                           u.name as guest_name, u.email as guest_email, u.phone as guest_phone,
                           owner.name as owner_name, owner.email as owner_email, owner.phone as owner_phone
                    FROM bookings b
                    JOIN properties p ON b.property_id = p.id
                    JOIN users u ON b.user_id = u.id
                    JOIN users owner ON p.owner_id = owner.id
                    WHERE b.id = ? " . ($isAdmin ? "" : "AND b.user_id = ?");
            
            $stmt = $conn->prepare($sql);
            if ($isAdmin) {
                $stmt->bind_param("i", $bookingId);
            } else {
                $stmt->bind_param("ii", $bookingId, $userId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $booking = $result->fetch_assoc();
                
                // Calculate total price
                $checkIn = new DateTime($booking['check_in']);
                $checkOut = new DateTime($booking['check_out']);
                $nights = $checkIn->diff($checkOut)->days;
                $booking['total_price'] = $nights * $booking['property_price'];
                $booking['nights'] = $nights;
                
                $response = [
                    'status' => 'success',
                    'data' => $booking
                ];
            } else {
                http_response_code(404);
                $response = [
                    'status' => 'error',
                    'message' => 'Booking not found or access denied'
                ];
            }
        } else {
            // Get filtered list of bookings
            $userId = $_SESSION['user_id'];
            $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
            
            $filters = [
                'status' => $_GET['status'] ?? '',
                'property_id' => isset($_GET['property_id']) ? intval($_GET['property_id']) : null,
                'user_id' => $isAdmin && isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId,
                'from_date' => $_GET['from_date'] ?? '',
                'to_date' => $_GET['to_date'] ?? '',
                'page' => max(1, intval($_GET['page'] ?? 1)),
                'limit' => min(20, max(1, intval($_GET['limit'] ?? 10)))
            ];
            
            // Build base query
            $query = "SELECT SQL_CALC_FOUND_ROWS b.*, 
                             p.title as property_title, p.price as property_price,
                             p.location as property_location, p.image_url as property_image,
                             u.name as guest_name, u.email as guest_email
                      FROM bookings b
                      JOIN properties p ON b.property_id = p.id
                      JOIN users u ON b.user_id = u.id
                      WHERE 1=1";
            
            $params = [];
            $types = '';
            
            // Add filters
            if (!$isAdmin) {
                // Non-admin users can only see their own bookings
                $query .= " AND b.user_id = ?";
                $params[] = $userId;
                $types .= 'i';
            } elseif (isset($_GET['user_id'])) {
                // Admin can filter by user_id
                $query .= " AND b.user_id = ?";
                $params[] = $filters['user_id'];
                $types .= 'i';
            }
            
            if (!empty($filters['status'])) {
                $query .= " AND b.status = ?";
                $params[] = $filters['status'];
                $types .= 's';
            }
            
            if ($filters['property_id'] !== null) {
                $query .= " AND b.property_id = ?";
                $params[] = $filters['property_id'];
                $types .= 'i';
            }
            
            if (!empty($filters['from_date'])) {
                $query .= " AND b.check_in >= ?";
                $params[] = $filters['from_date'];
                $types .= 's';
            }
            
            if (!empty($filters['to_date'])) {
                $query .= " AND b.check_out <= ?";
                $params[] = $filters['to_date'];
                $types .= 's';
            }
            
            // Add sorting
            $sort = $_GET['sort'] ?? 'created_at';
            $order = strtoupper($_GET['order'] ?? 'DESC');
            $validSorts = ['check_in', 'check_out', 'created_at', 'total_price'];
            $validOrders = ['ASC', 'DESC'];
            
            if (in_array($sort, $validSorts) && in_array($order, $validOrders)) {
                $query .= " ORDER BY b.$sort $order";
            } else {
                $query .= " ORDER BY b.created_at DESC";
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
            $bookings = [];
            
            while ($row = $result->fetch_assoc()) {
                // Calculate total price and nights
                $checkIn = new DateTime($row['check_in']);
                $checkOut = new DateTime($row['check_out']);
                $nights = $checkIn->diff($checkOut)->days;
                $row['total_price'] = $nights * $row['property_price'];
                $row['nights'] = $nights;
                
                $bookings[] = $row;
            }
            
            // Get total count
            $totalResult = $conn->query("SELECT FOUND_ROWS() as total");
            $total = $totalResult->fetch_assoc()['total'];
            
            $response = [
                'status' => 'success',
                'data' => [
                    'bookings' => $bookings,
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
        // Create a new booking
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'You must be logged in to make a booking']);
            exit();
        }
        
        // Validate required fields
        $required = ['property_id', 'check_in', 'check_out', 'guests'];
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
        
        $propertyId = intval($data['property_id']);
        $checkIn = $conn->real_escape_string($data['check_in']);
        $checkOut = $conn->real_escape_string($data['check_out']);
        $guests = intval($data['guests']);
        $specialRequests = $conn->real_escape_string($data['special_requests'] ?? '');
        $userId = $_SESSION['user_id'];
        
        // Validate dates
        $today = new DateTime();
        $checkInDate = new DateTime($checkIn);
        $checkOutDate = new DateTime($checkOut);
        
        if ($checkInDate < $today) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Check-in date cannot be in the past'
            ]);
            exit();
        }
        
        if ($checkOutDate <= $checkInDate) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Check-out date must be after check-in date'
            ]);
            exit();
        }
        
        // Check property availability
        $availabilityCheck = $conn->prepare("
            SELECT id, price, max_guests 
            FROM properties 
            WHERE id = ? 
            AND status = 'available'
            AND id NOT IN (
                SELECT property_id FROM bookings 
                WHERE 
                    (check_in <= ? AND check_out >= ?) OR
                    (check_in <= ? AND check_out >= ?) OR
                    (check_in >= ? AND check_out <= ?)
            )
        ");
        
        $availabilityCheck->bind_param("isssss", 
            $propertyId, 
            $checkOut, $checkIn,
            $checkIn, $checkOut,
            $checkIn, $checkOut
        );
        
        $availabilityCheck->execute();
        $property = $availabilityCheck->get_result()->fetch_assoc();
        
        if (!$property) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Property is not available for the selected dates'
            ]);
            exit();
        }
        
        // Check maximum guests
        if ($guests > $property['max_guests']) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => "Maximum {$property['max_guests']} guests allowed for this property"
            ]);
            exit();
        }
        
        // Calculate total price
        $nights = $checkInDate->diff($checkOutDate)->days;
        $totalPrice = $nights * $property['price'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create booking
            $bookingSql = "INSERT INTO bookings (
                property_id, user_id, check_in, check_out, guests, 
                total_price, status, special_requests
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)";
            
            $bookingStmt = $conn->prepare($bookingSql);
            $bookingStmt->bind_param("iissids", 
                $propertyId, $userId, $checkIn, $checkOut, $guests, $totalPrice, $specialRequests
            );
            
            if (!$bookingStmt->execute()) {
                throw new Exception("Failed to create booking: " . $bookingStmt->error);
            }
            
            $bookingId = $conn->insert_id;
            
            // In a real application, you would process payment here
            // For now, we'll just mark the booking as confirmed
            $updateStmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $updateStmt->bind_param("i", $bookingId);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to confirm booking: " . $updateStmt->error);
            }
            
            // Commit transaction
            $conn->commit();
            
            // Get booking details for response
            $newBooking = $conn->query("
                SELECT b.*, p.title as property_title, p.price as property_price, p.location as property_location
                FROM bookings b
                JOIN properties p ON b.property_id = p.id
                WHERE b.id = $bookingId
            ")->fetch_assoc();
            
            $newBooking['nights'] = $nights;
            $newBooking['total_price'] = $totalPrice;
            
            $response = [
                'status' => 'success',
                'message' => 'Booking created successfully',
                'data' => $newBooking
            ];
            
            // Send confirmation email (pseudo-code)
            // sendBookingConfirmationEmail($newBooking);
            
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
        // Update booking status (cancel, confirm, etc.)
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Booking ID is required']);
            exit();
        }
        
        $bookingId = intval($_GET['id']);
        $userId = $_SESSION['user_id'];
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
        
        // Check if booking exists and user has permission
        $checkStmt = $conn->prepare("
            SELECT b.*, p.owner_id 
            FROM bookings b
            JOIN properties p ON b.property_id = p.id
            WHERE b.id = ? AND (b.user_id = ? OR ? = 1 OR p.owner_id = ?)
        ");
        
        $checkStmt->bind_param("iiii", $bookingId, $userId, $isAdmin, $userId);
        $checkStmt->execute();
        $booking = $checkStmt->get_result()->fetch_assoc();
        
        if (!$booking) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Booking not found or access denied']);
            exit();
        }
        
        // Validate status update
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
        $newStatus = strtolower($data['status'] ?? '');
        
        if (empty($newStatus) || !in_array($newStatus, $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
            ]);
            exit();
        }
        
        // Additional validation for cancellation
        if ($newStatus === 'cancelled') {
            // Only allow cancellation if booking is not already completed or cancelled
            if (in_array($booking['status'], ['cancelled', 'completed'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cannot cancel a booking that is already ' . $booking['status']
                ]);
                exit();
            }
            
            // Check cancellation policy
            $checkInDate = new DateTime($booking['check_in']);
            $today = new DateTime();
            $daysUntilCheckIn = $today->diff($checkInDate)->days;
            
            // Example: No cancellation within 7 days of check-in
            if ($daysUntilCheckIn < 7 && !$isAdmin) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cancellation is not allowed within 7 days of check-in. Please contact support.'
                ]);
                exit();
            }
        }
        
        // Update booking status
        $updateStmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $bookingId);
        
        if ($updateStmt->execute()) {
            $response = [
                'status' => 'success',
                'message' => "Booking has been {$newStatus}",
                'data' => [
                    'booking_id' => $bookingId,
                    'new_status' => $newStatus
                ]
            ];
            
            // Send notification (pseudo-code)
            // sendBookingStatusUpdate($bookingId, $newStatus);
            
        } else {
            throw new Exception("Failed to update booking status: " . $updateStmt->error);
        }
        break;
        
    case 'DELETE':
        // Delete a booking (admin only)
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
            exit();
        }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Booking ID is required']);
            exit();
        }
        
        $bookingId = intval($_GET['id']);
        
        // Check if booking exists
        $checkStmt = $conn->prepare("SELECT id FROM bookings WHERE id = ?");
        $checkStmt->bind_param("i", $bookingId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
            exit();
        }
        
        // Delete booking
        $deleteStmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $deleteStmt->bind_param("i", $bookingId);
        
        if ($deleteStmt->execute()) {
            $response = [
                'status' => 'success',
                'message' => 'Booking deleted successfully'
            ];
        } else {
            throw new Exception("Failed to delete booking: " . $deleteStmt->error);
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

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
