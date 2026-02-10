<?php
/**
 * Booking Page - สรุปการจองและกรอกข้อมูลผู้จอง
 * FIXED: เพิ่ม debug และจัดการกรณีข้อมูลไม่ครบ
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

// ⭐ DEBUG: Log ข้อมูลที่ได้รับ
error_log("=== BOOKING.PHP DEBUG ===");
error_log("GET params: " . print_r($_GET, true));
error_log("room_type_id: " . ($_GET['room_type_id'] ?? 'NOT SET'));
error_log("check_in: " . ($_GET['check_in'] ?? 'NOT SET'));
error_log("check_out: " . ($_GET['check_out'] ?? 'NOT SET'));

// ตรวจสอบว่า login แล้วหรือยัง
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    setFlashMessage('กรุณาเข้าสู่ระบบก่อนทำการจอง', 'error');
    redirect('login.php');
}

// รับข้อมูลจาก GET parameters
$room_type_id = isset($_GET['room_type_id']) && !empty($_GET['room_type_id']) ? $_GET['room_type_id'] : null;
$check_in = isset($_GET['check_in']) && !empty($_GET['check_in']) ? $_GET['check_in'] : null;
$check_out = isset($_GET['check_out']) && !empty($_GET['check_out']) ? $_GET['check_out'] : null;
$adults = isset($_GET['adults']) && !empty($_GET['adults']) ? intval($_GET['adults']) : 2;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;
$rooms = isset($_GET['rooms']) && !empty($_GET['rooms']) ? intval($_GET['rooms']) : 1;

// ⭐ Validation แบบละเอียด
$validation_errors = [];

if (!$room_type_id) {
    $validation_errors[] = 'ไม่พบข้อมูลห้องพัก (room_type_id)';
    error_log("ERROR: room_type_id is missing or empty");
}

if (!$check_in) {
    $validation_errors[] = 'กรุณาเลือกวันเช็คอิน';
    error_log("ERROR: check_in is missing or empty");
}

if (!$check_out) {
    $validation_errors[] = 'กรุณาเลือกวันเช็คเอาท์';
    error_log("ERROR: check_out is missing or empty");
}

// ถ้ามี error ให้กลับไปหน้า room_detail พร้อมแสดง error
if (!empty($validation_errors)) {
    $error_message = implode(', ', $validation_errors);
    setFlashMessage($error_message, 'error');
    
    // ⭐ กลับไปหน้า room_detail พร้อมข้อมูลเดิม (ถ้ามี room_type_id)
    if ($room_type_id) {
        $redirect_url = 'room_detail.php?id=' . $room_type_id;
        if ($check_in) $redirect_url .= '&check_in=' . urlencode($check_in);
        if ($check_out) $redirect_url .= '&check_out=' . urlencode($check_out);
        if ($adults) $redirect_url .= '&adults=' . $adults;
        if ($children) $redirect_url .= '&children=' . $children;
        if ($rooms) $redirect_url .= '&rooms=' . $rooms;
        
        error_log("Redirecting to: " . $redirect_url);
        redirect($redirect_url);
    } else {
        error_log("Redirecting to: index.php (no room_type_id)");
        redirect('index.php');
    }
}

// ตรวจสอบวันที่
try {
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($check_in_date < $today) {
        setFlashMessage('ไม่สามารถจองย้อนหลังได้', 'error');
        redirect('room_detail.php?id=' . $room_type_id);
    }
    
    if ($check_out_date <= $check_in_date) {
        setFlashMessage('วันเช็คเอาท์ต้องมาหลังวันเช็คอิน', 'error');
        redirect('room_detail.php?id=' . $room_type_id);
    }
    
    $nights = $check_in_date->diff($check_out_date)->days;
    
} catch (Exception $e) {
    error_log("Date parsing error: " . $e->getMessage());
    setFlashMessage('รูปแบบวันที่ไม่ถูกต้อง', 'error');
    redirect('room_detail.php?id=' . $room_type_id);
}

// โหลดข้อมูลห้องพัก
$hotel = new Hotel();
$room = $hotel->getRoomTypeById($room_type_id);

if (!$room) {
    error_log("Room not found: " . $room_type_id);
    setFlashMessage('ไม่พบข้อมูลห้องพัก', 'error');
    redirect('index.php');
}

// ตรวจสอบห้องว่าง
$availability = $hotel->checkRoomAvailability($room_type_id, $check_in, $check_out, $rooms);
if (!$availability['available']) {
    error_log("Room not available: " . print_r($availability, true));
    setFlashMessage('ห้องพักเต็มในช่วงเวลาที่เลือก', 'error');
    redirect('room_detail.php?id=' . $room_type_id);
}

// คำนวณราคา
$base_price = floatval($room['base_price']);
$room_total = $base_price * $nights * $rooms;

// อาหารเช้า
$breakfast_included = intval($room['breakfast_included']);
$breakfast_price = floatval($room['breakfast_price']);
$add_breakfast = isset($_GET['add_breakfast']) ? intval($_GET['add_breakfast']) : 0;

$breakfast_total = 0;
if ($add_breakfast && !$breakfast_included) {
    $total_guests = $adults + $children;
    $breakfast_total = $breakfast_price * $total_guests * $nights * $rooms;
}

// ภาษีและค่าบริการ
$subtotal = $room_total + $breakfast_total;
$vat_rate = 7; // 7%
$service_rate = 10; // 10%

$vat_amount = $subtotal * ($vat_rate / 100);
$service_amount = $subtotal * ($service_rate / 100);
$grand_total = $subtotal + $vat_amount + $service_amount;

// จัดการ form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $guest_data = [
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'special_requests' => sanitize($_POST['special_requests'] ?? '')
    ];
    
    // Validation
    if (empty($guest_data['first_name']) || empty($guest_data['last_name']) || 
        empty($guest_data['email']) || empty($guest_data['phone'])) {
        setFlashMessage('กรุณากรอกข้อมูลให้ครบถ้วน', 'error');
    } else {
        // เก็บข้อมูลใน session เพื่อส่งไปหน้าชำระเงิน
        $_SESSION['booking_data'] = [
            'room_type_id' => $room_type_id,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'adults' => $adults,
            'children' => $children,
            'rooms' => $rooms,
            'nights' => $nights,
            'add_breakfast' => $add_breakfast,
            'guest_data' => $guest_data,
            'pricing' => [
                'base_price' => $base_price,
                'room_total' => $room_total,
                'breakfast_total' => $breakfast_total,
                'subtotal' => $subtotal,
                'vat_amount' => $vat_amount,
                'service_amount' => $service_amount,
                'grand_total' => $grand_total
            ]
        ];
        
        error_log("Booking data saved to session, redirecting to payment.php");
        
        // ไปหน้าชำระเงิน
        redirect('payment.php');
    }
}

// ข้อมูลผู้ใช้ปัจจุบัน
$current_user = [
    'first_name' => $_SESSION['first_name'] ?? '',
    'last_name' => $_SESSION['last_name'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'phone' => $_SESSION['phone'] ?? ''
];

$page_title = 'สรุปการจอง - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
?>

<style>
    .booking-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .booking-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
    }
    
    .booking-header h1 {
        margin: 0 0 10px 0;
        font-size: 2rem;
    }
    
    .booking-header p {
        margin: 0;
        opacity: 0.9;
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
    
    .booking-summary {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: fit-content;
        position: sticky;
        top: 20px;
    }
    
    .section-title {
        font-size: 1.3rem;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
        color: #333;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
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
        resize: vertical;
        min-height: 100px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .summary-item.highlight {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        border: 2px solid #667eea;
    }
    
    .summary-item strong {
        font-weight: 600;
    }
    
    .room-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .room-info h3 {
        margin: 0 0 10px 0;
        color: #667eea;
    }
    
    .booking-details {
        display: grid;
        gap: 8px;
        font-size: 14px;
        color: #666;
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
        margin-top: 20px;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-error {
        background: #fee;
        color: #c00;
        border: 1px solid #fcc;
    }
    
    .alert-success {
        background: #efe;
        color: #060;
        border: 1px solid #cfc;
    }
    
    @media (max-width: 992px) {
        .booking-content {
            grid-template-columns: 1fr;
        }
        
        .booking-summary {
            position: static;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="booking-container">
    <div class="booking-header">
        <h1><i class="fas fa-calendar-check"></i> สรุปการจอง</h1>
        <p>กรุณาตรวจสอบข้อมูลและกรอกรายละเอียดผู้เข้าพัก</p>
    </div>
    
    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] == 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
    <?php endif; ?>
    
    <div class="booking-content">
        <!-- Booking Form -->
        <div class="booking-form">
            <h2 class="section-title">
                <i class="fas fa-user"></i> ข้อมูลผู้เข้าพัก
            </h2>
            
            <form method="POST" id="guestForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            ชื่อ <span style="color: red;">*</span>
                        </label>
                        <input type="text" 
                               name="first_name" 
                               value="<?= htmlspecialchars($current_user['first_name']) ?>"
                               required
                               placeholder="กรอกชื่อ">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            นามสกุล <span style="color: red;">*</span>
                        </label>
                        <input type="text" 
                               name="last_name" 
                               value="<?= htmlspecialchars($current_user['last_name']) ?>"
                               required
                               placeholder="กรอกนามสกุล">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            อีเมล <span style="color: red;">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               value="<?= htmlspecialchars($current_user['email']) ?>"
                               required
                               placeholder="your@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            เบอร์โทรศัพท์ <span style="color: red;">*</span>
                        </label>
                        <input type="tel" 
                               name="phone" 
                               value="<?= htmlspecialchars($current_user['phone']) ?>"
                               required
                               placeholder="08X-XXX-XXXX">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        ความต้องการพิเศษ (ถ้ามี)
                    </label>
                    <textarea name="special_requests" 
                              placeholder="เช่น ขอห้องชั้นสูง, ขอเตียงเสริม, แพ้อาหาร..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-arrow-right"></i> ดำเนินการต่อไปหน้าชำระเงิน
                </button>
            </form>
        </div>
        
        <!-- Booking Summary -->
        <div class="booking-summary">
            <h2 class="section-title">
                <i class="fas fa-receipt"></i> สรุปการจอง
            </h2>
            
            <div class="room-info">
                <h3><?= htmlspecialchars($room['room_type_name']) ?></h3>
                <div class="booking-details">
                    <div><i class="fas fa-calendar"></i> <?= formatDate($check_in) ?> - <?= formatDate($check_out) ?></div>
                    <div><i class="fas fa-moon"></i> <?= $nights ?> คืน</div>
                    <div><i class="fas fa-users"></i> <?= $adults ?> ผู้ใหญ่<?= $children > 0 ? ", {$children} เด็ก" : '' ?></div>
                    <div><i class="fas fa-door-open"></i> <?= $rooms ?> ห้อง</div>
                </div>
            </div>
            
            <div class="summary-item">
                <span>ราคาห้องพัก (฿<?= number_format($base_price, 0) ?> × <?= $nights ?> คืน × <?= $rooms ?> ห้อง)</span>
                <strong>฿<?= number_format($room_total, 0) ?></strong>
            </div>
            
            <?php if ($breakfast_total > 0): ?>
            <div class="summary-item">
                <span>อาหารเช้า (฿<?= number_format($breakfast_price, 0) ?> × <?= $adults + $children ?> คน × <?= $nights ?> คืน)</span>
                <strong>฿<?= number_format($breakfast_total, 0) ?></strong>
            </div>
            <?php elseif ($breakfast_included): ?>
            <div class="summary-item">
                <span>อาหารเช้า</span>
                <strong style="color: #27ae60;">รวมแล้ว</strong>
            </div>
            <?php endif; ?>
            
            <div class="summary-item">
                <span>รวมย่อย</span>
                <strong>฿<?= number_format($subtotal, 0) ?></strong>
            </div>
            
            <div class="summary-item">
                <span>ภาษี VAT (<?= $vat_rate ?>%)</span>
                <strong>฿<?= number_format($vat_amount, 0) ?></strong>
            </div>
            
            <div class="summary-item">
                <span>ค่าบริการ (<?= $service_rate ?>%)</span>
                <strong>฿<?= number_format($service_amount, 0) ?></strong>
            </div>
            
            <div class="summary-item highlight">
                <span style="font-size: 1.2rem;">ยอดรวมทั้งหมด</span>
                <strong style="font-size: 1.5rem; color: #667eea;">฿<?= number_format($grand_total, 0) ?></strong>
            </div>
            
            <div style="text-align: center; padding-top: 15px; border-top: 1px solid #f0f0f0; margin-top: 15px;">
                <p style="font-size: 13px; color: #999; margin: 0;">
                    <i class="fas fa-shield-alt"></i> ชำระเงินปลอดภัย 100%
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>