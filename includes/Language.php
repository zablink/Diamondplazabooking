<?php
// includes/Language.php
// Multi-language Support System

class Language {
    private static $instance = null;
    private $currentLang = 'th'; // default language
    private $translations = [];
    
    // Singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Language();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Load language from session
        if (isset($_SESSION['lang'])) {
            $this->currentLang = $_SESSION['lang'];
        }
        
        // Load translations
        $this->loadTranslations();
    }
    
    /**
     * โหลดคำแปลภาษา
     */
    private function loadTranslations() {
        // Thai translations
        $this->translations['th'] = [
            // Common
            'home' => 'หน้าแรก',
            'rooms' => 'ห้องพัก',
            'about' => 'เกี่ยวกับเรา',
            'contact' => 'ติดต่อเรา',
            'login' => 'เข้าสู่ระบบ',
            'logout' => 'ออกจากระบบ',
            'register' => 'สมัครสมาชิก',
            'my_bookings' => 'การจองของฉัน',
            
            // Room Details
            'room_details' => 'รายละเอียดห้องพัก',
            'room_name' => 'ชื่อห้อง',
            'price_per_night' => 'ราคาต่อคืน',
            'max_occupancy' => 'จำนวนผู้พักสูงสุด',
            'room_size' => 'ขนาดห้อง',
            'bed_type' => 'ประเภทเตียง',
            'amenities' => 'สิ่งอำนวยความสะดวก',
            'description' => 'คำอธิบาย',
            'view_details' => 'ดูรายละเอียด',
            'book_now' => 'จองเลย',
            'check_availability' => 'ตรวจสอบห้องว่าง',
            
            // Booking
            'check_in' => 'เช็คอิน',
            'check_out' => 'เช็คเอาท์',
            'adults' => 'ผู้ใหญ่',
            'children' => 'เด็ก',
            'rooms_count' => 'จำนวนห้อง',
            'nights' => 'คืน',
            'total_price' => 'ราคารวม',
            'breakfast_included' => 'รวมอาหารเช้า',
            'breakfast_not_included' => 'ไม่รวมอาหารเช้า',
            'add_breakfast' => 'เพิ่มอาหารเช้า',
            
            // Status
            'available' => 'พร้อมให้บริการ',
            'unavailable' => 'ไม่พร้อมให้บริการ',
            'booked' => 'จองแล้ว',
            'pending' => 'รอดำเนินการ',
            'confirmed' => 'ยืนยันแล้ว',
            'cancelled' => 'ยกเลิกแล้ว',
            
            // Messages
            'no_rooms_available' => 'ไม่มีห้องว่างในช่วงเวลานี้',
            'booking_success' => 'จองห้องสำเร็จ',
            'booking_failed' => 'จองห้องไม่สำเร็จ',
            'please_login' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง',
            'error_occurred' => 'เกิดข้อผิดพลาด',
            
            // Units
            'baht' => 'บาท',
            'sqm' => 'ตร.ม.',
            'person' => 'คน',
            'persons' => 'คน',
            
            // Days of week
            'monday' => 'จันทร์',
            'tuesday' => 'อังคาร',
            'wednesday' => 'พุธ',
            'thursday' => 'พฤหัสบดี',
            'friday' => 'ศุกร์',
            'saturday' => 'เสาร์',
            'sunday' => 'อาทิตย์',
            
            // Months
            'january' => 'มกราคม',
            'february' => 'กุมภาพันธ์',
            'march' => 'มีนาคม',
            'april' => 'เมษายน',
            'may' => 'พฤษภาคม',
            'june' => 'มิถุนายน',
            'july' => 'กรกฎาคม',
            'august' => 'สิงหาคม',
            'september' => 'กันยายน',
            'october' => 'ตุลาคม',
            'november' => 'พฤศจิกายน',
            'december' => 'ธันวาคม',
        ];
        
        // English translations
        $this->translations['en'] = [
            // Common
            'home' => 'Home',
            'rooms' => 'Rooms',
            'about' => 'About Us',
            'contact' => 'Contact',
            'login' => 'Login',
            'logout' => 'Logout',
            'register' => 'Register',
            'my_bookings' => 'My Bookings',
            
            // Room Details
            'room_details' => 'Room Details',
            'room_name' => 'Room Name',
            'price_per_night' => 'Price per Night',
            'max_occupancy' => 'Max Occupancy',
            'room_size' => 'Room Size',
            'bed_type' => 'Bed Type',
            'amenities' => 'Amenities',
            'description' => 'Description',
            'view_details' => 'View Details',
            'book_now' => 'Book Now',
            'check_availability' => 'Check Availability',
            
            // Booking
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
            'adults' => 'Adults',
            'children' => 'Children',
            'rooms_count' => 'Rooms',
            'nights' => 'Nights',
            'total_price' => 'Total Price',
            'breakfast_included' => 'Breakfast Included',
            'breakfast_not_included' => 'Breakfast Not Included',
            'add_breakfast' => 'Add Breakfast',
            
            // Status
            'available' => 'Available',
            'unavailable' => 'Unavailable',
            'booked' => 'Booked',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            
            // Messages
            'no_rooms_available' => 'No rooms available for this period',
            'booking_success' => 'Booking successful',
            'booking_failed' => 'Booking failed',
            'please_login' => 'Please login to make a booking',
            'error_occurred' => 'An error occurred',
            
            // Units
            'baht' => 'THB',
            'sqm' => 'sqm',
            'person' => 'person',
            'persons' => 'persons',
            
            // Days of week
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
            
            // Months
            'january' => 'January',
            'february' => 'February',
            'march' => 'March',
            'april' => 'April',
            'may' => 'May',
            'june' => 'June',
            'july' => 'July',
            'august' => 'August',
            'september' => 'September',
            'october' => 'October',
            'november' => 'November',
            'december' => 'December',
        ];
    }
    
    /**
     * แปลข้อความ
     */
    public function translate($key, $params = []) {
        $lang = $this->currentLang;
        
        // ถ้าไม่เจอคำแปล ให้คืนค่า key
        if (!isset($this->translations[$lang][$key])) {
            return $key;
        }
        
        $translation = $this->translations[$lang][$key];
        
        // แทนที่ parameters (ถ้ามี)
        if (!empty($params)) {
            foreach ($params as $paramKey => $paramValue) {
                $translation = str_replace(':' . $paramKey, $paramValue, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * ตั้งค่าภาษา
     */
    public function setLanguage($lang) {
        if (in_array($lang, ['th', 'en'])) {
            $this->currentLang = $lang;
            $_SESSION['lang'] = $lang;
        }
    }
    
    /**
     * ดึงภาษาปัจจุบัน
     */
    public function getCurrentLanguage() {
        return $this->currentLang;
    }
    
    /**
     * เพิ่มคำแปล
     */
    public function addTranslation($lang, $key, $value) {
        if (!isset($this->translations[$lang])) {
            $this->translations[$lang] = [];
        }
        $this->translations[$lang][$key] = $value;
    }
    
    /**
     * เพิ่มคำแปลหลายคำ
     */
    public function addTranslations($lang, $translations) {
        if (!isset($this->translations[$lang])) {
            $this->translations[$lang] = [];
        }
        $this->translations[$lang] = array_merge($this->translations[$lang], $translations);
    }
}

/**
 * Helper function สำหรับแปลภาษา
 * 
 * @param string $key - คีย์ของคำแปล
 * @param array $params - parameters สำหรับแทนที่ในข้อความ
 * @return string - ข้อความที่แปลแล้ว
 */
/*
function __($key, $params = []) {
    return Language::getInstance()->translate($key, $params);
}*/


/**
 * Helper function สำหรับเปลี่ยนภาษา
 */
function setLang($lang) {
    Language::getInstance()->setLanguage($lang);
}

/**
 * Helper function สำหรับดึงภาษาปัจจุบัน
 */
function getLang() {
    return Language::getInstance()->getCurrentLanguage();
}
