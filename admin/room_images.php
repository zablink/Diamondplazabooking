<?php
// /booking/admin/room_images.php
// จัดการรูปภาพของห้องพัก

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

require_once PROJECT_ROOT . '/config/config.php';
require_once PROJECT_ROOT . '/modules/admin/AdminClass.php';
require_once PROJECT_ROOT . '/includes/RoomImage.php';

// ตรวจสอบ admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin = new Admin();
$roomImage = new RoomImage();
$message = '';
$messageType = '';

// ดึง room_type_id จาก URL
$roomTypeId = $_GET['room_id'] ?? 0;

// ดึงข้อมูลห้องพัก
$roomType = $admin->getRoomTypeById($roomTypeId);

if (!$roomType) {
    header('Location: rooms.php');
    exit;
}

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'upload_single':
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $isFeatured = isset($_POST['set_featured']) ? true : false;
                    $result = $roomImage->uploadImage($roomTypeId, $_FILES['image'], $isFeatured);
                    
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                } else {
                    $message = 'กรุณาเลือกไฟล์รูปภาพ';
                    $messageType = 'error';
                }
                break;
                
            case 'upload_multiple':
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $result = $roomImage->uploadMultipleImages($roomTypeId, $_FILES['images']);
                    
                    $message = "อัปโหลดสำเร็จ {$result['uploaded']} ไฟล์";
                    if ($result['failed'] > 0) {
                        $message .= ", ล้มเหลว {$result['failed']} ไฟล์";
                    }
                    $messageType = $result['success'] ? 'success' : 'warning';
                } else {
                    $message = 'กรุณาเลือกไฟล์รูปภาพ';
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                $imageId = $_POST['image_id'] ?? 0;
                $result = $roomImage->deleteImage($imageId);
                
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'set_featured':
                $imageId = $_POST['image_id'] ?? 0;
                $result = $roomImage->setFeaturedImage($imageId);
                
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'update_order':
                // อัปเดตลำดับ (จะใช้ AJAX)
                $imageId = $_POST['image_id'] ?? 0;
                $order = $_POST['order'] ?? 0;
                $result = $roomImage->updateDisplayOrder($imageId, $order);
                
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode($result);
                    exit;
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// ดึงรูปภาพทั้งหมด
$images = $roomImage->getImages($roomTypeId);
$imageCount = count($images);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรูปภาพห้องพัก - <?= htmlspecialchars($roomType['room_type_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .gallery-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        
        .gallery-item-overlay button {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .gallery-item-overlay button:hover {
            transform: scale(1.1);
        }
        
        .btn-featured {
            background: #f39c12;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .featured-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            box-shadow: 0 2px 10px rgba(243, 156, 18, 0.4);
            z-index: 1;
        }
        
        .upload-area {
            border: 3px dashed #ddd;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: #e8f0ff;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
        }
        
        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 32px;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-card p {
            color: #666;
            margin: 0;
        }
        
        .no-images {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-images i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .tab {
            padding: 12px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <div>
                    <h1>
                        <i class="fas fa-images"></i> 
                        จัดการรูปภาพห้องพัก: <?= htmlspecialchars($roomType['room_type_name']) ?>
                    </h1>
                    <p>อัปโหลดและจัดการรูปภาพสำหรับห้องพักนี้</p>
                </div>
                <a href="rooms.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <i class="fas fa-images" style="font-size: 32px; color: #667eea;"></i>
                    <h3><?= $imageCount ?></h3>
                    <p>รูปภาพทั้งหมด</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-star" style="font-size: 32px; color: #f39c12;"></i>
                    <h3><?= count(array_filter($images, fn($img) => $img['is_featured'])) ?></h3>
                    <p>ภาพหน้าปก</p>
                </div>
            </div>
            
            <!-- Upload Tabs -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-cloud-upload-alt"></i> อัปโหลดรูปภาพ</h2>
                </div>
                <div class="card-body">
                    <div class="tabs">
                        <button class="tab active" onclick="switchTab('single')">
                            <i class="fas fa-image"></i> อัปโหลดทีละรูป
                        </button>
                        <button class="tab" onclick="switchTab('multiple')">
                            <i class="fas fa-images"></i> อัปโหลดหลายรูป
                        </button>
                    </div>
                    
                    <!-- Single Upload -->
                    <div id="single-upload" class="tab-content active">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_single">
                            
                            <div class="upload-area" id="dropZoneSingle">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h3>ลากและวางรูปภาพที่นี่</h3>
                                <p>หรือคลิกปุ่มด้านล่างเพื่อเลือกไฟล์</p>
                                <p style="color: #999; font-size: 14px; margin-top: 10px;">
                                    รองรับ JPG, PNG, WebP (สูงสุด 5MB)
                                </p>
                                
                                <div style="margin-top: 20px;">
                                    <div class="file-input-wrapper">
                                        <input type="file" 
                                               name="image" 
                                               id="singleFileInput" 
                                               accept="image/*"
                                               onchange="handleFileSelect(this)">
                                        <label for="singleFileInput" class="file-input-label">
                                            <i class="fas fa-folder-open"></i> เลือกรูปภาพ
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="singlePreview" style="margin-top: 20px;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="set_featured" value="1">
                                    <span>ตั้งเป็นภาพหน้าปก (Featured Image)</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> อัปโหลด
                            </button>
                        </form>
                    </div>
                    
                    <!-- Multiple Upload -->
                    <div id="multiple-upload" class="tab-content">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_multiple">
                            
                            <div class="upload-area" id="dropZoneMultiple">
                                <div class="upload-icon">
                                    <i class="fas fa-images"></i>
                                </div>
                                <h3>ลากและวางรูปภาพหลายรูปที่นี่</h3>
                                <p>หรือคลิกปุ่มด้านล่างเพื่อเลือกไฟล์</p>
                                <p style="color: #999; font-size: 14px; margin-top: 10px;">
                                    รองรับ JPG, PNG, WebP (สูงสุด 5MB ต่อไฟล์)
                                </p>
                                
                                <div style="margin-top: 20px;">
                                    <div class="file-input-wrapper">
                                        <input type="file" 
                                               name="images[]" 
                                               id="multipleFileInput" 
                                               accept="image/*"
                                               multiple
                                               onchange="handleMultipleFileSelect(this)">
                                        <label for="multipleFileInput" class="file-input-label">
                                            <i class="fas fa-folder-open"></i> เลือกหลายรูปภาพ
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="multiplePreview" style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> อัปโหลดทั้งหมด
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Gallery -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-th"></i> แกลเลอรี่ (<?= $imageCount ?> รูป)</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($images)): ?>
                        <div class="no-images">
                            <i class="fas fa-image"></i>
                            <h3>ยังไม่มีรูปภาพ</h3>
                            <p>เริ่มต้นโดยอัปโหลดรูปภาพด้านบน</p>
                        </div>
                    <?php else: ?>
                        <div class="gallery-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="gallery-item">
                                    <?php if ($image['is_featured']): ?>
                                        <div class="featured-badge">
                                            <i class="fas fa-star"></i> ภาพหน้าปก
                                        </div>
                                    <?php endif; ?>
                                    
                                    <img src="<?= SITE_URL . '/' . htmlspecialchars($image['image_path']) ?>" 
                                         alt="Room Image"
                                         loading="lazy">
                                    
                                    <div class="gallery-item-overlay">
                                        <?php if (!$image['is_featured']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="set_featured">
                                                <input type="hidden" name="image_id" value="<?= $image['image_id'] ?>">
                                                <button type="submit" class="btn-featured" title="ตั้งเป็นภาพหน้าปก">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('ต้องการลบรูปภาพนี้?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="image_id" value="<?= $image['image_id'] ?>">
                                            <button type="submit" class="btn-delete" title="ลบรูปภาพ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab + '-upload').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Single file preview
        function handleFileSelect(input) {
            const preview = document.getElementById('singlePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             style="max-width: 300px; max-height: 300px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                        <p style="margin-top: 10px; font-weight: 600;">${file.name}</p>
                    `;
                };
                
                reader.readAsDataURL(file);
            }
        }
        
        // Multiple files preview
        function handleMultipleFileSelect(input) {
            const preview = document.getElementById('multiplePreview');
            preview.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.style.cssText = 'text-align: center;';
                        div.innerHTML = `
                            <img src="${e.target.result}" 
                                 style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <p style="font-size: 11px; margin-top: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${file.name}</p>
                        `;
                        preview.appendChild(div);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        }
        
        // Drag and drop for single upload
        const dropZoneSingle = document.getElementById('dropZoneSingle');
        const singleFileInput = document.getElementById('singleFileInput');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZoneSingle.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZoneSingle.addEventListener(eventName, () => {
                dropZoneSingle.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZoneSingle.addEventListener(eventName, () => {
                dropZoneSingle.classList.remove('dragover');
            }, false);
        });
        
        dropZoneSingle.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            singleFileInput.files = files;
            handleFileSelect(singleFileInput);
        }
        
        // Drag and drop for multiple upload
        const dropZoneMultiple = document.getElementById('dropZoneMultiple');
        const multipleFileInput = document.getElementById('multipleFileInput');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZoneMultiple.addEventListener(eventName, preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZoneMultiple.addEventListener(eventName, () => {
                dropZoneMultiple.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZoneMultiple.addEventListener(eventName, () => {
                dropZoneMultiple.classList.remove('dragover');
            }, false);
        });
        
        dropZoneMultiple.addEventListener('drop', handleMultipleDrop, false);
        
        function handleMultipleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            multipleFileInput.files = files;
            handleMultipleFileSelect(multipleFileInput);
        }
    </script>
</body>
</html>
