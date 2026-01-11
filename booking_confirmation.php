<?php
//// init for SESSION , PROJECT_PATH , etc..
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


require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/booking/Booking.php';

$bookingObj = new Booking();
$bookingRef = $_GET['ref'] ?? '';

if (!$bookingRef) {
    redirect( PROJECT_ROOT . '/index.php');
}

$booking = $bookingObj->getBookingByReference($bookingRef);

if (!$booking) {
    redirect( PROJECT_ROOT . '/index.php');
}

$nights = calculateNights($booking['check_in'], $booking['check_out']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการจอง - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-box {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: var(--shadow-hover);
            max-width: 800px;
            margin: 2rem auto;
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .success-icon i {
            font-size: 5rem;
            color: var(--success-color);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-hotel"></i> Hotel Booking
            </a>
            <ul class="nav-links">
                <li><a href="index.php">หน้าแรก</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="my_bookings.php">การจองของฉัน</a></li>
                    <li><a href="profile.php">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['first_name']; ?>
                    </a></li>
                    <li><a href="logout.php">ออกจากระบบ</a></li>
                <?php else: ?>
                    <li><a href="login.php">เข้าสู่ระบบ</a></li>
                    <li><a href="register.php">สมัครสมาชิก</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="confirmation-box">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
                <h1 style="margin-top: 1rem; color: var(--success-color);">จองสำเร็จ!</h1>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">
                    การจองของคุณได้รับการยืนยันแล้ว
                </p>
            </div>

            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                <div style="color: var(--text-secondary); margin-bottom: 0.5rem;">รหัสการจอง</div>
                <div style="font-size: 2rem; font-weight: bold; color: var(--primary-color); letter-spacing: 2px;">
                    <?php echo $booking['booking_reference']; ?>
                </div>
            </div>

            <h2 style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--primary-color);">
                <i class="fas fa-info-circle"></i> รายละเอียดการจอง
            </h2>

            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">ข้อมูลโรงแรม</h3>
                <div class="info-row">
                    <span class="info-label">โรงแรม</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['hotel_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">ที่อยู่</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($booking['address']); ?>, 
                        <?php echo htmlspecialchars($booking['city']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">เบอร์โทร</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['hotel_phone']); ?></span>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">ข้อมูลห้องพัก</h3>
                <div class="info-row">
                    <span class="info-label">ประเภทห้อง</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['room_type_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">เตียง</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['bed_type']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">จำนวนห้อง</span>
                    <span class="info-value"><?php echo $booking['num_rooms']; ?> ห้อง</span>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">ข้อมูลการเข้าพัก</h3>
                <div class="info-row">
                    <span class="info-label">วันที่เช็คอิน</span>
                    <span class="info-value"><?php echo formatDate($booking['check_in']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">วันที่เช็คเอาท์</span>
                    <span class="info-value"><?php echo formatDate($booking['check_out']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">จำนวนคืน</span>
                    <span class="info-value"><?php echo $nights; ?> คืน</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ผู้เข้าพัก</span>
                    <span class="info-value">
                        <?php echo $booking['num_adults']; ?> ผู้ใหญ่
                        <?php if ($booking['num_children'] > 0): ?>
                            , <?php echo $booking['num_children']; ?> เด็ก
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">ข้อมูลผู้จอง</h3>
                <div class="info-row">
                    <span class="info-label">ชื่อ-นามสกุล</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">อีเมล</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">เบอร์โทร</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                </div>
            </div>

            <?php if (!empty($booking['special_requests'])): ?>
            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">ความต้องการพิเศษ</h3>
                <div style="padding: 1rem; background: var(--bg-light); border-radius: 8px;">
                    <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="opacity: 0.9; margin-bottom: 0.3rem;">ยอดรวมทั้งหมด</div>
                        <div style="font-size: 2rem; font-weight: bold;">
                            <?php echo formatPrice($booking['total_price']); ?>
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem; margin-top: 0.3rem;">
                            (รวมภาษีและค่าบริการ)
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.2); border-radius: 20px; font-weight: 600;">
                            <?php 
                            $statusText = [
                                'pending' => 'รอดำเนินการ',
                                'confirmed' => 'ยืนยันแล้ว',
                                'cancelled' => 'ยกเลิกแล้ว',
                                'completed' => 'เสร็จสิ้น'
                            ];
                            echo $statusText[$booking['status']] ?? $booking['status'];
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background: #fff3e0; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ff9800; margin-bottom: 2rem;">
                <h4 style="color: #ef6c00; margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> ข้อมูลสำคัญ
                </h4>
                <ul style="color: #e65100; list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 0.5rem;">
                        <i class="fas fa-check"></i> ข้อมูลการจองได้ถูกส่งไปยังอีเมลของคุณแล้ว
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <i class="fas fa-check"></i> กรุณานำรหัสการจองมาแสดงเมื่อเช็คอิน
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <i class="fas fa-check"></i> เวลาเช็คอิน: 14:00 น. / เช็คเอาท์: 12:00 น.
                    </li>
                </ul>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <a href="my_bookings.php" class="btn btn-outline" style="text-align: center;">
                    <i class="fas fa-list"></i> ดูการจองทั้งหมด
                </a>
                <a href="index.php" class="btn btn-primary" style="text-align: center;">
                    <i class="fas fa-home"></i> กลับหน้าแรก
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 Hotel Booking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
