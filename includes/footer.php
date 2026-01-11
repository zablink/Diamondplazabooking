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
                        <li><a href="index.php"><?php _e('nav.home'); ?></a></li>
                        <li><a href="search.php"><?php _e('nav.search'); ?></a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="my_bookings.php"><?php _e('nav.my_bookings'); ?></a></li>
                            <li><a href="profile.php"><?php _e('nav.profile'); ?></a></li>
                        <?php else: ?>
                            <li><a href="login.php"><?php _e('nav.login'); ?></a></li>
                            <li><a href="register.php"><?php _e('nav.register'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- About -->
                <div class="footer-section">
                    <h3><i class="fas fa-info-circle"></i> <?php _e('footer.about'); ?></h3>
                    <p><?php _e('home.about_description'); ?></p>
                </div>

                <!-- Contact -->
                <div class="footer-section">
                    <h3><i class="fas fa-envelope"></i> <?php _e('footer.contact_us'); ?></h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Bangkok, Thailand</li>
                        <li><i class="fas fa-phone"></i> +66 2 XXX XXXX</li>
                        <li><i class="fas fa-envelope"></i> info@hotelbooking.com</li>
                    </ul>
                </div>

                <!-- Social -->
                <div class="footer-section">
                    <h3><i class="fas fa-share-alt"></i> <?php _e('footer.follow_us'); ?></h3>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Hotel Booking System'; ?>. <?php _e('footer.all_rights_reserved'); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>
