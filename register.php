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

require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/auth/Auth.php';
require_once PROJECT_ROOT . '/modules/auth/SocialAuth.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('index.php');
}

$auth = new Auth();
$socialAuth = new SocialAuth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    // Validate required fields
    if (empty($email)) {
        $error = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif (empty($firstName)) {
        $error = 'กรุณากรอกชื่อ';
    } elseif (empty($lastName)) {
        $error = 'กรุณากรอกนามสกุล';
    } elseif (empty($password)) {
        $error = 'กรุณากรอกรหัสผ่าน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $result = $auth->register($email, $password, $firstName, $lastName, $phone);
        
        if ($result['success']) {
            // Auto-login after successful registration
            // Get the newly registered user data
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $sql = "SELECT * FROM bk_users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Set session variables (auto-login)
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'] ?? 'customer';
                $_SESSION['login_method'] = 'email';
                
                setFlashMessage('สมัครสมาชิกสำเร็จ! ยินดีต้อนรับ', 'success');
                // Redirect to index page instead of login (use relative path for proper URL)
                redirect('index.php');
            } else {
                // Fallback: if user not found, redirect to login
                setFlashMessage('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ', 'success');
                redirect('login.php');
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Get social login URLs
$googleAuthUrl = $socialAuth->getGoogleAuthUrl();
$facebookAuthUrl = $socialAuth->getFacebookAuthUrl();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }
        
        .auth-box {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-hover);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .auth-header p {
            color: var(--text-secondary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.9rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-hotel"></i> Hotel Booking
            </a>
            <ul class="nav-links">
                <li><a href="index.php">หน้าแรก</a></li>
                
                <li><a href="login.php">เข้าสู่ระบบ</a></li>
                <li><a href="register.php">สมัครสมาชิก</a></li>
            </ul>
        </div>
    </nav>

    <div class="container auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>
                    <i class="fas fa-user-plus"></i> สมัครสมาชิก
                </h1>
                <p>สร้างบัญชีเพื่อเริ่มจองโรงแรม</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="first_name">
                            <i class="fas fa-user"></i> ชื่อ *
                        </label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               placeholder="ชื่อจริง" 
                               required
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            <i class="fas fa-user"></i> นามสกุล *
                        </label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               placeholder="นามสกุล" 
                               required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> อีเมล *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="example@email.com" 
                           required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> เบอร์โทรศัพท์
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           placeholder="0812345678"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> รหัสผ่าน * <small>(อย่างน้อย 6 ตัวอักษร)</small>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="••••••••" 
                           required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> ยืนยันรหัสผ่าน *
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="••••••••" 
                           required>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: start; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                        <input type="checkbox" required style="margin-top: 0.2rem;">
                        <span>
                            ฉันยอมรับ <a href="#" style="color: var(--primary-color);">ข้อกำหนดการใช้งาน</a> 
                            และ <a href="#" style="color: var(--primary-color);">นโยบายความเป็นส่วนตัว</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-user-plus"></i> สมัครสมาชิก
                </button>
            </form>

            <div class="auth-footer">
                <p style="color: var(--text-secondary);">
                    มีบัญชีอยู่แล้ว? 
                    <a href="login.php">เข้าสู่ระบบที่นี่</a>
                </p>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <div style="text-align: center; color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
                    หรือสมัครด้วย
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" 
                       class="btn btn-outline" 
                       style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none;">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Google
                    </a>
                    <a href="<?php echo htmlspecialchars($facebookAuthUrl); ?>" 
                       class="btn btn-outline" 
                       style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none;">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#1877F2" d="M48 24C48 10.745 37.255 0 24 0S0 10.745 0 24c0 11.979 8.776 21.908 20.25 23.708v-16.77h-6.094V24h6.094v-5.288c0-6.014 3.583-9.337 9.065-9.337 2.625 0 5.372.469 5.372.469v5.906h-3.026c-2.981 0-3.911 1.85-3.911 3.75V24h6.656l-1.064 6.938H27.75v16.77C39.224 45.908 48 35.978 48 24z"/>
                            <path fill="#FFF" d="M33.342 30.938L34.406 24H27.75v-4.5c0-1.9.93-3.75 3.911-3.75h3.026V9.844s-2.747-.469-5.372-.469c-5.482 0-9.065 3.323-9.065 9.337V24h-6.094v6.938h6.094v16.77a24.174 24.174 0 007.5 0v-16.77h5.592z"/>
                        </svg>
                        Facebook
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 Hotel Booking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
