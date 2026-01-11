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
require_once PROJECT_ROOT . '/modules/hotel/Hotel.php';

$hotelObj = new Hotel();

$hotelId = $_GET['id'] ?? 0;
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = $_GET['guests'] ?? 2;

if (!$hotelId) {
    redirect(PROJECT_ROOT . '/index.php');
}

$hotel = $hotelObj->getHotelById($hotelId);
if (!$hotel) {
    redirect(PROJECT_ROOT . '/index.php');
}

$roomTypes = $hotelObj->getRoomTypes($hotelId);
$reviews = $hotelObj->getHotelReviews($hotelId, 5);

$images = parseJSON($hotel['images']);
$amenities = parseJSON($hotel['amenities']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['hotel_name']); ?> - <?php echo SITE_NAME; ?></title>
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
                <li><a href="search.php">ค้นหาโรงแรม</a></li>
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

    <div class="container" style="margin-top: 2rem;">
        <!-- Breadcrumb -->
        <div style="margin-bottom: 1rem; color: var(--text-secondary);">
            <a href="index.php" style="color: var(--primary-color); text-decoration: none;">หน้าแรก</a>
            <i class="fas fa-chevron-right" style="margin: 0 0.5rem; font-size: 0.8rem;"></i>
            <a href="search.php" style="color: var(--primary-color); text-decoration: none;">ค้นหาโรงแรม</a>
            <i class="fas fa-chevron-right" style="margin: 0 0.5rem; font-size: 0.8rem;"></i>
            <span><?php echo htmlspecialchars($hotel['hotel_name']); ?></span>
        </div>

        <!-- Hotel Detail -->
        <div class="hotel-detail">
            <!-- Gallery -->
            <div class="hotel-gallery">
                <div class="gallery-main">
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hotel" style="font-size: 5rem; color: white;"></i>
                    </div>
                </div>
                <div class="gallery-item">
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bed" style="font-size: 3rem; color: white;"></i>
                    </div>
                </div>
                <div class="gallery-item">
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-swimming-pool" style="font-size: 3rem; color: white;"></i>
                    </div>
                </div>
                <div class="gallery-item">
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-utensils" style="font-size: 3rem; color: white;"></i>
                    </div>
                </div>
            </div>

            <!-- Hotel Info -->
            <div class="hotel-info">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h1 class="hotel-title"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h1>
                        <p class="hotel-card-location" style="font-size: 1.1rem;">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($hotel['address']); ?>, 
                            <?php echo htmlspecialchars($hotel['city']); ?>, 
                            <?php echo htmlspecialchars($hotel['country']); ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <?php echo generateStarRating($hotel['star_rating']); ?>
                        </div>
                        <?php if ($hotel['avg_rating']): ?>
                            <div style="margin-top: 0.5rem;">
                                <span style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 4px; font-size: 1.5rem; font-weight: bold;">
                                    <?php echo number_format($hotel['avg_rating'], 1); ?>
                                </span>
                                <span style="color: var(--text-secondary); font-size: 0.9rem; display: block; margin-top: 0.3rem;">
                                    จาก <?php echo $hotel['review_count']; ?> รีวิว
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin: 2rem 0; padding: 1.5rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
                    <p style="color: var(--text-secondary); line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($hotel['description'])); ?>
                    </p>
                </div>

                <!-- Amenities -->
                <?php if (!empty($amenities)): ?>
                <div style="margin: 2rem 0;">
                    <h3 style="margin-bottom: 1rem;">
                        <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                        สิ่งอำนวยความสะดวก
                    </h3>
                    <div class="amenities-list">
                        <?php foreach ($amenities as $amenity): ?>
                        <div class="amenity-item">
                            <i class="fas fa-check"></i>
                            <?php echo htmlspecialchars($amenity); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Room Types -->
        <div class="rooms-section">
            <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-door-open" style="color: var(--primary-color);"></i>
                เลือกห้องพัก
            </h2>

            <?php if (empty($roomTypes)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    ขออภัย ไม่มีห้องพักว่างในขณะนี้
                </div>
            <?php else: ?>
                <?php foreach ($roomTypes as $room): 
                    $roomAmenities = parseJSON($room['amenities']);
                ?>
                <div class="room-card">
                    <div class="room-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bed" style="font-size: 3rem; color: white;"></i>
                    </div>
                    
                    <div class="room-info">
                        <h3><?php echo htmlspecialchars($room['room_type_name']); ?></h3>
                        <p style="color: var(--text-secondary); margin: 0.5rem 0;">
                            <?php echo htmlspecialchars($room['description']); ?>
                        </p>
                        
                        <div class="room-features">
                            <div class="room-feature">
                                <i class="fas fa-ruler-combined"></i>
                                <?php echo $room['size_sqm']; ?> ตร.ม.
                            </div>
                            <div class="room-feature">
                                <i class="fas fa-users"></i>
                                <?php echo $room['max_occupancy']; ?> คน
                            </div>
                            <div class="room-feature">
                                <i class="fas fa-bed"></i>
                                <?php echo htmlspecialchars($room['bed_type']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($roomAmenities)): ?>
                        <div style="margin-top: 1rem; color: var(--text-secondary); font-size: 0.9rem;">
                            <i class="fas fa-check"></i>
                            <?php echo implode(' • ', array_slice($roomAmenities, 0, 3)); ?>
                            <?php if (count($roomAmenities) > 3): ?>
                                และอื่นๆ
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="room-booking">
                        <div class="room-price">
                            <?php echo formatPrice($room['base_price']); ?>
                            <span class="price-unit">/คืน</span>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1rem;">
                            รวมภาษีและค่าบริการ
                        </p>
                        
                        <?php if (isLoggedIn()): ?>
                            <a href="booking.php?hotel_id=<?php echo $hotelId; ?>&room_type_id=<?php echo $room['room_type_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>" 
                               class="btn btn-primary" style="width: 100%;">
                                จองเลย
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=hotel_detail.php?id=<?php echo $hotelId; ?>" 
                               class="btn btn-primary" style="width: 100%;">
                                เข้าสู่ระบบเพื่อจอง
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($room['total_rooms'] <= 5): ?>
                        <p style="color: var(--danger-color); font-size: 0.85rem; margin-top: 0.5rem; text-align: center;">
                            <i class="fas fa-exclamation-circle"></i>
                            เหลือห้องว่างเพียง <?php echo $room['total_rooms']; ?> ห้อง
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
        <div style="margin: 3rem 0;">
            <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-star" style="color: var(--secondary-color);"></i>
                รีวิวจากผู้เข้าพัก
            </h2>
            
            <?php foreach ($reviews as $review): ?>
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; box-shadow: var(--shadow);">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <img src="<?php echo getUserAvatar($review['email']); ?>" 
                             alt="<?php echo htmlspecialchars($review['first_name']); ?>"
                             style="width: 50px; height: 50px; border-radius: 50%;">
                        <div>
                            <div style="font-weight: 600;">
                                <?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1)); ?>.
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?php echo formatDate($review['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    <?php echo generateStarRating($review['rating']); ?>
                </div>
                
                <?php if ($review['title']): ?>
                <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($review['title']); ?></h4>
                <?php endif; ?>
                
                <p style="color: var(--text-secondary); line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                </p>
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
