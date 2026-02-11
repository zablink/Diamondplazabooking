<?php
/**
 * Room Detail Page - Production Version
 * พร้อม Image Gallery และ Booking Form
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
require_once PROJECT_ROOT . '/includes/PriceCalculator.php';
require_once PROJECT_ROOT . '/includes/RoomImage.php'; // เพิ่มการเรียกใช้ RoomImage

// รับ room_type_id จาก URL
$room_type_id = $_GET['room_type_id'] ?? $_GET['id'] ?? null;

if (!$room_type_id) {
    setFlashMessage(__('rooms.room_not_found'), 'error');
    redirect('index.php');
}

// โหลดข้อมูลห้องพัก
$hotel = new Hotel();
$priceCalculator = new PriceCalculator();
$roomImage = new RoomImage(); // สร้าง instance ของ RoomImage
$room = $hotel->getRoomTypeById($room_type_id);

if (!$room) {
    setFlashMessage(__('rooms.room_not_found'), 'error');
    redirect('index.php');
}

// โหลดรูปภาพทั้งหมดผ่าน RoomImage
$images = $roomImage->getImages($room_type_id);
$featuredImage = $roomImage->getFeaturedImage($room_type_id);

// โหลด amenities พร้อมคำแปลและไอคอน
$roomAmenities = $hotel->getTranslatedAmenities($room_type_id);

// ดึง room types อื่นๆ
$otherRoomTypes = [];
if (!empty($room['hotel_id'])) {
    $allRoomTypes = $hotel->getRoomTypes($room['hotel_id'], 'available');
    foreach ($allRoomTypes as $otherRoom) {
        if ($otherRoom['room_type_id'] != $room_type_id) {
            $otherRoomTypes[] = $otherRoom;
        }
    }
}

// ... (ส่วนที่เหลือของไฟล์เหมือนเดิม)
$page_title = htmlspecialchars($room['room_type_name']) . ' - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
?>

<style>
    .room-detail-container {
        max-width: 1400px;
        margin: 40px auto;
        padding: 0 20px;
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
    
    .breadcrumb a:hover {
        text-decoration: underline;
    }
    
    /* ===== IMAGE GALLERY ===== */
    .image-gallery {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .main-image-container {
        position: relative;
        width: 100%;
        height: 500px;
        background: #f0f0f0;
        overflow: hidden;
    }
    
    .main-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .main-image:hover {
        transform: scale(1.05);
    }
    
    .gallery-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 100%;
        display: flex;
        justify-content: space-between;
        padding: 0 20px;
        pointer-events: none;
    }
    
    .gallery-nav button {
        pointer-events: all;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.3s;
    }
    
    .gallery-nav button:hover {
        background: white;
        transform: scale(1.1);
    }
    
    .gallery-nav button:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    
    .thumbnails-container {
        display: flex;
        gap: 10px;
        padding: 20px;
        background: white;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    
    .thumbnails-container::-webkit-scrollbar {
        height: 6px;
    }
    
    .thumbnails-container::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 3px;
    }
    
    .thumbnail {
        flex-shrink: 0;
        width: 120px;
        height: 80px;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        border: 3px solid transparent;
        transition: all 0.3s;
    }
    
    .thumbnail:hover {
        border-color: #667eea;
        transform: translateY(-2px);
    }
    
    .thumbnail.active {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .image-counter {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    
    /* ===== ROOM CONTENT ===== */
    .room-content {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 40px;
        margin-top: 30px;
    }
    
    .room-main {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .room-title {
        font-size: 32px;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }
    
    .room-description {
        color: #666;
        line-height: 1.8;
        margin-bottom: 30px;
        font-size: 16px;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 30px;
        padding: 25px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .feature-item i {
        font-size: 28px;
        color: #667eea;
        width: 40px;
        text-align: center;
    }
    
    .amenities-section {
        margin-top: 30px;
    }
    
    .amenities-section h3 {
        font-size: 24px;
        margin-bottom: 20px;
        color: #333;
    }
    
    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .amenity-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .amenity-item i {
        color: #27ae60;
        font-size: 16px;
    }
    
    /* ===== BOOKING PANEL ===== */
    .booking-panel {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 20px;
        /* ลบ max-height และ overflow-y เพื่อให้แสดงข้อมูลทั้งหมด */
        /* max-height: calc(100vh - 40px); */
        /* overflow-y: auto; */
    }
    
    .booking-panel h3 {
        font-size: 20px;
        margin-bottom: 20px;
        color: #333;
    }
    
    .price-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .price-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 5px;
    }
    
    .price-amount {
        font-size: 42px;
        font-weight: bold;
    }
    
    .breakfast-info {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .breakfast-info.not-included {
        background: #fff3cd;
        border-color: #ffc107;
        color: #856404;
    }
    
    .breakfast-info i {
        margin-right: 8px;
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
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .btn-book {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-book:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-book:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    /* ===== OTHER ROOMS SECTION ===== */
    .other-rooms-section {
        margin-top: 60px;
        padding-top: 40px;
        border-top: 2px solid #e9ecef;
    }
    
    .other-rooms-section h2 {
        font-size: 28px;
        font-weight: bold;
        color: #333;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .other-rooms-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    
    .other-room-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
        position: relative;
    }
    
    .other-room-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(102, 126, 234, 0.25);
    }
    
    .other-room-image-wrapper {
        position: relative;
        width: 100%;
        height: 200px;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .other-room-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .other-room-card:hover .other-room-image {
        transform: scale(1.15);
    }
    
    .other-room-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: 1;
    }
    
    .other-room-card:hover .other-room-overlay {
        opacity: 1;
    }
    
    .other-room-price-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.95);
        color: #667eea;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        z-index: 2;
    }
    
    .other-room-card:hover .other-room-price-badge {
        transform: scale(1);
    }
    
    .other-room-breakfast-badge {
        position: absolute;
        bottom: 15px;
        left: 15px;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        background: rgba(39, 174, 96, 0.9);
        color: white;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        z-index: 2;
    }
    
    .other-room-breakfast-badge i {
        font-size: 12px;
    }
    
    .other-room-card:hover .other-room-breakfast-badge {
        transform: scale(1);
    }
    
    .other-room-info {
        padding: 20px;
    }
    
    .other-room-name {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        transition: color 0.3s ease;
        line-height: 1.4;
    }
    
    .other-room-card:hover .other-room-name {
        color: #667eea;
    }
    
    .other-room-features {
        display: flex;
        gap: 15px;
        font-size: 13px;
        color: #666;
        margin-top: 10px;
    }
    
    .other-room-feature {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .other-room-feature i {
        color: #667eea;
        font-size: 12px;
    }
    
    .other-room-view-btn {
        margin-top: 15px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(10px);
    }
    
    .other-room-card:hover .other-room-view-btn {
        opacity: 1;
        transform: translateY(0);
    }
    
    .other-room-view-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
        .room-content {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .booking-panel {
            position: static;
            max-height: none;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .amenities-grid {
            grid-template-columns: 1fr;
        }
        
        .main-image-container {
            height: 300px;
        }
        
        .other-rooms-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
    }
    
    @media (max-width: 576px) {
        .other-rooms-grid {
            grid-template-columns: 1fr;
        }
        
        .other-rooms-section h2 {
            font-size: 24px;
        }
    }
</style>

<div class="room-detail-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> <?php _e('rooms.home'); ?></a>
        <i class="fas fa-chevron-right" style="margin: 0 10px; font-size: 12px;"></i>
        <span><?= htmlspecialchars($room['room_type_name']) ?></span>
    </div>
    
    <!-- Flash Message -->
    <?php $flash = getFlashMessage(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: <?= $flash['type'] == 'error' ? '#f8d7da' : '#d4edda' ?>; color: <?= $flash['type'] == 'error' ? '#721c24' : '#155724' ?>;">
            <i class="fas fa-<?= $flash['type'] == 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
    
    <!-- Image Gallery -->
    <?php if (!empty($images) && count($images) > 0): ?>
    <div class="image-gallery">
        <div class="main-image-container">
            <img src="<?= htmlspecialchars($images[0]['image_path'] ?? 'assets/images/default-room.jpg') ?>" 
                 alt="<?= htmlspecialchars($room['room_type_name']) ?>" 
                 class="main-image" 
                 id="mainImage"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22800%22 height=%22500%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2220%22 text-anchor=%22middle%22 x=%22400%22 y=%22250%22%3ENo Image Available%3C/text%3E%3C/svg%3E'">
            
            <?php if (count($images) > 1): ?>
            <div class="gallery-nav">
                <button id="prevBtn" onclick="changeImage(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="nextBtn" onclick="changeImage(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <div class="image-counter">
                <span id="currentIndex">1</span> / <?= count($images) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($images) > 1): ?>
        <div class="thumbnails-container">
            <?php foreach ($images as $index => $image): ?>
            <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                 onclick="showImage(<?= $index ?>)"
                 data-index="<?= $index ?>">
                <img src="<?= htmlspecialchars($image['image_path']) ?>" 
                     alt="Image <?= $index + 1 ?>"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%2280%22%3E%3Crect fill=%22%23ddd%22 width=%22120%22 height=%2280%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2212%22 text-anchor=%22middle%22 x=%2260%22 y=%2245%22%3ENo Image%3C/text%3E%3C/svg%3E'">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Fallback: แสดงรูป default ถ้าไม่มีรูปในระบบ -->
    <div class="image-gallery">
        <div class="main-image-container">
            <img src="assets/images/default-room.jpg" 
                 alt="<?= htmlspecialchars($room['room_type_name']) ?>" 
                 class="main-image"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22800%22 height=%22500%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2224%22 text-anchor=%22middle%22 x=%22400%22 y=%22240%22%3E%F0%9F%8F%A8 No Image Available%3C/text%3E%3Ctext fill=%22%23aaa%22 font-family=%22Arial%22 font-size=%2216%22 text-anchor=%22middle%22 x=%22400%22 y=%22270%22%3EPlease upload room images in admin panel%3C/text%3E%3C/svg%3E'">
        </div>
    </div>
    <?php endif; ?>


    <div class="room-content">
        <div class="room-main">
            <h1 class="room-title"><?= htmlspecialchars($room['room_type_name']) ?></h1>
            
            <p class="room-description">
                <?php
                // รองรับหลายภาษา: ตรวจสอบว่ามี description_th/en/zh หรือไม่
                $currentLang = getCurrentLanguage();
                $roomDescription = '';
                
                if ($currentLang === 'th' && !empty($room['description_th'])) {
                    $roomDescription = $room['description_th'];
                } elseif ($currentLang === 'en' && !empty($room['description_en'])) {
                    $roomDescription = $room['description_en'];
                } elseif ($currentLang === 'zh' && !empty($room['description_zh'])) {
                    $roomDescription = $room['description_zh'];
                } elseif (!empty($room['description'])) {
                    // ถ้าไม่มี description_th/en/zh แต่มี description เดิม ให้ใช้
                    $roomDescription = $room['description'];
                } else {
                    // ถ้าไม่มีเลย ให้ใช้ default
                    $roomDescription = __('home.comfortable_room');
                }
                ?>
                <?= nl2br(htmlspecialchars($roomDescription)) ?>
            </p>
            
            <!-- Features -->
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-expand-arrows-alt"></i>
                    <div>
                        <strong><?= $room['size_sqm'] ?? 35 ?> <?php _e('rooms.sqm'); ?></strong>
                        <div style="font-size: 13px; color: #999;"><?php _e('rooms.room_size'); ?></div>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong><?php _e('rooms.max'); ?> <?= $room['max_occupancy'] ?> <?php _e('home.people'); ?></strong>
                        <div style="font-size: 13px; color: #999;"><?php _e('rooms.max_occupancy_label'); ?></div>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-bed"></i>
                    <div>
                        <?php
                        // รองรับหลายภาษา: ตรวจสอบว่ามี bed_type_th/en/zh หรือไม่
                        // ใช้ $currentLang ที่ประกาศไว้แล้วด้านบน
                        $bedType = '';
                        if ($currentLang === 'th' && !empty($room['bed_type_th'])) {
                            $bedType = $room['bed_type_th'];
                        } elseif ($currentLang === 'en' && !empty($room['bed_type_en'])) {
                            $bedType = $room['bed_type_en'];
                        } elseif ($currentLang === 'zh' && !empty($room['bed_type_zh'])) {
                            $bedType = $room['bed_type_zh'];
                        } elseif (!empty($room['bed_type'])) {
                            // ถ้าไม่มี bed_type_th/en/zh แต่มี bed_type เดิม ให้ใช้
                            $bedType = $room['bed_type'];
                        } else {
                            // ถ้าไม่มีเลย ให้ใช้ default
                            $bedType = __('rooms.king_bed');
                        }
                        ?>
                        <strong><?= htmlspecialchars($bedType) ?></strong>
                        <div style="font-size: 13px; color: #999;"><?php _e('rooms.bed_type_label'); ?></div>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-door-open"></i>
                    <div>
                        <strong><?= $room['current_availability'] ?? __('rooms.not_specified') ?> <?php _e('common.rooms'); ?></strong>
                        <div style="font-size: 13px; color: #999;"><?php _e('rooms.total_rooms_label'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Amenities -->
            <?php if (!empty($roomAmenities)): ?>
            <div class="amenities-section">
                <h3>
                    <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                    <?php _e('home.amenities'); ?>
                </h3>
                <div class="amenities-grid">
                    <?php foreach ($roomAmenities as $amenity): ?>
                        <!--
                        print_r($amenity);
                    -->
                        <div class="amenity-item">
                            <i class="fas fa-check"></i>
                            <span><?= htmlspecialchars($amenity) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="booking-panel">
        <h3>
                <i class="fas fa-calendar-check"></i> <?php _e('rooms.book_room'); ?>
            </h3>
            
            <!-- Price Badge -->
            <div class="price-badge">
                <div class="price-label white-text"><?php _e('rooms.starting_price'); ?></div>
                <?php
                // ดึงราคาจาก daily rates หรือ base price
                $priceInfo = $priceCalculator->getSimplePrice($room_type_id);
                $displayPrice = $priceInfo ? $priceInfo['from_price'] : $room['base_price'];
                ?>
                <div class="price-amount white-text">฿<?= number_format($displayPrice, 0) ?></div>
                <div style="font-size: 13px; opacity: 0.9; margin-top: 5px;"><?php _e('rooms.per_night'); ?></div>
            </div>
            
            <!-- Breakfast Info -->
            <?php if ($room['breakfast_included']): ?>
                <div class="breakfast-info">
                    <i class="fas fa-check-circle"></i>
                    <strong><?php _e('rooms.breakfast_included_price'); ?></strong>
                </div>
            <?php else: ?>
                <div class="breakfast-info not-included">
                    <i class="fas fa-info-circle"></i>
                    <strong><?php _e('rooms.breakfast_not_included'); ?></strong>
                    <?php if ($room['breakfast_price'] > 0): ?>
                        <div style="margin-top: 5px;">
                            <?php printf(__('rooms.add_breakfast_per_person'), number_format($room['breakfast_price'], 0)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="GET" action="booking.php" id="bookingForm">
                <input type="hidden" name="room_type_id" value="<?= $room_type_id ?>">
                
                <!-- Check-in Date -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-alt"></i> <?php _e('rooms.check_in_date'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="date" 
                           name="check_in" 
                           id="check_in"
                           min="<?= $today ?>"
                           value="<?= htmlspecialchars($check_in) ?>"
                           required>
                </div>
                
                <!-- Check-out Date -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-alt"></i> <?php _e('rooms.check_out_date'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="date" 
                           name="check_out" 
                           id="check_out"
                           min="<?= $tomorrow ?>"
                           value="<?= htmlspecialchars($check_out) ?>"
                           required>
                </div>
                
                <!-- Adults -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i> <?php _e('rooms.adults'); ?> <span style="color: red;">*</span>
                    </label>
                    <select name="adults" id="adults" required>
                        <?php for ($i = 1; $i <= $room['max_occupancy']; $i++): ?>
                            <option value="<?= $i ?>" <?= $adults == $i ? 'selected' : '' ?>>
                                <?= $i ?> <?php _e('common.people'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- Children -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-child"></i> <?php _e('rooms.children'); ?>
                    </label>
                    <select name="children" id="children">
                        <?php for ($i = 0; $i <= 3; $i++): ?>
                            <option value="<?= $i ?>" <?= $children == $i ? 'selected' : '' ?>>
                                <?= $i ?> <?php _e('common.people'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>


                <div class="form-group">
                    <label>
                        <i class="fas fa-door-open"></i> <?php _e('rooms.number_of_rooms'); ?> <span style="color: red;">*</span>
                    </label>
                    <select name="rooms" id="rooms" required>
                        <?php 
                        $maxRooms = min($room['current_availability'] ?? 0, 10);
                        if ($maxRooms <= 0) {
                            echo '<option value="0" disabled>'.__('rooms.no_rooms_available_today').'</option>';
                        } else {
                            for ($i = 1; $i <= $maxRooms; $i++): 
                        ?>
                            <option value="<?= $i ?>" <?= ($rooms ?? 1) == $i ? 'selected' : '' ?>>
                                <?= $i ?> <?php _e('common.rooms'); ?>
                            </option>
                        <?php 
                            endfor;
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-book" <?= ($maxRooms <= 0) ? 'disabled' : '' ?>>
                    <i class="fas fa-calendar-check"></i> <?php _e('rooms.book_now'); ?>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Other Rooms Section -->
    <?php if (!empty($otherRoomTypes)): ?>
    <div class="other-rooms-section">
        <h2>
            <i class="fas fa-door-open"></i> <?php echo function_exists('__') && defined('LANG') ? (LANG === 'th' ? 'ห้องพักอื่นๆ' : (LANG === 'zh' ? '其他房间' : 'Other Rooms')) : 'Other Rooms'; ?>
        </h2>
        <div class="other-rooms-grid">
            <?php foreach ($otherRoomTypes as $otherRoom): 
                $otherRoomImage = $hotel->getFeaturedImage($otherRoom['room_type_id']);
                $otherRoomImagePath = $otherRoomImage ? $otherRoomImage['image_path'] : 'assets/images/default-room.jpg';
            ?>
            <a href="room_detail.php?room_type_id=<?= $otherRoom['room_type_id'] ?>" class="other-room-card">
                <div class="other-room-image-wrapper">
                    <img src="<?= htmlspecialchars($otherRoomImagePath) ?>" 
                         alt="<?= htmlspecialchars($otherRoom['room_type_name']) ?>"
                         class="other-room-image"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22250%22 height=%22200%22/%3E%3Ctext fill=%22%23999%22 font-family=%22Arial%22 font-size=%2214%22 text-anchor=%22middle%22 x=%22125%22 y=%22100%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                    <div class="other-room-overlay"></div>
                    <div class="other-room-price-badge">
                        ฿<?= number_format($otherRoom['base_price'], 0) ?>/<?php _e('common.night'); ?>
                    </div>
                    <?php if (!empty($otherRoom['breakfast_included']) && $otherRoom['breakfast_included'] == 1): ?>
                    <div class="other-room-breakfast-badge">
                        <i class="fas fa-utensils"></i>
                        <span><?php _e('home.breakfast_included'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="other-room-info">
                    <h3 class="other-room-name"><?= htmlspecialchars($otherRoom['room_type_name']) ?></h3>
                    <div class="other-room-features">
                        <?php if (!empty($otherRoom['size_sqm'])): ?>
                        <div class="other-room-feature">
                            <i class="fas fa-expand-arrows-alt"></i>
                            <span><?= $otherRoom['size_sqm'] ?> <?php _e('rooms.sqm'); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($otherRoom['max_occupancy'])): ?>
                        <div class="other-room-feature">
                            <i class="fas fa-users"></i>
                            <span><?= $otherRoom['max_occupancy'] ?> <?php _e('home.people'); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="other-room-view-btn">
                        <i class="fas fa-eye"></i> <?php _e('common.view_details'); ?>
                    </button>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// ===== IMAGE GALLERY =====
<?php if (!empty($images) && count($images) > 1): ?>
const images = <?= json_encode(array_map(function($img) { return $img['image_path']; }, $images)) ?>;
let currentImageIndex = 0;

function showImage(index) {
    if (index < 0 || index >= images.length) return;
    
    currentImageIndex = index;
    
    // Update main image
    document.getElementById('mainImage').src = images[index];
    
    // Update counter
    document.getElementById('currentIndex').textContent = index + 1;
    
    // Update thumbnail active state
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        if (i === index) {
            thumb.classList.add('active');
        } else {
            thumb.classList.remove('active');
        }
    });
    
    // Update navigation buttons
    document.getElementById('prevBtn').disabled = index === 0;
    document.getElementById('nextBtn').disabled = index === images.length - 1;
}

function changeImage(direction) {
    const newIndex = currentImageIndex + direction;
    if (newIndex >= 0 && newIndex < images.length) {
        showImage(newIndex);
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
        changeImage(-1);
    } else if (e.key === 'ArrowRight') {
        changeImage(1);
    }
});

// Initialize
showImage(0);
<?php endif; ?>

// ===== BOOKING FORM VALIDATION =====
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    
    if (!checkIn || !checkOut) {
        e.preventDefault();
        alert('<?php echo addslashes(__('rooms.please_select_dates')); ?>');
        return;
    }
    
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    
    if (checkOutDate <= checkInDate) {
        e.preventDefault();
        alert('<?php echo addslashes(__('rooms.checkout_after_checkin')); ?>');
        return;
    }
    
    // Disable button to prevent double submit
    const btn = this.querySelector('.btn-book');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo addslashes(__('rooms.processing')); ?>';
});

// Update check-out min date when check-in changes
document.getElementById('check_in').addEventListener('change', function() {
    const checkInDate = new Date(this.value);
    checkInDate.setDate(checkInDate.getDate() + 1);
    const minCheckOut = checkInDate.toISOString().split('T')[0];
    document.getElementById('check_out').min = minCheckOut;
});
</script>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
