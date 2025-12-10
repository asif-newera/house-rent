<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}



// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'redirect' => ''
];

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        WHERE ip_address = ?  AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$_SERVER['REMOTE_ADDR']]);
    $attempts = $stmt->fetch(PDO::FETCH_ASSOC)['attempts'];
    
    if ($attempts >= 5) {
        $response['message'] = 'Too many failed attempts. Please try again in 15 minutes.';
        $response['locked'] = true;
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
        } catch (PDOException $e) {
            // Table might not exist, continue without lockout check
            error_log('Login attempts check failed: ' . $e->getMessage());
            $attempts = 0;
        }

        // Check user
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE email = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_admin'] = (bool)($user['is_admin'] ?? false);
            
            // Handle remember me
            // Handle remember me
        if ($remember) {
            try {
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
            } catch (PDOException $e) {
                error_log('Remember me failed: ' . $e->getMessage());
                // Continue without remember me
            }
        }
            
            // Clear failed login attempts
            try {
                $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")
                ->execute([$_SERVER['REMOTE_ADDR']]);
            } catch (PDOException $e) {
                error_log('Failed to clear login attempts: ' . $e->getMessage());
            }
            
            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
               ->execute([$user['id']]);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Success response
            $response['success'] = true;
            $response['message'] = 'Login successful!';
            $response['redirect'] = $user['is_admin'] ? 'admin/dashboard.php' : 'dashboard.php';
            
        } // Log failed attempt
            try {
                $pdo->prepare("
                    INSERT INTO login_attempts (ip_address, email, attempt_time) 
                    VALUES (?, ?, NOW())
                ")->execute([$_SERVER['REMOTE_ADDR'], $email]);
            } catch (PDOException $e) {
                error_log('Failed to log login attempt: ' . $e->getMessage());
            }
        
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a6bff;
            --primary-hover: #3a5bef;
            --danger-color: #dc3545;
            --danger-hover: #b02a37;
            --text-color: #2d3748;
            --light-gray: #f8f9fa;
            --border-color: #e2e8f0;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
            outline: none;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            color: #6c757d;
            pointer-events: none;
        }
        
        .input-group .form-control {
            padding-left: 45px;
        }
        
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background: transparent;
            transition: all 0.2s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 4px;
        }
        
        .password-toggle:hover {
            color: var(--text-color);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .text-decoration-none {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-home"></i> <?php echo htmlspecialchars(APP_NAME); ?></h1>
        </div>
        
        <div class="login-body">
            <div id="alert-container"></div>
            
            <form id="login-form" method="POST" novalidate>
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Enter your email"
                               required
                               autocomplete="username">
                    </div>
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required
                               minlength="8"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="toggle-password">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="password-error"></div>
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
            
            <div class="text-center mt-3">
                <a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a>
            </div>
            
            <div class="form-group mt-4 pt-3 border-top">
                <p class="text-center text-muted mb-3">Don't have an account?</p>
                <a href="register.php" class="btn btn-success w-100 py-2 fw-semibold d-flex align-items-center justify-content-center" style="background-color: #28a745; border: none;">
                    <i class="fas fa-user-plus me-2"></i>
                    <span>Create New Account</span>
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
                    hideAllAlerts();
                    clearErrors();
                    
                    // Validate form
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
                            showAlert('Login successful! Redirecting...', 'success');
                            setTimeout(() => {
                                window.location.href = data.redirect || 'dashboard.php';
                            }, 1000);
                        } else {
                            showAlert(data.message || 'Login failed. Please try again.', 'danger');
                            
                            // Show field errors if any
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
                const errorId = `${input.id}-error`;
                let errorElement = document.getElementById(errorId);
                
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.id = errorId;
                    errorElement.className = 'invalid-feedback';
                    input.parentNode.insertBefore(errorElement, input.nextSibling);
                }
                
                errorElement.textContent = message;
            }
            
            function clearErrors() {
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                
                document.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.textContent = '';
                });
            }
            
            function showAlert(message, type = 'danger') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} mb-3`;
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    <i class="fas ${type === 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle'} me-2"></i>
                    ${message}
                `;
                
                alertContainer.appendChild(alertDiv);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
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