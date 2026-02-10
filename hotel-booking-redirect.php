<?php
/*
Plugin Name: Hotel Booking Redirect
Description: Redirect WordPress posts/pages to booking system based on room_type_id. Works with Polylang.
Version: 1.0.0
Author: Over
*/

/**
 * WordPress Plugin สำหรับ redirect post/page ไปยัง Booking System
 * รองรับ Polylang และภาษาจีน
 * 
 * วิธีใช้งาน:
 * 1. เปิด post หรือ page ที่ต้องการ redirect
 * 2. กรอก Room Type ID ใน meta box "Hotel Booking Redirect"
 * 3. บันทึก post/page
 * 4. เมื่อเข้าหน้านั้นจะ redirect ไปยัง booking system อัตโนมัติ
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('ABSPATH')) {
    exit;
}

/**
 * เพิ่ม Meta Box สำหรับกรอก room_type_id
 */
function hotel_booking_redirect_add_meta_box() {
    // เพิ่ม meta box ให้กับ post และ page
    $post_types = apply_filters('hotel_booking_redirect_post_types', ['post', 'page']);
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'hotel_booking_redirect_meta_box',
            'Hotel Booking Redirect',
            'hotel_booking_redirect_meta_box_callback',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'hotel_booking_redirect_add_meta_box');

/**
 * Meta Box Callback - แสดงฟอร์มสำหรับกรอก room_type_id
 */
function hotel_booking_redirect_meta_box_callback($post) {
    // ใช้ nonce เพื่อความปลอดภัย
    wp_nonce_field('hotel_booking_redirect_save_meta_box', 'hotel_booking_redirect_meta_box_nonce');
    
    // ดึงค่า room_type_id ที่บันทึกไว้
    $room_type_id = get_post_meta($post->ID, '_hotel_booking_room_type_id', true);
    $enable_redirect = get_post_meta($post->ID, '_hotel_booking_enable_redirect', true);
    
    ?>
    <div style="padding: 10px 0;">
        <label for="hotel_booking_enable_redirect" style="display: block; margin-bottom: 10px;">
            <input type="checkbox" 
                   id="hotel_booking_enable_redirect" 
                   name="hotel_booking_enable_redirect" 
                   value="1" 
                   <?php checked($enable_redirect, '1'); ?>>
            <strong>Enable Redirect to Booking</strong>
        </label>
        
        <label for="hotel_booking_room_type_id" style="display: block; margin-top: 10px; margin-bottom: 5px;">
            <strong>Room Type ID:</strong>
        </label>
        <input type="number" 
               id="hotel_booking_room_type_id" 
               name="hotel_booking_room_type_id" 
               value="<?php echo esc_attr($room_type_id); ?>" 
               placeholder="e.g., 1, 2, 3"
               style="width: 100%; padding: 5px;"
               min="1">
        
        <p style="margin-top: 10px; font-size: 12px; color: #666;">
            <strong>Optional Parameters:</strong><br>
            (Leave empty to use defaults)
        </p>
        
        <label for="hotel_booking_check_in" style="display: block; margin-top: 8px; margin-bottom: 5px; font-size: 12px;">
            Check-in Date (YYYY-MM-DD):
        </label>
        <input type="date" 
               id="hotel_booking_check_in" 
               name="hotel_booking_check_in" 
               value="<?php echo esc_attr(get_post_meta($post->ID, '_hotel_booking_check_in', true)); ?>" 
               style="width: 100%; padding: 5px; font-size: 12px;">
        
        <label for="hotel_booking_check_out" style="display: block; margin-top: 8px; margin-bottom: 5px; font-size: 12px;">
            Check-out Date (YYYY-MM-DD):
        </label>
        <input type="date" 
               id="hotel_booking_check_out" 
               name="hotel_booking_check_out" 
               value="<?php echo esc_attr(get_post_meta($post->ID, '_hotel_booking_check_out', true)); ?>" 
               style="width: 100%; padding: 5px; font-size: 12px;">
        
        <label for="hotel_booking_adults" style="display: block; margin-top: 8px; margin-bottom: 5px; font-size: 12px;">
            Adults (default: 2):
        </label>
        <input type="number" 
               id="hotel_booking_adults" 
               name="hotel_booking_adults" 
               value="<?php echo esc_attr(get_post_meta($post->ID, '_hotel_booking_adults', true) ?: '2'); ?>" 
               min="1"
               style="width: 100%; padding: 5px; font-size: 12px;">
        
        <label for="hotel_booking_children" style="display: block; margin-top: 8px; margin-bottom: 5px; font-size: 12px;">
            Children (default: 0):
        </label>
        <input type="number" 
               id="hotel_booking_children" 
               name="hotel_booking_children" 
               value="<?php echo esc_attr(get_post_meta($post->ID, '_hotel_booking_children', true) ?: '0'); ?>" 
               min="0"
               style="width: 100%; padding: 5px; font-size: 12px;">
        
        <label for="hotel_booking_rooms" style="display: block; margin-top: 8px; margin-bottom: 5px; font-size: 12px;">
            Rooms (default: 1):
        </label>
        <input type="number" 
               id="hotel_booking_rooms" 
               name="hotel_booking_rooms" 
               value="<?php echo esc_attr(get_post_meta($post->ID, '_hotel_booking_rooms', true) ?: '1'); ?>" 
               min="1"
               style="width: 100%; padding: 5px; font-size: 12px;">
        
        <p style="margin-top: 15px; font-size: 11px; color: #999; font-style: italic;">
            เมื่อเปิดหน้านี้ จะ redirect ไปยัง booking system อัตโนมัติ
        </p>
    </div>
    <?php
}

/**
 * บันทึกข้อมูลจาก Meta Box
 */
function hotel_booking_redirect_save_meta_box($post_id) {
    // ตรวจสอบ nonce
    if (!isset($_POST['hotel_booking_redirect_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['hotel_booking_redirect_meta_box_nonce'], 'hotel_booking_redirect_save_meta_box')) {
        return;
    }
    
    // ตรวจสอบ autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // ตรวจสอบ permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // บันทึก enable_redirect
    if (isset($_POST['hotel_booking_enable_redirect'])) {
        update_post_meta($post_id, '_hotel_booking_enable_redirect', '1');
    } else {
        delete_post_meta($post_id, '_hotel_booking_enable_redirect');
    }
    
    // บันทึก room_type_id
    if (isset($_POST['hotel_booking_room_type_id']) && !empty($_POST['hotel_booking_room_type_id'])) {
        update_post_meta($post_id, '_hotel_booking_room_type_id', sanitize_text_field($_POST['hotel_booking_room_type_id']));
    } else {
        delete_post_meta($post_id, '_hotel_booking_room_type_id');
    }
    
    // บันทึก optional parameters
    $optional_fields = ['check_in', 'check_out', 'adults', 'children', 'rooms'];
    foreach ($optional_fields as $field) {
        $meta_key = '_hotel_booking_' . $field;
        if (isset($_POST['hotel_booking_' . $field]) && !empty($_POST['hotel_booking_' . $field])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST['hotel_booking_' . $field]));
        } else {
            delete_post_meta($post_id, $meta_key);
        }
    }
}
add_action('save_post', 'hotel_booking_redirect_save_meta_box');

/**
 * ฟังก์ชันสำหรับดึงภาษาปัจจุบัน (ใช้ logic เดียวกับ shortcode plugin)
 */
function hotel_booking_redirect_get_current_lang() {
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
    
    return $current_lang;
}

/**
 * สร้าง URL ไปยัง booking system (ใช้ logic เดียวกับ shortcode plugin)
 */
function hotel_booking_redirect_build_url($room_type_id, $params = array()) {
    // Base URL ของ Booking System
    $base_url = apply_filters('hotel_booking_base_url', 'https://diamondplazasurat.com/booking');
    
    // ดึงภาษาปัจจุบัน
    $current_lang = hotel_booking_redirect_get_current_lang();
    
    // สร้าง URL ไปยัง room_detail.php
    $url = trailingslashit($base_url) . $current_lang . '/room_detail.php';
    
    // สร้าง query parameters
    $query_params = array('room_type_id' => $room_type_id);
    
    // เพิ่ม optional parameters
    if (!empty($params['check_in'])) {
        $query_params['check_in'] = $params['check_in'];
    }
    if (!empty($params['check_out'])) {
        $query_params['check_out'] = $params['check_out'];
    }
    if (!empty($params['adults']) && $params['adults'] != '2') {
        $query_params['adults'] = $params['adults'];
    }
    if (!empty($params['children']) && $params['children'] != '0') {
        $query_params['children'] = $params['children'];
    }
    if (!empty($params['rooms']) && $params['rooms'] != '1') {
        $query_params['rooms'] = $params['rooms'];
    }
    
    // เพิ่ม query string
    if (!empty($query_params)) {
        $url .= '?' . http_build_query($query_params);
    }
    
    return $url;
}

/**
 * Redirect ไปยัง booking system เมื่อเข้าหน้า post/page ที่มี room_type_id
 */
function hotel_booking_redirect_template_redirect() {
    // ตรวจสอบว่าเป็น single post หรือ page
    if (!is_singular()) {
        return;
    }
    
    global $post;
    
    // ตรวจสอบว่ามีการเปิดใช้งาน redirect หรือไม่
    $enable_redirect = get_post_meta($post->ID, '_hotel_booking_enable_redirect', true);
    if ($enable_redirect !== '1') {
        return;
    }
    
    // ดึง room_type_id
    $room_type_id = get_post_meta($post->ID, '_hotel_booking_room_type_id', true);
    if (empty($room_type_id)) {
        return;
    }
    
    // ดึง optional parameters
    $params = array();
    $check_in = get_post_meta($post->ID, '_hotel_booking_check_in', true);
    $check_out = get_post_meta($post->ID, '_hotel_booking_check_out', true);
    $adults = get_post_meta($post->ID, '_hotel_booking_adults', true);
    $children = get_post_meta($post->ID, '_hotel_booking_children', true);
    $rooms = get_post_meta($post->ID, '_hotel_booking_rooms', true);
    
    if (!empty($check_in)) {
        $params['check_in'] = $check_in;
    }
    if (!empty($check_out)) {
        $params['check_out'] = $check_out;
    }
    if (!empty($adults)) {
        $params['adults'] = $adults;
    }
    if (!empty($children)) {
        $params['children'] = $children;
    }
    if (!empty($rooms)) {
        $params['rooms'] = $rooms;
    }
    
    // สร้าง URL
    $redirect_url = hotel_booking_redirect_build_url($room_type_id, $params);
    
    // Redirect (301 = Permanent Redirect)
    wp_redirect($redirect_url, 301);
    exit;
}
add_action('template_redirect', 'hotel_booking_redirect_template_redirect');

/**
 * เพิ่ม admin notice เมื่อบันทึก post/page ที่มี room_type_id
 */
function hotel_booking_redirect_admin_notices() {
    global $post;
    
    if (!$post || !is_admin()) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['post', 'page'])) {
        return;
    }
    
    $enable_redirect = get_post_meta($post->ID, '_hotel_booking_enable_redirect', true);
    $room_type_id = get_post_meta($post->ID, '_hotel_booking_room_type_id', true);
    
    if ($enable_redirect === '1' && !empty($room_type_id)) {
        $redirect_url = hotel_booking_redirect_build_url($room_type_id);
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>Hotel Booking Redirect:</strong> 
                หน้านี้จะ redirect ไปยัง 
                <a href="<?php echo esc_url($redirect_url); ?>" target="_blank">
                    <?php echo esc_html($redirect_url); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'hotel_booking_redirect_admin_notices');
