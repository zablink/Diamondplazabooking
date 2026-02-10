<?php
/**
 * booking/room_detail.php
 * หน้ารายละเอียดห้องพัก - แสดง Gallery รูปภาพทั้งหมดและข้อมูลครบถ้วน
 */

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
require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/hotel/Hotel.php';
require_once PROJECT_ROOT . '/includes/RoomImage.php';

// Get room ID
$roomTypeId = $_GET['id'] ?? 0;

if (!$roomTypeId) {
    header('Location: index.php');
    exit;
}

// Initialize classes
$hotel = new Hotel();
$roomImage = new RoomImage();

// ดึงข้อมูลห้องพัก
$roomType = $hotel->getRoomTypeById($roomTypeId);

if (!$roomType) {
    setFlashMessage('ไม่พบข้อมูลห้องพักที่ต้องการ', 'error');
    header('Location: index.php');
    exit;
}

// ดึงรูปภาพทั้งหมด
$roomImages = $roomImage->getImages($roomTypeId);
$featuredImage = $roomImage->getFeaturedImage($roomTypeId);

// Parse amenities
$amenities = [];
if (!empty($roomType['amenities'])) {
    $amenitiesData = json_decode($roomType['amenities'], true);
    if (is_array($amenitiesData)) {
        $amenities = $amenitiesData;
    }
}

$page_title = htmlspecialchars($roomType['room_type_name']) . ' - ' . SITE_NAME;
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
            background: #f8f9fa;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .back-button:hover {
            background: #667eea;
            color: white;
            transform: translateX(-5px);
        }

        /* Room Header */
        .room-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .room-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 15px;
        }

        .room-subtitle {
            color: #666;
            font-size: 1.1rem;
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .subtitle-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .subtitle-item i {
            color: #667eea;
        }

        /* Gallery Section */
        .gallery-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .gallery-section h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
        }

        /* Featured Image */
        .featured-image-container {
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            cursor: pointer;
            position: relative;
        }

        .featured-image-container::after {
            content: '🔍 คลิกเพื่อดูรูปใหญ่';
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .featured-image-container:hover::after {
            opacity: 1;
        }

        .featured-image-container img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s;
        }

        .featured-image-container:hover img {
            transform: scale(1.05);
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .gallery-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Room Details */
        .room-details {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .room-details h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .room-details p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        /* Amenities List */
        .amenities-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .amenity-item:hover {
            background: #f0f4ff;
            transform: translateX(5px);
        }

        .amenity-item i {
            color: #667eea;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .amenity-item span {
            color: #333;
            font-weight: 500;
        }

        /* Booking Card */
        .booking-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }

        .booking-card h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
        }

        .price-display {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            margin-bottom: 25px;
            color: white;
        }

        .price-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .price-unit {
            font-size: 16px;
            opacity: 0.9;
        }

        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-book-now {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .booking-info {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
            text-align: center;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .feature-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .feature-item i {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 8px;
        }

        .feature-item strong {
            display: block;
            color: #333;
            font-size: 18px;
            margin-bottom: 4px;
        }

        .feature-item span {
            color: #666;
            font-size: 13px;
        }

        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .lightbox.active {
            display: flex;
        }

        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }

        .lightbox-content img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 8px;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
            z-index: 10001;
            transition: transform 0.3s;
        }

        .lightbox-close:hover {
            transform: scale(1.2);
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 20px;
            font-size: 30px;
            cursor: pointer;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .lightbox-nav:hover {
            background: rgba(255,255,255,0.4);
        }

        .lightbox-prev {
            left: 30px;
        }

        .lightbox-next {
            right: 30px;
        }

        .image-counter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* No Images State */
        .no-images {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-images i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .booking-card {
                position: static;
            }

            .featured-image-container img {
                height: 300px;
            }
        }

        @media (max-width: 768px) {
            .room-title {
                font-size: 1.8rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .price-amount {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            กลับหน้าแรก
        </a>

        <!-- Room Header -->
        <div class="room-header">
            <h1 class="room-title"><?= htmlspecialchars($roomType['room_type_name']) ?></h1>
            <div class="room-subtitle">
                <div class="subtitle-item">
                    <i class="fas fa-users"></i>
                    <span>สูงสุด <?= $roomType['max_occupancy'] ?> คน</span>
                </div>
                <div class="subtitle-item">
                    <i class="fas fa-door-open"></i>
                    <span><?= $roomType['total_rooms'] ?? 0 ?> ห้องพร้อมให้บริการ</span>
                </div>
                <?php if ($roomType['breakfast_included']): ?>
                <div class="subtitle-item">
                    <i class="fas fa-utensils"></i>
                    <span>รวมอาหารเช้า</span>
                </div>
                <?php endif; ?>
                <div class="subtitle-item">
                    <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                    <span style="color: #27ae60;">พร้อมให้บริการ</span>
                </div>
            </div>
        </div>

        <!-- Gallery Section -->
        <?php if (!empty($roomImages)): ?>
        <div class="gallery-section">
            <h2><i class="fas fa-images"></i> ภาพห้องพัก (<?= count($roomImages) ?> รูป)</h2>
            
            <!-- Featured Image -->
            <?php if ($featuredImage): ?>
            <div class="featured-image-container" onclick="openLightbox(0)">
                <img src="<?= SITE_URL . '/' . htmlspecialchars($featuredImage['image_path']) ?>" 
                     alt="<?= htmlspecialchars($roomType['room_type_name']) ?>">
            </div>
            <?php endif; ?>
            
            <!-- Gallery Grid -->
            <div class="gallery-grid">
                <?php foreach ($roomImages as $index => $image): ?>
                    <div class="gallery-item" onclick="openLightbox(<?= $index ?>)">
                        <img src="<?= SITE_URL . '/' . htmlspecialchars($image['image_path']) ?>" 
                             alt="Room Image <?= $index + 1 ?>"
                             loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Room Details -->
            <div class="room-details">
                <h2><i class="fas fa-info-circle"></i> รายละเอียดห้องพัก</h2>
                <p><?= nl2br(htmlspecialchars($roomType['description'] ?? 'ห้องพักสะดวกสบาย พร้อมสิ่งอำนวยความสะดวกครบครัน')) ?></p>

                <!-- Features -->
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <strong><?= $roomType['max_occupancy'] ?></strong>
                        <span>คน</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-door-open"></i>
                        <strong><?= $roomType['total_rooms'] ?? 0 ?></strong>
                        <span>ห้องพร้อมให้บริการ</span>
                    </div>
                    <?php if ($roomType['breakfast_included']): ?>
                    <div class="feature-item">
                        <i class="fas fa-utensils"></i>
                        <strong>รวม</strong>
                        <span>อาหารเช้า</span>
                    </div>
                    <?php endif; ?>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <strong>14:00</strong>
                        <span>เช็คอิน</span>
                    </div>
                </div>

                <?php if (!empty($amenities)): ?>
                <h2 style="margin-top: 30px;"><i class="fas fa-star"></i> สิ่งอำนวยความสะดวก</h2>
                <div class="amenities-list">
                    <?php
                    $amenityIcons = [
                        'WiFi' => 'wifi',
                        'TV' => 'tv',
                        'Air Conditioning' => 'snowflake',
                        'Mini Bar' => 'glass-martini',
                        'Safe Box' => 'lock',
                        'Hair Dryer' => 'wind',
                        'Bathtub' => 'bath',
                        'Shower' => 'shower',
                        'Coffee Maker' => 'coffee',
                        'Electric Kettle' => 'mug-hot',
                        'Work Desk' => 'desk',
                        'Balcony' => 'home'
                    ];
                    
                    foreach ($amenities as $amenity):
                    ?>
                        <div class="amenity-item">
                            <i class="fas fa-<?= $amenityIcons[$amenity] ?? 'check' ?>"></i>
                            <span><?= htmlspecialchars($amenity) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Booking Card -->
            <div class="booking-card">
                <h3><i class="fas fa-calendar-check"></i> จองห้องพัก</h3>
                
                <div class="price-display">
                    <div class="price-label">ราคาเริ่มต้น</div>
                    <div class="price-amount">฿<?= number_format($roomType['base_price'], 0) ?></div>
                    <div class="price-unit">ต่อคืน</div>
                </div>

                <form class="booking-form" method="GET" action="booking.php">
                    <input type="hidden" name="room_id" value="<?= $roomType['room_type_id'] ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-check"></i> วันเช็คอิน</label>
                        <input type="date" name="check_in" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-times"></i> วันเช็คเอาท์</label>
                        <input type="date" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-users"></i> จำนวนผู้เข้าพัก</label>
                        <select name="guests" required>
                            <?php for ($i = 1; $i <= $roomType['max_occupancy']; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == 2 ? 'selected' : '' ?>><?= $i ?> คน</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-book-now">
                        <i class="fas fa-check-circle"></i> จองเลย
                    </button>
                </form>

                <div class="booking-info">
                    <i class="fas fa-shield-alt"></i>
                    การจองของคุณจะได้รับการยืนยันทันที
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <?php if (!empty($roomImages)): ?>
    <div id="lightbox" class="lightbox" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        
        <div class="lightbox-nav lightbox-prev" onclick="event.stopPropagation(); changeImage(-1)">
            <i class="fas fa-chevron-left"></i>
        </div>
        
        <div class="lightbox-content">
            <img id="lightbox-image" src="" alt="Room Image">
            <div class="image-counter">
                <span id="current-image">1</span> / <?= count($roomImages) ?>
            </div>
        </div>
        
        <div class="lightbox-nav lightbox-next" onclick="event.stopPropagation(); changeImage(1)">
            <i class="fas fa-chevron-right"></i>
        </div>
    </div>

    <script>
        const images = <?= json_encode(array_map(function($img) {
            return SITE_URL . '/' . $img['image_path'];
        }, $roomImages)) ?>;
        
        let currentImageIndex = 0;
        
        function openLightbox(index) {
            currentImageIndex = index;
            updateLightboxImage();
            document.getElementById('lightbox').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox(event) {
            if (!event || event.target.id === 'lightbox' || event.target.classList.contains('lightbox-close')) {
                document.getElementById('lightbox').classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        }
        
        function changeImage(direction) {
            currentImageIndex += direction;
            
            if (currentImageIndex < 0) {
                currentImageIndex = images.length - 1;
            } else if (currentImageIndex >= images.length) {
                currentImageIndex = 0;
            }
            
            updateLightboxImage();
        }
        
        function updateLightboxImage() {
            document.getElementById('lightbox-image').src = images[currentImageIndex];
            document.getElementById('current-image').textContent = currentImageIndex + 1;
        }
        
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightbox');
            if (lightbox.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    changeImage(-1);
                } else if (e.key === 'ArrowRight') {
                    changeImage(1);
                }
            }
        });

        // Auto-update checkout date
        document.querySelector('input[name="check_in"]').addEventListener('change', function() {
            const checkIn = new Date(this.value);
            const checkOut = document.querySelector('input[name="check_out"]');
            const minCheckOut = new Date(checkIn);
            minCheckOut.setDate(minCheckOut.getDate() + 1);
            
            const minDate = minCheckOut.toISOString().split('T')[0];
            checkOut.min = minDate;
            
            if (checkOut.value && new Date(checkOut.value) <= checkIn) {
                checkOut.value = minDate;
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>