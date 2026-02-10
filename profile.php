<?php
/**
 * Profile Page
 * แสดงและแก้ไขข้อมูลโปรไฟล์ผู้ใช้
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

$page_title = __('profile.title') . ' - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/modules/auth/Auth.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /booking/login.php');
    exit();
}

$auth = new Auth();
$user = $auth->getCurrentUser();
$login_method = $_SESSION['login_method'] ?? 'normal';
$is_social_login = ($login_method === 'google' || $login_method === 'facebook');

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $result = $auth->updateProfile(
            $_SESSION['user_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone']
        );
        
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        // Refresh user data
        if ($result['success']) {
            $user = $auth->getCurrentUser();
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password (only for normal login)
        if (!$is_social_login) {
            $result = $auth->changePassword(
                $_SESSION['user_id'],
                $_POST['current_password'],
                $_POST['new_password']
            );
            
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}
?>

<style>
    .container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .avatar-section {
        position: relative;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .avatar-badge {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: white;
        color: #667eea;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .profile-info h1 {
        margin-bottom: 10px;
        font-size: 32px;
    }

    .profile-meta {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
    }

    .card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card h2 {
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 600;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }

    .form-group input:disabled {
        background: #f5f5f5;
        cursor: not-allowed;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 15px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #e0e0e0;
        color: #333;
    }

    .btn-secondary:hover {
        background: #d0d0d0;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .info-box {
        background: #e7f3ff;
        border: 1px solid #b3d7ff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .info-box p {
        margin: 0;
        color: #0066cc;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
    }

    .stat-card h3 {
        font-size: 32px;
        margin-bottom: 5px;
    }

    .stat-card p {
        opacity: 0.9;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .profile-meta {
            flex-direction: column;
        }
    }
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <span><?php echo $message_type === 'success' ? '✅' : '❌'; ?></span>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="avatar-section">
            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? 'assets/images/default-avatar.png'); ?>" 
                 alt="Profile Avatar" 
                 class="profile-avatar"
                 onerror="this.src='assets/images/default-avatar.png'">
            <div class="avatar-badge">
                <?php
                if ($login_method === 'google') echo '🔵';
                elseif ($login_method === 'facebook') echo '📘';
                else echo '👤';
                ?>
            </div>
        </div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
            <div class="profile-meta">
                <div class="meta-item">
                    📧 <?php echo htmlspecialchars($user['email']); ?>
                </div>
                <div class="meta-item">
                    <?php
                    if ($login_method === 'google') echo '🔵 Google Login';
                    elseif ($login_method === 'facebook') echo '📘 Facebook Login';
                    else echo '🔑 Normal Login';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Information -->
    <div class="card">
        <h2>📝 ข้อมูลส่วนตัว</h2>
        
        <?php if ($is_social_login): ?>
            <div class="info-box">
                <p>
                    <span>ℹ️</span>
                    คุณเข้าสู่ระบบด้วย <?php echo ucfirst($login_method); ?> 
                    บางข้อมูลอาจไม่สามารถแก้ไขได้
                </p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" 
                           name="first_name" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                           required>
                </div>
                <div class="form-group">
                    <label>นามสกุล</label>
                    <input type="text" 
                           name="last_name" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           disabled>
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="tel" 
                           name="phone" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           placeholder="0812345678">
                </div>
            </div>

            <div class="form-group">
                <label>สมัครสมาชิกเมื่อ</label>
                <input type="text" 
                       value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" 
                       disabled>
            </div>

            <button type="submit" name="update_profile" class="btn btn-primary">
                💾 บันทึกการเปลี่ยนแปลง
            </button>
        </form>
    </div>

    <!-- Change Password (only for normal login) -->
    <?php if (!$is_social_login): ?>
    <div class="card">
        <h2>🔐 เปลี่ยนรหัสผ่าน</h2>
        
        <form method="POST">
            <div class="form-group">
                <label>รหัสผ่านปัจจุบัน</label>
                <input type="password" 
                       name="current_password" 
                       required 
                       minlength="6">
            </div>

            <div class="form-group">
                <label>รหัสผ่านใหม่</label>
                <input type="password" 
                       name="new_password" 
                       required 
                       minlength="6">
            </div>

            <div class="form-group">
                <label>ยืนยันรหัสผ่านใหม่</label>
                <input type="password" 
                       name="confirm_password" 
                       required 
                       minlength="6"
                       oninput="checkPasswordMatch(this)">
                <small id="password-match-message" style="color: #dc3545; display: none;">
                    รหัสผ่านไม่ตรงกัน
                </small>
            </div>

            <button type="submit" name="change_password" class="btn btn-primary" id="change-password-btn">
                🔒 เปลี่ยนรหัสผ่าน
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Account Information -->
    <div class="card">
        <h2>ℹ️ ข้อมูลบัญชี</h2>
        
        <div class="form-group">
            <label>ID ผู้ใช้</label>
            <input type="text" value="<?php echo htmlspecialchars($user['user_id']); ?>" disabled>
        </div>

        <div class="form-group">
            <label>สถานะบัญชี</label>
            <input type="text" value="<?php echo $user['role'] === 'admin' ? '👑 Admin' : '👤 Customer'; ?>" disabled>
        </div>

        <?php if ($is_social_login): ?>
        <div class="info-box">
            <p>
                <span>🔒</span>
                บัญชีของคุณเชื่อมต่อกับ <?php echo ucfirst($login_method); ?> 
                หากต้องการยกเลิกการเชื่อมต่อ กรุณาติดต่อทีมสนับสนุน
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function checkPasswordMatch(input) {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = input.value;
    const message = document.getElementById('password-match-message');
    const button = document.getElementById('change-password-btn');
    
    if (newPassword !== confirmPassword) {
        message.style.display = 'block';
        button.disabled = true;
        button.style.opacity = '0.5';
        button.style.cursor = 'not-allowed';
    } else {
        message.style.display = 'none';
        button.disabled = false;
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>