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

// Check if user is logged in
if (!isLoggedIn()) {
    redirect( PROJECT_ROOT . '/login.php');
}

$bookingObj = new Booking();
$userId = getCurrentUserId();

// Get bookings
$bookings = $bookingObj->getUserBookings($userId);

// Handle cancellation
if (isset($_POST['cancel_booking'])) {
    $bookingId = (int)$_POST['booking_id'];
    $result = $bookingObj->cancelBooking($bookingId, $userId);
    
    if ($result['success']) {
        setFlashMessage($result['message'], 'success');
    } else {
        setFlashMessage($result['message'], 'error');
    }
    
    redirect( PROJECT_ROOT . '/my_bookings.php');
}

$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองของฉัน - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                
                <li><a href="my_bookings.php">การจองของฉัน</a></li>
                <li><a href="profile.php">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['first_name']; ?>
                </a></li>
                <li><a href="logout.php">ออกจากระบบ</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="margin: 2rem auto;">
        <h1 style="margin-bottom: 2rem;">
            <i class="fas fa-calendar-check" style="color: var(--primary-color);"></i>
            การจองของฉัน
        </h1>

        <?php if ($flashMessage): ?>
        <div class="alert alert-<?php echo $flashMessage['type']; ?>">
            <?php echo $flashMessage['message']; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 8px;">
                <i class="fas fa-calendar-times" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                <h2 style="color: var(--text-secondary); margin-bottom: 1rem;">ยังไม่มีการจอง</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">เริ่มค้นหาโรงแรมที่คุณชื่นชอบ และจองเลยวันนี้</p>
                <a href="search.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> ค้นหาโรงแรม
                </a>
            </div>
        <?php else: ?>
            <div class="booking-list">
                <?php foreach ($bookings as $booking): 
                    $images = parseJSON($booking['hotel_images']);
                    $firstImage = !empty($images) ? $images[0] : '';
                    $nights = calculateNights($booking['check_in'], $booking['check_out']);
                ?>
                <div class="booking-item">
                    <div class="booking-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hotel" style="font-size: 3rem; color: white;"></i>
                    </div>
                    
                    <div class="booking-details">
                        <h3><?php echo htmlspecialchars($booking['hotel_name']); ?></h3>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($booking['city']); ?>
                        </p>
                        <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">
                            <i class="fas fa-door-open"></i>
                            <?php echo htmlspecialchars($booking['room_type_name']); ?>
                        </p>
                        
                        <div class="booking-info-row">
                            <div>
                                <i class="fas fa-calendar"></i>
                                เช็คอิน: <strong><?php echo formatDate($booking['check_in']); ?></strong>
                            </div>
                            <div>
                                <i class="fas fa-calendar"></i>
                                เช็คเอาท์: <strong><?php echo formatDate($booking['check_out']); ?></strong>
                            </div>
                            <div>
                                <i class="fas fa-moon"></i>
                                <?php echo $nights; ?> คืน
                            </div>
                        </div>
                        
                        <div class="booking-info-row">
                            <div>
                                <i class="fas fa-door-closed"></i>
                                <?php echo $booking['num_rooms']; ?> ห้อง
                            </div>
                            <div>
                                <i class="fas fa-users"></i>
                                <?php echo $booking['num_adults']; ?> ผู้ใหญ่, <?php echo $booking['num_children']; ?> เด็ก
                            </div>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <span class="booking-status status-<?php echo $booking['status']; ?>">
                                <?php 
                                $statusText = [
                                    'pending' => 'รอดำเนินการ',
                                    'confirmed' => 'ยืนยันแล้ว',
                                    'cancelled' => 'ยกเลิกแล้ว',
                                    'completed' => 'เสร็จสิ้น'
                                ];
                                echo $statusText[$booking['status']] ?? $booking['status'];
                                ?>
                            </span>
                            
                            <span style="margin-left: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                                รหัสการจอง: <strong><?php echo $booking['booking_reference']; ?></strong>
                            </span>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-secondary); font-size: 0.9rem;">ราคารวม</div>
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                <?php echo formatPrice($booking['total_price']); ?>
                            </div>
                        </div>
                        
                        <a href="booking_confirmation.php?ref=<?php echo $booking['booking_reference']; ?>" 
                           class="btn btn-outline" style="width: 100%; margin-bottom: 0.5rem;">
                            <i class="fas fa-eye"></i> ดูรายละเอียด
                        </a>
                        
                        <?php if ($booking['status'] === 'confirmed' || $booking['status'] === 'pending'): ?>
                        <form method="POST" style="margin-top: 0.5rem;">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                            <button type="submit" 
                                    name="cancel_booking" 
                                    class="btn btn-danger cancel-booking-btn" 
                                    style="width: 100%;">
                                <i class="fas fa-times"></i> ยกเลิกการจอง
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] === 'completed'): ?>
                        <a href="review.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                           class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;">
                            <i class="fas fa-star"></i> รีวิว
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
