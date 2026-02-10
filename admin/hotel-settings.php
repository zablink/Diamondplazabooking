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

require_once PROJECT_ROOT . '/includes/init.php';
require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? $_POST['name_th'] ?? '',
        'name_th' => $_POST['name_th'] ?? '',
        'name_en' => $_POST['name_en'] ?? '',
        'name_zh' => $_POST['name_zh'] ?? '',
        'description' => $_POST['description'] ?? $_POST['description_th'] ?? '',
        'description_th' => $_POST['description_th'] ?? '',
        'description_en' => $_POST['description_en'] ?? '',
        'description_zh' => $_POST['description_zh'] ?? '',
        'about_description' => $_POST['about_description'] ?? $_POST['about_description_th'] ?? '',
        'about_description_th' => $_POST['about_description_th'] ?? '',
        'about_description_en' => $_POST['about_description_en'] ?? '',
        'about_description_zh' => $_POST['about_description_zh'] ?? '',
        'address' => $_POST['address'] ?? $_POST['address_th'] ?? '',
        'address_th' => $_POST['address_th'] ?? '',
        'address_en' => $_POST['address_en'] ?? '',
        'address_zh' => $_POST['address_zh'] ?? '',
        'city' => $_POST['city'] ?? $_POST['city_th'] ?? '',
        'city_th' => $_POST['city_th'] ?? '',
        'city_en' => $_POST['city_en'] ?? '',
        'city_zh' => $_POST['city_zh'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'amenities' => $_POST['amenities'] ?? []
    ];
    
    // Debug: log ข้อมูลที่รับมา
    error_log("Hotel Settings POST data: " . print_r($data, true));
    
    if ($admin->updateHotelSettings($data)) {
        $message = 'อัปเดตข้อมูลโรงแรมสำเร็จ!';
        $messageType = 'success';
        // Reload ข้อมูลใหม่
        $hotel = $admin->getHotelSettings();
    } else {
        $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล กรุณาตรวจสอบ Error Log';
        $messageType = 'error';
    }
}

$hotel = $admin->getHotelSettings();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าโรงแรม - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> ตั้งค่าโรงแรม</h1>
                <p>จัดการข้อมูลและการตั้งค่าโรงแรม</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-hotel"></i> ข้อมูลโรงแรม</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>ชื่อโรงแรม <span style="color: red;">*</span></label>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                <input type="text" name="name_th" value="<?= htmlspecialchars($hotel['hotel_name_th'] ?? $hotel['hotel_name'] ?? '') ?>" required>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                <input type="text" name="name_en" value="<?= htmlspecialchars($hotel['hotel_name_en'] ?? '') ?>">
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                <input type="text" name="name_zh" value="<?= htmlspecialchars($hotel['hotel_name_zh'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>คำอธิบายโรงแรม</label>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                <textarea name="description_th" rows="5"><?= htmlspecialchars($hotel['description_th'] ?? $hotel['description'] ?? '') ?></textarea>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                <textarea name="description_en" rows="5"><?= htmlspecialchars($hotel['description_en'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                <textarea name="description_zh" rows="5"><?= htmlspecialchars($hotel['description_zh'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>ข้อความ About ใน Footer <small style="color: #666;">(สำหรับแสดงในส่วน Footer)</small></label>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                <textarea name="about_description_th" rows="4" placeholder="เราคือแพลตฟอร์มจองโรงแรมออนไลน์ที่ให้บริการโรงแรมคุณภาพทั่วประเทศไทย"><?= htmlspecialchars($hotel['about_description_th'] ?? $hotel['about_description'] ?? '') ?></textarea>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                <textarea name="about_description_en" rows="4" placeholder="We are an online hotel booking platform offering quality hotels throughout Thailand"><?= htmlspecialchars($hotel['about_description_en'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                <textarea name="about_description_zh" rows="4" placeholder="我们是在线酒店预订平台，提供全泰国优质酒店服务"><?= htmlspecialchars($hotel['about_description_zh'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>ที่อยู่</label>
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                    <input type="text" name="address_th" value="<?= htmlspecialchars($hotel['address_th'] ?? $hotel['address'] ?? '') ?>">
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                    <input type="text" name="address_en" value="<?= htmlspecialchars($hotel['address_en'] ?? '') ?>">
                                </div>
                                <div>
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                    <input type="text" name="address_zh" value="<?= htmlspecialchars($hotel['address_zh'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>เมือง</label>
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                    <input type="text" name="city_th" value="<?= htmlspecialchars($hotel['city_th'] ?? $hotel['city'] ?? '') ?>">
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                    <input type="text" name="city_en" value="<?= htmlspecialchars($hotel['city_en'] ?? '') ?>">
                                </div>
                                <div>
                                    <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                    <input type="text" name="city_zh" value="<?= htmlspecialchars($hotel['city_zh'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>เบอร์โทร</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($hotel['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>อีเมล</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($hotel['email'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> บันทึกการตั้งค่า
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>