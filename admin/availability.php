<?php

//// init for SESSION , PROJECT_ROOT , etc..
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

require_once PROJECT_ROOT . '/includes/init.php';
require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';
require_once PROJECT_ROOT . '/includes/PriceCalculator.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$admin = new Admin();
$stats = $admin->getDashboardStats();

// Get date range
$checkIn = $_GET['check_in'] ?? date('Y-m-d');
$checkOut = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));

// Get all room types
$roomTypes = $admin->getAllRoomTypes();

// Calculate availability for each room type
$calculator = new PriceCalculator();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบห้องว่าง - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .availability-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .room-availability-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .room-availability-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .room-availability-header h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .room-availability-header p {
            opacity: 0.9;
            margin: 0;
        }
        
        .room-availability-body {
            padding: 1.5rem;
        }
        
        .availability-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .availability-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .availability-stat .number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .availability-stat .label {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .availability-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .availability-status.available {
            background: #d1fae5;
            color: #065f46;
        }
        
        .availability-status.limited {
            background: #fef3c7;
            color: #92400e;
        }
        
        .availability-status.full {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .price-info {
            background: #f0f7ff;
            border: 1px solid #cce5ff;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .price-info strong {
            color: #004085;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-door-open"></i> ตรวจสอบห้องว่าง</h1>
                <p>ตรวจสอบความพร้อมของห้องพักในช่วงเวลาที่กำหนด</p>
            </div>
            
            <!-- Date Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group">
                            <label>Check-in</label>
                            <input type="date" name="check_in" value="<?= htmlspecialchars($checkIn) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Check-out</label>
                            <input type="date" name="check_out" value="<?= htmlspecialchars($checkOut) ?>" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> ตรวจสอบ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Room Availability -->
            <div class="availability-grid">
                <?php foreach ($roomTypes as $room): ?>
                    <?php
                    // Calculate total rooms and occupied rooms
                    $totalRooms = $room['total_rooms'] ?? 0;
                    
                    // In a real system, query bookings for this period
                    // For now, simulate with random numbers
                    $occupiedRooms = rand(0, $totalRooms);
                    $availableRooms = $totalRooms - $occupiedRooms;
                    $occupancyRate = $totalRooms > 0 ? ($occupiedRooms / $totalRooms * 100) : 0;
                    
                    // Determine status
                    if ($availableRooms == 0) {
                        $statusClass = 'full';
                        $statusText = 'เต็ม';
                        $statusIcon = 'times-circle';
                    } elseif ($availableRooms <= $totalRooms * 0.3) {
                        $statusClass = 'limited';
                        $statusText = 'เหลือน้อย';
                        $statusIcon = 'exclamation-triangle';
                    } else {
                        $statusClass = 'available';
                        $statusText = 'ว่าง';
                        $statusIcon = 'check-circle';
                    }
                    
                    // Calculate price
                    try {
                        $priceData = $calculator->calculateTotalPrice(
                            $room['room_type_id'],
                            $checkIn,
                            $checkOut,
                            false
                        );
                        $totalPrice = $priceData['total_price'] ?? $room['base_price'];
                        $nights = $priceData['nights'] ?? 1;
                    } catch (Exception $e) {
                        $totalPrice = $room['base_price'];
                        $nights = 1;
                    }
                    ?>
                    
                    <div class="room-availability-card">
                        <div class="room-availability-header">
                            <h3><?= htmlspecialchars($room['room_type_name']) ?></h3>
                            <p><i class="fas fa-users"></i> รองรับได้ <?= $room['max_occupancy'] ?> คน</p>
                        </div>
                        
                        <div class="room-availability-body">
                            <div class="availability-stats">
                                <div class="availability-stat">
                                    <div class="number"><?= $totalRooms ?></div>
                                    <div class="label">ห้องทั้งหมด</div>
                                </div>
                                <div class="availability-stat">
                                    <div class="number"><?= $availableRooms ?></div>
                                    <div class="label">ห้องว่าง</div>
                                </div>
                                <div class="availability-stat">
                                    <div class="number"><?= number_format($occupancyRate, 0) ?>%</div>
                                    <div class="label">อัตราการเข้าพัก</div>
                                </div>
                            </div>
                            
                            <div class="availability-status <?= $statusClass ?>">
                                <i class="fas fa-<?= $statusIcon ?>"></i>
                                <span><?= $statusText ?></span>
                            </div>
                            
                            <div class="price-info">
                                <div><i class="fas fa-dollar-sign"></i> ราคา: <strong>฿<?= number_format($totalPrice, 0) ?></strong></div>
                                <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">
                                    (฿<?= number_format($totalPrice / max($nights, 1), 0) ?>/คืน × <?= $nights ?> คืน)
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($roomTypes)): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-bed" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p>ยังไม่มีประเภทห้องพักในระบบ</p>
                        <a href="rooms.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> เพิ่มประเภทห้องพัก
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
