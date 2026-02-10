<?php
/**
 * Booking Confirmation Page
 * หน้ายืนยันการจองสำเร็จ
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
    setFlashMessage(__('confirmation.booking_not_found'), 'error');
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
    setFlashMessage('ไม่พบข้อมูลการจอง', 'error');
    redirect('index.php');
}

// คำนวณจำนวนคืน
$check_in = new DateTime($booking['check_in_date']);
$check_out = new DateTime($booking['check_out_date']);
$nights = $check_in->diff($check_out)->days;

$page_title = __('confirmation.title') . ' - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
?>

<style>
    .confirmation-container {
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
    
    .confirmation-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
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
    
    .status-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .status-confirmed {
        background: #d4edda;
        color: #155724;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .important-info {
        background: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    .important-info h3 {
        color: #2e7d32;
        margin-bottom: 15px;
        font-size: 18px;
    }
    
    .important-info ul {
        list-style: none;
        padding: 0;
    }
    
    .important-info li {
        padding: 8px 0;
        color: #1b5e20;
    }
    
    .important-info li i {
        margin-right: 10px;
        color: #4caf50;
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
    
    .print-btn {
        display: inline-block;
        padding: 10px 20px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        transition: all 0.3s;
    }
    
    .print-btn:hover {
        background: #667eea;
        color: white;
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
    
    @media print {
        .action-buttons,
        .print-btn,
        header,
        footer {
            display: none !important;
        }
    }
</style>

<div class="confirmation-container">
    <!-- Success Banner -->
    <div class="success-banner">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>จองสำเร็จ!</h1>
        <p>ขอบคุณที่เลือกใช้บริการของเรา</p>
        <div class="booking-reference">
            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($booking['booking_reference']) ?>
        </div>
    </div>
    
    <!-- Booking Details -->
    <div class="confirmation-card">
        <h2 class="section-title">
            <i class="fas fa-info-circle"></i> <?php _e('confirmation.booking_details'); ?>
        </h2>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><?php _e('confirmation.booking_reference'); ?></div>
                <div class="info-value"><?= htmlspecialchars($booking['booking_reference']) ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('confirmation.status'); ?></div>
                <div class="info-value">
                    <?php if ($booking['booking_status'] === 'confirmed'): ?>
                        <span class="status-badge status-confirmed">
                            <i class="fas fa-check"></i> <?php _e('confirmation.confirmed'); ?>
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-pending">
                            <i class="fas fa-clock"></i> <?php _e('confirmation.pending'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Room Details -->
    <div class="confirmation-card">
        <h2 class="section-title">
            <i class="fas fa-bed"></i> ข้อมูลห้องพัก
        </h2>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">ประเภทห้อง</div>
                <div class="info-value"><?= htmlspecialchars($booking['room_type_name']) ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">จำนวนห้อง</div>
                <div class="info-value"><?= $booking['rooms_booked'] ?> ห้อง</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">วันเช็คอิน</div>
                <div class="info-value">
                    <i class="fas fa-calendar-check"></i> 
                    <?= formatThaiDate($booking['check_in_date']) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">วันเช็คเอาท์</div>
                <div class="info-value">
                    <i class="fas fa-calendar-times"></i> 
                    <?= formatThaiDate($booking['check_out_date']) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">จำนวนคืน</div>
                <div class="info-value"><?= $nights ?> คืน</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">ผู้เข้าพัก</div>
                <div class="info-value">
                    <?= $booking['adults'] ?> ผู้ใหญ่
                    <?php if ($booking['children'] > 0): ?>
                        , <?= $booking['children'] ?> เด็ก
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Guest Details -->
    <div class="confirmation-card">
        <h2 class="section-title">
            <i class="fas fa-user"></i> <?php _e('confirmation.guest_info'); ?>
        </h2>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><?php _e('auth.first_name'); ?> - <?php _e('auth.last_name'); ?></div>
                <div class="info-value">
                    <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('auth.email'); ?></div>
                <div class="info-value"><?= htmlspecialchars($booking['email']) ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><?php _e('auth.phone'); ?></div>
                <div class="info-value"><?= htmlspecialchars($booking['phone']) ?></div>
            </div>
            
            <?php if (!empty($booking['special_requests'])): ?>
            <div class="info-item" style="grid-column: 1 / -1;">
                <div class="info-label"><?php _e('booking.special_requests'); ?></div>
                <div class="info-value"><?= htmlspecialchars($booking['special_requests']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Payment Summary -->
    <div class="confirmation-card">
        <h2 class="section-title">
            <i class="fas fa-receipt"></i> สรุปการชำระเงิน
        </h2>
        
        <div style="font-size: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span>ค่าห้องพัก</span>
                <strong>฿<?= number_format($booking['room_price'], 0) ?></strong>
            </div>
            
            <?php if ($booking['breakfast_price'] > 0): ?>
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span>ค่าอาหารเช้า</span>
                <strong>฿<?= number_format($booking['breakfast_price'], 0) ?></strong>
            </div>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span>ภาษีมูลค่าเพิ่ม</span>
                <strong>฿<?= number_format($booking['tax_amount'], 0) ?></strong>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span>ค่าบริการ</span>
                <strong>฿<?= number_format($booking['service_charge'], 0) ?></strong>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0; font-size: 24px; color: #667eea;">
                <strong>ยอดรวมทั้งหมด</strong>
                <strong>฿<?= number_format($booking['total_amount'], 0) ?></strong>
            </div>
        </div>
    </div>
    
    <!-- Important Information -->
    <div class="important-info">
        <h3><i class="fas fa-exclamation-circle"></i> <?php _e('confirmation.important_info'); ?></h3>
        <ul>
            <li>
                <i class="fas fa-check"></i>
                <?php printf(__('confirmation.confirmation_sent_to'), htmlspecialchars($booking['email'])); ?>
            </li>
            <li>
                <i class="fas fa-check"></i>
                <?php _e('confirmation.present_reference'); ?> <strong><?= htmlspecialchars($booking['booking_reference']) ?></strong> <?php _e('confirmation.at_checkin'); ?>
            </li>
            <li>
                <i class="fas fa-check"></i>
                <?php _e('confirmation.check_in_time'); ?>
            </li>
            <li>
                <i class="fas fa-check"></i>
                <?php _e('confirmation.cancel_change_info'); ?>
            </li>
        </ul>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="my_bookings.php" class="btn btn-primary">
            <i class="fas fa-list"></i> <?php _e('confirmation.view_all_bookings'); ?>
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> <?php _e('confirmation.back_to_home'); ?>
        </a>
    </div>
    
    <div style="text-align: center;">
        <a href="#" onclick="window.print(); return false;" class="print-btn">
            <i class="fas fa-print"></i> <?php _e('confirmation.print_confirmation'); ?>
        </a>
    </div>
</div>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
