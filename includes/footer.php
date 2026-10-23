    <!-- Footer -->
    <?php
    $ft_social = getSocialMedia();
    $ft_social_links = [
        'facebook'  => ['fab fa-facebook-f', 'Facebook'],
        'instagram' => ['fab fa-instagram',  'Instagram'],
        'youtube'   => ['fab fa-youtube',    'YouTube'],
        'twitter'   => ['fab fa-x-twitter',  'Twitter'],
    ];
    $ft_has_social = false;
    foreach ($ft_social_links as $key => $meta) {
        if (!empty($ft_social[$key])) { $ft_has_social = true; break; }
    }
    $ft_phone_numbers = array_filter(array_map('trim', explode(',', getSitePhone())));
    ?>
    <footer class="site-footer">
        <div class="container">
            <div class="ft-grid">
                <!-- About -->
                <div class="ft-col ft-brand">
                    <a href="index.php" class="ft-logo">
                        <img src="<?php echo getLogoPath(); ?>" alt="<?php echo htmlspecialchars(getSiteName()); ?> logo" loading="lazy" onerror="this.style.display='none'">
                        <span><?php echo getSiteName(); ?></span>
                    </a>
                    <p class="ft-about"><?php echo htmlspecialchars(getFooterContent()['about_text']); ?></p>
                    <?php if ($ft_has_social): ?>
                    <div class="ft-social" aria-label="Social media">
                        <?php foreach ($ft_social_links as $key => $meta): if (empty($ft_social[$key])) continue; ?>
                        <a href="<?php echo htmlspecialchars($ft_social[$key]); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo $meta[1]; ?>" aria-label="<?php echo $meta[1]; ?>"><i class="<?php echo $meta[0]; ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Links -->
                <nav class="ft-col" aria-label="Quick links">
                    <h3>Quick Links</h3>
                    <ul class="ft-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="courses.php">Our Courses</a></li>
                        <li><a href="admission.php">Admission</a></li>
                        <li><a href="faculty.php">Our Faculty</a></li>
                        <li><a href="events.php">Events &amp; News</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="alumni.php">Alumni Portal</a></li>
                        <?php $ft_eduportal = getEduPortalLinks(); ?>
                        <li><a href="parents-login.php">Parents Login</a></li>
                        <?php if ($ft_eduportal['admin']): ?>
                        <li><a href="<?php echo htmlspecialchars($ft_eduportal['admin']); ?>" target="_blank" rel="noopener noreferrer">Admin Portal (EduPortal)</a></li>
                        <?php endif; ?>
                        <li><a href="AdminCP/login.php?next=manage_support_staff.php" rel="nofollow">Support Staff (Staff Login)</a></li>
                    </ul>
                </nav>

                <!-- Programs -->
                <nav class="ft-col" aria-label="Programs">
                    <h3>Programs</h3>
                    <ul class="ft-links">
                        <li><a href="courses.php">Matric (SSC)</a></li>
                        <li><a href="courses.php">F.Sc Pre-Medical</a></li>
                        <li><a href="courses.php">F.Sc Pre-Engineering</a></li>
                        <li><a href="courses.php">ICS (Computer Science)</a></li>
                        <li><a href="courses.php">F.A (Arts &amp; Humanities)</a></li>
                        <li><a href="examination.php">Examination Info</a></li>
                        <li><a href="downloads.php">Downloads</a></li>
                    </ul>
                </nav>

                <!-- Contact -->
                <div class="ft-col">
                    <h3>Contact Us</h3>
                    <ul class="ft-contact">
                        <li>
                            <span class="ft-ico" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                            <span><span class="ft-label">Address</span><?php echo nl2br(htmlspecialchars(getSiteAddress())); ?></span>
                        </li>
                        <?php if (!empty($ft_phone_numbers)): ?>
                        <li>
                            <span class="ft-ico" aria-hidden="true"><i class="fas fa-phone-alt"></i></span>
                            <span>
                                <span class="ft-label">Phone</span>
                                <?php foreach ($ft_phone_numbers as $number): ?>
                                <a href="tel:<?php echo str_replace([' ', '-'], '', $number); ?>"><?php echo htmlspecialchars($number); ?></a>
                                <?php endforeach; ?>
                            </span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <span class="ft-ico" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                            <span><span class="ft-label">Email</span><a href="mailto:<?php echo getSiteEmail(); ?>"><?php echo getSiteEmail(); ?></a></span>
                        </li>
                        <li>
                            <span class="ft-ico" aria-hidden="true"><i class="fas fa-clock"></i></span>
                            <span><span class="ft-label">Office Hours</span><?php echo htmlspecialchars(getOfficeHours()); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="ft-bottom">
            <div class="container ft-bottom-inner">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSiteName(); ?>. All rights reserved.</p>
                <p>Designed by <span class="ft-credit">Computech</span></p>
            </div>
        </div>
    </footer>

    <!-- Analytics Tracker -->
    <?php
    if (file_exists(__DIR__ . '/analytics_tracker.php')) {
        include __DIR__ . '/analytics_tracker.php';
    }
    ?>

    <!-- JavaScript -->
    <script src="js/main.js?v=<?php echo @filemtime(__DIR__ . '/../js/main.js'); ?>" defer></script>
</body>
</html>
