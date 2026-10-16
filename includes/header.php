<?php
// Every page may optionally set $page_title, $page_description and $page_image
// before including this file; sensible site-wide defaults are used otherwise
// so no page is ever left with a blank or duplicate meta description.
$_seoTitle = (isset($page_title) && $page_title ? $page_title . ' - ' : '') . getSiteName();
$_seoDesc = (isset($page_description) && $page_description)
    ? $page_description
    : getSiteName() . ' — Excellence in Education. Explore our programs, admissions, faculty and campus life.';
$_seoImage = rtrim(SITE_URL, '/') . '/' . ltrim((isset($page_image) && $page_image) ? $page_image : getLogoPath(), '/');
$_seoCanonical = rtrim(SITE_URL, '/') . '/' . basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($_seoDesc); ?>">
    <meta name="keywords" content="iqra school and college daggar, iqra daggar buner, school in buner, college in buner, matric, f.sc pre-medical, f.sc pre-engineering, ics, fa">
    <meta name="author" content="<?php echo htmlspecialchars(getSiteName()); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($_seoCanonical); ?>">
    <title><?php echo htmlspecialchars($_seoTitle); ?></title>

    <!-- Open Graph / social sharing -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(getSiteName()); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($_seoTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($_seoDesc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($_seoCanonical); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($_seoImage); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($_seoTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($_seoDesc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($_seoImage); ?>">

    <!-- Google Fonts: Poppins (headings, navigation, buttons, labels) + Inter (body text) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">

    <!-- Dynamic Theme Colors -->
    <style>
        <?php echo getThemeCSS(); ?>
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo getLogoPath(); ?>">
</head>
<body>
    <!-- Header -->
    <header id="main-header">

        <!-- ===== BRAND BAR (logo, contact, primary actions) ===== -->
        <div class="brand-bar">
            <div class="container">
                <!-- Left: Logo + Name + Tagline -->
                <a href="index.php" class="brand-identity">
                    <img src="<?php echo getLogoPath(); ?>" alt="<?php echo getSiteName(); ?> Logo" onerror="this.style.display='none'">
                    <div class="brand-text">
                        <span class="brand-name"><?php echo getSiteName(); ?></span>
                        <span class="brand-tagline">Excellence in Education</span>
                    </div>
                </a>

                <!-- Right: Contact + Social + Campus Login + Apply Now -->
                <div class="brand-right">
                    <?php
                    $phone = getSitePhone();
                    $phone_numbers = explode(',', $phone);
                    $first_phone = trim($phone_numbers[0]);
                    $first_phone_href = str_replace([' ', '-'], '', $first_phone);
                    ?>
                    <div class="brand-contact">
                        <a href="tel:<?php echo $first_phone_href; ?>">
                            <span class="brand-contact-icon"><i class="fas fa-phone-alt"></i></span>
                            <span class="brand-contact-text"><small>Call Us</small><?php echo $first_phone; ?></span>
                        </a>
                        <span class="brand-contact-sep"></span>
                        <a href="mailto:<?php echo getSiteEmail(); ?>">
                            <span class="brand-contact-icon"><i class="fas fa-envelope"></i></span>
                            <span class="brand-contact-text"><small>Email Us</small><?php echo getSiteEmail(); ?></span>
                        </a>
                    </div>
                    <div class="brand-actions">
                        <?php
                        $social  = getSocialMedia();
                        $fb_url  = !empty($social['facebook'])  ? htmlspecialchars($social['facebook'])  : '#';
                        $ig_url  = !empty($social['instagram']) ? htmlspecialchars($social['instagram']) : '#';
                        $yt_url  = !empty($social['youtube'])   ? htmlspecialchars($social['youtube'])   : '#';
                        $fb_target = $fb_url !== '#' ? 'target="_blank"' : '';
                        $ig_target = $ig_url !== '#' ? 'target="_blank"' : '';
                        $yt_target = $yt_url !== '#' ? 'target="_blank"' : '';
                        ?>
                        <div class="brand-social">
                            <a href="<?php echo $fb_url; ?>" <?php echo $fb_target; ?> title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="<?php echo $ig_url; ?>" <?php echo $ig_target; ?> title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="<?php echo $yt_url; ?>" <?php echo $yt_target; ?> title="YouTube"><i class="fab fa-youtube"></i></a>
                            <?php if (!empty($social['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($social['twitter']); ?>" target="_blank" title="Twitter"><i class="fab fa-x-twitter"></i></a>
                            <?php endif; ?>
                        </div>
                        <a href="campus-portal.php" class="campus-login-btn">
                            <i class="fas fa-sign-in-alt"></i> Campus Login
                        </a>
                        <a href="admission.php" class="apply-now-btn">
                            Apply Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Mobile Hamburger -->
                    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-controls="navLinks" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== NAVIGATION BAR ===== -->
        <nav id="main-nav" aria-label="Main navigation">
            <div class="container">
                <ul class="nav-links" id="navLinks">
                    <?php foreach (getNavMenu() as $nav_item): ?>
                        <?php if (!empty($nav_item['children'])): ?>
                        <li class="dropdown">
                            <a href="<?php echo htmlspecialchars($nav_item['url']); ?>" class="dropdown-toggle"<?php echo $nav_item['open_new_tab'] ? ' target="_blank"' : ''; ?>>
                                <i class="fas <?php echo htmlspecialchars($nav_item['icon']); ?>"></i>
                                <span><?php echo htmlspecialchars($nav_item['label']); ?> <i class="fas fa-chevron-down chev"></i></span>
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($nav_item['children'] as $child): ?>
                                <li><a href="<?php echo htmlspecialchars($child['url']); ?>"<?php echo $child['open_new_tab'] ? ' target="_blank"' : ''; ?>><i class="fas <?php echo htmlspecialchars($child['icon']); ?>"></i> <?php echo htmlspecialchars($child['label']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php else: ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($nav_item['url']); ?>"<?php echo $nav_item['open_new_tab'] ? ' target="_blank"' : ''; ?>>
                                <i class="fas <?php echo htmlspecialchars($nav_item['icon']); ?>"></i>
                                <span><?php echo htmlspecialchars($nav_item['label']); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Mobile-only: actions + contact shown at the bottom of the menu panel -->
                    <li class="nav-mobile-extras">
                        <div class="nav-mobile-actions">
                            <a href="admission.php" class="apply-now-btn">Apply Now <i class="fas fa-arrow-right"></i></a>
                            <a href="campus-portal.php" class="campus-login-btn"><i class="fas fa-sign-in-alt"></i> Campus Login</a>
                        </div>
                        <div class="nav-mobile-contact">
                            <a href="tel:<?php echo $first_phone_href; ?>"><i class="fas fa-phone-alt"></i> <?php echo $first_phone; ?></a>
                            <a href="mailto:<?php echo getSiteEmail(); ?>"><i class="fas fa-envelope"></i> <?php echo getSiteEmail(); ?></a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

    </header>

    <script>
    (function() {
        var header  = document.getElementById('main-header');
        var toggle  = document.getElementById('menuToggle');
        var navList = document.getElementById('navLinks');
        var MOBILE_BP = 1024; // keep in sync with the header @media breakpoint in css/style.css

        // Scroll elevation + compact brand bar (hysteresis avoids flicker
        // when the shrinking header changes the page height)
        window.addEventListener('scroll', function() {
            var y = window.scrollY;
            if (y > 60) header.classList.add('scrolled');
            else if (y < 10) header.classList.remove('scrolled');
        }, { passive: true });

        function setMenu(open) {
            toggle.classList.toggle('active', open);
            navList.classList.toggle('active', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            document.documentElement.classList.toggle('nav-open', open);
            if (!open) {
                document.querySelectorAll('#navLinks .dropdown.open').forEach(function(d) {
                    d.classList.remove('open');
                });
            }
        }

        // Hamburger toggle
        if (toggle) {
            toggle.addEventListener('click', function() {
                setMenu(!navList.classList.contains('active'));
            });
        }

        // Mobile: dropdown click toggle (accordion — one section open at a time)
        document.querySelectorAll('#navLinks .dropdown > a').forEach(function(a) {
            a.setAttribute('aria-haspopup', 'true');
            a.addEventListener('click', function(e) {
                if (window.innerWidth <= MOBILE_BP) {
                    e.preventDefault();
                    var li = this.closest('.dropdown');
                    var willOpen = !li.classList.contains('open');
                    document.querySelectorAll('#navLinks .dropdown.open').forEach(function(d) {
                        if (d !== li) d.classList.remove('open');
                    });
                    li.classList.toggle('open', willOpen);
                }
            });
        });

        // Close nav when clicking outside, pressing Escape, or resizing to desktop
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#main-header')) setMenu(false);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navList.classList.contains('active')) {
                setMenu(false);
                toggle.focus();
            }
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth > MOBILE_BP && navList.classList.contains('active')) setMenu(false);
        });

        // Highlight active nav link (top-level link, or dropdown toggle whose submenu contains the current page)
        var path = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('#navLinks > li').forEach(function(li) {
            var topLink = li.querySelector(':scope > a');
            var subLinks = li.querySelectorAll('.dropdown-menu a');
            var isActive = false;

            if (topLink) {
                var href = (topLink.getAttribute('href') || '').split('?')[0];
                if (href === path || (path === '' && href === 'index.php')) isActive = true;
            }
            subLinks.forEach(function(sub) {
                var subHref = (sub.getAttribute('href') || '').split('?')[0];
                if (subHref === path) {
                    isActive = true;
                    sub.classList.add('active');
                    sub.setAttribute('aria-current', 'page');
                }
            });

            if (isActive && topLink) {
                topLink.classList.add('active');
            }
        });
    })();
    </script>
