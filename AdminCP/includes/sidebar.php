<?php
/**
 * Shared AdminCP sidebar — included on every admin page so navigation stays
 * fixed and identical everywhere. Expects $conn / SITE_NAME (from config.php)
 * and an active session; highlights the current page automatically.
 * On screens ≤1024px it becomes a slide-out drawer opened from a top bar.
 */
require_once __DIR__ . '/csrf.php';
$__current_page = basename($_SERVER['SCRIPT_NAME']);

$__nav_groups = [
    'Overview' => [
        ['dashboard.php',            'fa-gauge-high',          'Dashboard'],
        ['analytics.php',            'fa-chart-line',          'Website Analytics'],
    ],
    'Website Content' => [
        ['manage_home.php',          'fa-house',               'Home Page'],
        ['manage_about.php',         'fa-circle-info',         'About'],
        ['manage_academics.php',     'fa-graduation-cap',      'Academics'],
        ['manage_student_life.php',  'fa-users',               'Student Life'],
        ['manage_gallery.php',       'fa-images',              'Gallery'],
        ['manage_contact.php',       'fa-envelope',            'Contact'],
    ],
    'Admissions' => [
        ['manage_admission.php',     'fa-user-graduate',       'Admission'],
        ['manage_students.php',      'fa-id-card',             'Students'],
    ],
    'System' => [
        ['menu_builder.php',         'fa-bars',                'Menu Builder'],
        ['settings.php',             'fa-gear',                'Settings'],
        ['theme_manager.php',        'fa-palette',             'Theme Manager'],
        ['manage_erp.php',           'fa-network-wired',       'ERP Integration'],
    ],
];
$__logo = getLogoPath();
?>
<!-- Mobile / tablet top bar (hidden on desktop) -->
<header class="admin-topbar">
    <button type="button" id="adminNavToggle" aria-controls="adminSidebar" aria-expanded="false" aria-label="Open menu">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <span class="admin-topbar-title"><?php echo htmlspecialchars(getSiteName()); ?></span>
    <a href="../index.php" target="_blank" rel="noopener" aria-label="View website" title="View website">
        <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
    </a>
</header>
<div class="admin-overlay" id="adminOverlay" aria-hidden="true"></div>

<aside class="sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="sidebar-header">
        <?php if ($__logo): ?>
        <img src="../<?php echo htmlspecialchars(ltrim($__logo, '/')); ?>" alt="" class="sidebar-logo" onerror="this.style.display='none'">
        <?php endif; ?>
        <div class="sidebar-brand">
            <h2><?php echo htmlspecialchars(getSiteName()); ?></h2>
            <p>Admin Panel</p>
        </div>
    </div>

    <nav class="sidebar-scroll">
        <?php foreach ($__nav_groups as $__group => $__items): ?>
        <div class="sidebar-group">
            <div class="sidebar-group-label"><?php echo $__group; ?></div>
            <div class="sidebar-menu">
                <?php foreach ($__items as [$href, $icon, $label]): $__is_active = $__current_page === $href; ?>
                <a href="<?php echo $href; ?>" class="<?php echo $__is_active ? 'active' : ''; ?>"<?php echo $__is_active ? ' aria-current="page"' : ''; ?>>
                    <i class="fas <?php echo $icon; ?>" aria-hidden="true"></i>
                    <span><?php echo $label; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../index.php" target="_blank" rel="noopener">
            <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span>View Website</span>
        </a>
        <a href="logout.php" class="logout-link">
            <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<script>
// CSRF protection for GET-based action links (delete/toggle/approve/etc.):
// every same-page link whose href starts with "?" gets the current session's
// CSRF token appended automatically, so no individual link markup needs to
// change. Read-only navigation links (?edit=, ?tab=...) get the token too —
// harmless, since the server only checks it for actual mutating actions.
(function() {
    var token = <?php echo json_encode(csrf_token()); ?>;
    window.__CSRF_TOKEN = token;

    function rewriteLinks() {
        document.querySelectorAll('a[href^="?"]').forEach(function(a) {
            var href = a.getAttribute('href');
            if (href.indexOf('csrf_token=') !== -1) return;
            var sep = href.length > 1 ? '&' : '';
            a.setAttribute('href', href + sep + 'csrf_token=' + encodeURIComponent(token));
        });
    }
    // The sidebar (and this script) is included near the TOP of <body>, before
    // the rest of the page's own content — including the actual delete/toggle
    // links — has been parsed. Wait for the full DOM before rewriting them.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', rewriteLinks);
    } else {
        rewriteLinks();
    }
})();

// Shell behaviour: mobile drawer + horizontally scrollable tables
(function() {
    var toggle = document.getElementById('adminNavToggle');
    var overlay = document.getElementById('adminOverlay');
    var sidebar = document.getElementById('adminSidebar');

    function setNav(open) {
        document.body.classList.toggle('admin-nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        if (open) { var first = sidebar.querySelector('a'); if (first) first.focus({ preventScroll: true }); }
    }
    toggle.addEventListener('click', function() { setNav(!document.body.classList.contains('admin-nav-open')); });
    overlay.addEventListener('click', function() { setNav(false); });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.body.classList.contains('admin-nav-open')) { setNav(false); toggle.focus(); }
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024 && document.body.classList.contains('admin-nav-open')) setNav(false);
    });
    // keep the active item in view inside a long menu
    var active = sidebar.querySelector('.sidebar-menu a.active');
    if (active) active.scrollIntoView({ block: 'nearest' });

    function wrapTables() {
        document.querySelectorAll('.main-content table').forEach(function(t) {
            var p = t.parentElement;
            if (p.classList.contains('table-scroll')) return;
            var ox = getComputedStyle(p).overflowX;
            if (ox === 'auto' || ox === 'scroll') return;
            var w = document.createElement('div');
            w.className = 'table-scroll';
            p.insertBefore(w, t); w.appendChild(t);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wrapTables);
    else wrapTables();
})();
</script>
