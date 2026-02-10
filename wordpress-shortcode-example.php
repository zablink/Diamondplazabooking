<?php
/**
 * WordPress Shortcode สำหรับเชื่อมต่อกับ Booking System
 * 
 * วิธีใช้งาน:
 * 1. คัดลอกโค้ดนี้ไปใส่ใน functions.php ของ WordPress theme
 * 2. ใช้ shortcode [booking_link] ใน post หรือ page
 * 
 * ตัวอย่าง:
 * [booking_link type="detail" room_id="1" lang="th" text="จองห้องพักนี้"]
 * [booking_link type="booking" room_id="1" lang="en" check_in="2024-01-15" check_out="2024-01-17" text="Book Now"]
 */

// Shortcode สำหรับสร้างลิงก์ไปยัง Booking System
function booking_link_shortcode($atts) {
    // ตั้งค่า default values
    $atts = shortcode_atts(array(
        'type' => 'detail',        // detail, booking, search
        'room_id' => '',            // ID ของห้องพัก (required)
        'lang' => 'th',             // th, en
        'check_in' => '',           // วันที่เช็คอิน (YYYY-MM-DD)
        'check_out' => '',          // วันที่เช็คเอาท์ (YYYY-MM-DD)
        'adults' => '2',            // จำนวนผู้ใหญ่
        'children' => '0',          // จำนวนเด็ก
        'rooms' => '1',             // จำนวนห้อง
        'text' => 'จองเลย',         // ข้อความบนปุ่ม
        'class' => 'btn-book-now',  // CSS class
        'target' => '_self'          // _self, _blank
    ), $atts);
    
    // ตรวจสอบว่ามี room_id หรือไม่
    if (empty($atts['room_id'])) {
        return '<span style="color:red;">Error: room_id is required</span>';
    }
    
    // Base URL ของ Booking System
    $base_url = 'https://diamondplazasurat.com/booking';
    $lang = in_array($atts['lang'], ['th', 'en']) ? $atts['lang'] : 'th';
    
    // สร้าง URL ตาม type
    $url = '';
    $params = array();
    
    switch($atts['type']) {
        case 'detail':
            // room_detail.php?id=1&check_in=...&check_out=...
            $url = $base_url . '/' . $lang . '/room_detail.php';
            $params['id'] = $atts['room_id'];
            if (!empty($atts['check_in'])) $params['check_in'] = $atts['check_in'];
            if (!empty($atts['check_out'])) $params['check_out'] = $atts['check_out'];
            if (!empty($atts['adults'])) $params['adults'] = $atts['adults'];
            if (!empty($atts['children'])) $params['children'] = $atts['children'];
            if (!empty($atts['rooms'])) $params['rooms'] = $atts['rooms'];
            break;
            
        case 'booking':
            // booking.php?room_type_id=1&check_in=...&check_out=...
            $url = $base_url . '/' . $lang . '/booking.php';
            $params['room_type_id'] = $atts['room_id'];
            if (!empty($atts['check_in'])) $params['check_in'] = $atts['check_in'];
            if (!empty($atts['check_out'])) $params['check_out'] = $atts['check_out'];
            if (!empty($atts['adults'])) $params['adults'] = $atts['adults'];
            if (!empty($atts['children'])) $params['children'] = $atts['children'];
            if (!empty($atts['rooms'])) $params['rooms'] = $atts['rooms'];
            break;
            
        case 'search':
            // search.php?check_in=...&check_out=...&guests=...
            $url = $base_url . '/' . $lang . '/search.php';
            if (!empty($atts['check_in'])) $params['check_in'] = $atts['check_in'];
            if (!empty($atts['check_out'])) $params['check_out'] = $atts['check_out'];
            if (!empty($atts['adults'])) $params['guests'] = $atts['adults'];
            break;
            
        default:
            return '<span style="color:red;">Error: Invalid type. Use "detail", "booking", or "search"</span>';
    }
    
    // เพิ่ม query string
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    // สร้าง HTML
    $html = '<a href="' . esc_url($url) . '"';
    $html .= ' class="' . esc_attr($atts['class']) . '"';
    $html .= ' target="' . esc_attr($atts['target']) . '"';
    $html .= '>' . esc_html($atts['text']) . '</a>';
    
    return $html;
}
add_shortcode('booking_link', 'booking_link_shortcode');

/**
 * Shortcode สำหรับสร้างปุ่มจองพร้อมวันที่ (ใช้ JavaScript)
 * 
 * ตัวอย่าง:
 * [booking_button room_id="1" lang="th"]
 */
function booking_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'room_id' => '',
        'lang' => 'th',
        'text' => 'จองเลย',
        'class' => 'btn-book-now'
    ), $atts);
    
    if (empty($atts['room_id'])) {
        return '<span style="color:red;">Error: room_id is required</span>';
    }
    
    $base_url = 'https://diamondplazasurat.com/booking';
    $lang = in_array($atts['lang'], ['th', 'en']) ? $atts['lang'] : 'th';
    
    // สร้าง JavaScript สำหรับเปิด popup หรือ redirect
    $html = '<button class="' . esc_attr($atts['class']) . '" ';
    $html .= 'onclick="window.location.href=\'' . esc_js($base_url . '/' . $lang . '/room_detail.php?id=' . $atts['room_id']) . '\'"';
    $html .= '>' . esc_html($atts['text']) . '</button>';
    
    return $html;
}
add_shortcode('booking_button', 'booking_button_shortcode');

/**
 * Helper function สำหรับใช้ใน PHP template
 * 
 * ตัวอย่าง:
 * echo get_booking_url('detail', 1, 'th', ['check_in' => '2024-01-15']);
 */
function get_booking_url($type = 'detail', $room_id = '', $lang = 'th', $params = array()) {
    $base_url = 'https://diamondplazasurat.com/booking';
    $lang = in_array($lang, ['th', 'en']) ? $lang : 'th';
    
    if (empty($room_id) && $type !== 'search') {
        return '';
    }
    
    $url = '';
    $query_params = array();
    
    switch($type) {
        case 'detail':
            $url = $base_url . '/' . $lang . '/room_detail.php';
            $query_params['id'] = $room_id;
            break;
        case 'booking':
            $url = $base_url . '/' . $lang . '/booking.php';
            $query_params['room_type_id'] = $room_id;
            break;
        case 'search':
            $url = $base_url . '/' . $lang . '/search.php';
            break;
    }
    
    // รวม parameters
    $query_params = array_merge($query_params, $params);
    
    if (!empty($query_params)) {
        $url .= '?' . http_build_query($query_params);
    }
    
    return $url;
}
