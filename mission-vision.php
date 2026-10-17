<?php
require_once 'includes/config.php';
$page_title = 'Mission & Vision';
$page_description = 'The mission and vision that guide ' . getSiteName() . ' — quality education, character building and preparing students of District Buner for higher studies and life.';
$about = getAboutContent();
$about_stats = getAboutStats();

// Hero photo (campus); editable via the mv_hero_image setting
$mv_hero_image = getSetting('mv_hero_image', 'images/banners/banner-courtyard.jpg');

// "Our mission in practice" — four commitments (editable via mv_pillarN_* settings)
$mv_pillars = [
    ['fa-book-open-reader', getSetting('mv_pillar1_title', 'Quality Education'),
        getSetting('mv_pillar1_desc', 'Strong academic foundations from school level through Matric and Intermediate, taught by a dedicated team of teachers.')],
    ['fa-hand-holding-heart', getSetting('mv_pillar2_title', 'Character Building'),
        getSetting('mv_pillar2_desc', 'Discipline, good manners and moral values practised every day, so students grow as good people as well as good learners.')],
    ['fa-shield-halved', getSetting('mv_pillar3_title', 'Safe & Disciplined Campus'),
        getSetting('mv_pillar3_desc', 'A secure, well-supervised environment where students can focus fully on learning.')],
    ['fa-road', getSetting('mv_pillar4_title', 'Ready for the Future'),
        getSetting('mv_pillar4_desc', 'Preparing students with the knowledge and confidence they need for higher studies, careers and responsible citizenship.')],
];

// Core values (managed in AdminCP > About > Core Values)
$mv_values = [];
$cvr = mysqli_query($conn, "SELECT title, description, icon FROM core_values WHERE status = 'active' ORDER BY display_order ASC, id ASC LIMIT 6");
while ($cvr && ($row = mysqli_fetch_assoc($cvr))) { $mv_values[] = $row; }
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Mission & Vision — uses the shared system in css/style.css
   ================================================================ */

/* ── Hero ── */
.mv-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:360px; display:flex; align-items:center;
    padding:72px 0; background:var(--primary-dark);
}
.mv-hero-img { position:absolute; inset:0; z-index:-2; }
.mv-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.mv-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.94) 0%, rgba(16,42,86,0.84) 45%, rgba(16,42,86,0.55) 100%);
}
.mv-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.mv-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.mv-breadcrumb a:hover { color:#fff; }
.mv-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.mv-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.mv-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.mv-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.mv-hero h1 span { color:var(--accent-bright); }
.mv-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:600px; }

/* ── Section headings ── */
.mv-sh { text-align:center; margin-bottom:44px; }
.mv-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.mv-sh h2 span { color:var(--accent-hover); }
.mv-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

/* ── Mission & Vision statements ── */
.mv-statements { padding:var(--section-y) 0; background:#fff; }
.mv-grid { display:grid; grid-template-columns:1fr 1fr; gap:28px; }
.mv-card {
    position:relative; overflow:hidden;
    padding:40px 38px 38px;
    border-radius:var(--radius-card);
    border:1px solid var(--border-color);
    background:#fff; box-shadow:var(--shadow-card);
}
.mv-card::before {                          /* gold top accent */
    content:''; position:absolute; top:0; left:38px; width:56px; height:3px;
    border-radius:0 0 3px 3px; background:var(--gold-color);
}
.mv-card.is-vision { background:var(--primary-color); border-color:var(--primary-color); }
.mv-card-head { display:flex; align-items:center; gap:14px; margin-bottom:20px; }
.mv-card-head .ui-icon { width:52px; height:52px; font-size:20px; margin:0; }
.mv-card.is-vision .ui-icon { background:rgba(255,255,255,0.1); color:var(--accent-bright); }
.mv-card-label {
    font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600;
    letter-spacing:2px; text-transform:uppercase; color:var(--accent-text);
}
.mv-card h2 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:2px 0 0; }
.mv-card.is-vision .mv-card-label { color:var(--accent-bright); }
.mv-card.is-vision h2 { color:#fff; }
.mv-quote {
    position:relative; margin:0;
    font-family:var(--font-heading); font-size:clamp(17px, 0.6vw + 14px, 20px); font-weight:500;
    line-height:1.65; color:var(--text-color);
}
.mv-card.is-vision .mv-quote { color:rgba(255,255,255,0.92); }
.mv-quote-mark { display:block; font-size:22px; color:var(--accent-color); margin-bottom:10px; line-height:1; }
.mv-card.is-vision .mv-quote-mark { color:var(--gold-color); }

/* ── Mission in practice ── */
.mv-practice { padding:var(--section-y) 0; background:var(--background-color); }
.mv-pillars { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; counter-reset:pillar; }
.mv-pillars .ui-card { padding:28px 24px; position:relative; }
.mv-pillars .ui-card::after {
    counter-increment:pillar; content:'0' counter(pillar);
    position:absolute; top:22px; right:22px;
    font-family:var(--font-heading); font-size:13px; font-weight:700; color:var(--border-color);
}

/* ── Values ── */
.mv-values { padding:var(--section-y) 0; background:#fff; }
.mv-values-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
.mv-value {
    display:flex; gap:16px; align-items:flex-start;
    padding:22px; border-radius:var(--radius-card);
    border:1px solid var(--border-color); background:#fff;
    transition:border-color var(--dur) ease, box-shadow var(--dur) ease;
}
.mv-value:hover { border-color:rgba(11,166,201,0.4); box-shadow:var(--shadow-card); }
.mv-value .ui-icon { margin:0; width:44px; height:44px; font-size:17px; }
.mv-value h3 { font-size:var(--fs-card-title); font-weight:600; color:var(--primary-color); margin:0 0 4px; }
.mv-value p { font-size:var(--fs-body-sm); color:var(--text-light); line-height:1.65; margin:0; }
.mv-values-more { text-align:center; margin-top:32px; }

/* ── Since 1993 strip ── */
.mv-stats { padding:var(--section-y-sm) 0; background:var(--primary-color); }
.mv-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); }
.mv-stat { text-align:center; padding:8px 20px; }
.mv-stat + .mv-stat { border-left:1px solid rgba(255,255,255,0.12); }
.mv-stat-num { font-family:var(--font-heading); font-size:clamp(30px, 2vw + 18px, 42px); font-weight:700; color:#fff; line-height:1; }
.mv-stat-lbl { font-size:var(--fs-caption); color:rgba(255,255,255,0.7); margin-top:10px; font-weight:500; }

/* ── CTA ── */
.mv-cta { padding:var(--section-y) 0; background:var(--background-color); }
.mv-cta-box {
    display:flex; align-items:center; justify-content:space-between; gap:32px; flex-wrap:wrap;
    padding:40px 44px; border-radius:var(--radius-card);
    background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card);
    border-left:4px solid var(--gold-color);
}
.mv-cta-box h2 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0 0 6px; }
.mv-cta-box p { color:var(--text-light); font-size:var(--fs-body); margin:0; }
.mv-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }

/* Reveal on scroll */
.mv-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.mv-reveal.is-visible { opacity:1; transform:none; }

/* ── Responsive ── */
@media (max-width:1100px) { .mv-pillars { grid-template-columns:repeat(2,1fr); } }
@media (max-width:900px) {
    .mv-grid { grid-template-columns:1fr; }
    .mv-values-grid { grid-template-columns:1fr 1fr; }
    .mv-stats-grid { grid-template-columns:repeat(2,1fr); row-gap:28px; }
    .mv-stat:nth-child(3) { border-left:0; }
}
@media (max-width:600px) {
    .mv-hero { min-height:0; padding:56px 0; }
    .mv-card { padding:32px 24px 28px; }
    .mv-card::before { left:24px; }
    .mv-pillars, .mv-values-grid { grid-template-columns:1fr; }
    .mv-stat { padding:4px 10px; }
    .mv-cta-box { padding:28px 24px; }
    .mv-cta-btns { width:100%; }
    .mv-cta-btns .btn { flex:1 1 100%; }
}
@media (prefers-reduced-motion:reduce) { .mv-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="mv-hero">
    <div class="mv-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($mv_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="mv-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="about.php">About</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Mission &amp; Vision</span>
        </nav>
        <div class="mv-hero-badge">Our Direction</div>
        <h1>Mission &amp; <span>Vision</span></h1>
        <p>The purpose and long-term direction that guide everything we do at <?php echo htmlspecialchars(getSiteName()); ?>.</p>
    </div>
</section>

<!-- ── Statements ── -->
<section class="mv-statements">
    <div class="container">
        <div class="mv-grid">
            <article class="mv-card mv-reveal">
                <div class="mv-card-head">
                    <span class="ui-icon"><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                    <div>
                        <div class="mv-card-label">Why we exist</div>
                        <h2>Our Mission</h2>
                    </div>
                </div>
                <blockquote class="mv-quote">
                    <i class="fas fa-quote-left mv-quote-mark" aria-hidden="true"></i>
                    <?php echo nl2br(htmlspecialchars($about['mission'])); ?>
                </blockquote>
            </article>
            <article class="mv-card is-vision mv-reveal">
                <div class="mv-card-head">
                    <span class="ui-icon"><i class="fas fa-eye" aria-hidden="true"></i></span>
                    <div>
                        <div class="mv-card-label">Where we are heading</div>
                        <h2>Our Vision</h2>
                    </div>
                </div>
                <blockquote class="mv-quote">
                    <i class="fas fa-quote-left mv-quote-mark" aria-hidden="true"></i>
                    <?php echo nl2br(htmlspecialchars($about['vision'])); ?>
                </blockquote>
            </article>
        </div>
    </div>
</section>

<!-- ── Mission in practice ── -->
<section class="mv-practice">
    <div class="container">
        <div class="mv-sh">
            <div class="section-badge">In Practice</div>
            <h2>What Our Mission <span>Means Every Day</span></h2>
            <p>Four commitments that turn our mission into the daily experience of every student.</p>
        </div>
        <div class="mv-pillars">
            <?php foreach ($mv_pillars as $p): ?>
            <div class="ui-card mv-reveal">
                <span class="ui-icon"><i class="fas <?php echo $p[0]; ?>" aria-hidden="true"></i></span>
                <h3><?php echo htmlspecialchars($p[1]); ?></h3>
                <p><?php echo htmlspecialchars($p[2]); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Values ── -->
<?php if (!empty($mv_values)): ?>
<section class="mv-values">
    <div class="container">
        <div class="mv-sh">
            <div class="section-badge">Our Values</div>
            <h2>The Values Behind <span>Our Mission</span></h2>
            <p>The principles our teachers and students live by.</p>
        </div>
        <div class="mv-values-grid">
            <?php foreach ($mv_values as $v):
                $icon = trim($v['icon'] ?: 'fas fa-star');
                if (strpos($icon, 'fa-') === 0) $icon = 'fas ' . $icon;   // stored as "fa-x" or "fas fa-x"
            ?>
            <div class="mv-value mv-reveal">
                <span class="ui-icon"><i class="<?php echo htmlspecialchars($icon); ?>" aria-hidden="true"></i></span>
                <div>
                    <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                    <p><?php echo htmlspecialchars($v['description']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mv-values-more"><a href="core-values.php" class="ui-btn-outline">All Core Values <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>
<?php endif; ?>

<!-- ── Since 1993 ── -->
<section class="mv-stats" aria-label="At a glance">
    <div class="container">
        <div class="mv-stats-grid">
            <?php foreach ($about_stats as $s): ?>
            <div class="mv-stat">
                <div class="mv-stat-num"><?php echo htmlspecialchars($s['number']); ?></div>
                <div class="mv-stat-lbl"><?php echo htmlspecialchars($s['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="mv-cta">
    <div class="container">
        <div class="mv-cta-box mv-reveal">
            <div>
                <h2>See our mission in action</h2>
                <p>Meet the people who lead <?php echo htmlspecialchars(getSiteName()); ?>, or start your admission today.</p>
            </div>
            <div class="mv-cta-btns">
                <a href="leadership.php" class="btn btn-secondary"><i class="fas fa-users"></i> Our Leadership</a>
                <a href="admission.php" class="btn btn-primary"><i class="fas fa-graduation-cap"></i> Apply for Admission</a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var items = document.querySelectorAll('.mv-reveal');
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach(function (el) { el.classList.add('is-visible'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .mv-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 4) * 80 + 'ms';
            e.target.classList.add('is-visible');
            io.unobserve(e.target);
        });
    }, { threshold: 0.1 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
