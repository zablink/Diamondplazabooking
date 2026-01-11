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
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$customers = $admin->getAllCustomers();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการลูกค้า - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> จัดการลูกค้า</h1>
                <p>ดูรายละเอียดและประวัติการจองของลูกค้า</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายชื่อลูกค้า (<?= count($customers) ?> คน)</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>อีเมล</th>
                                    <th>เบอร์โทร</th>
                                    <th>การจองทั้งหมด</th>
                                    <th>ยอดใช้จ่ายรวม</th>
                                    <th>สมัครเมื่อ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">ยังไม่มีข้อมูลลูกค้า</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?= $customer['user_id'] ?></td>
                                            <td><strong><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($customer['email']) ?></td>
                                            <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                                            <td><?= number_format($customer['total_bookings']) ?> ครั้ง</td>
                                            <td><strong>฿<?= number_format($customer['total_spent'], 0) ?></strong></td>
                                            <td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td>
                                            <td>
                                                <a href="customer-detail.php?id=<?= $customer['user_id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </a>
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
</body>
</html>
