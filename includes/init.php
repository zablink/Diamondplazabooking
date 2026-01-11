<?php
// includes/init.php
// System Initialization File
// Include this file at the top of every page

// Start session if not already started
define('SESSION_NAME', 'hotel_booking_session');
define('SESSION_LIFETIME', 86400); // 24 hours

if (session_status() === PHP_SESSION_NONE) {
    // Session Configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);  // 0 = HTTP+HTTPS, 1 = HTTPS only
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_path', '/booking/');

    session_name(SESSION_NAME);
    session_start();  // เริ่ม session ที่นี่เลย

    
}

/****** กำหนด PROJECT_ROOT เพื่อใช้สำหรับ require_once *****/
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}
/****************************/

// Load configuration
require_once PROJECT_ROOT . '/config/config.php';

// Load Database class
require_once PROJECT_ROOT . '/includes/Database.php';

// Load Language system
require_once PROJECT_ROOT . '/includes/Language.php';

// Initialize Language
$lang = Language::getInstance();

// Set language from GET parameter if provided
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $lang->setLanguage($_GET['lang']);
}

// Set timezone
date_default_timezone_set('Asia/Bangkok');


define('DEBUG_MODE', 1); /// 1- debug / 0 - production

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}



/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script;
}

/**
 * Get asset URL
 */
function asset($path) {
    return getBaseUrl() . '/assets/' . ltrim($path, '/');
}

/**
 * Debug print
 */
function dd($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die();
}
