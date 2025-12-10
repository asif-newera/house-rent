<?php
$pageTitle = 'About Us - SwapnoNibash';
require_once 'config/config.php';
require_once 'includes/header.php';
?>

<!-- About Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="about-hero-content">
            <h1>About SwapnoNibash</h1>
            <p>Your trusted partner in finding the perfect home</p>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="about-content">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <img src="/HOUSE%20RENT/assets/homeImage.png" alt="About Us" class="img-fluid">
            </div>
            <div class="about-text">
                <h2>Our Story</h2>
                <p>Founded in 2023, SwapnoNibash has been helping people find their dream homes with ease and confidence. What started as a small real estate agency has grown into a trusted name in the housing market, known for our commitment to excellence and customer satisfaction.</p>
                
                <h2>Our Mission</h2>
                <p>Our mission is to simplify the home buying and renting process while providing exceptional service. We believe everyone deserves to find their perfect home, and we're dedicated to making that process as smooth and stress-free as possible.</p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Properties Listed</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">Satisfaction Rate</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Meet Our Team</h2>
            <p>Dedicated professionals working for you</p>
        </div>
        
        <div class="team-grid">
            <div class="team-member">
                <div class="member-image">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Team Member">
                </div>
                <h3>John Doe</h3>
                <p class="position">CEO & Founder</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="team-member">
                <div class="member-image">
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Team Member">
                </div>
                <h3>Jane Smith</h3>
                <p class="position">Real Estate Agent</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="team-member">
                <div class="member-image">
                    <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="Team Member">
                </div>
                <h3>Michael Johnson</h3>
                <p class="position">Property Manager</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
    <div class="container">
        <div class="section-header text-center">
            <h2>What Our Clients Say</h2>
            <p>Hear from people who found their dream home with us</p>
        </div>
        
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"Found my dream home within a week! The service was excellent and the team was very helpful throughout the process."</p>
                </div>
                <div class="testimonial-author">
                    <img src="https://randomuser.me/api/portraits/men/22.jpg" alt="Client">
                    <div class="author-info">
                        <h4>Robert Johnson</h4>
                        <span>Home Owner</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"As a first-time home buyer, I was nervous, but the team at SwapnoNibash made the process so easy and stress-free."</p>
                </div>
                <div class="testimonial-author">
                    <img src="https://randomuser.me/api/portraits/women/33.jpg" alt="Client">
                    <div class="author-info">
                        <h4>Sarah Williams</h4>
                        <span>Home Buyer</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Find Your Dream Home?</h2>
            <p>Join thousands of satisfied customers who found their perfect property with us.</p>
            <div class="cta-buttons">
                <a href="properties.php" class="btn btn-primary">Browse Properties</a>
                <a href="contact.php" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<style>
/* About Hero */
.about-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('/HOUSE%20RENT/assets/homeImage.png') no-repeat center center/cover;
    color: white;
    padding: 100px 0;
    text-align: center;
    position: relative;
}

.about-hero-content h1 {
    font-size: 3rem;
    margin-bottom: 15px;
}

.about-hero-content p {
    font-size: 1.2rem;
    opacity: 0.9;
}

/* About Content */
.about-content {
    padding: 80px 0;
}

.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
}

.about-image img {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.about-text h2 {
    font-size: 2rem;
    margin: 30px 0 15px;
    color: #333;
}

.about-text h2:first-child {
    margin-top: 0;
}

.about-text p {
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
}

.about-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #eee;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #4A6BFF;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

/* Team Section */
.team-section {
    background: #f8f9fa;
    padding: 80px 0;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.team-member {
    background: white;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.team-member:hover {
    transform: translateY(-5px);
}

.member-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    margin: 0 auto 20px;
    overflow: hidden;
    border: 5px solid #f0f0f0;
}

.member-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-member h3 {
    margin: 15px 0 5px;
    font-size: 1.3rem;
    color: #333;
}

.team-member .position {
    color: #4A6BFF;
    font-weight: 500;
    margin-bottom: 15px;
}

.social-links {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #f5f5f5;
    color: #666;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background: #4A6BFF;
    color: white;
}

/* Testimonials */
.testimonials {
    padding: 80px 0;
    background: white;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.testimonial-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.testimonial-content {
    font-style: italic;
    color: #555;
    line-height: 1.7;
    margin-bottom: 20px;
    position: relative;
    padding-left: 30px;
}

.testimonial-content:before {
    content: '"';
    font-size: 4rem;
    color: #4A6BFF;
    opacity: 0.2;
    position: absolute;
    top: -20px;
    left: -10px;
    font-family: serif;
    line-height: 1;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}

.testimonial-author img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.author-info h4 {
    margin: 0 0 5px;
    font-size: 1.1rem;
    color: #333;
}

.author-info span {
    color: #666;
    font-size: 0.9rem;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
}

.cta-content {
    max-width: 700px;
    margin: 0 auto;
}

.cta-content h2 {
    font-size: 2.2rem;
    margin-bottom: 15px;
}

.cta-content p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.cta-buttons .btn {
    min-width: 150px;
}

.cta-buttons .btn-outline {
    background: transparent;
    border-color: white;
    color: white;
}

.cta-buttons .btn-outline:hover {
    background: white;
    color: #4A6BFF;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .about-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .about-image {
        order: -1;
    }
    
    .about-stats {
        flex-direction: column;
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .about-hero-content h1 {
        font-size: 2.5rem;
    }
    
    .team-grid,
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .cta-buttons .btn {
        width: 100%;
        max-width: 250px;
    }
}

@media (max-width: 576px) {
    .about-hero-content h1 {
        font-size: 2rem;
    }
    
    .about-content,
    .team-section,
    .testimonials {
        padding: 60px 0;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
