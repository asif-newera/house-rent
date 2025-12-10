<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';



// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Redirect if already logged in
if (isLoggedIn() && isAdmin()) {
    $response['redirect'] = 'dashboard.php';
    echo json_encode($response);
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON content type for AJAX responses
    header('Content-Type: application/json');
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $response['message'] = 'Invalid request. Please try again.';
        echo json_encode($response);
        exit;
    }
    
    // Get and validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Input validation
    $errors = [];
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Please enter your password';
    }
    
    // Return validation errors if any
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Please fix the errors below';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Check for account lockout
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);
        $attempts = $stmt->fetch(PDO::FETCH_ASSOC)['attempts'];
        
        if ($attempts >= 5) {
            $response['message'] = 'Too many failed attempts. Please try again in 15 minutes.';
            $response['locked'] = true;
            echo json_encode($response);
            exit;
        }
        
        // Check admin user
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE email = ? AND is_active = 1 AND is_admin = 1 
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_logged_in'] = true; // Required for admin_auth.php
            $_SESSION['user_role'] = 'admin';    // Standardize role
            
            // Handle remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $pdo->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?")
                   ->execute([$token, $expires, $user['id']]);
                
                setcookie('remember_token', $token, [
                    'expires' => strtotime('+30 days'),
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
            
            // Clear failed login attempts
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")
               ->execute([$_SERVER['REMOTE_ADDR']]);
            
            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
               ->execute([$user['id']]);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Success response
            $response['success'] = true;
            $response['message'] = 'Login successful!';
            $response['redirect'] = 'dashboard.php';
        } else {
            // Log failed attempt
            $pdo->prepare("
                INSERT INTO login_attempts (ip_address, email, attempt_time) 
                VALUES (?, ?, NOW())
            ")->execute([$_SERVER['REMOTE_ADDR'], $email]);
            
            $response['message'] = 'Invalid email or password';
            $response['attempts_remaining'] = max(0, 5 - ($attempts + 1));
        }
        
    } catch (PDOException $e) {
        error_log('Admin login error: ' . $e->getMessage());
        $response['message'] = 'A system error occurred. Please try again later.';
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a6bff;
            --primary-hover: #3a5bef;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #e9ecef;
            --text-color: #2d3748;
            --text-muted: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
            margin: 20px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
            transform: rotate(30deg);
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .login-header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-group .form-control {
            padding-left: 42px;
            height: 48px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }
        
        .input-group .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
            background-color: white;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            color: var(--text-muted);
            font-size: 1.1rem;
            z-index: 2;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary {
            background: var(--danger-color);
            color: white;
            height: 48px;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background: #b02a37;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .btn-link {
            background: none;
            border: none;
            color: var(--danger-color);
            padding: 0;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            width: auto;
        }
        
        .btn-link:hover {
            text-decoration: underline;
            color: #b02a37;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            position: relative;
            border: 1px solid transparent;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .password-toggle:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-color);
        }
        
        .login-footer {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
            text-align: center;
        }
        
        /* Loading animation */
        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .login-body {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-lock"></i> SwapnoNibash Admin</h1>
            <p>Admin Panel Login</p>
        </div>
        
        <div class="login-body">
            <div id="alert-container"></div>
            
            <form id="login-form" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Enter your admin email"
                               required>
                    </div>
                    <div class="invalid-feedback">Please enter a valid email address</div>
                </div>
                
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password">Password</label>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required
                               minlength="8">
                        <button type="button" id="toggle-password" class="password-toggle" aria-label="Toggle password visibility">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Password must be at least 8 characters long</div>
                </div>
                
                <div class="form-group form-check d-flex align-items-center mb-4">
                    <input type="checkbox" class="form-check-input me-2" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <button type="submit" id="submit-btn" class="btn btn-primary">
                    <span id="btn-text">Sign In</span>
                    <div class="spinner-border spinner-border-sm d-none" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php" class="btn-link">
                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                </a>
            </div>
            

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('toggle-password');
            const submitBtn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            const alertContainer = document.getElementById('alert-container');
            
            // Toggle password visibility
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
            
            // Form submission
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Reset previous alerts and errors
                    hideAllAlerts();
                    clearErrors();
                    
                    // Basic validation
                    if (!validateForm()) {
                        return;
                    }
                    
                    // Show loading state
                    setLoadingState(true);
                    
                    try {
                        const formData = new FormData(loginForm);
                        
                        const response = await fetch('login.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Show success message
                            showAlert('Login successful! Redirecting...', 'success');
                            
                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = data.redirect || 'dashboard.php';
                            }, 1000);
                        } else {
                            // Show error message
                            showAlert(data.message || 'Login failed. Please try again.', 'danger');
                            
                            // Handle validation errors
                            if (data.errors) {
                                Object.entries(data.errors).forEach(([field, message]) => {
                                    const input = document.querySelector(`[name="${field}"]`);
                                    if (input) {
                                        showFieldError(input, message);
                                    }
                                });
                            }
                            
                            // Handle account lockout
                            if (data.locked) {
                                const retryTime = data.retry_after ? Math.ceil(data.retry_after / 60) : 15;
                                showAlert(`Too many failed attempts. Please try again in ${retryTime} minutes.`, 'warning');
                                disableForm(true);
                                
                                // Enable form after lockout period
                                setTimeout(() => {
                                    disableForm(false);
                                }, retryTime * 60 * 1000);
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showAlert('An error occurred. Please try again.', 'danger');
                    } finally {
                        setLoadingState(false);
                    }
                });
            }
            
            // Form validation
            function validateForm() {
                let isValid = true;
                
                // Email validation
                if (!emailInput.value.trim()) {
                    showFieldError(emailInput, 'Email is required');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                    showFieldError(emailInput, 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Password validation
                if (!passwordInput.value) {
                    showFieldError(passwordInput, 'Password is required');
                    isValid = false;
                } else if (passwordInput.value.length < 8) {
                    showFieldError(passwordInput, 'Password must be at least 8 characters long');
                    isValid = false;
                }
                
                return isValid;
            }
            
            function showFieldError(input, message) {
                input.classList.add('is-invalid');
                
                // Create or update error message
                let errorDiv = input.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    input.parentNode.insertBefore(errorDiv, input.nextSibling);
                }
                
                errorDiv.textContent = message;
            }
            
            function clearErrors() {
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                
                document.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.remove();
                });
            }
            
            function showAlert(message, type = 'danger') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show mb-3`;
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    <i class="fas ${type === 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                alertContainer.appendChild(alertDiv);
                
                // Auto-dismiss after 5 seconds for non-error messages
                if (type !== 'danger') {
                    setTimeout(() => {
                        alertDiv.classList.remove('show');
                        setTimeout(() => alertDiv.remove(), 150);
                    }, 5000);
                }
            }
            
            function hideAllAlerts() {
                while (alertContainer.firstChild) {
                    alertContainer.removeChild(alertContainer.firstChild);
                }
            }
            
            function setLoadingState(isLoading) {
                if (isLoading) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-loading');
                    if (btnText) btnText.style.visibility = 'hidden';
                } else {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-loading');
                    if (btnText) btnText.style.visibility = 'visible';
                }
            }
            
            function disableForm(disabled) {
                const inputs = loginForm.querySelectorAll('input, button');
                inputs.forEach(input => {
                    input.disabled = disabled;
                });
            }
        });
    </script>
</body>
</html>