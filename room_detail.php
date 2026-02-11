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
$images = $roomImage->getRoomImages($room_type_id);
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
    <!-- ... Breadcrumb, Flash Message, Image Gallery ... -->
    
    <div class="room-content">
        <div class="room-main">
            <!-- ... Room Title, Description ... -->
            
            <div class="features-grid">
                <!-- ... Room Size, Max Occupancy, Bed Type ... -->
                <div class="feature-item">
                    <i class="fas fa-door-open"></i>
                    <div>
                        <strong><?= $room['current_availability'] ?? 0 ?> <?php _e('common.rooms'); ?></strong>
                        <div style="font-size: 13px; color: #999;"><?php _e('rooms.available_today'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- ... Amenities Section ... -->
        </div>
        
        <div class="booking-panel">
            <!-- ... Price Badge, Breakfast Info ... -->
            
            <form method="GET" action="booking.php" id="bookingForm">
                <input type="hidden" name="room_type_id" value="<?= $room_type_id ?>">
                
                <!-- ... Check-in, Check-out, Adults, Children ... -->
                
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
    
    <!-- ... Other Rooms Section ... -->
</div>

<script>
    // ... JavaScript ...
</script>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
