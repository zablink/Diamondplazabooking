<?php
/**
 * Social Authentication Class
 * Handles Google, Facebook, and Apple Sign In
 */

class SocialAuth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Google OAuth - Generate Login URL
     */
    public function getGoogleAuthUrl() {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Google OAuth - Handle Callback
     */
    public function handleGoogleCallback($code) {
        try {
            // Exchange code for access token
            $tokenUrl = 'https://oauth2.googleapis.com/token';
            $tokenData = [
                'code' => $code,
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri' => GOOGLE_REDIRECT_URI,
                'grant_type' => 'authorization_code'
            ];
            
            $ch = curl_init($tokenUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
            $response = curl_exec($ch);
            curl_close($ch);
            
            $tokenResponse = json_decode($response, true);
            
            if (!isset($tokenResponse['access_token'])) {
                return ['success' => false, 'message' => 'Failed to get access token'];
            }
            
            // Get user info
            $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
            $ch = curl_init($userInfoUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $tokenResponse['access_token']
            ]);
            $userInfoResponse = curl_exec($ch);
            curl_close($ch);
            
            $userInfo = json_decode($userInfoResponse, true);
            
            if (!isset($userInfo['id'])) {
                return ['success' => false, 'message' => 'Failed to get user info'];
            }
            
            // Login or register user
            return $this->socialLoginOrRegister(
                'google',
                $userInfo['id'],
                $userInfo['email'],
                $userInfo['given_name'] ?? 'User',
                $userInfo['family_name'] ?? '',
                $userInfo['picture'] ?? null
            );
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Google login error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Facebook OAuth - Generate Login URL
     */
    public function getFacebookAuthUrl() {
        $params = [
            'client_id' => FACEBOOK_APP_ID,
            'redirect_uri' => FACEBOOK_REDIRECT_URI,
            'scope' => 'email,public_profile',
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16))
        ];
        
        $_SESSION['facebook_state'] = $params['state'];
        
        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }
    
    /**
     * Facebook OAuth - Handle Callback
     */
    public function handleFacebookCallback($code, $state) {
        try {
            // Verify state
            if (!isset($_SESSION['facebook_state']) || $state !== $_SESSION['facebook_state']) {
                return ['success' => false, 'message' => 'Invalid state parameter'];
            }
            
            unset($_SESSION['facebook_state']);
            
            // Exchange code for access token
            $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
            $tokenParams = [
                'client_id' => FACEBOOK_APP_ID,
                'client_secret' => FACEBOOK_APP_SECRET,
                'redirect_uri' => FACEBOOK_REDIRECT_URI,
                'code' => $code
            ];
            
            $ch = curl_init($tokenUrl . '?' . http_build_query($tokenParams));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $tokenResponse = json_decode($response, true);
            
            if (!isset($tokenResponse['access_token'])) {
                return ['success' => false, 'message' => 'Failed to get access token'];
            }
            
            // Get user info
            $userInfoUrl = 'https://graph.facebook.com/me';
            $userInfoParams = [
                'fields' => 'id,email,first_name,last_name,picture.type(large)',
                'access_token' => $tokenResponse['access_token']
            ];
            
            $ch = curl_init($userInfoUrl . '?' . http_build_query($userInfoParams));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $userInfoResponse = curl_exec($ch);
            curl_close($ch);
            
            $userInfo = json_decode($userInfoResponse, true);
            
            if (!isset($userInfo['id'])) {
                return ['success' => false, 'message' => 'Failed to get user info'];
            }
            
            // Login or register user
            return $this->socialLoginOrRegister(
                'facebook',
                $userInfo['id'],
                $userInfo['email'] ?? null,
                $userInfo['first_name'] ?? 'User',
                $userInfo['last_name'] ?? '',
                $userInfo['picture']['data']['url'] ?? null
            );
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Facebook login error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login or Register user with social account
     */
    private function socialLoginOrRegister($provider, $socialId, $email, $firstName, $lastName, $profilePicture) {
        try {
            // Check if user exists with this social account
            $sql = "SELECT * FROM users WHERE auth_provider = :provider AND social_id = :social_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'provider' => $provider,
                'social_id' => $socialId
            ]);
            
            $user = $stmt->fetch();
            
            // If user exists, login
            if ($user) {
                // Update profile picture if changed
                if ($profilePicture && $user['profile_picture'] !== $profilePicture) {
                    $updateSql = "UPDATE users SET profile_picture = :picture WHERE user_id = :user_id";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([
                        'picture' => $profilePicture,
                        'user_id' => $user['user_id']
                    ]);
                }
                
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_picture'] = $profilePicture ?? $user['profile_picture'];
                
                return ['success' => true, 'message' => 'เข้าสู่ระบบสำเร็จ'];
            }
            
            // Check if email already exists (linked to another account)
            if ($email) {
                $emailSql = "SELECT * FROM users WHERE email = :email";
                $emailStmt = $this->db->prepare($emailSql);
                $emailStmt->execute(['email' => $email]);
                $existingUser = $emailStmt->fetch();
                
                if ($existingUser) {
                    // Link social account to existing user
                    $linkSql = "UPDATE users 
                                SET auth_provider = :provider, 
                                    social_id = :social_id, 
                                    profile_picture = :picture,
                                    email_verified = TRUE
                                WHERE user_id = :user_id";
                    $linkStmt = $this->db->prepare($linkSql);
                    $linkStmt->execute([
                        'provider' => $provider,
                        'social_id' => $socialId,
                        'picture' => $profilePicture,
                        'user_id' => $existingUser['user_id']
                    ]);
                    
                    // Set session
                    $_SESSION['user_id'] = $existingUser['user_id'];
                    $_SESSION['email'] = $existingUser['email'];
                    $_SESSION['first_name'] = $existingUser['first_name'];
                    $_SESSION['last_name'] = $existingUser['last_name'];
                    $_SESSION['role'] = $existingUser['role'];
                    $_SESSION['profile_picture'] = $profilePicture;
                    
                    return ['success' => true, 'message' => 'เชื่อมต่อบัญชีสำเร็จ'];
                }
            }
            
            // Create new user
            if (!$email) {
                // Generate temporary email if not provided (Facebook can skip email)
                $email = $provider . '_' . $socialId . '@social.local';
            }
            
            $insertSql = "INSERT INTO users 
                         (email, first_name, last_name, auth_provider, social_id, profile_picture, email_verified) 
                         VALUES 
                         (:email, :first_name, :last_name, :provider, :social_id, :picture, TRUE)";
            
            $insertStmt = $this->db->prepare($insertSql);
            $result = $insertStmt->execute([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'provider' => $provider,
                'social_id' => $socialId,
                'picture' => $profilePicture
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['role'] = 'customer';
                $_SESSION['profile_picture'] = $profilePicture;
                
                return ['success' => true, 'message' => 'สมัครสมาชิกสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
