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

require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/includes/PriceCalculator.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$calculator = new PriceCalculator();

// จัดการ Actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_season':
            try {
                $sql = "INSERT INTO bk_seasonal_prices 
                        (room_type_id, season_name, start_date, end_date, 
                         price_modifier_type, price_modifier_value, priority, is_active)
                        VALUES (:room_type_id, :season_name, :start_date, :end_date,
                                :price_type, :price_value, :priority, :is_active)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'room_type_id' => $_POST['room_type_id'],
                    'season_name' => $_POST['season_name'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'price_type' => $_POST['price_modifier_type'],
                    'price_value' => $_POST['price_modifier_value'],
                    'priority' => $_POST['priority'] ?? 1,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ]);
                
                $message = 'เพิ่มฤดูกาลสำเร็จ!';
                $messageType = 'success';
                
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'update_season':
            try {
                $sql = "UPDATE bk_seasonal_prices SET
                        season_name = :season_name,
                        start_date = :start_date,
                        end_date = :end_date,
                        price_modifier_type = :price_type,
                        price_modifier_value = :price_value,
                        priority = :priority,
                        is_active = :is_active
                        WHERE price_id = :price_id";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'season_name' => $_POST['season_name'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'price_type' => $_POST['price_modifier_type'],
                    'price_value' => $_POST['price_modifier_value'],
                    'priority' => $_POST['priority'],
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'price_id' => $_POST['price_id']
                ]);
                
                $message = 'อัปเดตฤดูกาลสำเร็จ!';
                $messageType = 'success';
                
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'delete_season':
            try {
                $sql = "DELETE FROM bk_seasonal_prices WHERE price_id = :price_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['price_id' => $_POST['price_id']]);
                
                $message = 'ลบฤดูกาลสำเร็จ!';
                $messageType = 'success';
                
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
            
        case 'update_breakfast':
            try {
                $sql = "UPDATE bk_room_types SET
                        breakfast_included = :breakfast_included,
                        breakfast_price = :breakfast_price
                        WHERE room_type_id = :room_type_id";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'breakfast_included' => isset($_POST['breakfast_included']) ? 1 : 0,
                    'breakfast_price' => $_POST['breakfast_price'] ?? 0,
                    'room_type_id' => $_POST['room_type_id']
                ]);
                
                $message = 'อัปเดตข้อมูลอาหารเช้าสำเร็จ!';
                $messageType = 'success';
                
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;
    }
}

// ดึงข้อมูล room types
$roomTypes = $db->resultSet("SELECT * FROM bk_room_types WHERE status = 'active' ORDER BY room_type_name");

// ดึงข้อมูล seasonal prices
$selectedRoomType = $_GET['room_type_id'] ?? ($roomTypes[0]['room_type_id'] ?? null);
$seasonalPrices = [];
if ($selectedRoomType) {
    $seasonalPrices = $db->resultSet(
        "SELECT * FROM bk_seasonal_prices 
         WHERE room_type_id = :room_type_id 
         ORDER BY start_date, priority DESC",
        ['room_type_id' => $selectedRoomType]
    );
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการราคาตามฤดูกาล - Admin</title>
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
            color: #2c3e50;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tab {
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: #7f8c8d;
            cursor: pointer;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab:hover {
            background: #f0f0f0;
        }
        
        .tab.active:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        
        .card h2 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .room-selector {
            margin-bottom: 2rem;
        }
        
        .room-selector select {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .price-preview {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .price-preview h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .priority-high {
            background: #fee;
            color: #d00;
        }
        
        .priority-medium {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .priority-low {
            background: #dfe6e9;
            color: #2d3436;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> จัดการราคาและอาหารเช้า</h1>
            <p>ตั้งค่าราคาตามฤดูกาลและจัดการ options อาหารเช้า</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('seasonal')">
                <i class="fas fa-calendar-alt"></i> จัดการราคาตามฤดูกาล
            </button>
            <button class="tab" onclick="showTab('breakfast')">
                <i class="fas fa-coffee"></i> จัดการอาหารเช้า
            </button>
        </div>
        
        <!-- Seasonal Pricing Tab -->
        <div id="seasonal-tab" class="tab-content active">
            <!-- Room Selector -->
            <div class="card">
                <div class="room-selector">
                    <label><strong>เลือกประเภทห้อง:</strong></label>
                    <select onchange="window.location.href='?room_type_id='+this.value">
                        <?php foreach ($roomTypes as $room): ?>
                            <option value="<?= $room['room_type_id'] ?>" 
                                    <?= $selectedRoomType == $room['room_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($room['room_type_name']) ?> 
                                (฿<?= number_format($room['base_price'], 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Add Season Form -->
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> เพิ่มฤดูกาลใหม่</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_season">
                    <input type="hidden" name="room_type_id" value="<?= $selectedRoomType ?>">
                    
                    <div class="form-group">
                        <label>ชื่อฤดูกาล <span style="color: red;">*</span></label>
                        <input type="text" name="season_name" 
                               placeholder="เช่น High Season, Low Season, สงกรานต์" required>
                        <p class="help-text">ตั้งชื่อที่เข้าใจง่าย จะแสดงในระบบและรายงาน</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>วันที่เริ่มต้น <span style="color: red;">*</span></label>
                            <input type="date" name="start_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label>วันที่สิ้นสุด <span style="color: red;">*</span></label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>ประเภทการปรับราคา <span style="color: red;">*</span></label>
                            <select name="price_modifier_type" required>
                                <option value="percentage">เปอร์เซ็นต์ (%)</option>
                                <option value="fixed">จำนวนเงิน (฿)</option>
                            </select>
                            <p class="help-text">
                                Percentage: เพิ่ม/ลด X%<br>
                                Fixed: เพิ่ม/ลด X บาท
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label>ค่าปรับราคา <span style="color: red;">*</span></label>
                            <input type="number" step="0.01" name="price_modifier_value" 
                                   placeholder="30 หรือ -20" required>
                            <p class="help-text">
                                ใช้ตัวเลขบวกเพื่อเพิ่มราคา<br>
                                ใช้ตัวเลขลบเพื่อลดราคา
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label>ลำดับความสำคัญ <span style="color: red;">*</span></label>
                            <input type="number" name="priority" value="1" min="0" max="10" required>
                            <p class="help-text">
                                เลขมาก = สำคัญมาก<br>
                                ใช้เมื่อมีหลายฤดูกาลซ้อนกัน
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" id="is_active" checked>
                            <label for="is_active" style="margin: 0;">เปิดใช้งาน</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกฤดูกาล
                    </button>
                </form>
            </div>
            
            <!-- Existing Seasons -->
            <div class="card">
                <h2><i class="fas fa-list"></i> รายการฤดูกาลที่มีอยู่</h2>
                
                <?php if (empty($seasonalPrices)): ?>
                    <p style="color: #7f8c8d; text-align: center; padding: 2rem;">
                        <i class="fas fa-info-circle"></i> ยังไม่มีข้อมูลฤดูกาล
                    </p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ฤดูกาล</th>
                                    <th>ช่วงเวลา</th>
                                    <th>การปรับราคา</th>
                                    <th>ลำดับความสำคัญ</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($seasonalPrices as $season): 
                                    // คำนวณราคาที่ปรับแล้ว
                                    $basePrice = 0;
                                    foreach ($roomTypes as $room) {
                                        if ($room['room_type_id'] == $selectedRoomType) {
                                            $basePrice = $room['base_price'];
                                            break;
                                        }
                                    }
                                    
                                    if ($season['price_modifier_type'] == 'percentage') {
                                        $adjustment = $basePrice * ($season['price_modifier_value'] / 100);
                                        $finalPrice = $basePrice + $adjustment;
                                        $displayModifier = ($season['price_modifier_value'] > 0 ? '+' : '') . 
                                                          $season['price_modifier_value'] . '%';
                                    } else {
                                        $finalPrice = $basePrice + $season['price_modifier_value'];
                                        $displayModifier = ($season['price_modifier_value'] > 0 ? '+' : '') . 
                                                          '฿' . number_format(abs($season['price_modifier_value']), 0);
                                    }
                                    
                                    $priorityClass = $season['priority'] >= 3 ? 'priority-high' : 
                                                   ($season['priority'] >= 2 ? 'priority-medium' : 'priority-low');
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($season['season_name']) ?></strong>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($season['start_date'])) ?> - 
                                            <?= date('d/m/Y', strtotime($season['end_date'])) ?>
                                        </td>
                                        <td>
                                            <strong style="color: <?= $season['price_modifier_value'] > 0 ? '#e74c3c' : '#27ae60' ?>">
                                                <?= $displayModifier ?>
                                            </strong>
                                            <br>
                                            <small style="color: #7f8c8d;">
                                                ฿<?= number_format($basePrice, 0) ?> → 
                                                ฿<?= number_format($finalPrice, 0) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="priority-badge <?= $priorityClass ?>">
                                                <i class="fas fa-flag"></i> <?= $season['priority'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($season['is_active']): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> เปิดใช้งาน
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-pause"></i> ปิดใช้งาน
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button onclick="editSeason(<?= htmlspecialchars(json_encode($season)) ?>)" 
                                                        class="btn btn-secondary" style="padding: 8px 16px;">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('ต้องการลบฤดูกาลนี้?')">
                                                    <input type="hidden" name="action" value="delete_season">
                                                    <input type="hidden" name="price_id" value="<?= $season['price_id'] ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 8px 16px;">
                                                        <i class="fas fa-trash"></i> ลบ
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Breakfast Tab -->
        <div id="breakfast-tab" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-coffee"></i> จัดการอาหารเช้าสำหรับแต่ละประเภทห้อง</h2>
                
                <?php foreach ($roomTypes as $room): ?>
                    <div style="border: 2px solid #e0e0e0; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 1rem;">
                            <?= htmlspecialchars($room['room_type_name']) ?>
                            <span style="color: #7f8c8d; font-size: 1rem; font-weight: normal;">
                                (฿<?= number_format($room['base_price'], 0) ?>/คืน)
                            </span>
                        </h3>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_breakfast">
                            <input type="hidden" name="room_type_id" value="<?= $room['room_type_id'] ?>">
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" 
                                           name="breakfast_included" 
                                           id="breakfast_<?= $room['room_type_id'] ?>"
                                           <?= $room['breakfast_included'] ? 'checked' : '' ?>
                                           onchange="toggleBreakfastPrice(<?= $room['room_type_id'] ?>)">
                                    <label for="breakfast_<?= $room['room_type_id'] ?>" style="margin: 0;">
                                        <strong>รวมอาหารเช้าในราคาห้องพัก</strong>
                                    </label>
                                </div>
                                <p class="help-text">
                                    ถ้าเลือก: อาหารเช้ารวมในราคาห้องแล้ว ไม่ต้องจ่ายเพิ่ม<br>
                                    ถ้าไม่เลือก: ลูกค้าสามารถเลือกซื้ออาหารเช้าเพิ่มได้
                                </p>
                            </div>
                            
                            <div class="form-group" id="breakfast_price_<?= $room['room_type_id'] ?>"
                                 style="<?= $room['breakfast_included'] ? 'display: none;' : '' ?>">
                                <label>ราคาอาหารเช้า (ต่อคืน)</label>
                                <input type="number" 
                                       step="0.01" 
                                       name="breakfast_price" 
                                       value="<?= $room['breakfast_price'] ?? 0 ?>"
                                       placeholder="200">
                                <p class="help-text">
                                    ราคาที่ลูกค้าต้องจ่ายเพิ่มถ้าต้องการอาหารเช้า
                                </p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> บันทึกการตั้งค่า
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // ซ่อน tabs ทั้งหมด
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // แสดง tab ที่เลือก
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function toggleBreakfastPrice(roomId) {
            const checkbox = document.getElementById('breakfast_' + roomId);
            const priceDiv = document.getElementById('breakfast_price_' + roomId);
            
            if (checkbox.checked) {
                priceDiv.style.display = 'none';
            } else {
                priceDiv.style.display = 'block';
            }
        }
        
        function editSeason(season) {
            // TODO: แสดง modal หรือ form สำหรับแก้ไข
            // ใช้ sweet alert หรือ modal library
            alert('Feature แก้ไขจะเพิ่มเร็วๆ นี้\n\nข้อมูลปัจจุบัน:\n' + JSON.stringify(season, null, 2));
        }
    </script>
</body>
</html>