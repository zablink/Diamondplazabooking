<?php
/**
 * Email Helper - สำหรับส่งอีเมลยืนยันการจองพร้อม QR Code
 */

require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/includes/lang.php';

class EmailHelper {
    private const RESERVATION_EMAIL = 'reservation_s@diamondplazasurat.com';
    private const HOTEL_LOGO_URL = 'https://diamondplazasurat.com/wp-content/uploads/2022/02/logo-64.png';
    
    /**
     * ส่งอีเมลยืนยันการจองพร้อม QR Code PromptPay
     */
    public static function sendBookingConfirmationEmail($booking_id) {
        try {
            error_log("=== START EMAIL SENDING ===");
            error_log("Booking ID: $booking_id");
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // ดึงข้อมูลการจอง
            // ใช้ alias เพื่อให้ compatible กับโค้ดเดิมที่ใช้ check_in_date, check_out_date และ total_amount
            $sql = "SELECT b.*, 
                           b.check_in as check_in_date, 
                           b.check_out as check_out_date,
                           b.total_price as total_amount,
                           rt.room_type_name, h.hotel_name, h.phone as hotel_phone, h.email as hotel_email, h.address
                    FROM bk_bookings b
                    LEFT JOIN bk_room_types rt ON b.room_type_id = rt.room_type_id
                    LEFT JOIN bk_hotels h ON rt.hotel_id = h.hotel_id
                    WHERE b.booking_id = :booking_id";
            
            error_log("Email SQL: $sql");
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errorInfo = $conn->errorInfo();
                error_log("❌ Failed to prepare email SQL: " . print_r($errorInfo, true));
                return false;
            }
            
            $stmt->execute(['booking_id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                error_log("❌ Booking not found: $booking_id");
                return false;
            }
            
            error_log("Booking found - Reference: " . $booking['booking_reference']);
            error_log("Email to: " . $booking['email']);
            
            // ดึง QR Code URL จาก settings
            $qrCodeUrl = self::getQRCodeUrlFromSettings($conn);
            
            if (empty($qrCodeUrl)) {
                // Fallback: ใช้ default path
                $qrCodePath = PROJECT_ROOT . '/images/QR-Diamond.jpg';
                if (file_exists($qrCodePath) && defined('SITE_URL')) {
                    $qrCodeUrl = rtrim(SITE_URL, '/') . '/images/QR-Diamond.jpg';
                    error_log("✅ Using default QR Code URL: $qrCodeUrl");
                } else {
                    error_log("❌ QR Code not found in settings or default path");
                }
            } else {
                error_log("✅ QR Code URL from settings: $qrCodeUrl");
            }
            
            // ใช้ภาษาตามที่ผู้ใช้เลือก
            $lang = getCurrentLanguage();
            error_log("Current Language: $lang");

            // ดึงข้อมูลโรงแรมจาก Hotel Setting (หน้า admin)
            $hotelSettings = self::getHotelSettingsForEmail($conn, $lang);
            
            // สร้างเนื้อหาอีเมล
            error_log("Generating email content...");
            $emailContent = self::generateEmailContent($booking, $qrCodeUrl, $lang, $hotelSettings);
            error_log("Email content length: " . strlen($emailContent) . " bytes");
            
            // ส่งอีเมล
            $to = $booking['email'];
            $subject = self::getEmailSubject($booking['booking_reference']);
            
            $fromEmail = self::RESERVATION_EMAIL;
            $replyToEmail = self::RESERVATION_EMAIL;
            
            // ใช้ HTML email แบบธรรมดา (ไม่ต้อง multipart/related เพราะใช้ URL)
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . $fromEmail . "\r\n";
            $headers .= "Reply-To: " . $replyToEmail . "\r\n";
            $headers .= "Bcc: " . self::RESERVATION_EMAIL . "\r\n";
            
            $message = $emailContent;
            
            error_log("Sending email...");
            error_log("   To: $to");
            error_log("   Subject: $subject");
            error_log("   From: $fromEmail");
            error_log("   QR Code URL: " . ($qrCodeUrl ?: 'NOT SET'));
            
            $result = mail($to, $subject, $message, $headers);
            
            if ($result) {
                error_log("✅ Email sent successfully to: $to");
                error_log("=== END EMAIL SENDING (SUCCESS) ===");
                return true;
            } else {
                $lastError = error_get_last();
                error_log("❌ Failed to send email to: $to");
                error_log("   Last PHP error: " . print_r($lastError, true));
                error_log("=== END EMAIL SENDING (FAILED) ===");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Exception in sendBookingConfirmationEmail:");
            error_log("   Message: " . $e->getMessage());
            error_log("   File: " . $e->getFile() . ":" . $e->getLine());
            error_log("   Trace: " . $e->getTraceAsString());
            error_log("=== END EMAIL SENDING (EXCEPTION) ===");
            return false;
        }
    }
    
    /**
     * ดึง subject ของอีเมลตามภาษา
     */
    private static function getEmailSubject($bookingReference) {
        return 'Diamond Plaza Surat Hotel : Booking Confirmation - ' . $bookingReference;
    }
    
    /**
     * ดึง QR Code URL จาก settings
     */
    private static function getQRCodeUrlFromSettings($conn) {
        try {
            // ตรวจสอบว่ามี column qr_code_url ใน bk_system_settings หรือไม่
            $checkCol = $conn->query("SHOW COLUMNS FROM bk_system_settings LIKE 'qr_code_url'");
            if ($checkCol && $checkCol->rowCount() > 0) {
                $sql = "SELECT qr_code_url FROM bk_system_settings WHERE id = 1 LIMIT 1";
                $stmt = $conn->query($sql);
                $result = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                
                if ($result && !empty($result['qr_code_url'])) {
                    return $result['qr_code_url'];
                }
            }
        } catch (Exception $e) {
            error_log("Error getting QR code URL from settings: " . $e->getMessage());
        }
        
        return '';
    }

    /**
     * ดึงข้อมูล Hotel Setting สำหรับใช้ในอีเมล (ที่อยู่/โทรศัพท์/ชื่อ)
     */
    private static function getHotelSettingsForEmail(PDO $conn, string $lang): array {
        try {
            $stmt = $conn->query("SELECT * FROM bk_hotel_settings WHERE hotel_id = 1 LIMIT 1");
            $settings = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if (!$settings) {
                return [];
            }

            // เลือก address ตามภาษา ถ้ามี
            $addrKey = 'address_' . $lang;
            if (!empty($settings[$addrKey])) {
                $settings['address'] = $settings[$addrKey];
            } elseif (!empty($settings['address'])) {
                $settings['address'] = $settings['address'];
            }

            // เลือก hotel_name ตามภาษา ถ้ามี (เผื่อใช้ใน footer)
            $nameKey = 'hotel_name_' . $lang;
            if (!empty($settings[$nameKey])) {
                $settings['hotel_name'] = $settings[$nameKey];
            }

            return $settings;
        } catch (Exception $e) {
            error_log("Error fetching hotel settings for email: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงข้อความตามภาษา (จาก database หรือ fallback)
     */
    private static function getEmailText($lang, $key) {
        // ลองดึงจาก database ก่อน
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $langColumn = 'content_' . $lang;
            
            // ตรวจสอบว่ามี column นี้หรือไม่
            $checkCol = $conn->query("SHOW COLUMNS FROM bk_email_templates LIKE '$langColumn'");
            if ($checkCol->rowCount() > 0) {
                $sql = "SELECT $langColumn as content FROM bk_email_templates WHERE template_key = :key LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->execute([':key' => $key]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['content'])) {
                    return $result['content'];
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching email template from database: " . $e->getMessage());
        }
        
        // Fallback to hardcoded texts
        $texts = [
            'th' => [
                'title' => 'ยืนยันการจองห้องพัก',
                'booking_success' => 'การจองสำเร็จ',
                'booking_reference_label' => 'รหัสการจอง',
                'booking_details' => 'รายละเอียดการจอง',
                'room_type' => 'ประเภทห้อง',
                'rooms' => 'ห้อง',
                'check_in' => 'วันเช็คอิน',
                'check_out' => 'วันเช็คเอาท์',
                'nights' => 'คืน',
                'guests' => 'ผู้เข้าพัก',
                'adults' => 'ผู้ใหญ่',
                'children' => 'เด็ก',
                'guest_info' => 'ข้อมูลผู้เข้าพัก',
                'full_name' => 'ชื่อ-นามสกุล',
                'email' => 'อีเมล',
                'phone' => 'เบอร์โทรศัพท์',
                'payment_summary' => 'สรุปการชำระเงิน',
                'room_price' => 'ค่าห้องพัก',
                'breakfast_price' => 'ค่าอาหารเช้า',
                'tax' => 'ภาษีมูลค่าเพิ่ม (7%)',
                'service_charge' => 'ค่าบริการ (10%)',
                'total_amount' => 'ยอดรวมทั้งหมด',
                'payment_method' => 'วิธีการชำระเงิน',
                'payment_counter' => 'กรุณาชำระเงินที่หน้าเคาน์เตอร์ของโรงแรม',
                'payment_qr' => 'หรือสแกน QR Code ด้านล่างเพื่อชำระเงินผ่าน PromptPay ได้ทันที',
                'scan_qr' => 'สแกน QR Code เพื่อชำระเงินผ่าน PromptPay',
                'amount' => 'ยอดเงิน',
                'note' => 'หมายเหตุ',
                'note_text' => 'กรุณานำรหัสการจอง',
                'note_show' => 'มาแสดงที่หน้าเคาน์เตอร์เมื่อเช็คอิน',
                'important_info' => 'ข้อมูลสำคัญ',
                'check_in_time' => 'เวลาเช็คอิน: 14:00 น. / เช็คเอาท์: 12:00 น.',
                'arrival_time' => 'กรุณามาถึงก่อนเวลา 18:00 น. หากมาถึงช้ากรุณาแจ้งล่วงหน้า',
                'bring_reference' => 'กรุณานำรหัสการจองมาด้วยเมื่อเช็คอิน',
                'cancel_info' => 'หากต้องการยกเลิกหรือเปลี่ยนแปลงการจอง กรุณาติดต่อโรงแรมล่วงหน้า',
                'thank_you' => 'ขอบคุณที่เลือกใช้บริการของเรา',
                'qr_code_not_available' => 'QR Code ไม่พร้อมใช้งาน'
            ],
            'en' => [
                'title' => 'Booking Confirmation',
                'booking_success' => 'Booking Successful',
                'booking_reference_label' => 'Booking Reference',
                'booking_details' => 'Booking Details',
                'room_type' => 'Room Type',
                'rooms' => 'rooms',
                'check_in' => 'Check-in Date',
                'check_out' => 'Check-out Date',
                'nights' => 'nights',
                'guests' => 'Guests',
                'adults' => 'adults',
                'children' => 'children',
                'guest_info' => 'Guest Information',
                'full_name' => 'Full Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'payment_summary' => 'Payment Summary',
                'room_price' => 'Room Price',
                'breakfast_price' => 'Breakfast Price',
                'tax' => 'VAT (7%)',
                'service_charge' => 'Service Charge (10%)',
                'total_amount' => 'Total Amount',
                'payment_method' => 'Payment Method',
                'payment_counter' => 'Please pay at the hotel counter',
                'payment_qr' => 'or scan the QR Code below to pay via PromptPay immediately',
                'scan_qr' => 'Scan QR Code to pay via PromptPay',
                'amount' => 'Amount',
                'note' => 'Note',
                'note_text' => 'Please bring your booking reference',
                'note_show' => 'to show at the counter when checking in',
                'important_info' => 'Important Information',
                'check_in_time' => 'Check-in time: 14:00 / Check-out time: 12:00',
                'arrival_time' => 'Please arrive before 18:00. If arriving late, please notify in advance',
                'bring_reference' => 'Please bring your booking reference when checking in',
                'cancel_info' => 'If you need to cancel or modify your booking, please contact the hotel in advance',
                'thank_you' => 'Thank you for choosing our service',
                'qr_code_not_available' => 'QR Code not available'
            ],
            'zh' => [
                'title' => '预订确认',
                'booking_success' => '预订成功',
                'booking_reference_label' => '预订编号',
                'booking_details' => '预订详情',
                'room_type' => '房型',
                'rooms' => '间',
                'check_in' => '入住日期',
                'check_out' => '退房日期',
                'nights' => '晚',
                'guests' => '住客',
                'adults' => '成人',
                'children' => '儿童',
                'guest_info' => '住客信息',
                'full_name' => '姓名',
                'email' => '邮箱',
                'phone' => '电话',
                'payment_summary' => '付款摘要',
                'room_price' => '房价',
                'breakfast_price' => '早餐价格',
                'tax' => '增值税 (7%)',
                'service_charge' => '服务费 (10%)',
                'total_amount' => '总金额',
                'payment_method' => '付款方式',
                'payment_counter' => '请在酒店前台付款',
                'payment_qr' => '或扫描下方二维码通过 PromptPay 立即付款',
                'scan_qr' => '扫描二维码通过 PromptPay 付款',
                'amount' => '金额',
                'note' => '备注',
                'note_text' => '请携带您的预订编号',
                'note_show' => '在办理入住时向前台出示',
                'important_info' => '重要信息',
                'check_in_time' => '入住时间：14:00 / 退房时间：12:00',
                'arrival_time' => '请在 18:00 前到达。如晚到，请提前通知',
                'bring_reference' => '办理入住时请携带您的预订编号',
                'cancel_info' => '如需取消或修改预订，请提前联系酒店',
                'thank_you' => '感谢您选择我们的服务',
                'qr_code_not_available' => '二维码不可用'
            ]
        ];
        
        return $texts[$lang][$key] ?? $texts['th'][$key] ?? $key;
    }
    
    /**
     * สร้างเนื้อหาอีเมล HTML
     */
    private static function generateEmailContent($booking, $qrCodeUrl, $lang = 'th', $hotelSettings = []) {
        $checkIn = date('d/m/Y', strtotime($booking['check_in_date']));
        $checkOut = date('d/m/Y', strtotime($booking['check_out_date']));
        $nights = (new DateTime($booking['check_in_date']))->diff(new DateTime($booking['check_out_date']))->days;
        
        // ดึงข้อความตามภาษา
        $t = function($key) use ($lang) {
            return self::getEmailText($lang, $key);
        };
        
        $hotelAddress = $hotelSettings['address'] ?? '';
        $hotelPhone = $hotelSettings['phone'] ?? ($booking['hotel_phone'] ?? '');

        $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($t('title')) . '</title>
    <style>
        body {
            font-family: "Sarabun", "Kanit", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
            font-size: 28px;
        }
        .success-badge {
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
            font-weight: bold;
        }
        .booking-reference {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .section h2 {
            color: #667eea;
            margin-top: 0;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
            text-align: right;
        }
        .total-amount {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }
        .payment-section {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-section h3 {
            color: #856404;
            margin-top: 0;
        }
        .qr-code {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .qr-code img {
            max-width: 350px;
            width: 100%;
            height: auto;
            border: 4px solid #667eea;
            border-radius: 15px;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
            display: block;
            margin: 0 auto 20px;
        }
        .qr-code p {
            margin-top: 15px;
            color: #856404;
            font-size: 16px;
            line-height: 1.6;
            font-weight: 600;
        }
        .important-note {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .important-note h3 {
            color: #2e7d32;
            margin-top: 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="text-align:center; margin-bottom: 10px;">
                <img src="' . htmlspecialchars(self::HOTEL_LOGO_URL) . '" alt="Diamond Plaza Surat" style="width:64px; height:auto; display:block; margin:0 auto 10px;" />
                <div style="font-size:22px; font-weight:700; color:#2c3e50; line-height:1.2;">Diamond Plaza Surat</div>
                <div style="font-size:20px; font-weight:700; color:#667eea; line-height:1.2;">Booking Confirmation</div>
            </div>
            <div class="success-badge">✓ ' . htmlspecialchars($t('booking_success')) . '</div>
        </div>
        
        <div class="booking-reference">
            ' . htmlspecialchars($t('booking_reference_label')) . ': ' . htmlspecialchars($booking['booking_reference']) . '
        </div>
        
        <div class="section">
            <h2>📋 ' . htmlspecialchars($t('booking_details')) . '</h2>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('room_type')) . ':</span>
                <span class="info-value">' . htmlspecialchars($booking['room_type_name']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('rooms')) . ':</span>
                <span class="info-value">' . $booking['rooms_booked'] . ' ' . htmlspecialchars($t('rooms')) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('check_in')) . ':</span>
                <span class="info-value">' . $checkIn . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('check_out')) . ':</span>
                <span class="info-value">' . $checkOut . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('nights')) . ':</span>
                <span class="info-value">' . $nights . ' ' . htmlspecialchars($t('nights')) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('guests')) . ':</span>
                <span class="info-value">' . $booking['adults'] . ' ' . htmlspecialchars($t('adults')) . ($booking['children'] > 0 ? ', ' . $booking['children'] . ' ' . htmlspecialchars($t('children')) : '') . '</span>
            </div>
        </div>
        
        <div class="section">
            <h2>👤 ' . htmlspecialchars($t('guest_info')) . '</h2>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('full_name')) . ':</span>
                <span class="info-value">' . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('email')) . ':</span>
                <span class="info-value">' . htmlspecialchars($booking['email']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('phone')) . ':</span>
                <span class="info-value">' . htmlspecialchars($booking['phone']) . '</span>
            </div>
        </div>
        
        <div class="section">
            <h2>💰 ' . htmlspecialchars($t('payment_summary')) . '</h2>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('room_price')) . ':</span>
                <span class="info-value">฿' . number_format($booking['room_price'], 0) . '</span>
            </div>';
        
        if ($booking['breakfast_price'] > 0) {
            $html .= '
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('breakfast_price')) . ':</span>
                <span class="info-value">฿' . number_format($booking['breakfast_price'], 0) . '</span>
            </div>';
        }
        
        $html .= '
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('tax')) . ':</span>
                <span class="info-value">฿' . number_format($booking['tax_amount'], 0) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">' . htmlspecialchars($t('service_charge')) . ':</span>
                <span class="info-value">฿' . number_format($booking['service_charge'], 0) . '</span>
            </div>
        </div>
        
        <div class="total-amount">
            ' . htmlspecialchars($t('total_amount')) . ': ฿' . number_format($booking['total_amount'], 0) . '
        </div>
        
        <div class="payment-section">
            <h3>💳 ' . htmlspecialchars($t('payment_method')) . '</h3>
            <p><strong>' . htmlspecialchars($t('payment_counter')) . '</strong></p>
            <p>' . htmlspecialchars($t('payment_qr')) . '</p>
            
            <div class="qr-code">';
        
        if (!empty($qrCodeUrl)) {
            // ใช้ URL แบบเต็มสำหรับ QR code image
            $html .= '<img src="' . htmlspecialchars($qrCodeUrl) . '" alt="QR Code PromptPay" style="max-width: 350px; width: 100%; height: auto; border: 4px solid #667eea; border-radius: 15px; padding: 15px; background: white; box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2); display: block; margin: 0 auto 20px;" />';
        } else {
            $html .= '<p style="color: #999; font-style: italic; padding: 20px;">' . htmlspecialchars($t('qr_code_not_available')) . '</p>';
        }
        
        $html .= '
                <p style="margin-top: 15px; font-weight: 600; color: #856404; font-size: 16px;">' . htmlspecialchars($t('scan_qr')) . '</p>
                <p style="font-size: 14px; color: #856404; margin-top: 8px;">' . htmlspecialchars($t('amount')) . ': <strong style="font-size: 18px; color: #667eea;">฿' . number_format($booking['total_amount'], 0) . '</strong></p>
            </div>
            
            <p style="margin-top: 15px; font-size: 14px; color: #666;">
                <strong>' . htmlspecialchars($t('note')) . ':</strong> ' . htmlspecialchars($t('note_text')) . ' <strong>' . htmlspecialchars($booking['booking_reference']) . '</strong> ' . htmlspecialchars($t('note_show')) . '
            </p>
        </div>
        
        <div class="important-note">
            <h3>📌 ' . htmlspecialchars($t('important_info')) . '</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>' . htmlspecialchars($t('check_in_time')) . '</li>
                <li>' . htmlspecialchars($t('arrival_time')) . '</li>
                <li>' . htmlspecialchars($t('bring_reference')) . '</li>
                <li>' . htmlspecialchars($t('cancel_info')) . '</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>' . htmlspecialchars($t('thank_you')) . '</p>
            <p>' . htmlspecialchars($hotelSettings['hotel_name'] ?? 'Diamond Plaza Surat') . '</p>
            ' . (!empty($hotelAddress) ? '<p>' . htmlspecialchars($hotelAddress) . '</p>' : '') . '
            ' . (!empty($hotelPhone) ? '<p>' . ($lang === 'en' ? 'Tel' : ($lang === 'zh' ? '电话' : 'โทร')) . ': ' . htmlspecialchars($hotelPhone) . '</p>' : '') . '
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
