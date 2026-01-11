<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');


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

// ดึงข้อมูลโรงแรม
$hotel = $hotelObj->getHotelById(HOTEL_ID);

if (!$hotel) {
    die('Hotel not found. Please check HOTEL_ID in config.php');
}

// ดึงห้องพักทั้งหมด
$roomTypes = $hotelObj->getRoomTypes(HOTEL_ID);

// ดึงรีวิว
$reviews = $hotelObj->getHotelReviews(HOTEL_ID, 3);

$flashMessage = getFlashMessage();

// ดึงข้อมูลเพิ่มเติม
$amenities = parseJSON($hotel['amenities']);
$images = parseJSON($hotel['images']);

// ตั้งค่า page title
$page_title = htmlspecialchars($hotel['hotel_name']) . ' - ' . SITE_NAME;

// Include header
include './includes/header.php';
?>

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
                <?php if (isset($hotel['avg_rating'])): ?>
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

    <!-- About Hotel -->
    <section class="container" id="about" style="margin: 3rem auto;">
        <div style="background: white; padding: 3rem; border-radius: 12px; box-shadow: var(--shadow);">
            <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                <?php _e('home.about_hotel'); ?>
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
                    <h4><?php _e('home.location'); ?></h4>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;"><?php echo htmlspecialchars($hotel['city']); ?></p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-light); border-radius: 8px;">
                    <i class="fas fa-star" style="font-size: 2rem; color: var(--secondary-color); margin-bottom: 0.5rem;"></i>
                    <h4><?php _e('home.rating'); ?></h4>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;"><?php echo generateStarRating($hotel['star_rating']); ?></p>
                </div>
                <div style="text-align: center; padding: 1.5rem; background: var(--bg-light); border-radius: 8px;">
                    <i class="fas fa-phone" style="font-size: 2rem; color: var(--success-color); margin-bottom: 0.5rem;"></i>
                    <h4><?php _e('home.contact'); ?></h4>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;"><?php echo htmlspecialchars($hotel['phone']); ?></p>
                </div>
            </div>
            
            <?php if (!empty($amenities)): ?>
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--bg-light);">
                <h3 style="font-size: 1.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    <?php _e('home.hotel_amenities'); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <?php foreach ($amenities as $amenity): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary);">
                        <i class="fas fa-check" style="color: var(--success-color);"></i>
                        <span><?php echo htmlspecialchars($amenity); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Available Rooms -->
    <section class="container" id="rooms" style="margin: 3rem auto;">
        <h2 style="font-size: 2.5rem; margin-bottom: 3rem; text-align: center; font-weight: 700;">
            <i class="fas fa-door-open" style="color: var(--primary-color);"></i>
            <?php _e('hotel.our_rooms'); ?>
        </h2>
        
        <?php if (!empty($roomTypes)): ?>
            <div style="display: flex; flex-direction: column; gap: 3rem;">
                <?php 
                foreach ($roomTypes as $room):
                    $roomAmenities = parseJSON($room['amenities']);
                ?>
                <div style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 6px 25px rgba(0,0,0,0.1); transition: all 0.3s ease;" 
                     onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 35px rgba(0,0,0,0.15)';" 
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 25px rgba(0,0,0,0.1)';">
                    
                    <div style="display: grid; grid-template-columns: 400px 1fr; gap: 0; min-height: 350px;">
                        <!-- Room Image Section -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);"></div>
                            <i class="fas fa-bed" style="font-size: 6rem; color: rgba(255,255,255,0.3); position: relative; z-index: 1;"></i>
                            <div style="position: absolute; top: 1.5rem; left: 1.5rem; background: rgba(255,255,255,0.95); padding: 0.75rem 1.5rem; border-radius: 25px; font-weight: bold; color: var(--primary-color); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($room['room_type_name']); ?>
                            </div>
                        </div>
                        
                        <!-- Room Details Section -->
                        <div style="padding: 3rem; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="font-size: 2.2rem; color: var(--text-primary); margin-bottom: 1.5rem; font-weight: 700;">
                                    <?php echo htmlspecialchars($room['room_type_name']); ?>
                                </h3>
                                
                                <p style="color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.8; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($room['description']); ?>
                                </p>
                                
                                <!-- Room Features Grid -->
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);">
                                            <i class="fas fa-ruler-combined" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 0.9rem; color: var(--text-secondary);">ขนาดห้อง</div>
                                            <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?php echo $room['size_sqm']; ?> ตร.ม.</div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);">
                                            <i class="fas fa-users" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 0.9rem; color: var(--text-secondary);">จำนวนผู้เข้าพัก</div>
                                            <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;">สูงสุด <?php echo $room['max_occupancy']; ?> คน</div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);">
                                            <i class="fas fa-bed" style="color: white; font-size: 1.3rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size: 0.9rem; color: var(--text-secondary);">ประเภทเตียง</div>
                                            <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?php echo htmlspecialchars($room['bed_type']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Amenities -->
                                <?php if (!empty($roomAmenities)): ?>
                                <div>
                                    <h4 style="font-size: 1.2rem; color: var(--text-primary); margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-star" style="color: var(--secondary-color);"></i>
                                        <span>สิ่งอำนวยความสะดวก</span>
                                    </h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                                        <?php 
                                        $displayAmenities = array_slice($roomAmenities, 0, 6);
                                        foreach ($displayAmenities as $amenity): 
                                        ?>
                                        <span style="background: var(--bg-light); padding: 0.6rem 1.2rem; border-radius: 25px; font-size: 0.95rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;"
                                              onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white';"
                                              onmouseout="this.style.background='var(--bg-light)'; this.style.color='var(--text-secondary)';">
                                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                                            <?php echo htmlspecialchars($amenity); ?>
                                        </span>
                                        <?php endforeach; ?>
                                        <?php if (count($roomAmenities) > 6): ?>
                                        <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.6rem 1.2rem; border-radius: 25px; font-size: 0.95rem; font-weight: 600; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);">
                                            +<?php echo count($roomAmenities) - 6; ?> เพิ่มเติม
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Bottom Action Bar -->
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 2rem; margin-top: 2rem; border-top: 2px solid var(--bg-light);">
                                <div>
                                    <div style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                                        <span>คลิกเพื่อดูรายละเอียดเพิ่มเติม</span>
                                    </div>
                                    <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                        ราคา พร้อมจองห้อง และดูตัวเลือกห้องพักเพิ่มเติม
                                    </div>
                                </div>
                                
                                <a href="room_detail.php?room_type_id=<?php echo $room['room_type_id']; ?>" 
                                   class="btn btn-primary" 
                                   style="padding: 1.2rem 3rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.75rem; font-size: 1.15rem; font-weight: 700; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s; border-radius: 30px;"
                                   onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 25px rgba(102, 126, 234, 0.6)';"
                                   onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.4)';">
                                    <span>ดูรายละเอียด</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 5rem 2rem; background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <i class="fas fa-bed" style="font-size: 5rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1.5rem;"></i>
                <p style="color: var(--text-secondary); font-size: 1.3rem; font-weight: 500;">
                    <?php _e('hotel.no_rooms'); ?>
                </p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Reviews Section -->
    <?php if (!empty($reviews)): ?>
    <section class="container" id="reviews" style="margin: 3rem auto;">
        <h2 style="font-size: 2rem; margin-bottom: 2rem; text-align: center;">
            <i class="fas fa-comments" style="color: var(--primary-color);"></i>
            <?php _e('home.customer_reviews'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <?php foreach ($reviews as $review): ?>
            <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <img src="<?php echo getUserAvatar($review['email']); ?>" 
                         alt="<?php echo htmlspecialchars($review['first_name']); ?>"
                         style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"
                         onerror="this.src='assets/images/default-avatar.png'">
                    <div>
                        <div style="font-weight: bold; color: var(--text-primary);">
                            <?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1)); ?>.
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                            <?php echo formatDateByLang($review['created_at']); ?>
                        </div>
                    </div>
                    <div style="margin-left: auto;">
                        <?php echo generateStarRating($review['rating']); ?>
                    </div>
                </div>
                
                <?php if ($review['title']): ?>
                    <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($review['title']); ?>
                    </h4>
                <?php endif; ?>
                
                <p style="color: var(--text-secondary); line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

<?php include './includes/footer.php'; ?>