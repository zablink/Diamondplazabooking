<?php
/**
 * Thank You Page - หน้าแสดงขอบคุณหลังจากจองสำเร็จ
 * แจ้งว่าส่งอีเมลยืนยันการจองไปแล้ว
 */

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

require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/hotel/Hotel.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isLoggedIn()) {
    redirect('login.php');
}

// รับ booking_id
$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    setFlashMessage(__('thank_you.booking_not_found'), 'error');
    redirect('index.php');
}

// โหลดข้อมูลการจอง
$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "SELECT b.*, 
               b.check_in as check_in_date, 
               b.check_out as check_out_date,
               b.total_price as total_amount,
               rt.room_type_name, rt.description as room_description, rt.base_price
        FROM bk_bookings b
        LEFT JOIN bk_room_types rt ON b.room_type_id = rt.room_type_id
        WHERE b.booking_id = :booking_id AND b.user_id = :user_id";

$stmt = $conn->prepare($sql);
$stmt->execute([
    'booking_id' => $booking_id,
    'user_id' => getCurrentUserId()
]);

$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    setFlashMessage(__('thank_you.booking_not_found'), 'error');
    redirect('index.php');
}

// คำนวณจำนวนคืน
$check_in = new DateTime($booking['check_in_date']);
$check_out = new DateTime($booking['check_out_date']);
$nights = $check_in->diff($check_out)->days;

$page_title = __('thank_you.title') . ' - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
?>

<style>
    .thank-you-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .success-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
        padding: 50px 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
    }
    
    .success-icon {
        font-size: 80px;
        margin-bottom: 20px;
        animation: scaleIn 0.5s ease-out;
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }
    
    .success-banner h1 {
        font-size: 36px;
        margin-bottom: 10px;
    }
    
    .success-banner p {
        font-size: 18px;
        opacity: 0.9;
    }
    
    .booking-reference {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        padding: 15px 30px;
        border-radius: 10px;
        margin-top: 20px;
        font-size: 24px;
        font-weight: bold;
        letter-spacing: 2px;
    }
    
    .email-notification {
        background: #d1ecf1;
        border: 2px solid #0c5460;
        border-radius: 10px;
        padding: 25px;
        margin: 30px 0;
        text-align: center;
    }
    
    .email-notification i {
        font-size: 48px;
        color: #0c5460;
        margin-bottom: 15px;
    }
    
    .email-notification h2 {
        color: #0c5460;
        margin-bottom: 10px;
        font-size: 24px;
    }
    
    .email-notification p {
        color: #0c5460;
        font-size: 16px;
        margin: 5px 0;
    }
    
    .email-address {
        background: white;
        padding: 10px 20px;
        border-radius: 5px;
        display: inline-block;
        margin-top: 10px;
        font-weight: bold;
        color: #0c5460;
    }
    
    .info-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 22px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .info-label {
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    
    .payment-info {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    .payment-info h3 {
        color: #856404;
        margin-top: 0;
    }
    
    .payment-info ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    
    .payment-info li {
        color: #856404;
        margin: 8px 0;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn {
        flex: 1;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }
    
    .btn-secondary:hover {
        background: #f8f9fa;
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .success-banner h1 {
            font-size: 28px;
        }
        
        .booking-reference {
            font-size: 18px;
        }
    }
</style>

<div class="thank-you-container">
    <!-- Success Banner -->
    <div class="success-banner">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1><?php _e('thank_you.success_title'); ?></h1>
        <p><?php _e('thank_you.success_message'); ?></p>
        <div class="booking-reference">
            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($booking['booking_reference']) ?>
        </div>
    </div>
    
    <!-- Email Notification -->
    <div class="email-notification">
        <i class="fas fa-envelope-circle-check"></i>
        <h2>📧 <?php _e('thank_you.email_sent_title'); ?></h2>
        <p><?php _e('thank_you.email_sent_message'); ?></p>
        <div class="email-address">
            <i class="fas fa-envelope"></i> <?= htmlspecialchars($booking['email']) ?>
        </div>
        <p style="margin-top: 15px; font-size: 14px;">
            <?php _e('thank_you.email_check_note'); ?>
        </p>
    </div>
    
    <!-- Booking Summary -->
    <div class="info-card">
        <h2 class="section-title">
            <i class="fas fa-info-circle"></i> <?php _e('thank_you.booking_summary'); ?>
        </h2>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.booking_reference'); ?></div>
                <div class="info-value"><?= htmlspecialchars($booking['booking_reference']) ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.room_type'); ?></div>
                <div class="info-value"><?= htmlspecialchars($booking['room_type_name']) ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.check_in'); ?></div>
                <div class="info-value">
                    <i class="fas fa-calendar-check"></i> 
                    <?= date('d/m/Y', strtotime($booking['check_in_date'])) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.check_out'); ?></div>
                <div class="info-value">
                    <i class="fas fa-calendar-times"></i> 
                    <?= date('d/m/Y', strtotime($booking['check_out_date'])) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.nights'); ?></div>
                <div class="info-value"><?= $nights ?> <?php _e('common.night'); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.rooms'); ?></div>
                <div class="info-value"><?= $booking['rooms_booked'] ?> <?php _e('common.rooms'); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.guests'); ?></div>
                <div class="info-value">
                    <?= $booking['adults'] ?> <?php _e('thank_you.adults'); ?>
                    <?php if ($booking['children'] > 0): ?>
                        , <?= $booking['children'] ?> <?php _e('thank_you.children'); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('thank_you.total_amount'); ?></div>
                <div class="info-value" style="color: #667eea; font-size: 20px;">
                    ฿<?= number_format($booking['total_amount'], 0) ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Information -->
    <div class="info-card">
        <div class="payment-info">
            <h3><i class="fas fa-credit-card"></i> <?php _e('thank_you.payment_info_title'); ?></h3>
            <ul>
                <li><strong><?php _e('thank_you.payment_counter'); ?></strong></li>
                <li><?php _e('thank_you.payment_qr'); ?></li>
                <li><?php _e('thank_you.payment_reference'); ?> <strong><?= htmlspecialchars($booking['booking_reference']) ?></strong> <?php _e('thank_you.payment_reference_note'); ?></li>
                <li><?php _e('thank_you.check_in_time'); ?></li>
            </ul>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="booking_confirmation.php?booking_id=<?= $booking_id ?>" class="btn btn-primary">
            <i class="fas fa-file-alt"></i> <?php _e('thank_you.view_booking_details'); ?>
        </a>
        <a href="my_bookings.php" class="btn btn-secondary">
            <i class="fas fa-list"></i> <?php _e('thank_you.view_all_bookings'); ?>
        </a>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" class="btn btn-secondary" style="display: inline-block; flex: none;">
            <i class="fas fa-home"></i> <?php _e('thank_you.back_to_home'); ?>
        </a>
    </div>
</div>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
