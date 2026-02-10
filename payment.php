<?php
/**
 * Payment Page - ชำระเงินด้วย Omise
 */

// Auto-find project root
$projectRoot = __DIR__;
while (!file_exists($projectRoot . '/includes/init.php')) {
    $parent = dirname($projectRoot);
    if ($parent === $projectRoot) {
        die('Error: Cannot find project root');
    }
    $projectRoot = $parent;
}
require_once $projectRoot . '/includes/init.php';

require_once PROJECT_ROOT . '/includes/helpers.php';
require_once PROJECT_ROOT . '/modules/hotel/Hotel.php';
require_once PROJECT_ROOT . '/modules/payment/OmisePayment.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!isLoggedIn()) {
    setFlashMessage(__('booking.please_login'), 'error');
    redirect('login.php');
}

// ตรวจสอบว่ามีข้อมูลการจองหรือไม่
if (!isset($_SESSION['booking_data'])) {
    setFlashMessage(__('booking.room_not_found') . ' ' . __('messages.error'), 'error');
    redirect('index.php');
}

$booking = $_SESSION['booking_data'];
$guest = $booking['guest_data'];
$pricing = $booking['pricing'];

// โหลดข้อมูลห้องพัก
$hotel = new Hotel();
$room = $hotel->getRoomTypeById($booking['room_type_id']);

if (!$room) {
    setFlashMessage('ไม่พบข้อมูลห้องพัก', 'error');
    redirect('index.php');
}

// เตรียม Omise
$omise = new OmisePayment();
$omise_public_key = $omise->getPublicKey();

// จำนวนเงินที่ต้องชำระ (สตางค์)
$amount_satang = intval($pricing['grand_total'] * 100);

$page_title = __('payment.title') . ' - ' . SITE_NAME;
require_once PROJECT_ROOT . '/includes/header.php';
?>

<style>
    .payment-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .payment-card {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .page-title {
        font-size: 32px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
        text-align: center;
    }
    
    .page-subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 30px;
    }
    
    .booking-summary-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 15px;
    }
    
    .summary-item.total {
        font-size: 24px;
        font-weight: 700;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #e0e0e0;
        color: #667eea;
    }
    
    .payment-methods {
        margin: 30px 0;
    }
    
    .payment-method {
        display: flex;
        align-items: center;
        padding: 20px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .payment-method:hover {
        border-color: #667eea;
        background: #f8f9fa;
    }
    
    .payment-method input[type="radio"] {
        width: 20px;
        height: 20px;
        margin-right: 15px;
    }
    
    .payment-method .method-icon {
        width: 50px;
        height: 50px;
        margin-right: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 24px;
    }
    
    .payment-method .method-info h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #333;
    }
    
    .payment-method .method-info p {
        margin: 0;
        font-size: 13px;
        color: #666;
    }
    
    .card-form {
        display: none;
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .card-form.active {
        display: block;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 15px;
    }
    
    .secure-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px;
        background: #e8f5e9;
        border-radius: 8px;
        margin: 20px 0;
        color: #2e7d32;
        font-weight: 600;
    }
    
    .btn-pay {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 20px;
    }
    
    .btn-pay:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-pay:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-back {
        display: inline-block;
        padding: 10px 20px;
        color: #667eea;
        text-decoration: none;
        border: 2px solid #667eea;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    
    .btn-back:hover {
        background: #667eea;
        color: white;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-error {
        background: #fee;
        color: #c00;
        border: 1px solid #fcc;
    }
    
    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    
    .loading-overlay.active {
        display: flex;
    }
    
    .loading-content {
        background: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<!-- Omise.js -->
<script src="https://cdn.omise.co/omise.js"></script>
<script>
    Omise.setPublicKey('<?= $omise_public_key ?>');
</script>

<div class="payment-container">
    <a href="booking.php?<?= http_build_query([
        'room_type_id' => $booking['room_type_id'],
        'check_in' => $booking['check_in'],
        'check_out' => $booking['check_out'],
        'adults' => $booking['adults'],
        'children' => $booking['children'],
        'rooms' => $booking['rooms'],
        'add_breakfast' => $booking['add_breakfast']
    ]) ?>" class="btn-back">
        <i class="fas fa-arrow-left"></i> <?php _e('payment.back_to_edit'); ?>
    </a>
    
    <div class="payment-card">
        <h1 class="page-title"><?php _e('payment.title'); ?></h1>
        <p class="page-subtitle"><?php _e('payment.select_payment_method'); ?></p>
        
        <?php if ($flash = getFlashMessage()): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Booking Summary -->
        <div class="booking-summary-box">
            <h3 style="margin-bottom: 15px; color: #333;">
                <i class="fas fa-file-invoice"></i> สรุปการจอง
            </h3>
            
            <div class="summary-item">
                <span>ห้องพัก</span>
                <strong><?= htmlspecialchars($room['room_type_name']) ?></strong>
            </div>
            <div class="summary-item">
                <span>เช็คอิน</span>
                <strong><?= formatThaiDate($booking['check_in']) ?></strong>
            </div>
            <div class="summary-item">
                <span>เช็คเอาท์</span>
                <strong><?= formatThaiDate($booking['check_out']) ?></strong>
            </div>
            <div class="summary-item">
                <span>จำนวนคืน</span>
                <strong><?= $booking['nights'] ?> คืน</strong>
            </div>
            <div class="summary-item">
                <span>ผู้เข้าพัก</span>
                <strong><?= $booking['adults'] ?> ผู้ใหญ่<?= $booking['children'] > 0 ? ', ' . $booking['children'] . ' เด็ก' : '' ?></strong>
            </div>
            
            <div class="summary-item total">
                <span>ยอดชำระทั้งหมด</span>
                <span>฿<?= number_format($pricing['grand_total'], 0) ?></span>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <form id="payment-form">
            <div class="payment-methods">
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="creditcard" checked>
                    <div class="method-icon">
                        <i class="fas fa-credit-card" style="color: #667eea;"></i>
                    </div>
                    <div class="method-info">
                        <h4><?php _e('payment.credit_card'); ?></h4>
                        <p><?php _e('payment.credit_card_desc'); ?></p>
                    </div>
                </label>
                
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="promptpay">
                    <div class="method-icon">
                        <i class="fas fa-mobile-alt" style="color: #1e88e5;"></i>
                    </div>
                    <div class="method-info">
                        <h4><?php _e('payment.promptpay'); ?></h4>
                        <p><?php _e('payment.promptpay_desc'); ?></p>
                    </div>
                </label>
                
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="internetbanking">
                    <div class="method-icon">
                        <i class="fas fa-university" style="color: #43a047;"></i>
                    </div>
                    <div class="method-info">
                        <h4><?php _e('payment.internet_banking'); ?></h4>
                        <p><?php _e('payment.internet_banking_desc'); ?></p>
                    </div>
                </label>
            </div>
            
            <!-- Credit Card Form -->
            <div id="card-form" class="card-form active">
                <h4 style="margin-bottom: 20px;"><?php _e('payment.credit_card'); ?></h4>
                
                <div class="form-group">
                    <label><?php _e('payment.card_holder_name'); ?></label>
                    <input type="text" 
                           id="card-holder-name" 
                           placeholder="JOHN DOE"
                           required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('payment.card_number'); ?></label>
                    <input type="text" 
                           id="card-number" 
                           placeholder="1234 5678 9012 3456"
                           maxlength="19"
                           required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php _e('payment.expiry_date'); ?></label>
                        <input type="text" 
                               id="expiry-date" 
                               placeholder="MM/YY"
                               maxlength="5"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label><?php _e('payment.cvv'); ?></label>
                        <input type="text" 
                               id="cvv" 
                               placeholder="123"
                               maxlength="4"
                               required>
                    </div>
                </div>
            </div>
            
            <div class="secure-badge">
                <i class="fas fa-lock"></i>
                <span><?php _e('payment.secure_payment'); ?></span>
            </div>
            
            <button type="submit" id="btn-pay" class="btn-pay">
                <i class="fas fa-lock"></i> <?php _e('payment.pay_amount'); ?> ฿<?= number_format($pricing['grand_total'], 0) ?>
            </button>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <h3>กำลังประมวลผลการชำระเงิน...</h3>
        <p>กรุณารอสักครู่</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('payment-form');
    const btnPay = document.getElementById('btn-pay');
    const loadingOverlay = document.getElementById('loading-overlay');
    
    // Payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const cardForm = document.getElementById('card-form');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'creditcard') {
                cardForm.classList.add('active');
            } else {
                cardForm.classList.remove('active');
            }
        });
    });
    
    // Card number formatting
    const cardNumber = document.getElementById('card-number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }
    
    // Expiry date formatting
    const expiryDate = document.getElementById('expiry-date');
    if (expiryDate) {
        expiryDate.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
    
    // Form submission
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'creditcard') {
            processCreditCardPayment();
        } else if (paymentMethod === 'promptpay') {
            processPromptPayPayment();
        } else if (paymentMethod === 'internetbanking') {
            processInternetBankingPayment();
        }
    });
    
    function processCreditCardPayment() {
        // Show loading
        btnPay.disabled = true;
        loadingOverlay.classList.add('active');
        
        // Get card details
        const cardHolderName = document.getElementById('card-holder-name').value;
        const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
        const expiryDate = document.getElementById('expiry-date').value;
        const cvv = document.getElementById('cvv').value;
        
        const [expiryMonth, expiryYear] = expiryDate.split('/');
        
        // Create Omise token
        Omise.createToken('card', {
            name: cardHolderName,
            number: cardNumber,
            expiration_month: expiryMonth,
            expiration_year: '20' + expiryYear,
            security_code: cvv
        }, function(statusCode, response) {
            if (response.object === 'error' || !response.id) {
                // Error
                loadingOverlay.classList.remove('active');
                btnPay.disabled = false;
                
                alert('เกิดข้อผิดพลาด: ' + (response.message || 'ไม่สามารถประมวลผลบัตรได้'));
            } else {
                // Success - Send token to server
                submitPayment(response.id, 'creditcard');
            }
        });
    }
    
    function processPromptPayPayment() {
        btnPay.disabled = true;
        loadingOverlay.classList.add('active');
        
        // For PromptPay, we'll create a source
        Omise.createSource('promptpay', {
            amount: <?= $amount_satang ?>,
            currency: 'THB'
        }, function(statusCode, response) {
            if (response.object === 'error' || !response.id) {
                loadingOverlay.classList.remove('active');
                btnPay.disabled = false;
                alert('เกิดข้อผิดพลาด: ' + (response.message || 'ไม่สามารถสร้าง PromptPay ได้'));
            } else {
                submitPayment(response.id, 'promptpay');
            }
        });
    }
    
    function processInternetBankingPayment() {
        btnPay.disabled = true;
        loadingOverlay.classList.add('active');
        
        // For Internet Banking
        Omise.createSource('internet_banking_scb', {
            amount: <?= $amount_satang ?>,
            currency: 'THB'
        }, function(statusCode, response) {
            if (response.object === 'error' || !response.id) {
                loadingOverlay.classList.remove('active');
                btnPay.disabled = false;
                alert('เกิดข้อผิดพลาด: ' + (response.message || 'ไม่สามารถสร้างรายการชำระเงินได้'));
            } else {
                submitPayment(response.id, 'internetbanking');
            }
        });
    }
    
    function submitPayment(token, method) {
        // Send to server
        fetch('payment-process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                payment_method: method
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to confirmation page
                window.location.href = 'booking_confirmation.php?booking_id=' + data.booking_id;
            } else {
                loadingOverlay.classList.remove('active');
                btnPay.disabled = false;
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        })
        .catch(error => {
            loadingOverlay.classList.remove('active');
            btnPay.disabled = false;
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });
    }
});
</script>

<?php require_once PROJECT_ROOT . '/includes/footer.php'; ?>
