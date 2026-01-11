<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<?php
/**
 * Debug: ตรวจสอบว่าใช้ไฟล์ header.php ตัวไหน
 * วางไฟล์นี้ใน root แล้วเปิดดู
 */

echo "<h1>🔍 Debug: Header File Detection</h1>";
echo "<hr>";

// ตรวจสอบไฟล์ header ที่มี
echo "<h2>📁 ไฟล์ Header ที่พบ:</h2>";

$possible_headers = [
    './header.php',
    './includes/header.php',
    './public/header.php',
    './booking/header.php',
    './booking/includes/header.php',
    '../header.php',
    '../includes/header.php',
];

foreach ($possible_headers as $path) {
    if (file_exists($path)) {
        $full_path = realpath($path);
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        
        echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<strong>✅ พบไฟล์:</strong> <code>$path</code><br>";
        echo "<strong>📍 Path เต็ม:</strong> <code>$full_path</code><br>";
        echo "<strong>📦 ขนาด:</strong> " . number_format($size) . " bytes<br>";
        echo "<strong>🕐 แก้ไขล่าสุด:</strong> $modified<br>";
        
        // อ่านบรรทัดแรกๆ เพื่อดูว่าเป็นไฟล์ตัวไหน
        $content = file_get_contents($path);
        if (strpos($content, 'แก้ไขและเพิ่มฟีเจอร์') !== false || 
            strpos($content, 'improved avatar handling') !== false) {
            echo "<strong style='color: green;'>🎯 นี่คือไฟล์ใหม่ที่อัพเดทแล้ว!</strong><br>";
        } else {
            echo "<strong style='color: orange;'>⚠️ นี่เป็นไฟล์เก่า</strong><br>";
        }
        
        // แสดง 10 บรรทัดแรก
        $lines = explode("\n", $content);
        echo "<details><summary>ดูโค้ด 15 บรรทัดแรก</summary>";
        echo "<pre style='background: #f5f5f5; padding: 10px; overflow-x: auto;'>";
        echo htmlspecialchars(implode("\n", array_slice($lines, 0, 15)));
        echo "</pre></details>";
        
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>🔍 ตรวจสอบการเรียกใช้งาน:</h2>";

// สร้างไฟล์ทดสอบ
$test_files = [
    'index.php',
    'profile.php',
    'login.php',
    'rooms.php',
    'booking/index.php',
    'public/index.php',
];

echo "<p>ตรวจสอบไฟล์เหล่านี้ว่าเรียก header จากไหน:</p>";

foreach ($test_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // หา require/include header
        preg_match_all("/(require_once|require|include_once|include)\s*['\"]?([^'\";\)]+header[^'\";\)]*)['\"]?/i", $content, $matches);
        
        if (!empty($matches[2])) {
            echo "<div style='background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "<strong>📄 $file</strong> เรียก header จาก:<br>";
            foreach ($matches[2] as $header_path) {
                echo "<code style='background: #fff; padding: 3px 8px; border-radius: 3px;'>$header_path</code><br>";
            }
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<h2>💡 แนะนำ:</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
echo "<ol>";
echo "<li>ดูว่าไฟล์ไหนเป็น 'ไฟล์ใหม่' (มีเครื่องหมาย 🎯)</li>";
echo "<li>ดูว่าไฟล์หน้าเพจของคุณเรียก header จากไหน</li>";
echo "<li>แทนที่ไฟล์เก่าด้วยไฟล์ใหม่ หรือเปลี่ยน path ให้เรียกไฟล์ใหม่</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<h2>🛠️ Quick Fix:</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>วิธีที่ 1:</strong> คัดลอกไฟล์ใหม่ไปแทนที่ที่ตำแหน่งที่ถูกเรียกใช้</p>";
echo "<p><strong>วิธีที่ 2:</strong> แก้ไขไฟล์เพจให้เรียก header ใหม่</p>";
echo "<p><strong>วิธีที่ 3:</strong> เอาโค้ดจากไฟล์ใหม่ไปใส่ในไฟล์เก่า</p>";
echo "</div>";

// แสดง session info
echo "<hr>";
echo "<h2>📊 Session Info (ถ้า login อยู่):</h2>";
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    echo "<pre style='background: #f5f5f5; padding: 15px;'>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p><strong>Avatar URL:</strong> ";
    if (isset($_SESSION['user_avatar'])) {
        echo $_SESSION['user_avatar'];
        echo "<br><img src='{$_SESSION['user_avatar']}' style='width: 50px; height: 50px; border-radius: 50%; margin-top: 10px;'>";
    } else if (isset($_SESSION['profile_picture'])) {
        echo $_SESSION['profile_picture'];
        echo "<br><img src='{$_SESSION['profile_picture']}' style='width: 50px; height: 50px; border-radius: 50%; margin-top: 10px;'>";
    } else {
        echo "ไม่มี";
    }
    echo "</p>";
} else {
    echo "<p style='color: orange;'>ไม่ได้ Login</p>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 { color: #667eea; }
    h2 { color: #333; margin-top: 20px; }
    code { 
        background: #f5f5f5; 
        padding: 2px 6px; 
        border-radius: 3px;
        font-family: monospace;
    }
</style>