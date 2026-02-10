<?php
/**
 * Helper Functions for Hotel Booking System
 */

/**
 * Get Hotel Name from settings or fallback to SITE_NAME
 * รองรับหลายภาษา
 */
function getHotelName($lang = null) {
    static $hotelNames = [];
    
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    if (!isset($hotelNames[$lang])) {
        try {
            require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';
            $admin = new Admin();
            $settings = $admin->getHotelSettings($lang);
            $langKey = 'hotel_name_' . $lang;
            $hotelNames[$lang] = !empty($settings[$langKey]) ? $settings[$langKey] : 
                                (!empty($settings['hotel_name']) ? $settings['hotel_name'] : 
                                (defined('SITE_NAME') ? SITE_NAME : 'Hotel'));
        } catch (Exception $e) {
            $hotelNames[$lang] = defined('SITE_NAME') ? SITE_NAME : 'Hotel';
        }
    }
    
    return $hotelNames[$lang];
}

/**
 * Get Hotel Settings (cached)
 * รองรับหลายภาษา
 */
function getHotelSettings($lang = null) {
    static $settingsCache = [];
    
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    if (!isset($settingsCache[$lang])) {
        try {
            require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';
            $admin = new Admin();
            $settings = $admin->getHotelSettings($lang);
            $settingsCache[$lang] = $settings;
        } catch (Exception $e) {
            $settingsCache[$lang] = [
                'hotel_name' => defined('SITE_NAME') ? SITE_NAME : 'Hotel',
                'description' => '',
                'address' => '',
                'city' => '',
                'phone' => '',
                'email' => ''
            ];
        }
    }
    
    return $settingsCache[$lang];
}

/**
 * Get Hotel Description (multilingual)
 */
function getHotelDescription($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    $settings = getHotelSettings($lang);
    $langKey = 'description_' . $lang;
    return !empty($settings[$langKey]) ? $settings[$langKey] : 
           (!empty($settings['description']) ? $settings['description'] : '');
}

/**
 * Get Amenity Name (multilingual)
 * ดึงชื่อสิ่งอำนวยความสะดวกตามภาษา
 */
function getAmenityName($amenityName, $lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    try {
        require_once PROJECT_ROOT . '/includes/Database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // ตรวจสอบว่ามีตาราง bk_amenities หรือไม่
        $checkTable = $conn->query("SHOW TABLES LIKE 'bk_amenities'");
        if ($checkTable->rowCount() == 0) {
            return $amenityName; // ถ้าไม่มีตาราง ให้ return ชื่อเดิม
        }
        
        // ค้นหา amenity โดยใช้ชื่อภาษาไทยเป็นหลัก
        $stmt = $conn->prepare("SELECT amenity_name_th, amenity_name_en, amenity_name_zh, amenity_name FROM bk_amenities WHERE amenity_name = :name OR amenity_name_th = :name LIMIT 1");
        $stmt->execute([':name' => $amenityName]);
        $amenity = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($amenity) {
            $langKey = 'amenity_name_' . $lang;
            if (!empty($amenity[$langKey])) {
                return $amenity[$langKey];
            }
            // Fallback to Thai
            if (!empty($amenity['amenity_name_th'])) {
                return $amenity['amenity_name_th'];
            }
            // Fallback to original name
            if (!empty($amenity['amenity_name'])) {
                return $amenity['amenity_name'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting amenity name: " . $e->getMessage());
    }
    
    return $amenityName;
}

/**
 * Redirect to a page
 * รองรับ URL ที่มีภาษา
 */
function redirect($url) {
    // ถ้า URL ไม่มีภาษาและเป็น relative path ให้เพิ่มภาษา
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0 && strpos($url, '/') !== 0) {
        // ตรวจสอบว่ามีฟังก์ชัน url() หรือไม่
        if (function_exists('url')) {
            // แยก path และ query string
            $parts = parse_url($url);
            $path = $parts['path'] ?? $url;
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            
            // ใช้ฟังก์ชัน url() เพื่อสร้าง URL ที่มีภาษา
            parse_str($parts['query'] ?? '', $params);
            $url = url($path, $params);
        } else {
            // Fallback: เพิ่มภาษาเอง
            $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';
            if (strpos($url, '/booking/') !== 0) {
                $url = '/booking/' . $lang . '/' . $url;
            }
        }
    }
    
    session_write_close();
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']) || 
           (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Format price
 */
function formatPrice($price) {
    return '฿' . number_format($price, 0);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Format Thai date
 */
function formatThaiDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = date('n', $timestamp);
    $year = date('Y', $timestamp) + 543; // Buddhist year
    
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    return $day . ' ' . $thaiMonths[$month] . ' ' . $year;
}


/**
 * Calculate nights between dates
 */
function calculateNights($checkIn, $checkOut) {
    $start = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    $diff = $start->diff($end);
    return $diff->days;
}

/**
 * Generate booking reference
 */
function generateBookingReference() {
    return 'BK' . strtoupper(uniqid());
}

/**
 * Flash message
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}



/**
 * Generate star rating HTML
 */
function generateStarRating($rating) {
    $html = '<div class="star-rating">';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    
    if ($halfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    $emptyStars = 5 - ceil($rating);
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    
    $html .= '<span class="rating-number">' . number_format($rating, 1) . '</span>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Parse JSON or return empty array
 */
function parseJSON($json) {
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Check date availability
 */
function isDateAvailable($checkIn, $checkOut) {
    $today = date('Y-m-d');
    return $checkIn >= $today && $checkOut > $checkIn;
}

/**
 * Get user avatar
 */
function getUserAvatar($email) {
    return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?d=identicon&s=100';
}

/**
 * สร้าง URL ที่มีภาษา
 * ฟังก์ชัน url() ถูก define ใน lang.php
 * ไม่ต้อง declare ที่นี่เพื่อหลีกเลี่ยงการ declare ซ้ำ
 */
