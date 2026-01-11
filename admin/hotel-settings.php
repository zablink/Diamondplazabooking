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
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'address' => $_POST['address'],
        'city' => $_POST['city'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'amenities' => $_POST['amenities'] ?? []
    ];
    
    if ($admin->updateHotelSettings($data)) {
        $message = 'อัปเดตข้อมูลโรงแรมสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
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
                            <label>ชื่อโรงแรม</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($hotel['hotel_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>คำอธิบาย</label>
                            <textarea name="description" rows="5"><?= htmlspecialchars($hotel['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>ที่อยู่</label>
                                <input type="text" name="address" value="<?= htmlspecialchars($hotel['address'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>เมือง</label>
                                <input type="text" name="city" value="<?= htmlspecialchars($hotel['city'] ?? '') ?>">
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