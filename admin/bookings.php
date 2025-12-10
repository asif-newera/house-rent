<?php
/**
 * Admin Bookings Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_token']) && csrf_verify($_POST['_token'])) {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_status':
                $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $stmt->execute([sanitize($_POST['status']), intval($_POST['id'])]);
                $success_message = 'Booking status updated!';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $stmt->execute([intval($_POST['id'])]);
                $success_message = 'Booking deleted successfully!';
                break;
        }
    } catch (PDOException $e) {
        error_log("Bookings error: " . $e->getMessage());
        $error_message = "Error: " . $e->getMessage();
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filter
$status_filter = $_GET['status'] ?? '';
$where = $status_filter ? "WHERE b.status = ?" : "";
$params = $status_filter ? [$status_filter] : [];

// Get total count
$count_query = "SELECT COUNT(*) FROM bookings b $where";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_bookings = $stmt->fetchColumn();
$total_pages = ceil($total_bookings / $perPage);

// Get bookings
$query = "SELECT b.*, p.title as property_title, u.name as user_name, u.email as user_email
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON b.user_id = u.id
          $where
          ORDER BY b.booking_date DESC
          LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div id="wrapper">
        <nav class="sidebar" id="sidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon"><i class="bi bi-house-heart-fill"></i></div>
                <div class="sidebar-brand-text mx-3"><?= htmlspecialchars(APP_NAME) ?></div>
            </a>
            <hr class="sidebar-divider my-0">
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
                <hr class="sidebar-divider">
                <div class="sidebar-heading">Management</div>
                <li class="nav-item"><a class="nav-link" href="properties.php"><i class="bi bi-building"></i><span>Properties</span></a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i><span>Users</span></a></li>
                <li class="nav-item"><a class="nav-link active" href="bookings.php"><i class="bi bi-calendar-check"></i><span>Bookings</span></a></li>
                <hr class="sidebar-divider">
                <li class="nav-item"><a class="nav-link" href="../index.php" target="_blank"><i class="bi bi-globe"></i><span>View Website</span></a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </nav>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="topbar navbar navbar-expand navbar-light bg-white shadow-sm">
                    <button class="sidebar-toggle btn btn-link d-md-none rounded-circle me-3"><i class="bi bi-list"></i></button>
                    <ul class="navbar-nav ms-auto topbar-nav">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="user-info">
                                    <img class="user-avatar" src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=4e73df&color=fff" alt="Avatar">
                                    <span class="user-name d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($user_name) ?></span>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow">
                                <a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                            </div>
                        </li>
                    </ul>
                </nav>
                
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Manage Bookings</h1>
                    </div>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Filter</button>
                                    <a href="bookings.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Bookings List (<?= $total_bookings ?> total)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Property</th>
                                            <th>User</th>
                                            <th>Check-In</th>
                                            <th>Check-Out</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($bookings)): ?>
                                            <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?= $booking['id'] ?></td>
                                                <td><?= htmlspecialchars($booking['property_title']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($booking['user_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($booking['user_email']) ?></small>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($booking['start_date'])) ?></td>
                                                <td><?= date('M d, Y', strtotime($booking['end_date'])) ?></td>
                                                <td>৳<?= number_format($booking['total_amount'], 2) ?></td>
                                                <td>
                                                    <?php
                                                    $badge_color = match($booking['status']) {
                                                        'confirmed' => 'success',
                                                        'pending' => 'warning',
                                                        'cancelled' => 'danger',
                                                        'completed' => 'info',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?= $badge_color ?>">
                                                        <?= ucfirst($booking['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="editBooking(<?= $booking['id'] ?>, '<?= $booking['status'] ?>')" title="Edit Status">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteBooking(<?= $booking['id'] ?>, '<?= addslashes($booking['property_title']) ?>')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="8" class="text-center text-muted py-4">No bookings found</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= min($total_pages, 5); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; <?= htmlspecialchars(APP_NAME) ?> <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Edit Status Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="editId">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Booking Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this booking for <strong id="bookingName"></strong>?</p>
                        <p class="text-danger"><small>This action cannot be undone.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        function editBooking(id, status) {
            document.getElementById('editId').value = id;
            document.getElementById('editStatus').value = status;
            editModal.show();
        }
        
        function deleteBooking(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('bookingName').textContent = name;
            deleteModal.show();
        }
    </script>
</body>
</html>
