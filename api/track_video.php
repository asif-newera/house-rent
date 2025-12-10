<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the video ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$videoId = $data['videoId'] ?? null;

if (!$videoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Video ID is required']);
    exit;
}

try {
    // Get user IP and user agent for tracking
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;
    
    // Insert the view into the database
    $stmt = $pdo->prepare("
        INSERT INTO video_views (video_id, user_id, ip_address, user_agent) 
        VALUES (:video_id, :user_id, :ip_address, :user_agent)
    ");
    
    $stmt->execute([
        ':video_id' => $videoId,
        ':user_id' => $userId,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent
    ]);
    
    // Get total views for this video
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_views FROM video_views WHERE video_id = ?");
    $stmt->execute([$videoId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_views' => (int)$result['total_views']
    ]);
    
} catch (PDOException $e) {
    error_log('Video tracking error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to track video view']);
}
?>
