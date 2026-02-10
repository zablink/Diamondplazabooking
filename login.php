<?php
// ⭐ Start Output Buffering เพื่อป้องกัน "headers already sent"
ob_start();

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

// ⭐ Function สำหรับ redirect ที่ทำงานแน่นอน
function safeRedirect($url) {
    // ลอง header() ก่อน
    if (!headers_sent()) {
        ob_end_clean(); // Clear output buffer
        session_write_close();
        header('Location: ' . $url);
        exit;
    }
    
    // ถ้า header ไม่ได้ ใช้ JavaScript + Meta Refresh
    ob_end_clean();
    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '">';
    echo '</head><body>';
    echo '<script>window.location.href="' . htmlspecialchars($url) . '";</script>';
    echo '<p>Redirecting... <a href="' . htmlspecialchars($url) . '">Click here if not redirected</a></p>';
    echo '</body></html>';
    exit;
}

// ⭐ DEBUG: Log session info
error_log("=== LOGIN.PHP START ===");
error_log("Session ID: " . session_id());
error_log("Has user_id: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO'));

// ตรวจสอบว่ามี logged_out parameter หรือไม่
if (isset($_GET['logged_out'])) {
    error_log("⚠️ Redirected back with logged_out=" . $_GET['logged_out']);
    $error = 'Session หายไป - กรุณา login อีกครั้ง';
}

// ตรวจสอบว่า login แล้วหรือยัง
if (isset($_SESSION['user_id'])) {
    error_log("User already logged in, redirecting...");
    
    if (isset($_SESSION['admin_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
        error_log("→ Redirecting to admin/dashboard.php");
        safeRedirect('admin/dashboard.php');
    } else {
        error_log("→ Redirecting to index.php");
        safeRedirect('index.php');
    }
}

// สร้าง Google OAuth URL
$googleClientId = GOOGLE_CLIENT_ID ?? '';
$googleRedirectUri = SITE_URL . '/auth/google-callback.php';
$googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $googleClientId,
    'redirect_uri' => $googleRedirectUri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online'
]);

$error = '';
$success = '';

// จัดการ login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== LOGIN POST REQUEST ===");
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt - Email: " . $email);
    
    if (empty($email) || empty($password)) {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
        error_log("❌ Empty email or password");
    } else {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            $stmt = $pdo->prepare("SELECT * FROM bk_users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user exists
            if (!$user) {
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
                error_log("❌ User not found for email: " . $email);
            } elseif (empty($user['password'])) {
                // User registered with social login, no password set
                $error = 'บัญชีนี้สมัครด้วย Social Login กรุณาใช้ Google หรือ Facebook เพื่อเข้าสู่ระบบ';
                error_log("❌ User registered with social login: " . $email);
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
                error_log("❌ Invalid password for email: " . $email);
            } else {
                error_log("✓ Password verified for user: " . $user['user_id']);
                
                // เก็บข้อมูลใน session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_method'] = 'email';
                
                error_log("Session data set:");
                error_log("  user_id: " . $_SESSION['user_id']);
                error_log("  role: " . $_SESSION['role']);
                
                // Redirect ตาม role
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_id'] = $user['user_id'];
                    error_log("  admin_id: " . $_SESSION['admin_id']);
                    
                    error_log("→ Redirecting to admin/dashboard.php");
                    
                    // ⭐ ใช้ safeRedirect แทน header()
                    safeRedirect('admin/dashboard.php');
                } else {
                    error_log("→ Redirecting to index.php");
                    safeRedirect('index.php');
                }
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
            error_log('❌ Login Error: ' . $e->getMessage());
        }
    }
}

error_log("=== LOGIN.PHP END (Showing form) ===");

// ⭐ Flush output buffer ก่อนแสดง HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Hotel Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e0e0e0;
            --success-color: #27ae60;
            --error-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 0.5rem;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: var(--text-light);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }
        
        .divider span {
            padding: 0 1rem;
            font-size: 0.9rem;
        }
        
        .social-login {
            display: grid;
            gap: 1rem;
        }
        
        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: white;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .btn-social:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-light);
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #060;
            border: 1px solid #cfc;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
                margin: 10px;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.75rem;
            }
            
            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-hotel"></i> เข้าสู่ระบบ</h1>
            <p>ยินดีต้อนรับกลับมา!</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label>อีเมล</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" 
                               name="email" 
                               placeholder="your@email.com" 
                               required
                               autocomplete="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               name="password" 
                               placeholder="••••••••" 
                               required
                               autocomplete="current-password">
                    </div>
                    <div class="forgot-password">
                        <a href="forgot-password.php">ลืมรหัสผ่าน?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="divider">
                <span>หรือเข้าสู่ระบบด้วย</span>
            </div>
            
            <div class="social-login">
                <a href="<?= htmlspecialchars($googleAuthUrl) ?>" class="btn-social">
                    <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    <span>Google</span>
                </a>
            </div>
            
            <div class="register-link">
                ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังเข้าสู่ระบบ...';
        });
        
        document.querySelector('input[name="email"]').focus();
    </script>
</body>
</html>