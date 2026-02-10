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
    if (isset($_POST['action']) && $_POST['action'] === 'update_templates') {
        $templates = [];
        
        // รวบรวมข้อมูล templates จาก form
        foreach ($_POST as $key => $value) {
            if (strpos($key, '_') !== false) {
                $parts = explode('_', $key, 2);
                if (count($parts) == 2) {
                    $templateKey = $parts[0];
                    $field = $parts[1];
                    
                    if (!isset($templates[$templateKey])) {
                        $templates[$templateKey] = [];
                    }
                    
                    if ($field === 'name') {
                        $templates[$templateKey]['template_name'] = $value;
                    } elseif (in_array($field, ['th', 'en', 'zh'])) {
                        $templates[$templateKey]['content_' . $field] = $value;
                    }
                }
            }
        }
        
        if ($admin->updateEmailTemplates($templates)) {
            $message = 'บันทึกการตั้งค่าอีเมลสำเร็จ!';
            $messageType = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            $messageType = 'error';
        }
    }
    
    // จัดการอัปโหลด QR Code
    if (isset($_POST['action']) && $_POST['action'] === 'upload_qr_code') {
        $result = $admin->uploadQRCode($_FILES['qr_code_file'] ?? null, $_POST['qr_code_url'] ?? '');
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// ดึง QR Code URL ปัจจุบัน
$qrCodeUrl = $admin->getQRCodeUrl();

// ดึง templates ทั้งหมด
$templates = $admin->getEmailTemplates();

// ถ้ายังไม่มี templates ให้ใช้ default
if (empty($templates)) {
    $defaultTemplates = [
        ['template_key' => 'title', 'template_name' => 'หัวข้ออีเมล', 'content_th' => 'ยืนยันการจองห้องพัก', 'content_en' => 'Booking Confirmation', 'content_zh' => '预订确认'],
        ['template_key' => 'booking_success', 'template_name' => 'ข้อความสำเร็จ', 'content_th' => 'การจองสำเร็จ', 'content_en' => 'Booking Successful', 'content_zh' => '预订成功'],
        ['template_key' => 'booking_reference_label', 'template_name' => 'ป้ายรหัสการจอง', 'content_th' => 'รหัสการจอง', 'content_en' => 'Booking Reference', 'content_zh' => '预订编号'],
        ['template_key' => 'booking_details', 'template_name' => 'รายละเอียดการจอง', 'content_th' => 'รายละเอียดการจอง', 'content_en' => 'Booking Details', 'content_zh' => '预订详情'],
        ['template_key' => 'room_type', 'template_name' => 'ประเภทห้อง', 'content_th' => 'ประเภทห้อง', 'content_en' => 'Room Type', 'content_zh' => '房型'],
        ['template_key' => 'rooms', 'template_name' => 'ห้อง', 'content_th' => 'ห้อง', 'content_en' => 'rooms', 'content_zh' => '间'],
        ['template_key' => 'check_in', 'template_name' => 'วันเช็คอิน', 'content_th' => 'วันเช็คอิน', 'content_en' => 'Check-in Date', 'content_zh' => '入住日期'],
        ['template_key' => 'check_out', 'template_name' => 'วันเช็คเอาท์', 'content_th' => 'วันเช็คเอาท์', 'content_en' => 'Check-out Date', 'content_zh' => '退房日期'],
        ['template_key' => 'nights', 'template_name' => 'คืน', 'content_th' => 'คืน', 'content_en' => 'nights', 'content_zh' => '晚'],
        ['template_key' => 'guests', 'template_name' => 'ผู้เข้าพัก', 'content_th' => 'ผู้เข้าพัก', 'content_en' => 'Guests', 'content_zh' => '住客'],
        ['template_key' => 'adults', 'template_name' => 'ผู้ใหญ่', 'content_th' => 'ผู้ใหญ่', 'content_en' => 'adults', 'content_zh' => '成人'],
        ['template_key' => 'children', 'template_name' => 'เด็ก', 'content_th' => 'เด็ก', 'content_en' => 'children', 'content_zh' => '儿童'],
        ['template_key' => 'guest_info', 'template_name' => 'ข้อมูลผู้เข้าพัก', 'content_th' => 'ข้อมูลผู้เข้าพัก', 'content_en' => 'Guest Information', 'content_zh' => '住客信息'],
        ['template_key' => 'full_name', 'template_name' => 'ชื่อ-นามสกุล', 'content_th' => 'ชื่อ-นามสกุล', 'content_en' => 'Full Name', 'content_zh' => '姓名'],
        ['template_key' => 'email', 'template_name' => 'อีเมล', 'content_th' => 'อีเมล', 'content_en' => 'Email', 'content_zh' => '邮箱'],
        ['template_key' => 'phone', 'template_name' => 'เบอร์โทรศัพท์', 'content_th' => 'เบอร์โทรศัพท์', 'content_en' => 'Phone', 'content_zh' => '电话'],
        ['template_key' => 'payment_summary', 'template_name' => 'สรุปการชำระเงิน', 'content_th' => 'สรุปการชำระเงิน', 'content_en' => 'Payment Summary', 'content_zh' => '付款摘要'],
        ['template_key' => 'room_price', 'template_name' => 'ค่าห้องพัก', 'content_th' => 'ค่าห้องพัก', 'content_en' => 'Room Price', 'content_zh' => '房价'],
        ['template_key' => 'breakfast_price', 'template_name' => 'ค่าอาหารเช้า', 'content_th' => 'ค่าอาหารเช้า', 'content_en' => 'Breakfast Price', 'content_zh' => '早餐价格'],
        ['template_key' => 'tax', 'template_name' => 'ภาษีมูลค่าเพิ่ม', 'content_th' => 'ภาษีมูลค่าเพิ่ม (7%)', 'content_en' => 'VAT (7%)', 'content_zh' => '增值税 (7%)'],
        ['template_key' => 'service_charge', 'template_name' => 'ค่าบริการ', 'content_th' => 'ค่าบริการ (10%)', 'content_en' => 'Service Charge (10%)', 'content_zh' => '服务费 (10%)'],
        ['template_key' => 'total_amount', 'template_name' => 'ยอดรวมทั้งหมด', 'content_th' => 'ยอดรวมทั้งหมด', 'content_en' => 'Total Amount', 'content_zh' => '总金额'],
        ['template_key' => 'payment_method', 'template_name' => 'วิธีการชำระเงิน', 'content_th' => 'วิธีการชำระเงิน', 'content_en' => 'Payment Method', 'content_zh' => '付款方式'],
        ['template_key' => 'payment_counter', 'template_name' => 'ชำระเงินที่เคาน์เตอร์', 'content_th' => 'กรุณาชำระเงินที่หน้าเคาน์เตอร์ของโรงแรม', 'content_en' => 'Please pay at the hotel counter', 'content_zh' => '请在酒店前台付款'],
        ['template_key' => 'payment_qr', 'template_name' => 'ชำระเงินผ่าน QR', 'content_th' => 'หรือสแกน QR Code ด้านล่างเพื่อชำระเงินผ่าน PromptPay ได้ทันที', 'content_en' => 'or scan the QR Code below to pay via PromptPay immediately', 'content_zh' => '或扫描下方二维码通过 PromptPay 立即付款'],
        ['template_key' => 'scan_qr', 'template_name' => 'สแกน QR Code', 'content_th' => 'สแกน QR Code เพื่อชำระเงินผ่าน PromptPay', 'content_en' => 'Scan QR Code to pay via PromptPay', 'content_zh' => '扫描二维码通过 PromptPay 付款'],
        ['template_key' => 'amount', 'template_name' => 'ยอดเงิน', 'content_th' => 'ยอดเงิน', 'content_en' => 'Amount', 'content_zh' => '金额'],
        ['template_key' => 'note', 'template_name' => 'หมายเหตุ', 'content_th' => 'หมายเหตุ', 'content_en' => 'Note', 'content_zh' => '备注'],
        ['template_key' => 'note_text', 'template_name' => 'ข้อความหมายเหตุ', 'content_th' => 'กรุณานำรหัสการจอง', 'content_en' => 'Please bring your booking reference', 'content_zh' => '请携带您的预订编号'],
        ['template_key' => 'note_show', 'template_name' => 'แสดงที่เคาน์เตอร์', 'content_th' => 'มาแสดงที่หน้าเคาน์เตอร์เมื่อเช็คอิน', 'content_en' => 'to show at the counter when checking in', 'content_zh' => '在办理入住时向前台出示'],
        ['template_key' => 'important_info', 'template_name' => 'ข้อมูลสำคัญ', 'content_th' => 'ข้อมูลสำคัญ', 'content_en' => 'Important Information', 'content_zh' => '重要信息'],
        ['template_key' => 'check_in_time', 'template_name' => 'เวลาเช็คอิน/เอาท์', 'content_th' => 'เวลาเช็คอิน: 14:00 น. / เช็คเอาท์: 12:00 น.', 'content_en' => 'Check-in time: 14:00 / Check-out time: 12:00', 'content_zh' => '入住时间：14:00 / 退房时间：12:00'],
        ['template_key' => 'arrival_time', 'template_name' => 'เวลามาถึง', 'content_th' => 'กรุณามาถึงก่อนเวลา 18:00 น. หากมาถึงช้ากรุณาแจ้งล่วงหน้า', 'content_en' => 'Please arrive before 18:00. If arriving late, please notify in advance', 'content_zh' => '请在 18:00 前到达。如晚到，请提前通知'],
        ['template_key' => 'bring_reference', 'template_name' => 'นำรหัสการจอง', 'content_th' => 'กรุณานำรหัสการจองมาด้วยเมื่อเช็คอิน', 'content_en' => 'Please bring your booking reference when checking in', 'content_zh' => '办理入住时请携带您的预订编号'],
        ['template_key' => 'cancel_info', 'template_name' => 'ข้อมูลการยกเลิก', 'content_th' => 'หากต้องการยกเลิกหรือเปลี่ยนแปลงการจอง กรุณาติดต่อโรงแรมล่วงหน้า', 'content_en' => 'If you need to cancel or modify your booking, please contact the hotel in advance', 'content_zh' => '如需取消或修改预订，请提前联系酒店'],
        ['template_key' => 'thank_you', 'template_name' => 'ขอบคุณ', 'content_th' => 'ขอบคุณที่เลือกใช้บริการของเรา', 'content_en' => 'Thank you for choosing our service', 'content_zh' => '感谢您选择我们的服务'],
        ['template_key' => 'qr_code_not_available', 'template_name' => 'QR Code ไม่พร้อมใช้งาน', 'content_th' => 'QR Code ไม่พร้อมใช้งาน', 'content_en' => 'QR Code not available', 'content_zh' => '二维码不可用']
    ];
    $templates = $defaultTemplates;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อความอีเมล - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .template-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .template-item {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .template-item:last-child {
            border-bottom: none;
        }
        
        .template-header {
            margin-bottom: 1rem;
        }
        
        .template-header strong {
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .template-header small {
            color: #7f8c8d;
            display: block;
            margin-top: 0.25rem;
        }
        
        .template-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .template-field {
            display: flex;
            flex-direction: column;
        }
        
        .template-field label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .template-field textarea {
            min-height: 60px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-envelope"></i> จัดการข้อความอีเมล</h1>
                <p>แก้ไขข้อความที่ใช้ในอีเมลยืนยันการจอง (รองรับ 3 ภาษา)</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- QR Code Section -->
            <div class="template-section">
                <h2><i class="fas fa-qrcode"></i> QR Code สำหรับชำระเงิน</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_qr_code">
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label><strong>QR Code ปัจจุบัน:</strong></label>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; margin-top: 5px;">
                            <?php if ($qrCodeUrl): ?>
                                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                    <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code" style="max-width: 150px; height: auto; border: 2px solid #ddd; border-radius: 5px; padding: 5px; background: white;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="font-weight: 600; margin-bottom: 5px;">URL:</div>
                                        <a href="<?= htmlspecialchars($qrCodeUrl) ?>" target="_blank" style="color: #667eea; word-break: break-all; font-size: 0.9rem;">
                                            <?= htmlspecialchars($qrCodeUrl) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="color: #999;">ยังไม่ได้ตั้งค่า QR Code</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label><strong>อัปโหลด QR Code ใหม่:</strong></label>
                        <input type="file" name="qr_code_file" accept="image/jpeg,image/jpg,image/png,image/gif" style="margin-top: 5px; padding: 8px;">
                        <small style="color: #666; display: block; margin-top: 5px;">รองรับไฟล์ JPG, PNG, GIF (แนะนำขนาด 300x300px ขึ้นไป, สูงสุด 5MB)</small>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label><strong>หรือกรอก URL QR Code:</strong></label>
                        <input type="url" name="qr_code_url" value="<?= htmlspecialchars($qrCodeUrl) ?>" placeholder="https://example.com/images/qr-code.jpg" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px;">
                        <small style="color: #666; display: block; margin-top: 5px;">กรอก URL แบบเต็มของ QR Code image (ถ้าไม่ต้องการอัปโหลดไฟล์)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก QR Code
                    </button>
                </form>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_templates">
                
                <div class="template-section">
                    <h2><i class="fas fa-list"></i> รายการข้อความ (<?= count($templates) ?> รายการ)</h2>
                    
                    <?php foreach ($templates as $template): ?>
                        <div class="template-item">
                            <div class="template-header">
                                <strong><?= htmlspecialchars($template['template_name']) ?></strong>
                                <small>Key: <?= htmlspecialchars($template['template_key']) ?></small>
                            </div>
                            
                            <div class="template-fields">
                                <div class="template-field">
                                    <label>ภาษาไทย</label>
                                    <textarea 
                                        name="<?= htmlspecialchars($template['template_key']) ?>_th" 
                                        rows="2"
                                    ><?= htmlspecialchars($template['content_th'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="template-field">
                                    <label>English</label>
                                    <textarea 
                                        name="<?= htmlspecialchars($template['template_key']) ?>_en" 
                                        rows="2"
                                    ><?= htmlspecialchars($template['content_en'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="template-field">
                                    <label>中文</label>
                                    <textarea 
                                        name="<?= htmlspecialchars($template['template_key']) ?>_zh" 
                                        rows="2"
                                    ><?= htmlspecialchars($template['content_zh'] ?? '') ?></textarea>
                                </div>
                                
                                <input type="hidden" name="<?= htmlspecialchars($template['template_key']) ?>_name" value="<?= htmlspecialchars($template['template_name']) ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 40px; font-size: 1.1rem;">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่า
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
