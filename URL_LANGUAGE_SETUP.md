# ระบบ URL แยกตามภาษา - คู่มือการใช้งาน

## สรุปการเปลี่ยนแปลง

ระบบ booking ได้รับการปรับปรุงให้รองรับ URL แยกตามภาษาแล้ว โดยแต่ละภาษาจะมี URL ที่แตกต่างกัน:

- **ภาษาไทย**: `/booking/th/index.php`
- **ภาษาอังกฤษ**: `/booking/en/index.php`

## โครงสร้าง URL

### รูปแบบ URL ใหม่

```
/booking/{language}/{page}.php?{parameters}
```

ตัวอย่าง:
- `/booking/th/index.php` - หน้าแรกภาษาไทย
- `/booking/en/index.php` - หน้าแรกภาษาอังกฤษ
- `/booking/th/room_detail.php?id=1` - รายละเอียดห้องพักภาษาไทย
- `/booking/en/room_detail.php?id=1` - รายละเอียดห้องพักภาษาอังกฤษ

## ไฟล์ที่แก้ไข

### 1. `.htaccess`
- เพิ่ม URL rewriting เพื่อรองรับภาษาใน path
- Redirect URL ที่ไม่มีภาษาไปยัง URL ที่มีภาษา

### 2. `includes/lang.php`
- เพิ่มฟังก์ชัน `getLanguageFromUrl()` - ดึงภาษาจาก URL path
- เพิ่มฟังก์ชัน `getCurrentPathWithoutLang()` - ดึง path โดยไม่มีภาษา
- แก้ไข `getCurrentLanguage()` - อ่านภาษาจาก URL ก่อน
- แก้ไข `getLanguageUrl()` - สร้าง URL ที่มีภาษาใน path
- เพิ่มฟังก์ชัน `url()` - helper สำหรับสร้าง URL ที่มีภาษา

### 3. `includes/helpers.php`
- แก้ไข `redirect()` - รองรับ URL ที่มีภาษา

### 4. `includes/header.php`
- แก้ไขลิงก์ทั้งหมดให้ใช้ฟังก์ชัน `url()`
- แก้ไข language switcher ให้ใช้ `getLanguageUrl()`

### 5. `includes/footer.php`
- แก้ไขลิงก์ทั้งหมดให้ใช้ฟังก์ชัน `url()`

### 6. `index.php`
- แก้ไขลิงก์ไปยัง `room_detail.php` ให้ใช้ฟังก์ชัน `url()`

## วิธีใช้งาน

### สร้างลิงก์ภายในเว็บ

ใช้ฟังก์ชัน `url()` เพื่อสร้างลิงก์ที่มีภาษา:

```php
// ลิงก์ไปยังหน้าแรก
<a href="<?php echo url('index.php'); ?>">หน้าแรก</a>

// ลิงก์พร้อม parameters
<a href="<?php echo url('room_detail.php', ['id' => 1]); ?>">ดูรายละเอียด</a>

// ลิงก์ไปยังหน้าอื่น
<a href="<?php echo url('booking.php', ['room_id' => 5]); ?>">จองห้อง</a>
```

### เปลี่ยนภาษา

ใช้ฟังก์ชัน `getLanguageUrl()`:

```php
<a href="<?php echo getLanguageUrl('th'); ?>">ไทย</a>
<a href="<?php echo getLanguageUrl('en'); ?>">English</a>
```

### Redirect

ฟังก์ชัน `redirect()` จะเพิ่มภาษาให้อัตโนมัติ:

```php
redirect('index.php'); // จะ redirect ไปยัง /booking/{lang}/index.php
```

## การทำงานของระบบ

1. **เมื่อผู้ใช้เข้าถึง URL ที่ไม่มีภาษา**:
   - ระบบจะตรวจสอบภาษาจาก session หรือ browser
   - Redirect ไปยัง URL ที่มีภาษา (เช่น `/booking/th/index.php`)

2. **เมื่อผู้ใช้เปลี่ยนภาษา**:
   - ระบบจะสร้าง URL ใหม่ที่มีภาษาใน path
   - Redirect ไปยัง URL ใหม่พร้อมเก็บ parameters อื่นๆ ไว้

3. **เมื่อสร้างลิงก์**:
   - ใช้ฟังก์ชัน `url()` เพื่อสร้าง URL ที่มีภาษาปัจจุบัน
   - URL จะมีรูปแบบ `/booking/{lang}/{page}.php`

## การใช้งานกับ Polylang

ตอนนี้ระบบรองรับ URL แยกตามภาษาแล้ว สามารถใช้งานกับ Polylang ได้โดย:

1. **ตั้งค่า Polylang**:
   - ไปที่ Settings > Languages
   - ตั้งค่า URL modifications เป็น "The language is set from different domains" หรือ "The language is set from the directory name in pretty permalinks"

2. **เพิ่ม Custom URLs**:
   - ใน Polylang สามารถเพิ่ม custom URLs สำหรับแต่ละภาษา
   - ใช้ URL ที่มีภาษา เช่น:
     - ภาษาไทย: `/booking/th/`
     - ภาษาอังกฤษ: `/booking/en/`

3. **การเชื่อมต่อ**:
   - Polylang จะสามารถเชื่อมโยง URL ของแต่ละภาษาได้
   - ผู้ใช้สามารถสลับภาษาผ่าน Polylang language switcher ได้

## หมายเหตุ

- ระบบจะไม่ redirect สำหรับ:
  - Admin pages (`/booking/admin/`)
  - Auth pages (`/booking/auth/`)
  - AJAX requests
  - POST requests

- ระบบยังรองรับ URL แบบเก่า (มี `?lang=th` หรือ `?lang=en`) และจะ redirect ไปยัง URL ใหม่อัตโนมัติ

## การทดสอบ

1. เข้าถึง `/booking/index.php` - ควร redirect ไปยัง `/booking/th/index.php` หรือ `/booking/en/index.php`
2. เปลี่ยนภาษาผ่าน language switcher - URL ควรเปลี่ยนเป็นภาษาที่เลือก
3. คลิกลิงก์ต่างๆ - ทุกลิงก์ควรมีภาษาใน URL
4. ตรวจสอบว่า parameters ยังคงอยู่เมื่อเปลี่ยนภาษา
