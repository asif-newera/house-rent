<?php
// Include config first (starts session, connects DB)
require_once __DIR__ . '/../config/config.php';
// require_once __DIR__ . '/includes/functions.php'; // Removed to avoid conflict with header.php which includes root functions

$admin_id = $_SESSION['user_id'] ?? 0;
// Check auth
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $price = $_POST['price'] ?? 0;
    $address = $_POST['address'] ?? '';
    $bedrooms = $_POST['bedrooms'] ?? 0;
    $bathrooms = $_POST['bathrooms'] ?? 0;
    $area = $_POST['area'] ?? 0;
    $status = $_POST['status'] ?? 'available';
    
    try {
        if ($property_id > 0) {
            // Update existing property
            $stmt = $pdo->prepare("
                UPDATE properties 
                SET title = ?, description = ?, property_type = ?, type = ?, price = ?, 
                    address = ?, location = ?, bedrooms = ?, bathrooms = ?, area = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $type, $type, $price, $address, $address, $bedrooms, $bathrooms, $area, $status, $property_id]);
            $_SESSION['success'] = 'Property updated successfully';
        } else {
            // Insert new property
            $stmt = $pdo->prepare("
                INSERT INTO properties (user_id, title, description, property_type, type, price, address, location, bedrooms, bathrooms, area, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$admin_id, $title, $description, $type, $type, $price, $address, $address, $bedrooms, $bathrooms, $area, $status]);
            $_SESSION['success'] = 'Property added successfully';
            // Update ID for uploads
            if ($property_id === 0) {
                $property_id = $pdo->lastInsertId();
            }
        }
        
        
        // Handle Image Uploads
        if (!empty($_FILES['images']['name'][0])) {
            $targetDir = __DIR__ . '/../assets/images/properties/';
            if (!file_exists($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    error_log("Failed to create directory: " . $targetDir);
                    $_SESSION['error'] = 'Failed to create upload directory.';
                }
            }
            
            $uploadedImages = [];
            $totalFiles = count($_FILES['images']['name']);
            $uploadErrors = [];
            
            // Check for overwrite
            if (isset($_POST['overwrite_images'])) {
                $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
                $chkId = $property_id;
                $stmt->execute([$chkId]);
                $oldImages = $stmt->fetchAll();
                foreach ($oldImages as $img) {
                    $path = __DIR__ . '/../' . $img['image_url'];
                    if (file_exists($path)) unlink($path);
                }
                $pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$chkId]);
            }

            error_log("Upload request: " . $totalFiles . " images");

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $fileName = uniqid('img_', true) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetFilePath = $targetDir . $fileName;
                    $dbFilePath = "assets/images/properties/" . $fileName; // Path for DB
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFilePath)) {
                        // Insert into property_images
                        try {
                            $stmt_img = $pdo->prepare("INSERT INTO property_images (property_id, image_url, created_at) VALUES (?, ?, NOW())");
                            $stmt_img->execute([$property_id, $dbFilePath]);
                            $uploadedImages[] = $dbFilePath;
                            error_log("Saved image: " . $dbFilePath);
                        } catch (PDOException $e) {
                            error_log("DB insert image error: " . $e->getMessage());
                            $uploadErrors[] = "Database error for file " . $_FILES['images']['name'][$i];
                        }
                    } else {
                        error_log("Failed to move uploaded file: " . $targetFilePath);
                        $uploadErrors[] = "Failed to save file " . $_FILES['images']['name'][$i];
                    }
                } elseif ($_FILES['images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $uploadErrors[] = "Upload error code " . $_FILES['images']['error'][$i] . " for file " . $_FILES['images']['name'][$i];
                    error_log("Upload error code: " . $_FILES['images']['error'][$i]);
                }
            }
            
            if (!empty($uploadErrors)) {
                $_SESSION['error'] = 'Some images failed to upload: ' . implode(', ', $uploadErrors);
            }
            
            // Set primary image if main property image is empty
            if (!empty($uploadedImages)) {
                $checkStmt = $pdo->prepare("SELECT image_url FROM properties WHERE id = ?");
                $checkStmt->execute([$property_id]);
                $currentMain = $checkStmt->fetchColumn();
                
                if (empty($currentMain) || isset($_POST['overwrite_images'])) {
                    $updateMain = $pdo->prepare("UPDATE properties SET image_url = ? WHERE id = ?");
                    $updateMain->execute([$uploadedImages[0], $property_id]);
                }
            }
        }
        
        // Handle Video Uploads
        if (!empty($_FILES['videos']['name'][0])) {
            $targetDir = __DIR__ . '/../assets/videos/properties/';
            if (!file_exists($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    error_log("Failed to create directory: " . $targetDir);
                }
            }
            
            $totalFiles = count($_FILES['videos']['name']);
            
            for ($i = 0; $i < $totalFiles; $i++) {
                // Check for overwrite
                if (isset($_POST['overwrite_videos']) && $i === 0) { // Only delete once
                    $stmt = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ?");
                    $pid = $property_id;
                    $stmt->execute([$pid]);
                    $oldVideos = $stmt->fetchAll();
                    foreach ($oldVideos as $vid) {
                        $path = __DIR__ . '/../' . $vid['video_url'];
                        if (file_exists($path)) unlink($path);
                    }
                    $pdo->prepare("DELETE FROM property_videos WHERE property_id = ?")->execute([$pid]);
                }

                if ($_FILES['videos']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['videos']['name'][$i]));
                    $targetFilePath = $targetDir . $fileName;
                    $dbFilePath = "assets/videos/properties/" . $fileName;
                    
                    if (move_uploaded_file($_FILES['videos']['tmp_name'][$i], $targetFilePath)) {
                        try {
                            $stmt_vid = $pdo->prepare("INSERT INTO property_videos (property_id, video_url, created_at) VALUES (?, ?, NOW())");
                            $stmt_vid->execute([$property_id, $dbFilePath]);
                        } catch (PDOException $e) {
                            error_log("DB insert video error: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        header('Location: properties.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error saving property: ' . $e->getMessage();
    }
}

$pageTitle = $property_id > 0 ? 'Edit Property' : 'Add Property';
require_once __DIR__ . '/includes/header.php';

// Fetch property data if editing
$property = null;
if ($property_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
        $property = $stmt->fetch();
        
        // Fetch existing images
        if ($property) {
            $stmt_img = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
            $stmt_img->execute([$property_id]);
            $existing_images = $stmt_img->fetchAll();
            
            $stmt_vid = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ?");
            $stmt_vid->execute([$property_id]);
            $existing_videos = $stmt_vid->fetchAll();
        }
        
        if (!$property) {
            $_SESSION['error'] = 'Property not found';
            echo "<script>window.location.href='properties.php';</script>"; // Fallback if header fails
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching property: ' . $e->getMessage();
        echo "<script>window.location.href='properties.php';</script>";
        exit;
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= $property_id > 0 ? 'Edit' : 'Add New' ?> Property</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?= htmlspecialchars($property['title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($property['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="house" <?= ($property['type'] ?? '') === 'house' ? 'selected' : '' ?>>House</option>
                                <option value="apartment" <?= ($property['type'] ?? '') === 'apartment' ?  'selected' : '' ?>>Apartment</option>
                                <option value="villa" <?= ($property['type'] ?? '') === 'villa' ?  'selected' : '' ?>>Villa</option>
                                <option value="studio" <?= ($property['type'] ?? '') === 'studio' ? 'selected' : '' ?>>Studio</option>
                                <option value="commercial" <?= ($property['type'] ?? '') === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price ($) *</label>
                            <input type="number" name="price" class="form-control" step="0.01"
                                   value="<?= $property['price'] ?? '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?= htmlspecialchars($property['address'] ?? '') ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control" 
                                   value="<?= $property['bedrooms'] ?? 0 ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control" 
                                   value="<?= $property['bathrooms'] ?? 0 ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Area (sqft)</label>
                            <input type="number" name="area" class="form-control" 
                                   value="<?= $property['area'] ?? 0 ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="available" <?= ($property['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="rented" <?= ($property['status'] ?? '') === 'rented' ? 'selected' : '' ?>>Rented</option>
                            <option value="maintenance" <?= ($property['status'] ?? '') === 'maintenance' ?  'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Property Images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="overwrite_images" id="overwrite_images">
                            <label class="form-check-label text-muted small" for="overwrite_images">
                                Replace existing images (delete old ones)
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($existing_images)): ?>
                    <div class="mb-3">
                        <label class="form-label">Existing Images</label>
                        <div class="row g-2">
                            <?php foreach ($existing_images as $img): ?>
                            <div class="col-md-3 col-6 text-center">
                                <img src="../<?= htmlspecialchars($img['image_url']) ?>" class="img-thumbnail" style="height: 100px; object-fit: cover;">
                                <div class="mt-1">
                                    <a href="delete_property_image.php?id=<?= $img['id'] ?>&property_id=<?= $property_id ?>" 
                                       class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Property Videos</label>
                        <input type="file" name="videos[]" class="form-control" multiple accept="video/*">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="overwrite_videos" id="overwrite_videos">
                            <label class="form-check-label text-muted small" for="overwrite_videos">
                                Replace existing videos (delete old ones)
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($existing_videos)): ?>
                    <div class="mb-3">
                        <label class="form-label">Existing Videos</label>
                        <div class="row g-2">
                            <?php foreach ($existing_videos as $vid): ?>
                            <div class="col-md-3 col-6 text-center">
                                <video src="../<?= htmlspecialchars($vid['video_url']) ?>" class="img-thumbnail" style="height: 100px; object-fit: cover;" controls></video>
                                <div class="mt-1">
                                    <a href="delete_property_video.php?id=<?= $vid['id'] ?>&property_id=<?= $property_id ?>" 
                                       class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Property
                        </button>
                        <a href="properties.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>