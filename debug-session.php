<?php
/**
 * Session Debug Tool
 * ใช้ตรวจสอบว่า session ทำงานหรือไม่
 * 
 * วิธีใช้: เข้า https://diamondplazasurat.com/booking/debug-session.php
 * 
 * ⚠️ อย่าลืมลบไฟล์นี้หลังแก้ปัญหาเสร็จ!
 */

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

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug Tool</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            margin-top: 0;
            color: #667eea;
        }
        .info {
            margin: 5px 0;
            padding: 5px 10px;
            background: white;
            border-radius: 3px;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #000;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Session Debug Tool</h1>
        
        <div class="section">
            <h2>📊 Session Status</h2>
            <div class="info">
                <span class="label">Session Started:</span>
                <span class="value <?php echo (session_status() === PHP_SESSION_ACTIVE) ? 'success' : 'error'; ?>">
                    <?php echo (session_status() === PHP_SESSION_ACTIVE) ? '✓ YES' : '✗ NO'; ?>
                </span>
            </div>
            <div class="info">
                <span class="label">Session ID:</span>
                <span class="value"><?php echo session_id(); ?></span>
            </div>
            <div class="info">
                <span class="label">Session Name:</span>
                <span class="value"><?php echo session_name(); ?></span>
            </div>
        </div>
        
        <div class="section">
            <h2>🍪 Cookie Configuration</h2>
            <div class="info">
                <span class="label">Cookie Path:</span>
                <span class="value"><?php echo ini_get('session.cookie_path'); ?></span>
            </div>
            <div class="info">
                <span class="label">Cookie Domain:</span>
                <span class="value"><?php echo ini_get('session.cookie_domain') ?: '(not set)'; ?></span>
            </div>
            <div class="info">
                <span class="label">Cookie Lifetime:</span>
                <span class="value"><?php echo ini_get('session.cookie_lifetime'); ?> seconds</span>
            </div>
            <div class="info">
                <span class="label">Cookie Secure:</span>
                <span class="value"><?php echo ini_get('session.cookie_secure') ? 'YES (HTTPS only)' : 'NO (HTTP + HTTPS)'; ?></span>
            </div>
            <div class="info">
                <span class="label">Cookie HttpOnly:</span>
                <span class="value"><?php echo ini_get('session.cookie_httponly') ? 'YES' : 'NO'; ?></span>
            </div>
        </div>
        
        <div class="section">
            <h2>👤 Session Data</h2>
            <?php if (empty($_SESSION)): ?>
                <div class="info warning">⚠️ Session is empty!</div>
            <?php else: ?>
                <div class="info">
                    <span class="label">Logged In:</span>
                    <span class="value <?php echo isset($_SESSION['user_id']) ? 'success' : 'error'; ?>">
                        <?php echo isset($_SESSION['user_id']) ? '✓ YES' : '✗ NO'; ?>
                    </span>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="info">
                        <span class="label">User ID:</span>
                        <span class="value"><?php echo $_SESSION['user_id']; ?></span>
                    </div>
                    <div class="info">
                        <span class="label">Name:</span>
                        <span class="value"><?php echo ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''); ?></span>
                    </div>
                    <div class="info">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo $_SESSION['email'] ?? ''; ?></span>
                    </div>
                    <div class="info">
                        <span class="label">Role:</span>
                        <span class="value"><?php echo $_SESSION['role'] ?? ''; ?></span>
                    </div>
                    <div class="info">
                        <span class="label">Admin ID:</span>
                        <span class="value"><?php echo isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : '(not set)'; ?></span>
                    </div>
                    <div class="info">
                        <span class="label">Login Method:</span>
                        <span class="value"><?php echo $_SESSION['login_method'] ?? '(not set)'; ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>📦 Full Session Array</h2>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="section">
            <h2>📍 Current Request Info</h2>
            <div class="info">
                <span class="label">Current URL:</span>
                <span class="value"><?php echo $_SERVER['REQUEST_URI']; ?></span>
            </div>
            <div class="info">
                <span class="label">Script Path:</span>
                <span class="value"><?php echo $_SERVER['SCRIPT_NAME']; ?></span>
            </div>
            <div class="info">
                <span class="label">HTTPS:</span>
                <span class="value"><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'YES' : 'NO'; ?></span>
            </div>
        </div>
        
        <div class="section">
            <h2>⚙️ Actions</h2>
            <p><a href="?clear_session=1" style="color: #e74c3c; text-decoration: none; font-weight: bold;">🗑️ Clear Session (Logout)</a></p>
            <?php
            if (isset($_GET['clear_session'])) {
                session_destroy();
                echo '<p class="success">✓ Session cleared! Reload the page.</p>';
            }
            ?>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>⚠️ Important:</strong> Delete this file after fixing the session issue!
        </div>
    </div>
</body>
</html>