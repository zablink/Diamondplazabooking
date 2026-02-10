<?php
// Standard logout
require_once __DIR__ . '/includes/init.php';

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Handle Google Logout BEFORE destroying session
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
        
        // Clear all session data
        $_SESSION = array();
        
        // If you want to kill the session, also delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        header('Location: ' . $googleLogoutUrl);
        exit;
    }
}

// Standard logout process
$_SESSION = array(); // Clear all session variables

// If you want to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
header('Location: index.php');
exit;