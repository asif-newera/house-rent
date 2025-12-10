<?php
/**
 * Admin Users Management
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
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT COALESCE(is_active, 0) WHERE id = ?");
                $stmt->execute([intval($_POST['id'])]);
                $success_message = 'User status updated!';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
                $stmt->execute([intval($_POST['id'])]);
                $success_message = 'User deleted successfully!';
                break;
        }
    } catch (PDOException $e) {
        error_log("Users error: " . $e->getMessage());
        $error_message = "Error: " . $e->getMessage();
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE (name LIKE ? OR email LIKE ?) AND is_admin = 0" : "WHERE is_admin = 0";
$params = $search ? ["%$search%", "%$search%"] : [];

// Get total count
$count_query = "SELECT COUNT(*) FROM users $where";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $perPage);

// Get users
$query = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?= htmlspecialchars(APP_NAME) ?></title>
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
                <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-people"></i><span>Users</span></a></li>
                <li class="nav-item"><a class="nav-link" href="bookings.php"><i class="bi bi-calendar-check"></i><span>Bookings</span></a></li>
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
                        <h1 class="h3 mb-0 text-gray-800">Manage Users</h1>
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
                                <div class="col-md-8">
                                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i> Search</button>
                                    <a href="users.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Users List (<?= $total_users ?> total)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php $is_active = $user['is_active'] ?? 1; ?>
                                                    <span class="badge bg-<?= $is_active ? 'success' : 'danger' ?>">
                                                        <?= $is_active ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Toggle Status">
                                                            <i class="bi bi-toggle-<?= $is_active ? 'on' : 'off' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="7" class="text-center text-muted py-4">No users found</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= min($total_pages, 5); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">Next</a>
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
                        <p>Are you sure you want to delete <strong id="userName"></strong>?</p>
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
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        function deleteUser(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('userName').textContent = name;
            deleteModal.show();
        }
    </script>
</body>
</html>
