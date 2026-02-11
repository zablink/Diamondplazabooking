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

// รับ room_type_id จาก URL
$room_type_id = $_GET['room_type_id'] ?? $_GET['id'] ?? null;

if (!$room_type_id) {
    setFlashMessage(__('rooms.room_not_found'), 'error');
    redirect('index.php');
}

// โหลดข้อมูลห้องพัก
$hotel = new Hotel();
$priceCalculator = new PriceCalculator();
$room = $hotel->getRoomTypeById($room_type_id);

if (!$room) {
    setFlashMessage(__('rooms.room_not_found'), 'error');
    redirect('index.php');
}

// โหลดรูปภาพทั้งหมด
$images = $hotel->getRoomImages($room_type_id);
$featuredImage = $hotel->getFeaturedImage($room_type_id);

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

// ... (รับค่าจาก URL และตั้งค่าวันที่)

$page_title = htmlspecialchars($room['room_type_name']) . ' - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
?>

<style>
    /* ... CSS ... */
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