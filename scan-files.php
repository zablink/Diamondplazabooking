<?php
/**
 * PHP File Scanner - ตรวจสอบและแสดงรายงานการใช้ require_once
 * 
 * สแกนไฟล์ PHP ทั้งหมดและแสดงว่าต้องแก้ไขอย่างไร
 * แสดงผลทีละไฟล์แบบ real-time
 */

// ตั้งค่า
set_time_limit(300); // 5 นาที
ini_set('max_execution_time', 300);

// กำหนด root directory (ปรับตามต้องการ)
$rootDir = __DIR__;

// Pattern ที่ต้องการค้นหา
$patterns = [
    'old_absolute' => "/require_once\s+['\"]\/booking\/.*?['\"];/",
    'old_relative' => "/require_once\s+__DIR__\s*\.\s*['\"]\/.+?init\.php['\"];/",
    'dirname_style' => "/require_once\s+dirname\(__DIR__\).*?init\.php['\"];/",
    'auto_find' => "/\\\$projectRoot\s*=\s*__DIR__;.*?while.*?includes\/init\.php/s"
];

// โฟลเดอร์ที่ต้องข้าม
$skipDirs = ['vendor', 'node_modules', 'cache', 'logs', '.git', 'images', 'uploads', 'assets'];

// ตัวแปรสำหรับเก็บผลลัพธ์
$results = [];
$totalFiles = 0;
$needsFix = 0;
$alreadyFixed = 0;

/**
 * ฟังก์ชันสแกนไฟล์
 */
function scanFiles($dir, $rootDir, $skipDirs, $patterns, &$results, &$totalFiles, &$needsFix, &$alreadyFixed) {
    if (!is_dir($dir)) return;
    
    $items = @scandir($dir);
    if ($items === false) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        
        // ข้ามโฟลเดอร์ที่ไม่ต้องการ
        if (is_dir($path)) {
            $basename = basename($path);
            if (in_array($basename, $skipDirs)) continue;
            
            // สแกน subfolder
            scanFiles($path, $rootDir, $skipDirs, $patterns, $results, $totalFiles, $needsFix, $alreadyFixed);
            continue;
        }
        
        // ตรวจสอบเฉพาะไฟล์ .php
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;
        
        $totalFiles++;
        
        // อ่านเนื้อหาไฟล์
        $content = @file_get_contents($path);
        if ($content === false) continue;
        
        // คำนวณระดับ folder
        $relativePath = str_replace($rootDir . '/', '', $path);
        $depth = substr_count($relativePath, '/');
        
        // ตรวจสอบ pattern
        $status = 'unknown';
        $currentCode = '';
        $matches = [];
        
        // ตรวจหา auto-find snippet
        if (preg_match($patterns['auto_find'], $content, $matches)) {
            $status = 'fixed';
            $currentCode = trim($matches[0]);
            $alreadyFixed++;
        }
        // ตรวจหา old absolute path
        elseif (preg_match($patterns['old_absolute'], $content, $matches)) {
            $status = 'needs_fix';
            $currentCode = trim($matches[0]);
            $needsFix++;
        }
        // ตรวจหา old relative path
        elseif (preg_match($patterns['old_relative'], $content, $matches)) {
            $status = 'needs_fix';
            $currentCode = trim($matches[0]);
            $needsFix++;
        }
        // ตรวจหา dirname style
        elseif (preg_match($patterns['dirname_style'], $content, $matches)) {
            $status = 'manual_check';
            $currentCode = trim($matches[0]);
        }
        // ไม่เจอ require_once init.php
        else {
            $status = 'no_init';
        }
        
        // คำนวณโค้ดที่แนะนำ
        $recommendedCode = generateRecommendedCode($depth);
        
        // เก็บผลลัพธ์
        $results[] = [
            'path' => $path,
            'relativePath' => $relativePath,
            'depth' => $depth,
            'status' => $status,
            'currentCode' => $currentCode,
            'recommendedCode' => $recommendedCode,
            'fileSize' => filesize($path),
            'modified' => filemtime($path)
        ];
    }
}

/**
 * สร้างโค้ดที่แนะนำตามระดับ folder
 */
function generateRecommendedCode($depth) {
    return <<<'PHP'
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
PHP;
}

/**
 * สร้าง badge สถานะ
 */
function getStatusBadge($status) {
    $badges = [
        'fixed' => '<span class="badge badge-success">✅ ใช้ Auto-Find แล้ว</span>',
        'needs_fix' => '<span class="badge badge-danger">❌ ต้องแก้ไข</span>',
        'manual_check' => '<span class="badge badge-warning">⚠️ ตรวจสอบด้วยตนเอง</span>',
        'no_init' => '<span class="badge badge-info">ℹ️ ไม่มี init</span>',
        'unknown' => '<span class="badge badge-secondary">❓ ไม่ทราบ</span>'
    ];
    
    return $badges[$status] ?? $badges['unknown'];
}

// เริ่มสแกน
scanFiles($rootDir, $rootDir, $skipDirs, $patterns, $results, $totalFiles, $needsFix, $alreadyFixed);

// จัดเรียง
usort($results, function($a, $b) {
    // เรียงตาม status (needs_fix ก่อน)
    $statusOrder = ['needs_fix' => 1, 'manual_check' => 2, 'no_init' => 3, 'fixed' => 4];
    $aOrder = $statusOrder[$a['status']] ?? 5;
    $bOrder = $statusOrder[$b['status']] ?? 5;
    
    if ($aOrder !== $bOrder) {
        return $aOrder - $bOrder;
    }
    
    // ถ้า status เหมือนกัน เรียงตาม path
    return strcmp($a['relativePath'], $b['relativePath']);
});

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP File Scanner - ตรวจสอบการใช้ require_once</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.95em;
        }
        
        .stat-card.total .number { color: #2196F3; }
        .stat-card.needs-fix .number { color: #f44336; }
        .stat-card.fixed .number { color: #4CAF50; }
        .stat-card.percentage .number { color: #FF9800; }
        
        .filters {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95em;
        }
        
        .filter-group button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .filter-group button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .file-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .file-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .file-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .file-path {
            font-family: 'Courier New', monospace;
            color: #667eea;
            font-weight: bold;
            font-size: 1.05em;
            flex: 1;
        }
        
        .file-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4CAF50;
        }
        
        .badge-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
        }
        
        .badge-warning {
            background: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ff9800;
        }
        
        .badge-info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #2196F3;
        }
        
        .badge-secondary {
            background: #f5f5f5;
            color: #616161;
            border: 1px solid #9e9e9e;
        }
        
        .depth-badge {
            background: #e1bee7;
            color: #6a1b9a;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .file-body {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .file-card.expanded .file-body {
            max-height: 2000px;
            padding: 20px;
        }
        
        .code-section {
            margin-bottom: 20px;
        }
        
        .code-section h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .code-block {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            position: relative;
        }
        
        .code-block.current {
            background: #fff3e0;
            border-color: #ff9800;
        }
        
        .code-block.recommended {
            background: #e8f5e9;
            border-color: #4CAF50;
        }
        
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85em;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: #5568d3;
        }
        
        .copy-btn.copied {
            background: #4CAF50;
        }
        
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .toggle-icon {
            transition: transform 0.3s;
        }
        
        .file-card.expanded .toggle-icon {
            transform: rotate(180deg);
        }
        
        .no-files {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-files .icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
        
        .no-files h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔍 PHP File Scanner</h1>
            <p>ตรวจสอบการใช้ require_once ในไฟล์ PHP ทั้งหมด</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card total">
                <div class="number"><?php echo $totalFiles; ?></div>
                <div class="label">ไฟล์ทั้งหมด</div>
            </div>
            <div class="stat-card needs-fix">
                <div class="number"><?php echo $needsFix; ?></div>
                <div class="label">ต้องแก้ไข</div>
            </div>
            <div class="stat-card fixed">
                <div class="number"><?php echo $alreadyFixed; ?></div>
                <div class="label">แก้ไขแล้ว</div>
            </div>
            <div class="stat-card percentage">
                <div class="number">
                    <?php 
                    $percentage = $totalFiles > 0 ? round(($alreadyFixed / $totalFiles) * 100) : 0;
                    echo $percentage . '%';
                    ?>
                </div>
                <div class="label">ความสำเร็จ</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label>กรองตาม:</label>
                <select id="statusFilter">
                    <option value="all">ทั้งหมด</option>
                    <option value="needs_fix" selected>ต้องแก้ไข</option>
                    <option value="fixed">แก้ไขแล้ว</option>
                    <option value="manual_check">ตรวจสอบด้วยตนเอง</option>
                    <option value="no_init">ไม่มี init</option>
                </select>
                
                <input type="text" id="searchPath" placeholder="ค้นหา path...">
                
                <button onclick="applyFilters()">🔍 ค้นหา</button>
                <button onclick="expandAll()">📂 ขยายทั้งหมด</button>
                <button onclick="collapseAll()">📁 ย่อทั้งหมด</button>
            </div>
        </div>
        
        <!-- File List -->
        <div id="fileList">
            <?php if (empty($results)): ?>
                <div class="no-files">
                    <div class="icon">📭</div>
                    <h2>ไม่พบไฟล์</h2>
                    <p>ไม่พบไฟล์ PHP ในโฟลเดอร์นี้</p>
                </div>
            <?php else: ?>
                <?php foreach ($results as $index => $file): ?>
                    <div class="file-card" 
                         data-status="<?php echo $file['status']; ?>"
                         data-path="<?php echo htmlspecialchars($file['relativePath']); ?>">
                        
                        <div class="file-header" onclick="toggleCard(this)">
                            <div class="file-path">
                                <?php echo htmlspecialchars($file['relativePath']); ?>
                            </div>
                            <div class="file-meta">
                                <span class="depth-badge">Level <?php echo $file['depth']; ?></span>
                                <?php echo getStatusBadge($file['status']); ?>
                                <span class="toggle-icon">▼</span>
                            </div>
                        </div>
                        
                        <div class="file-body">
                            <?php if ($file['status'] === 'needs_fix' || $file['status'] === 'manual_check'): ?>
                                <div class="code-section">
                                    <h3>⚠️ โค้ดปัจจุบัน (ต้องแก้ไข):</h3>
                                    <div class="code-block current">
                                        <button class="copy-btn" onclick="copyCode(this, event)">คัดลอก</button>
                                        <pre><?php echo htmlspecialchars($file['currentCode']); ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($file['status'] === 'fixed'): ?>
                                <div class="code-section">
                                    <h3>✅ โค้ดปัจจุบัน (ถูกต้องแล้ว):</h3>
                                    <div class="code-block recommended">
                                        <pre><?php echo htmlspecialchars($file['currentCode']); ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($file['status'] !== 'fixed' && $file['status'] !== 'no_init'): ?>
                                <div class="code-section">
                                    <h3>✨ โค้ดที่แนะนำ (Auto-Find Snippet):</h3>
                                    <div class="code-block recommended">
                                        <button class="copy-btn" onclick="copyCode(this, event)">คัดลอก</button>
                                        <pre><?php echo htmlspecialchars($file['recommendedCode']); ?></pre>
                                    </div>
                                </div>
                                
                                <div class="action-buttons">
                                    <button class="btn-primary" onclick="copyCode(document.querySelectorAll('.file-card')[<?php echo $index; ?>].querySelector('.code-block.recommended .copy-btn'), event)">
                                        📋 คัดลอกโค้ดที่แนะนำ
                                    </button>
                                    <button class="btn-secondary" onclick="openFile('<?php echo htmlspecialchars($file['path']); ?>')">
                                        📝 เปิดไฟล์
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($file['status'] === 'no_init'): ?>
                                <div class="code-section">
                                    <h3>ℹ️ ไฟล์นี้ไม่มีการ require init.php</h3>
                                    <p>ถ้าต้องการเพิ่ม init.php ให้ใช้โค้ดนี้:</p>
                                    <div class="code-block recommended">
                                        <button class="copy-btn" onclick="copyCode(this, event)">คัดลอก</button>
                                        <pre><?php echo htmlspecialchars($file['recommendedCode']); ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle card expansion
        function toggleCard(header) {
            const card = header.parentElement;
            card.classList.toggle('expanded');
        }
        
        // Copy code to clipboard
        function copyCode(button, event) {
            event.stopPropagation();
            
            const codeBlock = button.parentElement;
            const code = codeBlock.querySelector('pre').textContent;
            
            navigator.clipboard.writeText(code).then(() => {
                const originalText = button.textContent;
                button.textContent = '✓ คัดลอกแล้ว!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            });
        }
        
        // Filter files
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const searchText = document.getElementById('searchPath').value.toLowerCase();
            const cards = document.querySelectorAll('.file-card');
            
            let visibleCount = 0;
            
            cards.forEach(card => {
                const status = card.dataset.status;
                const path = card.dataset.path.toLowerCase();
                
                const matchesStatus = statusFilter === 'all' || status === statusFilter;
                const matchesSearch = searchText === '' || path.includes(searchText);
                
                if (matchesStatus && matchesSearch) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            console.log(`Showing ${visibleCount} of ${cards.length} files`);
        }
        
        // Expand all cards
        function expandAll() {
            document.querySelectorAll('.file-card').forEach(card => {
                if (card.style.display !== 'none') {
                    card.classList.add('expanded');
                }
            });
        }
        
        // Collapse all cards
        function collapseAll() {
            document.querySelectorAll('.file-card').forEach(card => {
                card.classList.remove('expanded');
            });
        }
        
        // Open file (for development - won't work in browser)
        function openFile(path) {
            alert('Path: ' + path + '\n\nใช้ text editor เปิดไฟล์นี้เพื่อแก้ไข');
        }
        
        // Apply initial filter
        applyFilters();
        
        // Auto-expand first card that needs fix
        setTimeout(() => {
            const firstNeedsFix = document.querySelector('.file-card[data-status="needs_fix"]');
            if (firstNeedsFix) {
                firstNeedsFix.classList.add('expanded');
                firstNeedsFix.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 500);
    </script>
</body>
</html>