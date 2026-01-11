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

$hotel = new Hotel();

// Get search parameters
$city = $_GET['city'] ?? '';
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = $_GET['guests'] ?? 2;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Search hotels
$hotels = $hotel->searchHotels($city, $checkIn, $checkOut, $guests, $page);
$totalHotels = $hotel->getTotalHotels($city);
$totalPages = ceil($totalHotels / ITEMS_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาโรงแรม - <?php echo SITE_NAME; ?></title>
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

    <!-- Search Bar -->
    <div class="container" style="margin-top: 2rem;">
        <div class="search-box">
            <form action="search.php" method="GET" class="search-form">
                <div class="form-group">
                    <label>จังหวัด/เมือง</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="เช่น Bangkok, Phuket">
                </div>
                
                <div class="form-group">
                    <label>วันที่เช็คอิน</label>
                    <input type="date" name="check_in" value="<?php echo htmlspecialchars($checkIn); ?>">
                </div>
                
                <div class="form-group">
                    <label>วันที่เช็คเอาท์</label>
                    <input type="date" name="check_out" value="<?php echo htmlspecialchars($checkOut); ?>">
                </div>
                
                <div class="form-group">
                    <label>จำนวนผู้เข้าพัก</label>
                    <select name="guests">
                        <option value="1" <?php echo $guests == 1 ? 'selected' : ''; ?>>1 คน</option>
                        <option value="2" <?php echo $guests == 2 ? 'selected' : ''; ?>>2 คน</option>
                        <option value="3" <?php echo $guests == 3 ? 'selected' : ''; ?>>3 คน</option>
                        <option value="4" <?php echo $guests == 4 ? 'selected' : ''; ?>>4 คน</option>
                        <option value="5" <?php echo $guests >= 5 ? 'selected' : ''; ?>>5+ คน</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <div class="container" style="margin: 2rem auto;">
        <h2 style="margin-bottom: 1rem;">
            <?php if (!empty($city)): ?>
                พบ <?php echo $totalHotels; ?> โรงแรมใน <?php echo htmlspecialchars($city); ?>
            <?php else: ?>
                โรงแรมทั้งหมด (<?php echo $totalHotels; ?> แห่ง)
            <?php endif; ?>
        </h2>

        <?php if (empty($hotels)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                ไม่พบโรงแรมที่ตรงกับเงื่อนไขการค้นหา กรุณาลองค้นหาใหม่อีกครั้ง
            </div>
        <?php else: ?>
            <div class="hotels-grid">
                <?php foreach ($hotels as $hotelItem): 
                    $images = parseJSON($hotelItem['images']);
                    $firstImage = !empty($images) ? $images[0] : '';
                ?>
                <div class="hotel-card" onclick="location.href='hotel_detail.php?id=<?php echo $hotelItem['hotel_id']; ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>'">
                    <div class="hotel-card-image">
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-hotel" style="font-size: 3rem; color: white;"></i>
                        </div>
                    </div>
                    <div class="hotel-card-content">
                        <h3 class="hotel-card-title"><?php echo htmlspecialchars($hotelItem['hotel_name']); ?></h3>
                        <p class="hotel-card-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($hotelItem['city']); ?>, <?php echo htmlspecialchars($hotelItem['country']); ?>
                        </p>
                        
                        <div class="hotel-card-rating">
                            <?php echo generateStarRating($hotelItem['star_rating']); ?>
                            <?php if ($hotelItem['review_count'] > 0): ?>
                                <span class="review-count">(<?php echo $hotelItem['review_count']; ?> รีวิว)</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($hotelItem['avg_rating']): ?>
                            <div style="margin: 0.5rem 0;">
                                <span style="background: var(--primary-color); color: white; padding: 0.3rem 0.6rem; border-radius: 4px; font-weight: bold;">
                                    <?php echo number_format($hotelItem['avg_rating'], 1); ?>
                                </span>
                                <span style="color: var(--text-secondary); font-size: 0.9rem; margin-left: 0.5rem;">ดีมาก</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="hotel-card-price">
                            <div>
                                <div class="price-label">เริ่มต้น</div>
                                <div class="price-amount">
                                    <?php echo formatPrice($hotelItem['min_price'] ?? 0); ?>
                                    <span class="price-unit">/คืน</span>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="event.stopPropagation();">
                                ดูรายละเอียด
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?city=<?php echo urlencode($city); ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>&page=<?php echo $page - 1; ?>">
                        <i class="fas fa-chevron-left"></i> ก่อนหน้า
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?city=<?php echo urlencode($city); ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?city=<?php echo urlencode($city); ?>&check_in=<?php echo urlencode($checkIn); ?>&check_out=<?php echo urlencode($checkOut); ?>&guests=<?php echo $guests; ?>&page=<?php echo $page + 1; ?>">
                        ถัดไป <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
