<?php
// includes/header.php
// ไฟล์ Header สำหรับทุกหน้า - รองรับหลายภาษา

//// init for SESSION , PROJECT_PATH , etc..
// Auto-find project root
$projectRoot = __DIR__;
while (!file_exists($projectRoot . '/includes/init.php')) {
    $parent = dirname($projectRoot);
    if ($parent === $projectRoot) {
        die('Error: Cannot find project root');
    }
    $projectRoot = $parent;
}
require_once $projectRoot . '/includes/init.php';

// ตรวจสอบว่ามี helpers.php load แล้วหรือยัง
if (!function_exists('isLoggedIn')) {
    require_once PROJECT_ROOT . '/includes/helpers.php';
}

// Load language system
require_once PROJECT_ROOT . '/includes/lang.php';

// Check if user is logged in using helper function
$is_logged_in = isLoggedIn();
$user_name = $_SESSION['first_name'] ?? __('common.welcome');
$user_email = $_SESSION['email'] ?? '';
$login_method = $_SESSION['login_method'] ?? 'normal';

// Avatar handling using helper function
$user_avatar = $is_logged_in ? getUserAvatar($user_email) : 'assets/images/default-avatar.png';

// Get current language
$current_lang = getCurrentLanguage();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #667eea;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #667eea;
        }

        .login-method-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 10px;
            border-radius: 10px;
            background: #667eea;
            color: white;
            margin-left: 5px;
        }

        .login-method-badge.google {
            background: #4285F4;
        }

        .login-method-badge.facebook {
            background: #1877F2;
        }

        .logout-btn {
            padding: 8px 16px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Language Switcher */
        .lang-switcher {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: 20px;
        }

        .lang-switcher a {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .lang-switcher a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .lang-switcher a:not(.active) {
            background: #f0f0f0;
            color: #666;
        }

        .lang-switcher a:not(.active):hover {
            background: #e0e0e0;
            border-color: #667eea;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }

        .modal-content p {
            margin-bottom: 25px;
            color: #666;
            font-size: 16px;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-button {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modal-button.confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-button.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .modal-button.cancel {
            background: #e0e0e0;
            color: #333;
        }

        .modal-button.cancel:hover {
            background: #d0d0d0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                gap: 15px;
            }

            .lang-switcher {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-hotel"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>

            <nav class="nav-menu">
                <a href="index.php">
                    <i class="fas fa-door-open"></i> <?php _e('nav.rooms'); ?>
                </a>

                <?php if ($is_logged_in): ?>
                    <a href="my_bookings.php">
                        <i class="fas fa-calendar-check"></i> <?php _e('nav.my_bookings'); ?>
                    </a>
                    
                    <div class="user-info">
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" 
                             alt="<?php echo htmlspecialchars($user_name); ?>" 
                             class="user-avatar"
                             onerror="this.src='assets/images/default-avatar.png'">
                        <span>
                            <?php echo htmlspecialchars($user_name); ?>
                            <?php if ($login_method === 'google'): ?>
                                <span class="login-method-badge google">
                                    <i class="fab fa-google"></i> Google
                                </span>
                            <?php elseif ($login_method === 'facebook'): ?>
                                <span class="login-method-badge facebook">
                                    <i class="fab fa-facebook"></i> Facebook
                                </span>
                            <?php endif; ?>
                        </span>
                        <button onclick="showLogoutModal()" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> <?php _e('nav.logout'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> <?php _e('nav.login'); ?>
                    </a>
                    <a href="register.php" style="padding: 8px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; font-weight: 600;">
                        <i class="fas fa-user-plus"></i> <?php _e('nav.register'); ?>
                    </a>
                <?php endif; ?>

                <!-- Language Switcher -->
                <div class="lang-switcher">
                    <a href="?lang=th" class="<?php echo $current_lang === 'th' ? 'active' : ''; ?>">
                        <i class="fas fa-flag"></i> TH
                    </a>
                    <a href="?lang=en" class="<?php echo $current_lang === 'en' ? 'active' : ''; ?>">
                        <i class="fas fa-flag"></i> EN
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-sign-out-alt"></i> <?php _e('auth.logout_confirm'); ?></h3>
            <p><?php _e('auth.logout_confirm'); ?></p>
            <div class="modal-buttons">
                <button onclick="confirmLogout()" class="modal-button confirm">
                    <i class="fas fa-check"></i> <?php _e('common.confirm'); ?>
                </button>
                <button onclick="closeLogoutModal()" class="modal-button cancel">
                    <i class="fas fa-times"></i> <?php _e('common.cancel'); ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Logout Modal Functions
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            <?php if ($login_method === 'google' || $login_method === 'facebook'): ?>
                // For social logins, revoke tokens
                fetch('logout.php?revoke_social_token=1')
                    .then(() => window.location.href = 'logout.php')
                    .catch(() => window.location.href = 'logout.php');
            <?php else: ?>
                window.location.href = 'logout.php';
            <?php endif; ?>
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target === modal) {
                closeLogoutModal();
            }
        }
    </script>