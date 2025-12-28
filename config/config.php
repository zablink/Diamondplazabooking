<?php
// Configuration File for Hotel Booking System
    
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'diamondp_surat');
define('DB_USER', 'diamondp_surat');
define('DB_PASS', '3Z3S4.xhp-');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'Booking System : Diamond Plaza Surat');
define('SITE_URL', 'https://diamondplazasurat.com/bk');
define('BASE_PATH', dirname(__DIR__));

// Single Hotel Mode - กำหนด ID โรงแรมที่ใช้งาน
define('SINGLE_HOTEL_MODE', true);
define('HOTEL_ID', 1); // เปลี่ยนเป็น ID โรงแรมของคุณ

// Session Configuration
define('SESSION_NAME', 'hotel_booking_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Social Login Configuration
// Google OAuth 2.0
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/public/auth/google-callback.php');

// Facebook Login
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET');
define('FACEBOOK_REDIRECT_URI', SITE_URL . '/public/auth/facebook-callback.php');

// Apple Sign In (Optional)
define('APPLE_CLIENT_ID', 'YOUR_APPLE_CLIENT_ID');
define('APPLE_TEAM_ID', 'YOUR_APPLE_TEAM_ID');
define('APPLE_KEY_ID', 'YOUR_APPLE_KEY_ID');
define('APPLE_REDIRECT_URI', SITE_URL . '/public/auth/apple-callback.php');

// Pagination
define('ITEMS_PER_PAGE', 12);

// Upload Configuration
define('UPLOAD_PATH', BASE_PATH . '/public/images/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
