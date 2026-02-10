<?php
/**
 * Booking Confirmation Page
 * หน้ายืนยันการจองและกรอกข้อมูลผู้เข้าพัก
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

// ============================================
// รับและตรวจสอบ Parameters
// ============================================

// รับ room_type_id
$room_type_id = null;
$possible_params = ['room_type_id', 'id', 'room_id'];
foreach ($possible_params as $param) {
    if (isset($_GET[$param]) && !empty($_GET[$param])) {
        $room_type_id = intval($_GET[$param]);
        break;
    }
}

// รับ parameters อื่นๆ
$check_in = isset($_GET['check_in']) && !empty($_GET['check_in']) ? $_GET['check_in'] : null;
$check_out = isset($_GET['check_out']) && !empty($_GET['check_out']) ? $_GET['check_out'] : null;
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 2;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;
$rooms = isset($_GET['rooms']) ? intval($_GET['rooms']) : 1;
$add_breakfast = isset($_GET['add_breakfast']) ? intval($_GET['add_breakfast']) : 0;

// ============================================
// Validation
// ============================================
$validation_errors = [];

if (!$room_type_id) {
    $validation_errors[] = __('rooms.room_not_found');
}

if (!$check_in || empty($check_in)) {
    $validation_errors[] = __('rooms.check_in_date') . ' ' . __('messages.required_fields');
}

if (!$check_out || empty($check_out)) {
    $validation_errors[] = __('rooms.check_out_date') . ' ' . __('messages.required_fields');
}

if (!empty($validation_errors)) {
    $error_message = implode("<br>", $validation_errors);
    setFlashMessage($error_message, 'error');
    
    if ($room_type_id) {
        redirect('room_detail.php?id=' . $room_type_id);
    } else {
        redirect('index.php');
    }
}

// ============================================
// Date Validation
// ============================================
try {
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $today = new DateTime('today');
    
    if ($check_in_date < $today) {
        throw new Exception(__('rooms.check_in_date') . ' must be today or in the future');
    }
    
    if ($check_out_date <= $check_in_date) {
        throw new Exception(__('rooms.checkout_after_checkin'));
    }
    
    $nights = $check_in_date->diff($check_out_date)->days;
    
} catch (Exception $e) {
    setFlashMessage($e->getMessage(), 'error');
    redirect('room_detail.php?id=' . $room_type_id);
}

// ============================================
// Load Room Data
// ============================================
$hotel = new Hotel();
$room = $hotel->getRoomTypeById($room_type_id);

if (!$room) {
    setFlashMessage(__('booking.room_booking_not_found'), 'error');
    redirect('index.php');
}

// โหลดรูปภาพห้องพัก
$featuredImage = $hotel->getFeaturedImage($room_type_id);
$roomImages = $hotel->getRoomImages($room_type_id);

// ============================================
// Check User Login
// ============================================
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    setFlashMessage('กรุณาเข้าสู่ระบบก่อนทำการจอง', 'error');
    redirect('login.php');
}

// ดึงข้อมูล user ที่ login
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? '';
$user_first_name = $_SESSION['first_name'] ?? '';
$user_last_name = $_SESSION['last_name'] ?? '';
// ดึงเบอร์โทรจากฐานข้อมูลเพื่อ prefill (ลดโอกาส submit ไม่ผ่านแล้วผู้ใช้คิดว่าอยู่หน้าเดิม)
$user_phone = '';
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT phone FROM bk_users WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_phone = $row['phone'] ?? '';
} catch (Exception $e) {
    // ignore
}

// ============================================
// Price Calculation
// ============================================
$base_price = floatval($room['base_price']);
$breakfast_price = floatval($room['breakfast_price'] ?? 0);
$breakfast_included = intval($room['breakfast_included'] ?? 0);

// คำนวณราคาห้อง
$room_subtotal = $base_price * $nights * $rooms;

// คำนวณราคาอาหารเช้า
$breakfast_total = 0;
$total_guests = $adults + $children;

if ($breakfast_included) {
    // ถ้ารวมอาหารเช้าอยู่แล้ว
    $breakfast_note = __('booking.breakfast_included');
} else {
    // ถ้าไม่รวม ให้ลูกค้าเลือก
    if ($add_breakfast && $breakfast_price > 0) {
        $breakfast_total = $breakfast_price * $total_guests * $nights * $rooms;
        $breakfast_note = sprintf(__('rooms.add_breakfast_per_person'), number_format($breakfast_price, 0));
    } else {
        $breakfast_note = __('booking.breakfast_not_included');
    }
}

// คำนวณยอดรวมก่อนภาษี
$subtotal_before_tax = $room_subtotal + $breakfast_total;

// ภาษีและค่าบริการ (7% VAT + 10% Service Charge)
$vat_rate = 0.07;
$service_rate = 0.10;

$vat_amount = $subtotal_before_tax * $vat_rate;
$service_amount = $subtotal_before_tax * $service_rate;

// ยอดรวมทั้งหมด
$grand_total = $subtotal_before_tax + $vat_amount + $service_amount;

$page_title = __('booking.title') . ' - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="<?= getLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-header .icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .breadcrumb {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .booking-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .booking-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .room-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #667eea;
        }
        
        .room-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .room-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .price-breakdown {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }
        
        .price-row.subtotal {
            border-top: 1px solid #dee2e6;
            margin-top: 10px;
            padding-top: 15px;
        }
        
        .price-row.total {
            border-top: 2px solid #667eea;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: #e74c3c;
            margin-left: 3px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .checkbox-group:hover {
            background: #e9ecef;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0 !important;
            cursor: pointer;
            flex: 1;
        }
        
        .breakfast-info {
            padding: 12px 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .breakfast-info.included {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .info-box {
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .info-box i {
            color: #2196F3;
            margin-right: 8px;
        }
        
        @media (max-width: 992px) {
            .booking-content {
                grid-template-columns: 1fr;
            }
            
            .room-summary {
                position: static;
                order: -1;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> <?php _e('rooms.home'); ?></a>
            <span> / </span>
            <a href="room_detail.php?id=<?= $room_type_id ?>"><?php _e('confirmation.room_details'); ?></a>
            <span> / </span>
            <span><?php _e('booking.confirm_booking'); ?></span>
        </div>

        <!-- Flash Message -->
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin: 15px 0; padding: 15px; border-radius: 10px; background: <?= $flash['type'] === 'error' ? '#f8d7da' : '#d4edda' ?>; color: <?= $flash['type'] === 'error' ? '#721c24' : '#155724' ?>;">
                <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <h1><?php _e('booking.confirm_booking'); ?></h1>
            <p style="color: #666; font-size: 1.1rem;"><?php _e('confirmation.please_review'); ?></p>
        </div>
        
        <div class="booking-content">
            <!-- Booking Form -->
            <div class="booking-form">
                <h2 class="section-title">
                    <i class="fas fa-user-edit"></i>
                    ข้อมูลผู้เข้าพัก
                </h2>
                
                <form method="POST" action="process-booking.php" id="bookingForm">
                    <!-- Hidden Fields -->
                    <input type="hidden" name="room_type_id" value="<?= $room_type_id ?>">
                    <input type="hidden" name="check_in" value="<?= htmlspecialchars($check_in) ?>">
                    <input type="hidden" name="check_out" value="<?= htmlspecialchars($check_out) ?>">
                    <input type="hidden" name="adults" value="<?= $adults ?>">
                    <input type="hidden" name="children" value="<?= $children ?>">
                    <input type="hidden" name="rooms" value="<?= $rooms ?>">
                    <input type="hidden" name="nights" value="<?= $nights ?>">
                    <input type="hidden" name="total_amount" value="<?= $grand_total ?>">
                    <input type="hidden" name="add_breakfast" value="<?= $add_breakfast ?>" id="hiddenBreakfast">
                    
                    <!-- Guest Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <?php _e('booking.first_name'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="first_name" 
                                   value="<?= htmlspecialchars($user_first_name) ?>"
                                   required
                                   placeholder="<?php _e('auth.first_name'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <?php _e('booking.last_name'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   name="last_name" 
                                   value="<?= htmlspecialchars($user_last_name) ?>"
                                   required
                                   placeholder="<?php _e('auth.last_name'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <?php _e('booking.email'); ?> <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user_email) ?>"
                                   required
                                   placeholder="email@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <?php _e('booking.phone'); ?> <span class="required">*</span>
                            </label>
                            <input type="tel" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($user_phone) ?>"
                                   required
                                   placeholder="<?php _e('booking.phone_placeholder'); ?>"
                                   pattern="[0-9]{10}"
                                   maxlength="10">
                        </div>
                    </div>
                    
                    <!-- Breakfast Option (ถ้าไม่รวม) -->
                    <?php if (!$breakfast_included && $breakfast_price > 0): ?>
                        <div class="form-group">
                            <label>อาหารเช้า</label>
                            <div class="checkbox-group" onclick="toggleBreakfast()">
                                <input type="checkbox" 
                                       id="breakfastCheck" 
                                       <?= $add_breakfast ? 'checked' : '' ?>
                                       onchange="updateBreakfastPrice()">
                                <label for="breakfastCheck">
                                    <strong>เพิ่มอาหารเช้า</strong>
                                    <div style="color: #666; font-size: 13px; margin-top: 3px;">
                                        ฿<?= number_format($breakfast_price, 0) ?> ต่อคน ต่อคืน
                                        (รวม <?= $total_guests ?> คน × <?= $nights ?> คืน × <?= $rooms ?> ห้อง = ฿<span id="breakfastTotal"><?= number_format($breakfast_total, 0) ?></span>)
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="breakfast-info included">
                            <i class="fas fa-check-circle"></i>
                            <strong>รวมอาหารเช้าในราคาแล้ว</strong>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Special Requests -->
                    <div class="form-group">
                        <label>ความต้องการพิเศษ (ถ้ามี)</label>
                        <textarea name="special_requests" 
                                  placeholder="เช่น เตียงเสริม, ชั้นสูง, มุมมองทะเล, ฯลฯ"></textarea>
                    </div>
                    
                    <!-- Important Note -->
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>ข้อมูลสำคัญ:</strong><br>
                        • เวลาเช็คอิน: 14:00 น. / เช็คเอาท์: 12:00 น.<br>
                        • กรุณามาถึงก่อนเวลา 18:00 น. หากมาถึงช้ากรุณาแจ้งล่วงหน้า<br>
                        • ราคารวมภาษีและค่าบริการแล้ว
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit" id="btnSubmit">
                        <i class="fas fa-arrow-right"></i> <?php _e('booking.proceed_to_payment'); ?>
                    </button>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="room_detail.php?id=<?= $room_type_id ?>" class="btn-back">
                            <i class="fas fa-arrow-left"></i> กลับไปแก้ไข
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Room Summary -->
            <div class="room-summary">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-list"></i>
                    <?php _e('booking.booking_summary'); ?>
                </h3>
                
                <!-- Room Image -->
                <?php if ($featuredImage): ?>
                    <img src="<?= htmlspecialchars($featuredImage['image_url']) ?>" 
                         alt="<?= htmlspecialchars($room['room_type_name']) ?>"
                         class="room-image"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'200\'%3E%3Crect fill=\'%23f0f0f0\' width=\'400\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23999\' text-anchor=\'middle\' dy=\'.3em\' font-family=\'sans-serif\' font-size=\'18\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                <?php elseif (!empty($roomImages)): ?>
                    <img src="<?= htmlspecialchars($roomImages[0]['image_url']) ?>" 
                         alt="<?= htmlspecialchars($room['room_type_name']) ?>"
                         class="room-image"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'200\'%3E%3Crect fill=\'%23f0f0f0\' width=\'400\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' fill=\'%23999\' text-anchor=\'middle\' dy=\'.3em\' font-family=\'sans-serif\' font-size=\'18\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                <?php else: ?>
                    <div class="room-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                        <i class="fas fa-image" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>
                
                <div class="room-name"><?= htmlspecialchars($room['room_type_name']) ?></div>
                
                <!-- Booking Details -->
                <div class="detail-row">
                    <span class="detail-label">เช็คอิน</span>
                    <span class="detail-value"><?= formatDate($check_in) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">เช็คเอาท์</span>
                    <span class="detail-value"><?= formatDate($check_out) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">จำนวนคืน</span>
                    <span class="detail-value"><?= $nights ?> คืน</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">จำนวนผู้เข้าพัก</span>
                    <span class="detail-value"><?= $adults ?> ผู้ใหญ่<?= $children > 0 ? ', ' . $children . ' เด็ก' : '' ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">จำนวนห้อง</span>
                    <span class="detail-value"><?= $rooms ?> ห้อง</span>
                </div>
                
                <!-- Price Breakdown -->
                <div class="price-breakdown">
                    <h4 style="margin-bottom: 15px; color: #333;">รายละเอียดราคา</h4>
                    
                    <div class="price-row">
                        <span>ค่าห้องพัก (<?= $nights ?> คืน × <?= $rooms ?> ห้อง)</span>
                        <span>฿<?= number_format($room_subtotal, 0) ?></span>
                    </div>
                    
                    <?php if (!$breakfast_included && $add_breakfast && $breakfast_price > 0): ?>
                    <div class="price-row" id="breakfastRow">
                        <span>อาหารเช้า (<?= $total_guests ?> คน × <?= $nights ?> คืน)</span>
                        <span id="breakfastAmount">฿<?= number_format($breakfast_total, 0) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="price-row subtotal">
                        <span>รวมย่อย</span>
                        <span id="subtotalAmount">฿<?= number_format($subtotal_before_tax, 0) ?></span>
                    </div>
                    
                    <div class="price-row">
                        <span>ภาษี (7%)</span>
                        <span id="vatAmount">฿<?= number_format($vat_amount, 0) ?></span>
                    </div>
                    
                    <div class="price-row">
                        <span>ค่าบริการ (10%)</span>
                        <span id="serviceAmount">฿<?= number_format($service_amount, 0) ?></span>
                    </div>
                    
                    <div class="price-row total">
                        <span>ยอดรวมทั้งหมด</span>
                        <span id="grandTotal">฿<?= number_format($grand_total, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ข้อมูลสำหรับคำนวณ
        const basePrice = <?= $base_price ?>;
        const breakfastPrice = <?= $breakfast_price ?>;
        const nights = <?= $nights ?>;
        const rooms = <?= $rooms ?>;
        const totalGuests = <?= $total_guests ?>;
        const vatRate = <?= $vat_rate ?>;
        const serviceRate = <?= $service_rate ?>;
        const breakfastIncluded = <?= $breakfast_included ? 'true' : 'false' ?>;
        
        function toggleBreakfast() {
            const checkbox = document.getElementById('breakfastCheck');
            checkbox.checked = !checkbox.checked;
            updateBreakfastPrice();
        }
        
        function updateBreakfastPrice() {
            if (breakfastIncluded) return;
            
            const checkbox = document.getElementById('breakfastCheck');
            const hiddenInput = document.getElementById('hiddenBreakfast');
            
            // อัพเดต hidden input
            hiddenInput.value = checkbox.checked ? '1' : '0';
            
            // คำนวณราคาใหม่
            const roomSubtotal = basePrice * nights * rooms;
            let breakfastTotal = 0;
            
            if (checkbox.checked && breakfastPrice > 0) {
                breakfastTotal = breakfastPrice * totalGuests * nights * rooms;
            }
            
            const subtotalBeforeTax = roomSubtotal + breakfastTotal;
            const vatAmount = subtotalBeforeTax * vatRate;
            const serviceAmount = subtotalBeforeTax * serviceRate;
            const grandTotal = subtotalBeforeTax + vatAmount + serviceAmount;
            
            // อัพเดตการแสดงผล
            document.getElementById('breakfastTotal').textContent = formatNumber(breakfastTotal);
            
            const breakfastRow = document.getElementById('breakfastRow');
            if (breakfastRow) {
                breakfastRow.style.display = checkbox.checked ? 'flex' : 'none';
                document.getElementById('breakfastAmount').textContent = '฿' + formatNumber(breakfastTotal);
            }
            
            document.getElementById('subtotalAmount').textContent = '฿' + formatNumber(subtotalBeforeTax);
            document.getElementById('vatAmount').textContent = '฿' + formatNumber(vatAmount);
            document.getElementById('serviceAmount').textContent = '฿' + formatNumber(serviceAmount);
            document.getElementById('grandTotal').textContent = '฿' + formatNumber(grandTotal);
            
            // อัพเดต hidden total amount
            document.querySelector('input[name="total_amount"]').value = grandTotal;
        }
        
        function formatNumber(num) {
            return Math.round(num).toLocaleString('th-TH');
        }
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="phone"]').value;
            
            // Validate phone number
            if (!/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('<?php echo addslashes(__('booking.phone_invalid')); ?>');
                return false;
            }
            
            // Disable button
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo addslashes(__('booking.processing')); ?>';
        });
    </script>
</body>
</html>