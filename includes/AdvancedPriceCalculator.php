<?php
// includes/AdvancedPriceCalculator.php
// Advanced Price Calculator with Seasonal Rates and Holiday Surcharges

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

/****************************/
require_once PROJECT_ROOT . '/includes/Database.php';

class AdvancedPriceCalculator {
    private $db;
    private $conn;
    
    public function __construct() {
        // Use singleton pattern to get database instance
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * คำนวณราคาห้องพักแบบละเอียด
     * 
     * @param int $roomTypeId - ID ของประเภทห้อง
     * @param string $checkIn - วันที่เช็คอิน (Y-m-d)
     * @param string $checkOut - วันที่เช็คเอาท์ (Y-m-d)
     * @param int $adults - จำนวนผู้ใหญ่
     * @param int $children - จำนวนเด็ก
     * @param int $rooms - จำนวนห้อง
     * @param bool $includeBreakfast - รวมอาหารเช้าหรือไม่
     * @return array - รายละเอียดราคา
     */
    public function calculatePrice($roomTypeId, $checkIn, $checkOut, $adults = 2, $children = 0, $rooms = 1, $includeBreakfast = false) {
        try {
            // 1. ดึงข้อมูลห้องพัก
            $roomType = $this->getRoomType($roomTypeId);
            if (!$roomType) {
                throw new Exception("ไม่พบข้อมูลห้องพัก");
            }
            
            // 2. คำนวณจำนวนคืน
            $nights = $this->calculateNights($checkIn, $checkOut);
            if ($nights <= 0) {
                throw new Exception("จำนวนคืนต้องมากกว่า 0");
            }
            
            // 3. ดึงราคาฐานและราคาตามฤดูกาล
            $basePrice = floatval($roomType['base_price']);
            $dailyPrices = $this->getDailyPrices($roomTypeId, $checkIn, $checkOut, $basePrice);
            
            // 4. คำนวณราคารวมทั้งหมด
            $subtotal = 0;
            $breakdown = [];
            
            foreach ($dailyPrices as $date => $priceInfo) {
                $dailyPrice = $priceInfo['price'];
                $subtotal += $dailyPrice;
                $breakdown[] = [
                    'date' => $date,
                    'price' => $dailyPrice,
                    'reason' => $priceInfo['reason'],
                    'is_weekend' => $priceInfo['is_weekend'],
                    'is_holiday' => $priceInfo['is_holiday']
                ];
            }
            
            // 5. คำนวณค่าผู้เข้าพักเพิ่มเติม (ถ้ามี)
            $guestSurcharge = $this->calculateGuestSurcharge($roomType, $adults, $children, $nights);
            
            // 6. คำนวณค่าอาหารเช้า
            $breakfastCost = 0;
            if ($includeBreakfast && !$roomType['breakfast_included']) {
                $breakfastPrice = floatval($roomType['breakfast_price'] ?? 0);
                $totalGuests = $adults + $children;
                $breakfastCost = $breakfastPrice * $totalGuests * $nights;
            }
            
            // 7. คำนวณค่าบริการและภาษี (ถ้ามี)
            $serviceFee = $this->calculateServiceFee($subtotal + $guestSurcharge);
            $vat = $this->calculateVAT($subtotal + $guestSurcharge + $serviceFee);
            
            // 8. คำนวณราคารวมต่อห้อง
            $totalPerRoom = $subtotal + $guestSurcharge + $breakfastCost + $serviceFee + $vat;
            
            // 9. คูณด้วยจำนวนห้อง
            $totalAllRooms = $totalPerRoom * $rooms;
            
            return [
                'success' => true,
                'room_type' => $roomType['room_name'],
                'base_price' => $basePrice,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'adults' => $adults,
                'children' => $children,
                'rooms' => $rooms,
                'daily_breakdown' => $breakdown,
                'subtotal' => $subtotal,
                'guest_surcharge' => $guestSurcharge,
                'breakfast_cost' => $breakfastCost,
                'service_fee' => $serviceFee,
                'vat' => $vat,
                'total_per_room' => $totalPerRoom,
                'total_all_rooms' => $totalAllRooms,
                'average_per_night' => $totalPerRoom / $nights
            ];
            
        } catch (Exception $e) {
            error_log("Price Calculation Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงข้อมูลประเภทห้อง
     */
    private function getRoomType($roomTypeId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM bk_room_types 
            WHERE room_type_id = ? AND status = 'available'
        ");
        $stmt->execute([$roomTypeId]);
        return $stmt->fetch();
    }
    
    /**
     * คำนวณจำนวนคืน
     */
    private function calculateNights($checkIn, $checkOut) {
        $start = new DateTime($checkIn);
        $end = new DateTime($checkOut);
        $diff = $start->diff($end);
        return $diff->days;
    }
    
    /**
     * ดึงราคารายวัน (รวมราคาตามฤดูกาลและวันหยุด)
     */
    private function getDailyPrices($roomTypeId, $checkIn, $checkOut, $basePrice) {
        $dailyPrices = [];
        $currentDate = new DateTime($checkIn);
        $endDate = new DateTime($checkOut);
        
        while ($currentDate < $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            
            // เช็คว่าเป็นวันหยุด/วันพิเศษหรือไม่
            $holiday = $this->getHolidaySurcharge($roomTypeId, $dateString);
            
            // เช็คว่าเป็นราคาตามฤดูกาลหรือไม่
            $seasonalRate = $this->getSeasonalRate($roomTypeId, $dateString);
            
            // เช็คว่าเป็นวันเสาร์-อาทิตย์หรือไม่
            $isWeekend = in_array($currentDate->format('N'), [6, 7]); // 6=Sat, 7=Sun
            
            // คำนวณราคาประจำวัน
            $price = $basePrice;
            $reason = "ราคาปกติ";
            
            if ($holiday) {
                $price = $holiday['surcharge_price'];
                $reason = "วันพิเศษ: " . $holiday['holiday_name'];
            } elseif ($seasonalRate) {
                $price = $seasonalRate['seasonal_price'];
                $reason = "ฤดูกาล: " . $seasonalRate['season_name'];
            } elseif ($isWeekend) {
                // อาจมีการปรับราคาวันหยุดสุดสัปดาห์
                $weekendSurcharge = $this->getWeekendSurcharge($roomTypeId);
                if ($weekendSurcharge > 0) {
                    $price += $weekendSurcharge;
                    $reason = "วันหยุดสุดสัปดาห์";
                }
            }
            
            $dailyPrices[$dateString] = [
                'price' => $price,
                'reason' => $reason,
                'is_weekend' => $isWeekend,
                'is_holiday' => $holiday ? true : false
            ];
            
            $currentDate->modify('+1 day');
        }
        
        return $dailyPrices;
    }
    
    /**
     * ดึงราคาวันพิเศษ/วันหยุด
     */
    private function getHolidaySurcharge($roomTypeId, $date) {
        $stmt = $this->conn->prepare("
            SELECT * FROM bk_holiday_surcharges 
            WHERE room_type_id = ? 
            AND holiday_date = ? 
            AND status = 'active'
        ");
        $stmt->execute([$roomTypeId, $date]);
        return $stmt->fetch();
    }
    
    /**
     * ดึงราคาตามฤดูกาล
     */
    private function getSeasonalRate($roomTypeId, $date) {
        $stmt = $this->conn->prepare("
            SELECT * FROM bk_seasonal_rates 
            WHERE room_type_id = ? 
            AND start_date <= ? 
            AND end_date >= ? 
            AND status = 'active'
            ORDER BY priority DESC
            LIMIT 1
        ");
        $stmt->execute([$roomTypeId, $date, $date]);
        return $stmt->fetch();
    }
    
    /**
     * ดึงราคาเพิ่มวันหยุดสุดสัปดาห์
     */
    private function getWeekendSurcharge($roomTypeId) {
        // สามารถ implement ได้ถ้าต้องการ
        return 0;
    }
    
    /**
     * คำนวณค่าผู้เข้าพักเพิ่มเติม
     */
    private function calculateGuestSurcharge($roomType, $adults, $children, $nights) {
        $surcharge = 0;
        $maxOccupancy = intval($roomType['max_occupancy']);
        $totalGuests = $adults + $children;
        
        // ถ้าเกินจำนวนผู้พักสูงสุด คิดค่าเพิ่ม
        if ($totalGuests > $maxOccupancy) {
            $extraGuests = $totalGuests - $maxOccupancy;
            $extraGuestPrice = floatval($roomType['extra_guest_price'] ?? 0);
            $surcharge = $extraGuestPrice * $extraGuests * $nights;
        }
        
        return $surcharge;
    }
    
    /**
     * คำนวณค่าบริการ
     */
    private function calculateServiceFee($amount) {
        // สามารถ implement ตามนโยบายของโรงแรม
        // เช่น 10% ของราคา
        return 0; // ปิดการใช้งานไว้ก่อน
    }
    
    /**
     * คำนวณภาษีมูลค่าเพิ่ม (VAT)
     */
    private function calculateVAT($amount) {
        // VAT 7%
        $vatRate = 0.07;
        return $amount * $vatRate;
    }
    
    /**
     * ตรวจสอบห้องว่าง
     */
    public function checkAvailability($roomTypeId, $checkIn, $checkOut, $roomsNeeded = 1) {
        try {
            // ดึงจำนวนห้องทั้งหมด
            $roomType = $this->getRoomType($roomTypeId);
            if (!$roomType) {
                return ['available' => false, 'message' => 'ไม่พบข้อมูลห้อง'];
            }
            
            $totalRooms = intval($roomType['total_rooms']);
            
            // เช็คจำนวนห้องที่ถูกจองแล้วในช่วงเวลานี้
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
            
        } catch (Exception $e) {
            error_log("Availability Check Error: " . $e->getMessage());
            return [
                'available' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบห้องว่าง'
            ];
        }
    }
}
