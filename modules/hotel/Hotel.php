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
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    // ... other methods ...

    public function getRoomTypes($hotelId, $status = null) {
        try {
            $today = date('Y-m-d');
            $sql = "SELECT rt.*, COALESCE(inv.available_rooms, rt.total_rooms) as current_availability
                    FROM bk_room_types rt
                    LEFT JOIN bk_room_inventory inv ON rt.room_type_id = inv.room_type_id AND inv.date = ?
                    WHERE rt.hotel_id = ?";
            
            $params = [$today, $hotelId];
            
            if ($status) {
                $sql .= " AND rt.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY rt.room_type_id ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting room types: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRoomTypeById($roomTypeId) {
        try {
            // ... (code to add columns if they don't exist)

            $today = date('Y-m-d');
            $stmt = $this->conn->prepare("\n                SELECT rt.*, h.hotel_name, h.hotel_id, COALESCE(inv.available_rooms, rt.total_rooms) as current_availability
                FROM bk_room_types rt
                LEFT JOIN bk_hotels h ON rt.hotel_id = h.hotel_id
                LEFT JOIN bk_room_inventory inv ON rt.room_type_id = inv.room_type_id AND inv.date = ?
                WHERE rt.room_type_id = ?\n            ");

            $stmt->execute([$today, $roomTypeId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting room type: " . $e->getMessage());
            return null;
        }
    }

    // ... other methods like getHotelReviews, getRoomImages, etc. ...
    
    /**
     * ดึง amenities ของห้องพัก
     */
    public function getRoomAmenities($roomTypeId) {
        try {
            $stmt = $this->conn->prepare("\n                SELECT amenities FROM bk_room_types WHERE room_type_id = ?\n            ");
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
     * ดึงข้อมูล amenities พร้อมคำแปลจาก Master List
     */
    public function getTranslatedAmenities($roomTypeId) {
        $amenityNames = $this->getRoomAmenities($roomTypeId);
        
        if (empty($amenityNames) || !is_array($amenityNames)) {
            return [];
        }
        
        try {
            // สร้าง placeholder สำหรับ IN clause
            $placeholders = implode(',', array_fill(0, count($amenityNames), '?'));
            
            $sql = "SELECT * FROM bk_amenities WHERE amenity_name IN ($placeholders) ORDER BY display_order ASC, amenity_name ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($amenityNames);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting translated amenities: " . $e->getMessage());
            return []; // คืนค่าเป็น array ว่างถ้าเกิด error
        }
    }

}
