<?php
// includes/footer.php
// ไฟล์ Footer สำหรับทุกหน้า - รองรับหลายภาษา
?>
    <style>
        .footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: white;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.8rem;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-section p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 1.5rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
    </style>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <!-- Quick Links -->
                <div class="footer-section">
                    <h3><i class="fas fa-link"></i> <?php _e('footer.quick_links'); ?></h3>
                    <ul>
                        <?php 
                        // ลิงก์หน้าโฮม - กลับไปหน้าหลักของเว็บไซต์ (ไม่ใช่ส่วน booking)
                        $mainSiteUrl = defined('MAIN_SITE_URL') ? MAIN_SITE_URL : str_replace('/booking', '', SITE_URL);
                        ?>
                        <li><a href="<?= htmlspecialchars($mainSiteUrl) ?>"><?php _e('nav.home'); ?></a></li>
                        <li><a href="<?php echo url('search.php'); ?>"><?php _e('nav.search'); ?></a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo url('my_bookings.php'); ?>"><?php _e('nav.my_bookings'); ?></a></li>
                            <li><a href="<?php echo url('profile.php'); ?>"><?php _e('nav.profile'); ?></a></li>
                        <?php else: ?>
                            <li><a href="<?php echo url('login.php'); ?>"><?php _e('nav.login'); ?></a></li>
                            <li><a href="<?php echo url('register.php'); ?>"><?php _e('nav.register'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- About -->
                <div class="footer-section">
                    <h3><i class="fas fa-info-circle"></i> <?php _e('footer.about'); ?></h3>
                    <?php 
                    // ดึงข้อความ About จาก hotel settings ก่อน ถ้าไม่มีค่อยใช้ translation key
                    $hotel = getHotelSettings();
                    $currentLang = getCurrentLanguage();
                    $aboutText = '';
                    
                    // ลองดึงจาก hotel settings ตามภาษา
                    if (!empty($hotel['about_description_' . $currentLang])) {
                        $aboutText = $hotel['about_description_' . $currentLang];
                    } elseif (!empty($hotel['about_description'])) {
                        $aboutText = $hotel['about_description'];
                    } else {
                        // ถ้าไม่มีใน hotel settings ให้ใช้ translation key
                        $aboutText = __('footer.about_description');
                    }
                    ?>
                    <p><?= nl2br(htmlspecialchars($aboutText)) ?></p>
                </div>

                <!-- Contact -->
                <div class="footer-section">
                    <h3><i class="fas fa-envelope"></i> <?php _e('footer.contact_us'); ?></h3>
                    <ul>
                        <?php 
                        // ดึงข้อมูลโรงแรมตามภาษาที่เลือก (รองรับหลายภาษา)
                        $hotel = getHotelSettings();
                        $currentLang = getCurrentLanguage();
                        
                        // ดึง address และ city ตามภาษา
                        $address = !empty($hotel['address_' . $currentLang]) ? $hotel['address_' . $currentLang] : 
                                  (!empty($hotel['address']) ? $hotel['address'] : __('footer.default_address'));
                        $city = !empty($hotel['city_' . $currentLang]) ? $hotel['city_' . $currentLang] : 
                               (!empty($hotel['city']) ? $hotel['city'] : '');
                        $fullAddress = $address . ($city ? ', ' . $city : '');
                        ?>
                        <li><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($fullAddress ?: __('footer.default_address')) ?></li>
                        <li><i class="fas fa-phone"></i> <?= htmlspecialchars($hotel['phone'] ?: __('footer.default_phone')) ?></li>
                        <li><i class="fas fa-envelope"></i> <?= htmlspecialchars($hotel['email'] ?: __('footer.default_email')) ?></li>
                    </ul>
                </div>

                <!-- Social -->
                <div class="footer-section">
                    <h3><i class="fas fa-share-alt"></i> <?php _e('footer.follow_us'); ?></h3>
                    <div class="social-links">
                        <?php 
                        // ดึง social media links จาก hotel settings หรือใช้ default
                        $hotel = getHotelSettings();
                        $facebookUrl = !empty($hotel['facebook_url']) ? $hotel['facebook_url'] : 'https://www.facebook.com';
                        $instagramUrl = !empty($hotel['instagram_url']) ? $hotel['instagram_url'] : 'https://www.instagram.com';
                        $twitterUrl = !empty($hotel['twitter_url']) ? $hotel['twitter_url'] : 'https://www.twitter.com';
                        $linkedinUrl = !empty($hotel['linkedin_url']) ? $hotel['linkedin_url'] : 'https://www.linkedin.com';
                        ?>
                        <a href="<?= htmlspecialchars($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php _e('footer.social_facebook'); ?>"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?= htmlspecialchars($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php _e('footer.social_instagram'); ?>"><i class="fab fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($twitterUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php _e('footer.social_twitter'); ?>"><i class="fab fa-twitter"></i></a>
                        <a href="<?= htmlspecialchars($linkedinUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php _e('footer.social_linkedin'); ?>"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getHotelName()); ?>. <?php _e('footer.all_rights_reserved'); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>
