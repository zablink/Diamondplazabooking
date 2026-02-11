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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .hero-section p {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 2rem;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: -40px auto 60px;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        /* Search Box */
        .search-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
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

        .btn-search {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            font-size: 14px;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        /* Section Header */
        .section-header {
            margin-bottom: 30px;
        }

        .section-header h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }

        .section-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Room Cards Grid */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .room-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        /* Room Image */
        .room-image {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .room-card:hover .room-image img {
            transform: scale(1.1);
        }

        .room-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.9);
        }

        .room-image-placeholder i {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.7;
        }

        .room-image-placeholder p {
            font-size: 14px;
            opacity: 0.8;
        }

        /* Status Badge */
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            z-index: 1;
        }

        .status-badge.available {
            background: rgba(39, 174, 96, 0.9);
            color: white;
        }

        .status-badge.limited {
            background: rgba(241, 196, 15, 0.9);
            color: white;
        }

        .breakfast-badge {
            position: absolute;
            bottom: 15px;
            left: 15px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            z-index: 1;
            background: rgba(39, 174, 96, 0.9);
            color: white;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breakfast-badge i {
            font-size: 12px;
        }

        /* Room Content */
        .room-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .room-header {
            margin-bottom: 15px;
        }

        .room-name {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .room-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Room Meta */
        .room-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            align-items: flex-start;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            color: #666;
            font-size: 14px;
            text-align: center;
            min-width: 60px;
        }

        .meta-item i {
            color: #667eea;
            font-size: 16px;
            line-height: 1;
        }

        /* Amenities Preview */
        .amenities-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .amenity-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: #f0f4ff;
            color: #667eea;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .amenity-tag i {
            font-size: 12px;
        }

        /* Room Footer */
        .room-footer {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: auto;
        }

        .room-price {
            display: flex;
            flex-direction: column;
        }

        .price-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 4px;
        }

        .price-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
        }

        .price-unit {
            font-size: 14px;
            color: #999;
            margin-left: 4px;
        }

        .btn-book {
            padding: 12px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .room-footer .btn-book {
            width: 100%;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
        }

        /* Flash Messages */
        .flash-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .flash-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .flash-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .flash-message i {
            font-size: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .rooms-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .room-footer {
                align-items: stretch;
            }

            .btn-book {
                width: 100%;
                text-align: center;
            }
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 60px 20px;
        }

        .loading i {
            font-size: 48px;
            color: #667eea;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Info Section */
        .info-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-top: 40px;
        }

        .info-section h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
        }

        .info-section p {
            color: #666;
            line-height: 1.8;
        }

        /* Stats Counter */
        .stats-bar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item h4 {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-item p {
            color: #666;
            font-size: 14px;
        }
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
