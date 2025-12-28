<?php
/**
 * Hotel Class
 * Handles hotel search, details, and room availability
 */

class Hotel {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Search hotels
     */
    public function searchHotels($city = '', $checkIn = '', $checkOut = '', $guests = 1, $page = 1) {
        try {
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT h.*, 
                    (SELECT AVG(rating) FROM bk_reviews WHERE hotel_id = h.hotel_id) as avg_rating,
                    (SELECT COUNT(*) FROM bk_reviews WHERE hotel_id = h.hotel_id) as review_count,
                    (SELECT MIN(base_price) FROM bk_room_types WHERE hotel_id = h.hotel_id AND status = 'available') as min_price
                    FROM bk_hotels h 
                    WHERE h.status = 'active'";
            
            $params = [];
            
            if (!empty($city)) {
                $sql .= " AND h.city LIKE :city";
                $params['city'] = "%{$city}%";
            }
            
            $sql .= " ORDER BY h.star_rating DESC, h.hotel_name ASC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get total hotel count for pagination
     */
    public function getTotalHotels($city = '') {
        try {
            $sql = "SELECT COUNT(*) as total FROM bk_hotels WHERE status = 'active'";
            $params = [];
            
            if (!empty($city)) {
                $sql .= " AND city LIKE :city";
                $params['city'] = "%{$city}%";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result['total'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get hotel by ID
     */
    public function getHotelById($hotelId) {
        try {
            $sql = "SELECT h.*, 
                    (SELECT AVG(rating) FROM bk_reviews WHERE hotel_id = h.hotel_id) as avg_rating,
                    (SELECT COUNT(*) FROM bk_reviews WHERE hotel_id = h.hotel_id) as review_count
                    FROM bk_hotels h 
                    WHERE h.hotel_id = :hotel_id AND h.status = 'active'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['hotel_id' => $hotelId]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get room types for a hotel
     */
    public function getRoomTypes($hotelId) {
        try {
            $sql = "SELECT * FROM bk_room_types 
                    WHERE hotel_id = :hotel_id AND status = 'available'
                    ORDER BY base_price ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['hotel_id' => $hotelId]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Check room availability
     */
    public function checkAvailability($roomTypeId, $checkIn, $checkOut, $numRooms = 1) {
        try {
            $sql = "SELECT rt.total_rooms,
                    (SELECT COUNT(*) FROM bk_bookings 
                     WHERE room_type_id = :room_type_id 
                     AND status NOT IN ('cancelled')
                     AND (
                        (check_in <= :check_in AND check_out > :check_in)
                        OR (check_in < :check_out AND check_out >= :check_out)
                        OR (check_in >= :check_in AND check_out <= :check_out)
                     )
                    ) as booked_rooms
                    FROM bk_room_types rt
                    WHERE rt.room_type_id = :room_type_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'room_type_id' => $roomTypeId,
                'check_in' => $checkIn,
                'check_out' => $checkOut
            ]);
            
            $result = $stmt->fetch();
            
            if ($result) {
                $availableRooms = $result['total_rooms'] - $result['booked_rooms'];
                return $availableRooms >= $numRooms;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get hotel reviews
     */
    public function getHotelReviews($hotelId, $limit = 10) {
        try {
            $sql = "SELECT r.*, u.first_name, u.last_name, u.email
                    FROM bk_reviews r
                    JOIN bk_users u ON r.user_id = u.user_id
                    WHERE r.hotel_id = :hotel_id
                    ORDER BY r.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':hotel_id', $hotelId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get popular destinations
     */
    public function getPopularDestinations($limit = 6) {
        try {
            $sql = "SELECT city, country, COUNT(*) as hotel_count,
                    (SELECT images FROM bk_hotels WHERE city = h.city LIMIT 1) as sample_image
                    FROM bk_hotels h
                    WHERE status = 'active'
                    GROUP BY city, country
                    ORDER BY hotel_count DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get featured hotels
     */
    public function getFeaturedHotels($limit = 8) {
        try {
            $sql = "SELECT h.*, 
                    (SELECT AVG(rating) FROM bk_reviews WHERE hotel_id = h.hotel_id) as avg_rating,
                    (SELECT COUNT(*) FROM bk_reviews WHERE hotel_id = h.hotel_id) as review_count,
                    (SELECT MIN(base_price) FROM bk_room_types WHERE hotel_id = h.hotel_id AND status = 'available') as min_price
                    FROM bk_hotels h 
                    WHERE h.status = 'active'
                    ORDER BY h.star_rating DESC, RAND()
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Add to wishlist
     */
    public function addToWishlist($userId, $hotelId) {
        try {
            $sql = "INSERT INTO bk_wishlist (user_id, hotel_id) VALUES (:user_id, :hotel_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'hotel_id' => $hotelId
            ]);
            
            return ['success' => true, 'message' => 'เพิ่มในรายการโปรดแล้ว'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        }
    }
    
    /**
     * Remove from wishlist
     */
    public function removeFromWishlist($userId, $hotelId) {
        try {
            $sql = "DELETE FROM bk_wishlist WHERE user_id = :user_id AND hotel_id = :hotel_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'hotel_id' => $hotelId
            ]);
            
            return ['success' => true, 'message' => 'ลบออกจากรายการโปรดแล้ว'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
        }
    }
}
