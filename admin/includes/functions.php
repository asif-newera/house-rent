<?php
// Function to check user permissions
function hasPermission($permission, $role = null) {
    if ($role === null) {
        $role = $_SESSION['user_role'] ?? 'guest';
    }
    
    $permissions = [
        'admin' => [
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_properties',
            'manage_bookings',
            'view_reports',
            'manage_settings'
        ],
        'manager' => [
            'view_dashboard',
            'manage_properties',
            'manage_bookings',
            'view_reports'
        ],
        'agent' => [
            'view_dashboard',
            'manage_properties',
            'view_bookings'
        ],
        'guest' => []
    ];
    
    return in_array($permission, $permissions[$role] ?? []);
}

// Function to get role permissions
function getRolePermissions($role) {
    return hasPermission('*', $role) ? ['all'] : [];
}

// Function to get all roles
function getAllRoles() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Function to get status badge
function getStatusBadge($status) {
    $statuses = [
        'active' => 'success',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'approved' => 'info',
        'rejected' => 'danger',
        'available' => 'success',
        'rented' => 'danger',
        'maintenance' => 'warning'
    ];
    
    $class = $statuses[strtolower($status)] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . ucfirst($status) . '</span>';
}

// Function to log activity
function logActivity($userId, $action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Function to get recent activities
function getRecentActivities($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as user_name, u.email as user_email 
        FROM activity_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
