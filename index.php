<?php
$pageTitle = 'Home';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get featured properties
$featuredProperties = [];
try {
    // Check if is_featured column exists
    $columnCheck = $pdo->query("SHOW COLUMNS FROM properties LIKE 'is_featured'");
    $hasFeaturedColumn = $columnCheck->rowCount() > 0;
    
    $query = "SELECT p.*, 
             (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as image_url
             FROM properties p ";
    
    if ($hasFeaturedColumn) {
        // First try to get featured properties
        $query .= "WHERE p.is_featured = 1 ";
        $query .= "ORDER BY p.created_at DESC LIMIT 6";
        
        $stmt = $pdo->query($query);
        $featuredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no featured properties, fall back to available properties
        if (empty($featuredProperties)) {
            $query = "SELECT p.*, 
                     (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as image_url
                     FROM properties p WHERE p.status = 'available' ORDER BY p.created_at DESC LIMIT 6";
            $stmt = $pdo->query($query);
            $featuredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // No is_featured column, just get available properties
        $query .= "WHERE p.status = 'available' ";
        $query .= "ORDER BY p.created_at DESC LIMIT 6";
        
        $stmt = $pdo->query($query);
        $featuredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching featured properties: " . $e->getMessage());
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <div class="container">
            <div class="hero-text">
                <h1>Find Your Dream <span>Home</span> Today</h1>
                <p>Discover the perfect property that matches your lifestyle and budget</p>
                <a href="#featured" class="btn btn-primary">Explore Properties</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties -->
<section id="featured" class="featured-properties">
    <div class="container">
        <div class="section-header">
            <h2>Featured Properties</h2>
            <a href="properties.php" class="btn btn-outline">View All</a>
        </div>
        
        <?php if (!empty($featuredProperties)): ?>
            <div class="property-grid">
                <?php foreach ($featuredProperties as $property): ?>
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
                            <div class="property-status"><?php echo ucfirst($property['status']); ?></div>
                            <div class="property-price">৳<?php echo number_format($property['price']); ?></div>
                        </div>
                        <div class="property-details">
                            <h3><a href="property-details.php?id=<?php echo $property['id']; ?>"><?php echo htmlspecialchars($property['title']); ?></a></h3>
                            <p class="property-address">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($property['location'] ?? $property['address']); ?>
                            </p>
                            <div class="property-features">
                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                                <span><i class="fas fa-vector-square"></i> <?php echo $property['area']; ?> sqft</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-properties">
                <p>No featured properties available at the moment. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us -->
<section class="why-choose-us">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose Us</h2>
            <p>We provide the best service for our customers</p>
        </div>
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>Wide Range of Properties</h3>
                <p>Browse through thousands of properties to find your perfect match.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h3>Best Prices</h3>
                <p>Get the best deals and prices on properties across the country.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Our support team is always available to assist you with any queries.</p>
            </div>
        </div>
    </div>
</section>

<style>
/* Hero Section */
.hero {
    position: relative;
    height: 100vh;
    min-height: 600px;
    background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                url('assets/homeImage.png') no-repeat center center/cover;
    display: flex;
    align-items: center;
    color: #fff;
    text-align: left;
    overflow: hidden;
    margin-top: 0;
    background-attachment: fixed;
}

.hero-content {
    width: 100%;
    padding: 0 5%;
    max-width: 1200px;
    margin: 0 auto;
}

.hero-text {
    max-width: 600px;
    animation: fadeInUp 1s ease-out;
}

.hero h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    font-weight: 800;
    line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.hero h1 span {
    color: #4A6BFF;
}

.hero p {
    font-size: 1.3rem;
    margin-bottom: 30px;
    opacity: 0.9;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background-color: #4A6BFF;
    color: white;
    box-shadow: 0 4px 15px rgba(74, 107, 255, 0.3);
}

.btn-primary:hover {
    background-color: #3a5bef;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(74, 107, 255, 0.4);
}

.btn-outline {
    background: transparent;
    color: #4A6BFF;
    border: 2px solid #4A6BFF;
    padding: 10px 25px;
}

.btn-outline:hover {
    background: #4A6BFF;
    color: white;
}

/* Featured Properties */
.featured-properties {
    padding: 80px 0;
    background: #f8f9fa;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.section-header h2 {
    font-size: 2rem;
    color: #333;
    margin: 0;
}

.property-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
}

/* Property Card */
.property-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
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

.property-status {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #4A6BFF;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.property-price {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 1.2rem;
    font-weight: 700;
    color: #4A6BFF;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.property-details {
    padding: 20px;
}

.property-details h3 {
    margin: 0 0 10px;
    font-size: 1.2rem;
}

.property-details h3 a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.property-details h3 a:hover {
    color: #4A6BFF;
}

.property-address {
    color: #666;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.95rem;
}

.property-features {
    display: flex;
    gap: 15px;
    margin: 15px 0;
    padding: 15px 0;
    border-top: 1px solid #eee;
}

.property-features span {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 0.9rem;
}

.property-features i {
    color: #4A6BFF;
}

.no-properties {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 992px) {
    .hero h1 {
        font-size: 3rem;
    }
    
    .property-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .hero {
        height: 80vh;
    }
    
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .hero p {
        font-size: 1.1rem;
    }
    
    .property-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}

@media (max-width: 576px) {
    .hero h1 {
        font-size: 2rem;
    }
    
    .featured-properties {
        padding: 60px 0;
    }
}
</style>

<?php
require_once 'includes/footer.php';
?>