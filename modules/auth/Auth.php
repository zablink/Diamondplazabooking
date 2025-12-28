<?php
/**
 * Authentication Class
 * Handles user registration, login, and authentication
 */

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Register new user
     */
    public function register($email, $password, $firstName, $lastName, $phone = '') {
        try {
            // Check if email already exists
            $sql = "SELECT user_id FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'อีเมลนี้ถูกใช้งานแล้ว'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (email, password, first_name, last_name, phone) 
                    VALUES (:email, :password, :first_name, :last_name, :phone)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'email' => $email,
                'password' => $hashedPassword,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'สมัครสมาชิกสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                
                return ['success' => true, 'message' => 'เข้าสู่ระบบสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'ออกจากระบบสำเร็จ'];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $sql = "SELECT user_id, email, first_name, last_name, phone, role, created_at 
                    FROM users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $firstName, $lastName, $phone) {
        try {
            $sql = "UPDATE users 
                    SET first_name = :first_name, last_name = :last_name, phone = :phone 
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'user_id' => $userId
            ]);
            
            if ($result) {
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                return ['success' => true, 'message' => 'อัพเดทข้อมูลสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $sql = "SELECT password FROM users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'password' => $hashedPassword,
                'user_id' => $userId
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'];
            }
            
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    }
}
