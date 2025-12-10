<?php
/**
 * Admin Properties Management - CRUD Operations
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['_token']) || !verifyCsrfToken($_POST['_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        try {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'add':
                    $stmt = $pdo->prepare("
                        INSERT INTO properties (title, description, price, location, address, bedrooms, bathrooms, area, property_type, type, status, user_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        sanitizeInput($_POST['title']),
                        sanitizeInput($_POST['description']),
                        floatval($_POST['price']),
                        sanitizeInput($_POST['location']),
                        sanitizeInput($_POST['address'] ?? $_POST['location']),
                        intval($_POST['bedrooms'] ?? 0),
                        intval($_POST['bathrooms'] ?? 0),
                        intval($_POST['area'] ?? 0),
                        sanitizeInput($_POST['property_type'] ?? 'apartment'),
                        sanitizeInput($_POST['property_type'] ?? 'apartment'),
                        sanitizeInput($_POST['status'] ?? 'available'),
                        $user_id
                    ]);
                    $success_message = 'Property added successfully!';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE properties 
                        SET title = ?, description = ?, price = ?, location = ?, address = ?, 
                            bedrooms = ?, bathrooms = ?, area = ?, property_type = ?, type = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        sanitizeInput($_POST['title']),
                        sanitizeInput($_POST['description']),
                        floatval($_POST['price']),
                        sanitizeInput($_POST['location']),
                        sanitizeInput($_POST['address'] ?? $_POST['location']),
                        intval($_POST['bedrooms'] ?? 0),
                        intval($_POST['bathrooms'] ?? 0),
                        intval($_POST['area'] ?? 0),
                        sanitizeInput($_POST['property_type'] ?? 'apartment'),
                        sanitizeInput($_POST['property_type'] ?? 'apartment'),
                        sanitizeInput($_POST['status'] ?? 'available'),
                        intval($_POST['id'])
                    ]);
                    $success_message = 'Property updated successfully!';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
                    $stmt->execute([intval($_POST['id'])]);
                    $success_message = 'Property deleted successfully!';
                    break;
                    
                case 'toggle_featured':
                    $stmt = $pdo->prepare("UPDATE properties SET is_featured = NOT COALESCE(is_featured, 0) WHERE id = ?");
                    $stmt->execute([intval($_POST['id'])]);
                    $success_message = 'Property featured status updated!';
                    break;
            }
            
            
            // Handle File Uploads for Add/Update
            if (($action === 'add' || $action === 'update') && empty($error_message)) {
                $property_id = ($action === 'add') ? $pdo->lastInsertId() : intval($_POST['id']);
                
                // Handle Images
                if (!empty($_FILES['images']['name'][0])) {
                    // Check for overwrite
                    if (isset($_POST['overwrite_images'])) {
                         $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
                         $stmt->execute([$property_id]);
                         $oldImages = $stmt->fetchAll();
                         foreach ($oldImages as $img) {
                             $path = __DIR__ . '/../' . $img['image_url'];
                             if (file_exists($path)) unlink($path);
                         }
                         $pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$property_id]);
                    }

                    $targetDir = __DIR__ . '/../assets/images/properties/';
                    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                    
                    $totalFiles = count($_FILES['images']['name']);
                    $uploadedImages = [];
                    
                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                            $fileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['images']['name'][$i]));
                            $targetFilePath = $targetDir . $fileName;
                            $dbFilePath = "assets/images/properties/" . $fileName;
                            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFilePath)) {
                                $stmt_img = $pdo->prepare("INSERT INTO property_images (property_id, image_url, created_at) VALUES (?, ?, NOW())");
                                $stmt_img->execute([$property_id, $dbFilePath]);
                                $uploadedImages[] = $dbFilePath;
                            }
                        }
                    }
                    
                    // Set primary image if needed
                    if (!empty($uploadedImages)) {
                        $checkStmt = $pdo->prepare("SELECT image_url FROM properties WHERE id = ?");
                        $checkStmt->execute([$property_id]);
                        if (empty($checkStmt->fetchColumn()) || isset($_POST['overwrite_images'])) {
                            $updateMain = $pdo->prepare("UPDATE properties SET image_url = ? WHERE id = ?");
                            $updateMain->execute([$uploadedImages[0], $property_id]);
                        }
                    }
                }
                
                // Handle Videos
                if (!empty($_FILES['videos']['name'][0])) {
                    // Check for overwrite
                    if (isset($_POST['overwrite_videos'])) {
                         $stmt = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ?");
                         $stmt->execute([$property_id]);
                         $oldVideos = $stmt->fetchAll();
                         foreach ($oldVideos as $vid) {
                             $path = __DIR__ . '/../' . $vid['video_url'];
                             if (file_exists($path)) unlink($path);
                         }
                         $pdo->prepare("DELETE FROM property_videos WHERE property_id = ?")->execute([$property_id]);
                    }

                    $targetDir = __DIR__ . '/../assets/videos/properties/';
                    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                    
                    $totalFiles = count($_FILES['videos']['name']);
                    
                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($_FILES['videos']['error'][$i] === UPLOAD_ERR_OK) {
                            $fileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['videos']['name'][$i]));
                            $targetFilePath = $targetDir . $fileName;
                            $dbFilePath = "assets/videos/properties/" . $fileName;
                            
                            if (move_uploaded_file($_FILES['videos']['tmp_name'][$i], $targetFilePath)) {
                                $stmt_vid = $pdo->prepare("INSERT INTO property_videos (property_id, video_url, created_at) VALUES (?, ?, NOW())");
                                $stmt_vid->execute([$property_id, $dbFilePath]);
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Properties error: " . $e->getMessage());
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(title LIKE ? OR location LIKE ? OR address LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) FROM properties $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_properties = $stmt->fetchColumn();
$total_pages = ceil($total_properties / $perPage);

// Get properties
$query = "SELECT p.*, u.name as creator_name 
          FROM properties p 
          LEFT JOIN users u ON p.user_id = u.id 
          $where_clause 
          ORDER BY p.created_at DESC 
          LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - <?= htmlspecialchars(APP_NAME) ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar (same as dashboard) -->
        <nav class="sidebar" id="sidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-house-heart-fill"></i>
                </div>
                <div class="sidebar-brand-text mx-3"><?= htmlspecialchars(APP_NAME) ?></div>
            </a>
            
            <hr class="sidebar-divider my-0">
            
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <hr class="sidebar-divider">
                <div class="sidebar-heading">Management</div>
                
                <li class="nav-item">
                    <a class="nav-link active" href="properties.php">
                        <i class="bi bi-building"></i>
                        <span>Properties</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Users</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="bookings.php">
                        <i class="bi bi-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                
                <hr class="sidebar-divider">
                <div class="sidebar-heading">Other</div>
                
                <li class="nav-item">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="bi bi-globe"></i>
                        <span>View Website</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="topbar navbar navbar-expand navbar-light bg-white shadow-sm">
                    <button class="sidebar-toggle btn btn-link d-md-none rounded-circle me-3">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <form class="d-none d-sm-inline-block form-inline me-auto ms-md-3 my-2 my-md-0 mw-100 navbar-search topbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search..." aria-label="Search">
                            <button class="btn btn-primary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <ul class="navbar-nav ms-auto topbar-nav">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="user-info">
                                    <img class="user-avatar" src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=4e73df&color=fff" alt="User Avatar">
                                    <span class="user-name d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($user_name) ?></span>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in">
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                
                <!-- Page Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Properties</h1>
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#propertyModal" onclick="resetForm()">
                            <i class="bi bi-plus-circle me-1"></i> Add New Property
                        </button>
                    </div>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Search and Filter -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-5">
                                    <input type="text" name="search" class="form-control" placeholder="Search properties..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="available" <?= $status_filter === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="rented" <?= $status_filter === 'rented' ? 'selected' : '' ?>>Rented</option>
                                        <option value="maintenance" <?= $status_filter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                    <a href="properties.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Properties Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Properties List (<?= $total_properties ?> total)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Price</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($properties)): ?>
                                            <?php foreach ($properties as $property): ?>
                                            <tr>
                                                <td><?= $property['id'] ?></td>
                                                <td>
                                                    <?= htmlspecialchars($property['title']) ?>
                                                    <?php if (!empty($property['is_featured'])): ?>
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($property['location']) ?></td>
                                                <td>৳<?= number_format($property['price'], 2) ?></td>
                                                <td><?= htmlspecialchars(ucfirst($property['property_type'] ?? 'N/A')) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $property['status'] === 'available' ? 'success' : ($property['status'] === 'rented' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($property['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($property['created_at'])) ?></td>
                                                <td>
                                                    <a href="property-edit.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-info" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteProperty(<?= $property['id'] ?>, '<?= addslashes($property['title']) ?>')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    No properties found. <a href="#" data-bs-toggle="modal" data-bs-target="#propertyModal" onclick="resetForm()">Add your first property</a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; <?= htmlspecialchars(APP_NAME) ?> <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Add/Edit Property Modal -->
    <div class="modal fade" id="propertyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="propertyForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="propertyId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" class="form-control" name="title" id="title" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description *</label>
                                <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (৳) *</label>
                                <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" name="property_type" id="property_type">
                                    <option value="apartment">Apartment</option>
                                    <option value="house">House</option>
                                    <option value="villa">Villa</option>
                                    <option value="studio">Studio</option>
                                    <option value="commercial">Commercial</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Location *</label>
                                <input type="text" class="form-control" name="location" id="location" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Full Address</label>
                                <input type="text" class="form-control" name="address" id="address">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bedrooms</label>
                                <input type="number" class="form-control" name="bedrooms" id="bedrooms" min="0" value="0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" class="form-control" name="bathrooms" id="bathrooms" min="0" value="0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Area (sqft)</label>
                                <input type="number" class="form-control" name="area" id="area" min="0" value="0">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="available">Available</option>
                                    <option value="rented">Rented</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Property Photos</label>
                                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="overwrite_images" id="overwrite_images">
                                    <label class="form-check-label text-muted small" for="overwrite_images">
                                        Delete existing and replace with these
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Property Videos</label>
                                <input type="file" name="videos[]" class="form-control" multiple accept="video/*">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="overwrite_videos" id="overwrite_videos">
                                    <label class="form-check-label text-muted small" for="overwrite_videos">
                                        Delete existing and replace with these
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Save Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="propertyName"></strong>?</p>
                        <p class="text-danger"><small>This action cannot be undone.</small></p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    
    <script>
        const propertyModal = new bootstrap.Modal(document.getElementById('propertyModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        function resetForm() {
            document.getElementById('propertyForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('propertyId').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Property';
        }
        
        function editProperty(property) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('propertyId').value = property.id;
            document.getElementById('modalTitle').textContent = 'Edit Property';
            document.getElementById('title').value = property.title;
            document.getElementById('description').value = property.description || '';
            document.getElementById('price').value = property.price;
            document.getElementById('location').value = property.location || '';
            document.getElementById('address').value = property.address || '';
            document.getElementById('bedrooms').value = property.bedrooms || 0;
            document.getElementById('bathrooms').value = property.bathrooms || 0;
            document.getElementById('area').value = property.area || 0;
            document.getElementById('property_type').value = property.property_type || 'apartment';
            document.getElementById('status').value = property.status || 'available';
            propertyModal.show();
        }
        
        function deleteProperty(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('propertyName').textContent = name;
            deleteModal.show();
        }
    </script>
</body>
</html>
