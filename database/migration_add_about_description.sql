-- Migration Script: Add about_description columns to bk_hotel_settings
-- สำหรับเพิ่มคอลัมน์ about_description ในตาราง bk_hotel_settings
-- รองรับหลายภาษา (th, en, zh)

-- ตรวจสอบและเพิ่มคอลัมน์ about_description (default)
ALTER TABLE `bk_hotel_settings` 
ADD COLUMN IF NOT EXISTS `about_description` TEXT NULL AFTER `description`;

-- เพิ่มคอลัมน์ about_description สำหรับแต่ละภาษา
ALTER TABLE `bk_hotel_settings` 
ADD COLUMN IF NOT EXISTS `about_description_th` TEXT NULL AFTER `description_zh`;

ALTER TABLE `bk_hotel_settings` 
ADD COLUMN IF NOT EXISTS `about_description_en` TEXT NULL AFTER `about_description_th`;

ALTER TABLE `bk_hotel_settings` 
ADD COLUMN IF NOT EXISTS `about_description_zh` TEXT NULL AFTER `about_description_en`;

-- หมายเหตุ: 
-- - ระบบจะสร้างคอลัมน์อัตโนมัติเมื่อมีการบันทึกการตั้งค่าใน Admin Panel
-- - แต่ถ้าต้องการสร้างล่วงหน้า สามารถรัน SQL script นี้ได้
-- - สำหรับ MySQL เวอร์ชันเก่าที่ไม่รองรับ IF NOT EXISTS ให้ลบ IF NOT EXISTS ออก
