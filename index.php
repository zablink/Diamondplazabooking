<?php
/**
 * booking/index.php
 * หน้าแรกระบบจองห้องพัก - แสดงรายการห้องพักทั้งหมดพร้อม Featured Image
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
require_once PROJECT_ROOT . '/includes/PriceCalculator.php';

$page_title = __('home.our_rooms') . ' - ' . SITE_NAME;

// Initialize classes
$hotel = new Hotel();
$roomImage = new RoomImage();
$priceCalculator = new PriceCalculator();

// ดึงรายการห้องพักทั้งหมดที่พร้อมใช้งาน
$roomTypes = $hotel->getRoomTypes(HOTEL_ID, 'available');

// ดึง flash message (ถ้ามี)
$flash = getFlashMessage();

require_once PROJECT_ROOT . '/includes/header.php';
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ... CSS styles remain the same ... */
    </style>
</head>
<body>
    <!-- ... Hero Section, Main Content, Search Box, Stats Bar, Section Header ... -->

        <!-- Rooms Grid -->
        <?php if (!empty($roomTypes)): ?>
            <div class="rooms-grid">
                <?php foreach ($roomTypes as $room): ?>
                    <?php
                    // ... (get featured image and amenities)
                    $featuredImage = $roomImage->getFeaturedImage($room['room_type_id']);
                    $amenitiesData = $hotel->getTranslatedAmenities($room['room_type_id']);
                    $amenities = array_slice($amenitiesData, 0, 4);
                    ?>
                    <div class="room-card">
                        <!-- ... Room Image ... -->

                        <!-- Room Content -->
                        <div class="room-content">
                            <!-- ... Room Header, Description ... -->

                            <!-- [REVISED] Room Meta -->
                            <div class="room-meta">
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <span><?= $room['max_occupancy'] ?> <?php _e('home.people'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-door-open"></i>
                                    <span><?= $room['current_availability'] ?? 0 ?> <?php _e('home.available_rooms'); ?></span>
                                </div>
                                <?php if ($room['breakfast_included']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-utensils"></i>
                                    <span><?php _e('home.breakfast_included'); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ... Amenities Preview, Room Footer ... -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- ... Empty State ... -->
        <?php endif; ?>

        <!-- ... Info Section ... -->

    </div>

    <script>
        // ... JavaScript remains the same ...
    </script>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
