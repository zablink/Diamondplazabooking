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
                SELECT b.booking_id, b.check_in, b.check_out, b.status, b.total_amount,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as guest_name,
                    rt.name as room_name
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
                    u.email as guest_email, u.phone as guest_phone, rt.name as room_name
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
            
            $sql = "
                INSERT INTO bk_room_types (name, description, price, max_guests, total_rooms, 
                    amenities, breakfast_included, status, created_at)
                VALUES (:name, :description, :price, :max_guests, :total_rooms,
                    :amenities, :breakfast_included, 'available', NOW())
            ";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':name' => $data['name'] ?? '',
                ':description' => $data['description'] ?? '',
                ':price' => $data['price'] ?? 0,
                ':max_guests' => $data['occupancy'] ?? $data['max_guests'] ?? 2,
                ':total_rooms' => $data['total_rooms'] ?? 0,
                ':amenities' => $amenitiesJson,
                ':breakfast_included' => $data['breakfast_included'] ?? 0
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
            
            $sql = "
                UPDATE bk_room_types SET name = :name, description = :description, price = :price,
                    max_guests = :max_guests, total_rooms = :total_rooms, amenities = :amenities,
                    breakfast_included = :breakfast_included, status = :status, updated_at = NOW()
                WHERE room_type_id = :room_type_id
            ";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':name' => $data['name'] ?? '',
                ':description' => $data['description'] ?? '',
                ':price' => $data['price'] ?? 0,
                ':max_guests' => $data['occupancy'] ?? $data['max_guests'] ?? 2,
                ':total_rooms' => $data['total_rooms'] ?? 0,
                ':amenities' => $amenitiesJson,
                ':breakfast_included' => $data['breakfast_included'] ?? 0,
                ':status' => $data['status'] ?? 'available',
                ':room_type_id' => $roomTypeId
            ]);
        } catch (Exception $e) {
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
                SELECT rt.name as room_type_name, COUNT(b.booking_id) as booking_count, SUM(b.total_amount) as total_revenue
                FROM bk_room_types rt
                LEFT JOIN bk_bookings b ON rt.room_type_id = b.room_type_id AND b.status IN ('confirmed', 'completed')
                GROUP BY rt.room_type_id, rt.name
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
    
    public function getHotelSettings() {
        try {
            $conn = $this->db->getConnection();
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
            
            return $settings;
        } catch (Exception $e) {
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
            
            $checkSql = "SELECT COUNT(*) as count FROM bk_hotel_settings WHERE hotel_id = 1";
            $checkStmt = $conn->query($checkSql);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $sql = "
                    UPDATE bk_hotel_settings SET
                        hotel_name = :name, description = :description, address = :address,
                        city = :city, phone = :phone, email = :email, updated_at = NOW()
                    WHERE hotel_id = 1
                ";
            } else {
                $sql = "
                    INSERT INTO bk_hotel_settings (hotel_id, hotel_name, description, address, 
                        city, phone, email, created_at)
                    VALUES (1, :name, :description, :address, :city, :phone, :email, NOW())
                ";
            }
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':name' => $data['name'] ?? '',
                ':description' => $data['description'] ?? '',
                ':address' => $data['address'] ?? '',
                ':city' => $data['city'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':email' => $data['email'] ?? ''
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ============================================
    // SYSTEM SETTINGS METHODS
    // ============================================
    
    public function getSystemSettings() {
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
            
            $checkSql = "SELECT COUNT(*) as count FROM bk_system_settings WHERE id = 1";
            $checkStmt = $conn->query($checkSql);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                $sql = "
                    UPDATE bk_system_settings SET
                        site_name = :site_name,
                        site_url = :site_url,
                        timezone = :timezone,
                        date_format = :date_format,
                        time_format = :time_format,
                        currency = :currency,
                        currency_symbol = :currency_symbol,
                        default_language = :default_language,
                        items_per_page = :items_per_page,
                        enable_registration = :enable_registration,
                        enable_social_login = :enable_social_login,
                        require_email_verification = :require_email_verification,
                        maintenance_mode = :maintenance_mode,
                        updated_at = NOW()
                    WHERE id = 1
                ";
            } else {
                $sql = "
                    INSERT INTO bk_system_settings (
                        id, site_name, site_url, timezone, date_format, time_format,
                        currency, currency_symbol, default_language, items_per_page,
                        enable_registration, enable_social_login, require_email_verification,
                        maintenance_mode, created_at
                    ) VALUES (
                        1, :site_name, :site_url, :timezone, :date_format, :time_format,
                        :currency, :currency_symbol, :default_language, :items_per_page,
                        :enable_registration, :enable_social_login, :require_email_verification,
                        :maintenance_mode, NOW()
                    )
                ";
            }
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                ':site_name' => $data['site_name'],
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
            ]);
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
}