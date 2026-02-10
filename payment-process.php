<?php
/**
 * Payment Processing Backend
 * ประมวลผลการชำระเงินและบันทึกข้อมูล
 */

// Auto-find project root
$projectRoot = __DIR__;
while (!file_exists($projectRoot . '/includes/init.php')) {
    $parent = dirname($projectRoot);
    if ($parent === $projectRoot) {
        die(json_encode(['success' => false, 'message' => 'System error']));
    }
    $projectRoot = $parent;
}
require_once $projectRoot . '/includes/init.php';

require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/payment/OmisePayment.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    // ตรวจสอบว่า login แล้วหรือยัง
    if (!isLoggedIn()) {
        throw new Exception('กรุณาเข้าสู่ระบบก่อนทำการชำระเงิน');
    }
    
    // ตรวจสอบว่ามีข้อมูลการจองหรือไม่
    if (!isset($_SESSION['booking_data'])) {
        throw new Exception('ไม่พบข้อมูลการจอง');
    }
    
    // รับข้อมูลจาก request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['token']) || !isset($input['payment_method'])) {
        throw new Exception('ข้อมูลการชำระเงินไม่ครบถ้วน');
    }
    
    $token = $input['token'];
    $payment_method = $input['payment_method'];
    
    $booking = $_SESSION['booking_data'];
    $guest = $booking['guest_data'];
    $pricing = $booking['pricing'];
    
    // เริ่ม transaction
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $conn->beginTransaction();
    
    try {
        // สร้างรหัสการจอง
        $booking_reference = generateBookingReference();
        
        // บันทึกข้อมูลการจอง
        $sql = "INSERT INTO bk_bookings (
            user_id, room_type_id, booking_reference, 
            check_in_date, check_out_date, 
            adults, children, rooms_booked,
            first_name, last_name, email, phone,
            special_requests,
            room_price, breakfast_price, tax_amount, service_charge, total_amount,
            payment_method, payment_status, booking_status,
            created_at
        ) VALUES (
            :user_id, :room_type_id, :booking_reference,
            :check_in, :check_out,
            :adults, :children, :rooms,
            :first_name, :last_name, :email, :phone,
            :special_requests,
            :room_price, :breakfast_price, :tax_amount, :service_charge, :total_amount,
            :payment_method, 'pending', 'pending',
            NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'user_id' => getCurrentUserId(),
            'room_type_id' => $booking['room_type_id'],
            'booking_reference' => $booking_reference,
            'check_in' => $booking['check_in'],
            'check_out' => $booking['check_out'],
            'adults' => $booking['adults'],
            'children' => $booking['children'],
            'rooms' => $booking['rooms'],
            'first_name' => $guest['first_name'],
            'last_name' => $guest['last_name'],
            'email' => $guest['email'],
            'phone' => $guest['phone'],
            'special_requests' => $guest['special_requests'],
            'room_price' => $pricing['room_total'],
            'breakfast_price' => $pricing['breakfast_total'],
            'tax_amount' => $pricing['vat_amount'],
            'service_charge' => $pricing['service_amount'],
            'total_amount' => $pricing['grand_total'],
            'payment_method' => $payment_method
        ]);
        
        $booking_id = $conn->lastInsertId();
        
        // ประมวลผลการชำระเงินกับ Omise
        $omise = new OmisePayment();
        
        $amount_satang = intval($pricing['grand_total'] * 100);
        $description = "Booking #{$booking_reference} - " . $guest['first_name'] . ' ' . $guest['last_name'];
        
        $metadata = [
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'room_type_id' => $booking['room_type_id'],
            'check_in' => $booking['check_in'],
            'check_out' => $booking['check_out']
        ];
        
        $result = $omise->createCharge($amount_satang, 'THB', $token, $description, $metadata);
        
        if (!$result['success']) {
            throw new Exception($result['message'] ?? 'ไม่สามารถประมวลผลการชำระเงินได้');
        }
        
        $charge = $result['data'];
        $charge_id = $charge['id'];
        $charge_status = $charge['status'];
        
        // บันทึกข้อมูลการชำระเงิน
        $sql = "INSERT INTO bk_payments (
            booking_id, charge_id, amount, currency, status,
            payment_method, metadata, created_at
        ) VALUES (
            :booking_id, :charge_id, :amount, :currency, :status,
            :payment_method, :metadata, NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'booking_id' => $booking_id,
            'charge_id' => $charge_id,
            'amount' => $pricing['grand_total'],
            'currency' => 'THB',
            'status' => $charge_status,
            'payment_method' => $payment_method,
            'metadata' => json_encode($charge)
        ]);
        
        // อัพเดทสถานะการจอง
        $booking_status = 'pending';
        $payment_status = 'pending';
        
        if ($charge_status === 'successful') {
            $booking_status = 'confirmed';
            $payment_status = 'paid';
        } elseif ($charge_status === 'failed') {
            $booking_status = 'cancelled';
            $payment_status = 'failed';
        }
        
        $sql = "UPDATE bk_bookings 
                SET booking_status = :booking_status, 
                    payment_status = :payment_status,
                    updated_at = NOW()
                WHERE booking_id = :booking_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'booking_status' => $booking_status,
            'payment_status' => $payment_status,
            'booking_id' => $booking_id
        ]);
        
        // Commit transaction
        $conn->commit();
        
        // Clear booking data from session
        unset($_SESSION['booking_data']);
        
        // ส่งอีเมลยืนยัน (TODO: implement email sending)
        // sendBookingConfirmationEmail($booking_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'การจองสำเร็จ',
            'booking_id' => $booking_id,
            'booking_reference' => $booking_reference,
            'charge_status' => $charge_status
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
