<?php
require_once 'includes/config.php';
$page_title = 'About Us';
$page_description = 'About ' . getSiteName() . ' — serving District Buner since 1993 with quality education and character building. Our story, mission, vision and values.';
$about = getAboutContent();
$about_hero = getAboutHero();
$about_who = getAboutWho();
$about_explore = getAboutExplore();
$about_cta_content = getAboutCta();
$about_stats = getAboutStats();

// Highlights shown under "Who We Are" (text from AdminCP, icons fixed)
$about_highlights = [
    ['fa-landmark',          $about_who['hl1']],
    ['fa-graduation-cap',    $about_who['hl2']],
    ['fa-shield-halved',     $about_who['hl3']],
    ['fa-chalkboard-user',   $about_who['hl4']],
];
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   About page — uses the shared system in css/style.css
   (section badges, cards, icon tiles, buttons, spacing tokens)
   ================================================================ */

/* ── Hero ── */
.ab-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:380px; display:flex; align-items:center;
    padding:72px 0;
    background:var(--primary-dark);
}
.ab-hero-img { position:absolute; inset:0; z-index:-2; }
.ab-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.ab-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.94) 0%, rgba(16,42,86,0.84) 45%, rgba(16,42,86,0.55) 100%);
}
.ab-breadcrumb { display:flex; align-items:center; gap:8px; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.ab-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.ab-breadcrumb a:hover { color:#fff; }
.ab-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.ab-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase;
    margin-bottom:14px;
}
.ab-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.ab-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; max-width:760px; }
.ab-hero h1 span { color:var(--accent-bright); }
.ab-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:600px; }
.ab-hero-facts { display:flex; flex-wrap:wrap; gap:10px; margin-top:24px; }
.ab-hero-facts span {
    display:inline-flex; align-items:center; gap:8px;
    padding:7px 14px; border-radius:999px;
    background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18);
    color:#fff; font-size:var(--fs-caption); font-weight:500;
}
.ab-hero-facts i { color:var(--gold-color); font-size:12px; }

/* ── Who we are ── */
.ab-who { padding:var(--section-y) 0; background:#fff; }
.ab-who-grid { display:grid; grid-template-columns:1.05fr 1fr; gap:64px; align-items:center; }
.ab-who h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); line-height:1.25; margin:0 0 18px; }
.ab-who h2 span { color:var(--accent-hover); }
.ab-who p { font-size:var(--fs-body); color:var(--text-color); line-height:1.8; margin:0 0 16px; }
.ab-who p + p { color:var(--text-light); }
.ab-hl-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:28px; }
.ab-hl {
    display:flex; align-items:center; gap:12px;
    padding:14px 16px; border-radius:var(--radius-card);
    background:var(--background-color); border:1px solid var(--border-color);
    font-family:var(--font-heading); font-size:14.5px; font-weight:600; color:var(--primary-color); line-height:1.35;
}
.ab-hl .ui-icon { width:40px; height:40px; font-size:15px; margin:0; }

.ab-photo { position:relative; padding:0 0 22px 22px; }
.ab-photo::before {
    content:''; position:absolute; left:0; bottom:0; width:62%; height:72%;
    border-radius:var(--radius-card); background:var(--light-blue); z-index:0;
}
.ab-photo .ui-media { position:relative; z-index:1; aspect-ratio:4 / 3; border-radius:var(--radius-card); box-shadow:var(--shadow-lg); }
.ab-photo-badge {
    position:absolute; z-index:2; right:18px; bottom:-2px;
    background:var(--primary-color); color:#fff;
    padding:14px 20px; border-radius:var(--radius-card);
    border-bottom:3px solid var(--gold-color);
    box-shadow:0 14px 32px rgba(16,42,86,0.32); text-align:center;
}
.ab-photo-badge .num { font-family:var(--font-heading); font-size:28px; font-weight:700; line-height:1; }
.ab-photo-badge .lbl { font-size:var(--fs-caption); color:rgba(255,255,255,0.78); margin-top:4px; letter-spacing:0.4px; }

/* ── Mission & vision ── */
.ab-mv { padding:var(--section-y) 0; background:var(--background-color); }
.ab-mv-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.ab-mv .ui-card { padding:32px; position:relative; overflow:hidden; }
.ab-mv .ui-card::after {
    content:''; position:absolute; top:0; left:32px; width:48px; height:3px;
    border-radius:0 0 3px 3px; background:var(--gold-color);
}
.ab-mv .ui-card h3 { font-size:var(--fs-h3); margin-bottom:12px; }
.ab-mv .ui-card p { font-size:var(--fs-body); color:var(--text-color); line-height:1.8; }

/* ── Stats ── */
.ab-stats { padding:var(--section-y-sm) 0; background:var(--primary-color); }
.ab-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); }
.ab-stat { text-align:center; padding:8px 20px; }
.ab-stat + .ab-stat { border-left:1px solid rgba(255,255,255,0.12); }
.ab-stat-num { font-family:var(--font-heading); font-size:clamp(30px, 2vw + 18px, 42px); font-weight:700; color:#fff; line-height:1; }
.ab-stat-lbl { font-size:var(--fs-caption); color:rgba(255,255,255,0.7); margin-top:10px; font-weight:500; letter-spacing:0.3px; }

/* ── Explore ── */
.ab-explore { padding:var(--section-y) 0; background:#fff; }
.ab-sh { text-align:center; margin-bottom:40px; }
.ab-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 10px; }
.ab-sh h2 span { color:var(--accent-hover); }
.ab-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0 auto; max-width:620px; }
.ab-explore-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }

/* ── CTA ── */
.ab-cta { padding:var(--section-y-sm) 0; background:var(--primary-color); }
.ab-cta-inner { display:flex; align-items:center; justify-content:space-between; gap:40px; flex-wrap:wrap; }
.ab-cta-text { flex:1 1 420px; }
.ab-cta .section-badge { color:var(--accent-bright); }
.ab-cta h2 { font-size:var(--fs-h2); font-weight:700; color:#fff; line-height:1.25; margin:0 0 12px; }
.ab-cta h2 span { color:var(--accent-bright); }
.ab-cta p { color:rgba(255,255,255,0.75); font-size:var(--fs-body); line-height:1.75; margin:0; max-width:560px; }
.ab-cta-actions { display:flex; flex-direction:column; gap:16px; }
.ab-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }
.ab-cta-pills { display:flex; gap:8px 18px; flex-wrap:wrap; }
.ab-cta-pills span { display:inline-flex; align-items:center; gap:7px; color:rgba(255,255,255,0.8); font-size:var(--fs-caption); }
.ab-cta-pills i { color:var(--gold-color); font-size:12px; }

/* Reveal on scroll (content stays visible without JS / with reduced motion) */
.ab-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.ab-reveal.is-visible { opacity:1; transform:none; }
.no-js .ab-reveal { opacity:1; transform:none; }

/* ── Responsive ── */
@media (max-width:1024px) {
    .ab-who-grid { gap:44px; }
}
@media (max-width:900px) {
    .ab-who-grid { grid-template-columns:1fr; }
    .ab-photo { max-width:620px; }
    .ab-mv-grid, .ab-explore-grid { grid-template-columns:1fr; }
    .ab-stats-grid { grid-template-columns:repeat(2,1fr); row-gap:28px; }
    .ab-stat:nth-child(3) { border-left:0; }
}
@media (max-width:560px) {
    .ab-hero { min-height:0; padding:56px 0; }
    .ab-hl-grid { grid-template-columns:1fr; }
    .ab-photo { padding:0 0 16px 14px; }
    .ab-photo-badge { right:12px; padding:11px 16px; }
    .ab-photo-badge .num { font-size:22px; }
    .ab-mv .ui-card { padding:24px; }
    .ab-mv .ui-card::after { left:24px; }
    .ab-stat { padding:4px 10px; }
}
@media (prefers-reduced-motion:reduce) { .ab-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="ab-hero">
    <div class="ab-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($about_hero['image']); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="ab-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">About Us</span>
        </nav>
        <div class="ab-hero-badge"><?php echo htmlspecialchars($about_hero['badge']); ?></div>
        <h1>About <span><?php echo htmlspecialchars(getSiteName()); ?></span></h1>
        <p><?php echo htmlspecialchars($about_hero['subtitle']); ?></p>
        <div class="ab-hero-facts">
            <?php if (!empty($about_who['image_badge_num'])): ?>
            <span><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($about_who['image_badge_label'] . ' ' . $about_who['image_badge_num']); ?></span>
            <?php endif; ?>
            <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars(getSiteAddress()); ?></span>
        </div>
    </div>
</section>

<!-- ── Who we are ── -->
<section class="ab-who">
    <div class="container">
        <div class="ab-who-grid">
            <div class="ab-reveal">
                <div class="section-badge"><?php echo htmlspecialchars($about_who['badge']); ?></div>
                <h2><?php echo renderHighlightedTitle($about_who['title'], $about_who['title_highlight'], 'span'); ?></h2>
                <?php if (!empty($about['description'])): ?><p><?php echo nl2br(htmlspecialchars($about['description'])); ?></p><?php endif; ?>
                <?php if (!empty($about_who['paragraph2'])): ?><p><?php echo htmlspecialchars($about_who['paragraph2']); ?></p><?php endif; ?>

                <div class="ab-hl-grid">
                    <?php foreach ($about_highlights as $hl): if (trim($hl[1]) === '') continue; ?>
                    <div class="ab-hl">
                        <span class="ui-icon"><i class="fas <?php echo $hl[0]; ?>" aria-hidden="true"></i></span>
                        <?php echo htmlspecialchars($hl[1]); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="ab-photo ab-reveal">
                <div class="ui-media">
                    <img src="<?php echo htmlspecialchars($about_who['image']); ?>" alt="<?php echo htmlspecialchars(getSiteName()); ?> campus" loading="lazy">
                </div>
                <?php if (!empty($about_who['image_badge_num'])): ?>
                <div class="ab-photo-badge">
                    <div class="num"><?php echo htmlspecialchars($about_who['image_badge_num']); ?></div>
                    <div class="lbl"><?php echo htmlspecialchars($about_who['image_badge_label']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ── Mission & vision ── -->
<?php if (!empty($about['mission']) || !empty($about['vision'])): ?>
<section class="ab-mv">
    <div class="container">
        <div class="ab-sh">
            <div class="section-badge">Our Purpose</div>
            <h2>Mission &amp; <span>Vision</span></h2>
        </div>
        <div class="ab-mv-grid">
            <?php if (!empty($about['mission'])): ?>
            <div class="ui-card ab-reveal">
                <span class="ui-icon"><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                <h3>Our Mission</h3>
                <p><?php echo htmlspecialchars($about['mission']); ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($about['vision'])): ?>
            <div class="ui-card ab-reveal">
                <span class="ui-icon"><i class="fas fa-eye" aria-hidden="true"></i></span>
                <h3>Our Vision</h3>
                <p><?php echo htmlspecialchars($about['vision']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── At a glance ── -->
<section class="ab-stats" aria-label="At a glance">
    <div class="container">
        <div class="ab-stats-grid">
            <?php foreach ($about_stats as $s): ?>
            <div class="ab-stat">
                <div class="ab-stat-num"><?php echo htmlspecialchars($s['number']); ?></div>
                <div class="ab-stat-lbl"><?php echo htmlspecialchars($s['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Explore ── -->
<section class="ab-explore">
    <div class="container">
        <div class="ab-sh">
            <div class="section-badge"><?php echo htmlspecialchars($about_explore['badge']); ?></div>
            <h2><?php echo renderHighlightedTitle($about_explore['title'], $about_explore['title_highlight'], 'span'); ?></h2>
            <p><?php echo htmlspecialchars($about_explore['subtitle']); ?></p>
        </div>
        <div class="ab-explore-grid">
            <?php
            $explore_cards = [
                ['mission-vision.php', 'fa-compass',      $about_explore['card1_title'], $about_explore['card1_desc']],
                ['core-values.php',    'fa-heart',        $about_explore['card2_title'], $about_explore['card2_desc']],
                ['leadership.php',     'fa-users',        $about_explore['card3_title'], $about_explore['card3_desc']],
            ];
            foreach ($explore_cards as $c): ?>
            <a href="<?php echo $c[0]; ?>" class="ui-card ab-reveal">
                <span class="ui-icon"><i class="fas <?php echo $c[1]; ?>" aria-hidden="true"></i></span>
                <h3><?php echo htmlspecialchars($c[2]); ?></h3>
                <p><?php echo htmlspecialchars($c[3]); ?></p>
                <span class="ui-more">Read more <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="ab-cta">
    <div class="container">
        <div class="ab-cta-inner">
            <div class="ab-cta-text">
                <div class="section-badge"><?php echo htmlspecialchars($about_cta_content['badge']); ?></div>
                <h2><?php echo htmlspecialchars($about_cta_content['heading_line1']); ?> <span><?php echo htmlspecialchars($about_cta_content['heading_line2']); ?></span></h2>
                <p><?php echo htmlspecialchars(applySiteNamePlaceholder($about_cta_content['subtext'])); ?></p>
            </div>
            <div class="ab-cta-actions">
                <div class="ab-cta-btns">
                    <a href="admission.php" class="btn btn-accent"><i class="fas fa-graduation-cap"></i> Apply for Admission</a>
                    <a href="contact.php" class="btn btn-outline"><i class="fas fa-phone-alt"></i> Contact Us</a>
                </div>
                <div class="ab-cta-pills">
                    <?php foreach (['pill1', 'pill2'] as $pk): if (empty($about_cta_content[$pk])) continue; ?>
                    <span><i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($about_cta_content[$pk]); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var items = document.querySelectorAll('.ab-reveal');
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach(function (el) { el.classList.add('is-visible'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .ab-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 4) * 80 + 'ms';
            e.target.classList.add('is-visible');
            io.unobserve(e.target);
        });
    }, { threshold: 0.1 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
