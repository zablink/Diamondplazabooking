<?php
/**
 * Google OAuth Callback Handler
 * Updated to properly store access_token for logout functionality
 */

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

require_once PROJECT_ROOT .  '/config/config.php';
require_once PROJECT_ROOT .  '/includes/Database.php';
require_once PROJECT_ROOT .  '/includes/helpers.php';
require_once PROJECT_ROOT .  '/modules/auth/SocialAuth.php';

// Check if code is present
if (!isset($_GET['code'])) {
    setFlashMessage('การเข้าสู่ระบบด้วย Google ล้มเหลว', 'error');
    redirect(PROJECT_ROOT . '/login.php');
}

$socialAuth = new SocialAuth();
$result = $socialAuth->handleGoogleCallback($_GET['code']);

if ($result['success']) {
    // ⭐⭐⭐ สำคัญมาก! เก็บข้อมูลสำหรับ logout
    $_SESSION['login_method'] = 'google';
    
    // เก็บ access_token สำหรับการ logout (revoke token)
    if (isset($result['access_token']) && !empty($result['access_token'])) {
        $_SESSION['social_access_token'] = $result['access_token'];
    }
    
    // เก็บ google_id สำหรับ reference
    if (isset($result['google_id']) && !empty($result['google_id'])) {
        $_SESSION['google_id'] = $result['google_id'];
    }
    
    // เก็บข้อมูลเพิ่มเติม (optional)
    if (isset($result['expires_at']) && !empty($result['expires_at'])) {
        $_SESSION['token_expires_at'] = $result['expires_at'];
    }
    
    // Log for debugging (ลบออกใน production)
    error_log("Google login success - User: " . ($_SESSION['email'] ?? 'unknown') . 
              ", Has token: " . (isset($_SESSION['social_access_token']) ? 'YES' : 'NO'));
    
    setFlashMessage('เข้าสู่ระบบด้วย Google สำเร็จ!', 'success');
    
    // Redirect to original page or home
    $redirect = $_SESSION['redirect_after_login'] ?? '/booking/index.php';
    unset($_SESSION['redirect_after_login']);
    $redirect = str_replace('/booking', 'booking', $redirect);
    redirect($redirect);
} else {
    setFlashMessage($result['message'], 'error');
    redirect( PROJECT_ROOT . '/login.php');
}