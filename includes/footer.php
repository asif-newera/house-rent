<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">
                <a href="/HOUSE%20RENT/" class="logo-link">
                    <svg class="logo-icon" width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="#4A6BFF"/>
                        <path d="M20 10L10 18V30H16V24H24V30H30V18L20 10Z" fill="white"/>
                        <path d="M16 24H24V30H16V24Z" fill="#3A5BEF"/>
                    </svg>
                    <span class="logo-text">Swapno<span>Nibash</span></span>
                </a>
                <p class="footer-about">Find your dream property with our comprehensive real estate platform. We connect buyers and sellers with the perfect properties.</p>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="/HOUSE%20RENT/">Home</a></li>
                    <li><a href="/HOUSE%20RENT/properties.php">Properties</a></li>
                    <li><a href="/HOUSE%20RENT/about.php">About Us</a></li>
                    <li><a href="/HOUSE%20RENT/contact.php">Contact</a></li>
                    <li><a href="/HOUSE%20RENT/terms.php">Terms of Service</a></li>
                    <li><a href="/HOUSE%20RENT/privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Real Estate, Dhaka, Bangladesh</li>
                    <li><i class="fas fa-phone"></i> +880 1234 567890</li>
                    <li><i class="fas fa-envelope"></i> info@swapnonibash.com</li>
                </ul>
                
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-newsletter">
                <h4>Newsletter</h4>
                <p>Subscribe to our newsletter for the latest properties and updates.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Your email address" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SwapnoNibash. All rights reserved.</p>
            <div class="payment-methods">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-paypal"></i>
                <i class="fab fa-btc"></i>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.site-footer {
    background: #1a1e2e;
    color: #fff;
    padding: 70px 0 0;
    margin-top: 60px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    margin-bottom: 60px;
}

.footer-logo .logo-link {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.footer-logo .logo-text {
    color: #fff;
    font-size: 1.8rem;
}

.footer-logo .logo-text span {
    color: #4A6BFF;
}

.footer-about {
    color: #b3b8cd;
    line-height: 1.7;
    margin: 15px 0 0;
    font-size: 0.95rem;
}

.footer-links h4,
.footer-contact h4,
.footer-newsletter h4 {
    color: #fff;
    font-size: 1.2rem;
    margin-bottom: 25px;
    position: relative;
    padding-bottom: 10px;
}

.footer-links h4:after,
.footer-contact h4:after,
.footer-newsletter h4:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 40px;
    height: 2px;
    background: #4A6BFF;
}

.footer-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #b3b8cd;
    text-decoration: none;
    transition: color 0.3s ease;
    display: block;
}

.footer-links a:hover {
    color: #4A6BFF;
    padding-left: 5px;
}

.footer-contact ul {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
}

.footer-contact li {
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    color: #b3b8cd;
    line-height: 1.6;
}

.footer-contact i {
    color: #4A6BFF;
    margin-right: 10px;
    margin-top: 4px;
    min-width: 20px;
    text-align: center;
}

.social-links {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    color: #b3b8cd;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: #4A6BFF;
    color: white;
    transform: translateY(-3px);
}

.newsletter-form {
    margin-top: 20px;
}

.newsletter-form input {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 6px;
    margin-bottom: 12px;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.newsletter-form input::placeholder {
    color: #b3b8cd;
}

.newsletter-form .btn {
    width: 100%;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding: 20px 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    color: #b3b8cd;
    font-size: 0.9rem;
}

.payment-methods {
    display: flex;
    gap: 15px;
    font-size: 1.5rem;
    color: #b3b8cd;
}

/* Responsive Footer */
@media (max-width: 992px) {
    .footer-content {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
}
</style>