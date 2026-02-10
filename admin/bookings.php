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

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$message = '';
$messageType = '';

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            if ($admin->updateBookingStatus($_POST['booking_id'], $_POST['status'])) {
                $message = 'อัปเดตสถานะสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการอัปเดตสถานะ';
                $messageType = 'error';
            }
            break;
            
        case 'delete':
            if ($admin->deleteBooking($_POST['booking_id'])) {
                $message = 'ลบการจองสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการลบข้อมูล';
                $messageType = 'error';
            }
            break;
            
        case 'close_room':
            if (!empty($_POST['room_type_id'])) {
                if ($admin->closeRoomQuickly($_POST['room_type_id'])) {
                    $message = 'ปิดห้องพักสำเร็จ!';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการปิดห้องพัก';
                    $messageType = 'error';
                }
            } else {
                $message = 'ไม่พบข้อมูลห้องพัก';
                $messageType = 'error';
            }
            break;
            
        case 'open_room':
            if (!empty($_POST['room_type_id'])) {
                if ($admin->openRoom($_POST['room_type_id'])) {
                    $message = 'เปิดห้องพักสำเร็จ!';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการเปิดห้องพัก';
                    $messageType = 'error';
                }
            } else {
                $message = 'ไม่พบข้อมูลห้องพัก';
                $messageType = 'error';
            }
            break;
    }
}

// ตัวกรอง
$filters = [
    'status' => $_GET['status'] ?? '',
    'from_date' => $_GET['from_date'] ?? '',
    'to_date' => $_GET['to_date'] ?? ''
];

$bookings = $admin->getAllBookings($filters);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการจอง - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-calendar-check"></i> จัดการการจอง</h1>
                <p>ดูและจัดการการจองห้องพักทั้งหมด</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> ตัวกรอง</h2>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="form-row">
                            <div class="form-group">
                                <label>สถานะ</label>
                                <select name="status">
                                    <option value="">ทั้งหมด</option>
                                    <option value="pending" <?= $filters['status'] == 'pending' ? 'selected' : '' ?>>รอยืนยัน</option>
                                    <option value="confirmed" <?= $filters['status'] == 'confirmed' ? 'selected' : '' ?>>ยืนยันแล้ว</option>
                                    <option value="checked_in" <?= $filters['status'] == 'checked_in' ? 'selected' : '' ?>>เช็คอินแล้ว</option>
                                    <option value="completed" <?= $filters['status'] == 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                                    <option value="cancelled" <?= $filters['status'] == 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>จาก</label>
                                <input type="date" name="from_date" value="<?= htmlspecialchars($filters['from_date']) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>ถึง</label>
                                <input type="date" name="to_date" value="<?= htmlspecialchars($filters['to_date']) ?>">
                            </div>
                            
                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bookings Table -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายการจอง (<?= count($bookings) ?> รายการ)</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ลูกค้า</th>
                                    <th>ห้อง</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>ราคา</th>
                                    <th>สถานะการจอง</th>
                                    <th>สถานะห้อง</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">ไม่พบข้อมูลการจอง</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><strong>#<?= $booking['booking_id'] ?></strong></td>
                                            <td>
                                                <strong><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></strong><br>
                                                <small><?= htmlspecialchars($booking['email']) ?></small><br>
                                                <small><i class="fas fa-phone"></i> <?= htmlspecialchars($booking['phone'] ?? '-') ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($booking['room_type_name'] ?? 'N/A') ?></strong>
                                                <?php if (!empty($booking['room_type_id'])): ?>
                                                    <br><small class="text-muted">ID: <?= $booking['room_type_id'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $checkIn = $booking['check_in_date'] ?? $booking['check_in'] ?? '';
                                                echo $checkIn ? date('d/m/Y', strtotime($checkIn)) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $checkOut = $booking['check_out_date'] ?? $booking['check_out'] ?? '';
                                                echo $checkOut ? date('d/m/Y', strtotime($checkOut)) : '-';
                                                ?>
                                            </td>
                                            <td>
                                                <strong style="color: #28a745; font-size: 16px;">
                                                    ฿<?= number_format($booking['total_price'] ?? $booking['total_amount'] ?? 0, 0) ?>
                                                </strong>
                                            </td>
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
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-primary" onclick="viewBooking(<?= $booking['booking_id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $booking['booking_id'] ?>, 'confirmed')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบการจองนี้?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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
        function viewBooking(id) {
            window.location.href = 'booking-detail.php?id=' + id;
        }
        
        function updateStatus(id, status) {
            const statusText = {
                'pending': 'รอยืนยัน',
                'confirmed': 'ยืนยันแล้ว',
                'checked_in': 'เช็คอินแล้ว',
                'completed': 'เสร็จสิ้น',
                'cancelled': 'ยกเลิก'
            };
            
            if (confirm('ต้องการเปลี่ยนสถานะการจองเป็น "' + (statusText[status] || status) + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" value="${id}">
                    <input type="hidden" name="status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeRoomQuickly(roomTypeId, roomName) {
            if (confirm('ต้องการปิดห้องพัก "' + roomName + '" แบบด่วน?\n\nห้องนี้จะไม่สามารถจองได้จนกว่าจะเปิดอีกครั้ง')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="close_room">
                    <input type="hidden" name="room_type_id" value="${roomTypeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function openRoom(roomTypeId, roomName) {
            if (confirm('ต้องการเปิดห้องพัก "' + roomName + '" อีกครั้ง?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="open_room">
                    <input type="hidden" name="room_type_id" value="${roomTypeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>