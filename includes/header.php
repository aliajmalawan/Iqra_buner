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
    <!-- Header: two tiers (white brand/contact bar, navy centred menu below), sticky on every page.
         Layout mirrors the Tameer-e-Nau header, in IQRA's navy / cyan / gold colours. -->
    <?php
    $hx_page  = basename($_SERVER['SCRIPT_NAME']);
    $hx_phone = trim(explode(',', getSitePhone())[0]);
    $hx_email = getSiteEmail();
    $hx_social = array_filter(getSocialMedia());
    $hx_menu  = getNavMenu();
    $hx_url_page = function ($url) { return basename(strtok((string) $url, '?#')); };
    ?>
    <header class="hx-header" id="site-header">
        <div class="hx-topbar">
            <div class="container hx-topbar-inner">
                <a class="hx-brand" href="index.php" aria-label="<?php echo htmlspecialchars(getSiteName()); ?> - Home">
                    <img src="<?php echo getLogoPath(); ?>" alt="" class="hx-brand-logo" width="56" height="56" onerror="this.style.display='none'">
                    <span class="hx-brand-text">
                        <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                        <small><?php echo htmlspecialchars(getSetting('header_tagline', 'Daggar, District Buner')); ?></small>
                    </span>
                </a>

                <div class="hx-info-group">
                    <?php if ($hx_phone !== ''): ?>
                    <a class="hx-info" href="tel:<?php echo htmlspecialchars(preg_replace('/[^+0-9]/', '', $hx_phone)); ?>">
                        <span class="hx-info-icon"><i class="fas fa-phone" aria-hidden="true"></i></span>
                        <span><small>Call Us</small><strong><?php echo htmlspecialchars($hx_phone); ?></strong></span>
                    </a>
                    <?php endif; ?>
                    <?php if ($hx_email !== ''): ?>
                    <a class="hx-info" href="mailto:<?php echo htmlspecialchars($hx_email); ?>">
                        <span class="hx-info-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                        <span><small>Email Us</small><strong><?php echo htmlspecialchars($hx_email); ?></strong></span>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="hx-actions">
                    <?php if ($hx_social): ?>
                    <nav class="hx-social" aria-label="Social media">
                        <?php foreach (['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-x-twitter'] as $net => $ico):
                            if (empty($hx_social[$net])) continue; ?>
                        <a href="<?php echo htmlspecialchars($hx_social[$net]); ?>" target="_blank" rel="noopener" aria-label="<?php echo ucfirst($net); ?>"><i class="fab <?php echo $ico; ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </nav>
                    <?php endif; ?>

                    <a class="hx-apply-btn" href="admission.php"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Apply Online</a>

                    <button type="button" class="hx-toggle" id="hx-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="hx-nav">
                        <span class="hx-toggle-bar"></span>
                        <span class="hx-toggle-bar"></span>
                        <span class="hx-toggle-bar"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="hx-backdrop" id="hx-backdrop"></div>

        <nav class="hx-navbar" aria-label="Primary navigation">
            <div class="container hx-navbar-inner">
                <div class="hx-nav" id="hx-nav">
                    <?php foreach ($hx_menu as $item):
                        $ext = !empty($item['open_new_tab']) ? ' target="_blank" rel="noopener"' : '';
                        if (empty($item['children'])):
                            $on = $hx_url_page($item['url']) === $hx_page; ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>"<?php echo $ext; ?><?php echo $on ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo htmlspecialchars($item['label']); ?></a>
                    <?php else:
                            $group_on = false;
                            foreach ($item['children'] as $ch) { if ($hx_url_page($ch['url']) === $hx_page) $group_on = true; } ?>
                    <div class="hx-dropdown<?php echo $group_on ? ' is-active' : ''; ?>">
                        <span class="hx-dropdown-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false"><?php echo htmlspecialchars($item['label']); ?> <i class="fas fa-chevron-down hx-caret" aria-hidden="true"></i></span>
                        <div class="hx-dropdown-panel">
                            <?php foreach ($item['children'] as $ch):
                                $c_on = $hx_url_page($ch['url']) === $hx_page;
                                $c_ext = !empty($ch['open_new_tab']) ? ' target="_blank" rel="noopener"' : ''; ?>
                            <a href="<?php echo htmlspecialchars($ch['url']); ?>"<?php echo $c_ext; ?><?php echo $c_on ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo htmlspecialchars($ch['label']); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>

                    <a class="btn btn-accent hx-drawer-apply" href="admission.php">Apply Online</a>
                </div>
            </div>
        </nav>
    </header>

    <script>
    // Header: shadow on scroll, mobile drawer + dropdowns (same behaviour as the Tameer-e-Nau header)
    (function () {
        var header = document.getElementById('site-header');
        var toggle = document.getElementById('hx-toggle');
        var nav = document.getElementById('hx-nav');
        var backdrop = document.getElementById('hx-backdrop');
        var DRAWER_MAX = 900;   // keep in sync with the @media (max-width: 900px) header rules in css/style.css

        function shadow() { header.classList.toggle('is-scrolled', window.scrollY > 10); }
        shadow();
        window.addEventListener('scroll', shadow, { passive: true });
        if (!toggle || !nav) return;

        function setOpen(open) {
            nav.classList.toggle('is-open', open);
            toggle.classList.toggle('is-open', open);
            if (backdrop) backdrop.classList.toggle('is-open', open);
            header.classList.toggle('nav-open', open);   // lifts the brand bar so the close (X) button stays visible
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Toggle menu');
            document.body.style.overflow = open ? 'hidden' : '';
            if (!open) nav.querySelectorAll('.hx-dropdown').forEach(function (d) { d.classList.remove('is-open'); });
        }
        toggle.addEventListener('click', function () { setOpen(!nav.classList.contains('is-open')); });
        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });

        // Dropdown groups expand inline inside the drawer; on desktop they open on hover/focus (CSS)
        nav.querySelectorAll('.hx-dropdown-trigger').forEach(function (trigger) {
            var group = trigger.closest('.hx-dropdown');
            function toggleGroup() {
                if (window.innerWidth > DRAWER_MAX) return;
                var open = !group.classList.contains('is-open');
                group.classList.toggle('is-open', open);
                trigger.setAttribute('aria-expanded', String(open));
            }
            trigger.addEventListener('click', toggleGroup);
            trigger.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleGroup(); }
            });
        });

        // Close the drawer after choosing a link, on Escape, or when resizing up to desktop
        nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); toggle.focus(); } });
        window.addEventListener('resize', function () { if (window.innerWidth > DRAWER_MAX) setOpen(false); });

        // Open the current page's group inside the drawer
        if (window.innerWidth <= DRAWER_MAX) {
            var active = nav.querySelector('.hx-dropdown.is-active');
            if (active) active.classList.add('is-open');
        }
    })();
    </script>
