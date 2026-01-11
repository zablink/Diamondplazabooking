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
require_once PROJECT_ROOT . '/includes/AdvancedPriceCalculator.php';
require_once PROJECT_ROOT . '/modules/hotel/Hotel.php';

// Get room type ID
$roomTypeId = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;

if (!$roomTypeId) {
    header('Location: index.php');
    exit();
}

$hotelObj = new Hotel();
$calculator = new AdvancedPriceCalculator();

// Get room type details
$room = $hotelObj->getRoomTypeById($roomTypeId);

if (!$room) {
    header('Location: index.php');
    exit();
}

$hotel = $hotelObj->getHotelById($room['hotel_id']);
$roomAmenities = parseJSON($room['amenities']);

// Handle price calculation
$priceResult = null;
$availableRooms = 0;
$showResults = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_price'])) {
    $params = [
        'room_type_id' => $roomTypeId,
        'check_in' => $_POST['check_in'] ?? '',
        'check_out' => $_POST['check_out'] ?? '',
        'adults' => (int)($_POST['adults'] ?? 1),
        'children' => (int)($_POST['children'] ?? 0),
        'num_rooms' => (int)($_POST['num_rooms'] ?? 1),
        'include_breakfast' => isset($_POST['include_breakfast'])
    ];
    
    $priceResult = $calculator->calculatePrice($params);
    
    if ($priceResult['success']) {
        $availableRooms = $calculator->getAvailableRooms(
            $roomTypeId,
            $params['check_in'],
            $params['check_out']
        );
        $showResults = true;
    }
}

// Set page title
$page_title = htmlspecialchars($room['room_type_name']) . ' - ' . __('rooms.room_details');

// Include header
include './includes/header.php';
?>

<style>
    .room-detail-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .breadcrumb {
        margin-bottom: 30px;
        display: flex;
        gap: 10px;
        align-items: center;
        color: var(--text-secondary);
    }

    .breadcrumb a {
        color: var(--primary-color);
        text-decoration: none;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    .room-detail-grid {
        display: grid;
        grid-template-columns: 1fr 450px;
        gap: 30px;
        margin-bottom: 40px;
    }

    .room-info-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .room-image-placeholder {
        width: 100%;
        height: 400px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 30px;
    }

    .room-image-placeholder i {
        font-size: 6rem;
        color: white;
        opacity: 0.8;
    }

    .room-title {
        font-size: 2rem;
        color: var(--text-primary);
        margin-bottom: 15px;
    }

    .room-description {
        color: var(--text-secondary);
        line-height: 1.8;
        margin-bottom: 25px;
    }

    .room-features {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: var(--bg-light);
        border-radius: 8px;
    }

    .feature-item i {
        color: var(--primary-color);
        font-size: 1.2rem;
    }

    .amenities-section {
        margin-top: 30px;
    }

    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 15px;
    }

    .amenity-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
    }

    .amenity-item i {
        color: var(--success-color);
    }

    .booking-panel {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        position: sticky;
        top: 20px;
        height: fit-content;
    }

    .booking-panel h3 {
        margin-bottom: 20px;
        color: var(--text-primary);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-primary);
        font-weight: 600;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .guest-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 15px 0;
    }

    .checkbox-group input[type="checkbox"] {
        width: auto;
    }

    .price-summary {
        background: var(--bg-light);
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        color: var(--text-secondary);
    }

    .price-row.total {
        padding-top: 15px;
        border-top: 2px solid #ddd;
        margin-top: 15px;
        font-size: 1.25rem;
        font-weight: bold;
        color: var(--text-primary);
    }

    .price-row .amount {
        color: var(--primary-color);
        font-weight: 600;
    }

    .price-row.total .amount {
        color: var(--success-color);
        font-size: 1.5rem;
    }

    .availability-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .availability-badge.available {
        background: #d4edda;
        color: #155724;
    }

    .availability-badge.limited {
        background: #fff3cd;
        color: #856404;
    }

    .availability-badge.unavailable {
        background: #f8d7da;
        color: #721c24;
    }

    .nightly-breakdown {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
    }

    .nightly-breakdown h4 {
        font-size: 0.9rem;
        margin-bottom: 10px;
        color: var(--text-secondary);
    }

    .nightly-rates {
        max-height: 200px;
        overflow-y: auto;
    }

    .nightly-rate-item {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    @media (max-width: 968px) {
        .room-detail-grid {
            grid-template-columns: 1fr;
        }

        .booking-panel {
            position: relative;
            top: 0;
        }

        .room-features,
        .amenities-grid,
        .guest-selector {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="room-detail-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php"><?php _e('nav.home'); ?></a>
        <span>/</span>
        <span><?php echo htmlspecialchars($room['room_type_name']); ?></span>
    </div>

    <div class="room-detail-grid">
        <!-- Room Information -->
        <div class="room-info-section">
            <!-- Room Image -->
            <div class="room-image-placeholder">
                <i class="fas fa-bed"></i>
            </div>

            <!-- Room Title & Description -->
            <h1 class="room-title"><?php echo htmlspecialchars($room['room_type_name']); ?></h1>
            
            <p class="room-description">
                <?php echo nl2br(htmlspecialchars($room['description'])); ?>
            </p>

            <!-- Room Features -->
            <div class="room-features">
                <div class="feature-item">
                    <i class="fas fa-ruler-combined"></i>
                    <div>
                        <strong><?php echo __('hotel.size', ['size' => $room['size_sqm']]); ?></strong>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong><?php echo __('hotel.max_occupancy', ['count' => $room['max_occupancy']]); ?></strong>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-bed"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($room['bed_type']); ?></strong>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-door-open"></i>
                    <div>
                        <strong><?php echo $room['total_rooms']; ?> <?php _e('common.rooms'); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Amenities -->
            <?php if (!empty($roomAmenities)): ?>
            <div class="amenities-section">
                <h3>
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                    <?php _e('hotel.amenities'); ?>
                </h3>
                <div class="amenities-grid">
                    <?php foreach ($roomAmenities as $amenity): ?>
                    <div class="amenity-item">
                        <i class="fas fa-check"></i>
                        <span><?php echo htmlspecialchars($amenity); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Hotel Information -->
            <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid var(--bg-light);">
                <h3 style="margin-bottom: 15px;"><?php _e('confirmation.hotel_info'); ?></h3>
                <p><strong><?php echo htmlspecialchars($hotel['hotel_name']); ?></strong></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hotel['address'] . ', ' . $hotel['city']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($hotel['phone']); ?></p>
            </div>
        </div>

        <!-- Booking Panel -->
        <div class="booking-panel">
            <h3><i class="fas fa-calendar-check"></i> <?php _e('rooms.check_availability'); ?></h3>

            <?php if ($showResults && !$priceResult['success']): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($priceResult['message']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Check-in Date -->
                <div class="form-group">
                    <label><?php _e('home.check_in'); ?> <span style="color: red;">*</span></label>
                    <input type="date" 
                           name="check_in" 
                           id="check_in"
                           value="<?php echo $_POST['check_in'] ?? ''; ?>"
                           required>
                </div>

                <!-- Check-out Date -->
                <div class="form-group">
                    <label><?php _e('home.check_out'); ?> <span style="color: red;">*</span></label>
                    <input type="date" 
                           name="check_out" 
                           id="check_out"
                           value="<?php echo $_POST['check_out'] ?? ''; ?>"
                           required>
                </div>

                <!-- Guests -->
                <div class="form-group">
                    <label><?php _e('home.guests'); ?> <span style="color: red;">*</span></label>
                    <div class="guest-selector">
                        <div>
                            <label style="font-weight: 400; font-size: 0.9rem;"><?php _e('common.adults'); ?></label>
                            <select name="adults" required>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_POST['adults']) && $_POST['adults'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight: 400; font-size: 0.9rem;"><?php _e('common.children'); ?></label>
                            <select name="children">
                                <?php for ($i = 0; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($_POST['children']) && $_POST['children'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Number of Rooms -->
                <div class="form-group">
                    <label><?php _e('common.rooms'); ?></label>
                    <select name="num_rooms">
                        <?php for ($i = 1; $i <= min(5, $room['total_rooms']); $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($_POST['num_rooms']) && $_POST['num_rooms'] == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?> <?php echo $i == 1 ? __('common.room') : __('common.rooms'); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Breakfast Option -->
                <div class="checkbox-group">
                    <input type="checkbox" 
                           name="include_breakfast" 
                           id="include_breakfast"
                           <?php echo isset($_POST['include_breakfast']) ? 'checked' : ''; ?>>
                    <label for="include_breakfast" style="margin: 0; font-weight: 400;">
                        <?php _e('booking.add_breakfast'); ?>
                        <small style="display: block; color: var(--text-secondary); margin-top: 5px;">
                            <?php echo getCurrentLanguage() === 'th' 
                                ? 'ผู้ใหญ่ ฿250/คน/คืน, เด็ก ฿150/คน/คืน' 
                                : 'Adult THB 250/person/night, Child THB 150/person/night'; ?>
                        </small>
                    </label>
                </div>

                <!-- Calculate Button -->
                <button type="submit" name="calculate_price" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-calculator"></i> <?php _e('booking.calculate_price'); ?>
                </button>
            </form>

            <!-- Price Summary -->
            <?php if ($showResults && $priceResult['success']): ?>
                <?php $details = $priceResult['details']; ?>

                <!-- Availability Badge -->
                <?php if ($availableRooms > 0): ?>
                    <div class="availability-badge <?php echo $availableRooms <= 3 ? 'limited' : 'available'; ?>">
                        <i class="fas fa-check-circle"></i> 
                        <?php echo __('rooms.rooms_available', ['count' => $availableRooms]); ?>
                    </div>
                <?php else: ?>
                    <div class="availability-badge unavailable">
                        <i class="fas fa-times-circle"></i> 
                        <?php _e('rooms.no_rooms_available'); ?>
                    </div>
                <?php endif; ?>

                <div class="price-summary">
                    <h4 style="margin-bottom: 15px;"><?php _e('booking.price_breakdown'); ?></h4>

                    <div class="price-row">
                        <span><?php _e('booking.number_of_nights'); ?></span>
                        <span class="amount"><?php echo $details['nights']; ?> <?php _e('common.nights'); ?></span>
                    </div>

                    <div class="price-row">
                        <span><?php _e('booking.number_of_rooms'); ?></span>
                        <span class="amount"><?php echo $details['num_rooms']; ?> <?php echo $details['num_rooms'] == 1 ? __('common.room') : __('common.rooms'); ?></span>
                    </div>

                    <div class="price-row">
                        <span><?php _e('home.guests'); ?></span>
                        <span class="amount">
                            <?php echo $details['adults']; ?> <?php _e('common.adults'); ?>
                            <?php if ($details['children'] > 0): ?>
                                + <?php echo $details['children']; ?> <?php _e('common.children'); ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="price-row" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                        <span><?php _e('booking.room_price_per_night'); ?> (<?php echo getCurrentLanguage() === 'th' ? 'เฉลี่ย' : 'Avg.'; ?>)</span>
                        <span class="amount"><?php echo formatPriceByLang($details['average_per_night']); ?></span>
                    </div>

                    <div class="price-row">
                        <span><?php echo getCurrentLanguage() === 'th' ? 'ยอดรวมห้องพัก' : 'Room Subtotal'; ?></span>
                        <span class="amount"><?php echo formatPriceByLang($details['room_subtotal']); ?></span>
                    </div>

                    <?php if ($details['breakfast_total'] > 0): ?>
                    <div class="price-row">
                        <span><?php _e('booking.breakfast_price'); ?></span>
                        <span class="amount"><?php echo formatPriceByLang($details['breakfast_total']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="price-row">
                        <span><?php echo __('booking.service_charge', ['rate' => ($details['service_charge_rate'] * 100)]); ?></span>
                        <span class="amount"><?php echo formatPriceByLang($details['service_charge']); ?></span>
                    </div>

                    <div class="price-row">
                        <span><?php echo __('booking.vat', ['rate' => ($details['vat_rate'] * 100)]); ?></span>
                        <span class="amount"><?php echo formatPriceByLang($details['vat']); ?></span>
                    </div>

                    <div class="price-row total">
                        <span><?php _e('booking.grand_total'); ?></span>
                        <span class="amount"><?php echo formatPriceByLang($details['grand_total']); ?></span>
                    </div>

                    <!-- Nightly Breakdown -->
                    <div class="nightly-breakdown">
                        <h4><?php echo getCurrentLanguage() === 'th' ? 'รายละเอียดราคาแต่ละคืน' : 'Nightly Rate Breakdown'; ?></h4>
                        <div class="nightly-rates">
                            <?php foreach ($details['nightly_rates'] as $date => $rate): ?>
                            <div class="nightly-rate-item">
                                <span><?php echo formatDateByLang($date); ?></span>
                                <span><?php echo formatPriceByLang($rate); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Book Now Button -->
                <?php if ($availableRooms > 0): ?>
                    <?php if (isLoggedIn()): ?>
                        <form action="booking.php" method="GET" style="margin-top: 20px;">
                            <input type="hidden" name="room_type_id" value="<?php echo $roomTypeId; ?>">
                            <input type="hidden" name="check_in" value="<?php echo $details['check_in']; ?>">
                            <input type="hidden" name="check_out" value="<?php echo $details['check_out']; ?>">
                            <input type="hidden" name="adults" value="<?php echo $details['adults']; ?>">
                            <input type="hidden" name="children" value="<?php echo $details['children']; ?>">
                            <input type="hidden" name="num_rooms" value="<?php echo $details['num_rooms']; ?>">
                            <input type="hidden" name="include_breakfast" value="<?php echo $details['breakfast_included'] ? '1' : '0'; ?>">
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-calendar-check"></i> <?php _e('booking.proceed_booking'); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="login.php?redirect=<?php echo urlencode('room_detail.php?room_type_id=' . $roomTypeId); ?>" 
                           class="btn btn-primary" 
                           style="width: 100%; display: block; text-align: center; margin-top: 20px; text-decoration: none;">
                            <i class="fas fa-sign-in-alt"></i> <?php _e('hotel.login_to_book'); ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Set minimum dates
const today = new Date().toISOString().split('T')[0];
const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];

const checkInInput = document.getElementById('check_in');
const checkOutInput = document.getElementById('check_out');

if (checkInInput && checkOutInput) {
    checkInInput.setAttribute('min', today);
    if (!checkInInput.value) {
        checkInInput.value = today;
    }
    
    checkOutInput.setAttribute('min', tomorrow);
    if (!checkOutInput.value) {
        checkOutInput.value = tomorrow;
    }
    
    checkInInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const nextDay = new Date(selectedDate.getTime() + 86400000);
        checkOutInput.setAttribute('min', nextDay.toISOString().split('T')[0]);
        if (new Date(checkOutInput.value) <= selectedDate) {
            checkOutInput.value = nextDay.toISOString().split('T')[0];
        }
    });
}
</script>

<?php include './includes/footer.php'; ?>