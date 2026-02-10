<?php
/**
 * Admin Class - Complete Version with Admin Users & System Settings
 */

class Admin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // ============================================
    // DASHBOARD METHODS
    // ============================================
    
    public function getDashboardStats() {
        try {
            $conn = $this->db->getConnection();
            $stats = ['total_bookings' => 0, 'pending_bookings' => 0, 'total_revenue' => 0, 'active_rooms' => 0];
            
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM bk_bookings");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_bookings'] = $result['count'] ?? 0;
            } catch (Exception $e) {}
            
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM bk_bookings WHERE status = 'pending'");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['pending_bookings'] = $result['count'] ?? 0;
            } catch (Exception $e) {}
            
            try {
                $stmt = $conn->query("SELECT SUM(total_amount) as total FROM bk_bookings WHERE status IN ('confirmed', 'completed')");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_revenue'] = $result['total'] ?? 0;
            } catch (Exception $e) {}
            
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM bk_room_types WHERE status = 'available'");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['active_rooms'] = $result['count'] ?? 0;
            } catch (Exception $e) {}
            
            return $stats;
        } catch (Exception $e) {
            return ['total_bookings' => 0, 'pending_bookings' => 0, 'total_revenue' => 0, 'active_rooms' => 0];
        }
    }
    
    public function getRecentBookings($limit = 10) {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT b.booking_id, b.check_in, b.check_out, b.status, b.total_price as total_amount,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as guest_name,
                    rt.room_type_name as room_name
                FROM bk_bookings b
                LEFT JOIN bk_users u ON b.user_id = u.user_id
                LEFT JOIN bk_room_types rt ON b.room_type_id = rt.room_type_id
                ORDER BY b.created_at DESC LIMIT :limit
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ============================================
    // BOOKING METHODS
    // ============================================
    
    public function getAllBookings($filters = []) {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT b.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as guest_name,
                    u.email as guest_email, u.phone as guest_phone, 
                    rt.room_type_name as room_name, rt.room_type_id, rt.status as room_status
                FROM bk_bookings b
                LEFT JOIN bk_users u ON b.user_id = u.user_id
                LEFT JOIN bk_room_types rt ON b.room_type_id = rt.room_type_id
                WHERE 1=1
            ";
            
            $params = [];
            if (!empty($filters['status'])) {
                $sql .= " AND b.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['from_date'])) {
                $sql .= " AND b.check_in >= :from_date";
                $params[':from_date'] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $sql .= " AND b.check_out <= :to_date";
                $params[':to_date'] = $filters['to_date'];
            }
            
            $sql .= " ORDER BY b.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function updateBookingStatus($bookingId, $status) {
        try {
            $conn = $this->db->getConnection();
            $sql = "UPDATE bk_bookings SET status = :status WHERE booking_id = :booking_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':status' => $status, ':booking_id' => $bookingId]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function deleteBooking($bookingId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "DELETE FROM bk_bookings WHERE booking_id = :booking_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':booking_id' => $bookingId]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * ปิดห้องพักแบบด่วน (เปลี่ยนสถานะเป็น unavailable)
     */
    public function closeRoomQuickly($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "UPDATE bk_room_types SET status = 'unavailable' WHERE room_type_id = :room_type_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':room_type_id' => $roomTypeId]);
        } catch (Exception $e) {
            error_log("Error closing room quickly: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * เปิดห้องพัก (เปลี่ยนสถานะเป็น available)
     */
    public function openRoom($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "UPDATE bk_room_types SET status = 'available' WHERE room_type_id = :room_type_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':room_type_id' => $roomTypeId]);
        } catch (Exception $e) {
            error_log("Error opening room: " . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // ROOM TYPE METHODS
    // ============================================
    
    public function getAllRoomTypes() {
        try {
            $conn = $this->db->getConnection();
            $sql = "SELECT * FROM bk_room_types ORDER BY room_type_name ASC";
            $stmt = $conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getRoomTypes() {
        return $this->getAllRoomTypes();
    }
    
    public function getRoomTypeById($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "SELECT * FROM bk_room_types WHERE room_type_id = :room_type_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':room_type_id' => $roomTypeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createRoomType($data) {
        try {
            $conn = $this->db->getConnection();
            $amenitiesJson = '';
            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $amenitiesJson = json_encode($data['amenities']);
            }
            
            // ตรวจสอบและเพิ่ม columns สำหรับหลายภาษา ถ้ายังไม่มี
            try {
                $checkCol = $conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'description_th'");
                if ($checkCol->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_th TEXT AFTER description");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_en TEXT AFTER description_th");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_zh TEXT AFTER description_en");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_th VARCHAR(100) AFTER bed_type");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_en VARCHAR(100) AFTER bed_type_th");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_zh VARCHAR(100) AFTER bed_type_en");
                    error_log("Added multi-language columns to bk_room_types");
                }
                // ตรวจสอบว่ามี description_zh และ bed_type_zh หรือยัง
                $checkZhDesc = $conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'description_zh'");
                if ($checkZhDesc->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_zh TEXT AFTER description_en");
                }
                $checkZhBed = $conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'bed_type_zh'");
                if ($checkZhBed->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_zh VARCHAR(100) AFTER bed_type_en");
                }
            } catch (Exception $e) {
                error_log("Error checking/adding columns in createRoomType: " . $e->getMessage());
            }
            
            $sql = "
                INSERT INTO bk_room_types (hotel_id, room_type_name, description, description_th, description_en, description_zh, 
                    base_price, max_occupancy, total_rooms, size_sqm, bed_type, bed_type_th, bed_type_en, bed_type_zh,
                    amenities, breakfast_included, breakfast_price, status, created_at)
                VALUES (:hotel_id, :name, :description, :description_th, :description_en, :description_zh, 
                    :price, :max_occupancy, :total_rooms, :size_sqm, :bed_type, :bed_type_th, :bed_type_en, :bed_type_zh,
                    :amenities, :breakfast_included, :breakfast_price, :status, NOW())
            ";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':hotel_id' => $data['hotel_id'] ?? 1, // Default to hotel_id = 1 if not provided
                ':name' => $data['name'] ?? '',
                ':description' => $data['description'] ?? '',
                ':description_th' => $data['description_th'] ?? '',
                ':description_en' => $data['description_en'] ?? '',
                ':description_zh' => $data['description_zh'] ?? '',
                ':price' => $data['price'] ?? 0,
                ':max_occupancy' => $data['occupancy'] ?? $data['max_occupancy'] ?? 2,
                ':total_rooms' => $data['total_rooms'] ?? 0,
                ':size_sqm' => $data['size_sqm'] ?? null,
                ':bed_type' => $data['bed_type'] ?? null,
                ':bed_type_th' => $data['bed_type_th'] ?? '',
                ':bed_type_en' => $data['bed_type_en'] ?? '',
                ':bed_type_zh' => $data['bed_type_zh'] ?? '',
                ':amenities' => $amenitiesJson,
                ':breakfast_included' => $data['breakfast_included'] ?? 0,
                ':breakfast_price' => $data['breakfast_price'] ?? 0,
                ':status' => $data['status'] ?? 'available'
            ]);
        } catch (Exception $e) {
            error_log("Error creating room: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateRoomType($roomTypeId, $data) {
        try {
            $conn = $this->db->getConnection();
            $amenitiesJson = '';
            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $amenitiesJson = json_encode($data['amenities']);
            }
            
            // เพิ่ม field description_th, description_en, description_zh, bed_type_th, bed_type_en, bed_type_zh ถ้ายังไม่มี
            try {
                $checkCol = $conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'description_th'");
                if ($checkCol->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_th TEXT AFTER description");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_en TEXT AFTER description_th");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_zh TEXT AFTER description_en");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_th VARCHAR(100) AFTER bed_type");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_en VARCHAR(100) AFTER bed_type_th");
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_zh VARCHAR(100) AFTER bed_type_en");
                    error_log("Added description_th, description_en, description_zh, bed_type_th, bed_type_en, bed_type_zh columns to bk_room_types");
                }
                // ตรวจสอบว่ามี description_zh และ bed_type_zh หรือยัง
                $checkZhDesc = $conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'description_zh'");
                if ($checkZhDesc->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN description_zh TEXT AFTER description_en");
                    error_log("Added description_zh column to bk_room_types");
                }
                $checkZhBed = $conn->query("SHOW COLUMNS FROM bk_room_types LIKE 'bed_type_zh'");
                if ($checkZhBed->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_room_types ADD COLUMN bed_type_zh VARCHAR(100) AFTER bed_type_en");
                    error_log("Added bed_type_zh column to bk_room_types");
                }
            } catch (Exception $e) {
                error_log("Error checking/adding columns: " . $e->getMessage());
            }
            
            $sql = "
                UPDATE bk_room_types SET 
                    room_type_name = :name, 
                    description_th = :description_th,
                    description_en = :description_en,
                    description_zh = :description_zh,
                    base_price = :price,
                    max_occupancy = :max_occupancy, 
                    total_rooms = :total_rooms,
                    size_sqm = :size_sqm,
                    bed_type_th = :bed_type_th,
                    bed_type_en = :bed_type_en,
                    bed_type_zh = :bed_type_zh,
                    amenities = :amenities,
                    breakfast_included = :breakfast_included,
                    breakfast_price = :breakfast_price,
                    status = :status
                WHERE room_type_id = :room_type_id
            ";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                ':name' => $data['name'] ?? '',
                ':description_th' => $data['description_th'] ?? '',
                ':description_en' => $data['description_en'] ?? '',
                ':description_zh' => $data['description_zh'] ?? '',
                ':price' => $data['price'] ?? 0,
                ':max_occupancy' => $data['occupancy'] ?? $data['max_occupancy'] ?? 2,
                ':total_rooms' => $data['total_rooms'] ?? 0,
                ':size_sqm' => $data['size_sqm'] ?? null,
                ':bed_type_th' => $data['bed_type_th'] ?? '',
                ':bed_type_en' => $data['bed_type_en'] ?? '',
                ':bed_type_zh' => $data['bed_type_zh'] ?? '',
                ':amenities' => $amenitiesJson,
                ':breakfast_included' => $data['breakfast_included'] ?? 0,
                ':breakfast_price' => $data['breakfast_price'] ?? 0,
                ':status' => $data['status'] ?? 'available',
                ':room_type_id' => $roomTypeId
            ]);
            
            if (!$result) {
                error_log("Error updating room type: " . print_r($stmt->errorInfo(), true));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Exception updating room type: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteRoomType($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $checkSql = "SELECT COUNT(*) as count FROM bk_bookings WHERE room_type_id = :room_type_id";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':room_type_id' => $roomTypeId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return false;
            }
            
            $sql = "DELETE FROM bk_room_types WHERE room_type_id = :room_type_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':room_type_id' => $roomTypeId]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ============================================
    // CUSTOMER METHODS
    // ============================================
    
    public function getAllCustomers() {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT u.*, COUNT(DISTINCT b.booking_id) as total_bookings,
                    COALESCE(SUM(b.total_amount), 0) as total_spent
                FROM bk_users u
                LEFT JOIN bk_bookings b ON u.user_id = b.user_id
                WHERE u.role = 'customer'
                GROUP BY u.user_id
                ORDER BY u.created_at DESC
            ";
            $stmt = $conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getAllUsers($role = null) {
        try {
            $conn = $this->db->getConnection();
            $sql = "SELECT user_id, first_name, last_name, email, phone, role, created_at FROM bk_users";
            
            if ($role) {
                $sql .= " WHERE role = :role";
            }
            
            $sql .= " ORDER BY created_at DESC";
            $stmt = $conn->prepare($sql);
            
            if ($role) {
                $stmt->execute([':role' => $role]);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ============================================
    // ADMIN USER METHODS
    // ============================================
    
    public function getAdminUsers() {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT user_id, email, first_name, last_name, phone, created_at, updated_at
                FROM bk_users
                WHERE role = 'admin'
                ORDER BY created_at DESC
            ";
            $stmt = $conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting admin users: " . $e->getMessage());
            return [];
        }
    }
    
    public function createAdminUser($data) {
        try {
            $conn = $this->db->getConnection();
            
            // Check if email exists
            $checkSql = "SELECT COUNT(*) as count FROM bk_users WHERE email = :email";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':email' => $data['email']]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                error_log("Email already exists: " . $data['email']);
                return false;
            }
            
            $sql = "
                INSERT INTO bk_users (email, password, first_name, last_name, phone, role, auth_provider, created_at)
                VALUES (:email, :password, :first_name, :last_name, :phone, 'admin', 'local', NOW())
            ";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':phone' => $data['phone'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error creating admin user: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateAdminUser($userId, $data) {
        try {
            $conn = $this->db->getConnection();
            
            if (isset($data['password']) && !empty($data['password'])) {
                $sql = "
                    UPDATE bk_users SET
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        password = :password,
                        updated_at = NOW()
                    WHERE user_id = :user_id AND role = 'admin'
                ";
                
                $params = [
                    ':first_name' => $data['first_name'],
                    ':last_name' => $data['last_name'],
                    ':email' => $data['email'],
                    ':phone' => $data['phone'] ?? '',
                    ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                    ':user_id' => $userId
                ];
            } else {
                $sql = "
                    UPDATE bk_users SET
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        updated_at = NOW()
                    WHERE user_id = :user_id AND role = 'admin'
                ";
                
                $params = [
                    ':first_name' => $data['first_name'],
                    ':last_name' => $data['last_name'],
                    ':email' => $data['email'],
                    ':phone' => $data['phone'] ?? '',
                    ':user_id' => $userId
                ];
            }
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updating admin user: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteAdminUser($userId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "DELETE FROM bk_users WHERE user_id = :user_id AND role = 'admin'";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':user_id' => $userId]);
        } catch (Exception $e) {
            error_log("Error deleting admin user: " . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // REPORT METHODS
    // ============================================
    
    public function getRevenueReport($startDate, $endDate) {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT DATE(b.check_in) as date, COUNT(*) as bookings_count, SUM(b.total_amount) as revenue
                FROM bk_bookings b
                WHERE b.check_in BETWEEN :start_date AND :end_date
                AND b.status IN ('confirmed', 'completed')
                GROUP BY DATE(b.check_in)
                ORDER BY date ASC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getRoomTypeStats() {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT rt.room_type_name, COUNT(b.booking_id) as booking_count, SUM(b.total_price) as total_revenue
                FROM bk_room_types rt
                LEFT JOIN bk_bookings b ON rt.room_type_id = b.room_type_id AND b.status IN ('confirmed', 'completed')
                GROUP BY rt.room_type_id, rt.room_type_name
                ORDER BY total_revenue DESC
            ";
            
            $stmt = $conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ============================================
    // HOTEL SETTINGS METHODS
    // ============================================
    
    public function getHotelSettings($lang = null) {
        try {
            $conn = $this->db->getConnection();
            
            // ตรวจสอบว่าตารางมีอยู่หรือไม่
            try {
                $checkTable = $conn->query("SHOW TABLES LIKE 'bk_hotel_settings'");
                if ($checkTable->rowCount() == 0) {
                    // ถ้าไม่มีตาราง ให้ return default values
                    return [
                        'hotel_name' => SITE_NAME ?? 'Hotel',
                        'description' => '',
                        'address' => '',
                        'city' => '',
                        'phone' => '',
                        'email' => ''
                    ];
                }
            } catch (Exception $e) {
                error_log("Error checking table: " . $e->getMessage());
            }
            
            $sql = "SELECT * FROM bk_hotel_settings WHERE hotel_id = 1 LIMIT 1";
            $stmt = $conn->query($sql);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                return [
                    'hotel_name' => SITE_NAME ?? 'Hotel',
                    'description' => '',
                    'address' => '',
                    'city' => '',
                    'phone' => '',
                    'email' => ''
                ];
            }
            
            // ถ้ามีการระบุภาษา ให้ดึงข้อมูลตามภาษานั้น
            if ($lang) {
                $langKey = '_' . $lang;
                if (isset($settings['hotel_name' . $langKey]) && !empty($settings['hotel_name' . $langKey])) {
                    $settings['hotel_name'] = $settings['hotel_name' . $langKey];
                }
                if (isset($settings['description' . $langKey]) && !empty($settings['description' . $langKey])) {
                    $settings['description'] = $settings['description' . $langKey];
                }
                if (isset($settings['address' . $langKey]) && !empty($settings['address' . $langKey])) {
                    $settings['address'] = $settings['address' . $langKey];
                }
                if (isset($settings['city' . $langKey]) && !empty($settings['city' . $langKey])) {
                    $settings['city'] = $settings['city' . $langKey];
                }
                if (isset($settings['about_description' . $langKey]) && !empty($settings['about_description' . $langKey])) {
                    $settings['about_description'] = $settings['about_description' . $langKey];
                }
            } else {
                // ถ้าไม่ระบุภาษา ให้ใช้ภาษา default
                $currentLang = $_SESSION['lang'] ?? 'th';
                $langKey = '_' . $currentLang;
                if (isset($settings['hotel_name' . $langKey]) && !empty($settings['hotel_name' . $langKey])) {
                    $settings['hotel_name'] = $settings['hotel_name' . $langKey];
                } else if (!empty($settings['hotel_name'])) {
                    // ถ้าไม่มีข้อมูลภาษานั้น ให้ใช้ค่า default
                }
                if (isset($settings['description' . $langKey]) && !empty($settings['description' . $langKey])) {
                    $settings['description'] = $settings['description' . $langKey];
                }
                if (isset($settings['address' . $langKey]) && !empty($settings['address' . $langKey])) {
                    $settings['address'] = $settings['address' . $langKey];
                }
                if (isset($settings['city' . $langKey]) && !empty($settings['city' . $langKey])) {
                    $settings['city'] = $settings['city' . $langKey];
                }
                if (isset($settings['about_description' . $langKey]) && !empty($settings['about_description' . $langKey])) {
                    $settings['about_description'] = $settings['about_description' . $langKey];
                }
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log("Error getting hotel settings: " . $e->getMessage());
            return [
                'hotel_name' => SITE_NAME ?? 'Hotel',
                'description' => '',
                'address' => '',
                'city' => '',
                'phone' => '',
                'email' => ''
            ];
        }
    }
    
    public function updateHotelSettings($data) {
        try {
            $conn = $this->db->getConnection();
            
            // ตรวจสอบว่าตารางมีอยู่หรือไม่ ถ้าไม่มีให้สร้าง
            try {
                $checkTable = $conn->query("SHOW TABLES LIKE 'bk_hotel_settings'");
                if ($checkTable->rowCount() == 0) {
                    // สร้างตารางถ้ายังไม่มี
                    $createTableSql = "
                        CREATE TABLE IF NOT EXISTS bk_hotel_settings (
                            hotel_id INT PRIMARY KEY DEFAULT 1,
                            hotel_name VARCHAR(255) NOT NULL,
                            hotel_name_th VARCHAR(255),
                            hotel_name_en VARCHAR(255),
                            hotel_name_zh VARCHAR(255),
                            description TEXT,
                            description_th TEXT,
                            description_en TEXT,
                            description_zh TEXT,
                            address TEXT,
                            city VARCHAR(100),
                            phone VARCHAR(20),
                            email VARCHAR(255),
                            amenities TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                    ";
                    $conn->exec($createTableSql);
                    error_log("Created bk_hotel_settings table");
                }
                
                // เพิ่ม multilingual columns ถ้ายังไม่มี
                $languages = ['th', 'en', 'zh'];
                foreach ($languages as $lang) {
                    // ตรวจสอบและเพิ่ม hotel_name columns
                    $checkCol = $conn->query("SHOW COLUMNS FROM bk_hotel_settings LIKE 'hotel_name_" . $lang . "'");
                    if ($checkCol->rowCount() == 0) {
                        $conn->exec("ALTER TABLE bk_hotel_settings ADD COLUMN hotel_name_" . $lang . " VARCHAR(255) AFTER hotel_name");
                    }
                    
                    // ตรวจสอบและเพิ่ม description columns
                    $checkCol = $conn->query("SHOW COLUMNS FROM bk_hotel_settings LIKE 'description_" . $lang . "'");
                    if ($checkCol->rowCount() == 0) {
                        $conn->exec("ALTER TABLE bk_hotel_settings ADD COLUMN description_" . $lang . " TEXT AFTER description");
                    }
                    
                    // ตรวจสอบและเพิ่ม about_description columns
                    $checkCol = $conn->query("SHOW COLUMNS FROM bk_hotel_settings LIKE 'about_description_" . $lang . "'");
                    if ($checkCol->rowCount() == 0) {
                        $conn->exec("ALTER TABLE bk_hotel_settings ADD COLUMN about_description_" . $lang . " TEXT AFTER description_zh");
                    }
                    
                    // ตรวจสอบและเพิ่ม address columns
                    $checkCol = $conn->query("SHOW COLUMNS FROM bk_hotel_settings LIKE 'address_" . $lang . "'");
                    if ($checkCol->rowCount() == 0) {
                        $conn->exec("ALTER TABLE bk_hotel_settings ADD COLUMN address_" . $lang . " TEXT AFTER address");
                    }
                    
                    // ตรวจสอบและเพิ่ม city columns
                    $checkCol = $conn->query("SHOW COLUMNS FROM bk_hotel_settings LIKE 'city_" . $lang . "'");
                    if ($checkCol->rowCount() == 0) {
                        $conn->exec("ALTER TABLE bk_hotel_settings ADD COLUMN city_" . $lang . " VARCHAR(100) AFTER city");
                    }
                }
                
                // ตรวจสอบและเพิ่ม about_description column (default)
                $checkCol = $conn->query("SHOW COLUMNS FROM bk_hotel_settings LIKE 'about_description'");
                if ($checkCol->rowCount() == 0) {
                    $conn->exec("ALTER TABLE bk_hotel_settings ADD COLUMN about_description TEXT AFTER description");
                }
            } catch (Exception $e) {
                error_log("Error checking/creating table: " . $e->getMessage());
            }
            
            $checkSql = "SELECT COUNT(*) as count FROM bk_hotel_settings WHERE hotel_id = 1";
            $checkStmt = $conn->query($checkSql);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // สร้าง SQL สำหรับ multilingual fields
            $updateFields = [
                'hotel_name = :name',
                'address = :address',
                'city = :city',
                'phone = :phone',
                'email = :email',
                'updated_at = NOW()'
            ];
            
            $params = [
                ':name' => $data['name'] ?? $data['name_th'] ?? '',
                ':address' => $data['address'] ?? $data['address_th'] ?? '',
                ':city' => $data['city'] ?? $data['city_th'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':email' => $data['email'] ?? ''
            ];
            
            // เพิ่ม multilingual fields
            $languages = ['th', 'en', 'zh'];
            foreach ($languages as $lang) {
                $nameKey = 'name_' . $lang;
                $descKey = 'description_' . $lang;
                $aboutDescKey = 'about_description_' . $lang;
                $addrKey = 'address_' . $lang;
                $cityKey = 'city_' . $lang;
                
                if (isset($data[$nameKey])) {
                    $updateFields[] = "hotel_name_" . $lang . " = :name_" . $lang;
                    $params[':name_' . $lang] = $data[$nameKey];
                }
                
                if (isset($data[$descKey])) {
                    $updateFields[] = "description_" . $lang . " = :description_" . $lang;
                    $params[':description_' . $lang] = $data[$descKey];
                }
                
                if (isset($data[$aboutDescKey])) {
                    $updateFields[] = "about_description_" . $lang . " = :about_description_" . $lang;
                    $params[':about_description_' . $lang] = $data[$aboutDescKey];
                }
                
                if (isset($data[$addrKey])) {
                    $updateFields[] = "address_" . $lang . " = :address_" . $lang;
                    $params[':address_' . $lang] = $data[$addrKey];
                }
                
                if (isset($data[$cityKey])) {
                    $updateFields[] = "city_" . $lang . " = :city_" . $lang;
                    $params[':city_' . $lang] = $data[$cityKey];
                }
            }
            
            // เก็บ description เดิมไว้ถ้ามี
            if (isset($data['description'])) {
                $updateFields[] = "description = :description";
                $params[':description'] = $data['description'];
            }
            
            // เก็บ about_description เดิมไว้ถ้ามี
            if (isset($data['about_description'])) {
                $updateFields[] = "about_description = :about_description";
                $params[':about_description'] = $data['about_description'];
            }
            
            if ($result && $result['count'] > 0) {
                $sql = "UPDATE bk_hotel_settings SET " . implode(', ', $updateFields) . " WHERE hotel_id = 1";
            } else {
                // สำหรับ INSERT
                $fields = ['hotel_id', 'hotel_name', 'address', 'city', 'phone', 'email', 'created_at'];
                $values = [1, ':name', ':address', ':city', ':phone', ':email', 'NOW()'];
                
                foreach ($languages as $lang) {
                    if (isset($data['name_' . $lang])) {
                        $fields[] = "hotel_name_" . $lang;
                        $values[] = ":name_" . $lang;
                    }
                    if (isset($data['description_' . $lang])) {
                        $fields[] = "description_" . $lang;
                        $values[] = ":description_" . $lang;
                    }
                    if (isset($data['about_description_' . $lang])) {
                        $fields[] = "about_description_" . $lang;
                        $values[] = ":about_description_" . $lang;
                    }
                    if (isset($data['address_' . $lang])) {
                        $fields[] = "address_" . $lang;
                        $values[] = ":address_" . $lang;
                    }
                    if (isset($data['city_' . $lang])) {
                        $fields[] = "city_" . $lang;
                        $values[] = ":city_" . $lang;
                    }
                }
                
                if (isset($data['description'])) {
                    $fields[] = "description";
                    $values[] = ":description";
                }
                
                if (isset($data['about_description'])) {
                    $fields[] = "about_description";
                    $values[] = ":about_description";
                }
                
                $sql = "INSERT INTO bk_hotel_settings (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            }
            
            $stmt = $conn->prepare($sql);
            error_log("Updating hotel settings: " . print_r($params, true));
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("Error updating hotel settings: " . print_r($stmt->errorInfo(), true));
            } else {
                error_log("Hotel settings updated successfully");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Exception in updateHotelSettings: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    // ============================================
    // SYSTEM SETTINGS METHODS
    // ============================================
    
    public function getSystemSettings($lang = null) {
        try {
            $conn = $this->db->getConnection();
            $sql = "SELECT * FROM bk_system_settings WHERE id = 1 LIMIT 1";
            $stmt = $conn->query($sql);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                return [
                    'site_name' => SITE_NAME ?? 'Hotel Booking',
                    'site_url' => SITE_URL ?? '',
                    'timezone' => 'Asia/Bangkok',
                    'date_format' => 'd/m/Y',
                    'time_format' => 'H:i',
                    'currency' => 'THB',
                    'currency_symbol' => '฿',
                    'default_language' => 'th',
                    'items_per_page' => 12,
                    'enable_registration' => 1,
                    'enable_social_login' => 1,
                    'require_email_verification' => 0,
                    'maintenance_mode' => 0
                ];
            }
            
            // ถ้ามีการระบุภาษา ให้ดึงข้อมูลตามภาษานั้น
            if ($lang) {
                $langKey = '_' . $lang;
                if (isset($settings['site_name' . $langKey]) && !empty($settings['site_name' . $langKey])) {
                    $settings['site_name'] = $settings['site_name' . $langKey];
                }
            } else {
                // ถ้าไม่ระบุภาษา ให้ใช้ภาษา default
                $currentLang = $_SESSION['lang'] ?? 'th';
                $langKey = '_' . $currentLang;
                if (isset($settings['site_name' . $langKey]) && !empty($settings['site_name' . $langKey])) {
                    $settings['site_name'] = $settings['site_name' . $langKey];
                }
            }
            
            return $settings;
        } catch (Exception $e) {
            return [
                'site_name' => SITE_NAME ?? 'Hotel Booking',
                'site_url' => SITE_URL ?? '',
                'timezone' => 'Asia/Bangkok',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'currency' => 'THB',
                'currency_symbol' => '฿',
                'default_language' => 'th',
                'items_per_page' => 12,
                'enable_registration' => 1,
                'enable_social_login' => 1,
                'require_email_verification' => 0,
                'maintenance_mode' => 0
            ];
        }
    }
    
    public function updateSystemSettings($data) {
        try {
            $conn = $this->db->getConnection();
            
            // เพิ่ม multilingual columns ถ้ายังไม่มี
            try {
                $languages = ['th', 'en', 'zh'];
                foreach ($languages as $lang) {
                    $checkCol = $conn->query("SHOW COLUMNS FROM bk_system_settings LIKE 'site_name_" . $lang . "'");
                    if ($checkCol->rowCount() == 0) {
                        $conn->exec("ALTER TABLE bk_system_settings ADD COLUMN site_name_" . $lang . " VARCHAR(255) AFTER site_name");
                    }
                }
            } catch (Exception $e) {
                error_log("Error adding multilingual columns: " . $e->getMessage());
            }
            
            $checkSql = "SELECT COUNT(*) as count FROM bk_system_settings WHERE id = 1";
            $checkStmt = $conn->query($checkSql);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // สร้าง SQL สำหรับ multilingual fields
            $updateFields = [
                'site_name = :site_name',
                'site_url = :site_url',
                'timezone = :timezone',
                'date_format = :date_format',
                'time_format = :time_format',
                'currency = :currency',
                'currency_symbol = :currency_symbol',
                'default_language = :default_language',
                'items_per_page = :items_per_page',
                'enable_registration = :enable_registration',
                'enable_social_login = :enable_social_login',
                'require_email_verification = :require_email_verification',
                'maintenance_mode = :maintenance_mode',
                'updated_at = NOW()'
            ];
            
            $params = [
                ':site_name' => $data['site_name'] ?? $data['site_name_th'] ?? '',
                ':site_url' => $data['site_url'],
                ':timezone' => $data['timezone'],
                ':date_format' => $data['date_format'],
                ':time_format' => $data['time_format'],
                ':currency' => $data['currency'],
                ':currency_symbol' => $data['currency_symbol'],
                ':default_language' => $data['default_language'],
                ':items_per_page' => $data['items_per_page'],
                ':enable_registration' => $data['enable_registration'],
                ':enable_social_login' => $data['enable_social_login'],
                ':require_email_verification' => $data['require_email_verification'],
                ':maintenance_mode' => $data['maintenance_mode']
            ];
            
            // เพิ่ม multilingual fields
            $languages = ['th', 'en', 'zh'];
            foreach ($languages as $lang) {
                $key = 'site_name_' . $lang;
                if (isset($data[$key])) {
                    $updateFields[] = "site_name_" . $lang . " = :site_name_" . $lang;
                    $params[':site_name_' . $lang] = $data[$key];
                }
            }
            
            if ($result['count'] > 0) {
                $sql = "UPDATE bk_system_settings SET " . implode(', ', $updateFields) . " WHERE id = 1";
            } else {
                // สำหรับ INSERT
                $fields = [
                    'id', 'site_name', 'site_url', 'timezone', 'date_format', 'time_format',
                    'currency', 'currency_symbol', 'default_language', 'items_per_page',
                    'enable_registration', 'enable_social_login', 'require_email_verification',
                    'maintenance_mode', 'created_at'
                ];
                $values = [
                    1, ':site_name', ':site_url', ':timezone', ':date_format', ':time_format',
                    ':currency', ':currency_symbol', ':default_language', ':items_per_page',
                    ':enable_registration', ':enable_social_login', ':require_email_verification',
                    ':maintenance_mode', 'NOW()'
                ];
                
                foreach ($languages as $lang) {
                    if (isset($data['site_name_' . $lang])) {
                        $fields[] = "site_name_" . $lang;
                        $values[] = ":site_name_" . $lang;
                    }
                }
                
                $sql = "INSERT INTO bk_system_settings (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            }
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error updating system settings: " . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // UTILITY METHODS
    // ============================================
    
    public function isAdmin($userId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "SELECT role FROM bk_users WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ============================================
    // REVIEWS METHODS
    // ============================================
    
    /**
     * ดึงรีวิวทั้งหมด
     */
    public function getAllReviews($filters = []) {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT r.*, 
                    u.first_name, u.last_name, u.email,
                    b.booking_reference,
                    h.hotel_name
                FROM bk_reviews r
                LEFT JOIN bk_users u ON r.user_id = u.user_id
                LEFT JOIN bk_bookings b ON r.booking_id = b.booking_id
                LEFT JOIN bk_hotels h ON r.hotel_id = h.hotel_id
                WHERE 1=1
            ";
            
            $params = [];
            if (!empty($filters['status'])) {
                $sql .= " AND r.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['rating'])) {
                $sql .= " AND r.rating = :rating";
                $params[':rating'] = $filters['rating'];
            }
            
            $sql .= " ORDER BY r.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting reviews: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * อัปเดตสถานะรีวิว
     */
    public function updateReviewStatus($reviewId, $status) {
        try {
            $conn = $this->db->getConnection();
            $sql = "UPDATE bk_reviews SET status = :status WHERE review_id = :review_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':review_id' => $reviewId
            ]);
        } catch (Exception $e) {
            error_log("Error updating review status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ลบรีวิว
     */
    public function deleteReview($reviewId) {
        try {
            $conn = $this->db->getConnection();
            $sql = "DELETE FROM bk_reviews WHERE review_id = :review_id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute([':review_id' => $reviewId]);
        } catch (Exception $e) {
            error_log("Error deleting review: " . $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // EMAIL TEMPLATES METHODS
    // ============================================
    
    /**
     * ดึง email templates ทั้งหมด
     */
    public function getEmailTemplates() {
        try {
            $conn = $this->db->getConnection();
            
            // ตรวจสอบว่ามีตารางหรือไม่
            $checkTable = $conn->query("SHOW TABLES LIKE 'bk_email_templates'");
            if ($checkTable->rowCount() == 0) {
                return [];
            }
            
            $sql = "SELECT * FROM bk_email_templates ORDER BY template_key ASC";
            $stmt = $conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting email templates: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * อัปเดต email template
     */
    public function updateEmailTemplate($templateKey, $data) {
        try {
            $conn = $this->db->getConnection();
            
            // ตรวจสอบว่ามีตารางหรือไม่
            $checkTable = $conn->query("SHOW TABLES LIKE 'bk_email_templates'");
            if ($checkTable->rowCount() == 0) {
                // สร้างตาราง
                $createTableSql = "
                    CREATE TABLE IF NOT EXISTS `bk_email_templates` (
                        `template_id` INT PRIMARY KEY AUTO_INCREMENT,
                        `template_key` VARCHAR(100) NOT NULL UNIQUE,
                        `template_name` VARCHAR(255) NOT NULL,
                        `content_th` TEXT,
                        `content_en` TEXT,
                        `content_zh` TEXT,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_template_key (`template_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ";
                $conn->exec($createTableSql);
            }
            
            // ตรวจสอบว่ามี record หรือไม่
            $checkSql = "SELECT COUNT(*) as count FROM bk_email_templates WHERE template_key = :key";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':key' => $templateKey]);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists['count'] > 0) {
                // UPDATE
                $sql = "UPDATE bk_email_templates SET 
                        template_name = :template_name,
                        content_th = :content_th,
                        content_en = :content_en,
                        content_zh = :content_zh
                        WHERE template_key = :template_key";
            } else {
                // INSERT
                $sql = "INSERT INTO bk_email_templates 
                        (template_key, template_name, content_th, content_en, content_zh)
                        VALUES 
                        (:template_key, :template_name, :content_th, :content_en, :content_zh)";
            }
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':template_key' => $templateKey,
                ':template_name' => $data['template_name'] ?? '',
                ':content_th' => $data['content_th'] ?? '',
                ':content_en' => $data['content_en'] ?? '',
                ':content_zh' => $data['content_zh'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Error updating email template: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * อัปเดต email templates หลายตัวพร้อมกัน
     */
    public function updateEmailTemplates($templates) {
        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();
            
            foreach ($templates as $key => $data) {
                if (!$this->updateEmailTemplate($key, $data)) {
                    $conn->rollBack();
                    return false;
                }
            }
            
            $conn->commit();
            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error updating email templates: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ดึง QR Code URL จาก settings
     */
    public function getQRCodeUrl() {
        try {
            $conn = $this->db->getConnection();
            
            // ตรวจสอบว่ามี column qr_code_url ใน bk_system_settings หรือไม่
            $checkCol = $conn->query("SHOW COLUMNS FROM bk_system_settings LIKE 'qr_code_url'");
            if ($checkCol->rowCount() > 0) {
                $sql = "SELECT qr_code_url FROM bk_system_settings WHERE id = 1 LIMIT 1";
                $stmt = $conn->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && !empty($result['qr_code_url'])) {
                    return $result['qr_code_url'];
                }
            }
            
            // Fallback: ใช้ default path
            if (defined('SITE_URL')) {
                return rtrim(SITE_URL, '/') . '/images/QR-Diamond.jpg';
            }
            
            return '';
        } catch (Exception $e) {
            error_log("Error getting QR code URL: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * อัปโหลด QR Code หรือบันทึก URL
     */
    public function uploadQRCode($file = null, $url = '') {
        try {
            $conn = $this->db->getConnection();
            
            // เพิ่ม column ถ้ายังไม่มี
            $checkCol = $conn->query("SHOW COLUMNS FROM bk_system_settings LIKE 'qr_code_url'");
            if ($checkCol->rowCount() == 0) {
                $conn->exec("ALTER TABLE bk_system_settings ADD COLUMN qr_code_url VARCHAR(500) AFTER maintenance_mode");
            }
            
            $qrCodeUrl = '';
            
            // ถ้ามีการอัปโหลดไฟล์
            if ($file && isset($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
                // Validate file
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $maxSize = 5242880; // 5MB
                
                if (!in_array($file['type'], $allowedTypes)) {
                    return [
                        'success' => false,
                        'message' => 'ไฟล์ต้องเป็น JPG, PNG หรือ GIF เท่านั้น'
                    ];
                }
                
                if ($file['size'] > $maxSize) {
                    return [
                        'success' => false,
                        'message' => 'ขนาดไฟล์ต้องไม่เกิน 5MB'
                    ];
                }
                
                // สร้างชื่อไฟล์
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'QR-Diamond.' . $extension;
                $uploadPath = PROJECT_ROOT . '/images/';
                
                // สร้าง directory ถ้ายังไม่มี
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                $filepath = $uploadPath . $filename;
                
                // ลบไฟล์เก่าถ้ามี
                $oldFiles = glob($uploadPath . 'QR-Diamond.*');
                foreach ($oldFiles as $oldFile) {
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                // ย้ายไฟล์
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // สร้าง URL
                    if (defined('SITE_URL')) {
                        $qrCodeUrl = rtrim(SITE_URL, '/') . '/images/' . $filename;
                    } else {
                        $qrCodeUrl = '/images/' . $filename;
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'ไม่สามารถอัปโหลดไฟล์ได้'
                    ];
                }
            } elseif (!empty($url)) {
                // ใช้ URL ที่กรอกมา
                $qrCodeUrl = $url;
            } else {
                return [
                    'success' => false,
                    'message' => 'กรุณาอัปโหลดไฟล์หรือกรอก URL'
                ];
            }
            
            // บันทึก URL ลง database
            $checkSql = "SELECT COUNT(*) as count FROM bk_system_settings WHERE id = 1";
            $checkStmt = $conn->query($checkSql);
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists['count'] > 0) {
                $sql = "UPDATE bk_system_settings SET qr_code_url = :qr_code_url WHERE id = 1";
            } else {
                $sql = "INSERT INTO bk_system_settings (id, qr_code_url) VALUES (1, :qr_code_url)";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([':qr_code_url' => $qrCodeUrl]);
            
            return [
                'success' => true,
                'message' => 'บันทึก QR Code สำเร็จ',
                'url' => $qrCodeUrl
            ];
            
        } catch (Exception $e) {
            error_log("Error uploading QR code: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
}