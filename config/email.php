<?php
/**
 * Email Configuration for Mailtrap SMTP
 * 
 * This file contains email settings and helper functions for sending emails
 * using Mailtrap SMTP service.
 */

// Mailtrap SMTP Configuration
// Get these credentials from your Mailtrap account: https://mailtrap.io
define('SMTP_HOST', 'sandbox.smtp.mailtrap.io');  // Mailtrap SMTP host
define('SMTP_PORT', 2525);                         // Mailtrap SMTP port (can be 25, 465, 587, or 2525)
define('SMTP_USERNAME', 'your_mailtrap_username'); // Your Mailtrap username
define('SMTP_PASSWORD', 'your_mailtrap_password'); // Your Mailtrap password
define('SMTP_ENCRYPTION', 'tls');                  // Encryption type: 'tls' or 'ssl'

// Email Settings
define('MAIL_FROM_ADDRESS', 'noreply@swapnonibash.com');
define('MAIL_FROM_NAME', 'SwapnoNibash');
define('MAIL_REPLY_TO', 'support@swapnonibash.com');

// Application URLs
define('APP_URL', 'http://localhost/HOUSE%20RENT');

/**
 * Check if PHPMailer is available
 * 
 * @return bool
 */
function isPhpMailerAvailable() {
    return class_exists('PHPMailer\PHPMailer\PHPMailer');
}

/**
 * Send an email using PHPMailer and Mailtrap SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Plain text alternative body
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail($to, $subject, $body, $altBody = '') {
    // Check if PHPMailer is available
    if (!isPhpMailerAvailable()) {
        return [
            'success' => false,
            'message' => 'PHPMailer library is not installed. Please run: composer require phpmailer/phpmailer'
        ];
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        
        // Sender and recipient
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        
        // Send email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return [
            'success' => false,
            'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"
        ];
    }
}

/**
 * Send welcome email to new user
 * 
 * @param string $email User email
 * @param string $name User name
 * @return array
 */
function sendWelcomeEmail($email, $name) {
    $subject = "Welcome to " . APP_NAME . "!";
    
    $body = getEmailTemplate([
        'title' => 'Welcome to SwapnoNibash!',
        'greeting' => "Hello " . htmlspecialchars($name) . ",",
        'content' => "
            <p>Thank you for creating an account with SwapnoNibash!</p>
            <p>We're excited to help you find your dream property. Here's what you can do:</p>
            <ul style='text-align: left; margin: 20px 0;'>
                <li>Browse thousands of properties</li>
                <li>Save your favorite listings</li>
                <li>Contact property owners directly</li>
                <li>List your own properties</li>
            </ul>
            <p>If you have any questions, feel free to contact our support team.</p>
        ",
        'button_text' => 'Browse Properties',
        'button_url' => APP_URL . '/properties.php'
    ]);
    
    return sendMail($email, $subject, $body);
}

/**
 * Send password reset email
 * 
 * @param string $email User email
 * @param string $name User name
 * @param string $resetToken Reset token
 * @return array
 */
function sendPasswordResetEmail($email, $name, $resetToken) {
    $subject = "Reset Your Password - " . APP_NAME;
    $resetUrl = APP_URL . "/reset-password.php?token=" . urlencode($resetToken);
    
    $body = getEmailTemplate([
        'title' => 'Reset Your Password',
        'greeting' => "Hello " . htmlspecialchars($name) . ",",
        'content' => "
            <p>We received a request to reset your password. Click the button below to create a new password:</p>
            <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                This link will expire in 1 hour for security reasons.
            </p>
            <p style='color: #666; font-size: 14px;'>
                If you didn't request a password reset, you can safely ignore this email.
            </p>
        ",
        'button_text' => 'Reset Password',
        'button_url' => $resetUrl
    ]);
    
    return sendMail($email, $subject, $body);
}

/**
 * Send email verification email
 * 
 * @param string $email User email
 * @param string $name User name
 * @param string $verificationToken Verification token
 * @return array
 */
function sendVerificationEmail($email, $name, $verificationToken) {
    $subject = "Verify Your Email - " . APP_NAME;
    $verifyUrl = APP_URL . "/verify-email.php?token=" . urlencode($verificationToken);
    
    $body = getEmailTemplate([
        'title' => 'Verify Your Email Address',
        'greeting' => "Hello " . htmlspecialchars($name) . ",",
        'content' => "
            <p>Thank you for registering with SwapnoNibash!</p>
            <p>Please verify your email address by clicking the button below:</p>
            <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                This link will expire in 24 hours.
            </p>
        ",
        'button_text' => 'Verify Email',
        'button_url' => $verifyUrl
    ]);
    
    return sendMail($email, $subject, $body);
}

/**
 * Send booking confirmation email
 * 
 * @param string $email User email
 * @param string $name User name
 * @param array $bookingDetails Booking details
 * @return array
 */
function sendBookingConfirmationEmail($email, $name, $bookingDetails) {
    $subject = "Booking Confirmation - " . APP_NAME;
    
    $body = getEmailTemplate([
        'title' => 'Booking Confirmed!',
        'greeting' => "Hello " . htmlspecialchars($name) . ",",
        'content' => "
            <p>Your booking has been confirmed!</p>
            <div style='background: #f8f9fc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin: 0 0 15px 0; color: #333;'>Booking Details</h3>
                <p style='margin: 5px 0;'><strong>Property:</strong> " . htmlspecialchars($bookingDetails['property_name']) . "</p>
                <p style='margin: 5px 0;'><strong>Check-in:</strong> " . htmlspecialchars($bookingDetails['check_in']) . "</p>
                <p style='margin: 5px 0;'><strong>Check-out:</strong> " . htmlspecialchars($bookingDetails['check_out']) . "</p>
                <p style='margin: 5px 0;'><strong>Total Amount:</strong> ৳" . number_format($bookingDetails['total_amount'], 2) . "</p>
            </div>
            <p>We look forward to hosting you!</p>
        ",
        'button_text' => 'View Booking',
        'button_url' => APP_URL . '/my-bookings.php'
    ]);
    
    return sendMail($email, $subject, $body);
}

/**
 * Get HTML email template
 * 
 * @param array $data Template data
 * @return string HTML email template
 */
function getEmailTemplate($data) {
    $title = $data['title'] ?? 'Notification';
    $greeting = $data['greeting'] ?? 'Hello,';
    $content = $data['content'] ?? '';
    $buttonText = $data['button_text'] ?? '';
    $buttonUrl = $data['button_url'] ?? '';
    
    $buttonHtml = '';
    if ($buttonText && $buttonUrl) {
        $buttonHtml = "
            <table border='0' cellpadding='0' cellspacing='0' style='margin: 30px 0;'>
                <tr>
                    <td style='border-radius: 8px; background: #4A6BFF;'>
                        <a href='{$buttonUrl}' 
                           style='display: inline-block; padding: 14px 40px; color: #ffffff; 
                                  text-decoration: none; border-radius: 8px; font-weight: 600;'>
                            {$buttonText}
                        </a>
                    </td>
                </tr>
            </table>
        ";
    }
    
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$title}</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fc;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #f8f9fc; padding: 40px 0;'>
            <tr>
                <td align='center'>
                    <table border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%); padding: 40px 30px; text-align: center;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;'>
                                    <i class='fas fa-home'></i> SwapnoNibash
                                </h1>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='margin: 0 0 20px 0; color: #333; font-size: 24px;'>{$title}</h2>
                                <p style='margin: 0 0 15px 0; color: #555; font-size: 16px; line-height: 1.6;'>{$greeting}</p>
                                <div style='color: #555; font-size: 16px; line-height: 1.6;'>
                                    {$content}
                                </div>
                                {$buttonHtml}
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #f8f9fc; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                <p style='margin: 0 0 10px 0; color: #999; font-size: 14px;'>
                                    &copy; " . date('Y') . " SwapnoNibash. All rights reserved.
                                </p>
                                <p style='margin: 0; color: #999; font-size: 12px;'>
                                    123 Main Street, Dhaka 1000, Bangladesh
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}
?>
