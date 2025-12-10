<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ! isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get current admin user info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_email = $_SESSION['user_email'] ?? '';

// Initialize variables
$user_role = 'admin'; // Default role
$permissions = [];
$properties = [];
$users = [];
$recent_bookings = [];
$stats = [
    'total_properties' => 0,
    'available_properties' => 0,
    'rented_properties' => 0,
    'total_users' => 0,
    'total_bookings' => 0,
    'pending_bookings' => 0,
    'revenue' => 0,
    'monthly_revenue' => 0,
    'new_messages' => 0,
    'total_messages' => 0
];

try {
    // Get user role from database
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ? ");
    $stmt->execute([$admin_id]);
    $is_admin = $stmt->fetchColumn();
    $user_role = $is_admin ?  'admin' : 'user';
    
    // Get permissions
    $permissions = getRolePermissions($user_role);
    
    // ===== GET STATISTICS =====
    
    // Total Properties
    try {
        $query = "SELECT COUNT(*) FROM properties";
        if ($user_role !== 'admin') {
            $query .= " WHERE user_id = " . (int)$admin_id;
        }
        $stats['total_properties'] = $pdo->query($query)->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching total properties: " . $e->getMessage());
    }
    
    // Available Properties
    try {
        $query = "SELECT COUNT(*) FROM properties WHERE status = 'available'";
        if ($user_role !== 'admin') {
            $query .= " AND user_id = " . (int)$admin_id;
        }
        $stats['available_properties'] = $pdo->query($query)->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching available properties: " . $e->getMessage());
    }
    
    // Rented Properties
    try {
        $query = "SELECT COUNT(*) FROM properties WHERE status = 'rented'";
        if ($user_role !== 'admin') {
            $query .= " AND user_id = " . (int)$admin_id;
        }
        $stats['rented_properties'] = $pdo->query($query)->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error fetching rented properties: " . $e->getMessage());
    }
    
    // Total Users (admin only)
    if ($user_role === 'admin') {
        try {
            $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching total users: " . $e->getMessage());
        }
    }
    
    // Total Bookings (if table exists)
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'bookings'")->fetchAll();
        if (count($table_check) > 0) {
            $query = "SELECT COUNT(*) FROM bookings";
            if ($user_role !== 'admin') {
                $query .= " WHERE property_id IN (SELECT id FROM properties WHERE user_id = " . (int)$admin_id . ")";
            }
            $stats['total_bookings'] = $pdo->query($query)->fetchColumn();
            
            // Pending bookings
            $query = "SELECT COUNT(*) FROM bookings WHERE status = 'pending'";
            if ($user_role !== 'admin') {
                $query .= " AND property_id IN (SELECT id FROM properties WHERE user_id = " . (int)$admin_id . ")";
            }
            $stats['pending_bookings'] = $pdo->query($query)->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Error fetching bookings: " . $e->getMessage());
    }
    
    // Revenue (if payments table exists)
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'payments'")->fetchAll();
        if (count($table_check) > 0) {
            $query = "SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE status = 'completed'";
            if ($user_role !== 'admin') {
                $query . " AND booking_id IN (SELECT id FROM bookings WHERE property_id IN (SELECT id FROM properties WHERE user_id = " . (int)$admin_id . "))";
            }
            $stats['revenue'] = $pdo->query($query)->fetchColumn();
            
            // Monthly revenue
            $query = "SELECT COALESCE(SUM(amount_paid), 0) FROM payments 
                      WHERE status = 'completed' 
                      AND MONTH(payment_date) = MONTH(CURRENT_DATE()) 
                      AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
            if ($user_role !== 'admin') {
                $query .= " AND booking_id IN (SELECT id FROM bookings WHERE property_id IN (SELECT id FROM properties WHERE user_id = " . (int)$admin_id . "))";
            }
            $stats['monthly_revenue'] = $pdo->query($query)->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Error fetching revenue: " . $e->getMessage());
    }
    
    // Contact Messages (if table exists)
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'contact_messages'")->fetchAll();
        if (count($table_check) > 0) {
            $stats['total_messages'] = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
            $stats['new_messages'] = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Error fetching messages: " . $e->getMessage());
    }
    
    // ===== GET PROPERTIES =====
    try {
        $query = "SELECT p.*, u.name as owner_name 
                  FROM properties p 
                  LEFT JOIN users u ON p.user_id = u.id ";
        if ($user_role !== 'admin') {
            $query .= " WHERE p.user_id = " . (int)$admin_id;
        }
        $query .= " ORDER BY p.created_at DESC LIMIT 10";
        $properties = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching properties: " . $e->getMessage());
        $properties = [];
    }
    
    // ===== GET RECENT USERS (Admin only) =====
    if ($user_role === 'admin' && in_array('manage_users', $permissions)) {
        try {
            $users = $pdo->query("
                SELECT id, name, email, is_admin, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT 5
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching users: " . $e->getMessage());
            $users = [];
        }
    }
    
    // ===== GET RECENT BOOKINGS =====
    try {
        $table_check = $pdo->query("SHOW TABLES LIKE 'bookings'")->fetchAll();
        if (count($table_check) > 0) {
            $query = "SELECT 
                        b.id,
                        b.check_in,
                        b.check_out,
                        COALESCE(b.total_price, b.total_amount, 0) as total_price,
                        b.status,
                        COALESCE(b.booking_date, b.created_at) as booking_date,
                        p.title as property_title,
                        p.id as property_id,
                        u.name as user_name,
                        u.id as user_id
                      FROM bookings b
                      INNER JOIN properties p ON b.property_id = p.id
                      INNER JOIN users u ON b.user_id = u.id ";
            
            if ($user_role !== 'admin') {
                $query .= " WHERE p.user_id = " . (int)$admin_id;
            }
            
            $query .= " ORDER BY COALESCE(b.booking_date, b.created_at) DESC LIMIT 5";
            
            $recent_bookings = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching recent bookings: " . $e->getMessage());
        $recent_bookings = [];
    }
    
} catch (PDOException $e) {
    error_log("Dashboard fatal error: " . $e->getMessage());
    $error_message = "Error loading dashboard.  Please contact administrator.";
}

// Handle property actions (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (in_array('manage_properties', $permissions)) {
        try {
            switch ($_POST['action']) {
                case 'add_property':
                    $stmt = $pdo->prepare("
                        INSERT INTO properties (user_id, title, description, type, price, address, bedrooms, bathrooms, area, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $admin_id,
                        $_POST['title'] ?? '',
                        $_POST['description'] ?? '',
                        $_POST['type'] ?? 'house',
                        $_POST['price'] ?? 0,
                        $_POST['location'] ?? '',
                        $_POST['bedrooms'] ?? 0,
                        $_POST['bathrooms'] ?? 0,
                        $_POST['area'] ?? 0,
                        $_POST['status'] ?? 'available'
                    ]);
                    $_SESSION['success'] = 'Property added successfully';
                    break;

                case 'update_property':
                    $stmt = $pdo->prepare("
                        UPDATE properties 
                        SET title = ?, description = ?, type = ?, price = ?, address = ?, 
                            bedrooms = ?, bathrooms = ?, area = ?, status = ? 
                        WHERE id = ?  AND (user_id = ? OR ?  = 'admin')
                    ");
                    $stmt->execute([
                        $_POST['title'] ?? '',
                        $_POST['description'] ?? '',
                        $_POST['type'] ?? 'house',
                        $_POST['price'] ?? 0,
                        $_POST['location'] ?? '',
                        $_POST['bedrooms'] ?? 0,
                        $_POST['bathrooms'] ?? 0,
                        $_POST['area'] ?? 0,
                        $_POST['status'] ?? 'available',
                        $_POST['id'] ?? 0,
                        $admin_id,
                        $user_role
                    ]);
                    $_SESSION['success'] = 'Property updated successfully';
                    break;

                case 'delete_property':
                    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND (user_id = ? OR ? = 'admin')");
                    $stmt->execute([
                        $_POST['id'] ?? 0, 
                        $admin_id, 
                        $user_role
                    ]);
                    $_SESSION['success'] = 'Property deleted successfully';
                    break;
            }
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Property action error: " . $e->getMessage());
            $_SESSION['error'] = 'Operation failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= htmlspecialchars(APP_NAME) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, #224abe 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            font-size: 1.3rem;
            font-weight: 800;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding-left: 2rem;
        }
        
        .sidebar-menu a i {
            width: 20px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        /* Top Bar */
        .topbar {
            background: white;
            padding: 1rem 1.5rem;
            margin: -2rem -2rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        /* Cards */
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }
        
        .stat-card. primary { border-left-color: var(--primary); }
        .stat-card. success { border-left-color: var(--success); }
        .stat-card.info { border-left-color: var(--info); }
        .stat-card.warning { border-left-color: var(--warning); }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        /* Table */
        .table {
            font-size: 0.9rem;
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #858796;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-home"></i> Admin Panel
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="properties.php">
                <i class="fas fa-building"></i> Properties
            </a>
            <a href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="bookings.php">
                <i class="fas fa-calendar-check"></i> Bookings
            </a>
            <a href="messages.php">
                <i class="fas fa-envelope"></i> Messages
                <?php if ($stats['new_messages'] > 0): ?>
                    <span style="background: #ff6b6b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 5px;">
                        <?= $stats['new_messages'] ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 1rem;">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <div>
                <h4 class="mb-0">Dashboard</h4>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($admin_name, 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($admin_name) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($admin_email) ?></small>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Properties
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($stats['total_properties']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-building stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Available
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($stats['available_properties']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Bookings
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= number_format($stats['total_bookings']) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-check stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Revenue
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    $<?= number_format($stats['revenue'], 2) ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Properties -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Properties</h6>
                <a href="properties.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($properties)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No properties found.  Add your first property!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                <tr>
                                    <td><?= $property['id'] ?></td>
                                    <td><?= htmlspecialchars($property['title']) ?></td>
                                    <td><?= ucfirst($property['type'] ?? 'N/A') ?></td>
                                    <td><strong>$<?= number_format($property['price'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= $property['status'] === 'available' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($property['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="property-edit.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../property-details.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <?php if (! empty($recent_bookings)): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Guest</th>
                                <th>Check-in</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td>#<?= $booking['id'] ?></td>
                                <td><?= htmlspecialchars($booking['property_title']) ?></td>
                                <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                <td><?= isset($booking['check_in']) ?  date('M d, Y', strtotime($booking['check_in'])) : 'N/A' ?></td>
                                <td><strong>$<?= number_format($booking['total_price'], 2) ?></strong></td>
                                <td>
                                    <?php
                                    $badge_class = match($booking['status'] ?? 'pending') {
                                        'confirmed' => 'success',
                                        'completed' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge_class ?>">
                                        <?= ucfirst($booking['status'] ?? 'pending') ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            });
        }, 5000);
        
        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
    </script>
</body>
</html>