-- Migration Script: Add Multilingual Fields
-- สำหรับรองรับหลายภาษา (ไทย, อังกฤษ, จีน)
-- Date: 2024

-- ============================================
-- 1. bk_hotel_settings Table
-- ============================================
-- เพิ่ม columns สำหรับ hotel_name, description, address, city
ALTER TABLE `bk_hotel_settings` 
ADD COLUMN IF NOT EXISTS `hotel_name_th` VARCHAR(255) AFTER `hotel_name`,
ADD COLUMN IF NOT EXISTS `hotel_name_en` VARCHAR(255) AFTER `hotel_name_th`,
ADD COLUMN IF NOT EXISTS `hotel_name_zh` VARCHAR(255) AFTER `hotel_name_en`,
ADD COLUMN IF NOT EXISTS `description_th` TEXT AFTER `description`,
ADD COLUMN IF NOT EXISTS `description_en` TEXT AFTER `description_th`,
ADD COLUMN IF NOT EXISTS `description_zh` TEXT AFTER `description_en`,
ADD COLUMN IF NOT EXISTS `address_th` TEXT AFTER `address`,
ADD COLUMN IF NOT EXISTS `address_en` TEXT AFTER `address_th`,
ADD COLUMN IF NOT EXISTS `address_zh` TEXT AFTER `address_en`,
ADD COLUMN IF NOT EXISTS `city_th` VARCHAR(100) AFTER `city`,
ADD COLUMN IF NOT EXISTS `city_en` VARCHAR(100) AFTER `city_th`,
ADD COLUMN IF NOT EXISTS `city_zh` VARCHAR(100) AFTER `city_en`;

-- ============================================
-- 2. bk_system_settings Table
-- ============================================
-- เพิ่ม columns สำหรับ site_name
ALTER TABLE `bk_system_settings`
ADD COLUMN IF NOT EXISTS `site_name_th` VARCHAR(255) AFTER `site_name`,
ADD COLUMN IF NOT EXISTS `site_name_en` VARCHAR(255) AFTER `site_name_th`,
ADD COLUMN IF NOT EXISTS `site_name_zh` VARCHAR(255) AFTER `site_name_en`;

-- ============================================
-- 3. bk_amenities Table
-- ============================================
-- เพิ่ม columns สำหรับ amenity_name
ALTER TABLE `bk_amenities`
ADD COLUMN IF NOT EXISTS `amenity_name_th` VARCHAR(255) AFTER `amenity_name`,
ADD COLUMN IF NOT EXISTS `amenity_name_en` VARCHAR(255) AFTER `amenity_name_th`,
ADD COLUMN IF NOT EXISTS `amenity_name_zh` VARCHAR(255) AFTER `amenity_name_en`;

-- ============================================
-- 4. bk_seasonal_rates Table
-- ============================================
-- เพิ่ม columns สำหรับ season_name
ALTER TABLE `bk_seasonal_rates`
ADD COLUMN IF NOT EXISTS `season_name_th` VARCHAR(255) AFTER `season_name`,
ADD COLUMN IF NOT EXISTS `season_name_en` VARCHAR(255) AFTER `season_name_th`,
ADD COLUMN IF NOT EXISTS `season_name_zh` VARCHAR(255) AFTER `season_name_en`;

-- ============================================
-- Notes:
-- ============================================
-- 1. ถ้าใช้ MariaDB/MySQL เวอร์ชันเก่า (< 10.6) ที่ไม่รองรับ IF NOT EXISTS
--    ให้ลบ IF NOT EXISTS ออกและรันด้วยความระมัดระวัง
-- 
-- 2. สำหรับ MySQL เวอร์ชันเก่า ใช้คำสั่งนี้แทน:
-- 
--    -- ตรวจสอบก่อนว่ามี column หรือยัง (ใช้ใน MySQL Client)
--    -- SELECT COUNT(*) FROM information_schema.COLUMNS 
--    -- WHERE TABLE_SCHEMA = 'hotel_booking' 
--    -- AND TABLE_NAME = 'bk_hotel_settings' 
--    -- AND COLUMN_NAME = 'hotel_name_th';
-- 
-- 3. หลังจากรัน migration แล้ว:
--    - ข้อมูลเดิมจะยังอยู่ใน fields เดิม (hotel_name, description, etc.)
--    - สามารถคัดลอกข้อมูลเดิมไปยัง fields ภาษาไทยได้ถ้าต้องการ
--    - ระบบจะใช้ข้อมูลจาก fields หลายภาษาก่อน ถ้าไม่มีจะ fallback ไปใช้ข้อมูลเดิม
--
-- 4. Migration Script นี้เป็น idempotent (รันซ้ำได้โดยไม่เกิด error)
--    เพราะใช้ IF NOT EXISTS (ถ้ารองรับ)
