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
                
                // ตรวจสอบว่ามี daily rate with breakfast หรือไม่
                $breakfastCost = 0;
                $dailyPriceWithBreakfast = null;
                $adjustedPrice = $dailyPrice; // เก็บราคา room only ไว้
                
                try {
                    $checkCol = $this->conn->query("SHOW COLUMNS FROM bk_room_inventory LIKE 'price_with_breakfast'");
                    if ($checkCol->rowCount() > 0) {
                        $sql = "SELECT price_with_breakfast FROM bk_room_inventory 
                                WHERE room_type_id = :room_type_id AND date = :date LIMIT 1";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute([
                            'room_type_id' => $roomTypeId,
                            'date' => $dateStr
                        ]);
                        $dailyBreakfast = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($dailyBreakfast && !empty($dailyBreakfast['price_with_breakfast'])) {
                            $dailyPriceWithBreakfast = floatval($dailyBreakfast['price_with_breakfast']);
                        }
                    }
                } catch (Exception $e) {
                    // Ignore if column doesn't exist
                }
                
                // ถ้าต้องการอาหารเช้า
                if ($includeBreakfast) {
                    if ($dailyPriceWithBreakfast !== null) {
                        // ใช้ราคารวมอาหารเช้าจาก daily rate
                        $breakfastCost = $dailyPriceWithBreakfast - $adjustedPrice;
                        $dailyPrice = $dailyPriceWithBreakfast;
                    } elseif (!$roomInfo['breakfast_included']) {
                        // ใช้ราคาอาหารเช้าจาก room type
                        $breakfastCost = $roomInfo['breakfast_price'];
                        $dailyPrice += $breakfastCost;
                    }
                }
                
                $dailyBreakdown[] = [
                    'date' => $dateStr,
                    'day_name' => $currentDate->format('l'),
                    'base_price' => $roomInfo['base_price'],
                    'adjusted_price' => $adjustedPrice,
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
                WHERE room_type_id = :room_type_id AND status = 'available'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['room_type_id' => $roomTypeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * คำนวณราคาสำหรับวันที่ระบุ
     * Priority: 1. Daily Rate (bk_room_inventory) > 2. Seasonal Rate > 3. Base Price
     */
    private function getPriceForDate($roomTypeId, $date, $roomInfo) {
        $basePrice = $roomInfo['base_price'];
        
        // ตรวจสอบว่ามีคอลัมน์ price_with_breakfast หรือไม่
        try {
            $checkCol = $this->conn->query("SHOW COLUMNS FROM bk_room_inventory LIKE 'price_with_breakfast'");
            $hasBreakfastPrice = $checkCol->rowCount() > 0;
        } catch (Exception $e) {
            $hasBreakfastPrice = false;
        }
        
        // 1. ตรวจสอบ daily rate จาก bk_room_inventory ก่อน (Priority สูงสุด)
        $sql = "SELECT price, " . ($hasBreakfastPrice ? "price_with_breakfast, " : "") . "available_rooms
                FROM bk_room_inventory
                WHERE room_type_id = :room_type_id AND date = :date
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'room_type_id' => $roomTypeId,
            'date' => $date
        ]);
        
        $dailyRate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dailyRate && !empty($dailyRate['price'])) {
            // ใช้ราคาจาก daily rate
            return floatval($dailyRate['price']);
        }
        
        // 2. ตรวจสอบว่ามีคอลัมน์ price_modifier_type และ price_modifier_value หรือไม่
        $hasPriceModifierColumns = false;
        try {
            $checkCol1 = $this->conn->query("SHOW COLUMNS FROM bk_seasonal_rates LIKE 'price_modifier_type'");
            $checkCol2 = $this->conn->query("SHOW COLUMNS FROM bk_seasonal_rates LIKE 'price_modifier_value'");
            if ($checkCol1->rowCount() > 0 && $checkCol2->rowCount() > 0) {
                $hasPriceModifierColumns = true;
            }
        } catch (Exception $e) {
            $hasPriceModifierColumns = false;
        }
        
        // 3. ถ้าไม่มี daily rate ให้หา seasonal price ที่มี priority สูงสุด
        if ($hasPriceModifierColumns) {
            // ใช้คอลัมน์ใหม่ price_modifier_type และ price_modifier_value
            $sql = "SELECT season_name, price_modifier_type, price_modifier_value, priority
                    FROM bk_seasonal_rates
                    WHERE room_type_id = :room_type_id
                      AND :date BETWEEN start_date AND end_date
                      AND is_active = TRUE
                    ORDER BY priority DESC
                    LIMIT 1";
        } else {
            // ใช้คอลัมน์เก่า rate_multiplier และ base_rate_override
            $sql = "SELECT season_name, rate_multiplier, base_rate_override, priority
                    FROM bk_seasonal_rates
                    WHERE room_type_id = :room_type_id
                      AND :date BETWEEN start_date AND end_date
                      AND is_active = TRUE
                    ORDER BY priority DESC
                    LIMIT 1";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'room_type_id' => $roomTypeId,
            'date' => $date
        ]);
        
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($season) {
            if ($hasPriceModifierColumns) {
                // คำนวณราคาตาม type ใหม่
                if (isset($season['price_modifier_type']) && $season['price_modifier_type'] === 'percentage') {
                    $adjustment = $basePrice * (floatval($season['price_modifier_value']) / 100);
                    return $basePrice + $adjustment;
                } elseif (isset($season['price_modifier_type']) && $season['price_modifier_type'] === 'fixed') {
                    return $basePrice + floatval($season['price_modifier_value']);
                }
            } else {
                // ใช้คอลัมน์เก่า
                if (!empty($season['base_rate_override'])) {
                    return floatval($season['base_rate_override']);
                } elseif (!empty($season['rate_multiplier'])) {
                    return $basePrice * floatval($season['rate_multiplier']);
                }
            }
        }
        
        // 4. ไม่มี daily rate และ seasonal price ใช้ราคาฐาน
        return $basePrice;
    }
    
    /**
     * ดึงชื่อฤดูกาลสำหรับวันที่ระบุ
     */
    private function getSeasonForDate($roomTypeId, $date) {
        $sql = "SELECT season_name
                FROM bk_seasonal_rates
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
     * Priority: 1. Daily Rate (bk_room_inventory) > 2. Seasonal Rate > 3. Base Price
     */
    public function getSimplePrice($roomTypeId) {
        $roomInfo = $this->getRoomTypeInfo($roomTypeId);
        if (!$roomInfo) {
            return null;
        }
        
        $basePrice = $roomInfo['base_price'];
        $minPrice = $basePrice;
        
        // 1. ตรวจสอบราคาต่ำสุดจาก daily rates (30 วันถัดไป)
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        try {
            $checkCol = $this->conn->query("SHOW COLUMNS FROM bk_room_inventory LIKE 'price_with_breakfast'");
            $hasBreakfastPrice = $checkCol->rowCount() > 0;
            
            $sql = "SELECT MIN(price) as min_daily_price
                    FROM bk_room_inventory
                    WHERE room_type_id = :room_type_id 
                      AND date >= CURDATE() 
                      AND date <= :future_date
                      AND available_rooms > 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'room_type_id' => $roomTypeId,
                'future_date' => $futureDate
            ]);
            
            $dailyResult = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dailyResult && !empty($dailyResult['min_daily_price'])) {
                $minPrice = min($minPrice, floatval($dailyResult['min_daily_price']));
            }
        } catch (Exception $e) {
            // Ignore if table/column doesn't exist
        }
        
        // 2. ตรวจสอบราคาต่ำสุดจาก seasonal rates
        // ตรวจสอบว่ามีคอลัมน์ price_modifier_type และ price_modifier_value หรือไม่
        $hasPriceModifierColumns = false;
        try {
            $checkCol1 = $this->conn->query("SHOW COLUMNS FROM bk_seasonal_rates LIKE 'price_modifier_type'");
            $checkCol2 = $this->conn->query("SHOW COLUMNS FROM bk_seasonal_rates LIKE 'price_modifier_value'");
            if ($checkCol1->rowCount() > 0 && $checkCol2->rowCount() > 0) {
                $hasPriceModifierColumns = true;
            }
        } catch (Exception $e) {
            $hasPriceModifierColumns = false;
        }
        
        if ($hasPriceModifierColumns) {
            // ใช้คอลัมน์ใหม่ - ใช้ placeholder ที่แตกต่างกันเพื่อหลีกเลี่ยงปัญหา PDO
            $sql = "SELECT MIN(
                        CASE 
                            WHEN price_modifier_type = 'percentage' THEN 
                                :base_price1 + (:base_price2 * price_modifier_value / 100)
                            WHEN price_modifier_type = 'fixed' THEN 
                                :base_price3 + price_modifier_value
                            ELSE :base_price4
                        END
                    ) as min_seasonal_price
                    FROM bk_seasonal_rates
                    WHERE room_type_id = :room_type_id 
                      AND is_active = TRUE
                      AND end_date >= CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'base_price1' => $basePrice,
                'base_price2' => $basePrice,
                'base_price3' => $basePrice,
                'base_price4' => $basePrice,
                'room_type_id' => $roomTypeId
            ]);
        } else {
            // ใช้คอลัมน์เก่า - ใช้ placeholder ที่แตกต่างกัน
            $sql = "SELECT MIN(
                        CASE 
                            WHEN base_rate_override IS NOT NULL THEN base_rate_override
                            WHEN rate_multiplier IS NOT NULL THEN :base_price1 * rate_multiplier
                            ELSE :base_price2
                        END
                    ) as min_seasonal_price
                    FROM bk_seasonal_rates
                    WHERE room_type_id = :room_type_id 
                      AND is_active = TRUE
                      AND end_date >= CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'base_price1' => $basePrice,
                'base_price2' => $basePrice,
                'room_type_id' => $roomTypeId
            ]);
        }
        
        $seasonResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($seasonResult && !empty($seasonResult['min_seasonal_price'])) {
            $minPrice = min($minPrice, floatval($seasonResult['min_seasonal_price']));
        }
        
        return [
            'base_price' => $basePrice,
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
        $sql = "SELECT * FROM bk_seasonal_rates
                WHERE room_type_id = :room_type_id
                ORDER BY start_date, priority DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['room_type_id' => $roomTypeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}