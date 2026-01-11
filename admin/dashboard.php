<?php
/**
 * Admin Dashboard
 * ใช้ sidebar.php และ header.php ที่มีอยู่แล้ว
 */

//// init for SESSION , PROJECT_PATH , etc..
$projectRoot = __DIR__;
while (!file_exists($projectRoot . '/includes/init.php')) {
    $parent = dirname($projectRoot);
    if ($parent === $projectRoot) {
        die('Error: Cannot find project root');
    }
    $projectRoot = $parent;
}
require_once $projectRoot . '/includes/init.php';
require_once PROJECT_ROOT . '/config/config.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่าเป็น admin
$isAdmin = false;
if (isset($_SESSION['admin_id'])) {
    $isAdmin = true;
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['admin_id'] = $_SESSION['user_id'];
    }
}

if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// Set admin_name for sidebar/header
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
}

// Admin Class
class Admin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getDashboardStats() {
        try {
            $conn = $this->db->getConnection();
            $stats = [
                'total_bookings' => 0,
                'pending_bookings' => 0,
                'total_revenue' => 0,
                'active_rooms' => 0
            ];
            
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM bk_bookings");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_bookings'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                error_log("Error: " . $e->getMessage());
            }
            
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM bk_bookings WHERE status = 'pending'");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['pending_bookings'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                error_log("Error: " . $e->getMessage());
            }
            
            try {
                $stmt = $conn->query("SELECT SUM(total_amount) as total FROM bk_bookings WHERE status IN ('confirmed', 'completed')");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_revenue'] = $result['total'] ?? 0;
            } catch (Exception $e) {
                error_log("Error: " . $e->getMessage());
            }
            
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM bk_room_types WHERE status = 'available'");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['active_rooms'] = $result['count'] ?? 0;
            } catch (Exception $e) {
                error_log("Error: " . $e->getMessage());
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error in getDashboardStats: " . $e->getMessage());
            return [
                'total_bookings' => 0,
                'pending_bookings' => 0,
                'total_revenue' => 0,
                'active_rooms' => 0
            ];
        }
    }
    
    public function getRecentBookings($limit = 10) {
        try {
            $conn = $this->db->getConnection();
            $sql = "
                SELECT 
                    b.booking_id,
                    b.check_in,
                    b.check_out,
                    b.status,
                    b.total_amount,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as guest_name,
                    rt.name as room_name
                FROM bk_bookings b
                LEFT JOIN bk_users u ON b.user_id = u.user_id
                LEFT JOIN bk_room_types rt ON b.room_type_id = rt.room_type_id
                ORDER BY b.created_at DESC
                LIMIT :limit
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getRecentBookings: " . $e->getMessage());
            return [];
        }
    }
}

$admin = new Admin();
$stats = $admin->getDashboardStats();
$recentBookings = $admin->getRecentBookings(10);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        /* Layout */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            color: white;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.9rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 2rem;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left: 4px solid #4CAF50;
        }
        
        .nav-item i {
            width: 24px;
            margin-right: 0.75rem;
        }
        
        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 0.5rem 1.5rem;
        }
        
        /* Top Header */
        .top-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.95rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .header-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 50px;
        }
        
        .user-avatar i {
            font-size: 2rem;
            color: #667eea;
        }
        
        .user-info {
            line-height: 1.3;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .user-role {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Content Area */
        .content-area {
            padding: 2rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .stat-icon-blue { background: #e3f2fd; color: #1976d2; }
        .stat-icon-green { background: #e8f5e9; color: #388e3c; }
        .stat-icon-orange { background: #fff3e0; color: #f57c00; }
        .stat-icon-purple { background: #f3e5f5; color: #7b1fa2; }
        
        .stat-content h3 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .stat-content p {
            color: #666;
            font-size: 0.95rem;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .content-card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success { background: #e8f5e9; color: #388e3c; }
        .badge-warning { background: #fff3e0; color: #f57c00; }
        .badge-danger { background: #ffebee; color: #d32f2f; }
        
        .no-data {
            text-align: center;
            color: #999;
            padding: 3rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .header-item {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_bookings']) ?></h3>
                            <p>การจองทั้งหมด</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['pending_bookings']) ?></h3>
                            <p>รอดำเนินการ</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-green">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3>฿<?= number_format($stats['total_revenue']) ?></h3>
                            <p>รายได้ทั้งหมด</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-purple">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['active_rooms']) ?></h3>
                            <p>ห้องพักที่ใช้งาน</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="content-card">
                    <h2><i class="fas fa-list"></i> การจองล่าสุด</h2>
                    <?php if (empty($recentBookings)): ?>
                        <div class="no-data">
                            <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <p>ยังไม่มีการจอง</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>รหัสการจอง</th>
                                    <th>ชื่อลูกค้า</th>
                                    <th>ประเภทห้อง</th>
                                    <th>เช็คอิน</th>
                                    <th>เช็คเอาท์</th>
                                    <th>สถานะ</th>
                                    <th>ยอดรวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($booking['booking_id']) ?></td>
                                        <td><?= htmlspecialchars($booking['guest_name'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($booking['room_name'] ?: 'N/A') ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td>
                                        <td>
                                            <?php
                                            $status = $booking['status'];
                                            $badgeClass = 'badge-warning';
                                            $statusText = $status;
                                            if ($status === 'confirmed') {
                                                $badgeClass = 'badge-success';
                                                $statusText = 'ยืนยันแล้ว';
                                            } elseif ($status === 'cancelled') {
                                                $badgeClass = 'badge-danger';
                                                $statusText = 'ยกเลิก';
                                            } elseif ($status === 'pending') {
                                                $statusText = 'รอดำเนินการ';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                        </td>
                                        <td>฿<?= number_format($booking['total_amount'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>