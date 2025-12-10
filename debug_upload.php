<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

echo "<h2>Debug Upload</h2>";

// Check upload directory
$targetDir = "assets/images/properties/"; // Relative to root
if (!file_exists($targetDir)) {
    echo "Directory does not exist. Attempting to create...<br>";
    if (mkdir($targetDir, 0777, true)) {
        echo "Directory created.<br>";
    } else {
        echo "Failed to create directory. Permission denied?<br>";
    }
} else {
    echo "Directory exists.<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($targetDir)), -4) . "<br>";
    echo "Writable: " . (is_writable($targetDir) ? 'Yes' : 'No') . "<br>";
}

// Simple upload form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Upload Attempt</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";

    if (isset($_FILES['test_image'])) {
        $file = $_FILES['test_image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                echo "File uploaded successfully to: " . $targetFilePath . "<br>";
                
                // Try DB Insert
                try {
                    $stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_url, created_at) VALUES (1, ?, NOW())"); // Assuming ID 1 exists
                    $stmt->execute([$targetFilePath]);
                    echo "Database insert successful.<br>";
                    
                    // Verify correct path format for Windows vs Web
                    echo "Stored Path: " . $targetFilePath . "<br>";
                } catch (PDOException $e) {
                    echo "Database error: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "Failed to move uploaded file.<br>";
            }
        } else {
            echo "Upload error code: " . $file['error'] . "<br>";
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_image">
    <button type="submit">Test Upload</button>
</form>
