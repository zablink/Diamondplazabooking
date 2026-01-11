<?php
// config/config.php


// ********************
// Session Configuration was init..ed from includes/init.php before call this file
// ********************

// Configuration File for Hotel Booking System

    
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'diamondp_surat');
define('DB_USER', 'diamondp_surat');
define('DB_PASS', '3Z3S4.xhp-');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'Booking System : Diamond Plaza Surat');
define('SITE_URL', 'https://diamondplazasurat.com/booking');
define('BASE_PATH', dirname(__DIR__));

// Single Hotel Mode - กำหนด ID โรงแรมที่ใช้งาน
define('SINGLE_HOTEL_MODE', true);
define('HOTEL_ID', 1); // เปลี่ยนเป็น ID โรงแรมของคุณ
define('HOTEL_NAME', 'Diamond Plaza Surat'); 



// Social Login Configuration
// Google OAuth 2.0
define('GOOGLE_CLIENT_ID', '390968642886-a2vq1gdpmli6jukvbk24n1q4uejjvlpg.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');

define('GOOGLE_LOGOUT_REDIRECT', true);

// Facebook Login
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET');
define('FACEBOOK_REDIRECT_URI', SITE_URL . '/auth/facebook-callback.php');

// Apple Sign In (Optional)
define('APPLE_CLIENT_ID', 'YOUR_APPLE_CLIENT_ID');
define('APPLE_TEAM_ID', 'YOUR_APPLE_TEAM_ID');
define('APPLE_KEY_ID', 'YOUR_APPLE_KEY_ID');
define('APPLE_REDIRECT_URI', SITE_URL . '/auth/apple-callback.php');

// Pagination
define('ITEMS_PER_PAGE', 12);

// Upload Configuration
define('UPLOAD_PATH', BASE_PATH . '/images/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Email Configuration (สำหรับส่งอีเมล์ยืนยันการจอง)
define('SMTP_HOST', 'mail.diamondplazasurat.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'booking@diamondplazasurat.com');
define('SMTP_PASS', 'c8T2WTvp2kQ7qbymsmZ7');
define('SMTP_FROM', 'booking@diamondplazasurat.com');
define('SMTP_FROM_NAME', 'Booking system : ' . HOTEL_NAME);

// Payment Gateway Configuration
define('PAYMENT_GATEWAY', 'omise'); // omise, stripe, paypal
define('PAYMENT_PUBLIC_KEY', 'pkey_test_xxxxx');
define('PAYMENT_SECRET_KEY', 'skey_test_xxxxx');

