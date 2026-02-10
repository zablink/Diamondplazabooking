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
            if ($admin->updateReviewStatus($_POST['review_id'], $_POST['status'])) {
                $message = 'อัปเดตสถานะสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการอัปเดตสถานะ';
                $messageType = 'error';
            }
            break;
            
        case 'delete':
            if ($admin->deleteReview($_POST['review_id'])) {
                $message = 'ลบรีวิวสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการลบข้อมูล';
                $messageType = 'error';
            }
            break;
    }
}

// ตัวกรอง
$filters = [
    'status' => $_GET['status'] ?? '',
    'rating' => $_GET['rating'] ?? ''
];

$reviews = $admin->getAllReviews($filters);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรีวิว - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .rating-stars .empty {
            color: #ddd;
        }
        .review-comment {
            max-width: 400px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-comment-dots"></i> จัดการรีวิวและคำติชม</h1>
                <p>ดูและจัดการรีวิวจากลูกค้าทั้งหมด</p>
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
                                    <option value="pending" <?= $filters['status'] == 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                                    <option value="approved" <?= $filters['status'] == 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                                    <option value="rejected" <?= $filters['status'] == 'rejected' ? 'selected' : '' ?>>ปฏิเสธ</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>คะแนน</label>
                                <select name="rating">
                                    <option value="">ทั้งหมด</option>
                                    <option value="5" <?= $filters['rating'] == '5' ? 'selected' : '' ?>>5 ดาว</option>
                                    <option value="4" <?= $filters['rating'] == '4' ? 'selected' : '' ?>>4 ดาว</option>
                                    <option value="3" <?= $filters['rating'] == '3' ? 'selected' : '' ?>>3 ดาว</option>
                                    <option value="2" <?= $filters['rating'] == '2' ? 'selected' : '' ?>>2 ดาว</option>
                                    <option value="1" <?= $filters['rating'] == '1' ? 'selected' : '' ?>>1 ดาว</option>
                                </select>
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
            
            <!-- Reviews Table -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> รายการรีวิว (<?= count($reviews) ?> รายการ)</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ลูกค้า</th>
                                    <th>โรงแรม</th>
                                    <th>การจอง</th>
                                    <th>คะแนน</th>
                                    <th>หัวข้อ</th>
                                    <th>ความคิดเห็น</th>
                                    <th>สถานะ</th>
                                    <th>วันที่</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reviews)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">ไม่พบข้อมูลรีวิว</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td><strong>#<?= $review['review_id'] ?></strong></td>
                                            <td>
                                                <strong><?= htmlspecialchars(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')) ?></strong><br>
                                                <small><?= htmlspecialchars($review['email'] ?? '-') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($review['hotel_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <small><?= htmlspecialchars($review['booking_reference'] ?? '-') ?></small>
                                            </td>
                                            <td>
                                                <div class="rating-stars">
                                                    <?php
                                                    $rating = (int)($review['rating'] ?? 0);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star empty"></i>';
                                                        }
                                                    }
                                                    ?>
                                                    <span style="margin-left: 5px; color: #666;"><?= $rating ?></span>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($review['title'] ?? '-') ?></td>
                                            <td>
                                                <div class="review-comment">
                                                    <?= htmlspecialchars(substr($review['comment'] ?? '', 0, 100)) ?>
                                                    <?= strlen($review['comment'] ?? '') > 100 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'badge-warning',
                                                    'approved' => 'badge-success',
                                                    'rejected' => 'badge-danger'
                                                ];
                                                $statusText = [
                                                    'pending' => 'รออนุมัติ',
                                                    'approved' => 'อนุมัติแล้ว',
                                                    'rejected' => 'ปฏิเสธ'
                                                ];
                                                $currentStatus = $review['status'] ?? 'pending';
                                                ?>
                                                <span class="badge <?= $statusClass[$currentStatus] ?? 'badge-secondary' ?>">
                                                    <?= $statusText[$currentStatus] ?? $currentStatus ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= $review['created_at'] ? date('d/m/Y H:i', strtotime($review['created_at'])) : '-' ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <?php if ($currentStatus != 'approved'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $review['review_id'] ?>, 'approved')" title="อนุมัติ">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($currentStatus != 'rejected'): ?>
                                                        <button class="btn btn-sm btn-warning" onclick="updateStatus(<?= $review['review_id'] ?>, 'rejected')" title="ปฏิเสธ">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบรีวิวนี้?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="ลบ">
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
        function updateStatus(id, status) {
            const statusText = {
                'pending': 'รออนุมัติ',
                'approved': 'อนุมัติแล้ว',
                'rejected': 'ปฏิเสธ'
            };
            
            if (confirm('ต้องการเปลี่ยนสถานะรีวิวเป็น "' + (statusText[status] || status) + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="review_id" value="${id}">
                    <input type="hidden" name="status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
