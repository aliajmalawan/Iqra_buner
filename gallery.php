<?php
require_once 'includes/config.php';
$page_title = 'Photo Gallery';
$page_description = 'Browse photos of campus life, events and student activities at IQRA School & College Daggar Buner.';

// Fetch gallery categories from DB (admin-managed via AdminCP > Gallery)
$gallery_categories = [];
$gcq = mysqli_query($conn, "SELECT * FROM gallery_categories WHERE status = 'active' ORDER BY display_order ASC, name ASC");
if ($gcq) {
    while ($row = mysqli_fetch_assoc($gcq)) {
        $gallery_categories[] = $row;
    }
}

// Category slugs that have dedicated badge colors in the CSS below;
// anything else (newly added categories) falls back to .cat-default
$styled_cat_slugs = ['events', 'classes', 'activities', 'achievements'];

$cat_labels = [];
$filter_tabs = ['all' => ['label' => 'All Photos', 'icon' => 'fa-border-all']];
$cat_counts = ['all' => 0];
foreach ($gallery_categories as $gc) {
    $cat_labels[$gc['slug']] = $gc['name'];
    $filter_tabs[$gc['slug']] = ['label' => $gc['name'], 'icon' => $gc['icon'] ?: 'fa-tag'];
    $cat_counts[$gc['slug']] = 0;
}

// Fetch gallery images from DB
$gallery_items = [];

$gq = "SELECT * FROM gallery WHERE status = 'active' ORDER BY display_order ASC, created_at DESC";
$gr = mysqli_query($conn, $gq);
if ($gr && mysqli_num_rows($gr) > 0) {
    while ($row = mysqli_fetch_assoc($gr)) {
        $gallery_items[] = $row;
        $cat = strtolower(trim($row['category'] ?? 'events'));
        $cat_counts['all']++;
        if (isset($cat_counts[$cat])) $cat_counts[$cat]++;
    }
}

$has_images = count($gallery_items) > 0;

// Top 3 categories by photo count, for the stats bar (always reflects the real, current category list)
$top_stat_cats = $gallery_categories;
usort($top_stat_cats, function ($a, $b) use ($cat_counts) {
    return ($cat_counts[$b['slug']] ?? 0) <=> ($cat_counts[$a['slug']] ?? 0);
});
$top_stat_cats = array_slice($top_stat_cats, 0, 3);
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== GALLERY PAGE ===== */
:root {
    --primary-color: #102A56;
    --accent-color:  #0BA6C9;
    --text-dark:     #243247;
    --bg-light:      #E9F6FA;
}

/* --- Hero --- */
.gal-hero {
    background: linear-gradient(135deg, #0B1F42 0%, #102A56 55%, #0B1F42 100%);
    padding: 62px 0 50px;
    position: relative;
    overflow: hidden;
}
.gal-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.gal-hero::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0; right: 0;
    height: 40px;
    background: #F4F8FC;
    clip-path: ellipse(55% 100% at 50% 100%);
}
.gal-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: rgba(255,255,255,0.65);
    margin-bottom: 18px;
}
.gal-breadcrumb a { color: rgba(255,255,255,0.65); text-decoration: none; }
.gal-breadcrumb a:hover { color: var(--accent-color); }
.gal-breadcrumb .sep { font-size: var(--fs-caption-sm); }
.gal-breadcrumb .cur { color: var(--accent-color); }
.gal-hero-badge {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(11,166,201,0.18);
    border: 1px solid rgba(11,166,201,0.35);
    color: #3DBBD8;
    font-size: 12px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase;
    padding: 5px 14px; border-radius: var(--radius-card);
    margin-bottom: 14px;
}
.gal-hero h1 {
    font-size: var(--fs-page-title); font-weight: 700; color: #fff;
    line-height: 1.2; margin: 0 0 10px;
}
.gal-hero h1 span {
    background: linear-gradient(90deg, var(--accent-color), #0BA6C9);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.gal-hero p {
    color: rgba(255,255,255,0.72); font-size: var(--fs-lead); max-width: 500px; margin: 0;
}

/* --- Filter Bar --- */
.gal-filter-wrap {
    background: #F4F8FC;
    padding: 0;
    position: sticky; top: 0; z-index: 50;
    border-bottom: 1px solid rgba(16,42,86,0.1);
    box-shadow: 0 2px 12px rgba(16,42,86,0.06);
}
.gal-filter-inner {
    display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
    padding: 14px 0;
}
.gal-filter-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 18px; border-radius: 50px;
    border: 1.5px solid rgba(16,42,86,0.2);
    background: #fff;
    color: var(--text-light); font-size: 13px; font-weight: 600;
    cursor: pointer; white-space: nowrap;
    transition: color .25s ease, background-color .25s ease, border-color .25s ease, box-shadow .25s ease, transform .25s ease, opacity .25s ease;
}
.gal-filter-btn .cnt {
    background: rgba(16,42,86,0.1);
    color: var(--primary-color);
    font-size: var(--fs-caption-sm); font-weight: 700;
    padding: 1px 7px; border-radius: 10px;
    transition: color .25s ease, background-color .25s ease, border-color .25s ease, box-shadow .25s ease, transform .25s ease, opacity .25s ease;
}
.gal-filter-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    box-shadow: 0 4px 14px rgba(16,42,86,0.12);
}
.gal-filter-btn.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: #fff;
    box-shadow: 0 6px 20px rgba(16,42,86,0.3);
}
.gal-filter-btn.active .cnt {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

/* --- Gallery Section --- */
.gal-section {
    background: #F4F8FC;
    padding: var(--section-y) 0;
}

/* --- Grid --- */
.gal-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}
@media (max-width: 1100px) { .gal-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .gal-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .gal-grid { grid-template-columns: 1fr; } }

/* --- Cards --- */
.gal-card {
    position: relative;
    border-radius: var(--radius-card);
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 18px rgba(0,0,0,0.07);
    cursor: pointer;
    opacity: 0;
    transform: translateY(24px) scale(0.97);
    transition: opacity .45s ease, transform .45s ease, box-shadow .3s ease;
}
.gal-card.gal-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}
.gal-card:hover {
    box-shadow: 0 16px 48px rgba(16,42,86,0.18);
    transform: translateY(-4px) scale(1.01);
}
.gal-card img {
    width: 100%; height: 220px;
    object-fit: cover;
    display: block;
    transition: transform .45s ease;
}
.gal-card:hover img { transform: scale(1.05); }
.gal-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(11,31,66,0.85) 0%, rgba(11,31,66,0.2) 55%, transparent 100%);
    opacity: 0;
    transition: opacity .35s ease;
    display: flex; flex-direction: column;
    justify-content: flex-end;
    padding: 18px;
}
.gal-card:hover .gal-card-overlay { opacity: 1; }
.gal-card-cat {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--accent-color);
    color: #fff;
    font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
    padding: 3px 10px; border-radius: 10px;
    margin-bottom: 8px; width: fit-content;
}
.gal-card-title {
    color: #fff; font-size: 14px; font-weight: 600;
    line-height: 1.3;
    text-shadow: 0 1px 4px rgba(0,0,0,0.5);
}
.gal-card-zoom {
    position: absolute; top: 12px; right: 12px;
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.3);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 14px;
    opacity: 0; transform: scale(0.7);
    transition: opacity .3s ease, transform .3s ease;
}
.gal-card:hover .gal-card-zoom {
    opacity: 1; transform: scale(1);
}

/* --- Category color badges --- */
.cat-events      { background: #102A56; }
.cat-classes     { background: #0B1F42; }
.cat-activities  { background: #0BA6C9; }
.cat-achievements{ background: var(--accent-color); color: #FFFFFF !important; }
.cat-default     { background: #3DBBD8; }

/* --- Empty State --- */
.gal-empty {
    grid-column: 1 / -1;
    text-align: center; padding: 80px 20px;
}
.gal-empty-icon {
    width: 100px; height: 100px; border-radius: 50%;
    background: linear-gradient(135deg, rgba(16,42,86,0.1), rgba(11,166,201,0.1));
    border: 2px dashed rgba(16,42,86,0.2);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
}
.gal-empty-icon i { font-size: 40px; color: var(--primary-color); opacity: .5; }
.gal-empty h3 { color: var(--text-dark); font-size: 22px; font-weight: 700; margin-bottom: 10px; }
.gal-empty p  { color: #64748B; font-size: 15px; max-width: 460px; margin: 0 auto 24px; line-height: 1.7; }
.gal-empty-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--primary-color); color: #fff;
    padding: 12px 28px; border-radius: 10px;
    font-size: 14px; font-weight: 600;
    text-decoration: none;
    box-shadow: 0 6px 20px rgba(16,42,86,0.3);
    transition: color .25s ease, background-color .25s ease, border-color .25s ease, box-shadow .25s ease, transform .25s ease, opacity .25s ease;
}
.gal-empty-btn:hover { background: #0B1F42; transform: translateY(-2px); color: #fff; }

/* ===== LIGHTBOX ===== */
.gal-lightbox {
    display: none;
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(5,20,20,0.92);
    backdrop-filter: blur(8px);
    align-items: center; justify-content: center;
    padding: 20px;
    animation: lbFadeIn .3s ease;
}
.gal-lightbox.open { display: flex; }
@keyframes lbFadeIn { from { opacity:0; } to { opacity:1; } }
.lb-inner {
    position: relative;
    max-width: 880px; width: 100%;
    animation: lbSlideUp .35s ease;
}
@keyframes lbSlideUp {
    from { transform: translateY(30px) scale(0.96); opacity:0; }
    to   { transform: translateY(0) scale(1); opacity:1; }
}
.lb-img-wrap {
    border-radius: var(--radius-card);
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0,0,0,0.6);
    background: #0a1a1a;
    position: relative;
}
.lb-img-wrap img {
    width: 100%; max-height: 72vh;
    object-fit: contain; display: block;
}
.lb-caption {
    padding: 16px 20px 0;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.lb-caption-left { display: flex; align-items: center; gap: 10px; }
.lb-cat-pill {
    font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
    padding: 3px 10px; border-radius: 10px;
    background: var(--accent-color); color: #FFFFFF;
}
.lb-title { color: #fff; font-size: 16px; font-weight: 600; }
.lb-counter { color: rgba(255,255,255,0.5); font-size: 13px; font-weight: 500; }
.lb-close {
    position: absolute; top: -44px; right: 0;
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 18px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s ease;
}
.lb-close:hover { background: rgba(11,166,201,0.3); }
.lb-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s ease;
    z-index: 10;
}
.lb-nav:hover { background: var(--primary-color); border-color: var(--primary-color); }
.lb-prev { left: -56px; }
.lb-next { right: -56px; }
@media (max-width: 768px) {
    .lb-prev { left: 8px; }
    .lb-next { right: 8px; }
}

/* --- Stats Bar --- */
.gal-stats {
    background: linear-gradient(135deg, var(--primary-color), #0B1F42);
    padding: 36px 0;
}
.gal-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: rgba(255,255,255,0.12);
    border-radius: var(--radius-card);
    overflow: hidden;
}
@media (max-width: 640px) { .gal-stats-grid { grid-template-columns: repeat(2, 1fr); } }
.gal-stat {
    background: rgba(255,255,255,0.06);
    padding: 24px 16px; text-align: center;
    transition: background .25s ease;
}
.gal-stat:hover { background: rgba(255,255,255,0.12); }
.gal-stat-num {
    font-size: 28px; font-weight: 700;
    background: linear-gradient(90deg, var(--accent-color), #0BA6C9);
    -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    display: block; line-height: 1;
    margin-bottom: 6px;
}
.gal-stat-lbl { color: rgba(255,255,255,0.75); font-size: 13px; font-weight: 500; }

/* --- CTA --- */
.gal-cta {
    background: var(--primary-color);
    padding: var(--section-y-sm) 0;
    position: relative; overflow: hidden;
}
.gal-cta::before { display:none;
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='40' cy='40' r='30'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.gal-cta-inner {
    position: relative;
    display: flex; align-items: center; gap: 48px; flex-wrap: wrap;
}
.gal-cta-left { flex: 1 1 320px; }
.gal-cta-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(11,166,201,0.18);
    border: 1px solid rgba(11,166,201,0.35);
    color: #3DBBD8;
    font-size: 11px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase;
    padding: 4px 12px; border-radius: var(--radius-card);
    margin-bottom: 12px;
}
.gal-cta-left h2 {
    font-size: var(--fs-h2); font-weight: 700; color: #fff;
    line-height: 1.25; margin: 0 0 8px;
}
.gal-cta-left p { color: rgba(255,255,255,0.72); font-size: 14px; margin: 0; line-height: 1.6; }
.gal-cta-right {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
.gal-cta-btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--accent-color); color: #FFFFFF;
    padding: 13px 28px; border-radius: 10px;
    font-size: 14px; font-weight: 700;
    text-decoration: none; white-space: nowrap;
    box-shadow: 0 6px 20px rgba(11,166,201,0.35);
    transition: color .25s ease, background-color .25s ease, border-color .25s ease, box-shadow .25s ease, transform .25s ease, opacity .25s ease;
}
.gal-cta-btn-primary:hover { background: #098BA9; transform: translateY(-2px); color: #FFFFFF; }
.gal-cta-btn-outline {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.1);
    border: 1.5px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 12px 26px; border-radius: 10px;
    font-size: 14px; font-weight: 600;
    text-decoration: none; white-space: nowrap;
    backdrop-filter: blur(4px);
    transition: color .25s ease, background-color .25s ease, border-color .25s ease, box-shadow .25s ease, transform .25s ease, opacity .25s ease;
}
.gal-cta-btn-outline:hover { background: rgba(255,255,255,0.18); transform: translateY(-2px); color: #fff; }

/* --- Responsive tweaks --- */
@media (max-width: 768px) {
    .gal-hero { padding: 50px 0 42px; }
    .gal-hero h1 { font-size: var(--fs-page-title); }
    .gal-cta-inner { gap: 28px; }
    .gal-cta-left h2 { font-size: var(--fs-h2); }
    .lb-prev,.lb-next { display: none; }
}
</style>

<!-- HERO -->
<section class="gal-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="gal-breadcrumb">
            <a href="index.php"><i class="fas fa-home" style="font-size:11px;"></i> Home</a>
            <span class="sep"><i class="fas fa-chevron-right"></i></span>
            <span class="cur">Gallery</span>
        </div>
        <div class="gal-hero-badge">
            <i class="fas fa-images"></i> Photo Gallery
        </div>
        <h1>Our <span>Memories</span> &amp; Moments</h1>
        <p>A glimpse into life at <?php echo htmlspecialchars(SITE_NAME); ?> — memorable moments captured across campus.</p>
    </div>
</section>

<!-- STATS BAR -->
<section class="gal-stats">
    <div class="container">
        <div class="gal-stats-grid">
            <div class="gal-stat">
                <span class="gal-stat-num"><?php echo $cat_counts['all'] ?: '0'; ?></span>
                <span class="gal-stat-lbl">Total Photos</span>
            </div>
            <?php foreach ($top_stat_cats as $tc): ?>
            <div class="gal-stat">
                <span class="gal-stat-num"><?php echo $cat_counts[$tc['slug']] ?? 0; ?></span>
                <span class="gal-stat-lbl"><?php echo htmlspecialchars($tc['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FILTER + GALLERY -->
<div class="gal-filter-wrap">
    <div class="container">
        <div class="gal-filter-inner">
            <?php
            foreach ($filter_tabs as $key => $tab) {
                $cnt = $cat_counts[$key] ?? 0;
                $active = $key === 'all' ? 'active' : '';
                echo "<button class=\"gal-filter-btn $active\" data-filter=\"$key\">";
                echo "<i class=\"fas {$tab['icon']}\" style=\"font-size:12px;\"></i> {$tab['label']}";
                if ($cnt > 0 || $key === 'all') {
                    echo "<span class=\"cnt\">" . ($cnt ?: ($has_images ? '0' : '–')) . "</span>";
                }
                echo "</button>";
            }
            ?>
        </div>
    </div>
</div>

<section class="gal-section">
    <div class="container">
        <div class="gal-grid" id="galGrid">
            <?php if ($has_images): ?>
                <?php foreach ($gallery_items as $idx => $img):
                    $cat  = strtolower(trim($img['category'] ?? 'events'));
                    $title = htmlspecialchars($img['title'] ?? '');
                    $src   = htmlspecialchars($img['image_path'] ?? '');
                    $cat_label = $cat_labels[$cat] ?? ucfirst($cat);
                    $cat_css   = 'cat-' . (in_array($cat, $styled_cat_slugs) ? $cat : 'default');
                ?>
                <div class="gal-card" data-cat="<?php echo $cat; ?>"
                     data-idx="<?php echo $idx; ?>"
                     data-src="<?php echo $src; ?>"
                     data-title="<?php echo $title; ?>"
                     data-cat-label="<?php echo $cat_label; ?>"
                     role="button" tabindex="0" aria-label="View photo: <?php echo $title; ?>"
                     onclick="openLightbox(<?php echo $idx; ?>)"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openLightbox(<?php echo $idx; ?>);}"
                     style="transition-delay:<?php echo min($idx % 8, 7) * 60; ?>ms">
                    <img src="<?php echo $src; ?>" alt="<?php echo $title; ?>" loading="lazy">
                    <div class="gal-card-overlay">
                        <span class="gal-card-cat <?php echo $cat_css; ?>"><?php echo $cat_label; ?></span>
                        <?php if ($title): ?>
                        <div class="gal-card-title"><?php echo $title; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="gal-card-zoom"><i class="fas fa-expand-alt"></i></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="gal-empty">
                    <div class="gal-empty-icon"><i class="fas fa-images"></i></div>
                    <h3>Gallery Coming Soon</h3>
                    <p>We are working on adding amazing photos of our events, classes, and achievements. Check back soon!</p>
                    <a href="admission.php" class="gal-empty-btn">
                        <i class="fas fa-graduation-cap"></i> Apply for Admission
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- LIGHTBOX -->
<div class="gal-lightbox" id="galLightbox">
    <div class="lb-inner">
        <button class="lb-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <button class="lb-nav lb-prev" onclick="lbNav(-1)"><i class="fas fa-chevron-left"></i></button>
        <button class="lb-nav lb-next" onclick="lbNav(1)"><i class="fas fa-chevron-right"></i></button>
        <div class="lb-img-wrap">
            <img id="lbImg" src="" alt="">
        </div>
        <div class="lb-caption">
            <div class="lb-caption-left">
                <span class="lb-cat-pill" id="lbCat"></span>
                <span class="lb-title" id="lbTitle"></span>
            </div>
            <span class="lb-counter" id="lbCounter"></span>
        </div>
    </div>
</div>

<!-- CTA -->
<section class="gal-cta">
    <div class="container">
        <div class="gal-cta-inner">
            <div class="gal-cta-left">
                <div class="gal-cta-badge"><i class="fas fa-graduation-cap"></i> Join Our Community</div>
                <h2>Become Part of Our Story</h2>
                <p>Enroll today and create your own memories at <?php echo htmlspecialchars(SITE_NAME); ?>.</p>
            </div>
            <div class="gal-cta-right">
                <a href="admission.php" class="gal-cta-btn-primary">
                    <i class="fas fa-paper-plane"></i> Apply Now
                </a>
                <a href="contact.php" class="gal-cta-btn-outline">
                    <i class="fas fa-phone-alt"></i> Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<script>
/* ===== FILTER ===== */
const filterBtns = document.querySelectorAll('.gal-filter-btn');
const allCards   = document.querySelectorAll('.gal-card');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.filter;
        let delay = 0;
        allCards.forEach(card => {
            const show = !card.classList.contains('img-missing') && (f === 'all' || card.dataset.cat === f);
            if (show) {
                card.style.display = 'block';
                card.classList.remove('gal-visible');
                void card.offsetWidth;
                card.style.transitionDelay = delay + 'ms';
                setTimeout(() => card.classList.add('gal-visible'), 20);
                delay += 55;
            } else {
                card.style.display = 'none';
                card.classList.remove('gal-visible');
            }
        });
    });
});

/* ===== PRE-SELECT FILTER FROM URL (?category=events etc.) ===== */
(function() {
    const params = new URLSearchParams(window.location.search);
    const cat = params.get('category');
    if (!cat) return;
    const targetBtn = document.querySelector('.gal-filter-btn[data-filter="' + cat + '"]');
    if (targetBtn) targetBtn.click();
})();

/* ===== INTERSECTION OBSERVER ===== */
const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            const delay = parseInt(e.target.style.transitionDelay) || 0;
            setTimeout(() => e.target.classList.add('gal-visible'), delay);
            io.unobserve(e.target);
        }
    });
}, { threshold: 0.08 });

allCards.forEach(c => io.observe(c));

/* ===== LIGHTBOX ===== */
const visibleItems = () => [...allCards].filter(c => c.style.display !== 'none');
let lbIdx = 0;

function openLightbox(idx) {
    const all = [...allCards];
    const card = all[idx];
    if (!card) return;
    const vis = visibleItems();
    lbIdx = vis.indexOf(card);
    if (lbIdx === -1) lbIdx = 0;
    renderLightbox(vis, lbIdx);
    document.getElementById('galLightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function renderLightbox(vis, i) {
    const card = vis[i];
    document.getElementById('lbImg').src     = card.dataset.src;
    document.getElementById('lbImg').alt     = card.dataset.title || '';
    document.getElementById('lbTitle').textContent   = card.dataset.title || '';
    document.getElementById('lbCat').textContent     = card.dataset.catLabel || '';
    document.getElementById('lbCounter').textContent = (i + 1) + ' / ' + vis.length;
}

function lbNav(dir) {
    const vis = visibleItems();
    lbIdx = (lbIdx + dir + vis.length) % vis.length;
    renderLightbox(vis, lbIdx);
    document.getElementById('lbImg').style.animation = 'none';
    void document.getElementById('lbImg').offsetWidth;
    document.getElementById('lbImg').style.animation = '';
}

function closeLightbox() {
    document.getElementById('galLightbox').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('galLightbox').addEventListener('click', e => {
    if (e.target === document.getElementById('galLightbox')) closeLightbox();
});

document.addEventListener('keydown', e => {
    const lb = document.getElementById('galLightbox');
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft')  lbNav(-1);
    if (e.key === 'ArrowRight') lbNav(1);
});
</script>

<?php include 'includes/footer.php'; ?>
