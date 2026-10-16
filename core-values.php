<?php
require_once 'includes/config.php';
$page_title = 'Core Values';
$page_description = 'The core values of ' . getSiteName() . ' — the principles that guide how our teachers teach, how our students learn and how our community grows together.';

$core_values = [];
$core_values_result = mysqli_query($conn, "SELECT * FROM core_values WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($core_values_result && ($row = mysqli_fetch_assoc($core_values_result))) {
    $icon = trim($row['icon'] ?: 'fas fa-star');
    if (strpos($icon, 'fa-') === 0) $icon = 'fas ' . $icon;    // stored as "fa-x" or "fas fa-x"
    $row['icon'] = $icon;
    $core_values[] = $row;
}
$value_count = count($core_values);
$number_words = [1 => 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve'];

// Hero photo (campus); editable via the cv_hero_image setting
$cv_hero_image = getSetting('cv_hero_image', 'images/hero/slide_1792151883_1110.jpg');
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Core Values — uses the shared system in css/style.css
   ================================================================ */

/* ── Hero ── */
.cv-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:380px; display:flex; align-items:center;
    padding:72px 0 64px; background:var(--primary-dark);
}
.cv-hero-img { position:absolute; inset:0; z-index:-2; }
.cv-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.cv-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%);
}
.cv-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.cv-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.cv-breadcrumb a:hover { color:#fff; }
.cv-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.cv-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.cv-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.cv-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.cv-hero h1 span { color:var(--accent-bright); }
.cv-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:600px; }
.cv-hero-values { display:flex; flex-wrap:wrap; gap:8px; margin-top:26px; }
.cv-hero-values a {
    display:inline-flex; align-items:center; gap:8px;
    padding:7px 14px; border-radius:999px;
    background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18);
    color:#fff; font-size:var(--fs-caption); font-weight:500; text-decoration:none;
    transition:background-color var(--dur) ease, border-color var(--dur) ease;
}
.cv-hero-values a:hover { background:rgba(255,255,255,0.16); border-color:rgba(255,255,255,0.4); color:#fff; }
.cv-hero-values i { color:var(--gold-color); font-size:11px; }

/* ── Intro + values grid ── */
.cv-values { padding:var(--section-y) 0; background:#fff; }
.cv-intro { display:grid; grid-template-columns:1fr 1fr; gap:56px; align-items:end; margin-bottom:48px; }
.cv-intro h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); line-height:1.25; margin:0; }
.cv-intro h2 span { color:var(--accent-hover); }
.cv-intro p { color:var(--text-light); font-size:var(--fs-body); line-height:1.8; margin:0; }

.cv-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
.cv-card {
    position:relative; overflow:hidden;
    padding:34px 30px 30px;
    border-radius:var(--radius-card);
    background:#fff; border:1px solid var(--border-color);
    box-shadow:var(--shadow-card);
    transition:transform var(--dur) var(--ease-out), box-shadow var(--dur) ease, border-color var(--dur) ease;
    scroll-margin-top:var(--header-offset);
}
.cv-card::before {                       /* gold accent grows on hover */
    content:''; position:absolute; top:0; left:30px; height:3px; width:44px;
    border-radius:0 0 3px 3px; background:var(--gold-color);
    transition:width 0.45s var(--ease-out);
}
.cv-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-card-hover); border-color:rgba(11,166,201,0.35); }
.cv-card:hover::before { width:calc(100% - 60px); }
.cv-card:target { border-color:var(--accent-color); box-shadow:0 0 0 3px rgba(11,166,201,0.18), var(--shadow-card); }
.cv-num {
    position:absolute; top:14px; right:22px;
    font-family:var(--font-heading); font-size:64px; font-weight:700; line-height:1;
    color:transparent; -webkit-text-stroke:1.5px var(--border-color);
    pointer-events:none; user-select:none;
    transition:-webkit-text-stroke-color var(--dur) ease;
}
.cv-card:hover .cv-num { -webkit-text-stroke-color:rgba(11,166,201,0.35); }
.cv-card .ui-icon { width:56px; height:56px; font-size:21px; margin-bottom:22px; border-radius:12px; }
.cv-card:hover .ui-icon { background:var(--accent-color); color:#fff; }
.cv-card h3 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0 0 10px; }
.cv-card p { font-size:var(--fs-body); color:var(--text-light); line-height:1.75; margin:0; }
.cv-empty { grid-column:1 / -1; text-align:center; padding:56px 20px; color:var(--text-light); }
.cv-empty i { font-size:44px; color:var(--border-color); display:block; margin-bottom:14px; }

/* ── Values band ── */
.cv-band { padding:var(--section-y-sm) 0; background:var(--primary-color); text-align:center; }
.cv-band-label { color:var(--accent-bright); font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:16px; }
.cv-band-list {
    display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:10px 0;
    font-family:var(--font-heading); font-size:clamp(18px, 1vw + 14px, 26px); font-weight:600; color:#fff;
    max-width:1000px; margin:0 auto;
}
.cv-band-list span { display:inline-flex; align-items:center; }
.cv-band-list span + span::before {
    content:''; width:7px; height:7px; border-radius:50%; background:var(--gold-color);
    margin:0 18px; flex-shrink:0;
}
.cv-band p { color:rgba(255,255,255,0.72); font-size:var(--fs-body); margin:18px auto 0; max-width:620px; line-height:1.7; }

/* ── Related ── */
.cv-related { padding:var(--section-y) 0; background:var(--background-color); }
.cv-sh { text-align:center; margin-bottom:40px; }
.cv-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 10px; }
.cv-sh h2 span { color:var(--accent-hover); }
.cv-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0 auto; max-width:620px; }
.cv-related-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }

/* ── CTA ── */
.cv-cta { padding:var(--section-y-sm) 0; background:#fff; }
.cv-cta-box {
    display:flex; align-items:center; justify-content:space-between; gap:32px; flex-wrap:wrap;
    padding:40px 44px; border-radius:var(--radius-card);
    background:var(--primary-color); color:#fff;
    border-bottom:4px solid var(--gold-color);
}
.cv-cta-box h2 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0 0 6px; }
.cv-cta-box p { color:rgba(255,255,255,0.75); font-size:var(--fs-body); margin:0; }
.cv-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }

/* Reveal on scroll */
.cv-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.cv-reveal.is-visible { opacity:1; transform:none; }
.cv-card.cv-reveal.is-visible:hover { transform:translateY(-4px); }

/* ── Responsive ── */
@media (max-width:1024px) {
    .cv-intro { grid-template-columns:1fr; gap:16px; align-items:start; }
}
@media (max-width:900px) {
    .cv-grid { grid-template-columns:1fr 1fr; }
    .cv-related-grid { gap:16px; }
}
@media (max-width:700px) {
    .cv-related-grid { grid-template-columns:1fr; }
}
@media (max-width:600px) {
    .cv-hero { min-height:0; padding:56px 0 48px; }
    .cv-grid, .cv-related-grid { grid-template-columns:1fr; }
    .cv-card { padding:30px 24px 26px; }
    .cv-card::before { left:24px; }
    .cv-card:hover::before { width:calc(100% - 48px); }
    .cv-num { font-size:52px; right:18px; }
    .cv-band-list span + span::before { margin:0 12px; }
    .cv-cta-box { padding:28px 24px; }
    .cv-cta-btns { width:100%; }
    .cv-cta-btns .btn { flex:1 1 100%; }
}
@media (prefers-reduced-motion:reduce) {
    .cv-reveal { opacity:1; transform:none; transition:none; }
    .cv-card, .cv-card::before { transition:none; }
}
</style>

<!-- ── Hero ── -->
<section class="cv-hero">
    <div class="cv-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($cv_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="cv-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="about.php">About</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Core Values</span>
        </nav>
        <div class="cv-hero-badge">Our Principles</div>
        <h1>Our Core <span>Values</span></h1>
        <p>The principles that shape how we teach, learn and grow together at <?php echo htmlspecialchars(getSiteName()); ?>.</p>
        <?php if ($value_count): ?>
        <nav class="cv-hero-values" aria-label="Jump to a value">
            <?php foreach ($core_values as $i => $v): ?>
            <a href="#value-<?php echo $i + 1; ?>"><i class="<?php echo htmlspecialchars($v['icon']); ?>" aria-hidden="true"></i><?php echo htmlspecialchars($v['title']); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
</section>

<!-- ── Values ── -->
<section class="cv-values">
    <div class="container">
        <div class="cv-intro">
            <div>
                <div class="section-badge">What We Stand For</div>
                <h2><?php echo $value_count ? htmlspecialchars($number_words[$value_count] ?? (string) $value_count) . ' Principles,' : 'Our Principles,'; ?> <span>One Community</span></h2>
            </div>
            <p>These values guide how our teachers teach, how our students learn and how our school community grows together — in the classroom, on the playground and beyond the school gate.</p>
        </div>

        <div class="cv-grid">
            <?php if ($value_count): ?>
                <?php foreach ($core_values as $i => $v): ?>
                <article class="cv-card cv-reveal" id="value-<?php echo $i + 1; ?>">
                    <span class="cv-num" aria-hidden="true"><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="ui-icon"><i class="<?php echo htmlspecialchars($v['icon']); ?>" aria-hidden="true"></i></span>
                    <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                    <p><?php echo htmlspecialchars($v['description']); ?></p>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="cv-empty"><i class="fas fa-heart" aria-hidden="true"></i>No core values published yet.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Values band ── -->
<?php if ($value_count): ?>
<section class="cv-band" aria-label="Our values in brief">
    <div class="container">
        <div class="cv-band-label">The IQRA Way</div>
        <div class="cv-band-list">
            <?php foreach ($core_values as $v): ?><span><?php echo htmlspecialchars($v['title']); ?></span><?php endforeach; ?>
        </div>
        <p>Every day, in every classroom.</p>
    </div>
</section>
<?php endif; ?>

<!-- ── Related ── -->
<section class="cv-related">
    <div class="container">
        <div class="cv-sh">
            <div class="section-badge">Learn More</div>
            <h2>More About <span>IQRA</span></h2>
            <p>Our story, our direction and the people who lead us.</p>
        </div>
        <div class="cv-related-grid">
            <?php
            $related = [
                ['about.php',          'fa-circle-info', 'About Us',         'Our story since 1993 and what makes our school community special.'],
                ['mission-vision.php', 'fa-compass',     'Mission & Vision', 'The purpose and long-term direction behind everything we do.'],
                ['leadership.php',     'fa-users',       'Our Leadership',   'Meet the people guiding our institution and read their message.'],
            ];
            foreach ($related as $r): ?>
            <a href="<?php echo $r[0]; ?>" class="ui-card cv-reveal">
                <span class="ui-icon"><i class="fas <?php echo $r[1]; ?>" aria-hidden="true"></i></span>
                <h3><?php echo $r[2]; ?></h3>
                <p><?php echo $r[3]; ?></p>
                <span class="ui-more">Read more <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="cv-cta">
    <div class="container">
        <div class="cv-cta-box cv-reveal">
            <div>
                <h2>Grow with values that last a lifetime</h2>
                <p>Join <?php echo htmlspecialchars(getSiteName()); ?> — admissions for Matric and Intermediate.</p>
            </div>
            <div class="cv-cta-btns">
                <a href="admission.php" class="btn btn-accent"><i class="fas fa-graduation-cap"></i> Apply for Admission</a>
                <a href="contact.php" class="btn btn-outline"><i class="fas fa-phone-alt"></i> Contact Us</a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var items = document.querySelectorAll('.cv-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach(show); return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .cv-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 3) * 90 + 'ms';
            show(e.target);
            io.unobserve(e.target);
        });
    }, { threshold: 0.1 });
    items.forEach(function (el) { io.observe(el); });
    // a value opened via the hero chips (#value-N) is shown immediately
    if (location.hash) { var t = document.querySelector(location.hash.replace(/[^#\w-]/g, '')); if (t) show(t); }
    window.addEventListener('hashchange', function () { var t = document.querySelector(location.hash.replace(/[^#\w-]/g, '')); if (t) show(t); });
})();
</script>

<?php include 'includes/footer.php'; ?>
