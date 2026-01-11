<?php
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


session_start();
require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/includes/Database.php';
require_once PROJECT_ROOT . '/includes/helpers.php';

// ตรวจสอบว่าล็อกอินหรือยัง (ถ้าจำเป็น)
// requireLogin();

// PHP Logic ของหน้านี้
// ดึงข้อมูล, ประมวลผล form, etc.

// ตั้งค่า page title
$page_title = 'ชื่อหน้า - ' . SITE_NAME;

// Include header
include './includes/header.php';
?>

    <!-- เนื้อหาของหน้า -->
    <div class="container" style="margin: 3rem auto;">
        <h1>ชื่อหน้า</h1>
        <p>เนื้อหาของหน้า...</p>
    </div>

<?php include './includes/footer.php'; ?>