-- ============================================
-- Database Migration: เพิ่มภาษาจีน (Chinese Language Support)
-- ============================================
-- วันที่: 2024
-- คำอธิบาย: เพิ่มคอลัมน์สำหรับเก็บข้อมูลภาษาจีนในตาราง bk_room_types
-- ============================================

-- ตรวจสอบและเพิ่มคอลัมน์ description_th (ถ้ายังไม่มี)
SET @dbname = DATABASE();
SET @tablename = 'bk_room_types';
SET @columnname = 'description_th';
SET @prepared = CONCAT('SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
PREPARE stmt FROM @prepared;
EXECUTE stmt USING @dbname, @tablename, @columnname;
DEALLOCATE PREPARE stmt;

SET @sqlstmt = IF(@exist > 0,
    'SELECT ''Column description_th already exists.'' AS message',
    'ALTER TABLE bk_room_types ADD COLUMN description_th TEXT AFTER description');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ตรวจสอบและเพิ่มคอลัมน์ description_en (ถ้ายังไม่มี)
SET @columnname = 'description_en';
SET @prepared = CONCAT('SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
PREPARE stmt FROM @prepared;
EXECUTE stmt USING @dbname, @tablename, @columnname;
DEALLOCATE PREPARE stmt;

SET @sqlstmt = IF(@exist > 0,
    'SELECT ''Column description_en already exists.'' AS message',
    'ALTER TABLE bk_room_types ADD COLUMN description_en TEXT AFTER description_th');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ตรวจสอบและเพิ่มคอลัมน์ description_zh (ภาษาจีน)
SET @columnname = 'description_zh';
SET @prepared = CONCAT('SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
PREPARE stmt FROM @prepared;
EXECUTE stmt USING @dbname, @tablename, @columnname;
DEALLOCATE PREPARE stmt;

SET @sqlstmt = IF(@exist > 0,
    'SELECT ''Column description_zh already exists.'' AS message',
    'ALTER TABLE bk_room_types ADD COLUMN description_zh TEXT AFTER description_en');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ตรวจสอบและเพิ่มคอลัมน์ bed_type_th (ถ้ายังไม่มี)
SET @columnname = 'bed_type_th';
SET @prepared = CONCAT('SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
PREPARE stmt FROM @prepared;
EXECUTE stmt USING @dbname, @tablename, @columnname;
DEALLOCATE PREPARE stmt;

SET @sqlstmt = IF(@exist > 0,
    'SELECT ''Column bed_type_th already exists.'' AS message',
    'ALTER TABLE bk_room_types ADD COLUMN bed_type_th VARCHAR(100) AFTER bed_type');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ตรวจสอบและเพิ่มคอลัมน์ bed_type_en (ถ้ายังไม่มี)
SET @columnname = 'bed_type_en';
SET @prepared = CONCAT('SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
PREPARE stmt FROM @prepared;
EXECUTE stmt USING @dbname, @tablename, @columnname;
DEALLOCATE PREPARE stmt;

SET @sqlstmt = IF(@exist > 0,
    'SELECT ''Column bed_type_en already exists.'' AS message',
    'ALTER TABLE bk_room_types ADD COLUMN bed_type_en VARCHAR(100) AFTER bed_type_th');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ตรวจสอบและเพิ่มคอลัมน์ bed_type_zh (ภาษาจีน)
SET @columnname = 'bed_type_zh';
SET @prepared = CONCAT('SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
PREPARE stmt FROM @prepared;
EXECUTE stmt USING @dbname, @tablename, @columnname;
DEALLOCATE PREPARE stmt;

SET @sqlstmt = IF(@exist > 0,
    'SELECT ''Column bed_type_zh already exists.'' AS message',
    'ALTER TABLE bk_room_types ADD COLUMN bed_type_zh VARCHAR(100) AFTER bed_type_en');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- SQL แบบง่าย (ถ้าต้องการรันแบบตรงๆ โดยไม่ตรวจสอบ)
-- ============================================
/*
-- เพิ่มคอลัมน์ description_th (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS description_th TEXT AFTER description;

-- เพิ่มคอลัมน์ description_en (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS description_en TEXT AFTER description_th;

-- เพิ่มคอลัมน์ description_zh (ภาษาจีน)
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS description_zh TEXT AFTER description_en;

-- เพิ่มคอลัมน์ bed_type_th (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS bed_type_th VARCHAR(100) AFTER bed_type;

-- เพิ่มคอลัมน์ bed_type_en (ถ้ายังไม่มี)
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS bed_type_en VARCHAR(100) AFTER bed_type_th;

-- เพิ่มคอลัมน์ bed_type_zh (ภาษาจีน)
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS bed_type_zh VARCHAR(100) AFTER bed_type_en;
*/

-- ============================================
-- SQL แบบมาตรฐาน (สำหรับ MySQL เวอร์ชันที่รองรับ IF NOT EXISTS)
-- ============================================
/*
-- สำหรับ MySQL 8.0.19+ ที่รองรับ IF NOT EXISTS ใน ALTER TABLE
ALTER TABLE bk_room_types 
ADD COLUMN IF NOT EXISTS description_th TEXT AFTER description,
ADD COLUMN IF NOT EXISTS description_en TEXT AFTER description_th,
ADD COLUMN IF NOT EXISTS description_zh TEXT AFTER description_en,
ADD COLUMN IF NOT EXISTS bed_type_th VARCHAR(100) AFTER bed_type,
ADD COLUMN IF NOT EXISTS bed_type_en VARCHAR(100) AFTER bed_type_th,
ADD COLUMN IF NOT EXISTS bed_type_zh VARCHAR(100) AFTER bed_type_en;
*/

-- ============================================
-- ตรวจสอบผลลัพธ์
-- ============================================
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'bk_room_types'
    AND COLUMN_NAME IN ('description_th', 'description_en', 'description_zh', 
                        'bed_type_th', 'bed_type_en', 'bed_type_zh')
ORDER BY ORDINAL_POSITION;
