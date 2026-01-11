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
require_once PROJECT_ROOT . '/includes/PriceCalculator.php';

$calculator = new PriceCalculator();
$db = Database::getInstance();
$conn = $db->getConnection();

// ดึงข้อมูล room types
$stmt = $conn->query("SELECT * FROM bk_room_types WHERE status = 'active'");
$roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตัวแปรสำหรับเก็บผลลัพธ์การคำนวณ
$priceResult = null;
$selectedRoomType = null;

// คำนวณราคาเมื่อมีการ submit form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['room_type_id'])) {
    $roomTypeId = $_GET['room_type_id'];
    $checkIn = $_GET['check_in'] ?? date('Y-m-d');
    $checkOut = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
    $includeBreakfast = isset($_GET['include_breakfast']);
    
    // คำนวณราคา
    $priceResult = $calculator->calculateTotalPrice($roomTypeId, $checkIn, $checkOut, $includeBreakfast);
    
    // ดึงข้อมูล room type ที่เลือก
    $stmt = $conn->prepare("SELECT * FROM bk_room_types WHERE room_type_id = :id");
    $stmt->execute(['id' => $roomTypeId]);
    $selectedRoomType = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองห้องพัก - Hotel Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .booking-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .price-breakdown {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .price-breakdown h2 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }
        
        .room-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .room-info h3 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .daily-price {
            border: 1px solid #e0e0e0;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .daily-price:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        
        .daily-price-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .date {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .price {
            font-weight: 700;
            color: #667eea;
            font-size: 1.1rem;
        }
        
        .season-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #d1ecf1;
            color: #0c5460;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .price-details {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1.5rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total-row.grand {
            font-size: 1.5rem;
            font-weight: 700;
            padding-top: 1rem;
            border-top: 2px solid rgba(255,255,255,0.3);
        }
        
        .breakfast-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .breakfast-info i {
            color: #d63031;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-hotel"></i> จองห้องพัก</h1>
            <p>เลือกห้องและช่วงเวลาที่ต้องการ</p>
        </div>
        
        <div class="booking-form">
            <h2>ค้นหาห้องพัก</h2>
            <form method="GET">
                <div class="form-group">
                    <label>เลือกประเภทห้อง <span style="color: red;">*</span></label>
                    <select name="room_type_id" required>
                        <option value="">-- เลือกห้อง --</option>
                        <?php foreach ($roomTypes as $room): ?>
                            <option value="<?= $room['room_type_id'] ?>"
                                    <?= (isset($_GET['room_type_id']) && $_GET['room_type_id'] == $room['room_type_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($room['room_type_name']) ?> 
                                (ราคาเริ่มต้น ฿<?= number_format($room['base_price'], 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>วันที่เช็คอิน <span style="color: red;">*</span></label>
                        <input type="date" 
                               name="check_in" 
                               value="<?= $_GET['check_in'] ?? date('Y-m-d') ?>"
                               min="<?= date('Y-m-d') ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>วันที่เช็คเอาท์ <span style="color: red;">*</span></label>
                        <input type="date" 
                               name="check_out" 
                               value="<?= $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day')) ?>"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               required>
                    </div>
                </div>
                
                <?php if ($selectedRoomType && !$selectedRoomType['breakfast_included']): ?>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" 
                                   name="include_breakfast" 
                                   id="include_breakfast"
                                   <?= isset($_GET['include_breakfast']) ? 'checked' : '' ?>>
                            <label for="include_breakfast" style="margin: 0;">
                                <i class="fas fa-coffee"></i> 
                                เพิ่มอาหารเช้า 
                                <strong>(+฿<?= number_format($selectedRoomType['breakfast_price'], 0) ?>/คืน)</strong>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> คำนวณราคา
                </button>
            </form>
        </div>
        
        <?php if ($priceResult && $priceResult['success']): ?>
            <div class="price-breakdown">
                <h2><i class="fas fa-calculator"></i> รายละเอียดราคา</h2>
                
                <div class="room-info">
                    <h3><?= htmlspecialchars($priceResult['room_type_name']) ?></h3>
                    <p>
                        <i class="fas fa-calendar"></i> 
                        <?= $priceResult['nights'] ?> คืน 
                        (<?= date('d/m/Y', strtotime($priceResult['check_in'])) ?> - 
                        <?= date('d/m/Y', strtotime($priceResult['check_out'])) ?>)
                    </p>
                    <p>
                        <i class="fas fa-money-bill-wave"></i> 
                        ราคาฐาน: ฿<?= number_format($priceResult['base_price'], 0) ?>/คืน
                    </p>
                    
                    <?php if ($priceResult['breakfast_included_in_room']): ?>
                        <div class="breakfast-info">
                            <i class="fas fa-check-circle"></i> 
                            <strong>รวมอาหารเช้าในราคาห้องแล้ว</strong>
                        </div>
                    <?php elseif ($priceResult['breakfast_requested']): ?>
                        <div class="breakfast-info">
                            <i class="fas fa-coffee"></i> 
                            <strong>อาหารเช้า: ฿<?= number_format($priceResult['breakfast_price_per_night'], 0) ?>/คืน</strong>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>หมายเหตุ:</strong> ราคาจะเปลี่ยนแปลงตามช่วงเวลาและฤดูกาล
                </div>
                
                <h3 style="margin: 1.5rem 0 1rem 0;">ราคาแต่ละวัน</h3>
                
                <?php foreach ($priceResult['daily_breakdown'] as $day): ?>
                    <div class="daily-price">
                        <div class="daily-price-header">
                            <span class="date">
                                <i class="fas fa-calendar-day"></i>
                                <?= date('d/m/Y', strtotime($day['date'])) ?>
                                (<?= $day['day_name'] ?>)
                            </span>
                            <span class="price">
                                ฿<?= number_format($day['total'], 0) ?>
                            </span>
                        </div>
                        
                        <div class="price-details">
                            ราคาฐาน: ฿<?= number_format($day['base_price'], 0) ?>
                            
                            <?php if ($day['adjusted_price'] != $day['base_price']): ?>
                                → ฿<?= number_format($day['adjusted_price'], 0) ?>
                                <span style="color: <?= $day['adjusted_price'] > $day['base_price'] ? '#e74c3c' : '#27ae60' ?>">
                                    (<?= $day['adjusted_price'] > $day['base_price'] ? '+' : '' ?><?= number_format($day['adjusted_price'] - $day['base_price'], 0) ?>)
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($day['breakfast_cost'] > 0): ?>
                                + อาหารเช้า ฿<?= number_format($day['breakfast_cost'], 0) ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($day['season'] !== 'Regular Season'): ?>
                            <span class="season-badge">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($day['season']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="total-section">
                    <div class="total-row">
                        <span>จำนวนคืน</span>
                        <span><?= $priceResult['nights'] ?> คืน</span>
                    </div>
                    
                    <div class="total-row grand">
                        <span>ราคารวมทั้งหมด</span>
                        <span>฿<?= number_format($priceResult['total_price'], 0) ?></span>
                    </div>
                    
                    <button class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-check-circle"></i> ยืนยันการจอง
                    </button>
                </div>
            </div>
        <?php elseif ($priceResult && !$priceResult['success']): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                เกิดข้อผิดพลาด: <?= htmlspecialchars($priceResult['error']) ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>