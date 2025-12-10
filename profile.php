<?php
$pageTitle = 'My Profile';
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid request';
        $messageType = 'error';
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            $message = 'Name and email are required';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format';
            $messageType = 'error';
        } else {
            try {
                // Check if email is already used by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $message = 'Email already in use by another account';
                    $messageType = 'error';
                } else {
                    // Update user profile
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $userId]);
                    
                    $_SESSION['user_name'] = $name;
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error updating profile';
                $messageType = 'error';
                error_log($e->getMessage());
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid request';
        $messageType = 'error';
    } else {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'All password fields are required';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Password must be at least 6 characters';
            $messageType = 'error';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!password_verify($currentPassword, $user['password'])) {
                    $message = 'Current password is incorrect';
                    $messageType = 'error';
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error changing password';
                $messageType = 'error';
                error_log($e->getMessage());
            }
        }
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
} catch (PDOException $e) {
    die('Error fetching user data');
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'includes/header.php';
?>

<div class="profile-container">
    <div class="container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your account settings and personal information</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-content">
            <!-- Profile Information -->
            <div class="profile-card">
                <div class="card-header">
                    <h2><i class="fas fa-user"></i> Profile Information</h2>
                </div>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <div class="card-header">
                    <h2><i class="fas fa-lock"></i> Change Password</h2>
                </div>
                <form method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password"><i class="fas fa-key"></i> Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Account Info -->
            <div class="profile-card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i> Account Information</h2>
                </div>
                <div class="account-info">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar"></i> Member Since:</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-shield-alt"></i> Account Type:</span>
                        <span class="info-value"><?php echo $user['is_admin'] ? 'Administrator' : 'Regular User'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    padding: 40px 0 80px;
    background: #f8f9fa;
    min-height: calc(100vh - 85px);
}

.profile-header {
    text-align: center;
    margin-bottom: 40px;
}

.profile-header h1 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 10px;
}

.profile-header h1 i {
    color: #4A6BFF;
    margin-right: 15px;
}

.profile-header p {
    color: #666;
    font-size: 1.1rem;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.profile-content {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
    color: white;
    padding: 20px 25px;
}

.card-header h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.card-header h2 i {
    margin-right: 10px;
}

.profile-form {
    padding: 30px 25px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

.form-group label i {
    color: #4A6BFF;
    margin-right: 8px;
    width: 16px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #4A6BFF;
    outline: none;
    box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.1);
}

.account-info {
    padding: 30px 25px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
}

.info-label i {
    color: #4A6BFF;
    margin-right: 8px;
}

.info-value {
    color: #333;
    font-weight: 500;
}

@media (max-width: 768px) {
    .profile-header h1 {
        font-size: 2rem;
    }
    
    .profile-form,
    .account-info {
        padding: 20px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
