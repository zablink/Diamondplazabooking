<?php
/**
 * Admin Login - Redirect to Main Login
 * 
 * หน้านี้จะ redirect ไปยังหน้า login หลักที่ root
 * เพื่อป้องกันความสับสน และใช้ระบบ login เดียว
 */

// Redirect to main login page
header('Location: ../login.php');
exit;