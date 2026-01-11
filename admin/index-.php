<?php
// booking/admin/index.php

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
require_once PROJECT_ROOT . '/modules/admin/Admin.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$stats = $admin->getDashboardStats();
$recentBookings = $admin->getRecentBookings(10);
$upcomingCheckIns = $admin->getUpcomingCheckIns(7);
$roomTypeStats = $admin->getRoomTypeStats();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <p>ภาพรวมระบบจัดการโรงแรม</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_bookings']) ?></h3>
                        <p>การจองทั้งหมด</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['todays_checkins']) ?></h3>
                        <p>Check-in วันนี้</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['todays_checkouts']) ?></h3>
                        <p>Check-out วันนี้</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['current_guests']) ?></h3>
                        <p>แขกในโรงแรม</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>฿<?= number_format($stats['monthly_revenue'], 0) ?></h3>
                        <p>รายได้เดือนนี้</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['pending_bookings']) ?></h3>
                        <p>รอยืนยัน</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_room_types']) ?></h3>
                        <p>ประเภทห้องพัก</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_users']) ?></h3>
                        <p>ลูกค้าทั้งหมด</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings & Upcoming Check-ins -->
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> การจองล่าสุด</h2>
                        <a href="bookings.php" class="btn btn-sm btn-outline">ดูทั้งหมด</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ลูกค้า</th>
                                        <th>ห้อง</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>ราคา</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentBookings)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">ยังไม่มีข้อมูลการจอง</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentBookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></strong><br>
                                                    <small><?= htmlspecialchars($booking['email']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($booking['room_type_name']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td>
                                                <td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td>
                                                <td>฿<?= number_format($booking['total_price'], 0) ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'pending' => 'badge-warning',
                                                        'confirmed' => 'badge-success',
                                                        'checked_in' => 'badge-info',
                                                        'completed' => 'badge-secondary',
                                                        'cancelled' => 'badge-danger'
                                                    ];
                                                    $statusText = [
                                                        'pending' => 'รอยืนยัน',
                                                        'confirmed' => 'ยืนยันแล้ว',
                                                        'checked_in' => 'เช็คอินแล้ว',
                                                        'completed' => 'เสร็จสิ้น',
                                                        'cancelled' => 'ยกเลิก'
                                                    ];
                                                    ?>
                                                    <span class="badge <?= $statusClass[$booking['status']] ?? 'badge-secondary' ?>">
                                                        <?= $statusText[$booking['status']] ?? $booking['status'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-calendar-alt"></i> Check-in ที่กำลังจะมาถึง</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingCheckIns)): ?>
                            <p class="text-center text-muted">ไม่มี Check-in ที่กำลังจะมาถึง</p>
                        <?php else: ?>
                            <div class="checkin-list">
                                <?php foreach ($upcomingCheckIns as $checkin): ?>
                                    <div class="checkin-item">
                                        <div class="checkin-date">
                                            <div class="date-box">
                                                <div class="day"><?= date('d', strtotime($checkin['check_in'])) ?></div>
                                                <div class="month"><?= date('M', strtotime($checkin['check_in'])) ?></div>
                                            </div>
                                        </div>
                                        <div class="checkin-info">
                                            <h4><?= htmlspecialchars($checkin['first_name'] . ' ' . $checkin['last_name']) ?></h4>
                                            <p>
                                                <i class="fas fa-bed"></i> <?= htmlspecialchars($checkin['room_type_name']) ?><br>
                                                <i class="fas fa-phone"></i> <?= htmlspecialchars($checkin['phone']) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Room Type Statistics -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> สถิติประเภทห้องพัก</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ประเภทห้อง</th>
                                    <th>จำนวนการจอง</th>
                                    <th>รายได้รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($roomTypeStats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">ยังไม่มีข้อมูล</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($roomTypeStats as $stat): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($stat['room_type_name']) ?></strong></td>
                                            <td><?= number_format($stat['booking_count']) ?> ครั้ง</td>
                                            <td>฿<?= number_format($stat['total_revenue'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>