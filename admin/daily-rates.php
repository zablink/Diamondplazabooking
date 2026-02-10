<?php
// /booking/admin/daily-rates.php

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

require_once PROJECT_ROOT . '/includes/init.php';
require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$db = Database::getInstance();
$conn = $db->getConnection();

// ตรวจสอบและเพิ่มคอลัมน์ price_with_breakfast ถ้ายังไม่มี
try {
    $checkCol = $conn->query("SHOW COLUMNS FROM bk_room_inventory LIKE 'price_with_breakfast'");
    if ($checkCol->rowCount() == 0) {
        $conn->exec("ALTER TABLE bk_room_inventory ADD COLUMN price_with_breakfast DECIMAL(10,2) NULL AFTER price");
    }
} catch (Exception $e) {
    error_log("Error checking/adding price_with_breakfast column: " . $e->getMessage());
}

// Get date range (default: 14 days from today)
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+13 days'));

// Get all room types
$roomTypes = $admin->getAllRoomTypes();

// Generate date range
$dates = [];
$currentDate = new DateTime($startDate);
$endDateTime = new DateTime($endDate);
while ($currentDate <= $endDateTime) {
    $dates[] = $currentDate->format('Y-m-d');
    $currentDate->modify('+1 day');
}

// Get inventory data for all room types and dates
$inventoryData = [];
if (!empty($roomTypes) && !empty($dates)) {
    $roomTypeIds = array_column($roomTypes, 'room_type_id');
    $placeholders = implode(',', array_fill(0, count($roomTypeIds), '?'));
    $datePlaceholders = implode(',', array_fill(0, count($dates), '?'));
    
    $sql = "SELECT room_type_id, date, available_rooms, price, COALESCE(price_with_breakfast, 0) as price_with_breakfast
            FROM bk_room_inventory
            WHERE room_type_id IN ($placeholders) AND date IN ($datePlaceholders)";
    
    $params = array_merge($roomTypeIds, $dates);
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $inventoryData[$row['room_type_id']][$row['date']] = $row;
    }
}

// Get base prices and breakfast prices from room types
$roomTypeBaseData = [];
foreach ($roomTypes as $room) {
    $roomTypeBaseData[$room['room_type_id']] = [
        'base_price' => $room['base_price'] ?? 0,
        'breakfast_price' => $room['breakfast_price'] ?? 0,
        'total_rooms' => $room['total_rooms'] ?? 0
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการราคารายวัน - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .daily-rates-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .daily-rates-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .daily-rates-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .date-navigation {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .date-nav-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .date-nav-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .date-range-display {
            font-weight: 600;
            min-width: 200px;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-bulk, .btn-reset, .btn-save {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-bulk {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-bulk:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .btn-reset {
            background: #f59e0b;
            color: white;
        }
        
        .btn-reset:hover {
            background: #d97706;
        }
        
        .btn-save {
            background: #10b981;
            color: white;
        }
        
        .btn-save:hover {
            background: #059669;
        }
        
        .filters-section {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group select, .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .rates-grid {
            overflow-x: auto;
        }
        
        .room-type-section {
            margin-bottom: 2rem;
            background: #f0f7ff;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .room-type-header {
            background: #cce5ff;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: #004085;
        }
        
        .rates-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .rates-table th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            border: 1px solid #e9ecef;
            position: sticky;
            left: 0;
            z-index: 10;
            min-width: 150px;
        }
        
        .rates-table th.room-type-col {
            background: #e3f2fd;
            text-align: left;
            padding-left: 1rem;
        }
        
        .rates-table td {
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #e9ecef;
            position: relative;
        }
        
        .rates-table td.room-type-col {
            background: #f8f9fa;
            font-weight: 600;
            text-align: left;
            padding-left: 1rem;
            position: sticky;
            left: 0;
            z-index: 5;
        }
        
        .editable-cell {
            cursor: pointer;
            padding: 0.5rem;
            min-width: 60px;
            transition: background 0.2s;
        }
        
        .editable-cell:hover {
            background: #e3f2fd;
        }
        
        .editable-cell.editing {
            background: #fff3cd;
            padding: 0;
        }
        
        .editable-cell input {
            width: 100%;
            border: 2px solid #ffc107;
            padding: 0.5rem;
            text-align: center;
            font-size: 1rem;
            border-radius: 3px;
        }
        
        .editable-cell.unavailable {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 600;
        }
        
        .editable-cell.unavailable:hover {
            background: #fecaca;
        }
        
        .weekend-cell {
            background: #fff4e6;
        }
        
        .row-label {
            font-weight: 600;
            color: #495057;
        }
        
        .channels-info {
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: 0.5rem;
        }
        
        .date-header {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 0.85rem;
        }
        
        .date-header-full {
            writing-mode: horizontal-tb;
            text-orientation: mixed;
        }
        
        @media (min-width: 768px) {
            .date-header {
                writing-mode: horizontal-tb;
                text-orientation: mixed;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="daily-rates-container">
                <div class="daily-rates-header">
                    <h1><i class="fas fa-calendar-alt"></i> จัดการราคารายวัน</h1>
                    <div class="date-navigation">
                        <button class="date-nav-btn" onclick="changeDateRange(-14)">
                            <i class="fas fa-chevron-left"></i> <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="date-nav-btn" onclick="changeDateRange(-7)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="date-range-display">
                            <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?>
                        </div>
                        <button class="date-nav-btn" onclick="changeDateRange(7)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="date-nav-btn" onclick="changeDateRange(14)">
                            <i class="fas fa-chevron-right"></i> <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="action-buttons">
                        <button class="btn-bulk" onclick="showBulkUpdate()">
                            <i class="fas fa-edit"></i> Bulk Update
                        </button>
                        <button class="btn-reset" onclick="resetChanges()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button class="btn-save" onclick="saveAllChanges()">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </div>
                
                <div class="filters-section">
                    <div class="filter-group">
                        <label>Sort:</label>
                        <select id="sortFilter">
                            <option value="all">All Rates & Availability</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Room Types:</label>
                        <select id="roomTypeFilter">
                            <option value="all">All Room Types</option>
                            <?php foreach ($roomTypes as $room): ?>
                                <option value="<?= $room['room_type_id'] ?>"><?= htmlspecialchars($room['room_type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" id="searchRates" placeholder="Search Room Rates..." style="min-width: 200px;">
                    </div>
                    <button class="btn-reset" onclick="clearFilters()" style="padding: 0.5rem 1rem;">
                        Clear all filters
                    </button>
                </div>
                
                <div class="rates-grid">
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
            <?php else: ?>
                <?php foreach ($roomTypes as $room): ?>
                        <div class="room-type-section" data-room-type-id="<?= $room['room_type_id'] ?>">
                            <div class="room-type-header">
                                <?= htmlspecialchars($room['room_type_name']) ?>
                            </div>
                            <table class="rates-table">
                                <thead>
                                    <tr>
                                        <th class="room-type-col">Type</th>
                                        <?php foreach ($dates as $date): 
                                            $dateObj = new DateTime($date);
                                            $isWeekend = in_array($dateObj->format('w'), [0, 6]);
                                        ?>
                                            <th class="<?= $isWeekend ? 'weekend-cell' : '' ?> date-header">
                                                <div><?= $dateObj->format('D') ?></div>
                                                <div><?= $dateObj->format('d M') ?></div>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Availability Row -->
                                    <tr>
                                        <td class="room-type-col row-label">
                                            <strong>AVAIL</strong>
                                        </td>
                                        <?php foreach ($dates as $date): 
                                            $dateObj = new DateTime($date);
                                            $isWeekend = in_array($dateObj->format('w'), [0, 6]);
                                            $inventory = $inventoryData[$room['room_type_id']][$date] ?? null;
                                            $availableRooms = $inventory ? $inventory['available_rooms'] : ($roomTypeBaseData[$room['room_type_id']]['total_rooms'] ?? 0);
                                            $isUnavailable = $availableRooms == 0;
                                        ?>
                                            <td class="editable-cell <?= $isUnavailable ? 'unavailable' : '' ?> <?= $isWeekend ? 'weekend-cell' : '' ?>" 
                                                data-room-type="<?= $room['room_type_id'] ?>" 
                                                data-date="<?= $date ?>" 
                                                data-type="availability"
                                                onclick="editCell(this)"
                                                title="คลิกเพื่อแก้ไข">
                                                <?= $isUnavailable ? '<span style="color: #991b1b; font-weight: 600;">0</span>' : $availableRooms ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    
                                    <!-- Room Only Price Row -->
                                    <tr>
                                        <td class="room-type-col row-label">
                                            Room only
                                            <span class="channels-info">10 CHANNELS ></span>
                                        </td>
                                        <?php foreach ($dates as $date): 
                                            $dateObj = new DateTime($date);
                                            $isWeekend = in_array($dateObj->format('w'), [0, 6]);
                                            $inventory = $inventoryData[$room['room_type_id']][$date] ?? null;
                                            $price = $inventory ? $inventory['price'] : ($roomTypeBaseData[$room['room_type_id']]['base_price'] ?? 0);
                                        ?>
                                            <td class="editable-cell <?= $isWeekend ? 'weekend-cell' : '' ?>" 
                                                data-room-type="<?= $room['room_type_id'] ?>" 
                                                data-date="<?= $date ?>" 
                                                data-type="price"
                                                onclick="editCell(this)">
                                                <?= number_format($price, 0) ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    
                                    <!-- Room with Breakfast Price Row -->
                                    <tr>
                                        <td class="room-type-col row-label">
                                            Room with breakfast
                                            <span class="channels-info">9 CHANNELS ></span>
                                        </td>
                                        <?php foreach ($dates as $date): 
                                            $dateObj = new DateTime($date);
                                            $isWeekend = in_array($dateObj->format('w'), [0, 6]);
                                            $inventory = $inventoryData[$room['room_type_id']][$date] ?? null;
                                            $basePrice = $inventory ? $inventory['price'] : ($roomTypeBaseData[$room['room_type_id']]['base_price'] ?? 0);
                                            $breakfastPrice = $roomTypeBaseData[$room['room_type_id']]['breakfast_price'] ?? 0;
                                            $priceWithBreakfast = $inventory ? ($inventory['price_with_breakfast'] ?? ($basePrice + $breakfastPrice)) : ($basePrice + $breakfastPrice);
                                        ?>
                                            <td class="editable-cell <?= $isWeekend ? 'weekend-cell' : '' ?>" 
                                                data-room-type="<?= $room['room_type_id'] ?>" 
                                                data-date="<?= $date ?>" 
                                                data-type="price_with_breakfast"
                                                onclick="editCell(this)">
                                                <?= number_format($priceWithBreakfast, 0) ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let changes = {};
        
        function editCell(cell) {
            if (cell.classList.contains('editing')) return;
            
            const currentValue = cell.textContent.trim().replace(/,/g, '');
            const input = document.createElement('input');
            input.type = 'number';
            input.value = currentValue;
            input.min = 0;
            input.step = cell.dataset.type === 'availability' ? 1 : 0.01;
            
            cell.classList.add('editing');
            cell.innerHTML = '';
            cell.appendChild(input);
            input.focus();
            input.select();
            
            const saveCell = (e) => {
                if (e.key === 'Enter' || e.type === 'blur') {
                    const newValue = parseFloat(input.value) || 0;
                    const oldValue = parseFloat(currentValue) || 0;
                    
                    if (newValue !== oldValue) {
                        const key = `${cell.dataset.roomType}_${cell.dataset.date}_${cell.dataset.type}`;
                        changes[key] = {
                            room_type_id: cell.dataset.roomType,
                            date: cell.dataset.date,
                            type: cell.dataset.type,
                            value: newValue
                        };
                    }
                    
                    // Format display value
                    let displayValue = newValue;
                    if (cell.dataset.type !== 'availability') {
                        displayValue = Math.round(newValue).toLocaleString('en-US');
                    }
                    
                    cell.classList.remove('editing');
                    cell.textContent = displayValue;
                    
                    // Update unavailable class for availability cells
                    if (cell.dataset.type === 'availability') {
                        if (newValue === 0) {
                            cell.classList.add('unavailable');
                            cell.innerHTML = '<span style="color: #991b1b; font-weight: 600;">0</span>';
                        } else {
                            cell.classList.remove('unavailable');
                            cell.textContent = displayValue;
                        }
                    }
                    
                    input.removeEventListener('keydown', saveCell);
                    input.removeEventListener('blur', saveCell);
                } else if (e.key === 'Escape') {
                    cell.classList.remove('editing');
                    cell.textContent = currentValue;
                    input.removeEventListener('keydown', saveCell);
                    input.removeEventListener('blur', saveCell);
                }
            };
            
            input.addEventListener('keydown', saveCell);
            input.addEventListener('blur', saveCell);
        }
        
        function saveAllChanges() {
            if (Object.keys(changes).length === 0) {
                alert('ไม่มีข้อมูลที่เปลี่ยนแปลง');
                return;
            }
            
            const data = Object.values(changes);
            
            fetch('daily-rates-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'save', data: data })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('บันทึกข้อมูลสำเร็จ');
                    changes = {};
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (result.message || 'ไม่สามารถบันทึกข้อมูลได้'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            });
        }
        
        function resetChanges() {
            if (Object.keys(changes).length === 0) {
                alert('ไม่มีข้อมูลที่เปลี่ยนแปลง');
                return;
            }
            
            if (confirm('คุณต้องการยกเลิกการเปลี่ยนแปลงทั้งหมดหรือไม่?')) {
                changes = {};
                location.reload();
            }
        }
        
        function changeDateRange(days) {
            const url = new URL(window.location);
            const currentStart = new Date(url.searchParams.get('start_date') || '<?= $startDate ?>');
            const currentEnd = new Date(url.searchParams.get('end_date') || '<?= $endDate ?>');
            
            currentStart.setDate(currentStart.getDate() + days);
            currentEnd.setDate(currentEnd.getDate() + days);
            
            url.searchParams.set('start_date', currentStart.toISOString().split('T')[0]);
            url.searchParams.set('end_date', currentEnd.toISOString().split('T')[0]);
            
            window.location.href = url.toString();
        }
        
        function showBulkUpdate() {
            alert('ฟีเจอร์ Bulk Update กำลังพัฒนา');
        }
        
        function clearFilters() {
            document.getElementById('sortFilter').value = 'all';
            document.getElementById('roomTypeFilter').value = 'all';
            document.getElementById('searchRates').value = '';
        }
        
        // Filter functionality
        document.getElementById('roomTypeFilter').addEventListener('change', function() {
            const selectedId = this.value;
            document.querySelectorAll('.room-type-section').forEach(section => {
                if (selectedId === 'all' || section.dataset.roomTypeId === selectedId) {
                    section.style.display = '';
                } else {
                    section.style.display = 'none';
                }
            });
        });
        
        document.getElementById('searchRates').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.room-type-header').forEach(header => {
                const section = header.closest('.room-type-section');
                const roomName = header.textContent.toLowerCase();
                if (roomName.includes(searchTerm)) {
                    section.style.display = '';
                } else {
                    section.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
