<div class="top-header">
    <div class="header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span>Admin</span>
            <?php
            $currentPage = basename($_SERVER['PHP_SELF'], '.php');
            $pageNames = [
                'index' => 'Dashboard',
                'rooms' => 'จัดการห้องพัก',
                'bookings' => 'จัดการการจอง',
                'customers' => 'จัดการลูกค้า',
                'seasonal-pricing' => 'ราคาตามฤดูกาล',
                'hotel-settings' => 'ตั้งค่าโรงแรม'
            ];
            if (isset($pageNames[$currentPage])):
            ?>
                <i class="fas fa-chevron-right"></i>
                <span><?= $pageNames[$currentPage] ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-item">
            <i class="fas fa-calendar-alt"></i>
            <span><?= date('d/m/Y') ?></span>
        </div>
        
        <div class="header-item">
            <i class="fas fa-clock"></i>
            <span id="current-time"><?= date('H:i') ?></span>
        </div>
        
        <div class="user-menu" onclick="toggleUserMenu()" style="cursor: pointer; position: relative;">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                <div class="user-role">ผู้ดูแลระบบ</div>
            </div>
            <div class="user-dropdown" id="userDropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; margin-top: 10px; min-width: 200px; z-index: 1000;">
                <a href="logout.php" style="display: flex; align-items: center; padding: 12px 20px; color: #2c3e50; text-decoration: none; transition: background 0.3s;">
                    <i class="fas fa-sign-out-alt" style="margin-right: 10px; color: #e74c3c;"></i>
                    <span>ออกจากระบบ</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Update clock every minute
setInterval(function() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('current-time').textContent = hours + ':' + minutes;
}, 60000);

// Toggle sidebar on mobile
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// Toggle user menu dropdown
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');
    if (userMenu && dropdown && !userMenu.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

<style>
.user-menu {
    position: relative;
}

.user-dropdown a:hover {
    background: #f8f9fa;
}

.user-dropdown a:first-child {
    border-radius: 8px 8px 0 0;
}

.user-dropdown a:last-child {
    border-radius: 0 0 8px 8px;
}
</style>