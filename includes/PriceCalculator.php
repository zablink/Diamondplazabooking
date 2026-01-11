<?php
/**
 * PriceCalculator Class
 * คำนวณราคาห้องพักแบบ dynamic ตามฤดูกาลและอาหารเช้า
 */


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

class PriceCalculator {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * คำนวณราคารวมสำหรับการจอง
     * 
     * @param int $roomTypeId ID ของประเภทห้อง
     * @param string $checkIn วันที่ Check-in (Y-m-d)
     * @param string $checkOut วันที่ Check-out (Y-m-d)
     * @param bool $includeBreakfast ต้องการอาหารเช้าหรือไม่
     * @return array [total_price, breakdown, nights, breakfast_included]
     */
    public function calculateTotalPrice($roomTypeId, $checkIn, $checkOut, $includeBreakfast = false) {
        try {
            // ดึงข้อมูลห้อง
            $roomInfo = $this->getRoomTypeInfo($roomTypeId);
            if (!$roomInfo) {
                throw new Exception('Room type not found');
            }
            
            // คำนวณจำนวนคืน
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
            $nights = $checkInDate->diff($checkOutDate)->days;
            
            if ($nights < 1) {
                throw new Exception('Invalid booking dates');
            }
            
            // คำนวณราคาแต่ละวัน
            $dailyBreakdown = [];
            $totalPrice = 0;
            $currentDate = clone $checkInDate;
            
            for ($i = 0; $i < $nights; $i++) {
                $dateStr = $currentDate->format('Y-m-d');
                
                // หาราคาสำหรับวันนี้
                $dailyPrice = $this->getPriceForDate($roomTypeId, $dateStr, $roomInfo);
                
                // เพิ่มค่าอาหารเช้า (ถ้าไม่รวมในราคาห้อง และลูกค้าต้องการ)
                $breakfastCost = 0;
                if (!$roomInfo['breakfast_included'] && $includeBreakfast) {
                    $breakfastCost = $roomInfo['breakfast_price'];
                    $dailyPrice += $breakfastCost;
                }
                
                $dailyBreakdown[] = [
                    'date' => $dateStr,
                    'day_name' => $currentDate->format('l'),
                    'base_price' => $roomInfo['base_price'],
                    'adjusted_price' => $dailyPrice - $breakfastCost,
                    'breakfast_cost' => $breakfastCost,
                    'total' => $dailyPrice,
                    'season' => $this->getSeasonForDate($roomTypeId, $dateStr)
                ];
                
                $totalPrice += $dailyPrice;
                $currentDate->modify('+1 day');
            }
            
            return [
                'success' => true,
                'total_price' => $totalPrice,
                'base_price' => $roomInfo['base_price'],
                'room_type_name' => $roomInfo['room_type_name'],
                'nights' => $nights,
                'breakfast_included_in_room' => (bool)$roomInfo['breakfast_included'],
                'breakfast_requested' => $includeBreakfast,
                'breakfast_price_per_night' => $roomInfo['breakfast_price'],
                'daily_breakdown' => $dailyBreakdown,
                'check_in' => $checkIn,
                'check_out' => $checkOut
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงข้อมูลห้อง
     */
    private function getRoomTypeInfo($roomTypeId) {
        $sql = "SELECT room_type_id, room_type_name, base_price, 
                       breakfast_included, breakfast_price
                FROM bk_room_types 
                WHERE room_type_id = :room_type_id AND status = 'active'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['room_type_id' => $roomTypeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * คำนวณราคาสำหรับวันที่ระบุ
     */
    private function getPriceForDate($roomTypeId, $date, $roomInfo) {
        $basePrice = $roomInfo['base_price'];
        
        // หา seasonal price ที่มี priority สูงสุด
        $sql = "SELECT season_name, price_modifier_type, price_modifier_value, priority
                FROM bk_seasonal_prices
                WHERE room_type_id = :room_type_id
                  AND :date BETWEEN start_date AND end_date
                  AND is_active = TRUE
                ORDER BY priority DESC
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'room_type_id' => $roomTypeId,
            'date' => $date
        ]);
        
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($season) {
            // คำนวณราคาตาม type
            if ($season['price_modifier_type'] === 'percentage') {
                $adjustment = $basePrice * ($season['price_modifier_value'] / 100);
                return $basePrice + $adjustment;
            } else { // fixed
                return $basePrice + $season['price_modifier_value'];
            }
        }
        
        // ไม่มี seasonal price ใช้ราคาฐาน
        return $basePrice;
    }
    
    /**
     * ดึงชื่อฤดูกาลสำหรับวันที่ระบุ
     */
    private function getSeasonForDate($roomTypeId, $date) {
        $sql = "SELECT season_name
                FROM bk_seasonal_prices
                WHERE room_type_id = :room_type_id
                  AND :date BETWEEN start_date AND end_date
                  AND is_active = TRUE
                ORDER BY priority DESC
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'room_type_id' => $roomTypeId,
            'date' => $date
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['season_name'] : 'Regular Season';
    }
    
    /**
     * ดึงราคาอย่างง่ายสำหรับแสดงผลเบื้องต้น
     */
    public function getSimplePrice($roomTypeId) {
        $roomInfo = $this->getRoomTypeInfo($roomTypeId);
        if (!$roomInfo) {
            return null;
        }
        
        // ราคาเริ่มต้น (ราคาต่ำสุด)
        $sql = "SELECT MIN(
                    CASE 
                        WHEN price_modifier_type = 'percentage' THEN 
                            :base_price + (:base_price * price_modifier_value / 100)
                        WHEN price_modifier_type = 'fixed' THEN 
                            :base_price + price_modifier_value
                        ELSE :base_price
                    END
                ) as min_price
                FROM bk_seasonal_prices
                WHERE room_type_id = :room_type_id AND is_active = TRUE";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'base_price' => $roomInfo['base_price'],
            'room_type_id' => $roomTypeId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $minPrice = $result['min_price'] ?? $roomInfo['base_price'];
        
        return [
            'base_price' => $roomInfo['base_price'],
            'from_price' => $minPrice,
            'breakfast_included' => (bool)$roomInfo['breakfast_included'],
            'breakfast_price' => $roomInfo['breakfast_price']
        ];
    }
    
    /**
     * บันทึกข้อมูลราคาลง booking
     */
    public function savePriceToBooking($bookingId, $priceData) {
        try {
            $sql = "UPDATE bk_bookings SET
                    original_base_price = :base_price,
                    seasonal_adjustment = :seasonal_adjustment,
                    breakfast_cost = :breakfast_cost,
                    applied_season = :season,
                    total_price = :total_price
                    WHERE booking_id = :booking_id";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'base_price' => $priceData['base_price'] ?? 0,
                'seasonal_adjustment' => $priceData['seasonal_adjustment'] ?? 0,
                'breakfast_cost' => $priceData['breakfast_cost'] ?? 0,
                'season' => $priceData['season'] ?? 'Regular',
                'total_price' => $priceData['total_price'],
                'booking_id' => $bookingId
            ]);
            
        } catch (Exception $e) {
            error_log('Error saving price to booking: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ดึงรายการฤดูกาลทั้งหมดสำหรับห้อง
     */
    public function getSeasonalPrices($roomTypeId) {
        $sql = "SELECT * FROM bk_seasonal_prices
                WHERE room_type_id = :room_type_id
                ORDER BY start_date, priority DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['room_type_id' => $roomTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}