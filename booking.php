<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/helpers.php';
require_once '../modules/hotel/Hotel.php';
require_once '../modules/booking/Booking.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/public/login.php');
}

$hotelObj = new Hotel();
$bookingObj = new Booking();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingData = [
        'user_id' => getCurrentUserId(),
        'hotel_id' => sanitize($_POST['hotel_id']),
        'room_type_id' => sanitize($_POST['room_type_id']),
        'check_in' => sanitize($_POST['check_in']),
        'check_out' => sanitize($_POST['check_out']),
        'num_rooms' => (int)$_POST['num_rooms'],
        'num_adults' => (int)$_POST['num_adults'],
        'num_children' => (int)($_POST['num_children'] ?? 0),
        'room_price' => (float)$_POST['room_price'],
        'special_requests' => sanitize($_POST['special_requests'] ?? '')
    ];
    
    $result = $bookingObj->createBooking($bookingData);
    
    if ($result['success']) {
        setFlashMessage('จองห้องพักสำเร็จ! รหัสการจอง: ' . $result['booking_reference'], 'success');
        redirect('/public/booking_confirmation.php?ref=' . $result['booking_reference']);
    } else {
        $error = $result['message'];
    }
}

// Get booking parameters
$hotelId = $_GET['hotel_id'] ?? 0;
$roomTypeId = $_GET['room_type_id'] ?? 0;
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = $_GET['guests'] ?? 2;

if (!$hotelId || !$roomTypeId) {
    redirect('/public/index.php');
}

$hotel = $hotelObj->getHotelById($hotelId);
$roomTypes = $hotelObj->getRoomTypes($hotelId);

$selectedRoom = null;
foreach ($roomTypes as $room) {
    if ($room['room_type_id'] == $roomTypeId) {
        $selectedRoom = $room;
        break;
    }
}

if (!$hotel || !$selectedRoom) {
    redirect('/public/index.php');
}

// Calculate nights and total price
$nights = 1;
$totalPrice = $selectedRoom['base_price'];

if ($checkIn && $checkOut) {
    $nights = calculateNights($checkIn, $checkOut);
    $totalPrice = $selectedRoom['base_price'] * $nights;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองห้องพัก - <?php echo SITE_NAME; ?></title>
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
            ยืนยันการจอง
        </h1>

        <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Booking Form -->
            <div>
                <div class="booking-form">
                    <h2 style="margin-bottom: 1.5rem;">รายละเอียดการจอง</h2>
                    
                    <form method="POST" data-validate data-confirm>
                        <input type="hidden" name="hotel_id" value="<?php echo $hotelId; ?>">
                        <input type="hidden" name="room_type_id" value="<?php echo $roomTypeId; ?>">
                        <input type="hidden" name="room_price" id="room-price" value="<?php echo $selectedRoom['base_price']; ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>วันที่เช็คอิน *</label>
                                <input type="date" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>วันที่เช็คเอาท์ *</label>
                                <input type="date" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>" required>
                            </div>
                        </div>
                        
                        <div style="background: var(--bg-light); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary);">
                                <i class="fas fa-moon"></i>
                                <span>จำนวนคืน: <strong id="nights-display"><?php echo $nights; ?> คืน</strong></span>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label>จำนวนห้อง *</label>
                                <select name="num_rooms" id="num-rooms" required>
                                    <option value="1">1 ห้อง</option>
                                    <option value="2">2 ห้อง</option>
                                    <option value="3">3 ห้อง</option>
                                    <option value="4">4 ห้อง</option>
                                    <option value="5">5 ห้อง</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>ผู้ใหญ่ *</label>
                                <select name="num_adults" required>
                                    <option value="1" <?php echo $guests == 1 ? 'selected' : ''; ?>>1 คน</option>
                                    <option value="2" <?php echo $guests == 2 ? 'selected' : ''; ?>>2 คน</option>
                                    <option value="3" <?php echo $guests == 3 ? 'selected' : ''; ?>>3 คน</option>
                                    <option value="4" <?php echo $guests >= 4 ? 'selected' : ''; ?>>4 คน</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>เด็ก</label>
                                <select name="num_children">
                                    <option value="0">0 คน</option>
                                    <option value="1">1 คน</option>
                                    <option value="2">2 คน</option>
                                    <option value="3">3 คน</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>ความต้องการพิเศษ</label>
                            <textarea name="special_requests" rows="4" style="width: 100%; padding: 0.8rem; border: 1px solid var(--border-color); border-radius: 4px; font-family: inherit;" placeholder="เช่น เตียงเสริม, มุมมองทะเล, ชั้นสูง, ฯลฯ"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                            <i class="fas fa-check-circle"></i> ยืนยันการจอง
                        </button>
                    </form>
                </div>
            </div>

            <!-- Booking Summary -->
            <div>
                <div class="booking-summary" style="position: sticky; top: 100px;">
                    <h3 style="margin-bottom: 1rem;">สรุปการจอง</h3>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                        </h4>
                        <p style="color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($hotel['city']); ?>
                        </p>
                        <?php echo generateStarRating($hotel['star_rating']); ?>
                    </div>
                    
                    <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; margin-bottom: 1rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($selectedRoom['room_name']); ?>
                        </h4>
                        <div style="color: var(--text-secondary); font-size: 0.9rem;">
                            <div style="margin-bottom: 0.3rem;">
                                <i class="fas fa-bed"></i> <?php echo htmlspecialchars($selectedRoom['bed_type']); ?>
                            </div>
                            <div style="margin-bottom: 0.3rem;">
                                <i class="fas fa-users"></i> สูงสุด <?php echo $selectedRoom['max_occupancy']; ?> คน
                            </div>
                            <div>
                                <i class="fas fa-ruler-combined"></i> <?php echo $selectedRoom['size_sqm']; ?> ตร.ม.
                            </div>
                        </div>
                    </div>
                    
                    <div class="summary-row">
                        <span>ราคาห้องพักต่อคืน</span>
                        <strong><?php echo formatPrice($selectedRoom['base_price']); ?></strong>
                    </div>
                    
                    <div class="summary-row">
                        <span>จำนวนคืน</span>
                        <strong id="nights-summary"><?php echo $nights; ?> คืน</strong>
                    </div>
                    
                    <div class="summary-row" style="font-size: 1.3rem; color: var(--primary-color);">
                        <span>ยอดรวมทั้งหมด</span>
                        <strong id="total-price"><?php echo formatPrice($totalPrice); ?></strong>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 1rem; background: #e3f2fd; border-radius: 8px; font-size: 0.9rem;">
                        <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                        รวมภาษีและค่าบริการแล้ว
                    </div>
                </div>
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
