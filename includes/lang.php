<?php
/**
 * Multi-Language System
 * รองรับภาษาไทย อังกฤษ และจีน
 */

// ตรวจสอบว่า init.php ถูก load แล้วหรือยัง
// ถ้ายังไม่ถูก load ให้ load เอง (สำหรับกรณีที่ lang.php ถูก require โดยตรง)
if (!defined('PROJECT_ROOT')) {
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
}


// ภาษาที่รองรับ
define('SUPPORTED_LANGUAGES', ['th', 'en', 'zh']);
define('DEFAULT_LANGUAGE', 'th');

// Language mapping สำหรับ fallback (สำหรับรูปแบบภาษาจีนอื่นๆ ให้ใช้ zh)
define('LANGUAGE_FALLBACK', [
    'zh_CN' => 'zh',
    'zh_TW' => 'zh',
    'zh_Hans' => 'zh',
    'zh_Hant' => 'zh'
]);

/**
 * ดึงภาษาจาก URL path
 * รองรับรูปแบบ: /booking/th/index.php หรือ /booking/en/index.php
 */
function getLanguageFromUrl() {
    // ตรวจสอบจาก QUERY_STRING ก่อน (เพราะ .htaccess rewrite มาแล้ว)
    // รองรับ parameter จาก polylang: lang, pll_lang
    $langParam = $_GET['lang'] ?? $_GET['pll_lang'] ?? null;
    
    if ($langParam) {
        // ตรวจสอบว่ามี fallback หรือไม่ (เช่น zh_CN -> zh)
        if (isset(LANGUAGE_FALLBACK[$langParam])) {
            return LANGUAGE_FALLBACK[$langParam];
        }
        
        // ตรวจสอบว่าเป็นภาษาที่รองรับหรือไม่
        if (in_array($langParam, SUPPORTED_LANGUAGES)) {
            return $langParam;
        }
        
        // ถ้าเป็นภาษาจีนรูปแบบอื่น (zh_CN, zh_TW, zh_Hans, zh_Hant) ให้ fallback เป็น zh
        if (preg_match('/^zh/i', $langParam)) {
            return 'zh';
        }
    }
    
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // ลบ query string ออก
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // ตรวจสอบว่ามีภาษาใน path หรือไม่
    // รูปแบบ: /booking/th/... หรือ /booking/en/... หรือ /booking/zh/...
    if (preg_match('#/(th|en|zh)(/|$)#', $path, $matches)) {
        return $matches[1];
    }
    
    // ตรวจสอบจาก SCRIPT_NAME
    if (preg_match('#/(th|en|zh)/(.+)$#', $scriptName, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * ดึง path ปัจจุบันโดยไม่มีภาษา
 */
function getCurrentPathWithoutLang() {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // ใช้ SCRIPT_NAME เป็นหลัก (เพราะมันเป็น path จริงของไฟล์)
    $path = $scriptName;
    
    // ลบ /booking/ prefix
    $path = preg_replace('#^/booking/#', '', $path);
    
    // ลบภาษา prefix ถ้ามี (รวม zh ด้วย)
    $path = preg_replace('#^(th|en|zh)/#', '', $path);
    
    // ถ้าเป็น root หรือ empty ให้เป็น index.php
    if (empty($path) || $path === '/' || $path === '/booking/') {
        return 'index.php';
    }
    
    return $path;
}

// ดึงภาษาจาก URL
$urlLang = getLanguageFromUrl();

// ถ้ามีภาษาใน URL ให้ใช้ภาษานั้น
if ($urlLang && in_array($urlLang, SUPPORTED_LANGUAGES)) {
    $_SESSION['lang'] = $urlLang;
    $lang = $urlLang;
} else {
    // ถ้าไม่มีภาษาใน URL ให้ตรวจสอบ session หรือ browser
    if (!isset($_SESSION['lang'])) {
        // ตรวจสอบภาษาจาก browser
        $browserLang = getBrowserLanguage();
        $_SESSION['lang'] = $browserLang;
    }
    
    $lang = $_SESSION['lang'] ?? DEFAULT_LANGUAGE;
    
    // Redirect ไปยัง URL ที่มีภาษา (ถ้ายังไม่มี)
    // แต่ไม่ redirect ถ้าเป็น AJAX request หรือ POST request หรือ admin/auth
    // หรือถ้ามี lang parameter ใน query string แล้ว (เพราะ .htaccess rewrite มาแล้ว)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && 
        !isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strpos($_SERVER['REQUEST_URI'], '/admin/') === false &&
        strpos($_SERVER['REQUEST_URI'], '/auth/') === false &&
        !isset($_GET['lang']) && // ไม่ redirect ถ้ามี lang parameter แล้ว (ถูก rewrite มาแล้ว)
        strpos($_SERVER['REQUEST_URI'], '/booking/' . $lang . '/') === false) {
        
        $currentPath = getCurrentPathWithoutLang();
        
        // ตรวจสอบว่า path ไม่ใช่ empty หรือ root
        if (empty($currentPath) || $currentPath === '/' || $currentPath === '/booking/') {
            $currentPath = 'index.php';
        }
        
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        if ($queryString) {
            // ลบ lang parameter ออกถ้ามี
            parse_str($queryString, $params);
            unset($params['lang']);
            $queryString = http_build_query($params);
        }
        
        // สร้าง URL ในรูปแบบ /booking/{lang}/{path}
        $newUrl = '/booking/' . $lang . '/' . $currentPath;
        if ($queryString) {
            $newUrl .= '?' . $queryString;
        }
        
        // Redirect ไปยัง URL ที่มีภาษา
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $currentUriPath = parse_url($currentUri, PHP_URL_PATH);
        
        // ตรวจสอบว่า URL ปัจจุบันไม่ใช่ URL ที่ต้องการ redirect ไป
        if ($currentUriPath !== $newUrl && 
            strpos($currentUriPath, '/booking/' . $lang . '/') === false &&
            !isset($_GET['lang'])) { // ไม่ redirect ถ้ามี lang parameter แล้ว
            header("Location: $newUrl", true, 301);
            exit;
        }
    }
}

// Debug: log ภาษาที่ใช้
error_log("Language Detection - URL Lang: " . ($urlLang ?? 'null') . ", Session Lang: " . ($_SESSION['lang'] ?? 'null') . ", Final Lang: $lang");

// อัปเดต Language class ให้ตรงกับภาษาที่ตรวจพบ
if (class_exists('Language')) {
    $langInstance = Language::getInstance();
    $langInstance->setLanguage($lang);
}

// โหลดไฟล์ภาษา
$translations = [];

// ใช้ภาษาที่ตรวจพบ (รองรับ zh แล้ว)
$loadLang = $lang;

$langFile = __DIR__ . '/languages/' . $loadLang . '.php';
if (file_exists($langFile)) {
    $translations = require $langFile;
    error_log("Loaded language file: $langFile (requested lang: $lang, loaded lang: $loadLang, translations count: " . count($translations) . ")");
} else {
    // Fallback to default language if file doesn't exist
    $defaultLangFile = __DIR__ . '/languages/' . DEFAULT_LANGUAGE . '.php';
    if (file_exists($defaultLangFile)) {
        $translations = require $defaultLangFile;
        error_log("Language file not found: $langFile, using default: $defaultLangFile");
    } else {
        error_log("ERROR: Default language file not found: $defaultLangFile");
    }
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
    
    // Debug: log ข้อมูลภาษา
    static $debugLogged = false;
    if (!$debugLogged) {
        $currentLang = getCurrentLanguage();
        error_log("Translation Debug - Current Language: $currentLang, Translations loaded: " . (empty($translations) ? 'NO' : 'YES') . ", Key count: " . count($translations));
        $debugLogged = true;
    }
    
    // แยก key ด้วย dot notation (เช่น 'nav.home')
    $keys = explode('.', $key);
    $value = $translations;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            // ถ้าไม่เจอ key ให้ return key เดิม
            error_log("Translation key not found: $key (current lang: " . getCurrentLanguage() . ")");
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
    // ลองดึงจาก URL ก่อน
    $urlLang = getLanguageFromUrl();
    if ($urlLang) {
        return $urlLang;
    }
    
    $sessionLang = $_SESSION['lang'] ?? DEFAULT_LANGUAGE;
    return $sessionLang;
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
 * รองรับ URL ที่มีภาษาใน path
 */
function getLanguageUrl($language) {
    if (!in_array($language, SUPPORTED_LANGUAGES)) {
        $language = DEFAULT_LANGUAGE;
    }
    
    $currentPath = getCurrentPathWithoutLang();
    $queryParams = $_GET;
    unset($queryParams['lang']); // ลบ lang parameter ออก
    
    $url = '/booking/' . $language . '/' . $currentPath;
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    return $url;
}

/**
 * สร้าง URL ที่มีภาษา (helper function)
 * ใช้สำหรับสร้างลิงก์ภายในเว็บ
 */
if (!function_exists('url')) {
    function url($path = '', $params = []) {
        $lang = getCurrentLanguage();
        
        // ถ้า path เริ่มต้นด้วย http:// หรือ https:// ให้ return ตามเดิม
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        
        // ถ้า path เริ่มต้นด้วย / แต่ไม่ใช่ /booking/ ให้ return ตามเดิม
        if (strpos($path, '/') === 0 && strpos($path, '/booking/') !== 0) {
            return $path;
        }
        
        // ถ้า path เป็น empty ให้เป็น index.php
        if (empty($path)) {
            $path = 'index.php';
        }
        
        // ลบ /booking/ prefix ถ้ามี
        $path = preg_replace('#^/booking/#', '', $path);
        
        // ลบภาษา prefix ถ้ามี (th/, en/, หรือ zh/)
        $path = preg_replace('#^(th|en|zh)/#', '', $path);
        
        // ลบ leading slash ถ้ามี
        $path = ltrim($path, '/');
        
        // ถ้า path เป็น empty หลังจากลบ prefix แล้ว ให้เป็น index.php
        if (empty($path)) {
            $path = 'index.php';
        }
        
        // สร้าง URL ในรูปแบบ /booking/{lang}/{path}
        $url = '/booking/' . $lang . '/' . $path;
        
        // เพิ่ม query parameters
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
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
    } elseif ($lang === 'zh') {
        // ภาษาจีน
        $chinese_months = [
            1 => '1月', '2月', '3月', '4月', '5月', '6月',
            '7月', '8月', '9月', '10月', '11月', '12月'
        ];
        
        $day = date('d', $timestamp);
        $month = (int)date('m', $timestamp);
        $year = date('Y', $timestamp);
        
        if ($format === 'full') {
            return $year . '年' . $chinese_months[$month] . $day . '日';
        } else {
            return $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $day;
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
    } elseif ($lang === 'zh') {
        return '฿' . number_format($price, 0);
    } else {
        return 'THB ' . number_format($price, 2);
    }
}
