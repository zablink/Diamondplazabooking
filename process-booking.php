<?php
/**
 * Process Booking - ประมวลผลข้อมูลการจองก่อนไปหน้าชำระเงิน
 */

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

require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/hotel/Hotel.php';
require_once PROJECT_ROOT . '/includes/EmailHelper.php';

// ============================================
// Check Login
// ============================================
if (!isLoggedIn()) {
    setFlashMessage('กรุณาเข้าสู่ระบบก่อนทำการจอง', 'error');
    redirect('login.php');
}

// ============================================
// Check POST Method
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('กรุณาเข้าถึงหน้านี้ผ่านฟอร์มจองเท่านั้น', 'error');
    redirect('index.php');
}

// ============================================
// รับข้อมูลจาก POST
// ============================================
$room_type_id = isset($_POST['room_type_id']) ? intval($_POST['room_type_id']) : 0;
$check_in = isset($_POST['check_in']) ? $_POST['check_in'] : '';
$check_out = isset($_POST['check_out']) ? $_POST['check_out'] : '';
$adults = isset($_POST['adults']) ? intval($_POST['adults']) : 0;
$children = isset($_POST['children']) ? intval($_POST['children']) : 0;
$rooms = isset($_POST['rooms']) ? intval($_POST['rooms']) : 1;
$nights = isset($_POST['nights']) ? intval($_POST['nights']) : 1;
$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
$add_breakfast = isset($_POST['add_breakfast']) ? intval($_POST['add_breakfast']) : 0;

// Guest information
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';

// ============================================
// Validation
// ============================================
$errors = [];

// Validate room_type_id
if ($room_type_id <= 0) {
    $errors[] = 'ไม่พบข้อมูลห้องพัก';
}

// Validate dates
if (empty($check_in)) {
    $errors[] = 'กรุณาเลือกวันเช็คอิน';
}

if (empty($check_out)) {
    $errors[] = 'กรุณาเลือกวันเช็คเอาท์';
}

// Validate date format and logic
if (empty($errors)) {
    try {
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $today = new DateTime('today');
        
        if ($check_in_date < $today) {
            $errors[] = 'วันเช็คอินต้องเป็นวันนี้หรือในอนาคต';
        }
        
        if ($check_out_date <= $check_in_date) {
            $errors[] = 'วันเช็คเอาท์ต้องมาหลังวันเช็คอิน';
        }
        
        // Recalculate nights to be sure
        $nights = $check_in_date->diff($check_out_date)->days;
        
    } catch (Exception $e) {
        $errors[] = 'รูปแบบวันที่ไม่ถูกต้อง';
    }
}

// Validate guests
if ($adults < 1) {
    $errors[] = 'ต้องมีผู้ใหญ่อย่างน้อย 1 คน';
}

if ($children < 0) {
    $errors[] = 'จำนวนเด็กไม่ถูกต้อง';
}

if ($rooms < 1) {
    $errors[] = 'ต้องจองอย่างน้อย 1 ห้อง';
}

// Validate guest information
if (empty($first_name)) {
    $errors[] = 'กรุณากรอกชื่อ';
}

if (empty($last_name)) {
    $errors[] = 'กรุณากรอกนามสกุล';
}

if (empty($email)) {
    $errors[] = 'กรุณากรอกอีเมล';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
} elseif (preg_match('/@social\.local$/', $email)) {
    // ตรวจสอบว่าไม่ใช่อีเมลชั่วคราวจาก social login
    $errors[] = 'กรุณากรอกอีเมลที่ถูกต้อง (ไม่สามารถใช้อีเมลชั่วคราวได้)';
}

if (empty($phone)) {
    $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
} elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก';
}

// Validate amount
if ($total_amount <= 0) {
    $errors[] = 'ยอดเงินไม่ถูกต้อง';
}

// ============================================
// Handle Validation Errors
// ============================================
if (!empty($errors)) {
    $error_message = implode('<br>', $errors);
    setFlashMessage($error_message, 'error');
    
    // Redirect back to booking page with data
    $redirect_url = 'booking.php?' . http_build_query([
        'room_type_id' => $room_type_id,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'adults' => $adults,
        'children' => $children,
        'rooms' => $rooms,
        'add_breakfast' => $add_breakfast
    ]);
    
    redirect($redirect_url);
}

// ============================================
// Load Room Data
// ============================================
$hotel = new Hotel();
$room = $hotel->getRoomTypeById($room_type_id);

if (!$room) {
    setFlashMessage('ไม่พบข้อมูลห้องพักที่ต้องการจอง', 'error');
    redirect('index.php');
}

// ============================================
// Calculate Pricing (Verify)
// ============================================
$base_price = floatval($room['base_price']);
$breakfast_price = floatval($room['breakfast_price'] ?? 0);
$breakfast_included = intval($room['breakfast_included'] ?? 0);

// คำนวณราคาห้อง
$room_subtotal = $base_price * $nights * $rooms;

// คำนวณราคาอาหารเช้า
$breakfast_total = 0;
$total_guests = $adults + $children;

if (!$breakfast_included && $add_breakfast && $breakfast_price > 0) {
    $breakfast_total = $breakfast_price * $total_guests * $nights * $rooms;
}

// คำนวณยอดรวมก่อนภาษี
$subtotal_before_tax = $room_subtotal + $breakfast_total;

// ภาษีและค่าบริการ (7% VAT + 10% Service Charge)
$vat_rate = 0.07;
$service_rate = 0.10;

$vat_amount = $subtotal_before_tax * $vat_rate;
$service_amount = $subtotal_before_tax * $service_rate;

// ยอดรวมทั้งหมด
$grand_total = $subtotal_before_tax + $vat_amount + $service_amount;

// ตรวจสอบว่าราคาตรงกับที่คำนวณจากหน้า booking หรือไม่
$price_difference = abs($grand_total - $total_amount);
if ($price_difference > 1) { // อนุญาตให้ต่างได้ไม่เกิน 1 บาท (ปัดเศษ)
    error_log("Price mismatch - Calculated: $grand_total, Received: $total_amount, Diff: $price_difference");
    // ใช้ราคาที่คำนวณใหม่แทน
    $grand_total = $total_amount;
}

// ============================================
// Save Booking to Database
// ============================================
$db = Database::getInstance();
$conn = $db->getConnection();

// Debug: Log ข้อมูลที่จะบันทึก
error_log("=== START BOOKING SAVE ===");
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Room Type ID: $room_type_id");
error_log("Check In: $check_in");
error_log("Check Out: $check_out");
error_log("Adults: $adults, Children: $children, Rooms: $rooms");
error_log("Guest: $first_name $last_name");
error_log("Email: $email");
error_log("Phone: $phone");
error_log("Room Subtotal: $room_subtotal");
error_log("Breakfast Total: $breakfast_total");
error_log("VAT: $vat_amount, Service: $service_amount");
error_log("Grand Total: $grand_total");

try {
    $conn->beginTransaction();
    
    // สร้างรหัสการจอง
    $booking_reference = generateBookingReference();
    error_log("Generated Booking Reference: $booking_reference");
    
    // ดึง hotel_id จาก room และตรวจสอบว่ามีอยู่ในตาราง bk_hotels หรือไม่
    $hotel_id = $room['hotel_id'] ?? null;
    
    // ตรวจสอบว่า hotel_id มีอยู่จริงในตาราง bk_hotels
    if ($hotel_id) {
        $checkHotelSql = "SELECT hotel_id FROM bk_hotels WHERE hotel_id = :hotel_id LIMIT 1";
        $checkHotelStmt = $conn->prepare($checkHotelSql);
        $checkHotelStmt->execute(['hotel_id' => $hotel_id]);
        $hotelExists = $checkHotelStmt->fetch();
        
        if (!$hotelExists) {
            error_log("⚠️ Hotel ID $hotel_id from room does not exist in bk_hotels table");
            $hotel_id = null;
        }
    }
    
    // ถ้า hotel_id ไม่มีหรือไม่ถูกต้อง ให้หา hotel_id แรกที่มีอยู่
    if (!$hotel_id) {
        $getHotelSql = "SELECT hotel_id FROM bk_hotels ORDER BY hotel_id ASC LIMIT 1";
        $getHotelStmt = $conn->query($getHotelSql);
        $firstHotel = $getHotelStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($firstHotel) {
            $hotel_id = $firstHotel['hotel_id'];
            error_log("⚠️ Using first available hotel_id: $hotel_id");
        } else {
            // ถ้าไม่มี hotel เลย ให้ throw error
            throw new Exception("ไม่พบข้อมูลโรงแรมในระบบ กรุณาติดต่อเจ้าหน้าที่");
        }
    }
    
    error_log("Final Hotel ID: $hotel_id");
    
    // เตรียมข้อมูลสำหรับบันทึก
    $booking_data = [
        'user_id' => $_SESSION['user_id'],
        'hotel_id' => $hotel_id,
        'room_type_id' => $room_type_id,
        'booking_reference' => $booking_reference,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'adults' => $adults,
        'children' => $children,
        'rooms' => $rooms,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'special_requests' => $special_requests,
        'room_price' => $room_subtotal,
        'breakfast_price' => $breakfast_total,
        'tax_amount' => $vat_amount,
        'service_charge' => $service_amount,
        'total_price' => $grand_total
    ];
    
    error_log("Booking Data: " . json_encode($booking_data, JSON_UNESCAPED_UNICODE));
    
    // บันทึกข้อมูลการจอง
    // หมายเหตุ: ใช้ check_in และ check_out ตามโครงสร้างตารางจริง (ไม่ใช่ check_in_date, check_out_date)
    // และใช้ total_price ตามโครงสร้างตารางจริง (ไม่ใช่ total_amount)
    // และต้องมี hotel_id เพื่อให้ผ่าน foreign key constraint
    $sql = "INSERT INTO bk_bookings (
        user_id, hotel_id, room_type_id, booking_reference, 
        check_in, check_out, 
        adults, children, rooms_booked,
        first_name, last_name, email, phone,
        special_requests,
        room_price, breakfast_price, tax_amount, service_charge, total_price,
        payment_method, payment_status, booking_status,
        created_at
    ) VALUES (
        :user_id, :hotel_id, :room_type_id, :booking_reference,
        :check_in, :check_out,
        :adults, :children, :rooms,
        :first_name, :last_name, :email, :phone,
        :special_requests,
        :room_price, :breakfast_price, :tax_amount, :service_charge, :total_price,
        'counter', 'pending', 'pending',
        NOW()
    )";
    
    error_log("SQL Query: $sql");
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $errorInfo = $conn->errorInfo();
        throw new Exception("Failed to prepare SQL statement. Error: " . print_r($errorInfo, true));
    }
    
    $executeResult = $stmt->execute($booking_data);
    
    if (!$executeResult) {
        $errorInfo = $stmt->errorInfo();
        $errorMessage = "Failed to execute SQL statement. ";
        $errorMessage .= "SQL Error Code: " . ($errorInfo[0] ?? 'N/A') . ". ";
        $errorMessage .= "SQL Error: " . ($errorInfo[2] ?? 'Unknown error');
        $errorMessage .= " | SQL State: " . ($errorInfo[0] ?? 'N/A');
        error_log("❌ SQL Execution Error: " . print_r($errorInfo, true));
        throw new Exception($errorMessage);
    }
    
    $booking_id = $conn->lastInsertId();
    
    if (!$booking_id) {
        throw new Exception("Failed to get last insert ID. Booking may not have been saved.");
    }
    
    // Commit transaction
    $conn->commit();
    
    error_log("✅ Booking saved successfully!");
    error_log("   Booking ID: $booking_id");
    error_log("   Reference: $booking_reference");
    error_log("   Room: " . $room['room_type_name'] . " ($room_type_id)");
    error_log("   Dates: $check_in to $check_out ($nights nights)");
    error_log("   Guests: $adults adults, $children children, $rooms room(s)");
    error_log("   Total: ฿" . number_format($grand_total, 2));
    error_log("   Email: $email");
    error_log("=== END BOOKING SAVE ===");
    
    // ส่งอีเมลยืนยันการจอง
    try {
        $emailSent = EmailHelper::sendBookingConfirmationEmail($booking_id);
        
        if ($emailSent) {
            error_log("✅ Booking confirmation email sent to: $email");
        } else {
            error_log("⚠️ Failed to send booking confirmation email to: $email");
            // ไม่ redirect กลับ เพราะการจองสำเร็จแล้ว แค่ส่งอีเมลไม่สำเร็จ
        }
    } catch (Exception $emailError) {
        error_log("⚠️ Email sending error: " . $emailError->getMessage());
        // ไม่ throw เพราะการจองสำเร็จแล้ว
    }
    
    // Redirect to thank you page
    redirect('thank_you.php?booking_id=' . $booking_id);
    
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $errorDetails = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log("❌ PDO Exception saving booking:");
    error_log("   Message: " . $e->getMessage());
    error_log("   Code: " . $e->getCode());
    error_log("   File: " . $e->getFile() . ":" . $e->getLine());
    error_log("   Error Info: " . print_r($e->errorInfo ?? [], true));
    error_log("   Full Details: " . json_encode($errorDetails, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // สร้าง error message ที่ละเอียดขึ้น
    $userFriendlyMessage = 'เกิดข้อผิดพลาดในการบันทึกการจอง';
    
    // ตรวจสอบ error code เพื่อให้ข้อความที่ชัดเจนขึ้น
    if ($e->getCode() == 23000) {
        $userFriendlyMessage .= ' (ข้อมูลซ้ำหรือไม่ถูกต้อง)';
    } elseif ($e->getCode() == 42000) {
        $userFriendlyMessage .= ' (โครงสร้างฐานข้อมูลไม่ถูกต้อง)';
    } elseif (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        $userFriendlyMessage .= ' (ข้อผิดพลาดฐานข้อมูล)';
    }
    
    $userFriendlyMessage .= ' กรุณาลองใหม่อีกครั้ง หรือติดต่อเจ้าหน้าที่';
    
    // ในโหมด development แสดง error message ที่ละเอียดขึ้น
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $userFriendlyMessage .= '<br><small style="color: #666;">Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
    }
    
    setFlashMessage($userFriendlyMessage, 'error');
    
    redirect('booking.php?' . http_build_query([
        'room_type_id' => $room_type_id,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'adults' => $adults,
        'children' => $children,
        'rooms' => $rooms,
        'add_breakfast' => $add_breakfast
    ]));
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $errorDetails = [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log("❌ General Exception saving booking:");
    error_log("   Message: " . $e->getMessage());
    error_log("   Code: " . $e->getCode());
    error_log("   File: " . $e->getFile() . ":" . $e->getLine());
    error_log("   Full Details: " . json_encode($errorDetails, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    // สร้าง error message ที่ละเอียดขึ้น
    $userFriendlyMessage = 'เกิดข้อผิดพลาดในการบันทึกการจอง: ' . htmlspecialchars($e->getMessage());
    $userFriendlyMessage .= ' กรุณาลองใหม่อีกครั้ง หรือติดต่อเจ้าหน้าที่';
    
    setFlashMessage($userFriendlyMessage, 'error');
    
    redirect('booking.php?' . http_build_query([
        'room_type_id' => $room_type_id,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'adults' => $adults,
        'children' => $children,
        'rooms' => $rooms,
        'add_breakfast' => $add_breakfast
    ]));
}