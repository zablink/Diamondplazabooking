<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-hotel"></i>
            <span>Hotel Admin</span>
        </div>
        <div class="admin-profile">
            <div class="admin-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="admin-info">
                <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                <div class="admin-role">ผู้ดูแลระบบ</div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i>
            <span>สถิติและรายงาน</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- Bookings Section -->
        <div class="nav-section-title">
            <i class="fas fa-calendar"></i>
            <span>การจัดการการจอง</span>
        </div>
        
        <a href="bookings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i>
            <span>การจองทั้งหมด</span>
            <?php if (isset($stats['pending_bookings']) && $stats['pending_bookings'] > 0): ?>
                <span class="nav-badge"><?= $stats['pending_bookings'] ?></span>
            <?php endif; ?>
        </a>
        
        <a href="calendar.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>ปฏิทินการจอง</span>
        </a>
        
        <a href="availability.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'availability.php' ? 'active' : '' ?>">
            <i class="fas fa-door-open"></i>
            <span>ห้องว่าง</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- Rooms Section -->
        <div class="nav-section-title">
            <i class="fas fa-bed"></i>
            <span>การจัดการห้องพัก</span>
        </div>
        
        <a href="rooms.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : '' ?>">
            <i class="fas fa-bed"></i>
            <span>ประเภทห้องพัก</span>
        </a>
        
        <a href="seasonal-pricing.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'seasonal-pricing.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i>
            <span>ราคาตามฤดูกาล</span>
        </a>
        
        <a href="amenities.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'amenities.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i>
            <span>สิ่งอำนวยความสะดวก</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- Customers Section -->
        <div class="nav-section-title">
            <i class="fas fa-users"></i>
            <span>ลูกค้า</span>
        </div>
        
        <a href="customers.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>รายชื่อลูกค้า</span>
        </a>
        
        <a href="reviews.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots"></i>
            <span>รีวิวและคำติชม</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- Reports Section -->
        <div class="nav-section-title">
            <i class="fas fa-chart-bar"></i>
            <span>รายงาน</span>
        </div>
        
        <a href="reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i>
            <span>รายงานรายได้</span>
        </a>
        
        <a href="analytics.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i>
            <span>วิเคราะห์สถิติ</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <!-- Settings Section -->
        <div class="nav-section-title">
            <i class="fas fa-cog"></i>
            <span>ตั้งค่า</span>
        </div>
        
        <a href="hotel-settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'hotel-settings.php' ? 'active' : '' ?>">
            <i class="fas fa-hotel"></i>
            <span>ข้อมูลโรงแรม</span>
        </a>
        
        <a href="admin-users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-users.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i>
            <span>ผู้ดูแลระบบ</span>
        </a>
        
        <a href="system-settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'system-settings.php' ? 'active' : '' ?>">
            <i class="fas fa-sliders-h"></i>
            <span>ตั้งค่าระบบ</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="logout.php" class="nav-item nav-item-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>ออกจากระบบ</span>
        </a>
    </nav>
</div>

<style>
/* Admin Profile in Sidebar */
.admin-profile {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.admin-avatar i {
    font-size: 2.5rem;
    color: rgba(255,255,255,0.9);
}

.admin-info {
    flex: 1;
    min-width: 0;
}

.admin-name {
    font-weight: 600;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-role {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* Nav Section Title */
.nav-section-title {
    padding: 0.75rem 1.5rem;
    color: rgba(255,255,255,0.6);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.nav-section-title i {
    font-size: 0.85rem;
}

/* Nav Badge (สำหรับแสดงจำนวน pending) */
.nav-badge {
    margin-left: auto;
    background: #ff4757;
    color: white;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}

/* Logout Item */
.nav-item-logout:hover {
    background: rgba(255,0,0,0.1) !important;
    border-left-color: #ff4757 !important;
}
</style>