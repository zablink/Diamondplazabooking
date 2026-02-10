-- Migration Script: Add about_description columns to bk_hotel_settings
-- สำหรับเพิ่มคอลัมน์ about_description ในตาราง bk_hotel_settings
-- รองรับหลายภาษา (th, en, zh)
-- รองรับ MySQL เวอร์ชันเก่าที่ไม่มี IF NOT EXISTS

-- ตรวจสอบและเพิ่มคอลัมน์ about_description (default)
-- สำหรับ MySQL เวอร์ชันเก่า ให้รันทีละคำสั่งและข้าม error ถ้ามีคอลัมน์อยู่แล้ว

-- เพิ่มคอลัมน์ about_description (default)
SET @dbname = DATABASE();
SET @tablename = 'bk_hotel_settings';
SET @columnname = 'about_description';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT NULL AFTER description')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- เพิ่มคอลัมน์ about_description_th
SET @columnname = 'about_description_th';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT NULL AFTER description_zh')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- เพิ่มคอลัมน์ about_description_en
SET @columnname = 'about_description_en';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT NULL AFTER about_description_th')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- เพิ่มคอลัมน์ about_description_zh
SET @columnname = 'about_description_zh';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TEXT NULL AFTER about_description_en')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- หมายเหตุ: 
-- - ระบบจะสร้างคอลัมน์อัตโนมัติเมื่อมีการบันทึกการตั้งค่าใน Admin Panel
-- - แต่ถ้าต้องการสร้างล่วงหน้า สามารถรัน SQL script นี้ได้
-- - Script นี้รองรับ MySQL เวอร์ชันเก่าด้วย
