<?php
$pageTitle = 'Contact Us - SwapnoNibash';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_token']) && csrf_verify($_POST['_token'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // First, check if subject column exists, if not add it
            $checkColumn = $pdo->query("SHOW COLUMNS FROM contact_messages LIKE 'subject'");
            if ($checkColumn->rowCount() == 0) {
                $pdo->exec("ALTER TABLE contact_messages ADD COLUMN subject VARCHAR(200) AFTER phone");
            }
            
            // Save message to database
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'new', NOW())
            ");
            
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            // Optionally send email notification to admin
            // Uncomment the following lines to enable email notifications
            /*
            $adminEmail = 'admin@swapnonibash.com';
            $emailSubject = 'New Contact Form Submission' . ($subject ? ': ' . $subject : '');
            $emailBody = "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong><br>$message</p>
            ";
            sendEmail($adminEmail, $emailSubject, $emailBody);
            */
            
            $success_message = 'Thank you for contacting us! We will get back to you soon.';
            
            // Clear form data
            $name = $email = $phone = $subject = $message = '';
        } catch (PDOException $e) {
            error_log("Contact form error: " . $e->getMessage());
            $error_message = 'Sorry, there was an error sending your message. Please try again later.';
        }
    }
}
?>

<!-- Contact Hero Section -->
<section class="contact-hero">
    <div class="container">
        <div class="contact-hero-content">
            <h1><i class="fas fa-envelope"></i> Get in Touch</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="contact-content">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-form-wrapper">
                <div class="section-header">
                    <h2>Send Us a Message</h2>
                    <p>Fill out the form below and we'll get back to you within 24 hours</p>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="contact-form">
                    <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control" 
                                   value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="6" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="contact-info-wrapper">
                <div class="contact-info-card">
                    <h3>Contact Information</h3>
                    <p class="subtitle">Reach out to us through any of these channels</p>
                    
                    <div class="contact-info-items">
                        <div class="contact-info-item">
                            <div class="icon-wrapper">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Address</h4>
                                <p>123 Main Street, Dhaka 1000<br>Bangladesh</p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="icon-wrapper">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <h4>Phone</h4>
                                <p>+880 1712-345678<br>+880 1812-345678</p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="icon-wrapper">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p>info@swapnonibash.com<br>support@swapnonibash.com</p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <div class="icon-wrapper">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4>Business Hours</h4>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-media">
                        <h4>Follow Us</h4>
                        <div class="social-links">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Find Us on the Map</h2>
            <p>Visit our office or schedule an appointment</p>
        </div>
        
        <div class="map-container">
            <div id="contactMap" class="contact-map">
                <div style="text-align: center; padding: 60px 20px; background: #f8f9fc;">
                    <i class="fas fa-map-marked-alt" style="font-size: 4rem; margin-bottom: 20px; color: #4A6BFF;"></i>
                    <h3 style="color: #333; margin-bottom: 10px;">Interactive Map</h3>
                    <p style="color: #666;">Map would be displayed here with a valid Google Maps API key</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Frequently Asked Questions</h2>
            <p>Quick answers to common questions</p>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <h4><i class="fas fa-question-circle"></i> How quickly do you respond to inquiries?</h4>
                <p>We typically respond to all inquiries within 24 hours during business days.</p>
            </div>
            
            <div class="faq-item">
                <h4><i class="fas fa-question-circle"></i> Can I schedule a property viewing?</h4>
                <p>Yes! Contact us through the form or call us directly to schedule a convenient time.</p>
            </div>
            
            <div class="faq-item">
                <h4><i class="fas fa-question-circle"></i> Do you offer virtual tours?</h4>
                <p>Absolutely! We provide virtual tours for all our properties upon request.</p>
            </div>
            
            <div class="faq-item">
                <h4><i class="fas fa-question-circle"></i> What areas do you cover?</h4>
                <p>We cover all major areas in Dhaka and surrounding districts.</p>
            </div>
        </div>
    </div>
</section>

<style>
/* Contact Hero */
.contact-hero {
    background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
    color: white;
    padding: 100px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.contact-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.contact-hero-content {
    position: relative;
    z-index: 1;
}

.contact-hero-content h1 {
    font-size: 3rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.contact-hero-content h1 i {
    margin-right: 15px;
}

.contact-hero-content p {
    font-size: 1.2rem;
    opacity: 0.95;
    max-width: 600px;
    margin: 0 auto;
}

/* Contact Content */
.contact-content {
    padding: 80px 0;
    background: #f8f9fc;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 40px;
    margin-top: 40px;
}

/* Contact Form */
.contact-form-wrapper {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
}

.section-header h2 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 10px;
}

.section-header p {
    color: #666;
    margin-bottom: 30px;
}

.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.contact-form .form-group {
    margin-bottom: 25px;
}

.contact-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 0.95rem;
}

.contact-form .required {
    color: #ff4444;
}

.contact-form .form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.contact-form .form-control:focus {
    outline: none;
    border-color: #4A6BFF;
    box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.1);
}

.contact-form textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.contact-form .btn-primary {
    width: 100%;
    padding: 15px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.contact-form .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(74, 107, 255, 0.3);
}

/* Alerts */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Contact Info Card */
.contact-info-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.contact-info-card {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.contact-info-card h3 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
}

.contact-info-card .subtitle {
    color: #666;
    margin-bottom: 30px;
}

.contact-info-items {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.contact-info-item {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.info-content h4 {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 5px;
}

.info-content p {
    color: #666;
    line-height: 1.6;
    margin: 0;
}

/* Social Media */
.social-media {
    margin-top: 35px;
    padding-top: 30px;
    border-top: 1px solid #e0e0e0;
}

.social-media h4 {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 15px;
}

.social-links {
    display: flex;
    gap: 12px;
}

.social-links a {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    background: #f8f9fc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.social-links a:hover {
    background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
    color: white;
    transform: translateY(-3px);
}

/* Map Section */
.map-section {
    padding: 80px 0;
    background: white;
}

.map-container {
    margin-top: 40px;
}

.contact-map {
    width: 100%;
    height: 450px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

/* FAQ Section */
.faq-section {
    padding: 80px 0;
    background: #f8f9fc;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 40px;
}

.faq-item {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.faq-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.faq-item h4 {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.faq-item h4 i {
    color: #4A6BFF;
    font-size: 1.2rem;
}

.faq-item p {
    color: #666;
    line-height: 1.6;
    margin: 0;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-info-card {
        position: static;
    }
    
    .contact-form .form-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .contact-hero-content h1 {
        font-size: 2.5rem;
    }
    
    .contact-form-wrapper,
    .contact-info-card {
        padding: 30px 20px;
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .contact-hero {
        padding: 80px 0;
    }
    
    .contact-hero-content h1 {
        font-size: 2rem;
    }
    
    .contact-content,
    .map-section,
    .faq-section {
        padding: 60px 0;
    }
    
    .contact-map {
        height: 300px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
