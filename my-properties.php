<?php
$pageTitle = 'My Properties';
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch properties the user has booked
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as image_url,
        b.id as booking_id,
        b.start_date,
        b.end_date,
        b.total_amount,
        b.status as booking_status,
        b.booking_date
        FROM properties p 
        INNER JOIN bookings b ON p.id = b.property_id
        WHERE b.user_id = ? 
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$userId]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching booked properties: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="my-properties-container">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-calendar-check"></i> My Bookings</h1>
                <p>View and manage your property bookings</p>
            </div>
        </div>

        <?php if (empty($properties)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times fa-4x"></i>
                <h2>No Bookings Yet</h2>
                <p>You haven't booked any properties. Browse available properties to make your first booking!</p>
                <a href="properties.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Properties
                </a>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <?php 
                            $imgUrl = $property['image_url'] ?? '';
                            $imgFull = !empty($imgUrl) ? __DIR__ . '/' . $imgUrl : '';
                            $displayImg = (!empty($imgUrl) && is_file($imgFull)) ? '/HOUSE%20RENT/' . $imgUrl : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';
                            ?>
                            <img src="<?php echo htmlspecialchars($displayImg); ?>" 
                                 alt="<?php echo htmlspecialchars($property['title']); ?>"
                                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';">
                            <div class="booking-status-badge <?php echo $property['booking_status']; ?>">
                                <?php echo ucfirst($property['booking_status']); ?>
                            </div>
                        </div>
                        <div class="property-info">
                            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p class="property-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($property['location'] ?? $property['address']); ?>
                            </p>
                            <div class="property-meta">
                                <div class="meta-item">
                                    <i class="fas fa-bed"></i>
                                    <?php echo $property['bedrooms']; ?> Beds
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-bath"></i>
                                    <?php echo $property['bathrooms']; ?> Baths
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-vector-square"></i>
                                    <?php echo $property['area']; ?> sqft
                                </div>
                            </div>
                            <div class="booking-details">
                                <div class="booking-info">
                                    <div class="info-row">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($property['start_date'])); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($property['end_date'])); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-clock"></i>
                                        <span><strong>Booked on:</strong> <?php echo date('M d, Y', strtotime($property['booking_date'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="property-price">
                                ৳<?php echo number_format($property['total_amount']); ?>
                                <span class="price-label">Total Amount</span>
                            </div>
                            <div class="property-actions">
                                <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-view">
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

<style>
.my-properties-container {
    padding: 40px 0 80px;
    background: #f8f9fa;
    min-height: calc(100vh - 85px);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #333;
    margin: 0 0 10px 0;
}

.page-header h1 i {
    color: #4A6BFF;
    margin-right: 15px;
}

.page-header p {
    color: #666;
    margin: 0;
    font-size: 1.1rem;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.empty-state i {
    color: #4A6BFF;
    opacity: 0.3;
    margin-bottom: 20px;
}

.empty-state h2 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 30px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.property-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.property-image {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.property-card:hover .property-image img {
    transform: scale(1.05);
}

.property-status-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
}

.property-status-badge.available {
    background: #28a745;
    color: white;
}

.property-status-badge.rented {
    background: #ffc107;
    color: #333;
}

.property-status-badge.sold {
    background: #dc3545;
    color: white;
}

.booking-status-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
}

.booking-status-badge.pending {
    background: #ffc107;
    color: #333;
}

.booking-status-badge.confirmed {
    background: #28a745;
    color: white;
}

.booking-status-badge.cancelled {
    background: #dc3545;
    color: white;
}

.booking-status-badge.completed {
    background: #17a2b8;
    color: white;
}

.property-info {
    padding: 20px;
}

.property-info h3 {
    font-size: 1.3rem;
    margin: 0 0 10px 0;
    color: #333;
}

.property-location {
    color: #666;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.property-location i {
    color: #4A6BFF;
}

.property-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 0.9rem;
}

.property-meta i {
    color: #4A6BFF;
}

.booking-details {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.booking-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #666;
    font-size: 0.9rem;
}

.info-row i {
    color: #4A6BFF;
    width: 16px;
}

.info-row strong {
    color: #333;
    margin-right: 5px;
}

.property-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4A6BFF;
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.price-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #666;
}

.property-actions {
    display: flex;
    gap: 10px;
}

.btn-view {
    background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
    color: white;
    padding: 12px 20px;
    font-size: 1rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 107, 255, 0.3);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
