<?php
/**
 * Facebook OAuth Callback Handler
 */

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/helpers.php';
require_once '../../modules/auth/SocialAuth.php';

// Check if code and state are present
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    setFlashMessage('การเข้าสู่ระบบด้วย Facebook ล้มเหลว', 'error');
    redirect('/public/login.php');
}

$socialAuth = new SocialAuth();
$result = $socialAuth->handleFacebookCallback($_GET['code'], $_GET['state']);

if ($result['success']) {
    setFlashMessage('เข้าสู่ระบบด้วย Facebook สำเร็จ!', 'success');
    
    // Redirect to original page or home
    $redirect = $_SESSION['redirect_after_login'] ?? '/public/index.php';
    unset($_SESSION['redirect_after_login']);
    redirect($redirect);
} else {
    setFlashMessage($result['message'], 'error');
    redirect('/public/login.php');
}
