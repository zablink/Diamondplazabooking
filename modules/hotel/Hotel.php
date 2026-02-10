<?php
// modules/hotel/Hotel.php
// Hotel Management Class

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


require_once PROJECT_ROOT . '/includes/Database.php';

class Hotel {
    private $db;
    private $conn;
    
    public function __construct() {
        // ใช้ Singleton Pattern แทน new Database()
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * ดึงข้อมูลโรงแรมตาม ID
     */
    public function getHotelById($hotelId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM bk_hotels 
                WHERE hotel_id = ? AND status = 'active'
            ");
            $stmt->execute([$hotelId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting hotel: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ดึงรายการประเภทห้องพักทั้งหมดของโรงแรม
     */
    public function getRoomTypes($hotelId, $status = null) {
        echo "\n<!-- GETROOMTYPES -->\n";
        try {
            $sql = "SELECT * FROM bk_room_types WHERE hotel_id = ?";
            $params = [$hotelId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            //$sql .= " ORDER BY display_order ASC, room_type_id ASC";
            $sql .= " ORDER BY room_type_id ASC";
            
            $stmt = $this->conn->prepare($sql);
            //echo "\n<!-- ROOM__TYPE : " . $stmt->queryString() . " \n-->\n";
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            //echo "\n<!-- Error getting room types: " . $e->getMessage() . " -->\n";
            error_log("Error getting room types: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงข้อมูลประเภทห้องพักตาม ID
     */
    public function getRoomTypeById($roomTypeId) {
        try {
            // เพิ่ม field description_th, description_en, bed_type_th, bed_type_en ถ้ายังไม่มี
            try {
                $checkCol = $this->conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'description_th'");
                if ($checkCol->rowCount() == 0) {
                    $this->conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_th TEXT AFTER description");
                    $this->conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_en TEXT AFTER description_th");
                    $this->conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_th VARCHAR(100) AFTER bed_type");
                    $this->conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_en VARCHAR(100) AFTER bed_type_th");
                    error_log("Added description_th, description_en, bed_type_th, bed_type_en columns to bk_room_types");
                }
            } catch (Exception $e) {
                error_log("Error checking/adding columns: " . $e->getMessage());
            }
            
            $stmt = $this->conn->prepare("
                SELECT rt.*, h.hotel_name, h.hotel_id
                FROM bk_room_types rt
                LEFT JOIN bk_hotels h ON rt.hotel_id = h.hotel_id
                WHERE rt.room_type_id = ?
            ");


            $stmt->execute([$roomTypeId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting room type: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ดึงรีวิวของโรงแรม
     */
    public function getHotelReviews($hotelId, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.*, u.first_name, u.last_name, u.email
                FROM bk_reviews r
                LEFT JOIN bk_users u ON r.user_id = u.user_id
                WHERE r.hotel_id = ? AND r.status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$hotelId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting reviews: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงคะแนนรีวิวเฉลี่ยของโรงแรม
     */
    public function getAverageRating($hotelId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                FROM bk_reviews
                WHERE hotel_id = ? AND status = 'approved'
            ");
            $stmt->execute([$hotelId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting average rating: " . $e->getMessage());
            return ['avg_rating' => 0, 'total_reviews' => 0];
        }
    }
    
    /**
     * ดึงรูปภาพของห้องพัก
     */
    public function getRoomImages($roomTypeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM bk_room_images 
                WHERE room_type_id = ?
                ORDER BY is_featured DESC, display_order ASC
            ");
            $stmt->execute([$roomTypeId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting room images: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงรูปภาพ featured ของห้องพัก
     */
    public function getFeaturedImage($roomTypeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM bk_room_images 
                WHERE room_type_id = ? AND is_featured = 1
                LIMIT 1
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetch();
            
            // ถ้าไม่มีรูป featured ให้เอารูปแรก
            if (!$result) {
                $stmt = $this->conn->prepare("
                    SELECT * FROM bk_room_images 
                    WHERE room_type_id = ?
                    ORDER BY display_order ASC
                    LIMIT 1
                ");
                $stmt->execute([$roomTypeId]);
                $result = $stmt->fetch();
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error getting featured image: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * ค้นหาห้องพักว่าง
     */
    public function searchAvailableRooms($hotelId, $checkIn, $checkOut, $guests = 2) {
        try {
            $stmt = $this->conn->prepare("
                SELECT rt.*, 
                       rt.total_rooms - COALESCE(SUM(b.rooms_booked), 0) as available_rooms
                FROM bk_room_types rt
                LEFT JOIN bk_bookings b ON rt.room_type_id = b.room_type_id
                    AND b.status NOT IN ('cancelled', 'rejected')
                    AND (
                        (b.check_in_date <= ? AND b.check_out_date > ?)
                        OR (b.check_in_date < ? AND b.check_out_date >= ?)
                        OR (b.check_in_date >= ? AND b.check_out_date <= ?)
                    )
                WHERE rt.hotel_id = ?
                    AND rt.status = 'available'
                    AND rt.max_occupancy >= ?
                GROUP BY rt.room_type_id
                HAVING available_rooms > 0
                ORDER BY rt.display_order ASC, rt.base_price ASC
            ");
            
            $stmt->execute([
                $checkIn, $checkIn,
                $checkOut, $checkOut,
                $checkIn, $checkOut,
                $hotelId,
                $guests
            ]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching available rooms: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ตรวจสอบห้องว่างสำหรับประเภทห้องเฉพาะ
     */
    public function checkRoomAvailability($roomTypeId, $checkIn, $checkOut, $roomsNeeded = 1) {
        try {
            // ดึงจำนวนห้องทั้งหมด
            $stmt = $this->conn->prepare("
                SELECT total_rooms FROM bk_room_types 
                WHERE room_type_id = ?
            ");
            $stmt->execute([$roomTypeId]);
            $roomType = $stmt->fetch();
            
            if (!$roomType) {
                return ['available' => false, 'message' => 'ไม่พบข้อมูลห้อง'];
            }
            
            $totalRooms = intval($roomType['total_rooms']);
            
            // นับห้องที่ถูกจองในช่วงเวลานี้
            $stmt = $this->conn->prepare("
                SELECT SUM(rooms_booked) as booked
                FROM bk_bookings
                WHERE room_type_id = ?
                    AND status NOT IN ('cancelled', 'rejected')
                    AND (
                        (check_in_date <= ? AND check_out_date > ?)
                        OR (check_in_date < ? AND check_out_date >= ?)
                        OR (check_in_date >= ? AND check_out_date <= ?)
                    )
            ");
            
            $stmt->execute([
                $roomTypeId,
                $checkIn, $checkIn,
                $checkOut, $checkOut,
                $checkIn, $checkOut
            ]);
            
            $result = $stmt->fetch();
            $bookedRooms = intval($result['booked'] ?? 0);
            $availableRooms = $totalRooms - $bookedRooms;
            
            return [
                'available' => $availableRooms >= $roomsNeeded,
                'total_rooms' => $totalRooms,
                'booked_rooms' => $bookedRooms,
                'available_rooms' => $availableRooms,
                'rooms_needed' => $roomsNeeded
            ];
            
        } catch (PDOException $e) {
            error_log("Error checking availability: " . $e->getMessage());
            return [
                'available' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบห้องว่าง'
            ];
        }
    }
    
    /**
     * ดึง amenities ของโรงแรม
     */
    public function getHotelAmenities($hotelId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT amenities FROM bk_hotels WHERE hotel_id = ?
            ");
            $stmt->execute([$hotelId]);
            $result = $stmt->fetch();
            
            if ($result && $result['amenities']) {
                return json_decode($result['amenities'], true);
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("Error getting hotel amenities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึง amenities ของห้องพัก
     */
    public function getRoomAmenities($roomTypeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT amenities FROM bk_room_types WHERE room_type_id = ?
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetch();
            
            if ($result && $result['amenities']) {
                return json_decode($result['amenities'], true);
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("Error getting room amenities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ค้นหาโรงแรมตามเงื่อนไข
     */
    public function searchHotels($city = '', $checkIn = '', $checkOut = '', $guests = 2, $page = 1) {
        try {
            $itemsPerPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 12;
            $offset = ($page - 1) * $itemsPerPage;
            
            // Build base query
            $sql = "
                SELECT DISTINCT h.*,
                    (SELECT MIN(rt.base_price) 
                     FROM bk_room_types rt 
                     WHERE rt.hotel_id = h.hotel_id 
                     AND rt.status = 'available'
                     AND rt.max_occupancy >= ?) as min_price,
                    (SELECT AVG(r.rating) 
                     FROM bk_reviews r 
                     WHERE r.hotel_id = h.hotel_id 
                     AND r.status = 'approved') as avg_rating,
                    (SELECT COUNT(*) 
                     FROM bk_reviews r 
                     WHERE r.hotel_id = h.hotel_id 
                     AND r.status = 'approved') as review_count
                FROM bk_hotels h
                WHERE h.status = 'active'
            ";
            
            $params = [$guests];
            
            // Filter by city
            if (!empty($city)) {
                $sql .= " AND h.city LIKE ?";
                $params[] = '%' . $city . '%';
            }
            
            // Filter by availability if dates are provided
            if (!empty($checkIn) && !empty($checkOut)) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM bk_room_types rt
                    WHERE rt.hotel_id = h.hotel_id
                    AND rt.status = 'available'
                    AND rt.max_occupancy >= ?
                    AND rt.total_rooms > COALESCE((
                        SELECT SUM(b.rooms_booked)
                        FROM bk_bookings b
                        WHERE b.room_type_id = rt.room_type_id
                        AND b.status NOT IN ('cancelled', 'rejected')
                        AND (
                            (b.check_in_date <= ? AND b.check_out_date > ?)
                            OR (b.check_in_date < ? AND b.check_out_date >= ?)
                            OR (b.check_in_date >= ? AND b.check_out_date <= ?)
                        )
                    ), 0)
                )";
                $params[] = $guests;
                $params[] = $checkIn;
                $params[] = $checkIn;
                $params[] = $checkOut;
                $params[] = $checkOut;
                $params[] = $checkIn;
                $params[] = $checkOut;
            }
            
            $sql .= " ORDER BY h.star_rating DESC, min_price ASC";
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $itemsPerPage;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error searching hotels: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * นับจำนวนโรงแรมทั้งหมดที่ตรงกับเงื่อนไขการค้นหา
     */
    public function getTotalHotels($city = '', $checkIn = '', $checkOut = '', $guests = 2) {
        try {
            $sql = "
                SELECT COUNT(DISTINCT h.hotel_id) as total
                FROM bk_hotels h
                WHERE h.status = 'active'
            ";
            
            $params = [];
            
            // Filter by city
            if (!empty($city)) {
                $sql .= " AND h.city LIKE ?";
                $params[] = '%' . $city . '%';
            }
            
            // Filter by availability if dates are provided
            if (!empty($checkIn) && !empty($checkOut)) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM bk_room_types rt
                    WHERE rt.hotel_id = h.hotel_id
                    AND rt.status = 'available'
                    AND rt.max_occupancy >= ?
                    AND rt.total_rooms > COALESCE((
                        SELECT SUM(b.rooms_booked)
                        FROM bk_bookings b
                        WHERE b.room_type_id = rt.room_type_id
                        AND b.status NOT IN ('cancelled', 'rejected')
                        AND (
                            (b.check_in_date <= ? AND b.check_out_date > ?)
                            OR (b.check_in_date < ? AND b.check_out_date >= ?)
                            OR (b.check_in_date >= ? AND b.check_out_date <= ?)
                        )
                    ), 0)
                )";
                $params[] = $guests;
                $params[] = $checkIn;
                $params[] = $checkIn;
                $params[] = $checkOut;
                $params[] = $checkOut;
                $params[] = $checkIn;
                $params[] = $checkOut;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return intval($result['total'] ?? 0);
            
        } catch (PDOException $e) {
            error_log("Error getting total hotels: " . $e->getMessage());
            return 0;
        }
    }
}
