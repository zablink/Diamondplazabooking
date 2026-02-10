<?php
// /booking/admin/rooms.php

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
$message = '';
$messageType = '';

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                // Debug: แสดงข้อมูลที่รับมา
                error_log("Add Room - POST data: " . print_r($_POST, true));
                
                // รับ amenities เป็น array จาก checkbox
                $amenities = $_POST['amenities'] ?? [];
                
                $data = [
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'description_th' => $_POST['description_th'] ?? $_POST['description'] ?? '',
                    'description_en' => $_POST['description_en'] ?? '',
                    'description_zh' => $_POST['description_zh'] ?? '',
                    'price' => $_POST['price'] ?? 0,
                    'occupancy' => $_POST['occupancy'] ?? 0,
                    'total_rooms' => $_POST['total_rooms'] ?? 0,
                    'size_sqm' => !empty($_POST['size_sqm']) ? (int)$_POST['size_sqm'] : null,
                    'bed_type' => $_POST['bed_type'] ?? null,
                    'bed_type_th' => $_POST['bed_type_th'] ?? $_POST['bed_type'] ?? null,
                    'bed_type_en' => $_POST['bed_type_en'] ?? '',
                    'bed_type_zh' => $_POST['bed_type_zh'] ?? '',
                    'amenities' => $amenities, // ส่งเป็น array ไป Admin class จะจัดการเอง
                    'breakfast_included' => isset($_POST['breakfast_included']) ? 1 : 0,
                    'breakfast_price' => $_POST['breakfast_price'] ?? 0,
                    'status' => !empty($_POST['status']) ? $_POST['status'] : 'unavailable'
                ];
                
                error_log("Add Room - Amenities: " . json_encode($amenities));
                error_log("Add Room - Data to create: " . print_r($data, true));
                
                if ($admin->createRoomType($data)) {
                    $message = 'เพิ่มประเภทห้องพักสำเร็จ!';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ไม่สามารถบันทึกข้อมูลได้';
                    $messageType = 'error';
                }
                break;
                
            case 'update':
                // Debug: แสดงข้อมูลที่รับมา
                error_log("Update Room - POST data: " . print_r($_POST, true));
                
                if (!isset($_POST['room_id']) || empty($_POST['room_id'])) {
                    throw new Exception('ไม่พบ Room ID');
                }
                
                // รับ amenities เป็น array จาก checkbox
                $amenities = $_POST['amenities'] ?? [];
                error_log("Update Room - Amenities received: " . json_encode($amenities));
                
                $data = [
                    'name' => $_POST['name'] ?? '',
                    'description' => $_POST['description'] ?? '',
                    'description_th' => $_POST['description_th'] ?? $_POST['description'] ?? '',
                    'description_en' => $_POST['description_en'] ?? '',
                    'description_zh' => $_POST['description_zh'] ?? '',
                    'price' => $_POST['price'] ?? 0,
                    'occupancy' => $_POST['occupancy'] ?? 0,
                    'total_rooms' => $_POST['total_rooms'] ?? 0,
                    'size_sqm' => !empty($_POST['size_sqm']) ? (int)$_POST['size_sqm'] : null,
                    'bed_type' => $_POST['bed_type'] ?? null,
                    'bed_type_th' => $_POST['bed_type_th'] ?? $_POST['bed_type'] ?? null,
                    'bed_type_en' => $_POST['bed_type_en'] ?? '',
                    'bed_type_zh' => $_POST['bed_type_zh'] ?? '',
                    'amenities' => $amenities, // ส่งเป็น array ไป Admin class จะจัดการเอง
                    'breakfast_included' => isset($_POST['breakfast_included']) ? 1 : 0,
                    'breakfast_price' => $_POST['breakfast_price'] ?? 0,
                    'status' => !empty($_POST['status']) ? $_POST['status'] : 'unavailable'
                ];
                
                // Debug: แสดงข้อมูลที่จะ update
                error_log("Update Room - Data to update: " . print_r($data, true));
                
                $result = $admin->updateRoomType($_POST['room_id'], $data);
                
                // Debug: แสดงผลลัพธ์
                error_log("Update Room - Result: " . ($result ? 'success' : 'failed'));
                
                if ($result) {
                    $message = 'อัปเดตข้อมูลสำเร็จ!';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ไม่สามารถบันทึกการแก้ไขได้';
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                if (!isset($_POST['room_id']) || empty($_POST['room_id'])) {
                    throw new Exception('ไม่พบ Room ID');
                }
                
                if ($admin->deleteRoomType($_POST['room_id'])) {
                    $message = 'ลบข้อมูลสำเร็จ!';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการลบข้อมูล: ไม่สามารถลบข้อมูลได้';
                    $messageType = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$roomTypes = $admin->getAllRoomTypes();

// ดึงข้อมูล amenities จากตาราง bk_amenities
$db = Database::getInstance();
$conn = $db->getConnection();
$amenitiesList = [];
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'bk_amenities'");
    if ($checkTable->rowCount() > 0) {
        $sql = "SELECT * FROM bk_amenities WHERE is_active = 1 ORDER BY display_order ASC, amenity_name ASC";
        $stmt = $conn->query($sql);
        $amenitiesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching amenities: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการห้องพัก - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-bed"></i> จัดการห้องพัก</h1>
                <p>จัดการประเภทห้องพัก ราคา และสิ่งอำนวยความสะดวก</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-3">
                <div class="card-header">
                    <h2><i class="fas fa-plus"></i> เพิ่มประเภทห้องใหม่</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>ชื่อประเภทห้อง <span style="color: red;">*</span></label>
                                <input type="text" name="name" required placeholder="เช่น Deluxe Room">
                            </div>
                            
                            <div class="form-group">
                                <label>ราคาต่อคืน (฿) <span style="color: red;">*</span></label>
                                <input type="number" name="price" step="0.01" required placeholder="2000">
                            </div>
                            
                            <div class="form-group">
                                <label>จำนวนผู้พักสูงสุด <span style="color: red;">*</span></label>
                                <input type="number" name="occupancy" required placeholder="2">
                            </div>
                            
                            <div class="form-group">
                                <label>จำนวนห้องทั้งหมด</label>
                                <input type="number" name="total_rooms" placeholder="10">
                            </div>
                            
                            <div class="form-group">
                                <label>ขนาดห้อง (ตร.ม.)</label>
                                <input type="number" name="size_sqm" placeholder="35" min="1">
                            </div>
                            
                            <div class="form-group">
                                <label>ประเภทเตียง (ภาษาไทย)</label>
                                <input type="text" name="bed_type_th" placeholder="เช่น เตียงคิงไซส์, เตียงควีนไซส์">
                            </div>
                            
                            <div class="form-group">
                                <label>ประเภทเตียง (ภาษาอังกฤษ)</label>
                                <input type="text" name="bed_type_en" placeholder="e.g. King Bed, Queen Bed">
                            </div>
                            
                            <div class="form-group">
                                <label>ประเภทเตียง (ภาษาจีน)</label>
                                <input type="text" name="bed_type_zh" placeholder="例如：特大床、大床">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>คำอธิบาย (ภาษาไทย)</label>
                            <textarea name="description_th" placeholder="อธิบายรายละเอียดห้องพัก (ภาษาไทย)..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>คำอธิบาย (ภาษาอังกฤษ)</label>
                            <textarea name="description_en" placeholder="Room description (English)..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>คำอธิบาย (ภาษาจีน)</label>
                            <textarea name="description_zh" placeholder="房间描述（中文）..."></textarea>
                        </div>
                        
                        <!-- Amenities Selector -->
                        <div class="form-group">
                            <label>สิ่งอำนวยความสะดวก (Amenities)</label>
                            <?php if (empty($amenitiesList)): ?>
                                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffc107; margin-bottom: 10px;">
                                    <i class="fas fa-exclamation-triangle" style="color: #856404;"></i>
                                    <span style="color: #856404;">ยังไม่มีรายการสิ่งอำนวยความสะดวกในระบบ</span>
                                    <a href="amenities.php" style="color: #667eea; text-decoration: underline; margin-left: 10px;">ไปเพิ่มที่หน้า Amenities</a>
                                </div>
                            <?php endif; ?>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
                                <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 15px;" id="amenities_checkboxes">
                                    <?php if (!empty($amenitiesList)): ?>
                                        <?php foreach ($amenitiesList as $amenity): ?>
                                            <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                                <input type="checkbox" name="amenities[]" value="<?= htmlspecialchars($amenity['amenity_name']) ?>" style="margin-right: 10px;">
                                                <i class="<?= htmlspecialchars($amenity['amenity_icon']) ?>" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                                <span><?= htmlspecialchars($amenity['amenity_name']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Fallback: แสดงรายการพื้นฐานถ้ายังไม่มีใน database -->
                                        <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                            <input type="checkbox" name="amenities[]" value="WiFi" style="margin-right: 10px;">
                                            <i class="fas fa-wifi" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                            <span>WiFi</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                            <input type="checkbox" name="amenities[]" value="TV" style="margin-right: 10px;">
                                            <i class="fas fa-tv" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                            <span>TV</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                            <input type="checkbox" name="amenities[]" value="Air Conditioning" style="margin-right: 10px;">
                                            <i class="fas fa-snowflake" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                            <span>Air Conditioning</span>
                                        </label>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="display: flex;">
                                <label style="display: flex; align-items: center;">
                                    <input type="checkbox" name="breakfast_included" value="1" style="width: 8%;">
                                    <span style="width:100%; padding-left: 6px;">รวมอาหารเช้าในราคา</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>ราคาอาหารเช้า (ถ้าไม่รวม)</label>
                                <input type="number" name="breakfast_price" step="0.01" placeholder="200">
                            </div>
                            
                            <div class="form-group">
                                <label>สถานะ</label>
                                <select name="status">
                                    <option value="unavailable" selected>ไม่พร้อมใช้งาน</option>
                                    <option value="available">พร้อมใช้งาน</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายการห้องพักทั้งหมด</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อห้อง</th>
                                    <th>ราคา/คืน</th>
                                    <th>จำนวนผู้พัก</th>
                                    <th>จำนวนห้อง</th>
                                    <th>อาหารเช้า</th>
                                    <th>สถานะ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($roomTypes)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">ยังไม่มีข้อมูลห้องพัก</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($roomTypes as $room): ?>
                                        <tr>
                                            <td><?= $room['room_type_id'] ?></td>
                                            <td><strong><?= htmlspecialchars($room['room_type_name']) ?></strong></td>
                                            <td>฿<?= number_format($room['base_price'], 0) ?></td>
                                            <td><?= $room['max_occupancy'] ?> คน</td>
                                            <td><?= $room['total_rooms'] ?? 0 ?> ห้อง</td>
                                            <td>
                                                <?php if ($room['breakfast_included']): ?>
                                                    <span class="badge badge-success">รวมแล้ว</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">+฿<?= number_format($room['breakfast_price'] ?? 0, 0) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status = $room['status'] ?? 'unavailable';
                                                if ($status == 'available'): 
                                                ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle"></i> พร้อมใช้งาน
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-times-circle"></i> ไม่พร้อมใช้งาน
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <!--
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editRoom(<?= htmlspecialchars(json_encode($room)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบข้อมูลนี้?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="room_id" value="<?= $room['room_type_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            -->

                                            <td>
                                                <!-- ปุ่มจัดการรูปภาพ (สีม่วง) -->
                                                <a href="room_images.php?room_id=<?= $room['room_type_id'] ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="จัดการรูปภาพ"
                                                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white;">
                                                    <i class="fas fa-images"></i>
                                                </a>
                                                
                                                <!-- ปุ่มแก้ไข (สีน้ำเงิน) -->
                                                <button class="btn btn-sm btn-primary" onclick="editRoom(<?= htmlspecialchars(json_encode($room)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <!-- ปุ่มลบ (สีแดง) -->
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบข้อมูลนี้?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="room_id" value="<?= $room['room_type_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Room Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> แก้ไขข้อมูลห้องพัก</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="room_id" id="edit_room_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ชื่อประเภทห้อง <span style="color: red;">*</span></label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ราคาต่อคืน (฿) <span style="color: red;">*</span></label>
                        <input type="number" name="price" id="edit_price" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>จำนวนผู้พักสูงสุด <span style="color: red;">*</span></label>
                        <input type="number" name="occupancy" id="edit_occupancy" required>
                    </div>
                    
                    <div class="form-group">
                        <label>จำนวนห้องทั้งหมด</label>
                        <input type="number" name="total_rooms" id="edit_total_rooms">
                    </div>
                    
                    <div class="form-group">
                        <label>ขนาดห้อง (ตร.ม.)</label>
                        <input type="number" name="size_sqm" id="edit_size_sqm" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>ประเภทเตียง (ภาษาไทย)</label>
                        <input type="text" name="bed_type_th" id="edit_bed_type_th" placeholder="เช่น เตียงคิงไซส์, เตียงควีนไซส์">
                    </div>
                    
                    <div class="form-group">
                        <label>ประเภทเตียง (ภาษาอังกฤษ)</label>
                        <input type="text" name="bed_type_en" id="edit_bed_type_en" placeholder="e.g. King Bed, Queen Bed">
                    </div>
                    
                    <div class="form-group">
                        <label>ประเภทเตียง (ภาษาจีน)</label>
                        <input type="text" name="bed_type_zh" id="edit_bed_type_zh" placeholder="例如：特大床、大床">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>คำอธิบาย (ภาษาไทย)</label>
                    <textarea name="description_th" id="edit_description_th" rows="3" placeholder="อธิบายรายละเอียดห้องพัก (ภาษาไทย)..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>คำอธิบาย (ภาษาอังกฤษ)</label>
                    <textarea name="description_en" id="edit_description_en" rows="3" placeholder="Room description (English)..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>คำอธิบาย (ภาษาจีน)</label>
                    <textarea name="description_zh" id="edit_description_zh" rows="3" placeholder="房间描述（中文）..."></textarea>
                </div>
                
                <!-- Amenities Selector -->
                <div class="form-group">
                    <label>สิ่งอำนวยความสะดวก (Amenities)</label>
                    <?php if (empty($amenitiesList)): ?>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffc107; margin-bottom: 10px;">
                            <i class="fas fa-exclamation-triangle" style="color: #856404;"></i>
                            <span style="color: #856404;">ยังไม่มีรายการสิ่งอำนวยความสะดวกในระบบ</span>
                            <a href="amenities.php" style="color: #667eea; text-decoration: underline; margin-left: 10px;">ไปเพิ่มที่หน้า Amenities</a>
                        </div>
                    <?php endif; ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
                        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 15px;" id="edit_amenities_checkboxes">
                            <?php if (!empty($amenitiesList)): ?>
                                <?php foreach ($amenitiesList as $amenity): ?>
                                    <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                        <input type="checkbox" name="amenities[]" value="<?= htmlspecialchars($amenity['amenity_name']) ?>" style="margin-right: 10px;">
                                        <i class="<?= htmlspecialchars($amenity['amenity_icon']) ?>" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                        <span><?= htmlspecialchars($amenity['amenity_name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback: แสดงรายการพื้นฐานถ้ายังไม่มีใน database -->
                                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                    <input type="checkbox" name="amenities[]" value="WiFi" style="margin-right: 10px;">
                                    <i class="fas fa-wifi" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                    <span>WiFi</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                    <input type="checkbox" name="amenities[]" value="TV" style="margin-right: 10px;">
                                    <i class="fas fa-tv" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                    <span>TV</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; cursor: pointer; white-space: nowrap;">
                                    <input type="checkbox" name="amenities[]" value="Air Conditioning" style="margin-right: 10px;">
                                    <i class="fas fa-snowflake" style="margin-right: 8px; color: #667eea; width: 18px; text-align: center;"></i>
                                    <span>Air Conditioning</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="display: flex;">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="breakfast_included" id="edit_breakfast_included" value="1" style="width: 8%;">
                            <span style="width:100%; padding-left: 6px;">รวมอาหารเช้าในราคา</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>ราคาอาหารเช้า (ถ้าไม่รวม)</label>
                        <input type="number" name="breakfast_price" id="edit_breakfast_price" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select name="status" id="edit_status">
                            <option value="unavailable">ไม่พร้อมใช้งาน</option>
                            <option value="available">พร้อมใช้งาน</option>
                        </select>
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
            margin: 3% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
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

    <script>
        function editRoom(room) {
            console.log('%c🔧 Opening Edit Modal', 'background: #9C27B0; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
            console.log('Room data:', room);
            
            try {
                // เติมข้อมูลเข้าฟอร์มพร้อม null check
                const setInputValue = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = value == 1 || value === true;
                        } else {
                            element.value = value || '';
                        }
                    } else {
                        console.warn(`Element with id '${id}' not found`);
                    }
                };
                
                setInputValue('edit_room_id', room.room_type_id);
                setInputValue('edit_name', room.room_type_name);
                setInputValue('edit_price', room.base_price);
                setInputValue('edit_occupancy', room.max_occupancy);
                setInputValue('edit_total_rooms', room.total_rooms);
                setInputValue('edit_size_sqm', room.size_sqm);
                setInputValue('edit_bed_type_th', room.bed_type_th || '');
                setInputValue('edit_bed_type_en', room.bed_type_en || '');
                setInputValue('edit_bed_type_zh', room.bed_type_zh || '');
                setInputValue('edit_description_th', room.description_th || '');
                setInputValue('edit_description_en', room.description_en || '');
                setInputValue('edit_description_zh', room.description_zh || '');
                setInputValue('edit_breakfast_included', room.breakfast_included);
                setInputValue('edit_breakfast_price', room.breakfast_price);
                setInputValue('edit_status', room.status || 'unavailable');
                
                // จัดการ amenities - แปลงเป็น array
                let amenitiesArray = [];
                
                if (room.amenities) {
                    console.log('Amenities raw:', room.amenities);
                    console.log('Amenities type:', typeof room.amenities);
                    
                    if (typeof room.amenities === 'string') {
                        try {
                            amenitiesArray = JSON.parse(room.amenities);
                        } catch (e) {
                            console.warn('Cannot parse amenities as JSON:', e);
                            amenitiesArray = [];
                        }
                    } else if (Array.isArray(room.amenities)) {
                        amenitiesArray = room.amenities;
                    }
                }
                
                // Uncheck all checkboxes first
                const checkboxes = document.querySelectorAll('#edit_amenities_checkboxes input[type="checkbox"]');
                if (checkboxes.length > 0) {
                    checkboxes.forEach(cb => cb.checked = false);
                    
                    // Check checkboxes ตามค่าที่มี
                    amenitiesArray.forEach(amenity => {
                        const checkbox = Array.from(checkboxes).find(cb => cb.value === amenity);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                } else {
                    console.warn('Amenities checkboxes container not found');
                }
                
                console.log('Amenities processed:', {
                    original: room.amenities,
                    array: amenitiesArray,
                    checked_count: amenitiesArray.length
                });
                
                console.group('📝 Form Data Populated');
                console.table({
                    room_id: room.room_type_id,
                    name: room.room_type_name,
                    price: room.base_price,
                    status: room.status || 'unavailable',
                    amenities_count: amenitiesArray.length,
                    amenities: amenitiesArray.join(', ')
                });
                console.groupEnd();
                
                // แสดง modal
                const modal = document.getElementById('editModal');
                if (modal) {
                    modal.style.display = 'block';
                } else {
                    throw new Error('Edit Modal not found in page');
                }
            } catch (error) {
                console.error('%c❌ Error in editRoom', 'background: #f44336; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
                console.error('Error details:', error);
                console.error('Stack trace:', error.stack);
                alert('เกิดข้อผิดพลาดในการเปิดฟอร์มแก้ไข:\n\n' + error.message + '\n\nดูรายละเอียดใน Console (F12)');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // AJAX Form Submit สำหรับ Edit Form
        document.addEventListener('DOMContentLoaded', function() {
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // ป้องกันการ submit แบบปกติ
                    
                    console.log('%c📤 Form Submitting', 'background: #2196F3; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
                    
                    const formData = new FormData(editForm);
                    
                    // แสดงข้อมูลแบบ object ที่สามารถ expand ได้
                    const formObject = {};
                    formData.forEach((value, key) => {
                        formObject[key] = value;
                    });
                    
                    console.group('📋 Form Data Details');
                    console.table(formObject); // แสดงแบบตาราง
                    console.log('Full Object:', formObject); // แสดงแบบ expandable
                    console.groupEnd();
                    
                    // แสดง loading indicator
                    const submitBtn = editForm.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
                    
                    // ส่งข้อมูลด้วย AJAX
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('%c✓ Response Received', 'background: #4CAF50; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
                        console.log('Status:', response.status, response.statusText);
                        return response.text();
                    })
                    .then(html => {
                        console.log('Response length:', html.length, 'bytes');
                        
                        // ตรวจสอบว่ามี error message ในหน้าหรือไม่
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const errorAlert = doc.querySelector('.alert-error');
                        
                        if (errorAlert) {
                            // มี error - แสดง alert พร้อมรายละเอียด
                            const errorMsg = errorAlert.textContent.trim();
                            
                            console.group('%c❌ Update Failed', 'background: #f44336; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
                            console.error('Error message from server:', errorMsg);
                            console.error('Check PHP error log for more details');
                            
                            // แสดง form data ที่ส่งไป
                            console.log('Form data that was sent:');
                            console.table(formObject);
                            console.groupEnd();
                            
                            // แสดง error popup ที่สวยงาม
                            // สร้าง overlay
                            const overlay = document.createElement('div');
                            overlay.style.cssText = `
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background: rgba(0,0,0,0.5);
                                z-index: 10001;
                            `;
                            overlay.onclick = function() {
                                this.remove();
                                errorDiv.remove();
                            };
                            document.body.appendChild(overlay);
                            
                            const errorDiv = document.createElement('div');
                            errorDiv.style.cssText = `
                                position: fixed;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                background: white;
                                padding: 30px;
                                border-radius: 15px;
                                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                                z-index: 10002;
                                max-width: 500px;
                                border-top: 5px solid #f44336;
                                animation: errorSlideDown 0.3s ease-out;
                            `;
                            errorDiv.innerHTML = `
                                <div style="text-align: center; margin-bottom: 20px;">
                                    <i class="fas fa-exclamation-circle" style="font-size: 60px; color: #f44336;"></i>
                                </div>
                                <h3 style="margin: 0 0 15px 0; text-align: center; color: #333;">
                                    ⚠️ เกิดข้อผิดพลาดในการบันทึก
                                </h3>
                                <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ff9800;">
                                    <p style="margin: 0; color: #e65100; line-height: 1.6; font-weight: 500;">
                                        ${errorMsg}
                                    </p>
                                </div>
                                <div style="background: #f5f5f5; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                                    <p style="margin: 0 0 8px 0; font-size: 13px; color: #666;">
                                        <strong>💡 เคล็ดลับการแก้ไข:</strong>
                                    </p>
                                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #666;">
                                        <li>ตรวจสอบว่ากรอกข้อมูลครบถ้วนหรือไม่</li>
                                        <li>ตรวจสอบ Console (F12) เพื่อดูรายละเอียดเพิ่มเติม</li>
                                        <li>ตรวจสอบ PHP Error Log ใน server</li>
                                    </ul>
                                </div>
                                <div style="text-align: center;">
                                    <button onclick="document.querySelectorAll('[style*=\\"z-index: 1000\\"]').forEach(el => el.remove())" style="padding: 12px 40px; background: #f44336; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; transition: background 0.3s;">
                                        <i class="fas fa-times"></i> ปิด
                                    </button>
                                </div>
                            `;
                            document.body.appendChild(errorDiv);
                            
                            // เพิ่ม keyframe animation
                            if (!document.getElementById('errorAnimationStyle')) {
                                const style = document.createElement('style');
                                style.id = 'errorAnimationStyle';
                                style.textContent = `
                                    @keyframes errorSlideDown {
                                        from {
                                            transform: translate(-50%, -60%);
                                            opacity: 0;
                                        }
                                        to {
                                            transform: translate(-50%, -50%);
                                            opacity: 1;
                                        }
                                    }
                                `;
                                document.head.appendChild(style);
                            }
                            
                            // คืนค่าปุ่ม
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        } else {
                            // สำเร็จ - ไม่แสดง alert แต่แสดงใน console
                            console.log('%c✓ Update Successful!', 'background: #4CAF50; color: white; font-size: 16px; padding: 8px 15px; border-radius: 5px; font-weight: bold;');
                            console.log('เคล็ดลับ: คลิกขวาที่ Console แล้วเลือก "Preserve log" เพื่อไม่ให้ log หายเมื่อ reload');
                            
                            // ปิด modal
                            closeEditModal();
                            
                            // Reload หน้าเพื่อแสดงข้อมูลใหม่
                            setTimeout(() => {
                                window.location.reload();
                            }, 300);
                        }
                    })
                    .catch(error => {
                        console.error('%c❌ Network Error', 'background: #f44336; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
                        console.error('Error details:', error);
                        
                        // แสดง error message แบบ popup
                        const errorDiv = document.createElement('div');
                        errorDiv.style.cssText = `
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            background: #f44336;
                            color: white;
                            padding: 20px 25px;
                            border-radius: 10px;
                            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
                            z-index: 10001;
                            max-width: 400px;
                            animation: slideIn 0.3s ease-out;
                        `;
                        errorDiv.innerHTML = `
                            <h3 style="margin: 0 0 10px 0; font-size: 18px;">
                                <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด
                            </h3>
                            <p style="margin: 0 0 15px 0; line-height: 1.5;">${error.message}</p>
                            <button onclick="this.parentElement.remove()" style="margin-top: 10px; padding: 8px 20px; background: white; color: #f44336; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                                ปิด
                            </button>
                        `;
                        document.body.appendChild(errorDiv);
                        
                        // แสดง alert สำหรับ error
                        alert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ!\n\n' + error.message);
                        
                        // คืนค่าปุ่ม
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    });
                });
            }
        });

        // ปิด modal เมื่อคลิกนอก modal
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // ปิด modal ด้วยปุ่ม ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
        
        // แสดงคำแนะนำใน Console
        console.log('%c💡 เคล็ดลับ: Preserve Console Log', 'background: #2196F3; color: white; font-size: 14px; padding: 5px 10px; border-radius: 5px;');
        console.log('1. คลิกขวาที่ Console');
        console.log('2. เลือก "Preserve log"');
        console.log('3. Log จะไม่หายเมื่อ refresh หน้า');
    </script>
</body>
</html>