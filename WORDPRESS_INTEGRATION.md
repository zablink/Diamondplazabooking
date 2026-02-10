# คู่มือการเชื่อมต่อ Booking System กับ WordPress

## การสร้างลิงก์จาก WordPress Post ไปยัง Booking System

### 1. ลิงก์ไปยังรายละเอียดห้องพัก (Room Detail)

#### ภาษาไทย
```html
<a href="https://diamondplazasurat.com/booking/th/room_detail.php?id=1" 
   class="btn-book-now">
   จองห้องพักนี้
</a>
```

#### ภาษาอังกฤษ
```html
<a href="https://diamondplazasurat.com/booking/en/room_detail.php?id=1" 
   class="btn-book-now">
   Book This Room
</a>
```

### 2. ลิงก์ไปยังหน้าจองพร้อมข้อมูล (Booking Page)

#### ภาษาไทย - พร้อมวันที่และจำนวนผู้เข้าพัก
```html
<a href="https://diamondplazasurat.com/booking/th/booking.php?room_type_id=1&check_in=2024-01-15&check_out=2024-01-17&adults=2&children=0" 
   class="btn-book-now">
   จองเลย
</a>
```

#### ภาษาอังกฤษ
```html
<a href="https://diamondplazasurat.com/booking/en/booking.php?room_type_id=1&check_in=2024-01-15&check_out=2024-01-17&adults=2&children=0" 
   class="btn-book-now">
   Book Now
</a>
```

### 3. ลิงก์ไปยังหน้าค้นหา (Search Page)

#### ภาษาไทย
```html
<a href="https://diamondplazasurat.com/booking/th/search.php?check_in=2024-01-15&check_out=2024-01-17&guests=2" 
   class="btn-search">
   ค้นหาห้องพัก
</a>
```

#### ภาษาอังกฤษ
```html
<a href="https://diamondplazasurat.com/booking/en/search.php?check_in=2024-01-15&check_out=2024-01-17&guests=2" 
   class="btn-search">
   Search Rooms
</a>
```

## Parameters ที่รองรับ

### room_detail.php
- `id` หรือ `room_type_id` - ID ของห้องพัก (required)
- `check_in` - วันที่เช็คอิน (optional, format: YYYY-MM-DD)
- `check_out` - วันที่เช็คเอาท์ (optional, format: YYYY-MM-DD)
- `adults` - จำนวนผู้ใหญ่ (optional)
- `children` - จำนวนเด็ก (optional)
- `rooms` - จำนวนห้อง (optional)

### booking.php
- `room_type_id` - ID ของห้องพัก (required)
- `check_in` - วันที่เช็คอิน (required, format: YYYY-MM-DD)
- `check_out` - วันที่เช็คเอาท์ (required, format: YYYY-MM-DD)
- `adults` - จำนวนผู้ใหญ่ (optional, default: 2)
- `children` - จำนวนเด็ก (optional, default: 0)
- `rooms` - จำนวนห้อง (optional, default: 1)

### search.php
- `check_in` - วันที่เช็คอิน (optional, format: YYYY-MM-DD)
- `check_out` - วันที่เช็คเอาท์ (optional, format: YYYY-MM-DD)
- `guests` - จำนวนผู้เข้าพัก (optional)

## ตัวอย่างการใช้งานใน WordPress

### วิธีที่ 1: ใช้ Shortcode (แนะนำ)

สร้าง shortcode ใน WordPress functions.php:

```php
// เพิ่มใน functions.php ของ WordPress theme
function booking_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'detail', // detail, booking, search
        'room_id' => '',
        'lang' => 'th', // th, en
        'check_in' => '',
        'check_out' => '',
        'adults' => '2',
        'children' => '0',
        'rooms' => '1',
        'text' => 'จองเลย',
        'class' => 'btn-book-now'
    ), $atts);
    
    $base_url = 'https://diamondplazasurat.com/booking';
    $lang = $atts['lang'];
    $url = '';
    
    switch($atts['type']) {
        case 'detail':
            $url = $base_url . '/' . $lang . '/room_detail.php?id=' . $atts['room_id'];
            if ($atts['check_in']) $url .= '&check_in=' . $atts['check_in'];
            if ($atts['check_out']) $url .= '&check_out=' . $atts['check_out'];
            if ($atts['adults']) $url .= '&adults=' . $atts['adults'];
            if ($atts['children']) $url .= '&children=' . $atts['children'];
            break;
            
        case 'booking':
            $url = $base_url . '/' . $lang . '/booking.php?room_type_id=' . $atts['room_id'];
            if ($atts['check_in']) $url .= '&check_in=' . $atts['check_in'];
            if ($atts['check_out']) $url .= '&check_out=' . $atts['check_out'];
            if ($atts['adults']) $url .= '&adults=' . $atts['adults'];
            if ($atts['children']) $url .= '&children=' . $atts['children'];
            if ($atts['rooms']) $url .= '&rooms=' . $atts['rooms'];
            break;
            
        case 'search':
            $url = $base_url . '/' . $lang . '/search.php';
            if ($atts['check_in']) $url .= '?check_in=' . $atts['check_in'];
            if ($atts['check_out']) $url .= '&check_out=' . $atts['check_out'];
            if ($atts['adults']) $url .= '&guests=' . $atts['adults'];
            break;
    }
    
    return '<a href="' . esc_url($url) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
}
add_shortcode('booking_link', 'booking_link_shortcode');
```

**วิธีใช้งาน Shortcode:**
```
[booking_link type="detail" room_id="1" lang="th" text="จองห้องพักนี้"]
[booking_link type="booking" room_id="1" lang="en" check_in="2024-01-15" check_out="2024-01-17" text="Book Now"]
```

### วิธีที่ 2: ใช้ Custom Field ใน Post

ใน WordPress post editor เพิ่ม custom field:
- `room_type_id` = 1
- `booking_lang` = th หรือ en

แล้วใช้โค้ดนี้ใน template:

```php
<?php
$room_id = get_post_meta(get_the_ID(), 'room_type_id', true);
$lang = get_post_meta(get_the_ID(), 'booking_lang', true) ?: 'th';
$base_url = 'https://diamondplazasurat.com/booking';
?>

<?php if ($room_id): ?>
    <a href="<?php echo $base_url; ?>/<?php echo $lang; ?>/room_detail.php?id=<?php echo $room_id; ?>" 
       class="btn-book-now">
       <?php echo $lang === 'th' ? 'จองห้องพักนี้' : 'Book This Room'; ?>
    </a>
<?php endif; ?>
```

### วิธีที่ 3: ใช้ PHP Code ใน Post (ต้องใช้ plugin เช่น "Insert PHP Code Snippet")

```php
<?php
$room_id = 1; // เปลี่ยนเป็น ID ของห้องพัก
$lang = 'th'; // หรือ 'en'
$check_in = date('Y-m-d', strtotime('+7 days')); // 7 วันจากวันนี้
$check_out = date('Y-m-d', strtotime('+9 days')); // 9 วันจากวันนี้

$url = "https://diamondplazasurat.com/booking/{$lang}/booking.php?room_type_id={$room_id}&check_in={$check_in}&check_out={$check_out}&adults=2";
?>
<a href="<?php echo $url; ?>" class="btn-book-now">จองเลย</a>
```

## ตัวอย่างการใช้งานกับ Polylang

### ใช้ Polylang Language Switcher

```php
<?php
// ดึงภาษาปัจจุบันจาก Polylang
$current_lang = pll_current_language(); // 'th' หรือ 'en'
$room_id = 1;

$url = "https://diamondplazasurat.com/booking/{$current_lang}/room_detail.php?id={$room_id}";
?>
<a href="<?php echo esc_url($url); ?>" class="btn-book-now">
    <?php echo $current_lang === 'th' ? 'จองห้องพักนี้' : 'Book This Room'; ?>
</a>
```

### ใช้ Polylang Translation Links

```php
<?php
$room_id = 1;

// ภาษาไทย
$th_url = "https://diamondplazasurat.com/booking/th/room_detail.php?id={$room_id}";
// ภาษาอังกฤษ
$en_url = "https://diamondplazasurat.com/booking/en/room_detail.php?id={$room_id}";

// ใช้กับ Polylang
pll_register_string('booking_link_th', $th_url);
pll_register_string('booking_link_en', $en_url);
?>
```

## CSS สำหรับปุ่ม (เพิ่มใน theme)

```css
.btn-book-now {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-book-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}
```

## หมายเหตุ

1. **URL Structure**: ใช้รูปแบบ `/booking/{lang}/{page}.php?{params}`
2. **Language Codes**: `th` สำหรับภาษาไทย, `en` สำหรับภาษาอังกฤษ
3. **Date Format**: ใช้รูปแบบ `YYYY-MM-DD` (เช่น `2024-01-15`)
4. **Room ID**: ต้องเป็น `room_type_id` ที่มีอยู่ในฐานข้อมูล
5. **URL Encoding**: WordPress จะ encode URL อัตโนมัติ แต่ถ้าต้องการ encode เองใช้ `urlencode()`

## ตัวอย่าง URL ที่สมบูรณ์

```
https://diamondplazasurat.com/booking/th/room_detail.php?id=1&check_in=2024-01-15&check_out=2024-01-17&adults=2&children=0

https://diamondplazasurat.com/booking/en/booking.php?room_type_id=1&check_in=2024-01-15&check_out=2024-01-17&adults=2&children=0&rooms=1

https://diamondplazasurat.com/booking/th/search.php?check_in=2024-01-15&check_out=2024-01-17&guests=2
```
