<?php
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

require_once PROJECT_ROOT . '/includes/init.php';
require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$admin = new Admin();
$stats = $admin->getDashboardStats();

// Get current month/year or from query
$currentMonth = $_GET['month'] ?? date('n');
$currentYear = $_GET['year'] ?? date('Y');

// Get all bookings for the month
$startDate = date('Y-m-01', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$endDate = date('Y-m-t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

$bookings = $admin->getAllBookings([
    'from_date' => $startDate,
    'to_date' => $endDate
]);

// Group bookings by date
$bookingsByDate = [];
foreach ($bookings as $booking) {
    // ใช้ check_in และ check_out แทน check_in_date และ check_out_date
    $checkInDate = $booking['check_in'] ?? $booking['check_in_date'] ?? null;
    $checkOutDate = $booking['check_out'] ?? $booking['check_out_date'] ?? null;
    
    if (!$checkInDate || !$checkOutDate) {
        continue; // ข้ามถ้าไม่มีวันที่
    }
    
    $checkIn = new DateTime($checkInDate);
    $checkOut = new DateTime($checkOutDate);
    
    // Add booking to each date in the range
    $current = clone $checkIn;
    while ($current <= $checkOut) {
        $dateKey = $current->format('Y-m-d');
        if (!isset($bookingsByDate[$dateKey])) {
            $bookingsByDate[$dateKey] = [];
        }
        $bookingsByDate[$dateKey][] = $booking;
        $current->modify('+1 day');
    }
}

// Navigation
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทินการจอง - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .calendar-nav {
            display: flex;
            gap: 1rem;
        }
        
        .calendar-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e0e0e0;
            border: 1px solid #e0e0e0;
        }
        
        .calendar-day-header {
            background: #f8f9fa;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 0.5rem;
            position: relative;
        }
        
        .calendar-day.other-month {
            background: #fafafa;
            color: #bbb;
        }
        
        .calendar-day.today {
            background: #e3f2fd;
        }
        
        .day-number {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .booking-count {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .booking-pills {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .booking-pill {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .booking-pill.confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .booking-pill.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .booking-pill.checked_in {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .legend {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-calendar-alt"></i> ปฏิทินการจอง</h1>
                <p>ดูการจองในรูปแบบปฏิทิน</p>
            </div>
            
            <div class="calendar-header">
                <div class="calendar-title">
                    <?= $monthNames[$currentMonth] ?> <?= $currentYear + 543 ?>
                </div>
                <div class="calendar-nav">
                    <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline">
                        <i class="fas fa-chevron-left"></i> เดือนก่อน
                    </a>
                    <a href="calendar.php" class="btn btn-primary">
                        <i class="fas fa-calendar-day"></i> วันนี้
                    </a>
                    <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline">
                        เดือนถัดไป <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="calendar-container">
                <div class="calendar-grid">
                    <!-- Day headers -->
                    <div class="calendar-day-header">อาทิตย์</div>
                    <div class="calendar-day-header">จันทร์</div>
                    <div class="calendar-day-header">อังคาร</div>
                    <div class="calendar-day-header">พุธ</div>
                    <div class="calendar-day-header">พฤหัสบดี</div>
                    <div class="calendar-day-header">ศุกร์</div>
                    <div class="calendar-day-header">เสาร์</div>
                    
                    <?php
                    // Get first day of month
                    $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                    $firstDayOfWeek = date('w', $firstDay);
                    $daysInMonth = date('t', $firstDay);
                    
                    // Add empty cells for days before month starts
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo '<div class="calendar-day other-month"></div>';
                    }
                    
                    // Add days of month
                    $today = date('Y-m-d');
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $date = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                        $isToday = ($date == $today);
                        $dayBookings = $bookingsByDate[$date] ?? [];
                        
                        $classes = 'calendar-day';
                        if ($isToday) $classes .= ' today';
                        
                        echo '<div class="' . $classes . '">';
                        echo '<div class="day-number">' . $day . '</div>';
                        
                        if (!empty($dayBookings)) {
                            echo '<div class="booking-count">' . count($dayBookings) . ' การจอง</div>';
                            echo '<div class="booking-pills">';
                            
                            $displayCount = 0;
                            foreach ($dayBookings as $booking) {
                                if ($displayCount >= 3) {
                                    echo '<div class="booking-pill">+' . (count($dayBookings) - 3) . ' เพิ่มเติม</div>';
                                    break;
                                }
                                
                                $statusClass = $booking['status'];
                                $guestName = htmlspecialchars($booking['first_name']);
                                
                                echo '<div class="booking-pill ' . $statusClass . '">' . $guestName . '</div>';
                                $displayCount++;
                            }
                            
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    }
                    
                    // Add empty cells for remaining days
                    $remainingCells = 7 - (($firstDayOfWeek + $daysInMonth) % 7);
                    if ($remainingCells < 7) {
                        for ($i = 0; $i < $remainingCells; $i++) {
                            echo '<div class="calendar-day other-month"></div>';
                        }
                    }
                    ?>
                </div>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: #fef3c7;"></div>
                        <span>รอยืนยัน</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #d1fae5;"></div>
                        <span>ยืนยันแล้ว</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #dbeafe;"></div>
                        <span>เช็คอินแล้ว</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #e3f2fd;"></div>
                        <span>วันนี้</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>