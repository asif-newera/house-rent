<?php
/**
 * Process Booking Submission
 * Handles booking form submissions from property-details.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to book a property.';
    header('Location: login.php');
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: properties.php');
    exit;
}

// Verify CSRF token
if (!isset($_POST['_token']) || !csrf_verify($_POST['_token'])) {
    $_SESSION['error_message'] = 'Invalid request. Please try again.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'properties.php'));
    exit;
}

try {
    // Sanitize and validate input
    $property_id = filter_input(INPUT_POST, 'property_id', FILTER_VALIDATE_INT);
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $end_date = sanitizeInput($_POST['end_date'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Validation
    if (!$property_id) {
        throw new Exception('Invalid property selected.');
    }
    
    if (empty($start_date) || empty($end_date)) {
        throw new Exception('Please select check-in and check-out dates.');
    }
    
    // Validate dates
    $startDate = new DateTime($start_date);
    $endDate = new DateTime($end_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($startDate < $today) {
        throw new Exception('Check-in date cannot be in the past.');
    }
    
    if ($endDate <= $startDate) {
        throw new Exception('Check-out date must be after check-in date.');
    }
    
    // Get property details
    $stmt = $pdo->prepare("SELECT id, title, price FROM properties WHERE id = ? AND status = 'available'");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$property) {
        throw new Exception('Property not found or not available.');
    }
    
    // Calculate total price
    $interval = $startDate->diff($endDate);
    $days = $interval->days;
    $months = $days / 30;
    $duration_months = round($months, 2);
    $total_amount = ceil($months * $property['price']);
    
    // Insert booking
    $insertStmt = $pdo->prepare("
        INSERT INTO bookings (property_id, user_id, start_date, end_date, duration_months, total_amount, status, booking_date, notes)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
    ");
    
    $insertStmt->execute([
        $property_id,
        $user_id,
        $start_date,
        $end_date,
        $duration_months,
        $total_amount,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Success message
    $_SESSION['success_message'] = 'Booking request submitted successfully! Booking ID: #' . $booking_id;
    header('Location: my-bookings.php');
    exit;
    
} catch (Exception $e) {
    error_log('Booking error: ' . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'properties.php'));
    exit;
}
