<?php
/**
 * WordPress Shortcode สำหรับเชื่อมต่อกับ Booking System
 * รองรับ Polylang และภาษาจีน
 * 
 * วิธีใช้งาน:
 * 1. คัดลอกโค้ดนี้ไปใส่ใน functions.php ของ WordPress theme
 * 2. หรือสร้างเป็น plugin แยก
 * 3. ใช้ shortcode [hotel_booking] ใน post หรือ page
 * 
 * ตัวอย่าง:
 * [hotel_booking room_id="1"]
 * [hotel_booking room_id="1" check_in="2024-01-15" check_out="2024-01-17" adults="2"]
 * [hotel_booking room_id="1" button_text="จองห้องพักนี้" style="primary"]
 */

/**
 * Shortcode หลักสำหรับสร้างปุ่มจองห้องพัก
 * 
 * Parameters:
 * - room_id (required): ID ของห้องพัก
 * - check_in (optional): วันที่เช็คอิน (YYYY-MM-DD)
 * - check_out (optional): วันที่เช็คเอาท์ (YYYY-MM-DD)
 * - adults (optional): จำนวนผู้ใหญ่ (default: 2)
 * - children (optional): จำนวนเด็ก (default: 0)
 * - rooms (optional): จำนวนห้อง (default: 1)
 * - button_text (optional): ข้อความบนปุ่ม
 * - style (optional): สไตล์ปุ่ม (primary, secondary, outline)
 * - class (optional): CSS class เพิ่มเติม
 * - target (optional): _self หรือ _blank (default: _self)
 */
function hotel_booking_shortcode($atts) {
    // ตั้งค่า default values
    $atts = shortcode_atts(array(
        'room_id' => '',           // ID ของห้องพัก (required)
        'check_in' => '',          // วันที่เช็คอิน (YYYY-MM-DD)
        'check_out' => '',         // วันที่เช็คเอาท์ (YYYY-MM-DD)
        'adults' => '2',           // จำนวนผู้ใหญ่
        'children' => '0',         // จำนวนเด็ก
        'rooms' => '1',            // จำนวนห้อง
        'button_text' => '',       // ข้อความบนปุ่ม (ถ้าว่างจะใช้ตามภาษา)
        'style' => 'primary',      // primary, secondary, outline
        'class' => '',             // CSS class เพิ่มเติม
        'target' => '_self'        // _self, _blank
    ), $atts);
    
    // ตรวจสอบว่ามี room_id หรือไม่
    if (empty($atts['room_id'])) {
        return '<span style="color:red; padding:10px; display:block;">⚠️ Error: room_id is required. Example: [hotel_booking room_id="1"]</span>';
    }
    
    // ดึงภาษาปัจจุบันจาก Polylang
    $current_lang = 'th'; // default
    if (function_exists('pll_current_language')) {
        // Polylang function
        $poly_lang = pll_current_language('slug');
        // แปลงภาษา Polylang เป็นภาษาของระบบ
        // รองรับ zh_CN, zh_TW, zh_HK -> zh
        if (strpos($poly_lang, 'zh') === 0) {
            $current_lang = 'zh';
        } elseif (in_array($poly_lang, ['th', 'en', 'zh'])) {
            $current_lang = $poly_lang;
        }
    } elseif (function_exists('get_locale')) {
        // WordPress default locale
        $locale = get_locale();
        if (strpos($locale, 'zh') === 0) {
            $current_lang = 'zh';
        } elseif (strpos($locale, 'th') === 0) {
            $current_lang = 'th';
        } elseif (strpos($locale, 'en') === 0) {
            $current_lang = 'en';
        }
    }
    
    // Base URL ของ Booking System (แก้ไขตาม URL จริงของระบบ)
    // ตัวอย่าง: https://diamondplazasurat.com/booking
    $base_url = apply_filters('hotel_booking_base_url', 'https://diamondplazasurat.com/booking');
    
    // สร้าง URL ไปยัง room_detail.php
    $url = trailingslashit($base_url) . $current_lang . '/room_detail.php';
    
    // สร้าง query parameters
    $params = array();
    $params['room_type_id'] = $atts['room_id']; // room_detail.php รับทั้ง id และ room_type_id
    
    if (!empty($atts['check_in'])) {
        $params['check_in'] = $atts['check_in'];
    }
    if (!empty($atts['check_out'])) {
        $params['check_out'] = $atts['check_out'];
    }
    if (!empty($atts['adults']) && $atts['adults'] != '2') {
        $params['adults'] = $atts['adults'];
    }
    if (!empty($atts['children']) && $atts['children'] != '0') {
        $params['children'] = $atts['children'];
    }
    if (!empty($atts['rooms']) && $atts['rooms'] != '1') {
        $params['rooms'] = $atts['rooms'];
    }
    
    // เพิ่ม query string
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    // ข้อความบนปุ่มตามภาษา
    if (empty($atts['button_text'])) {
        switch ($current_lang) {
            case 'th':
                $button_text = 'ดูรายละเอียดและจอง';
                break;
            case 'en':
                $button_text = 'View Details & Book';
                break;
            case 'zh':
                $button_text = '查看详情并预订';
                break;
            default:
                $button_text = 'จองเลย';
        }
    } else {
        $button_text = $atts['button_text'];
    }
    
    // CSS classes
    $css_classes = array('hotel-booking-button');
    if ($atts['style'] === 'primary') {
        $css_classes[] = 'btn-primary';
    } elseif ($atts['style'] === 'secondary') {
        $css_classes[] = 'btn-secondary';
    } elseif ($atts['style'] === 'outline') {
        $css_classes[] = 'btn-outline';
    }
    if (!empty($atts['class'])) {
        $css_classes[] = $atts['class'];
    }
    $css_class = esc_attr(implode(' ', $css_classes));
    
    // สร้าง HTML
    $html = '<a href="' . esc_url($url) . '"';
    $html .= ' class="' . $css_class . '"';
    $html .= ' target="' . esc_attr($atts['target']) . '"';
    $html .= '>';
    $html .= esc_html($button_text);
    $html .= '</a>';
    
    return $html;
}
add_shortcode('hotel_booking', 'hotel_booking_shortcode');

/**
 * Shortcode สำหรับสร้างลิงก์ไปยังหน้าจองพร้อมข้อมูลทั้งหมด
 * 
 * ตัวอย่าง:
 * [hotel_booking_link room_id="1" check_in="2024-01-15" check_out="2024-01-17"]
 */
function hotel_booking_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'room_id' => '',
        'check_in' => '',
        'check_out' => '',
        'adults' => '2',
        'children' => '0',
        'rooms' => '1',
        'text' => '',
        'class' => 'hotel-booking-link'
    ), $atts);
    
    if (empty($atts['room_id'])) {
        return '<span style="color:red;">Error: room_id is required</span>';
    }
    
    // ดึงภาษาปัจจุบันจาก Polylang
    $current_lang = 'th';
    if (function_exists('pll_current_language')) {
        $poly_lang = pll_current_language('slug');
        if (strpos($poly_lang, 'zh') === 0) {
            $current_lang = 'zh';
        } elseif (in_array($poly_lang, ['th', 'en', 'zh'])) {
            $current_lang = $poly_lang;
        }
    }
    
    $base_url = apply_filters('hotel_booking_base_url', 'https://diamondplazasurat.com/booking');
    $url = trailingslashit($base_url) . $current_lang . '/room_detail.php';
    
    $params = array('room_type_id' => $atts['room_id']);
    if (!empty($atts['check_in'])) $params['check_in'] = $atts['check_in'];
    if (!empty($atts['check_out'])) $params['check_out'] = $atts['check_out'];
    if (!empty($atts['adults'])) $params['adults'] = $atts['adults'];
    if (!empty($atts['children'])) $params['children'] = $atts['children'];
    if (!empty($atts['rooms'])) $params['rooms'] = $atts['rooms'];
    
    $url .= '?' . http_build_query($params);
    
    if (empty($atts['text'])) {
        $atts['text'] = $current_lang === 'th' ? 'จองเลย' : ($current_lang === 'en' ? 'Book Now' : '立即预订');
    }
    
    return '<a href="' . esc_url($url) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
}
add_shortcode('hotel_booking_link', 'hotel_booking_link_shortcode');

/**
 * Helper function สำหรับใช้ใน PHP template
 * 
 * ตัวอย่าง:
 * <?php echo get_hotel_booking_url(1, ['check_in' => '2024-01-15']); ?>
 */
if (!function_exists('get_hotel_booking_url')) {
    function get_hotel_booking_url($room_id, $params = array()) {
        if (empty($room_id)) {
            return '';
        }
        
        // ดึงภาษาปัจจุบันจาก Polylang
        $current_lang = 'th';
        if (function_exists('pll_current_language')) {
            $poly_lang = pll_current_language('slug');
            if (strpos($poly_lang, 'zh') === 0) {
                $current_lang = 'zh';
            } elseif (in_array($poly_lang, ['th', 'en', 'zh'])) {
                $current_lang = $poly_lang;
            }
        }
        
        $base_url = apply_filters('hotel_booking_base_url', 'https://diamondplazasurat.com/booking');
        $url = trailingslashit($base_url) . $current_lang . '/room_detail.php';
        
        $query_params = array('room_type_id' => $room_id);
        $query_params = array_merge($query_params, $params);
        
        return $url . '?' . http_build_query($query_params);
    }
}

/**
 * เพิ่ม CSS สำหรับปุ่ม (optional - สามารถเพิ่มใน theme CSS แทนได้)
 */
function hotel_booking_button_styles() {
    ?>
    <style>
        .hotel-booking-button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .hotel-booking-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .hotel-booking-button.btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .hotel-booking-button.btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        .hotel-booking-button.btn-outline:hover {
            background: #667eea;
            color: white;
        }
    </style>
    <?php
}
add_action('wp_head', 'hotel_booking_button_styles');
