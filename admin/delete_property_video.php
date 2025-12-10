<?php
/**
 * Delete Property Video Script
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($id > 0) {
    try {
        // Get video path to delete file
        $stmt = $pdo->prepare("SELECT video_url FROM property_videos WHERE id = ?");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        
        if ($video) {
            // Delete record
            $deleteStmt = $pdo->prepare("DELETE FROM property_videos WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            // Delete file
            $filePath = __DIR__ . '/../' . $video['video_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $_SESSION['success'] = 'Video deleted successfully';
        } else {
            $_SESSION['error'] = 'Video not found';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting video: ' . $e->getMessage();
    }
} else {
    $_SESSION['error'] = 'Invalid request';
}

header('Location: property-edit.php?id=' . $property_id);
exit;
?>
