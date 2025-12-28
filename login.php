<?php
require_once './config/config.php';
require_once './includes/Database.php';
require_once './includes/helpers.php';
require_once './modules/auth/Auth.php';
require_once './modules/auth/SocialAuth.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    redirect('/public/index.php');
}

$auth = new Auth();
$socialAuth = new SocialAuth();
$error = '';

// Store redirect URL for after login
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        // Check for redirect parameter
        $redirect = $_SESSION['redirect_after_login'] ?? '/public/index.php';
        unset($_SESSION['redirect_after_login']);
        setFlashMessage('เข้าสู่ระบบสำเร็จ!', 'success');
        header('Location: ' . $redirect);
        exit();
    } else {
        $error = $result['message'];
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
    <title>เข้าสู่ระบบ - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 200px);
            padding: 1.5rem 0;
        }
        
        .auth-box {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-hover);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .auth-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .auth-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .remember-forgot label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .remember-forgot a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .remember-forgot a:hover {
            text-decoration: underline;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.95rem;
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .social-login-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .social-login-title {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            position: relative;
        }
        
        .social-login-title::before,
        .social-login-title::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background-color: var(--border-color);
        }
        
        .social-login-title::before {
            left: 0;
        }
        
        .social-login-title::after {
            right: 0;
        }
        
        .social-login-buttons {
            display: flex;
            gap: 0.8rem;
        }
        
        .social-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: white;
            color: var(--text-primary);
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social-btn:hover {
            background-color: #f8f9fa;
            border-color: #ccc;
            transform: translateY(-1px);
        }
        
        .social-btn.google:hover {
            border-color: #EA4335;
        }
        
        .social-btn.facebook:hover {
            border-color: #1877F2;
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.9rem;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 480px) {
            .auth-box {
                padding: 2rem 1.5rem;
            }
            
            .social-login-buttons {
                flex-direction: column;
            }
            
            .social-login-title::before,
            .social-login-title::after {
                width: 25%;
            }
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
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </h1>
                <p>ยินดีต้อนรับกลับมา!</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> อีเมล
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="example@email.com" 
                           required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> รหัสผ่าน
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="••••••••" 
                           required>
                </div>

                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="remember">
                        <span>จดจำฉัน</span>
                    </label>
                    <a href="#">
                        ลืมรหัสผ่าน?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </button>
            </form>

            <!-- Social Login Section - ย้ายขึ้นมาให้เห็นง่ายขึ้น -->
            <div class="social-login-section">
                <div class="social-login-title">
                    หรือเข้าสู่ระบบด้วย
                </div>
                <div class="social-login-buttons">
                    <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" 
                       class="social-btn google">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Google
                    </a>
                    <a href="<?php echo htmlspecialchars($facebookAuthUrl); ?>" 
                       class="social-btn facebook">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#1877F2" d="M48 24C48 10.745 37.255 0 24 0S0 10.745 0 24c0 11.979 8.776 21.908 20.25 23.708v-16.77h-6.094V24h6.094v-5.288c0-6.014 3.583-9.337 9.065-9.337 2.625 0 5.372.469 5.372.469v5.906h-3.026c-2.981 0-3.911 1.85-3.911 3.75V24h6.656l-1.064 6.938H27.75v16.77C39.224 45.908 48 35.978 48 24z"/>
                            <path fill="#FFF" d="M33.342 30.938L34.406 24H27.75v-4.5c0-1.9.93-3.75 3.911-3.75h3.026V9.844s-2.747-.469-5.372-.469c-5.482 0-9.065 3.323-9.065 9.337V24h-6.094v6.938h6.094v16.77a24.174 24.174 0 007.5 0v-16.77h5.592z"/>
                        </svg>
                        Facebook
                    </a>
                </div>
            </div>

            <div class="auth-footer">
                <p>
                    ยังไม่มีบัญชี? 
                    <a href="register.php">สมัครสมาชิกที่นี่</a>
                </p>
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