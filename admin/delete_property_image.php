<?php
/**
 * Delete Property Image Script
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
        // Get image path to delete file
        $stmt = $pdo->prepare("SELECT image_url, property_id FROM property_images WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetch();
        
        if ($image) {
            // Check if user owns the property (if strict checking is needed, but admin is superuser)
            // For now, allow admin to delete any image
            
            // Delete record
            $deleteStmt = $pdo->prepare("DELETE FROM property_images WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            // Delete file
            $filePath = __DIR__ . '/../' . $image['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $_SESSION['success'] = 'Image deleted successfully';
        } else {
            $_SESSION['error'] = 'Image not found';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting image: ' . $e->getMessage();
    }
} else {
    $_SESSION['error'] = 'Invalid request';
}

header('Location: property-edit.php?id=' . $property_id);
exit;
?>
