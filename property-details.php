<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: properties.php');
    exit();
}

$propertyId = (int)$_GET['id'];

try {
    // Get property details
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$property) {
        header('Location: 404.php');
        exit();
    }
    
    // Get property features
    $featuresStmt = $pdo->prepare("SELECT * FROM property_features WHERE property_id = ?");
    $featuresStmt->execute([$propertyId]);
    $features = $featuresStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get property images
    $imagesStmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
    $imagesStmt->execute([$propertyId]);
    $images = $imagesStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // If no additional images, use the main image
    if (empty($images) && !empty($property['image_url'])) {
        $images = [$property['image_url']];
    }
    
    // Get property videos
    $videosStmt = $pdo->prepare("SELECT * FROM property_videos WHERE property_id = ?");
    $videosStmt->execute([$propertyId]);
    $videos = $videosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get related properties
    $relatedStmt = $pdo->prepare(
        "SELECT * FROM properties 
         WHERE id != ? AND location LIKE ? 
         ORDER BY RAND() LIMIT 3"
    );
    $relatedStmt->execute([$propertyId, "%{$property['location']}%"]);
    $relatedProperties = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    header('Location: error.php');
    exit();
}

// Set page title
$pageTitle = $property['title'] . ' - Property Details';


$additionalStyles = <<<CSS
/* General Layout */
.property-details-page {
    padding-bottom: 80px;
    background-color: #f8f9fc;
}

.container {
    max-width: 1200px;
}

/* Hero Section */
.hero-gallery {
    margin-bottom: 40px;
    padding-top: 20px;
}

.hero-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 15px;
    height: 500px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.hero-main {
    height: 100%;
    position: relative;
    overflow: hidden;
}

.hero-main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.hero-main:hover img {
    transform: scale(1.02);
}

.hero-side {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.hero-sub {
    flex: 1;
    position: relative;
    overflow: hidden;
}

.hero-sub img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.hero-sub:hover img {
    transform: scale(1.05);
}

.more-overlay-container {
    position: relative;
    cursor: pointer;
}

.more-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
    backdrop-filter: blur(2px);
    transition: background 0.3s;
}

.more-overlay:hover {
    background: rgba(0, 0, 0, 0.4);
}

/* Content Layout */
.property-content {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 40px;
}

/* Main Content Area */
.property-main {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.03);
}

.property-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1a1a1a;
    margin-bottom: 15px;
    line-height: 1.2;
}

.property-location {
    font-size: 1.1rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.property-price {
    font-size: 2.2rem;
    font-weight: 800;
    color: #2c5bf7; /* Primary Brand Color */
    display: flex;
    align-items: baseline;
    gap: 10px;
}

.price-suffix {
    font-size: 1.2rem;
    font-weight: 500;
    color: #888;
}

/* Features Grid */
.property-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 20px;
    margin: 30px 0;
    padding: 30px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.feature {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 15px;
    background: #f8f9fc;
    border-radius: 12px;
    transition: transform 0.2s;
}

.feature:hover {
    transform: translateY(-3px);
    background: #f0f4ff;
}

.feature i {
    font-size: 1.5rem;
    color: #2c5bf7;
    margin-bottom: 10px;
}

.feature span {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

/* Description & Amenities */
h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
}

.property-description {
    line-height: 1.8;
    color: #4a4a4a;
    font-size: 1.05rem;
    margin-bottom: 40px;
}

.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.amenity {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #fff;
    border: 1px solid #eee;
    border-radius: 8px;
    color: #555;
    font-weight: 500;
}

.amenity i {
    color: #2c5bf7;
}

/* Sidebar */
.property-sidebar-wrapper {
    position: relative;
}

.sticky-top {
    position: sticky;
    top: 100px; /* Adjust based on header height */
    z-index: 90;
}

.contact-agent {
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    border: 1px solid #eee;
}

.agent-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid #f0f0f0;
}

.agent-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #2c5bf7;
}

.agent-details h4 {
    margin: 0 0 5px;
    font-size: 1.1rem;
    font-weight: 700;
}

.contact-form .form-group {
    margin-bottom: 15px;
}

.contact-form input,
.contact-form textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #f0f0f0;
    border-radius: 10px;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.contact-form input:focus,
.contact-form textarea:focus {
    border-color: #2c5bf7;
    outline: none;
    box-shadow: 0 0 0 4px rgba(44, 91, 247, 0.1);
}

.btn-primary {
    background: #2c5bf7;
    border: none;
    padding: 14px;
    border-radius: 10px;
    font-weight: 600;
    width: 100%;
    letter-spacing: 0.5px;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #1a4bd6;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(44, 91, 247, 0.3);
}

.safety-tips {
    margin-top: 25px;
    background: #fff8e6;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid #f6c23e;
}

.safety-tips h3 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #856404;
}

.safety-tips ul li {
    font-size: 0.9rem;
    margin-bottom: 8px;
    color: #666;
}

/* Related Properties */
.related-properties {
    margin-top: 60px;
}

.property-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid #eee;
}

.property-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.property-image {
    height: 220px;
    position: relative;
}

.img-fluid {
    width: 100%;
    height: auto;
}

/* Responsive */
@media (max-width: 992px) {
    .hero-grid {
        height: 400px;
    }
    .property-content {
        grid-template-columns: 1fr;
    }
    .hero-grid {
        grid-template-columns: 1fr;
    }
    .hero-side {
        display: none; /* Hide side images on tablet/mobile for simpler view */
    }
}
CSS;

include 'includes/header.php'; ?>

<div class="property-details-page">
    <div class="container">
        <!-- Modern Hero Gallery -->
        <div class="hero-gallery">
            <div class="hero-grid">
                <!-- Main Large Image -->
                <div class="hero-main">
                    <?php 
                    $mainImgPath = $images[0] ?? '';
                    $mainImgFull = !empty($mainImgPath) ? __DIR__ . '/' . $mainImgPath : '';
                    $mainDisplay = (!empty($mainImgPath) && is_file($mainImgFull)) ? '/HOUSE%20RENT/' . $mainImgPath : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1200&q=90';
                    ?>
                    <img src="<?php echo htmlspecialchars($mainDisplay); ?>" 
                         alt="<?php echo htmlspecialchars($property['title']); ?>"
                         class="img-fluid"
                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1200&q=90';">
                </div>
                
                <!-- Side Grid Images -->
                <div class="hero-side">
                    <?php if (count($images) > 1): ?>
                        <?php 
                        $secondImg = $images[1]; 
                        $secondImgFull = __DIR__ . '/' . $secondImg;
                        $secondDisplay = (is_file($secondImgFull)) ? '/HOUSE%20RENT/' . $secondImg : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=600&q=80';
                        ?>
                        <div class="hero-sub">
                            <img src="<?php echo htmlspecialchars($secondDisplay); ?>" alt="Property Image 2">
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($images) > 2): ?>
                        <?php 
                        $thirdImg = $images[2];
                        $thirdImgFull = __DIR__ . '/' . $thirdImg;
                        $thirdDisplay = (is_file($thirdImgFull)) ? '/HOUSE%20RENT/' . $thirdImg : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=600&q=80';
                        ?>
                        <div class="hero-sub more-overlay-container">
                            <img src="<?php echo htmlspecialchars($thirdDisplay); ?>" alt="Property Image 3">
                            <?php if (count($images) > 3): ?>
                                <div class="more-overlay">
                                    <span>+<?php echo count($images) - 3; ?> Photos</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="property-content">
            <div class="property-main">
                <div class="property-header">
                    <h1><?php echo htmlspecialchars($property['title']); ?></h1>
                    <p class="property-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['location']); ?>
                    </p>
                    <div class="property-price">
                        ৳<?php echo number_format($property['price']); ?>
                        <?php if (!empty($property['price_suffix'])): ?>
                            <span class="price-suffix"><?php echo htmlspecialchars($property['price_suffix']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="property-features">
                    <div class="feature">
                        <i class="fas fa-bed"></i>
                        <span><?php echo $property['bedrooms'] ?? 'N/A'; ?> Bedrooms</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bath"></i>
                        <span><?php echo $property['bathrooms'] ?? 'N/A'; ?> Bathrooms</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-vector-square"></i>
                        <span><?php echo $property['area'] ? $property['area'] . ' sqft' : 'N/A'; ?></span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-home"></i>
                        <span><?php echo ucfirst(htmlspecialchars($property['type'] ?? 'Property')); ?></span>
                    </div>
                </div>
                
                <div class="property-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($property['description'] ?? 'No description available.')); ?></p>
                </div>
                
                <?php if (!empty($features)): ?>
                    <div class="property-amenities">
                        <h3>Features & Amenities</h3>
                        <div class="amenities-grid">
                            <?php foreach ($features as $feature): ?>
                                <div class="amenity">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo htmlspecialchars($feature['feature_name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($videos)): ?>
                    <div class="property-videos">
                        <h3>Property Videos</h3>
                        <div class="videos-grid">
                            <?php foreach ($videos as $video): ?>
                                <div class="video-container">
                                    <video controls class="w-100 rounded">
                                        <source src="/HOUSE%20RENT/<?php echo htmlspecialchars($video['video_url']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="property-location">
                    <h3>Location</h3>
                    <div id="propertyMap" class="property-map" 
                         data-lat="<?php echo htmlspecialchars($property['latitude'] ?? '23.8103'); ?>" 
                         data-lng="<?php echo htmlspecialchars($property['longitude'] ?? '90.4125'); ?>">
                        <!-- Map will be loaded here via JavaScript -->
                    </div>
                    <p class="map-note">
                        <i class="fas fa-info-circle"></i>
                        The exact location will be provided after booking.
                    </p>
                </div>
            </div>
            
            <div class="property-sidebar-wrapper">
                <div class="property-sidebar sticky-top">
                <div class="contact-agent">
                    <h3><i class="fas fa-calendar-check"></i> Book This Property</h3>
                    
                    <?php if ($isLoggedIn): ?>
                        <div class="price-display mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Price per month:</span>
                                <strong class="text-primary">৳<?php echo number_format($property['price']); ?></strong>
                            </div>
                        </div>
                        
                        <form id="bookingForm" action="process-booking.php" method="POST">
                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                            
                            <div class="form-group">
                                <label>Check-In Date</label>
                                <input type="date" name="start_date" id="checkIn" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Check-Out Date</label>
                                <input type="date" name="end_date" id="checkOut" class="form-control" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3" 
                                          placeholder="Any special requirements or notes..."></textarea>
                            </div>
                            
                            <div class="booking-summary p-3 mb-3" style="background: #f8f9fc; border-radius: 8px;">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Duration:</span>
                                    <strong id="duration">Select dates</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Price:</span>
                                    <strong class="text-primary" id="totalPrice">৳0</strong>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check-circle"></i> Book Now
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Please <a href="/HOUSE%20RENT/login.php" class="alert-link">login</a> to book this property.
                        </div>
                        <a href="/HOUSE%20RENT/login.php" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Login to Book
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="safety-tips">
                    <h3>Safety Tips</h3>
                    <ul>
                        <li>Always meet in a public place</li>
                        <li>Never pay with cash or wire transfer</li>
                        <li>Inspect the property before paying any deposit</li>
                        <li>Verify ownership documents</li>
                    </ul>
                </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($relatedProperties)): ?>
            <section class="related-properties">
                <h2>Similar Properties</h2>
                <div class="properties-grid">
                    <?php foreach ($relatedProperties as $related): ?>
                        <div class="property-card">
                            <div class="property-image">
                                <?php 
                                $relImgPath = $related['image_url'] ?? '';
                                $relImgFull = !empty($relImgPath) ? __DIR__ . '/' . $relImgPath : '';
                                $relDisplay = (!empty($relImgPath) && is_file($relImgFull)) ? '/HOUSE%20RENT/' . $relImgPath : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';
                                ?>
                                <img src="<?php echo htmlspecialchars($relDisplay); ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';">
                                <span class="property-type"><?php echo ucfirst(htmlspecialchars($related['type'] ?: 'Property')); ?></span>
                                <span class="property-price">৳<?php echo number_format($related['price']); ?></span>
                            </div>
                            <div class="property-details">
                                <h3><a href="property-details.php?id=<?php echo $related['id']; ?>">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a></h3>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($related['location']); ?>
                                </p>
                                <div class="property-features">
                                    <span><i class="fas fa-bed"></i> <?php echo $related['bedrooms'] ?: 'N/A'; ?> Beds</span>
                                    <span><i class="fas fa-bath"></i> <?php echo $related['bathrooms'] ?: 'N/A'; ?> Baths</span>
                                    <span><i class="fas fa-vector-square"></i> <?php echo $related['area'] ? $related['area'] . ' sqft' : 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>


<script>
// Booking form date validation and price calculation
const checkInInput = document.getElementById('checkIn');
const checkOutInput = document.getElementById('checkOut');
const durationDisplay = document.getElementById('duration');
const totalPriceDisplay = document.getElementById('totalPrice');
const propertyPrice = <?php echo $property['price']; ?>;

function calculateBooking() {
    if (!checkInInput || !checkOutInput) return;
    
    const checkIn = new Date(checkInInput.value);
    const checkOut = new Date(checkOutInput.value);
    
    if (checkInInput.value && checkOutInput.value && checkOut > checkIn) {
        // Calculate number of days
        const timeDiff = checkOut - checkIn;
        const days = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
        const months = (days / 30).toFixed(1);
        
        // Calculate total price (assuming monthly rate)
        const totalPrice = Math.ceil((days / 30) * propertyPrice);
        
        // Update display
        durationDisplay.textContent = `${days} days (${months} months)`;
        totalPriceDisplay.textContent = `৳${totalPrice.toLocaleString()}`;
    } else {
        durationDisplay.textContent = 'Select dates';
        totalPriceDisplay.textContent = '৳0';
    }
}

// Update check-out min date when check-in changes
if (checkInInput) {
    checkInInput.addEventListener('change', function() {
        const checkInDate = new Date(this.value);
        const minCheckOut = new Date(checkInDate);
        minCheckOut.setDate(minCheckOut.getDate() + 1);
        
        if (checkOutInput) {
            checkOutInput.min = minCheckOut.toISOString().split('T')[0];
        }
        
        calculateBooking();
    });
}

if (checkOutInput) {
    checkOutInput.addEventListener('change', calculateBooking);
}

// For demo purposes, placeholder map
const propertyMap = document.getElementById('propertyMap');
if (propertyMap) {
    propertyMap.style.display = 'flex';
    propertyMap.style.alignItems = 'center';
    propertyMap.style.justifyContent = 'center';
    propertyMap.style.background = '#f0f0f0';
    propertyMap.style.color = '#666';
    propertyMap.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 10px; color: #2c5bf7;"></i><p>Interactive map would be displayed here with a valid Google Maps API key</p></div>';
}
</script>

<?php include 'includes/footer.php'; ?>
