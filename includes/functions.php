<?php
/**
 * Common utility functions for the House Rent application
 */

/**
 * Sanitize input data
 * 
 * @param string $data The input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate a random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format price with currency
 * 
 * @param float $amount The amount to format
 * @param string $currency Currency symbol
 * @return string Formatted price
 */
function formatPrice($amount, $currency = '৳') {
    return $currency . number_format($amount, 2);
}

/**
 * Calculate number of nights between two dates
 * 
 * @param string $checkIn Check-in date (Y-m-d)
 * @param string $checkOut Check-out date (Y-m-d)
 * @return int Number of nights
 */
function calculateNights($checkIn, $checkOut) {
    $start = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    $interval = $start->diff($end);
    return $interval->days;
}

/**
 * Check if a date range is available for a property
 * 
 * @param mysqli $conn Database connection
 * @param int $propertyId Property ID
 * @param string $checkIn Check-in date (Y-m-d)
 * @param string $checkOut Check-out date (Y-m-d)
 * @param int $excludeBookingId Booking ID to exclude (for updates)
 * @return bool True if available, false otherwise
 */
function isPropertyAvailable($conn, $propertyId, $checkIn, $checkOut, $excludeBookingId = null) {
    $sql = "SELECT id FROM bookings 
            WHERE property_id = ? 
            AND status NOT IN ('cancelled')
            AND (
                (check_in <= ? AND check_out >= ?) OR
                (check_in <= ? AND check_out >= ?) OR
                (check_in >= ? AND check_out <= ?)
            )";
    
    $params = [$propertyId, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut];
    $types = 'isssss';
    
    if ($excludeBookingId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
        $types .= 'i';
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 0;
}

/**
 * Upload a file with validation
 * 
 * @param array $file $_FILES array element
 * @param string $targetDir Target directory
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array [status, message, filePath]
 */
function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'status' => 'error',
            'message' => 'File upload error: ' . $file['error']
        ];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $maxSizeMB = $maxSize / (1024 * 1024);
        return [
            'status' => 'error',
            'message' => "File is too large. Maximum size is {$maxSizeMB}MB"
        ];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedTypes)) {
        return [
            'status' => 'error',
            'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)
        ];
    }
    
    // Create target directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetFile = rtrim($targetDir, '/') . '/' . $fileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return [
            'status' => 'success',
            'message' => 'File uploaded successfully',
            'filePath' => $targetFile,
            'fileName' => $fileName
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to move uploaded file'
        ];
    }
}

/**
 * Send JSON response and exit
 * 
 * @param int $statusCode HTTP status code
 * @param array $data Response data
 */
function sendJsonResponse($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
// isLoggedIn() function is defined in config.php

// isAdmin() function is defined in config.php

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        sendJsonResponse(401, [
            'status' => 'error',
            'message' => 'Authentication required'
        ]);
    }
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        sendJsonResponse(403, [
            'status' => 'error',
            'message' => 'Admin privileges required'
        ]);
    }
}

/**
 * Get pagination parameters from request
 * 
 * @return array [page, limit, offset]
 */
function getPaginationParams() {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * Generate pagination metadata
 * 
 * @param int $total Total number of items
 * @param int $page Current page
 * @param int $limit Items per page
 * @return array Pagination metadata
 */
function getPaginationMeta($total, $page, $limit) {
    return [
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ];
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array [isValid, message]
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters long'];
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return [false, 'Password must contain at least one uppercase letter'];
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return [false, 'Password must contain at least one lowercase letter'];
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return [false, 'Password must contain at least one number'];
    }
    
    return [true, 'Password is valid'];
}

/**
 * Generate a slug from a string
 * 
 * @param string $string String to convert to slug
 * @return string Slug
 */
function slugify($string) {
    $string = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $string);
    $string = preg_replace('/[\-\s]+/', '-', $string);
    $string = trim($string, '-');
    return mb_strtolower($string, 'UTF-8');
}

/**
 * Generate a CSRF token and store it in session
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// redirect() function is defined in config.php

/**
 * Get the base URL of the application
 * 
 * @return string Base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    return rtrim("$protocol://$host$script", '/');
}

/**
 * Get the current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Send an email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $from Sender email
 * @param string $fromName Sender name
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $body, $from = null, $fromName = null) {
    if ($from === null) {
        $from = getenv('MAIL_FROM') ?: 'noreply@' . $_SERVER['HTTP_HOST'];
    }
    
    if ($fromName === null) {
        $fromName = getenv('MAIL_FROM_NAME') ?: 'House Rent System';
    }
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $fromName . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Log an action to the database
 * 
 * @param mysqli $conn Database connection
 * @param string $action Action description
 * @param array $data Additional data to log
 * @param int $userId User ID (optional)
 * @return bool True on success, false on failure
 */
function logAction($conn, $action, $data = [], $userId = null) {
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    $ip = getClientIp();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $dataJson = json_encode($data);
    
    $sql = "INSERT INTO activity_logs (user_id, action, data, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $action, $dataJson, $ip, $userAgent);
    
    return $stmt->execute();
}
/**
 * Get permissions based on user role
 * 
 * @param string $role User role
 * @return array List of permissions
 */
function getRolePermissions($role) {
    $permissions = [];
    
    if ($role === 'admin') {
        $permissions = [
            'manage_properties',
            'manage_users', 
            'manage_bookings',
            'view_reports',
            'manage_settings'
        ];
    } else {
        // Default user permissions
        $permissions = [
            'manage_own_properties',
            'view_own_bookings'
        ];
    }
    
    return $permissions;
}
?>
