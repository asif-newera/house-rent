<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$userName = $_SESSION['user_name'] ?? 'User';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/HOUSE%20RENT/assets/css/style.css">
    <script src="/HOUSE%20RENT/assets/js/main.js" defer></script>
    <style>
        /* Header Styles */
        .site-header {
            background: linear-gradient(to bottom, #ffffff 0%, #fafbff 100%);
            box-shadow: 0 2px 20px rgba(74, 107, 255, 0.08);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            padding: 0;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(74, 107, 255, 0.1);
        }

        .site-header.scrolled {
            box-shadow: 0 4px 30px rgba(74, 107, 255, 0.12);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 18px 40px;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .logo-link:hover {
            transform: scale(1.02);
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            margin-right: 12px;
            filter: drop-shadow(0 2px 8px rgba(74, 107, 255, 0.2));
            transition: filter 0.3s ease;
        }

        .logo-link:hover .logo-icon {
            filter: drop-shadow(0 4px 12px rgba(74, 107, 255, 0.3));
        }

        .logo-text {
            font-size: 1.9rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .logo-text span {
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 35px;
        }

        .nav-links a {
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 10px 16px;
            position: relative;
            transition: all 0.3s ease;
            text-decoration: none;
            border-radius: 8px;
        }

        .nav-links a:not(.btn):hover {
            background: linear-gradient(135deg, #f5f7ff 0%, #eef1ff 100%);
            color: #4A6BFF;
            transform: translateY(-2px);
        }

        .nav-links a:not(.btn).active {
            background: linear-gradient(135deg, #eef1ff 0%, #e5e9ff 100%);
            color: #4A6BFF;
        }

        .auth-buttons {
            display: flex;
            gap: 12px;
            margin-left: 25px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
        }

        .btn i {
            font-size: 0.9rem;
        }

        .btn-outline {
            border: 2px solid #4A6BFF;
            color: #4A6BFF;
            background: transparent;
        }

        .btn-outline:hover {
            background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(74, 107, 255, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
            color: white;
            border: 2px solid transparent;
            box-shadow: 0 2px 10px rgba(74, 107, 255, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 107, 255, 0.3);
        }

        .user-dropdown {
            position: relative;
            margin-left: 20px;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f5f7ff 0%, #eef1ff 100%);
            color: #4A6BFF;
            border: 2px solid rgba(74, 107, 255, 0.2);
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-btn:hover {
            background: linear-gradient(135deg, #eef1ff 0%, #e5e9ff 100%);
            border-color: rgba(74, 107, 255, 0.4);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 107, 255, 0.15);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4A6BFF 0%, #3a5bef 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(74, 107, 255, 0.3);
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            min-width: 220px;
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            border: 1px solid rgba(74, 107, 255, 0.1);
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 8px;
            font-weight: 500;
        }

        .dropdown-menu a:hover {
            background: linear-gradient(135deg, #f5f7ff 0%, #eef1ff 100%);
            color: #4A6BFF;
            padding-left: 20px;
        }

        .dropdown-menu i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            font-size: 0.95rem;
        }

        .dropdown-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 8px 0;
        }

        .mobile-menu-toggle {
            display: none;
            background: linear-gradient(135deg, #f5f7ff 0%, #eef1ff 100%);
            border: 2px solid rgba(74, 107, 255, 0.2);
            font-size: 1.3rem;
            color: #4A6BFF;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle:hover {
            background: linear-gradient(135deg, #eef1ff 0%, #e5e9ff 100%);
            transform: scale(1.05);
        }

        .mobile-menu-toggle.active i::before {
            content: '\f00d';
        }

        /* Mobile Overlay */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .header-container {
                padding: 15px 25px;
            }

            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 320px;
                max-width: 85vw;
                height: 100vh;
                background: linear-gradient(to bottom, #ffffff 0%, #fafbff 100%);
                flex-direction: column;
                justify-content: flex-start;
                align-items: stretch;
                padding: 100px 30px 30px;
                box-shadow: -5px 0 30px rgba(0, 0, 0, 0.15);
                transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1000;
                margin: 0;
                gap: 0;
                overflow-y: auto;
            }

            .nav-links.active {
                right: 0;
            }

            .nav-links a {
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 5px;
            }

            .nav-links a:not(.btn)::before {
                display: none;
            }

            .nav-links a:hover,
            .nav-links a.active {
                background: linear-gradient(135deg, #f5f7ff 0%, #eef1ff 100%);
            }

            .mobile-menu-toggle {
                display: block;
                z-index: 1001;
            }

            .auth-buttons {
                flex-direction: column;
                width: 100%;
                margin: 20px 0 0;
                gap: 10px;
            }

            .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }

            .user-dropdown {
                width: 100%;
                margin: 15px 0 0;
            }

            .user-btn {
                width: 100%;
                justify-content: center;
            }

            .dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                display: none;
                width: 100%;
                margin-top: 10px;
                background: rgba(245, 247, 255, 0.5);
            }

            .user-dropdown.active .dropdown-menu {
                display: block;
            }
        }

        /* Main content padding to account for fixed header */
        .main-content {
            padding-top: 85px;
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 12px 20px;
            }

            .main-content {
                padding-top: 75px;
            }
            
            .logo-text {
                font-size: 1.6rem;
            }

            .logo-icon {
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 1.4rem;
            }

            .nav-links {
                width: 280px;
            }
        }
    </style>
    <?php if (isset($additionalStyles)): ?>
        <style><?php echo $additionalStyles; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="mainContainer">
        <header class="site-header">
            <div class="header-container">
                <div class="logo">
                    <a href="/HOUSE%20RENT/" class="logo-link">
                        <svg class="logo-icon" width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="8" fill="#4A6BFF"/>
                            <path d="M20 10L10 18V30H16V24H24V30H30V18L20 10Z" fill="white"/>
                            <path d="M16 24H24V30H16V24Z" fill="#3A5BEF"/>
                        </svg>
                        <span class="logo-text">Swapno<span>Nibash</span></span>
                    </a>
                </div>

                <button class="mobile-menu-toggle" aria-label="Toggle navigation" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>

                <nav class="nav-links">
                    <a href="/HOUSE%20RENT/" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                        Home
                    </a>
                    <a href="/HOUSE%20RENT/properties.php" class="<?php echo $currentPage === 'properties.php' ? 'active' : ''; ?>">
                        Properties
                    </a>
                    <a href="/HOUSE%20RENT/about.php" class="<?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">
                        About Us
                    </a>
                    <a href="/HOUSE%20RENT/contact.php" class="<?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>">
                        Contact
                    </a>
                    
                    <?php if ($isLoggedIn): ?>
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($userName); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="/HOUSE%20RENT/profile.php">
                                    <i class="fas fa-user"></i> My Profile
                                </a>
                                <a href="/HOUSE%20RENT/my-properties.php">
                                    <i class="fas fa-calendar-check"></i> My Bookings
                                </a>
                                <?php if ($isAdmin): ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="/HOUSE%20RENT/admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a href="/HOUSE%20RENT/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="/HOUSE%20RENT/login.php" class="btn btn-outline">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                            <a href="/HOUSE%20RENT/register.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Sign Up
                            </a>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        
        <main class="main-content">