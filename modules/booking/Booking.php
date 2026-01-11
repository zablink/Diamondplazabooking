<?php
/**
 * Booking Class
 * Handles hotel room bookings
 */

class Booking {
    private $db;
    
    public function __construct() {
        // ใช้ Singleton Pattern แทน new Database()
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new booking
     */
    public function createBooking($data) {
        try {
            // Validate data
            if (!$this->validateBookingData($data)) {
                return ['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'];
            }
            
            // Check availability
            $hotel = new Hotel();
            if (!$hotel->checkAvailability($data['room_type_id'], $data['check_in'], $data['check_out'], $data['num_rooms'])) {
                return ['success' => false, 'message' => 'ห้องพักไม่ว่าง กรุณาเลือกวันที่อื่น'];
            }
            
            // Calculate total price
            $nights = calculateNights($data['check_in'], $data['check_out']);
            $totalPrice = $data['room_price'] * $data['num_rooms'] * $nights;
            
            // Generate booking reference
            $bookingReference = generateBookingReference();
            
            // Insert booking
            $sql = "INSERT INTO bookings 
                    (user_id, hotel_id, room_type_id, booking_reference, check_in, check_out, 
                     num_rooms, num_adults, num_children, total_price, special_requests, status, payment_status)
                    VALUES 
                    (:user_id, :hotel_id, :room_type_id, :booking_reference, :check_in, :check_out,
                     :num_rooms, :num_adults, :num_children, :total_price, :special_requests, 'pending', 'pending')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'user_id' => $data['user_id'],
                'hotel_id' => $data['hotel_id'],
                'room_type_id' => $data['room_type_id'],
                'booking_reference' => $bookingReference,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'num_rooms' => $data['num_rooms'],
                'num_adults' => $data['num_adults'],
                'num_children' => $data['num_children'] ?? 0,
                'total_price' => $totalPrice,
                'special_requests' => $data['special_requests'] ?? ''
            ]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'จองห้องพักสำเร็จ',
                    'booking_reference' => $bookingReference,
                    'booking_id' => $this->db->lastInsertId()
                ];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate booking data
     */
    private function validateBookingData($data) {
        $required = ['user_id', 'hotel_id', 'room_type_id', 'check_in', 'check_out', 'num_rooms', 'num_adults', 'room_price'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get booking by ID
     */
    public function getBookingById($bookingId, $userId = null) {
        try {
            $sql = "SELECT b.*, h.hotel_name, h.address, h.city, h.phone as hotel_phone,
                    rt.room_type_name, rt.bed_type, rt.max_occupancy,
                    u.first_name, u.last_name, u.email, u.phone
                    FROM bookings b
                    JOIN hotels h ON b.hotel_id = h.hotel_id
                    JOIN room_types rt ON b.room_type_id = rt.room_type_id
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.booking_id = :booking_id";
            
            $params = ['booking_id' => $bookingId];
            
            if ($userId !== null) {
                $sql .= " AND b.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get booking by reference
     */
    public function getBookingByReference($reference) {
        try {
            $sql = "SELECT b.*, h.hotel_name, h.address, h.city, h.phone as hotel_phone,
                    rt.room_type_name, rt.bed_type, rt.max_occupancy, rt.images as room_images,
                    u.first_name, u.last_name, u.email, u.phone
                    FROM bookings b
                    JOIN hotels h ON b.hotel_id = h.hotel_id
                    JOIN room_types rt ON b.room_type_id = rt.room_type_id
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.booking_reference = :reference";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['reference' => $reference]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user bookings
     */
    public function getUserBookings($userId, $status = null) {
        try {
            $sql = "SELECT b.*, h.hotel_name, h.city, h.images as hotel_images,
                    rt.room_type_name
                    FROM bookings b
                    JOIN hotels h ON b.hotel_id = h.hotel_id
                    JOIN room_types rt ON b.room_type_id = rt.room_type_id
                    WHERE b.user_id = :user_id";
            
            $params = ['user_id' => $userId];
            
            if ($status !== null) {
                $sql .= " AND b.status = :status";
                $params['status'] = $status;
            }
            
            $sql .= " ORDER BY b.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Update booking status
     */
    public function updateBookingStatus($bookingId, $status) {
        try {
            $sql = "UPDATE bookings SET status = :status WHERE booking_id = :booking_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'status' => $status,
                'booking_id' => $bookingId
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'อัพเดทสถานะสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cancel booking
     */
    public function cancelBooking($bookingId, $userId) {
        try {
            // Check if booking belongs to user
            $booking = $this->getBookingById($bookingId, $userId);
            
            if (!$booking) {
                return ['success' => false, 'message' => 'ไม่พบการจอง'];
            }
            
            if ($booking['status'] === 'cancelled') {
                return ['success' => false, 'message' => 'การจองนี้ถูกยกเลิกแล้ว'];
            }
            
            if ($booking['status'] === 'completed') {
                return ['success' => false, 'message' => 'ไม่สามารถยกเลิกการจองที่เสร็จสิ้นแล้ว'];
            }
            
            // Update status
            $sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['booking_id' => $bookingId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'ยกเลิกการจองสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Confirm payment
     */
    public function confirmPayment($bookingId) {
        try {
            $sql = "UPDATE bookings 
                    SET payment_status = 'paid', status = 'confirmed' 
                    WHERE booking_id = :booking_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['booking_id' => $bookingId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'ชำระเงินสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Add review
     */
    public function addReview($bookingId, $userId, $rating, $title, $comment) {
        try {
            // Check if booking exists and belongs to user
            $booking = $this->getBookingById($bookingId, $userId);
            
            if (!$booking) {
                return ['success' => false, 'message' => 'ไม่พบการจอง'];
            }
            
            // Check if already reviewed
            $sql = "SELECT review_id FROM reviews WHERE booking_id = :booking_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['booking_id' => $bookingId]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'คุณได้รีวิวการจองนี้แล้ว'];
            }
            
            // Insert review
            $sql = "INSERT INTO reviews (booking_id, user_id, hotel_id, rating, title, comment)
                    VALUES (:booking_id, :user_id, :hotel_id, :rating, :title, :comment)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'hotel_id' => $booking['hotel_id'],
                'rating' => $rating,
                'title' => $title,
                'comment' => $comment
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'เพิ่มรีวิวสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}
