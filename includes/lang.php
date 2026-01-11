<?php
/**
 * Multi-Language System
 * รองรับภาษาไทยและอังกฤษ
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


// ภาษาที่รองรับ
define('SUPPORTED_LANGUAGES', ['th', 'en']);
define('DEFAULT_LANGUAGE', 'th');

// ตั้งค่าภาษาเริ่มต้น
if (!isset($_SESSION['lang'])) {
    // ตรวจสอบภาษาจาก browser
    $browserLang = getBrowserLanguage();
    $_SESSION['lang'] = $browserLang;
}

// เปลี่ยนภาษาเมื่อมีการคลิกเปลี่ยน
if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Redirect กลับหน้าเดิมโดยไม่มี ?lang= ใน URL
    $redirect_url = $_SERVER['PHP_SELF'];
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $params);
        unset($params['lang']);
        if (!empty($params)) {
            $redirect_url .= '?' . http_build_query($params);
        }
    }
    header("Location: $redirect_url");
    exit;
}

// โหลดไฟล์ภาษา
$lang = $_SESSION['lang'] ?? DEFAULT_LANGUAGE;
$translations = [];

$langFile = __DIR__ . '/languages/' . $lang . '.php';
if (file_exists($langFile)) {
    $translations = require $langFile;
}

/**
 * ฟังก์ชันแปลภาษา
 * 
 * @param string $key คีย์ของข้อความ
 * @param array $params ตัวแปรที่จะแทนที่ใน string (optional)
 * @return string ข้อความที่แปลแล้ว
 */
function __($key, $params = []) {
    global $translations;
    
    // แยก key ด้วย dot notation (เช่น 'nav.home')
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            // ถ้าไม่เจอ key ให้ return key เดิม
            return $key;
        }
    }
    
    // แทนที่ตัวแปร {variable}
    if (!empty($params)) {
        foreach ($params as $param_key => $param_value) {
            $value = str_replace('{' . $param_key . '}', $param_value, $value);
        }
    }
    
    return $value;
}

/**
 * ฟังก์ชันแปลภาษาและ echo ออกมาเลย
 */
function _e($key, $params = []) {
    echo __($key, $params);
}

/**
 * ดึงภาษาปัจจุบัน
 */
function getCurrentLanguage() {
    return $_SESSION['lang'] ?? DEFAULT_LANGUAGE;
}

/**
 * ตรวจสอบว่าเป็นภาษาที่กำหนดหรือไม่
 */
function isLanguage($language) {
    return getCurrentLanguage() === $language;
}

/**
 * ตรวจสอบภาษาจาก browser
 */
function getBrowserLanguage() {
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browserLangs as $browserLang) {
            $lang = substr($browserLang, 0, 2);
            if (in_array($lang, SUPPORTED_LANGUAGES)) {
                return $lang;
            }
        }
    }
    return DEFAULT_LANGUAGE;
}

/**
 * สร้าง URL สำหรับเปลี่ยนภาษา
 */
function getLanguageUrl($language) {
    $currentUrl = $_SERVER['PHP_SELF'];
    $queryParams = $_GET;
    $queryParams['lang'] = $language;
    return $currentUrl . '?' . http_build_query($queryParams);
}

/**
 * แปลงวันที่ตามภาษา
 */
function formatDateByLang($date, $format = null) {
    $lang = getCurrentLanguage();
    $timestamp = strtotime($date);
    
    if ($lang === 'th') {
        // ภาษาไทย
        $thai_months = [
            1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
        ];
        $thai_months_full = [
            1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        
        $day = date('d', $timestamp);
        $month = (int)date('m', $timestamp);
        $year = date('Y', $timestamp) + 543; // พ.ศ.
        
        if ($format === 'full') {
            return $day . ' ' . $thai_months_full[$month] . ' ' . $year;
        } else {
            return $day . ' ' . $thai_months[$month] . ' ' . $year;
        }
    } else {
        // ภาษาอังกฤษ
        if ($format === 'full') {
            return date('d F Y', $timestamp);
        } else {
            return date('d M Y', $timestamp);
        }
    }
}

/**
 * แปลงราคาตามภาษา
 */
function formatPriceByLang($price) {
    $lang = getCurrentLanguage();
    
    if ($lang === 'th') {
        return '฿' . number_format($price, 0);
    } else {
        return 'THB ' . number_format($price, 2);
    }
}
