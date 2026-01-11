<?php
/**
 * Check for Whitespace and BOM in PHP Files
 * ตรวจสอบไฟล์ที่มี whitespace หรือ BOM ก่อน <?php
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

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Whitespace & BOM</title>
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
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .file-issue {
            margin: 15px 0;
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 5px;
        }
        .file-path {
            font-weight: bold;
            color: #856404;
            font-size: 1.1em;
        }
        .issue-detail {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 3px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Check Whitespace & BOM in PHP Files</h1>
        
        <div class="alert alert-info">
            <strong>📌 What This Tool Checks:</strong><br>
            1. UTF-8 BOM (Byte Order Mark) at the beginning of files<br>
            2. Whitespace (spaces, newlines) before &lt;?php tag<br>
            3. Whitespace after ?&gt; closing tag<br>
            <br>
            These issues cause "Headers already sent" errors!
        </div>
        
        <?php
        $issues = [];
        $filesChecked = 0;
        
        // Files to check
        $filesToCheck = [
            PROJECT_ROOT . '/includes/init.php',
            PROJECT_ROOT . '/includes/Database.php',
            PROJECT_ROOT . '/config/config.php',
            PROJECT_ROOT . '/login.php',
            PROJECT_ROOT . '/admin/dashboard.php',
            PROJECT_ROOT . '/admin/index.php'
        ];
        
        foreach ($filesToCheck as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $filesChecked++;
            $content = file_get_contents($file);
            $relativePath = str_replace(PROJECT_ROOT, '', $file);
            
            $fileIssues = [];
            
            // Check for BOM
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $fileIssues[] = "UTF-8 BOM detected at start of file";
            }
            
            // Check for whitespace before <?php
            if (preg_match('/^(\s+)<\?php/s', $content, $matches)) {
                $whitespace = $matches[1];
                $wsType = [];
                if (strpos($whitespace, "\n") !== false) $wsType[] = "newlines";
                if (strpos($whitespace, " ") !== false) $wsType[] = "spaces";
                if (strpos($whitespace, "\t") !== false) $wsType[] = "tabs";
                
                $fileIssues[] = "Whitespace before &lt;?php: " . implode(", ", $wsType) . " (" . strlen($whitespace) . " chars)";
            }
            
            // Check for whitespace after ?>
            if (preg_match('/\?>\s+$/s', $content, $matches)) {
                $fileIssues[] = "Whitespace after ?&gt; closing tag";
            }
            
            // Check if file ends with ?>
            if (preg_match('/\?>[\s]*$/s', $content)) {
                $fileIssues[] = "File ends with ?&gt; closing tag (should be removed)";
            }
            
            if (!empty($fileIssues)) {
                $issues[$relativePath] = [
                    'path' => $file,
                    'issues' => $fileIssues,
                    'first_bytes' => substr($content, 0, 50)
                ];
            }
        }
        
        if (empty($issues)) {
            echo '<div class="alert alert-success">';
            echo '<strong>✓ No Issues Found!</strong><br>';
            echo 'Checked ' . $filesChecked . ' files. No whitespace or BOM issues detected.';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠️ Issues Found: ' . count($issues) . ' files</strong><br>';
            echo 'These files have whitespace or BOM issues that can cause "Headers already sent" errors.';
            echo '</div>';
            
            foreach ($issues as $relativePath => $data) {
                echo '<div class="file-issue">';
                echo '<div class="file-path">📄 ' . htmlspecialchars($relativePath) . '</div>';
                echo '<div style="margin-left: 20px;">';
                
                foreach ($data['issues'] as $issue) {
                    echo '<div style="margin: 5px 0; color: #856404;">• ' . htmlspecialchars($issue) . '</div>';
                }
                
                echo '<div class="issue-detail">';
                echo '<strong>First 50 bytes (hex):</strong><br>';
                echo bin2hex($data['first_bytes']);
                echo '</div>';
                
                echo '</div>';
                echo '</div>';
            }
            
            echo '<h2>🔧 How to Fix</h2>';
            echo '<div class="alert alert-info">';
            echo '<strong>For each affected file:</strong><br><br>';
            echo '1. <strong>Remove BOM:</strong> Open file in text editor, save as UTF-8 (without BOM)<br>';
            echo '2. <strong>Remove whitespace before &lt;?php:</strong> Make sure &lt;?php is the very first thing in the file<br>';
            echo '3. <strong>Remove ?&gt; closing tag:</strong> PHP files should NOT end with ?&gt;<br>';
            echo '4. <strong>Remove whitespace after ?&gt;:</strong> If you must keep ?&gt;, remove all whitespace after it<br>';
            echo '</div>';
        }
        ?>
        
        <h2>📋 Files Checked</h2>
        <table>
            <tr>
                <th>File</th>
                <th>Status</th>
            </tr>
            <?php foreach ($filesToCheck as $file): ?>
                <?php
                $relativePath = str_replace(PROJECT_ROOT, '', $file);
                $exists = file_exists($file);
                $hasIssue = isset($issues[$relativePath]);
                ?>
                <tr>
                    <td><?= htmlspecialchars($relativePath) ?></td>
                    <td>
                        <?php if (!$exists): ?>
                            <span style="color: #999;">Not found</span>
                        <?php elseif ($hasIssue): ?>
                            <span style="color: #dc3545; font-weight: bold;">⚠️ Has issues</span>
                        <?php else: ?>
                            <span style="color: #28a745; font-weight: bold;">✓ OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>⚠️ Important:</strong> Delete this file after checking!
        </div>
    </div>
</body>
</html>
