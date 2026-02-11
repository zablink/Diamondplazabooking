<?php
/**
 * booking/index.php.  
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
        /* ... (styles remain unchanged) ... */
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1>
                <i class="fas fa-hotel"></i>
                <?php _e('home.hero_title'); ?>
            </h1>
            <p><?php _e('home.hero_subtitle'); ?></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- ... (Flash Message and Search Box remain unchanged) ... -->

        <!-- Stats Bar -->
        <?php if (!empty($roomTypes)): ?>
        <div class="stats-bar">
            <div class="stat-item">
                <h4><?= count($roomTypes) ?></h4>
                <p><?php _e('home.room_types'); ?></p>
            </div>
            <div class="stat-item">
                <h4><?= array_sum(array_column($roomTypes, 'current_availability')) ?></h4>
                <p><?php _e('home.total_rooms'); ?></p>
            </div>
            <div class="stat-item">
                <?php
                $minPrices = [];
                foreach ($roomTypes as $room) {
                    $priceInfo = $priceCalculator->getSimplePrice($room['room_type_id']);
                    $minPrices[] = $priceInfo ? $priceInfo['from_price'] : $room['base_price'];
                }
                $minPrice = !empty($minPrices) ? min($minPrices) : 0;
                ?>
                <h4>฿<?= number_format($minPrice) ?>+</h4>
                <p><?php _e('home.starting_price'); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section Header -->
        <div class="section-header">
            <h2>
                <i class="fas fa-bed"></i> <?php _e('home.our_rooms'); ?>
            </h2>
            <p><?php _e('home.select_room_for_you'); ?></p>
        </div>

        <!-- Rooms Grid -->
        <?php if (!empty($roomTypes)): ?>
            <div class="rooms-grid">
                <?php foreach ($roomTypes as $room): ?>
                    <?php
                    $featuredImage = $roomImage->getFeaturedImage($room['room_type_id']);
                    // ดึง amenities พร้อมคำแปลและไอคอนล่าสุด
                    $roomAmenities = $hotel->getTranslatedAmenities($room['room_type_id']);
                    $amenities_to_show = array_slice($roomAmenities, 0, 4);
                    $currentLang = getCurrentLanguage();
                    $amenityNameCol = 'amenity_name_' . $currentLang;
                    ?>
                    <div class="room-card">
                        <!-- Room Image -->
                        <div class="room-image">
                            <?php if ($featuredImage): ?>
                                <img src="<?= SITE_URL . '/' . htmlspecialchars($featuredImage['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($room['room_type_name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="room-image-placeholder">
                                    <i class="fas fa-image"></i>
                                    <p><?php _e('home.no_image'); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge -->
                            <div class="status-badge available">
                                <i class="fas fa-check-circle"></i> <?php _e('home.available'); ?>
                            </div>
                            
                            <!-- Breakfast Badge -->
                            <?php if (!empty($room['breakfast_included']) && $room['breakfast_included'] == 1): ?>
                            <div class="breakfast-badge">
                                <i class="fas fa-utensils"></i>
                                <span><?php _e('home.breakfast_included'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Room Content -->
                        <div class="room-content">
                            <div class="room-header">
                                <h3 class="room-name"><?= htmlspecialchars($room['room_type_name']) ?></h3>
                            </div>

                            <p class="room-description">
                                <?= htmlspecialchars($room['description'] ?? __('home.comfortable_room')) ?>
                            </p>

                            <!-- Room Meta -->
                            <div class="room-meta">
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <span><?= $room['max_occupancy'] ?> <?php _e('home.people'); ?></span>
                                </div>
                                <?php if (isset($room['current_availability'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-door-open"></i>
                                    <span><?= $room['current_availability'] ?? 0 ?> <?php _e('home.rooms'); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($room['breakfast_included']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-utensils"></i>
                                    <span><?php _e('home.breakfast_included'); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Amenities Preview -->
                            <?php if (!empty($roomAmenities)): ?>
                            <div class="amenities-preview">
                                <?php foreach ($amenities_to_show as $amenity): ?>
                                    <span class="amenity-tag">
                                        <?php if (!empty($amenity['icon'])): ?>
                                            <i class="<?= htmlspecialchars($amenity['icon']) ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-check"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($amenity[$amenityNameCol] ?? $amenity['amenity_name']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($roomAmenities) > 4): ?>
                                    <span class="amenity-tag">
                                        <i class="fas fa-plus"></i>
                                        +<?= count($roomAmenities) - 4 ?> <?php _e('home.more'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Room Footer -->
                            <div class="room-footer">
                                <div class="room-price">
                                    <span class="price-label"><?php _e('home.starting_from'); ?></span>
                                    <div>
                                        <?php
                                        $priceInfo = $priceCalculator->getSimplePrice($room['room_type_id']);
                                        $displayPrice = $priceInfo ? $priceInfo['from_price'] : $room['base_price'];
                                        ?>
                                        <span class="price-amount">฿<?= number_format($displayPrice, 0) ?></span>
                                        <span class="price-unit"><?php _e('home.per_night'); ?></span>
                                    </div>
                                </div>
                                <a href="<?php echo url('room_detail.php', ['id' => $room['room_type_id']]); ?>" class="btn-book">
                                    <i class="fas fa-arrow-right"></i> <?php _e('home.view_details'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-bed"></i>
                <h3><?php _e('home.no_rooms_available'); ?></h3>
                <p><?php _e('home.contact_us_for_more_info'); ?></p>
            </div>
        <?php endif; ?>

        <!-- ... (Info Section and Scripts remain unchanged) ... -->

    </div>

    <script>
        // ... (JavaScript remains unchanged) ...
    </script>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
