<?php
/**
 * Logout Handler with Debug Logging
 * ตรวจสอบว่าใครเรียก logout.php และทำไม
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

// ============================================
// 🔍 DEBUG LOGGING - บันทึกทุกอย่างก่อน logout
// ============================================

$debugInfo = [
    'timestamp' => date('Y-m-d H:i:s'),
    'datetime' => date('c'),
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'http_referer' => $_SERVER['HTTP_REFERER'] ?? 'NO REFERER',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
    'user_agent' => $_SERVER['USER_AGENT'] ?? 'UNKNOWN',
    'query_string' => $_SERVER['QUERY_STRING'] ?? '',
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? [],
    'get_params' => $_GET ?? [],
    'post_params' => $_POST ?? [],
    'cookies' => $_COOKIE ?? []
];

// บันทึก debug log
$logMessage = "\n" . str_repeat('=', 80) . "\n";
$logMessage .= "🚪 LOGOUT.PHP CALLED - DEBUG LOG\n";
$logMessage .= str_repeat('=', 80) . "\n";
$logMessage .= "Timestamp: " . $debugInfo['timestamp'] . "\n";
$logMessage .= "Request Method: " . $debugInfo['request_method'] . "\n";
$logMessage .= "Request URI: " . $debugInfo['request_uri'] . "\n";
$logMessage .= "⭐ HTTP Referer: " . $debugInfo['http_referer'] . "\n";
$logMessage .= "Remote IP: " . $debugInfo['remote_addr'] . "\n";
$logMessage .= "User Agent: " . substr($debugInfo['user_agent'], 0, 100) . "\n";
$logMessage .= "\nSession Info:\n";
$logMessage .= "  Session ID: " . $debugInfo['session_id'] . "\n";
$logMessage .= "  Session Status: " . $debugInfo['session_status'] . "\n";

if (!empty($debugInfo['session_data'])) {
    $logMessage .= "\nSession Data:\n";
    foreach ($debugInfo['session_data'] as $key => $value) {
        if (is_array($value)) {
            $logMessage .= "  $key: " . json_encode($value) . "\n";
        } else {
            $logMessage .= "  $key: $value\n";
        }
    }
} else {
    $logMessage .= "  (Session is empty)\n";
}

if (!empty($debugInfo['get_params'])) {
    $logMessage .= "\nGET Parameters:\n";
    foreach ($debugInfo['get_params'] as $key => $value) {
        $logMessage .= "  $key: $value\n";
    }
}

if (!empty($debugInfo['post_params'])) {
    $logMessage .= "\nPOST Parameters:\n";
    foreach ($debugInfo['post_params'] as $key => $value) {
        $logMessage .= "  $key: $value\n";
    }
}

// บันทึก Stack Trace เพื่อหาว่าใครเรียก
$logMessage .= "\n📍 Call Stack:\n";
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
foreach ($backtrace as $i => $trace) {
    $file = $trace['file'] ?? 'unknown';
    $line = $trace['line'] ?? 'unknown';
    $function = $trace['function'] ?? 'unknown';
    $logMessage .= "  #$i $file:$line - $function()\n";
}

$logMessage .= str_repeat('=', 80) . "\n";

// บันทึกลง error log
error_log($logMessage);

// บันทึกลงไฟล์แยก (สำหรับ debug)
$debugFile = PROJECT_ROOT . '/logs/logout-debug.log';
$logDir = dirname($debugFile);
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}
@file_put_contents($debugFile, $logMessage, FILE_APPEND);

// ============================================
// 🛑 STOP HERE FOR DEBUGGING
// ============================================

// ⚠️ แสดงข้อมูล debug แทนการ logout จริง
if (isset($_GET['debug']) || DEBUG_MODE) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout Debug</title>
        <style>
            body {
                font-family: 'Courier New', monospace;
                padding: 20px;
                background: #1e1e1e;
                color: #d4d4d4;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: #252526;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            }
            h1 {
                color: #4ec9b0;
                border-bottom: 2px solid #4ec9b0;
                padding-bottom: 10px;
            }
            h2 {
                color: #dcdcaa;
                margin-top: 30px;
            }
            .alert {
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
                border-left: 4px solid;
            }
            .alert-warning {
                background: #3c2f00;
                border-color: #d19a66;
                color: #d19a66;
            }
            .alert-danger {
                background: #3c0000;
                border-color: #f48771;
                color: #f48771;
            }
            .alert-info {
                background: #003c3c;
                border-color: #4ec9b0;
                color: #4ec9b0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                background: #1e1e1e;
            }
            th, td {
                padding: 10px;
                text-align: left;
                border: 1px solid #3e3e42;
            }
            th {
                background: #2d2d30;
                color: #4ec9b0;
                font-weight: bold;
            }
            tr:hover {
                background: #2a2a2d;
            }
            .highlight {
                color: #ce9178;
                font-weight: bold;
            }
            pre {
                background: #1e1e1e;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
                border: 1px solid #3e3e42;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background: #0e639c;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 5px;
            }
            .button:hover {
                background: #1177bb;
            }
            .button-danger {
                background: #c5004a;
            }
            .button-danger:hover {
                background: #e81050;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Logout Debug Information</h1>
            
            <div class="alert alert-warning">
                <strong>⚠️ DEBUG MODE:</strong> Logout has been intercepted for debugging.<br>
                This page shows who called logout.php and why.
            </div>
            
            <h2>📍 HTTP Referer (Who Called This Page)</h2>
            <div class="alert alert-info">
                <strong>Referer:</strong> <span class="highlight"><?= htmlspecialchars($debugInfo['http_referer']) ?></span>
            </div>
            
            <?php if ($debugInfo['http_referer'] === 'NO REFERER'): ?>
                <div class="alert alert-danger">
                    ❌ No HTTP Referer found!<br>
                    This could mean:
                    <ul>
                        <li>Direct browser navigation to logout.php</li>
                        <li>Bookmark or link clicked</li>
                        <li>JavaScript redirect without referer</li>
                        <li>Browser privacy settings blocking referer</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    ✓ HTTP Referer found: The page that called logout.php is shown above.
                </div>
            <?php endif; ?>
            
            <h2>🌐 Request Information</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Request Method</td>
                    <td><?= htmlspecialchars($debugInfo['request_method']) ?></td>
                </tr>
                <tr>
                    <td>Request URI</td>
                    <td><?= htmlspecialchars($debugInfo['request_uri']) ?></td>
                </tr>
                <tr>
                    <td>Query String</td>
                    <td><?= htmlspecialchars($debugInfo['query_string'] ?: '(empty)') ?></td>
                </tr>
                <tr>
                    <td>Remote IP</td>
                    <td><?= htmlspecialchars($debugInfo['remote_addr']) ?></td>
                </tr>
                <tr>
                    <td>Timestamp</td>
                    <td><?= htmlspecialchars($debugInfo['timestamp']) ?></td>
                </tr>
            </table>
            
            <h2>🔐 Session Information</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Session ID</td>
                    <td><?= htmlspecialchars($debugInfo['session_id']) ?></td>
                </tr>
                <tr>
                    <td>Session Status</td>
                    <td><?= $debugInfo['session_status'] === PHP_SESSION_ACTIVE ? 'ACTIVE ✓' : 'INACTIVE ✗' ?></td>
                </tr>
            </table>
            
            <?php if (!empty($debugInfo['session_data'])): ?>
                <h3>Session Data:</h3>
                <pre><?= htmlspecialchars(print_r($debugInfo['session_data'], true)) ?></pre>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ Session is empty (already logged out?)
                </div>
            <?php endif; ?>
            
            <?php if (!empty($debugInfo['get_params'])): ?>
                <h2>📥 GET Parameters</h2>
                <pre><?= htmlspecialchars(print_r($debugInfo['get_params'], true)) ?></pre>
            <?php endif; ?>
            
            <?php if (!empty($debugInfo['post_params'])): ?>
                <h2>📬 POST Parameters</h2>
                <pre><?= htmlspecialchars(print_r($debugInfo['post_params'], true)) ?></pre>
            <?php endif; ?>
            
            <h2>🔎 User Agent</h2>
            <pre><?= htmlspecialchars($debugInfo['user_agent']) ?></pre>
            
            <h2>📊 Call Stack</h2>
            <pre><?php
            foreach ($backtrace as $i => $trace) {
                $file = $trace['file'] ?? 'unknown';
                $line = $trace['line'] ?? 'unknown';
                $function = $trace['function'] ?? 'unknown';
                echo "#$i $file:$line - $function()\n";
            }
            ?></pre>
            
            <h2>⚙️ Actions</h2>
            <a href="?force_logout=1" class="button button-danger">🚪 Force Logout Now</a>
            <a href="../index.php" class="button">🏠 Go to Home</a>
            <a href="../admin/dashboard.php" class="button">📊 Go to Dashboard</a>
            
            <div style="margin-top: 30px; padding: 15px; background: #3c2f00; border-left: 4px solid #d19a66; border-radius: 5px; color: #d19a66;">
                <strong>📝 Note:</strong> Debug information has been logged to:<br>
                <code><?= htmlspecialchars($debugFile) ?></code><br>
                And to the main error log.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// ถ้าไม่ใช่ debug mode ให้ logout จริง
// ============================================

if (isset($_GET['force_logout']) && $_GET['force_logout'] == '1') {
    // Forced logout from debug page
    error_log("🔴 FORCED LOGOUT from debug page");
}

// Handle Google Logout
if (isset($_SESSION['login_method']) && $_SESSION['login_method'] === 'google') {
    if (isset($_SESSION['social_access_token']) && !empty($_SESSION['social_access_token'])) {
        try {
            $accessToken = $_SESSION['social_access_token'];
            
            // Revoke Google token
            $revokeUrl = 'https://oauth2.googleapis.com/revoke?token=' . $accessToken;
            
            $ch = curl_init($revokeUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_exec($ch);
            curl_close($ch);
            
            error_log("Google token revoked for user: " . ($_SESSION['email'] ?? 'unknown'));
        } catch (Exception $e) {
            error_log("Error revoking Google token: " . $e->getMessage());
        }
    }
    
    if (defined('GOOGLE_LOGOUT_REDIRECT') && GOOGLE_LOGOUT_REDIRECT === true) {
        $googleLogoutUrl = 'https://accounts.google.com/Logout';
        
        session_unset();
        session_destroy();
        
        header('Location: ' . $googleLogoutUrl);
        exit;
    }
}

// Standard logout
session_unset();
session_destroy();

// Redirect to login page
header('Location: /booking/login.php?logged_out=1');
exit;