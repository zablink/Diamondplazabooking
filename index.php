<?php
require_once './config/config.php';
require_once './includes/Database.php';
require_once './includes/helpers.php';
require_once './modules/hotel/Hotel.php';

$hotelObj = new Hotel();

// ดึงข้อมูลโรงแรม
$hotel = $hotelObj->getHotelById(HOTEL_ID);

if (!$hotel) {
    die('ไม่พบข้อมูลโรงแรม กรุณาตรวจสอบการตั้งค่า HOTEL_ID ใน config.php');
}

// ดึงห้องพักทั้งหมด
$roomTypes = $hotelObj->getRoomTypes(HOTEL_ID);

// ดึงรีวิว
$reviews = $hotelObj->getHotelReviews(HOTEL_ID, 3);

$flashMessage = getFlashMessage();

// ดึงข้อมูลเพิ่มเติม
$amenities = parseJSON($hotel['amenities']);
$images = parseJSON($hotel['images']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - จองโรงแรมออนไลน์</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-hotel"></i> <?php echo htmlspecialchars($hotel['hotel_name']); ?>
            </a>
            <ul class="nav-links">
                <li><a href="index.php">หน้าแรก</a></li>
                <li><a href="#rooms">ห้องพัก</a></li>
                <li><a href="#about">เกี่ยวกับเรา</a></li>
                <li><a href="#reviews">รีวิว</a></li>
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

    <!-- Flash Message -->
    <?php if ($flashMessage): ?>
    <div class="container" style="margin-top: 20px;">
        <div class="alert alert-<?php echo $flashMessage['type']; ?>">
            <?php echo $flashMessage['message']; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="search-hero">
        <div class="container">
            <h1><?php echo htmlspecialchars($hotel['hotel_name']); ?></h1>
            <div style="margin: 1rem 0;">
                <?php echo generateStarRating($hotel['star_rating']); ?>
                <?php if ($hotel['avg_rating']): ?>
                    <span style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; margin-left: 1rem;">
                        <i class="fas fa-star"></i> <?php echo number_format($hotel['avg_rating'], 1); ?>/5.0
                    </span>
                <?php endif; ?>
            </div>
            <p>
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($hotel['address']); ?>, 
                <?php echo htmlspecialchars($hotel['city']); ?>
            </p>
        </div>
    </section>

    <!-- Quick Booking Box -->
    <section class="container" style="margin-top: -3rem; position: relative; z-index: 10;">
        <div class="search-box">
            <h3 style="margin-bottom: 1.5rem; color: var(--text-primary);">
                <i class="fas fa-calendar-check"></i> จองห้องพักของคุณ
            </h3>
            <form action="#rooms" method="GET" class="search-form">
                <div class="form-group">
                    <label>วันที่เช็คอิน</label>
                    <input type="date" name="check_in" id="check_in" required>
                </div>
                
                <div class="form-group">
                    <label>วันที่เช็คเอาท์</label>
                    <input type="date" name="check_out" id="check_out" required>
                </div>
                
                <div class="form-group">
                    <label>จำนวนผู้เข้าพัก</label>
                    <select name="guests">
                        <option value="1">1 คน</option>
                        <option value="2" selected>2 คน</option>
                        <option value="3">3 คน</option>
                        <option value="4">4 คน</option>
                        <option value="5">5+ คน</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="align-self: end;">
                    <i class="fas fa-search"></i> ค้นหาห้องว่าง
                </button>
            </form>
        </div>
    </section>

    <!-- About Hotel -->
    <section class="container" id="about" style="margin: 3rem auto;">
        <div style="background: white; padding: 3rem; border-radius: 12px; box-shadow: var(--shadow);">
            <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                เกี่ยวกับเรา
            </h2>
            
            <!-- Gallery -->
            <?php if (!empty($images) && count($images) >= 4): ?>
            <div class="hotel-gallery" style="margin-bottom: 2rem;">
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
            <?php endif; ?>
            
            <p style="color: var(--text-secondary); line-height: 1.8; font-size: 1.1rem; margin-bottom: 2rem;">
                <?php echo nl2br(htmlspecialchars($hotel['description'])); ?>
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-light); border-radius: 8px;">
                    <i class="fas fa-map-marker-alt" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                    <h4>ที่ตั้ง</h4>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                        <?php echo htmlspecialchars($hotel['city']); ?>
                    </p>
                </div>
                
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-light); border-radius: 8px;">
                    <i class="fas fa-phone" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                    <h4>ติดต่อเรา</h4>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                        <?php echo htmlspecialchars($hotel['phone']); ?>
                    </p>
                </div>
                
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-light); border-radius: 8px;">
                    <i class="fas fa-envelope" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                    <h4>อีเมล</h4>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                        <?php echo htmlspecialchars($hotel['email']); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Amenities -->
    <?php if (!empty($amenities)): ?>
    <section class="container" style="margin: 3rem auto;">
        <div style="background: white; padding: 3rem; border-radius: 12px; box-shadow: var(--shadow);">
            <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-concierge-bell" style="color: var(--primary-color);"></i>
                สิ่งอำนวยความสะดวก
            </h2>
            <div class="amenities-list">
                <?php foreach ($amenities as $amenity): ?>
                <div class="amenity-item">
                    <i class="fas fa-check"></i>
                    <?php echo htmlspecialchars($amenity); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Room Types -->
    <section class="container" id="rooms" style="margin: 3rem auto;">
        <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
            <i class="fas fa-bed" style="color: var(--primary-color);"></i>
            ห้องพักของเรา
        </h2>
        
        <?php if (empty($roomTypes)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                ขณะนี้ไม่มีห้องพักให้บริการ
            </div>
        <?php else: ?>
            <div class="rooms-section">
                <?php 
                $checkIn = $_GET['check_in'] ?? '';
                $checkOut = $_GET['check_out'] ?? '';
                $guests = $_GET['guests'] ?? 2;
                
                foreach ($roomTypes as $room): 
                    $roomAmenities = parseJSON($room['amenities']);
                    $roomImages = parseJSON($room['images']);
                ?>
                <div class="room-card">
                    <div class="room-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-bed" style="font-size: 3rem; color: white;"></i>
                    </div>
                    
                    <div class="room-info">
                        <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
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
                                สูงสุด <?php echo $room['max_occupancy']; ?> คน
                            </div>
                            <div class="room-feature">
                                <i class="fas fa-bed"></i>
                                <?php echo htmlspecialchars($room['bed_type']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($roomAmenities)): ?>
                        <div style="margin-top: 1rem;">
                            <strong style="color: var(--text-primary); font-size: 0.95rem;">สิ่งอำนวยความสะดวก:</strong>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">
                                <?php 
                                $displayAmenities = array_slice($roomAmenities, 0, 4);
                                foreach ($displayAmenities as $amenity): 
                                ?>
                                <span style="display: inline-block; margin-right: 1rem; margin-bottom: 0.3rem;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    <?php echo htmlspecialchars($amenity); ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if (count($roomAmenities) > 4): ?>
                                <span style="color: var(--primary-color);">และอื่นๆ</span>
                                <?php endif; ?>
                            </div>
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
                            <a href="booking.php?hotel_id=<?php echo HOTEL_ID; ?>&room_type_id=<?php echo $room['room_type_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>" 
                               class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-calendar-check"></i> จองเลย
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=index.php" 
                               class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบเพื่อจอง
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($room['total_rooms'] <= 5): ?>
                        <p style="color: var(--danger-color); font-size: 0.85rem; margin-top: 0.5rem; text-align: center;">
                            <i class="fas fa-exclamation-circle"></i>
                            เหลือเพียง <?php echo $room['total_rooms']; ?> ห้อง!
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Reviews -->
    <?php if (!empty($reviews)): ?>
    <section class="container" id="reviews" style="margin: 3rem auto;">
        <div style="background: white; padding: 3rem; border-radius: 12px; box-shadow: var(--shadow);">
            <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-star" style="color: var(--secondary-color);"></i>
                รีวิวจากผู้เข้าพัก
            </h2>
            
            <?php if ($hotel['avg_rating']): ?>
            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                <div style="font-size: 3rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo number_format($hotel['avg_rating'], 1); ?>/5.0
                </div>
                <div style="margin: 0.5rem 0;">
                    <?php echo generateStarRating($hotel['avg_rating']); ?>
                </div>
                <div style="color: var(--text-secondary);">
                    จาก <?php echo $hotel['review_count']; ?> รีวิว
                </div>
            </div>
            <?php endif; ?>
            
            <?php foreach ($reviews as $review): ?>
            <div style="padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color);">
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
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div>
                    <h4>เกี่ยวกับเรา</h4>
                    <a href="#">เกี่ยวกับบริษัท</a>
                    <a href="#">ร่วมงานกับเรา</a>
                    <a href="#">ติดต่อเรา</a>
                </div>
                
                <div>
                    <h4>สำหรับธุรกิจ</h4>
                    <a href="#">ลงทะเบียนโรงแรม</a>
                    <a href="#">Extranet</a>
                    <a href="#">โฆษณากับเรา</a>
                </div>
                
                <div>
                    <h4>ช่วยเหลือ</h4>
                    <a href="#">คำถามที่พบบ่อย</a>
                    <a href="#">นโยบายการยกเลิก</a>
                    <a href="#">เงื่อนไขการใช้บริการ</a>
                </div>
                
                <div>
                    <h4>ติดตามเรา</h4>
                    <a href="#"><i class="fab fa-facebook"></i> Facebook</a>
                    <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
                    <a href="#"><i class="fab fa-line"></i> Line</a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Hotel Booking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
