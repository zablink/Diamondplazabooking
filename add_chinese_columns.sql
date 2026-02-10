-- ============================================
-- SQL สำหรับเพิ่มคอลัมน์ภาษาจีนในตาราง bk_room_types
-- ============================================
-- รองรับ: MySQL 5.7+ และ MariaDB 10.2+
-- ============================================

-- วิธีที่ 1: ใช้ IF NOT EXISTS (MySQL 8.0.19+ หรือ MariaDB 10.2.5+)
-- ถ้า MySQL version ของคุณรองรับ IF NOT EXISTS ให้ใช้วิธีนี้

ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS description_th TEXT AFTER description,
ADD COLUMN IF NOT EXISTS description_en TEXT AFTER description_th,
ADD COLUMN IF NOT EXISTS description_zh TEXT AFTER description_en,
ADD COLUMN IF NOT EXISTS bed_type_th VARCHAR(100) AFTER bed_type,
ADD COLUMN IF NOT EXISTS bed_type_en VARCHAR(100) AFTER bed_type_th,
ADD COLUMN IF NOT EXISTS bed_type_zh VARCHAR(100) AFTER bed_type_en;

-- ============================================
-- วิธีที่ 2: รันทีละคำสั่ง (สำหรับ MySQL เวอร์ชันเก่า)
-- ============================================
-- ถ้า MySQL version ของคุณไม่รองรับ IF NOT EXISTS ให้รันทีละคำสั่ง
-- และถ้ามีคอลัมน์อยู่แล้วจะเกิด error แต่ไม่เป็นไร

-- เพิ่มคอลัมน์ description_th (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN description_th TEXT AFTER description;

-- เพิ่มคอลัมน์ description_en (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN description_en TEXT AFTER description_th;

-- เพิ่มคอลัมน์ description_zh (ภาษาจีน) - ใหม่!
ALTER TABLE bk_room_types 
ADD COLUMN description_zh TEXT AFTER description_en;

-- เพิ่มคอลัมน์ bed_type_th (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN bed_type_th VARCHAR(100) AFTER bed_type;

-- เพิ่มคอลัมน์ bed_type_en (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN bed_type_en VARCHAR(100) AFTER bed_type_th;

-- เพิ่มคอลัมน์ bed_type_zh (ภาษาจีน) - ใหม่!
ALTER TABLE bk_room_types 
ADD COLUMN bed_type_zh VARCHAR(100) AFTER bed_type_en;

-- ============================================
-- ตรวจสอบผลลัพธ์
-- ============================================
SELECT 
    COLUMN_NAME AS 'ชื่อคอลัมน์',
    DATA_TYPE AS 'ประเภทข้อมูล',
    CHARACTER_MAXIMUM_LENGTH AS 'ความยาวสูงสุด',
    IS_NULLABLE AS 'เป็น NULL ได้',
    COLUMN_DEFAULT AS 'ค่าเริ่มต้น'
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bk_room_types'
    AND COLUMN_NAME IN ('description_th', 'description_en', 'description_zh', 
                        'bed_type_th', 'bed_type_en', 'bed_type_zh')
ORDER BY ORDINAL_POSITION;
