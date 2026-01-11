<?php
/**
 * Admin Users Management
 * จัดการผู้ดูแลระบบ (Admin Users)
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
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$admin = new Admin();
$message = '';
$messageType = '';

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $data = [
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'] ?? '',
                'role' => 'admin'
            ];
            
            if ($admin->createAdminUser($data)) {
                $message = 'เพิ่มผู้ดูแลระบบสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการเพิ่มผู้ดูแลระบบ';
                $messageType = 'error';
            }
            break;
            
        case 'edit':
            $data = [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email']
            ];
            
            // เปลี่ยนรหัสผ่านถ้ามีการกรอก
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            if ($admin->updateAdminUser($_POST['user_id'], $data)) {
                $message = 'อัปเดตข้อมูลสำเร็จ!';
                $messageType = 'success';
            } else {
                $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
                $messageType = 'error';
            }
            break;
            
        case 'delete':
            // ป้องกันการลบตัวเอง
            if ($_POST['user_id'] == $_SESSION['admin_id']) {
                $message = 'ไม่สามารถลบบัญชีของตัวเองได้';
                $messageType = 'error';
            } else {
                if ($admin->deleteAdminUser($_POST['user_id'])) {
                    $message = 'ลบผู้ดูแลระบบสำเร็จ!';
                    $messageType = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการลบข้อมูล';
                    $messageType = 'error';
                }
            }
            break;
    }
}

$adminUsers = $admin->getAdminUsers();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ดูแลระบบ - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .user-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #95a5a6;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h2 {
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="fas fa-user-shield"></i> จัดการผู้ดูแลระบบ</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> เพิ่มผู้ดูแลระบบ
                </button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> รายชื่อผู้ดูแลระบบ (<?= count($adminUsers) ?> คน)</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($adminUsers)): ?>
                        <p class="text-center">ยังไม่มีผู้ดูแลระบบในระบบ</p>
                    <?php else: ?>
                        <?php foreach ($adminUsers as $user): ?>
                            <div class="user-card">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                        <?php if ($user['user_id'] == $_SESSION['admin_id']): ?>
                                            <span class="badge badge-primary">คุณ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-email">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                    <div class="user-meta">
                                        <?php if ($user['phone']): ?>
                                            <span><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-calendar"></i> สมัครเมื่อ <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="user-actions">
                                    <button class="btn btn-sm btn-primary" onclick='openEditModal(<?= json_encode($user) ?>)'>
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('ต้องการลบผู้ดูแลระบบนี้?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> ลบ
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> เพิ่มผู้ดูแลระบบ</h2>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label>นามสกุล</label>
                    <input type="text" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>เบอร์โทร</label>
                    <input type="tel" name="phone">
                </div>
                
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeAddModal()">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> แก้ไขข้อมูล</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" name="first_name" id="edit_first_name" required>
                </div>
                
                <div class="form-group">
                    <label>นามสกุล</label>
                    <input type="text" name="last_name" id="edit_last_name" required>
                </div>
                
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>เบอร์โทร</label>
                    <input type="tel" name="phone" id="edit_phone">
                </div>
                
                <div class="form-group">
                    <label>รหัสผ่านใหม่ (เว้นว่างถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="password" name="password" minlength="6">
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
