<?php
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

// สร้าง Database class instance (แบบใหม่ - Singleton Pattern)
$database = Database::getInstance();
$db_conn = $database->getConnection();

// Alias สำหรับโค้ดเก่า
$db = $database;
$conn = $db_conn;