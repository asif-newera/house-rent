<?php
// Set page title before including header
$pageTitle = 'Properties';

// Include configuration and functions
require_once 'config/config.php';
require_once 'includes/functions.php';

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Get filter parameters
$location = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 10000000;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;

// Build the SQL query
$query = "SELECT * FROM properties WHERE 1=1";
$params = [];

if (!empty($location)) {
    $query .= " AND location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($type)) {
    $query .= " AND type = ?";
    $params[] = $type;
}

if ($minPrice > 0) {
    $query .= " AND price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $query .= " AND price <= ?";
    $params[] = $maxPrice;
}

if ($bedrooms > 0) {
    $query .= " AND bedrooms >= ?";
    $params[] = $bedrooms;
}

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) AS total");
$countStmt->execute($params);
$totalProperties = $countStmt->fetchColumn();
$totalPages = ceil($totalProperties / $perPage);

// Add pagination to the query
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header after setting all variables
require_once 'includes/header.php';
?>

<main class="properties-page">
    <div class="properties-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Find Your Dream Property</h1>
            <p>Your trusted partner in finding the perfect home</p>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <p class="reveal delay-2"><?php echo $totalProperties; ?> properties found</p>
        </div>
        
        <style>
            .properties-hero {
                position: relative;
                height: 300px;
                background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                            url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1920&q=80');
                background-size: cover;
                background-position: center;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 40px;
            }
            
            .hero-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.4);
            }
            
            .hero-content {
                position: relative;
                z-index: 2;
                text-align: center;
                color: white;
                padding: 20px;
            }
            
            .hero-content h1 {
                font-size: 3rem;
                margin-bottom: 15px;
                font-weight: 700;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            }
            
            .hero-content p {
                font-size: 1.3rem;
                margin: 0;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            }
            .properties-page {
                padding: 60px 0;
                background: #f8f9fa;
                min-height: calc(100vh - 85px);
            }
            
            .page-header {
                text-align: center;
                margin: 0 0 40px;
                padding: 0 20px;
            }
            
            .page-header p {
                font-size: 1.2rem;
                color: var(--dark-gray);
                max-width: 600px;
                margin: 0 auto;
            }
            
            .property-filters {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: var(--box-shadow);
                margin-bottom: 30px;
            }
            
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .filter-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .property-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .property-image {
            position: relative;
            height: 200px;
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
        
        .property-type {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .property-price {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .property-details {
            padding: 20px;
        }
        
        .property-details h3 {
            margin: 0 0 10px;
            font-size: 1.2rem;
        }
        
        .property-details h3 a {
            color: var(--text-color);
            text-decoration: none;
        }
        
        .property-details h3 a:hover {
            color: var(--primary-color);
        }
        
        .property-location {
            color: var(--dark-gray);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .property-features {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .property-features span {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: var(--dark-gray);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            background: #fff;
            border: 1px solid #ddd;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover,
        .pagination .current {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        
        .no-properties {
            text-align: center;
            grid-column: 1 / -1;
            padding: 40px 0;
            color: var(--dark-gray);
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .properties-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            @media (max-width: 768px) {
                .properties-hero {
                    height: 250px;
                }
                
                .hero-content h1 {
                    font-size: 2rem;
                }
                
                .hero-content p {
                    font-size: 1.1rem;
                }
                
                .filter-row {
                    flex-direction: column;
                    gap: 15px;
                }
                
                .filter-group {
                    width: 100%;
                }
                
                .properties-grid {
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                }
            }
        </style>
            
            <form method="GET" class="property-filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="Any location">
                    </div>
                    
                    <div class="filter-group">
                        <label for="type">Property Type</label>
                        <select id="type" name="type">
                            <option value="">Any Type</option>
                            <?php foreach ($propertyTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_GET['type']) && $_GET['type'] === $type) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="bedrooms">Bedrooms</label>
                        <select id="bedrooms" name="bedrooms">
                            <option value="">Any</option>
                            <option value="1" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '1') ? 'selected' : ''; ?>>1+</option>
                            <option value="2" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '2') ? 'selected' : ''; ?>>2+</option>
                            <option value="3" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '3') ? 'selected' : ''; ?>>3+</option>
                            <option value="4" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '4') ? 'selected' : ''; ?>>4+</option>
                            <option value="5" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '5') ? 'selected' : ''; ?>>5+</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="min_price">Min Price (৳)</label>
                        <input type="number" id="min_price" name="min_price" value="<?php echo $minPrice; ?>" placeholder="Min price">
                    </div>
                    
                    <div class="filter-group">
                        <label for="max_price">Max Price (৳)</label>
                        <input type="number" id="max_price" name="max_price" value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>" placeholder="Max price">
                    </div>
                    
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <div class="search-count">
                        Showing <?php echo count($properties); ?> of <?php echo $totalProperties; ?> properties
                    </div>
                    <?php if (!empty($_GET)): ?>
                        <a href="properties.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if (count($properties) > 0): ?>
                <div class="properties-grid">
                    <?php foreach ($properties as $property): ?>
                        <div class="property-card">
                            <div class="property-image">
                                <?php 
                                $imagePath = $property['image_url'] ?? '';
                                $fullPath = __DIR__ . '/' . $imagePath;
                                $displayImage = (!empty($imagePath) && is_file($fullPath)) ? '/HOUSE%20RENT/' . $imagePath : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';
                                ?>
                                <img src="<?php echo htmlspecialchars($displayImage); ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?>"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80';">
                                <span class="property-type"><?php echo 'For Rent'; // Default property type since it's not in the database ?></span>
                                <span class="property-price">৳<?php echo number_format($property['price']); ?></span>
                            </div>
                            <div class="property-details">
                                <h3><a href="property-details.php?id=<?php echo $property['id']; ?>">
                                    <?php echo htmlspecialchars($property['title']); ?>
                                </a></h3>
                                <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location'] ?? 'Location not specified'); ?>
                            </div>
                                <div class="property-features">
                                    <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms'] ?: 'N/A'; ?> Beds</span>
                                    <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms'] ?: 'N/A'; ?> Baths</span>
                                    <span><i class="fas fa-vector-square"></i> <?php echo $property['area'] ? $property['area'] . ' sqft' : 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-properties">
                    <h3>No properties found matching your criteria.</h3>
                    <p>Try adjusting your search filters or <a href="properties.php">clear all filters</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Initialize any necessary JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Price range slider (if needed)
            const minPriceInput = document.getElementById('min_price');
            const maxPriceInput = document.getElementById('max_price');
            
            if (minPriceInput && maxPriceInput) {
                // You can add a range slider here if needed
            }
        });
    </script>
</body>
</html>
