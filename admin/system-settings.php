<?php
/**
 * System Settings
 * ตั้งค่าระบบทั่วไป
 */

//// init for SESSION , PROJECT_PATH , etc..
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
    header('Location: ../login.php');
    exit;
}

$admin = new Admin();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'site_name' => $_POST['site_name'] ?? $_POST['site_name_th'] ?? '',
        'site_name_th' => $_POST['site_name_th'] ?? '',
        'site_name_en' => $_POST['site_name_en'] ?? '',
        'site_name_zh' => $_POST['site_name_zh'] ?? '',
        'site_url' => $_POST['site_url'],
        'timezone' => $_POST['timezone'],
        'date_format' => $_POST['date_format'],
        'time_format' => $_POST['time_format'],
        'currency' => $_POST['currency'],
        'currency_symbol' => $_POST['currency_symbol'],
        'default_language' => $_POST['default_language'],
        'items_per_page' => $_POST['items_per_page'],
        'enable_registration' => isset($_POST['enable_registration']) ? 1 : 0,
        'enable_social_login' => isset($_POST['enable_social_login']) ? 1 : 0,
        'require_email_verification' => isset($_POST['require_email_verification']) ? 1 : 0,
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
    ];
    
    if ($admin->updateSystemSettings($data)) {
        $message = 'บันทึกการตั้งค่าสำเร็จ!';
        $messageType = 'success';
    } else {
        $message = 'เกิดข้อผิดพลาดในการบันทึก';
        $messageType = 'error';
    }
}

$settings = $admin->getSystemSettings();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .settings-section h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .settings-section h3 i {
            color: #667eea;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .switch-label {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .switch-label strong {
            color: #2c3e50;
        }
        
        .switch-label small {
            color: #7f8c8d;
            font-size: 0.85rem;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #667eea;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .info-box i {
            color: #2196f3;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-sliders-h"></i> ตั้งค่าระบบ</h1>
                <p>จัดการการตั้งค่าทั่วไปของระบบ</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- General Settings -->
                <div class="settings-section">
                    <h3><i class="fas fa-cog"></i> การตั้งค่าทั่วไป</h3>
                    
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>ชื่อเว็บไซต์</label>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">ภาษาไทย</label>
                                <input type="text" name="site_name_th" value="<?= htmlspecialchars($settings['site_name_th'] ?? $settings['site_name'] ?? SITE_NAME) ?>" required>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">English</label>
                                <input type="text" name="site_name_en" value="<?= htmlspecialchars($settings['site_name_en'] ?? '') ?>">
                            </div>
                            <div>
                                <label style="font-size: 0.9rem; color: #666; display: block; margin-bottom: 0.5rem;">中文</label>
                                <input type="text" name="site_name_zh" value="<?= htmlspecialchars($settings['site_name_zh'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>URL เว็บไซต์</label>
                            <input type="url" name="site_url" value="<?= htmlspecialchars($settings['site_url'] ?? SITE_URL) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>เขตเวลา</label>
                            <select name="timezone">
                                <option value="Asia/Bangkok" <?= ($settings['timezone'] ?? 'Asia/Bangkok') == 'Asia/Bangkok' ? 'selected' : '' ?>>Bangkok (GMT+7)</option>
                                <option value="Asia/Singapore" <?= ($settings['timezone'] ?? '') == 'Asia/Singapore' ? 'selected' : '' ?>>Singapore (GMT+8)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') == 'UTC' ? 'selected' : '' ?>>UTC (GMT+0)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>รูปแบบวันที่</label>
                            <select name="date_format">
                                <option value="d/m/Y" <?= ($settings['date_format'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                <option value="Y-m-d" <?= ($settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                <option value="m/d/Y" <?= ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>รูปแบบเวลา</label>
                            <select name="time_format">
                                <option value="H:i" <?= ($settings['time_format'] ?? 'H:i') == 'H:i' ? 'selected' : '' ?>>24 ชั่วโมง (13:00)</option>
                                <option value="h:i A" <?= ($settings['time_format'] ?? '') == 'h:i A' ? 'selected' : '' ?>>12 ชั่วโมง (1:00 PM)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>สกุลเงิน</label>
                            <select name="currency">
                                <option value="THB" <?= ($settings['currency'] ?? 'THB') == 'THB' ? 'selected' : '' ?>>THB - บาท</option>
                                <option value="USD" <?= ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD - ดอลลาร์</option>
                                <option value="EUR" <?= ($settings['currency'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR - ยูโร</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>สัญลักษณ์สกุลเงิน</label>
                            <input type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '฿') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>ภาษาเริ่มต้น</label>
                            <select name="default_language">
                                <option value="th" <?= ($settings['default_language'] ?? 'th') == 'th' ? 'selected' : '' ?>>ไทย</option>
                                <option value="en" <?= ($settings['default_language'] ?? '') == 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>จำนวนรายการต่อหน้า</label>
                            <input type="number" name="items_per_page" value="<?= htmlspecialchars($settings['items_per_page'] ?? '12') ?>" min="5" max="100" required>
                        </div>
                    </div>
                </div>
                
                <!-- User Settings -->
                <div class="settings-section">
                    <h3><i class="fas fa-users"></i> การตั้งค่าผู้ใช้</h3>
                    
                    <div class="switch-container">
                        <div class="switch-label">
                            <strong>เปิดใช้งานการสมัครสมาชิก</strong>
                            <small>อนุญาตให้ผู้ใช้สมัครสมาชิกใหม่ได้</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="enable_registration" <?= ($settings['enable_registration'] ?? 1) ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="switch-container">
                        <div class="switch-label">
                            <strong>เปิดใช้งาน Social Login</strong>
                            <small>อนุญาตให้เข้าสู่ระบบด้วย Google, Facebook</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="enable_social_login" <?= ($settings['enable_social_login'] ?? 1) ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div class="switch-container">
                        <div class="switch-label">
                            <strong>ต้องยืนยันอีเมล</strong>
                            <small>ผู้ใช้ต้องยืนยันอีเมลก่อนเข้าใช้งาน</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="require_email_verification" <?= ($settings['require_email_verification'] ?? 0) ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- System Maintenance -->
                <div class="settings-section">
                    <h3><i class="fas fa-tools"></i> การบำรุงรักษาระบบ</h3>
                    
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>โหมดบำรุงรักษา:</strong> เมื่อเปิดใช้งาน จะมีเฉพาะ Admin เท่านั้นที่สามารถเข้าถึงเว็บไซต์ได้
                    </div>
                    
                    <div class="switch-container">
                        <div class="switch-label">
                            <strong>โหมดบำรุงรักษา</strong>
                            <small>เปิดเมื่อต้องการปิดปรับปรุงระบบชั่วคราว</small>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="maintenance_mode" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="settings-section">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่า
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
