<?php
/**
 * Find Logout Calls - ค้นหาไฟล์ที่เรียก logout.php
 */

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

// ตรวจสอบว่าเป็น admin
if (!isAdmin()) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Logout Calls</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        .file-path {
            color: #667eea;
            font-weight: bold;
            font-size: 1.1em;
        }
        .line-number {
            display: inline-block;
            width: 60px;
            color: #999;
            font-family: 'Courier New', monospace;
        }
        .code-line {
            font-family: 'Courier New', monospace;
            padding: 5px 10px;
            background: #fff;
            margin: 5px 0;
            border-radius: 3px;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .alert-info {
            background: #e3f2fd;
            border-color: #2196f3;
            color: #1976d2;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .search-pattern {
            background: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .count {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Find Files That Call logout.php</h1>
        
        <div class="alert alert-info">
            <strong>📌 What This Tool Does:</strong><br>
            Searches all PHP files in your booking system to find references to logout.php
        </div>
        
        <h2>Search Patterns <span class="count"><?php
        $patterns = [
            'logout.php',
            'logout\.php',
            'header.*logout',
            'Location.*logout',
            'redirect.*logout',
            'window\.location.*logout',
            'href.*logout'
        ];
        echo count($patterns);
        ?></span></h2>
        
        <div class="search-pattern">
            <?php foreach ($patterns as $i => $pattern): ?>
                <?= ($i + 1) ?>. <?= htmlspecialchars($pattern) ?><br>
            <?php endforeach; ?>
        </div>
        
        <?php
        $searchDir = PROJECT_ROOT;
        $excludeDirs = ['vendor', 'node_modules', '.git', 'logs'];
        $results = [];
        
        // Function to search files
        function searchInFile($file, $patterns) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $matches = [];
            
            foreach ($lines as $lineNum => $line) {
                foreach ($patterns as $pattern) {
                    if (stripos($line, $pattern) !== false || preg_match('/' . $pattern . '/i', $line)) {
                        $matches[] = [
                            'line' => $lineNum + 1,
                            'content' => trim($line),
                            'pattern' => $pattern
                        ];
                        break; // เจอแล้วไม่ต้องค้นต่อในบรรทัดเดียวกัน
                    }
                }
            }
            
            return $matches;
        }
        
        // Function to scan directory recursively
        function scanDirectory($dir, $excludeDirs, $patterns, &$results) {
            if (!is_dir($dir)) {
                return;
            }
            
            $items = scandir($dir);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                
                $path = $dir . '/' . $item;
                
                // Skip excluded directories
                $skip = false;
                foreach ($excludeDirs as $excludeDir) {
                    if (strpos($path, '/' . $excludeDir . '/') !== false || 
                        strpos($path, '/' . $excludeDir) === strlen($path) - strlen($excludeDir) - 1) {
                        $skip = true;
                        break;
                    }
                }
                
                if ($skip) {
                    continue;
                }
                
                if (is_dir($path)) {
                    scanDirectory($path, $excludeDirs, $patterns, $results);
                } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $matches = searchInFile($path, $patterns);
                    if (!empty($matches)) {
                        $results[$path] = $matches;
                    }
                }
            }
        }
        
        // Start scanning
        echo '<h2>Scanning Files... <span class="count" id="file-count">0</span></h2>';
        echo '<div id="progress">Searching in: ' . htmlspecialchars($searchDir) . '</div>';
        
        scanDirectory($searchDir, $excludeDirs, $patterns, $results);
        
        echo '<h2>Results <span class="count">' . count($results) . ' files</span></h2>';
        
        if (empty($results)) {
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠️ No Results Found</strong><br>';
            echo 'No files found that reference logout.php<br>';
            echo 'This is unusual - there should be at least some navigation links.';
            echo '</div>';
        } else {
            // Sort by file path
            ksort($results);
            
            $totalMatches = 0;
            foreach ($results as $file => $matches) {
                $totalMatches += count($matches);
                
                $relativePath = str_replace(PROJECT_ROOT, '', $file);
                
                echo '<div class="result">';
                echo '<div class="file-path">📄 ' . htmlspecialchars($relativePath) . '</div>';
                echo '<div style="margin-left: 20px;">';
                
                foreach ($matches as $match) {
                    echo '<div class="code-line">';
                    echo '<span class="line-number">Line ' . $match['line'] . ':</span> ';
                    
                    // Highlight the matched pattern
                    $highlighted = $match['content'];
                    foreach ($patterns as $pattern) {
                        $highlighted = preg_replace(
                            '/(' . preg_quote($pattern, '/') . ')/i',
                            '<span class="highlight">$1</span>',
                            $highlighted
                        );
                    }
                    
                    echo $highlighted;
                    echo '</div>';
                }
                
                echo '</div>';
                echo '</div>';
            }
            
            echo '<div class="alert alert-info">';
            echo '<strong>📊 Summary:</strong><br>';
            echo 'Found <strong>' . $totalMatches . '</strong> references to logout in <strong>' . count($results) . '</strong> files.';
            echo '</div>';
        }
        ?>
        
        <h2>🎯 Common Causes of Automatic Logout</h2>
        <div class="alert alert-warning">
            <strong>Check these files specifically:</strong>
            <ul>
                <li><strong>header.php / nav.php</strong> - Navigation menus with logout links</li>
                <li><strong>dashboard.php</strong> - May have auto-redirect logic</li>
                <li><strong>init.php / auth-check.php</strong> - Session validation code</li>
                <li><strong>*.js files</strong> - JavaScript auto-logout timers (this tool doesn't check JS)</li>
                <li><strong>.htaccess</strong> - Apache redirect rules (this tool doesn't check htaccess)</li>
            </ul>
        </div>
        
        <h2>🔧 Next Steps</h2>
        <div class="alert alert-info">
            <ol>
                <li>Replace your current <code>logout.php</code> with the debug version</li>
                <li>Login again and observe what happens</li>
                <li>Check the HTTP Referer in the debug page</li>
                <li>Check <code>/booking/logs/logout-debug.log</code> for details</li>
                <li>Look at the files found above to see which one is calling logout</li>
            </ol>
        </div>
        
        <h2>📝 JavaScript Check</h2>
        <div class="alert alert-warning">
            <strong>⚠️ Important:</strong> This tool only checks PHP files!<br>
            If no suspicious PHP code is found, check JavaScript files for:
            <pre>window.location = "logout.php"
setTimeout(function() { location.href = "logout.php" }, ...)
window.location.href = "/booking/logout.php"</pre>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>⚠️ Important:</strong> Delete this file after debugging!
        </div>
    </div>
    
    <script>
        // Scroll to first result
        setTimeout(function() {
            const firstResult = document.querySelector('.result');
            if (firstResult) {
                firstResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    </script>
</body>
</html>