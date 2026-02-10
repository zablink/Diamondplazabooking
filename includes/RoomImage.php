<?php
/**
 * RoomImage Class
 * จัดการรูปภาพของห้องพัก - Upload, Delete, Set Featured Image
 */

class RoomImage {
    private $db;
    private $uploadPath;
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private $maxFileSize = 5242880; // 5MB
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // กำหนด upload path
        $this->uploadPath = PROJECT_ROOT . '/images/rooms/';
        
        // สร้าง directory ถ้ายังไม่มี
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * อัปโหลดรูปภาพ
     */
    public function uploadImage($roomTypeId, $file, $isFeatured = false) {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // สร้างชื่อไฟล์ unique
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'room_' . $roomTypeId . '_' . uniqid() . '.' . $extension;
            $filepath = $this->uploadPath . $filename;
            
            // ย้ายไฟล์
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถอัปโหลดไฟล์ได้'
                ];
            }
            
            // ถ้าเป็น featured image ให้เอา featured ออกจากรูปอื่นทั้งหมด
            if ($isFeatured) {
                $this->clearFeaturedImage($roomTypeId);
            }
            
            // หา display_order สูงสุด
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT COALESCE(MAX(display_order), 0) as max_order 
                FROM bk_room_images 
                WHERE room_type_id = ?
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $displayOrder = $result['max_order'] + 1;
            
            // บันทึกลง database
            $stmt = $conn->prepare("
                INSERT INTO bk_room_images 
                (room_type_id, image_path, is_featured, display_order, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $imagePath = 'images/rooms/' . $filename;
            $stmt->execute([
                $roomTypeId,
                $imagePath,
                $isFeatured ? 1 : 0,
                $displayOrder
            ]);
            
            $imageId = $conn->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'อัปโหลดรูปภาพสำเร็จ',
                'image_id' => $imageId,
                'image_path' => $imagePath
            ];
            
        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปโหลดหลายไฟล์พร้อมกัน
     */
    public function uploadMultipleImages($roomTypeId, $files) {
        $results = [
            'success' => true,
            'uploaded' => 0,
            'failed' => 0,
            'messages' => []
        ];
        
        // Convert $_FILES array format to easier format
        $fileArray = $this->reArrayFiles($files);
        
        foreach ($fileArray as $file) {
            $result = $this->uploadImage($roomTypeId, $file, false);
            
            if ($result['success']) {
                $results['uploaded']++;
            } else {
                $results['failed']++;
                $results['success'] = false;
            }
            
            $results['messages'][] = [
                'filename' => $file['name'],
                'success' => $result['success'],
                'message' => $result['message']
            ];
        }
        
        return $results;
    }
    
    /**
     * ลบรูปภาพ
     */
    public function deleteImage($imageId) {
        try {
            $conn = $this->db->getConnection();
            
            // ดึงข้อมูลรูปภาพ
            $stmt = $conn->prepare("
                SELECT image_path FROM bk_room_images WHERE image_id = ?
            ");
            $stmt->execute([$imageId]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบรูปภาพ'
                ];
            }
            
            // ลบไฟล์จริง
            $fullPath = PROJECT_ROOT . '/' . $image['image_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            // ลบจาก database
            $stmt = $conn->prepare("DELETE FROM bk_room_images WHERE image_id = ?");
            $stmt->execute([$imageId]);
            
            return [
                'success' => true,
                'message' => 'ลบรูปภาพสำเร็จ'
            ];
            
        } catch (Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ตั้งรูปภาพเป็น featured image
     */
    public function setFeaturedImage($imageId) {
        try {
            $conn = $this->db->getConnection();
            
            // ดึงข้อมูลรูปภาพ
            $stmt = $conn->prepare("
                SELECT room_type_id FROM bk_room_images WHERE image_id = ?
            ");
            $stmt->execute([$imageId]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบรูปภาพ'
                ];
            }
            
            // เอา featured ออกจากรูปอื่นทั้งหมด
            $this->clearFeaturedImage($image['room_type_id']);
            
            // ตั้งรูปนี้เป็น featured
            $stmt = $conn->prepare("
                UPDATE bk_room_images 
                SET is_featured = 1 
                WHERE image_id = ?
            ");
            $stmt->execute([$imageId]);
            
            return [
                'success' => true,
                'message' => 'ตั้งเป็นภาพหน้าปกสำเร็จ'
            ];
            
        } catch (Exception $e) {
            error_log("Set featured error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * อัปเดตลำดับการแสดงผล
     */
    public function updateDisplayOrder($imageId, $order) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE bk_room_images 
                SET display_order = ? 
                WHERE image_id = ?
            ");
            $stmt->execute([$order, $imageId]);
            
            return [
                'success' => true,
                'message' => 'อัปเดตลำดับสำเร็จ'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ดึงรูปภาพทั้งหมดของห้อง
     */
    public function getImages($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT * FROM bk_room_images 
                WHERE room_type_id = ?
                ORDER BY is_featured DESC, display_order ASC
            ");
            $stmt->execute([$roomTypeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get images error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึง featured image
     */
    public function getFeaturedImage($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT * FROM bk_room_images 
                WHERE room_type_id = ? AND is_featured = 1
                LIMIT 1
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // ถ้าไม่มี featured ให้เอารูปแรก
            if (!$result) {
                $stmt = $conn->prepare("
                    SELECT * FROM bk_room_images 
                    WHERE room_type_id = ?
                    ORDER BY display_order ASC
                    LIMIT 1
                ");
                $stmt->execute([$roomTypeId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Get featured error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * นับจำนวนรูปภาพ
     */
    public function countImages($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total FROM bk_room_images 
                WHERE room_type_id = ?
            ");
            $stmt->execute([$roomTypeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    // ==================== Private Methods ====================
    
    /**
     * Validate ไฟล์
     */
    private function validateFile($file) {
        // ตรวจสอบว่ามี error หรือไม่
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลด (Error code: ' . $file['error'] . ')'
            ];
        }
        
        // ตรวจสอบขนาดไฟล์
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'ไฟล์มีขนาดใหญ่เกินกำหนด (สูงสุด 5MB)'
            ];
        }
        
        // ตรวจสอบประเภทไฟล์
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'ประเภทไฟล์ไม่ถูกต้อง (รองรับเฉพาะ JPG, PNG, WebP)'
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * เอา featured ออกจากรูปอื่นทั้งหมด
     */
    private function clearFeaturedImage($roomTypeId) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE bk_room_images 
                SET is_featured = 0 
                WHERE room_type_id = ?
            ");
            $stmt->execute([$roomTypeId]);
        } catch (Exception $e) {
            error_log("Clear featured error: " . $e->getMessage());
        }
    }
    
    /**
     * แปลง $_FILES array format
     */
    private function reArrayFiles($filePost) {
        $fileArray = [];
        $fileCount = count($filePost['name']);
        $fileKeys = array_keys($filePost);

        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $fileArray[$i][$key] = $filePost[$key][$i];
            }
        }

        return $fileArray;
    }
}
