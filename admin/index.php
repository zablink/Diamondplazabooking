<?php
/**
 * Admin Index - Redirect to Dashboard
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

// ตรวจสอบว่า login เป็น admin หรือไม่
if (!isset($_SESSION['admin_id'])) {
    // ถ้ายังไม่ login หรือไม่ใช่ admin ให้ไปหน้า login
    header('Location: ../login.php');
    exit;
}

// ถ้า login เป็น admin แล้วให้ไปหน้า dashboard
header('Location: dashboard.php');
exit;