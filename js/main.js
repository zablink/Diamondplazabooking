// Hotel Booking System - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize date inputs with minimum date as today
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        if (input.name === 'check_in' || input.name === 'check_out') {
            input.setAttribute('min', today);
        }
    });
    
    // Check-in and check-out date validation
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');
    
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const minCheckOut = new Date(checkInDate);
            minCheckOut.setDate(minCheckOut.getDate() + 1);
            checkOutInput.setAttribute('min', minCheckOut.toISOString().split('T')[0]);
            
            // Clear checkout if it's before check-in
            if (checkOutInput.value && new Date(checkOutInput.value) <= checkInDate) {
                checkOutInput.value = '';
            }
        });
    }
    
    // Calculate and display number of nights
    if (checkInInput && checkOutInput) {
        function calculateNights() {
            if (checkInInput.value && checkOutInput.value) {
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                
                const nightsDisplay = document.getElementById('nights-display');
                if (nightsDisplay && nights > 0) {
                    nightsDisplay.textContent = nights + ' คืน';
                }
                
                // Update total price if on booking page
                updateTotalPrice();
            }
        }
        
        checkInInput.addEventListener('change', calculateNights);
        checkOutInput.addEventListener('change', calculateNights);
    }
    
    // Update total price calculation
    function updateTotalPrice() {
        const roomPrice = document.getElementById('room-price');
        const numRooms = document.getElementById('num-rooms');
        const totalPriceElement = document.getElementById('total-price');
        
        if (roomPrice && numRooms && totalPriceElement && checkInInput && checkOutInput) {
            const price = parseFloat(roomPrice.value);
            const rooms = parseInt(numRooms.value);
            
            if (checkInInput.value && checkOutInput.value) {
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                
                if (nights > 0) {
                    const total = price * rooms * nights;
                    totalPriceElement.textContent = '฿' + total.toLocaleString();
                }
            }
        }
    }
    
    // Number of rooms change
    const numRoomsInput = document.getElementById('num-rooms');
    if (numRoomsInput) {
        numRoomsInput.addEventListener('change', updateTotalPrice);
    }
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    
                    // Add error message
                    if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('error-message')) {
                        const errorMsg = document.createElement('span');
                        errorMsg.className = 'error-message';
                        errorMsg.style.color = 'red';
                        errorMsg.style.fontSize = '0.85rem';
                        errorMsg.textContent = 'กรุณากรอกข้อมูลนี้';
                        input.parentNode.insertBefore(errorMsg, input.nextSibling);
                    }
                } else {
                    input.classList.remove('error');
                    const errorMsg = input.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Auto-hide flash messages
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    });
    
    // Image gallery lightbox (simple version)
    const galleryImages = document.querySelectorAll('.gallery-item img, .gallery-main img');
    galleryImages.forEach(img => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            const lightbox = document.createElement('div');
            lightbox.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: pointer;
            `;
            
            const imgClone = this.cloneNode();
            imgClone.style.cssText = 'max-width: 90%; max-height: 90%; object-fit: contain;';
            
            lightbox.appendChild(imgClone);
            document.body.appendChild(lightbox);
            
            lightbox.addEventListener('click', () => lightbox.remove());
        });
    });
    
    // Search form city autocomplete (simple version)
    const cityInput = document.querySelector('input[name="city"]');
    if (cityInput) {
        const cities = ['Bangkok', 'Phuket', 'Chiang Mai', 'Pattaya', 'Krabi', 'Koh Samui', 'Hua Hin'];
        
        cityInput.addEventListener('input', function() {
            // Remove existing datalist
            let datalist = document.getElementById('cities-datalist');
            if (!datalist) {
                datalist = document.createElement('datalist');
                datalist.id = 'cities-datalist';
                this.parentNode.appendChild(datalist);
                this.setAttribute('list', 'cities-datalist');
            }
            
            datalist.innerHTML = '';
            const value = this.value.toLowerCase();
            
            cities.forEach(city => {
                if (city.toLowerCase().includes(value)) {
                    const option = document.createElement('option');
                    option.value = city;
                    datalist.appendChild(option);
                }
            });
        });
    }
    
    // Wishlist functionality
    const wishlistBtns = document.querySelectorAll('.wishlist-btn');
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const hotelId = this.dataset.hotelId;
            
            fetch('wishlist_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `hotel_id=${hotelId}&action=toggle`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    if (this.classList.contains('active')) {
                        icon.className = 'fas fa-heart';
                    } else {
                        icon.className = 'far fa-heart';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Star rating input (for reviews)
    const starRatingInputs = document.querySelectorAll('.star-rating-input');
    starRatingInputs.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const input = container.querySelector('input[name="rating"]');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = index + 1;
                input.value = rating;
                
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });
    });
    
    // Booking confirmation
    const bookingForms = document.querySelectorAll('.booking-form[data-confirm]');
    bookingForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('ยืนยันการจองห้องพักนี้หรือไม่?')) {
                e.preventDefault();
            }
        });
    });
    
    // Cancel booking confirmation
    const cancelBtns = document.querySelectorAll('.cancel-booking-btn');
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('คุณต้องการยกเลิกการจองนี้หรือไม่? การยกเลิกไม่สามารถย้อนกลับได้')) {
                e.preventDefault();
            }
        });
    });
});

// Helper function to format currency
function formatCurrency(amount) {
    return '฿' + parseFloat(amount).toLocaleString('th-TH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('th-TH', options);
}
