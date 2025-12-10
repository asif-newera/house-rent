<?php
/**
 * My Bookings - User Dashboard
 * Displays booking history for logged-in users
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pageTitle = 'My Bookings';

// Get user's bookings
try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.title as property_title, p.location, p.image_url
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error fetching bookings: ' . $e->getMessage());
    $bookings = [];
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4"><i class="fas fa-calendar-check"></i> My Bookings</h1>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (empty($bookings)): ?>
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h3>No Bookings Yet</h3>
                        <p class="text-muted">You haven't made any bookings yet.</p>
                        <a href="properties.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Properties
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <?php
                                $imgPath = $booking['image_url'] ?? '';
                                $imgFull = !empty($imgPath) ? __DIR__ . '/' . $imgPath : '';
                                $imgDisplay = (!empty($imgPath) && is_file($imgFull)) ? '/HOUSE%20RENT/' . $imgPath : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=400&q=80';
                                ?>
                                <img src="<?php echo htmlspecialchars($imgDisplay); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($booking['property_title']); ?>"
                                     style="height: 200px; object-fit: cover;"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=400&q=80';">
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($booking['property_title']); ?></h5>
                                    <p class="text-muted small">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?>
                                    </p>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">Booking ID:</small>
                                        <strong>#<?php echo $booking['id']; ?></strong>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-calendar"></i>
                                        <small>
                                            <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong class="text-primary">৳<?php echo number_format($booking['total_amount']); ?></strong>
                                    </div>
                                    
                                    <?php
                                    $badge_color = match($booking['status']) {
                                        'confirmed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        'completed' => 'info',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="card-footer bg-white">
                                    <a href="property-details.php?id=<?php echo $booking['property_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Property
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>
