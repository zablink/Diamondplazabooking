<?php
// /booking/admin/daily-rates-api.php

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

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'save') {
        $data = $input['data'] ?? [];
        
        if (empty($data)) {
            echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลที่จะบันทึก']);
            exit;
        }
        
        try {
            $conn->beginTransaction();
            
            // Group data by room_type_id and date
            $groupedData = [];
            foreach ($data as $item) {
                $key = $item['room_type_id'] . '_' . $item['date'];
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'room_type_id' => $item['room_type_id'],
                        'date' => $item['date'],
                        'available_rooms' => null,
                        'price' => null,
                        'price_with_breakfast' => null
                    ];
                }
                
                if ($item['type'] === 'availability') {
                    $groupedData[$key]['available_rooms'] = $item['value'];
                } elseif ($item['type'] === 'price') {
                    $groupedData[$key]['price'] = $item['value'];
                } elseif ($item['type'] === 'price_with_breakfast') {
                    $groupedData[$key]['price_with_breakfast'] = $item['value'];
                }
            }
            
            // Get base prices for missing values
            foreach ($groupedData as $key => &$item) {
                $stmt = $conn->prepare("SELECT base_price, breakfast_price, total_rooms FROM bk_room_types WHERE room_type_id = ?");
                $stmt->execute([$item['room_type_id']]);
                $roomType = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($roomType) {
                    if ($item['available_rooms'] === null) {
                        // Get existing or use total_rooms
                        $stmt = $conn->prepare("SELECT available_rooms FROM bk_room_inventory WHERE room_type_id = ? AND date = ?");
                        $stmt->execute([$item['room_type_id'], $item['date']]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        $item['available_rooms'] = $existing ? $existing['available_rooms'] : $roomType['total_rooms'];
                    }
                    
                    if ($item['price'] === null) {
                        // Get existing or use base_price
                        $stmt = $conn->prepare("SELECT price FROM bk_room_inventory WHERE room_type_id = ? AND date = ?");
                        $stmt->execute([$item['room_type_id'], $item['date']]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        $item['price'] = $existing ? $existing['price'] : $roomType['base_price'];
                    }
                    
                    if ($item['price_with_breakfast'] === null) {
                        // Get existing or calculate from base_price + breakfast_price
                        $stmt = $conn->prepare("SELECT price_with_breakfast, price FROM bk_room_inventory WHERE room_type_id = ? AND date = ?");
                        $stmt->execute([$item['room_type_id'], $item['date']]);
                        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($existing && $existing['price_with_breakfast']) {
                            $item['price_with_breakfast'] = $existing['price_with_breakfast'];
                        } else {
                            $item['price_with_breakfast'] = $item['price'] + ($roomType['breakfast_price'] ?? 0);
                        }
                    }
                }
            }
            
            // Insert or update inventory
            $sql = "INSERT INTO bk_room_inventory (room_type_id, date, available_rooms, price, price_with_breakfast)
                    VALUES (:room_type_id, :date, :available_rooms, :price, :price_with_breakfast)
                    ON DUPLICATE KEY UPDATE
                    available_rooms = VALUES(available_rooms),
                    price = VALUES(price),
                    price_with_breakfast = VALUES(price_with_breakfast)";
            
            $stmt = $conn->prepare($sql);
            
            foreach ($groupedData as $item) {
                $stmt->execute([
                    ':room_type_id' => $item['room_type_id'],
                    ':date' => $item['date'],
                    ':available_rooms' => $item['available_rooms'],
                    ':price' => $item['price'],
                    ':price_with_breakfast' => $item['price_with_breakfast']
                ]);
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลสำเร็จ']);
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error saving daily rates: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
