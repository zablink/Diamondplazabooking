<?php
/**
 * Helper Functions for Hotel Booking System
 */

/**
 * Redirect to a page
 */
function redirect($url) {
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
