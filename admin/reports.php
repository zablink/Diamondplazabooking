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

// Default date range - current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get revenue report
$revenueData = $admin->getRevenueReport($startDate, $endDate);

// Calculate totals
$totalRevenue = 0;
$totalBookings = 0;
foreach ($revenueData as $row) {
    $totalRevenue += $row['revenue'];
    $totalBookings += $row['bookings_count'];
}

$avgPerBooking = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

// Get room type stats
$roomTypeStats = $admin->getRoomTypeStats();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานรายได้ - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .summary-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .summary-card h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .summary-card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .chart-bar {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .chart-label {
            width: 150px;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .chart-bar-fill {
            flex: 1;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
            position: relative;
            transition: all 0.3s;
        }
        
        .chart-bar-fill:hover {
            opacity: 0.8;
        }
        
        .chart-value {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> รายงานรายได้</h1>
                <p>สถิติและรายงานทางการเงิน</p>
            </div>
            
            <!-- Date Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group">
                            <label>เริ่มต้น</label>
                            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>สิ้นสุด</label>
                            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> ดูรายงาน
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>฿<?= number_format($totalRevenue, 0) ?></h3>
                    <p>รายได้รวม</p>
                </div>
                
                <div class="summary-card">
                    <div class="summary-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3><?= number_format($totalBookings) ?></h3>
                    <p>จำนวนการจอง</p>
                </div>
                
                <div class="summary-card">
                    <div class="summary-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>฿<?= number_format($avgPerBooking, 0) ?></h3>
                    <p>เฉลี่ยต่อการจอง</p>
                </div>
            </div>
            
            <!-- Export Buttons -->
            <div class="export-buttons">
                <button class="btn btn-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary" onclick="printReport()">
                    <i class="fas fa-print"></i> พิมพ์
                </button>
            </div>
            
            <!-- Daily Revenue Chart -->
            <div class="chart-container">
                <h2 style="margin-bottom: 1.5rem;"><i class="fas fa-chart-bar"></i> รายได้รายวัน</h2>
                
                <?php if (empty($revenueData)): ?>
                    <p class="text-center text-muted">ไม่มีข้อมูลในช่วงเวลาที่เลือก</p>
                <?php else: ?>
                    <?php
                    $maxRevenue = max(array_column($revenueData, 'revenue'));
                    foreach ($revenueData as $row):
                        $percentage = $maxRevenue > 0 ? ($row['revenue'] / $maxRevenue * 100) : 0;
                    ?>
                        <div class="chart-bar">
                            <div class="chart-label">
                                <?= date('d/m/Y', strtotime($row['date'])) ?>
                            </div>
                            <div class="chart-bar-fill" style="width: <?= $percentage ?>%;">
                                <div class="chart-value">
                                    ฿<?= number_format($row['revenue'], 0) ?> (<?= $row['bookings_count'] ?> จอง)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Room Type Revenue -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-bed"></i> รายได้แยกตามประเภทห้อง</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ประเภทห้อง</th>
                                    <th>จำนวนการจอง</th>
                                    <th>รายได้รวม</th>
                                    <th>เฉลี่ยต่อการจอง</th>
                                    <th>% ของรายได้รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($roomTypeStats)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">ยังไม่มีข้อมูล</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $totalRoomRevenue = array_sum(array_column($roomTypeStats, 'total_revenue'));
                                    foreach ($roomTypeStats as $stat):
                                        $avgRevenue = $stat['booking_count'] > 0 ? $stat['total_revenue'] / $stat['booking_count'] : 0;
                                        $percentage = $totalRoomRevenue > 0 ? ($stat['total_revenue'] / $totalRoomRevenue * 100) : 0;
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($stat['room_type_name']) ?></strong></td>
                                            <td><?= number_format($stat['booking_count']) ?> ครั้ง</td>
                                            <td><strong>฿<?= number_format($stat['total_revenue'], 0) ?></strong></td>
                                            <td>฿<?= number_format($avgRevenue, 0) ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <div style="flex: 1; background: #e0e0e0; height: 10px; border-radius: 5px; overflow: hidden;">
                                                        <div style="width: <?= $percentage ?>%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                                    </div>
                                                    <span><?= number_format($percentage, 1) ?>%</span>
                                                </div>
                                            </td>
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
    
    <script>
        function exportToExcel() {
            alert('Export to Excel - Feature coming soon!');
        }
        
        function exportToPDF() {
            alert('Export to PDF - Feature coming soon!');
        }
        
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>