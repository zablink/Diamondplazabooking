<?php
// /booking/admin/amenities.php

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
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$db = Database::getInstance();
$conn = $db->getConnection();

// ตั้งค่า charset สำหรับ connection เพื่อรองรับภาษาไทยและจีน
$conn->exec("SET NAMES utf8mb4");
$conn->exec("SET CHARACTER SET utf8mb4");

// ดึง message จาก session (ถ้ามีจากการ redirect)
$message = $_SESSION['amenity_message'] ?? '';
$messageType = $_SESSION['amenity_message_type'] ?? '';

// ลบ message จาก session หลังแสดงแล้ว
if (!empty($message)) {
    unset($_SESSION['amenity_message']);
    unset($_SESSION['amenity_message_type']);
}

// Auto-migrate: เพิ่ม amenities จาก room types เข้าไปใน bk_amenities อัตโนมัติ
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'bk_amenities'");
    if ($checkTable->rowCount() > 0) {
        // ดึงข้อมูล room types
        $roomTypes = $admin->getAllRoomTypes();
        $allAmenities = [];
        
        foreach ($roomTypes as $room) {
            if (!empty($room['amenities'])) {
                $roomAmenities = json_decode($room['amenities'], true);
                if (!is_array($roomAmenities)) {
                    if (is_string($room['amenities'])) {
                        if (strpos($room['amenities'], '[') === 0) {
                            $roomAmenities = json_decode($room['amenities'], true);
                        } else {
                            $roomAmenities = array_map('trim', explode(',', $room['amenities']));
                        }
                    } else {
                        $roomAmenities = [];
                    }
                }
                
                if (is_array($roomAmenities)) {
                    foreach ($roomAmenities as $amenityName) {
                        if (!empty($amenityName) && is_string($amenityName)) {
                            $amenityName = trim($amenityName);
                            if (!in_array($amenityName, $allAmenities)) {
                                $allAmenities[] = $amenityName;
                            }
                        }
                    }
                }
            }
        }
        
        // ตรวจสอบว่า amenities แต่ละตัวมีในตารางหรือยัง
        if (!empty($allAmenities)) {
            $existingSql = "SELECT amenity_name FROM bk_amenities";
            $existingStmt = $conn->query($existingSql);
            $existingAmenities = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $iconMap = [
                'WiFi' => 'fas fa-wifi',
                'TV' => 'fas fa-tv',
                'Air Conditioning' => 'fas fa-snowflake',
                'Mini Bar' => 'fas fa-glass-martini',
                'Safe Box' => 'fas fa-lock',
                'Hair Dryer' => 'fas fa-wind',
                'Bathtub' => 'fas fa-bath',
                'Shower' => 'fas fa-shower',
                'Coffee Maker' => 'fas fa-coffee',
                'Electric Kettle' => 'fas fa-mug-hot',
                'Work Desk' => 'fas fa-desktop',
                'Balcony' => 'fas fa-home',
                'ลิฟท์' => 'fas fa-elevator',
                'Elevator' => 'fas fa-elevator'
            ];
            
            foreach ($allAmenities as $amenityName) {
                if (!in_array($amenityName, $existingAmenities)) {
                    // เพิ่ม amenity ใหม่
                    $icon = $iconMap[$amenityName] ?? 'fas fa-star';
                    $insertSql = "INSERT INTO bk_amenities (amenity_name, amenity_icon, amenity_category, is_active, display_order)
                                  VALUES (:name, :icon, 'general', 1, 0)
                                  ON DUPLICATE KEY UPDATE amenity_name = amenity_name";
                    $insertStmt = $conn->prepare($insertSql);
                    $insertStmt->execute([
                        ':name' => $amenityName,
                        ':icon' => $icon
                    ]);
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error auto-migrating amenities: " . $e->getMessage());
}

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Debug log เพื่อดูว่า action ที่ส่งมาคืออะไร
    error_log("Amenities POST Action: " . $action);
    error_log("Amenities POST Data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
    
    // ป้องกันกรณีที่ส่ง action มาผิด: ถ้ามี amenity_id แสดงว่าเป็น update ไม่ใช่ add
    if ($action === 'add' && !empty($_POST['amenity_id'])) {
        $action = 'update';
        error_log("Action corrected from 'add' to 'update' because amenity_id exists: " . $_POST['amenity_id']);
    }
    
    try {
        switch ($action) {
            case 'migrate_amenities':
                // Migrate amenities จาก room types เข้าไปใน bk_amenities
                $roomTypes = $admin->getAllRoomTypes();
                $allAmenities = [];
                $migratedCount = 0;
                
                foreach ($roomTypes as $room) {
                    if (!empty($room['amenities'])) {
                        $roomAmenities = json_decode($room['amenities'], true);
                        if (!is_array($roomAmenities)) {
                            if (is_string($room['amenities'])) {
                                if (strpos($room['amenities'], '[') === 0) {
                                    $roomAmenities = json_decode($room['amenities'], true);
                                } else {
                                    $roomAmenities = array_map('trim', explode(',', $room['amenities']));
                                }
                            } else {
                                $roomAmenities = [];
                            }
                        }
                        
                        if (is_array($roomAmenities)) {
                            foreach ($roomAmenities as $amenityName) {
                                if (!empty($amenityName) && is_string($amenityName)) {
                                    $amenityName = trim($amenityName);
                                    if (!in_array($amenityName, $allAmenities)) {
                                        $allAmenities[] = $amenityName;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // ตรวจสอบว่า amenities แต่ละตัวมีในตารางหรือยัง
                if (!empty($allAmenities)) {
                    $existingSql = "SELECT amenity_name FROM bk_amenities";
                    $existingStmt = $conn->query($existingSql);
                    $existingAmenities = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $iconMap = [
                        'WiFi' => 'fas fa-wifi',
                        'TV' => 'fas fa-tv',
                        'Air Conditioning' => 'fas fa-snowflake',
                        'Mini Bar' => 'fas fa-glass-martini',
                        'Safe Box' => 'fas fa-lock',
                        'Hair Dryer' => 'fas fa-wind',
                        'Bathtub' => 'fas fa-bath',
                        'Shower' => 'fas fa-shower',
                        'Coffee Maker' => 'fas fa-coffee',
                        'Electric Kettle' => 'fas fa-mug-hot',
                        'Work Desk' => 'fas fa-desktop',
                        'Balcony' => 'fas fa-home',
                        'Coffee' => 'fas fa-coffee',
                        'Desk' => 'fas fa-desktop',
                        'Room' => 'fas fa-bed',
                        'ลิฟท์' => 'fas fa-elevator',
                        'Elevator' => 'fas fa-elevator'
                    ];
                    
                    foreach ($allAmenities as $amenityName) {
                        if (!in_array($amenityName, $existingAmenities)) {
                            // เพิ่ม amenity ใหม่
                            $icon = $iconMap[$amenityName] ?? 'fas fa-star';
                            $insertSql = "INSERT INTO bk_amenities (amenity_name, amenity_icon, amenity_category, is_active, display_order)
                                          VALUES (:name, :icon, 'general', 1, 0)";
                            $insertStmt = $conn->prepare($insertSql);
                            $insertStmt->execute([
                                ':name' => $amenityName,
                                ':icon' => $icon
                            ]);
                            $migratedCount++;
                        }
                    }
                }
                
                // Redirect เพื่อป้องกัน form resubmission และให้ข้อมูลแสดงล่าสุด
                $_SESSION['amenity_message'] = "Migrate สำเร็จ! เพิ่ม {$migratedCount} รายการเข้าไปใน Master List";
                $_SESSION['amenity_message_type'] = 'success';
                header('Location: amenities.php');
                exit;
                break;
                
            case 'add':
                $amenityNameTh = trim($_POST['amenity_name_th'] ?? '');
                $amenityNameEn = trim($_POST['amenity_name_en'] ?? '');
                $amenityNameZh = trim($_POST['amenity_name_zh'] ?? '');
                $amenityIcon = trim($_POST['amenity_icon'] ?? 'fas fa-star');
                $amenityCategory = trim($_POST['amenity_category'] ?? 'general');
                
                // ใช้ชื่อภาษาไทยเป็นหลัก
                $amenityName = $amenityNameTh;
                if (empty($amenityName)) {
                    throw new Exception('กรุณากรอกชื่อสิ่งอำนวยความสะดวก (ภาษาไทย)');
                }
                
                // ตรวจสอบว่ามีตาราง bk_amenities หรือไม่ ถ้าไม่มีให้สร้าง
                try {
                    $checkTable = $conn->query("SHOW TABLES LIKE 'bk_amenities'");
                    if ($checkTable->rowCount() == 0) {
                        // สร้างตาราง bk_amenities
                        $createTable = "
                            CREATE TABLE IF NOT EXISTS bk_amenities (
                                amenity_id INT PRIMARY KEY AUTO_INCREMENT,
                                amenity_name VARCHAR(255) NOT NULL,
                                amenity_name_th VARCHAR(255),
                                amenity_name_en VARCHAR(255),
                                amenity_name_zh VARCHAR(255),
                                amenity_icon VARCHAR(100) DEFAULT 'fas fa-star',
                                amenity_category VARCHAR(50) DEFAULT 'general',
                                is_active TINYINT(1) DEFAULT 1,
                                display_order INT DEFAULT 0,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                UNIQUE KEY unique_amenity_name (amenity_name)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                        ";
                        $conn->exec($createTable);
                    }
                    
                    // เพิ่ม multilingual columns ถ้ายังไม่มี
                    $languages = ['th', 'en', 'zh'];
                    foreach ($languages as $lang) {
                        $checkCol = $conn->query("SHOW COLUMNS FROM bk_amenities LIKE 'amenity_name_" . $lang . "'");
                        if ($checkCol->rowCount() == 0) {
                            $conn->exec("ALTER TABLE bk_amenities ADD COLUMN amenity_name_" . $lang . " VARCHAR(255) AFTER amenity_name");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error creating amenities table: " . $e->getMessage());
                }
                
                // ตรวจสอบว่ามีชื่อซ้ำหรือไม่ (ใช้ชื่อภาษาไทยเป็นหลัก)
                $checkName = $conn->prepare("SELECT amenity_id FROM bk_amenities WHERE amenity_name = :name1 OR amenity_name_th = :name2");
                $checkName->execute([':name1' => $amenityName, ':name2' => $amenityName]);
                if ($checkName->rowCount() > 0) {
                    throw new Exception('ชื่อสิ่งอำนวยความสะดวกนี้มีอยู่แล้วในระบบ');
                }
                
                // ตรวจสอบว่า icon นี้ถูกใช้โดย amenity อื่นอยู่แล้วหรือไม่
                $checkIcon = $conn->prepare("SELECT amenity_name, amenity_name_th FROM bk_amenities WHERE amenity_icon = :icon LIMIT 1");
                $checkIcon->execute([':icon' => $amenityIcon]);
                if ($checkIcon->rowCount() > 0) {
                    $existingAmenity = $checkIcon->fetch(PDO::FETCH_ASSOC);
                    $displayName = $existingAmenity['amenity_name_th'] ?? $existingAmenity['amenity_name'] ?? '';
                    throw new Exception('Icon นี้ถูกใช้โดย "' . htmlspecialchars($displayName) . '" อยู่แล้ว กรุณาเลือก Icon อื่น');
                }
                
                // เพิ่ม amenity ใหม่
                $sql = "INSERT INTO bk_amenities (amenity_name, amenity_name_th, amenity_name_en, amenity_name_zh, amenity_icon, amenity_category, display_order)
                        VALUES (:name, :name_th, :name_en, :name_zh, :icon, :category, :order)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':name' => $amenityName,
                    ':name_th' => $amenityNameTh,
                    ':name_en' => $amenityNameEn,
                    ':name_zh' => $amenityNameZh,
                    ':icon' => $amenityIcon,
                    ':category' => $amenityCategory,
                    ':order' => $_POST['display_order'] ?? 0
                ]);
                
                // Redirect เพื่อป้องกัน form resubmission และให้ข้อมูลแสดงล่าสุด
                $_SESSION['amenity_message'] = 'เพิ่มสิ่งอำนวยความสะดวกสำเร็จ!';
                $_SESSION['amenity_message_type'] = 'success';
                header('Location: amenities.php');
                exit;
                break;
                
            case 'update':
                $amenityId = intval($_POST['amenity_id'] ?? 0);
                $amenityNameTh = trim($_POST['amenity_name_th'] ?? '');
                $amenityNameEn = trim($_POST['amenity_name_en'] ?? '');
                $amenityNameZh = trim($_POST['amenity_name_zh'] ?? '');
                $amenityIcon = trim($_POST['amenity_icon'] ?? 'fas fa-star');
                $amenityCategory = trim($_POST['amenity_category'] ?? 'general');
                
                if (empty($amenityId) || empty($amenityNameTh)) {
                    throw new Exception('ข้อมูลไม่ครบถ้วน');
                }
                
                // ตรวจสอบและเพิ่ม multilingual columns ถ้ายังไม่มี
                try {
                    $languages = ['th', 'en', 'zh'];
                    foreach ($languages as $lang) {
                        $checkCol = $conn->query("SHOW COLUMNS FROM bk_amenities LIKE 'amenity_name_" . $lang . "'");
                        if ($checkCol->rowCount() == 0) {
                            $conn->exec("ALTER TABLE bk_amenities ADD COLUMN amenity_name_" . $lang . " VARCHAR(255) AFTER amenity_name");
                            error_log("Added column amenity_name_" . $lang . " to bk_amenities table");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error checking/adding multilingual columns: " . $e->getMessage());
                }
                
                // ดึงข้อมูลเดิมของ amenity นี้เพื่อตรวจสอบว่ามีการเปลี่ยนแปลงหรือไม่
                $getCurrentData = $conn->prepare("SELECT amenity_name, amenity_name_th, amenity_name_en, amenity_name_zh, amenity_icon FROM bk_amenities WHERE amenity_id = :id");
                $getCurrentData->execute([':id' => $amenityId]);
                $currentIcon = '';
                $currentName = ''; // เก็บ amenity_name เดิมไว้
                if ($getCurrentData->rowCount() > 0) {
                    $currentData = $getCurrentData->fetch(PDO::FETCH_ASSOC);
                    $currentIcon = $currentData['amenity_icon'] ?? '';
                    $currentName = $currentData['amenity_name'] ?? ''; // ใช้ค่าเดิมจาก DB
                    
                    error_log("Update amenity - ID: $amenityId");
                    error_log("  Current amenity_name: '" . $currentName . "'");
                    error_log("  New name_th: '" . $amenityNameTh . "'");
                }
                
                // ไม่เช็คชื่อซ้ำสำหรับ update เพราะเราไม่ได้ update amenity_name
                // เราจะ update เฉพาะ amenity_name_th, amenity_name_en, amenity_name_zh เท่านั้น
                
                // ตรวจสอบ icon ซ้ำเฉพาะเมื่อมีการเปลี่ยน icon เท่านั้น
                // ถ้า icon ใหม่เหมือนกับ icon เดิม (ไม่มีการเปลี่ยน) ก็ไม่ต้องตรวจสอบซ้ำ
                $iconChanged = ($amenityIcon !== $currentIcon);
                if ($iconChanged) {
                    // มีการเปลี่ยน icon ใหม่ ให้ตรวจสอบว่า icon นี้ถูกใช้โดย amenity อื่นอยู่แล้วหรือไม่
                    $checkIcon = $conn->prepare("SELECT amenity_name, amenity_name_th FROM bk_amenities WHERE amenity_icon = :icon AND amenity_id != :id LIMIT 1");
                    $checkIcon->execute([':icon' => $amenityIcon, ':id' => $amenityId]);
                    if ($checkIcon->rowCount() > 0) {
                        $existingAmenity = $checkIcon->fetch(PDO::FETCH_ASSOC);
                        $displayName = $existingAmenity['amenity_name_th'] ?? $existingAmenity['amenity_name'] ?? '';
                        throw new Exception('Icon นี้ถูกใช้โดย "' . htmlspecialchars($displayName) . '" อยู่แล้ว กรุณาเลือก Icon อื่น');
                    }
                }
                
                // UPDATE เฉพาะ multilingual columns และไม่แก้ไข amenity_name (เพื่อป้องกัน unique constraint)
                $sql = "UPDATE bk_amenities SET 
                        amenity_name_th = :name_th,
                        amenity_name_en = :name_en,
                        amenity_name_zh = :name_zh,
                        amenity_icon = :icon,
                        amenity_category = :category,
                        display_order = :order,
                        is_active = :active
                        WHERE amenity_id = :id";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    ':name_th' => $amenityNameTh,
                    ':name_en' => $amenityNameEn,
                    ':name_zh' => $amenityNameZh,
                    ':icon' => $amenityIcon,
                    ':category' => $amenityCategory,
                    ':order' => $_POST['display_order'] ?? 0,
                    ':active' => isset($_POST['is_active']) ? 1 : 0,
                    ':id' => $amenityId
                ]);
                
                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Update amenity failed: " . json_encode($errorInfo));
                    throw new Exception('ไม่สามารถอัปเดตข้อมูลได้: ' . ($errorInfo[2] ?? 'Unknown error'));
                }
                
                // ตรวจสอบว่ามีการอัปเดตจริงหรือไม่
                $affectedRows = $stmt->rowCount();
                error_log("Amenity update - ID: $amenityId, Affected rows: $affectedRows, TH: $amenityNameTh, EN: $amenityNameEn, ZH: $amenityNameZh");
                
                // Redirect เพื่อป้องกัน form resubmission และให้ข้อมูลแสดงล่าสุด
                $_SESSION['amenity_message'] = 'อัปเดตข้อมูลสำเร็จ!';
                $_SESSION['amenity_message_type'] = 'success';
                header('Location: amenities.php');
                exit;
                break;
                
            case 'delete':
                $amenityId = $_POST['amenity_id'] ?? 0;
                
                if (empty($amenityId)) {
                    throw new Exception('ไม่พบ ID');
                }
                
                $sql = "DELETE FROM bk_amenities WHERE amenity_id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id' => $amenityId]);
                
                // Redirect เพื่อป้องกัน form resubmission และให้ข้อมูลแสดงล่าสุด
                $_SESSION['amenity_message'] = 'ลบข้อมูลสำเร็จ!';
                $_SESSION['amenity_message_type'] = 'success';
                header('Location: amenities.php');
                exit;
                break;
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Helper function สำหรับดึงชื่อตามภาษา
function getAmenityName($amenity, $lang = null) {
    if ($lang === null) {
        $lang = $_SESSION['lang'] ?? 'th';
    }
    $langKey = 'amenity_name_' . $lang;
    if (isset($amenity[$langKey]) && !empty($amenity[$langKey])) {
        return $amenity[$langKey];
    }
    // Fallback to Thai or default name
    return $amenity['amenity_name_th'] ?? $amenity['amenity_name'] ?? '';
}

// ดึงข้อมูล amenities จากตาราง bk_amenities
$amenities = [];
$amenityIds = []; // เก็บ ID ของ amenities ที่มีในตาราง
$amenityMap = []; // เก็บ mapping ระหว่าง amenity_name กับ amenity data
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'bk_amenities'");
    if ($checkTable->rowCount() > 0) {
        $sql = "SELECT * FROM bk_amenities ORDER BY display_order ASC, amenity_name ASC";
        $stmt = $conn->query($sql);
        $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($amenities as $amenity) {
            // เก็บ ID โดยใช้หลายๆ key เพื่อให้ค้นหาได้ง่าย
            $amenityIds[$amenity['amenity_name']] = $amenity['amenity_id'];
            if (!empty($amenity['amenity_name_th'])) {
                $amenityIds[$amenity['amenity_name_th']] = $amenity['amenity_id'];
            }
            if (!empty($amenity['amenity_name_en'])) {
                $amenityIds[$amenity['amenity_name_en']] = $amenity['amenity_id'];
            }
            if (!empty($amenity['amenity_name_zh'])) {
                $amenityIds[$amenity['amenity_name_zh']] = $amenity['amenity_id'];
            }
            // เก็บข้อมูลเต็มสำหรับการแสดงผล
            $amenityMap[$amenity['amenity_name']] = $amenity;
        }
    } else {
        // ถ้ายังไม่มีตาราง ให้สร้างตาราง
        try {
            $createTable = "
                CREATE TABLE IF NOT EXISTS bk_amenities (
                    amenity_id INT PRIMARY KEY AUTO_INCREMENT,
                    amenity_name VARCHAR(255) NOT NULL,
                    amenity_icon VARCHAR(100) DEFAULT 'fas fa-star',
                    amenity_category VARCHAR(50) DEFAULT 'general',
                    is_active TINYINT(1) DEFAULT 1,
                    display_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_amenity_name (amenity_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $conn->exec($createTable);
        } catch (Exception $e) {
            error_log("Error creating amenities table: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log("Error fetching amenities: " . $e->getMessage());
}

// ดึงข้อมูล room types เพื่อรวบรวม amenities ที่ใช้อยู่
$roomTypes = $admin->getAllRoomTypes();
$amenityUsage = [];
$existingAmenities = []; // เก็บรายการ amenities ที่ใช้ใน room types

foreach ($roomTypes as $room) {
    if (!empty($room['amenities'])) {
        // ลอง decode JSON
        $roomAmenities = json_decode($room['amenities'], true);
        
        // ถ้า decode ไม่ได้ ลองเป็น string แบบ comma separated
        if (!is_array($roomAmenities)) {
            if (is_string($room['amenities'])) {
                // ลองแยกด้วย comma หรือเป็น JSON string
                if (strpos($room['amenities'], '[') === 0) {
                    $roomAmenities = json_decode($room['amenities'], true);
                } else {
                    $roomAmenities = array_map('trim', explode(',', $room['amenities']));
                }
            } else {
                $roomAmenities = [];
            }
        }
        
        if (is_array($roomAmenities) && !empty($roomAmenities)) {
            foreach ($roomAmenities as $amenityName) {
                if (!empty($amenityName) && is_string($amenityName)) {
                    $amenityName = trim($amenityName);
                    if (!isset($amenityUsage[$amenityName])) {
                        $amenityUsage[$amenityName] = [];
                    }
                    $amenityUsage[$amenityName][] = $room['room_type_name'];
                    
                    // เก็บรายการ amenities ที่มีอยู่
                    if (!in_array($amenityName, $existingAmenities)) {
                        $existingAmenities[] = $amenityName;
                    }
                }
            }
        }
    }
}

// เรียงลำดับ amenities
sort($existingAmenities);

// ดึงรายการ icon ที่ถูกใช้งานอยู่แล้วในระบบ (เพื่อไม่ให้แสดงใน icon picker)
$usedIcons = [];
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'bk_amenities'");
    if ($checkTable->rowCount() > 0) {
        $iconSql = "SELECT DISTINCT amenity_icon FROM bk_amenities WHERE amenity_icon IS NOT NULL AND amenity_icon != ''";
        $iconStmt = $conn->query($iconSql);
        $usedIcons = $iconStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    error_log("Error fetching used icons: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสิ่งอำนวยความสะดวก - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-star"></i> จัดการสิ่งอำนวยความสะดวก</h1>
                <p>จัดการรายการสิ่งอำนวยความสะดวกที่ใช้ในระบบ</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($existingAmenities)): 
                // ตรวจสอบว่ามี amenities ที่ยังไม่อยู่ใน Master List หรือไม่
                $needsMigration = false;
                foreach ($existingAmenities as $amenityName) {
                    // ตรวจสอบทั้งชื่อภาษาไทยและชื่อเดิม
                    $found = false;
                    foreach ($amenities as $amenity) {
                        if (getAmenityName($amenity, 'th') === $amenityName || 
                            ($amenity['amenity_name'] ?? '') === $amenityName) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $needsMigration = true;
                        break;
                    }
                }
                if ($needsMigration): ?>
                <div class="alert" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                        <div style="flex: 1; min-width: 250px;">
                            <i class="fas fa-info-circle" style="color: #2196f3; margin-right: 10px;"></i>
                            <strong>พบสิ่งอำนวยความสะดวกที่ใช้ในระบบแต่ยังไม่อยู่ใน Master List</strong>
                            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">
                                คลิกปุ่มด้านล่างเพื่อเพิ่มรายการเหล่านี้เข้าไปใน Master List อัตโนมัติ
                            </p>
                        </div>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="migrate_amenities">
                            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">
                                <i class="fas fa-sync"></i> Migrate ข้อมูล
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; endif; ?>
            
            <div class="card mb-3">
                <div class="card-header">
                    <h2><i class="fas fa-plus"></i> เพิ่มสิ่งอำนวยความสะดวกใหม่</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-row">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>ชื่อสิ่งอำนวยความสะดวก <span style="color: red;">*</span></label>
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                    <input type="text" name="amenity_name_th" required placeholder="เช่น WiFi, TV, Air Conditioning">
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                    <input type="text" name="amenity_name_en" placeholder="e.g. WiFi, TV, Air Conditioning">
                                </div>
                                <div>
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                    <input type="text" name="amenity_name_zh" placeholder="例如：WiFi, TV, 空调">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Icon <span style="color: red;">*</span></label>
                                <div style="position: relative;">
                                    <input type="text" name="amenity_icon" id="amenity_icon" value="fas fa-star" required readonly style="cursor: pointer; background: #f8f9fa;" onclick="openIconPicker()">
                                    <button type="button" onclick="openIconPicker()" class="btn btn-sm" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: #667eea; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer;">
                                        <i class="fas fa-icons"></i> เลือก Icon
                                    </button>
                                </div>
                                <div id="selectedIconPreview" style="margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                                    <i id="previewIcon" class="fas fa-star" style="font-size: 2rem; color: #667eea;"></i>
                                    <div style="margin-top: 5px; color: #666; font-size: 0.9rem;" id="previewText">fas fa-star</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>หมวดหมู่</label>
                                <select name="amenity_category">
                                    <option value="general">ทั่วไป</option>
                                    <option value="room">ห้องพัก</option>
                                    <option value="bathroom">ห้องน้ำ</option>
                                    <option value="entertainment">ความบันเทิง</option>
                                    <option value="service">บริการ</option>
                                    <option value="facility">สิ่งอำนวยความสะดวก</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>ลำดับการแสดง</label>
                                <input type="number" name="display_order" value="0" min="0">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- รายการ Amenities ที่ใช้ในระบบ (จาก Room Types) -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2><i class="fas fa-list-alt"></i> รายการสิ่งอำนวยความสะดวกที่ใช้ในระบบ</h2>
                    <p style="margin: 0; color: #666; font-size: 0.9rem; margin-top: 5px;">รายการที่ใช้ในห้องพัก (สามารถเพิ่มเข้าไปใน Master List ได้)</p>
                </div>
                <div class="card-body">
                    <?php if (empty($existingAmenities)): ?>
                        <div class="text-center" style="padding: 2rem;">
                            <i class="fas fa-info-circle" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;"></i>
                            <p style="color: #666;">ยังไม่มีสิ่งอำนวยความสะดวกที่ใช้ในห้องพัก</p>
                            <p style="color: #999; font-size: 0.9rem;">เพิ่มสิ่งอำนวยความสะดวกให้กับห้องพักในหน้า <a href="rooms.php">จัดการห้องพัก</a></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ชื่อ</th>
                                        <th>ภาษาไทย</th>
                                        <th>中文</th>
                                        <th>ใช้ในห้องพัก</th>
                                        <th>สถานะ</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existingAmenities as $amenityName): 
                                        $isInMasterList = isset($amenityIds[$amenityName]);
                                        $usageCount = isset($amenityUsage[$amenityName]) ? count($amenityUsage[$amenityName]) : 0;
                                        
                                        // ดึงข้อมูลภาษาจาก amenityMap โดยค้นหาจากทุกภาษา
                                        $amenityData = null;
                                        $nameTh = '';
                                        $nameZh = '';
                                        
                                        // ค้นหาข้อมูลจาก amenities array
                                        if ($isInMasterList) {
                                            $amenityId = $amenityIds[$amenityName];
                                            foreach ($amenities as $a) {
                                                if ($a['amenity_id'] == $amenityId) {
                                                    $amenityData = $a;
                                                    $nameTh = $a['amenity_name_th'] ?? '';
                                                    $nameZh = $a['amenity_name_zh'] ?? '';
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($amenityName) ?></strong>
                                                <?php if ($amenityData): ?>
                                                    <br><small style="color: #999;">ID: <?= $amenityData['amenity_id'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($nameTh)): ?>
                                                    <?= htmlspecialchars($nameTh) ?>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($nameZh)): ?>
                                                    <?= htmlspecialchars($nameZh) ?>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($usageCount > 0): ?>
                                                    <span class="badge badge-primary" title="<?= htmlspecialchars(implode(', ', $amenityUsage[$amenityName])) ?>">
                                                        <?= $usageCount ?> ห้อง
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isInMasterList): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> อยู่ใน Master List
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> ยังไม่อยู่ใน Master List
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isInMasterList): ?>
                                                    <?php 
                                                    $amenityId = $amenityIds[$amenityName];
                                                    $amenityData = null;
                                                    foreach ($amenities as $a) {
                                                        if ($a['amenity_id'] == $amenityId) {
                                                            $amenityData = $a;
                                                            break;
                                                        }
                                                    }
                                                    if ($amenityData): ?>
                                                        <button class="btn btn-sm btn-primary" onclick='editAmenity(<?= json_encode($amenityData, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                            <i class="fas fa-edit"></i> แก้ไข
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success" onclick="addToMasterList('<?= htmlspecialchars($amenityName, ENT_QUOTES) ?>')">
                                                        <i class="fas fa-plus"></i> เพิ่มเข้า Master List
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Master List - รายการสิ่งอำนวยความสะดวก</h2>
                    <p style="margin: 0; color: #666; font-size: 0.9rem; margin-top: 5px;">
                        รายการหลักที่สามารถเลือกใช้ในห้องพัก 
                        <?php if (!empty($amenities)): ?>
                            <span style="color: #667eea; font-weight: bold;">(<?= count($amenities) ?> รายการ)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="card-body">
                    <?php if (empty($amenities)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-info-circle" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                            <p style="color: #666;">ยังไม่มีข้อมูลสิ่งอำนวยความสะดวกใน Master List</p>
                            <p style="color: #999; font-size: 0.9rem;">เพิ่มสิ่งอำนวยความสะดวกใหม่โดยใช้ฟอร์มด้านบน หรือเพิ่มจากรายการที่ใช้ในระบบ</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ลำดับ</th>
                                        <th>Icon</th>
                                        <th>ชื่อ (EN)</th>
                                        <th>ภาษาไทย</th>
                                        <th>中文</th>
                                        <th>หมวดหมู่</th>
                                        <th>สถานะ</th>
                                        <th>ใช้ในห้องพัก</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($amenities as $amenity): ?>
                                        <tr>
                                            <td><?= $amenity['display_order'] ?></td>
                                            <td>
                                                <i class="<?= htmlspecialchars($amenity['amenity_icon']) ?>" style="font-size: 1.5rem; color: #667eea;"></i>
                                            </td>
                                            <td><strong><?= htmlspecialchars($amenity['amenity_name']) ?></strong></td>
                                            <td>
                                                <?php 
                                                $nameTh = $amenity['amenity_name_th'] ?? '';
                                                echo !empty($nameTh) ? htmlspecialchars($nameTh) : '<span style="color: #999;">-</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $nameZh = $amenity['amenity_name_zh'] ?? '';
                                                echo !empty($nameZh) ? htmlspecialchars($nameZh) : '<span style="color: #999;">-</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= htmlspecialchars($amenity['amenity_category']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($amenity['is_active']): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle"></i> เปิดใช้งาน
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-times-circle"></i> ปิดใช้งาน
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // ตรวจสอบ usage จากชื่อทุกภาษา
                                                $usageCount = 0;
                                                $usageRooms = [];
                                                
                                                // เช็คจาก amenity_name (ชื่อหลัก)
                                                if (isset($amenityUsage[$amenity['amenity_name']])) {
                                                    $usageCount = count($amenityUsage[$amenity['amenity_name']]);
                                                    $usageRooms = $amenityUsage[$amenity['amenity_name']];
                                                }
                                                // เช็คจากภาษาไทย
                                                else if (!empty($amenity['amenity_name_th']) && isset($amenityUsage[$amenity['amenity_name_th']])) {
                                                    $usageCount = count($amenityUsage[$amenity['amenity_name_th']]);
                                                    $usageRooms = $amenityUsage[$amenity['amenity_name_th']];
                                                }
                                                // เช็คจากภาษาอังกฤษ
                                                else if (!empty($amenity['amenity_name_en']) && isset($amenityUsage[$amenity['amenity_name_en']])) {
                                                    $usageCount = count($amenityUsage[$amenity['amenity_name_en']]);
                                                    $usageRooms = $amenityUsage[$amenity['amenity_name_en']];
                                                }
                                                ?>
                                                <?php if ($usageCount > 0): ?>
                                                    <span class="badge badge-primary" title="<?= htmlspecialchars(implode(', ', $usageRooms)) ?>">
                                                        <?= $usageCount ?> ห้อง
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #999;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick='editAmenity(<?= json_encode($amenity, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบสิ่งอำนวยความสะดวกนี้?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="amenity_id" value="<?= $amenity['amenity_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Amenity Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> แก้ไขสิ่งอำนวยความสะดวก</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="amenity_id" id="edit_amenity_id">
                
                <div class="form-row">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>ชื่อสิ่งอำนวยความสะดวก <span style="color: red;">*</span></label>
                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                            <input type="text" name="amenity_name_th" id="edit_amenity_name_th" required>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                            <input type="text" name="amenity_name_en" id="edit_amenity_name_en">
                        </div>
                        <div>
                            <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                            <input type="text" name="amenity_name_zh" id="edit_amenity_name_zh">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Icon <span style="color: red;">*</span></label>
                        <div style="position: relative;">
                            <input type="text" name="amenity_icon" id="edit_amenity_icon" required readonly style="cursor: pointer; background: #f8f9fa;" onclick="openIconPicker('edit')">
                            <button type="button" onclick="openIconPicker('edit')" class="btn btn-sm" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: #667eea; color: white; border: none; padding: 5px 15px; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-icons"></i> เลือก Icon
                            </button>
                        </div>
                        <div id="editSelectedIconPreview" style="margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                            <i id="editPreviewIcon" class="fas fa-star" style="font-size: 2rem; color: #667eea;"></i>
                            <div style="margin-top: 5px; color: #666; font-size: 0.9rem;" id="editPreviewText">fas fa-star</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select name="amenity_category" id="edit_amenity_category">
                            <option value="general">ทั่วไป</option>
                            <option value="room">ห้องพัก</option>
                            <option value="bathroom">ห้องน้ำ</option>
                            <option value="entertainment">ความบันเทิง</option>
                            <option value="service">บริการ</option>
                            <option value="facility">สิ่งอำนวยความสะดวก</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>ลำดับการแสดง</label>
                        <input type="number" name="display_order" id="edit_display_order" min="0">
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: center;">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width: auto; margin-right: 8px;">
                            <span>เปิดใช้งาน</span>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-content form {
            padding: 30px;
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>

    <!-- Icon Picker Modal -->
    <div id="iconPickerModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-icons"></i> เลือก Icon</h2>
                <span class="close" onclick="closeIconPicker()">&times;</span>
            </div>
            <div style="padding: 20px;">
                <div style="margin-bottom: 20px;">
                    <input type="text" id="iconSearch" placeholder="ค้นหา icon..." style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem;" onkeyup="filterIcons()">
                </div>
                <div id="iconGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; max-height: 500px; overflow-y: auto; padding: 10px;">
                    <!-- Icons will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // รายการ icon ที่ถูกใช้งานอยู่แล้วในระบบ (จาก PHP)
        const usedIcons = <?= json_encode($usedIcons) ?>;
        
        // เก็บ icon ปัจจุบันของ amenity ที่กำลังแก้ไข (ถ้ามี)
        let currentEditIcon = '';
        
        // รายการ icons ที่ใช้บ่อย
        const commonIcons = [
            // Room & Furniture
            { name: 'Bed', icon: 'fas fa-bed', category: 'room' },
            { name: 'Couch', icon: 'fas fa-couch', category: 'room' },
            { name: 'Chair', icon: 'fas fa-chair', category: 'room' },
            { name: 'Door Open', icon: 'fas fa-door-open', category: 'room' },
            { name: 'Window', icon: 'fas fa-window-maximize', category: 'room' },
            
            // Technology
            { name: 'WiFi', icon: 'fas fa-wifi', category: 'tech' },
            { name: 'TV', icon: 'fas fa-tv', category: 'tech' },
            { name: 'Laptop', icon: 'fas fa-laptop', category: 'tech' },
            { name: 'Mobile', icon: 'fas fa-mobile-alt', category: 'tech' },
            
            // Climate & Air
            { name: 'Air Conditioning', icon: 'fas fa-snowflake', category: 'climate' },
            { name: 'Fan', icon: 'fas fa-fan', category: 'climate' },
            
            // Bathroom
            { name: 'Shower', icon: 'fas fa-shower', category: 'bathroom' },
            { name: 'Bath', icon: 'fas fa-bath', category: 'bathroom' },
            { name: 'Hair Dryer', icon: 'fas fa-wind', category: 'bathroom' },
            { name: 'Soap', icon: 'fas fa-soap', category: 'bathroom' },
            
            // Food & Drink
            { name: 'Coffee', icon: 'fas fa-coffee', category: 'food' },
            { name: 'Utensils', icon: 'fas fa-utensils', category: 'food' },
            { name: 'Wine Glass', icon: 'fas fa-wine-glass', category: 'food' },
            { name: 'Cocktail', icon: 'fas fa-cocktail', category: 'food' },
            { name: 'Mug Hot', icon: 'fas fa-mug-hot', category: 'food' },
            
            // Safety & Security
            { name: 'Lock', icon: 'fas fa-lock', category: 'security' },
            { name: 'Key', icon: 'fas fa-key', category: 'security' },
            { name: 'Shield', icon: 'fas fa-shield-alt', category: 'security' },
            
            // Services
            { name: 'Bell', icon: 'fas fa-bell', category: 'service' },
            { name: 'Concierge', icon: 'fas fa-concierge-bell', category: 'service' },
            { name: 'Umbrella', icon: 'fas fa-umbrella-beach', category: 'service' },
            { name: 'Spa', icon: 'fas fa-spa', category: 'service' },
            { name: 'Swimming Pool', icon: 'fas fa-swimming-pool', category: 'service' },
            { name: 'Dumbbell', icon: 'fas fa-dumbbell', category: 'service' },
            
            // Transportation
            { name: 'Car', icon: 'fas fa-car', category: 'transport' },
            { name: 'Parking', icon: 'fas fa-parking', category: 'transport' },
            { name: 'Plane', icon: 'fas fa-plane', category: 'transport' },
            
            // Entertainment
            { name: 'Music', icon: 'fas fa-music', category: 'entertainment' },
            { name: 'Gamepad', icon: 'fas fa-gamepad', category: 'entertainment' },
            { name: 'Film', icon: 'fas fa-film', category: 'entertainment' },
            
            // General
            { name: 'Star', icon: 'fas fa-star', category: 'general' },
            { name: 'Heart', icon: 'fas fa-heart', category: 'general' },
            { name: 'Home', icon: 'fas fa-home', category: 'general' },
            { name: 'Building', icon: 'fas fa-building', category: 'general' },
            { name: 'Hotel', icon: 'fas fa-hotel', category: 'general' },
            { name: 'Sun', icon: 'fas fa-sun', category: 'general' },
            
            // Work & Business
            { name: 'Desk', icon: 'fas fa-desktop', category: 'work' },
            { name: 'Briefcase', icon: 'fas fa-briefcase', category: 'work' },
            
            // Other
            { name: 'Smoking', icon: 'fas fa-smoking', category: 'other' },
            { name: 'Smoking Ban', icon: 'fas fa-smoking-ban', category: 'other' },
            { name: 'Paw', icon: 'fas fa-paw', category: 'other' },
            { name: 'Baby', icon: 'fas fa-baby', category: 'other' },
            { name: 'Wheelchair', icon: 'fas fa-wheelchair', category: 'other' },
            { name: 'Fire', icon: 'fas fa-fire', category: 'other' },
            { name: 'Lightbulb', icon: 'fas fa-lightbulb', category: 'other' },
            { name: 'Bolt', icon: 'fas fa-bolt', category: 'other' },
            
            // Facility
            { name: 'ลิฟท์', icon: 'fas fa-elevator', category: 'facility' },
            { name: 'Elevator', icon: 'fas fa-elevator', category: 'facility' }
        ];

        let currentForm = 'add'; // 'add' or 'edit'

        function populateIcons() {
            const iconGrid = document.getElementById('iconGrid');
            iconGrid.innerHTML = '';
            
            commonIcons.forEach(iconData => {
                // กรอง icon ที่ถูกใช้งานแล้วออก (ยกเว้น icon ปัจจุบันของ amenity ที่กำลังแก้ไข)
                const isUsed = usedIcons.includes(iconData.icon);
                const isCurrentEditIcon = (currentForm === 'edit' && iconData.icon === currentEditIcon);
                
                // ถ้า icon ถูกใช้งานแล้ว และไม่ใช่ icon ปัจจุบันที่กำลังแก้ไข ให้ข้าม
                if (isUsed && !isCurrentEditIcon) {
                    return;
                }
                
                const iconDiv = document.createElement('div');
                iconDiv.className = 'icon-item';
                iconDiv.style.cssText = 'padding: 15px; text-align: center; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white;';
                iconDiv.onmouseover = function() {
                    this.style.borderColor = '#667eea';
                    this.style.background = '#f0f4ff';
                    this.style.transform = 'scale(1.05)';
                };
                iconDiv.onmouseout = function() {
                    this.style.borderColor = '#e0e0e0';
                    this.style.background = 'white';
                    this.style.transform = 'scale(1)';
                };
                iconDiv.onclick = function() {
                    selectIcon(iconData.icon, iconData.name);
                };
                
                iconDiv.innerHTML = `
                    <i class="${iconData.icon}" style="font-size: 2rem; color: #667eea; margin-bottom: 8px; display: block;"></i>
                    <div style="font-size: 0.75rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${iconData.name}</div>
                    <div style="font-size: 0.65rem; color: #999; margin-top: 4px;">${iconData.icon}</div>
                `;
                
                iconDiv.setAttribute('data-name', iconData.name.toLowerCase());
                iconDiv.setAttribute('data-icon', iconData.icon);
                iconGrid.appendChild(iconDiv);
            });
        }

        function filterIcons() {
            const search = document.getElementById('iconSearch').value.toLowerCase();
            const items = document.querySelectorAll('.icon-item');
            
            items.forEach(item => {
                const name = item.getAttribute('data-name');
                const icon = item.getAttribute('data-icon').toLowerCase();
                if (name.includes(search) || icon.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function openIconPicker(form = 'add') {
            currentForm = form;
            // ถ้าเป็นการเพิ่มใหม่ ให้รีเซ็ต currentEditIcon
            if (form === 'add') {
                currentEditIcon = '';
            }
            document.getElementById('iconPickerModal').style.display = 'block';
            populateIcons();
        }

        function closeIconPicker() {
            document.getElementById('iconPickerModal').style.display = 'none';
        }

        function selectIcon(iconClass, iconName) {
            if (currentForm === 'edit') {
                document.getElementById('edit_amenity_icon').value = iconClass;
                document.getElementById('editPreviewIcon').className = iconClass;
                document.getElementById('editPreviewText').textContent = iconClass;
            } else {
                document.getElementById('amenity_icon').value = iconClass;
                document.getElementById('previewIcon').className = iconClass;
                document.getElementById('previewText').textContent = iconClass;
            }
            closeIconPicker();
        }

        function addToMasterList(amenityName) {
            // เติมชื่อในฟอร์ม (ใช้ภาษาไทยเป็นหลัก)
            document.querySelector('input[name="amenity_name_th"]').value = amenityName;
            
            // เลือก icon ที่เหมาะสมตามชื่อ
            let suggestedIcon = 'fas fa-star';
            const nameLower = amenityName.toLowerCase();
            
            if (nameLower.includes('wifi') || nameLower.includes('internet')) {
                suggestedIcon = 'fas fa-wifi';
            } else if (nameLower.includes('tv') || nameLower.includes('television')) {
                suggestedIcon = 'fas fa-tv';
            } else if (nameLower.includes('air') || nameLower.includes('conditioning') || nameLower.includes('ac')) {
                suggestedIcon = 'fas fa-snowflake';
            } else if (nameLower.includes('coffee')) {
                suggestedIcon = 'fas fa-coffee';
            } else if (nameLower.includes('safe') || nameLower.includes('lock')) {
                suggestedIcon = 'fas fa-lock';
            } else if (nameLower.includes('shower')) {
                suggestedIcon = 'fas fa-shower';
            } else if (nameLower.includes('bath')) {
                suggestedIcon = 'fas fa-bath';
            } else if (nameLower.includes('hair') || nameLower.includes('dryer')) {
                suggestedIcon = 'fas fa-wind';
            } else if (nameLower.includes('desk') || nameLower.includes('work')) {
                suggestedIcon = 'fas fa-desktop';
            } else if (nameLower.includes('balcony')) {
                suggestedIcon = 'fas fa-home';
            } else if (nameLower.includes('ลิฟท์') || nameLower.includes('elevator') || nameLower.includes('lift')) {
                suggestedIcon = 'fas fa-elevator';
            }
            
            // เติม icon
            document.getElementById('amenity_icon').value = suggestedIcon;
            document.getElementById('previewIcon').className = suggestedIcon;
            document.getElementById('previewText').textContent = suggestedIcon;
            
            // Scroll ไปที่ฟอร์ม
            document.querySelector('.card.mb-3').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Highlight ฟอร์ม
            const formCard = document.querySelector('.card.mb-3');
            formCard.style.border = '2px solid #667eea';
            formCard.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.3)';
            setTimeout(() => {
                formCard.style.border = '';
                formCard.style.boxShadow = '';
            }, 2000);
        }

        function editAmenity(amenity) {
            console.log('Edit Amenity called with:', amenity);
            
            // ตรวจสอบว่าฟอร์มมี action="update" หรือไม่
            const form = document.getElementById('editForm');
            const actionInput = form.querySelector('input[name="action"]');
            console.log('Form action value:', actionInput ? actionInput.value : 'NOT FOUND');
            
            document.getElementById('edit_amenity_id').value = amenity.amenity_id;
            document.getElementById('edit_amenity_name_th').value = amenity.amenity_name_th || amenity.amenity_name || '';
            document.getElementById('edit_amenity_name_en').value = amenity.amenity_name_en || '';
            document.getElementById('edit_amenity_name_zh').value = amenity.amenity_name_zh || '';
            document.getElementById('edit_amenity_icon').value = amenity.amenity_icon;
            document.getElementById('edit_amenity_category').value = amenity.amenity_category;
            document.getElementById('edit_display_order').value = amenity.display_order;
            document.getElementById('edit_is_active').checked = amenity.is_active == 1;
            
            // เก็บ icon ปัจจุบันไว้เพื่อไม่ให้กรองออกตอนเลือก icon
            currentEditIcon = amenity.amenity_icon || '';
            
            // Update preview
            if (document.getElementById('editPreviewIcon')) {
                document.getElementById('editPreviewIcon').className = amenity.amenity_icon;
                document.getElementById('editPreviewText').textContent = amenity.amenity_icon;
            }
            
            // ตรวจสอบอีกครั้งหลังจากเซ็ตค่าแล้ว
            console.log('Form values before showing modal:');
            console.log('- amenity_id:', document.getElementById('edit_amenity_id').value);
            console.log('- action:', actionInput ? actionInput.value : 'NOT FOUND');
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // ปิด modal เมื่อคลิกนอก modal
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const iconModal = document.getElementById('iconPickerModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === iconModal) {
                closeIconPicker();
            }
        }

        // ปิด modal ด้วยปุ่ม ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
                closeIconPicker();
            }
        });
        
        // เพิ่ม event listener สำหรับ form submit เพื่อ debug
        document.addEventListener('DOMContentLoaded', function() {
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    const formData = new FormData(this);
                    console.log('Edit Form Submitting with data:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ':', value);
                    }
                });
            }
        });
    </script>
</body>
</html>
