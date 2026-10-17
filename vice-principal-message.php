<?php
require_once 'includes/config.php';
$page_title = "Vice Principal's Message";
$page_description = "A message from the Vice Principal & Academic Incharge of " . getSiteName() . " on teaching, learning and academic progress.";

// Reuses the Leadership CMS (AdminCP > Home Page > Leadership): the Vice Principal is the
// first active entry whose designation or role title contains "vice principal".
$vp = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM leadership
    WHERE status = 'active' AND (LOWER(designation) LIKE '%vice principal%' OR LOWER(role_title) LIKE '%vice principal%')
    ORDER BY display_order ASC LIMIT 1
"));
$msg_pages = [
    ['principal-message.php', 'fa-user-graduate', "Principal's Message", 'A message from our Principal on learning and student life.',      "(LOWER(designation) LIKE '%principal%' OR LOWER(role_title) LIKE '%principal%') AND LOWER(designation) NOT LIKE '%vice%' AND LOWER(role_title) NOT LIKE '%vice%'"],
    ['vice-principal-message.php', 'fa-chalkboard-user', "Vice Principal's Message", 'A message from our Vice Principal on teaching and academics.', "LOWER(designation) LIKE '%vice principal%' OR LOWER(role_title) LIKE '%vice principal%'"],
    ['pro-message.php',       'fa-handshake',     "PRO's Message",       'A message from our Public Relations Officer to parents and the community.', "LOWER(designation) LIKE '%public relation%' OR LOWER(role_title) LIKE '%public relation%'"],
];
$more = [];
foreach ($msg_pages as $mp) {
    if ($mp[0] === basename($_SERVER['SCRIPT_NAME'])) continue;
    if (mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM leadership WHERE status = 'active' AND ({$mp[4]}) LIMIT 1"))) $more[] = $mp;
}
foreach ([['leadership.php', 'fa-users', 'Our Leadership', 'Messages from the people who guide our institution.'],
          ['core-values.php', 'fa-heart', 'Core Values', 'The principles our teachers and students live by.'],
          ['contact.php', 'fa-envelope', 'Contact Us', 'Reach the school administration for any query.']] as $fb) {
    if (count($more) < 3) $more[] = $fb;
}
$about = getAboutContent();
$chm_hero_image = getSetting('vp_hero_image', 'images/banners/banner-courtyard.jpg');

$paras = [];
$pull_quote = '';
if ($vp && trim($vp['message']) !== '') {
    $paras = array_values(array_filter(array_map('trim', preg_split('/\R+/', $vp['message']))));
    // first sentence becomes the large pull quote (only when the message is long enough to repeat it)
    if (preg_match('/^(.{30,220}?[.!?])(\s|$)/u', $paras[0], $m) && mb_strlen($vp['message']) > 260) {
        $pull_quote = $m[1];
    }
}
function vpInitials($name) {
    $ini = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $p) { $ini .= mb_strtoupper(mb_substr($p, 0, 1)); }
    return $ini ?: '?';
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Vice Principal's Message — uses the shared system in css/style.css
   ================================================================ */

/* ── Hero ── */
.chm-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:340px; display:flex; align-items:center;
    padding:68px 0; background:var(--primary-dark);
}
.chm-hero-img { position:absolute; inset:0; z-index:-2; }
.chm-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.chm-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.6) 100%);
}
.chm-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.chm-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.chm-breadcrumb a:hover { color:#fff; }
.chm-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.chm-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.chm-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.chm-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.chm-hero h1 span { color:var(--accent-bright); }
.chm-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:600px; }

/* ── Message ── */
.chm-section { padding:var(--section-y) 0; background:var(--background-color); }
.chm-pull {
    max-width:860px; margin:0 auto 40px; text-align:center;
    font-family:var(--font-heading); font-weight:600;
    font-size:clamp(20px, 1.2vw + 14px, 28px); line-height:1.5; color:var(--primary-color);
}
.chm-pull i { display:block; color:var(--gold-color); font-size:26px; margin-bottom:14px; }
.chm-card {
    display:grid; grid-template-columns:330px 1fr;
    max-width:1080px; margin:0 auto;
    background:#fff; border:1px solid var(--border-color);
    border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden;
}
.chm-side {
    background:var(--primary-color); color:#fff;
    padding:48px 32px; text-align:center;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
}
.chm-portrait {
    width:184px; height:184px; border-radius:50%;
    outline:2px solid var(--gold-color); outline-offset:7px;
    overflow:hidden; background:rgba(255,255,255,0.08); margin-bottom:28px;
}
.chm-portrait img { width:100%; height:100%; object-fit:cover; object-position:center 18%; }
.chm-initials { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-family:var(--font-heading); font-size:58px; font-weight:700; color:var(--accent-bright); }
.chm-side-role { color:var(--accent-bright); font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:1.8px; text-transform:uppercase; margin-bottom:8px; }
.chm-side h2 { font-size:22px; font-weight:600; line-height:1.3; margin:0 0 4px; color:#fff; }
.chm-side p { font-size:var(--fs-body-sm); color:rgba(255,255,255,0.7); margin:0; }
.chm-side-note { margin-top:16px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.14); font-size:var(--fs-caption); line-height:1.6; color:rgba(255,255,255,0.7); max-width:260px; margin-left:auto; margin-right:auto; }

.chm-body { padding:52px 56px; }
.chm-label { display:flex; align-items:center; gap:12px; margin-bottom:8px; }
.chm-label span { font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:2px; text-transform:uppercase; color:var(--accent-text); }
.chm-label::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.chm-body h3 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0 0 22px; }
.chm-text { font-size:var(--fs-body); line-height:1.9; color:var(--text-color); }
.chm-text p { margin:0 0 16px; }
.chm-text p:first-child::first-letter {
    float:left; font-family:var(--font-heading); font-size:56px; line-height:0.9; font-weight:700;
    color:var(--primary-color); margin:6px 10px 0 0;
}
.chm-sign { display:flex; align-items:center; gap:18px; margin-top:28px; padding-top:22px; border-top:1px solid var(--border-color); }
.chm-sign img { height:46px; width:auto; max-width:150px; opacity:0.85; filter:saturate(0); }
.chm-sign-sep { width:1px; height:40px; background:var(--border-color); }
.chm-sign strong { display:block; font-family:var(--font-heading); font-size:16px; font-weight:600; color:var(--primary-color); }
.chm-sign span { font-size:var(--fs-caption); color:var(--text-light); }

.chm-empty {
    max-width:640px; margin:0 auto; text-align:center;
    padding:52px 32px; border-radius:var(--radius-card);
    background:#fff; border:1px dashed var(--input-border);
}
.chm-empty .ui-icon { margin:0 auto 18px; width:60px; height:60px; font-size:24px; }
.chm-empty h3 { font-size:var(--fs-h3); color:var(--primary-color); margin:0 0 8px; }
.chm-empty p { color:var(--text-light); margin:0 0 22px; }

/* ── Direction (mission & vision) ── */
.chm-direction { padding:var(--section-y) 0; background:#fff; }
.chm-sh { text-align:center; margin-bottom:40px; }
.chm-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 10px; }
.chm-sh h2 span { color:var(--accent-hover); }
.chm-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0 auto; max-width:620px; }
.chm-mv { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.chm-mv .ui-card { padding:30px; }
.chm-mv .ui-card h3 { font-size:var(--fs-h3); }
.chm-mv .ui-card p { font-size:var(--fs-body); color:var(--text-color); line-height:1.8; }

/* ── More ── */
.chm-more { padding:var(--section-y) 0; background:var(--background-color); }
.chm-more-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }

/* Reveal on scroll */
.chm-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.chm-reveal.is-visible { opacity:1; transform:none; }

/* ── Responsive ── */
@media (max-width:1024px) {
    .chm-card { grid-template-columns:280px 1fr; }
    .chm-body { padding:42px 38px; }
}
@media (max-width:860px) {
    .chm-card { grid-template-columns:1fr; }
    .chm-side { padding:38px 24px; }
    .chm-portrait { width:156px; height:156px; }
    .chm-mv { grid-template-columns:1fr; }
}
@media (max-width:760px) { .chm-more-grid { grid-template-columns:1fr; } }
@media (max-width:600px) {
    .chm-hero { min-height:0; padding:56px 0; }
    .chm-pull { margin-bottom:28px; }
    .chm-body { padding:30px 22px; }
    .chm-text p:first-child::first-letter { font-size:46px; }
    .chm-mv .ui-card { padding:24px; }
}
@media (prefers-reduced-motion:reduce) { .chm-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="chm-hero">
    <div class="chm-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($chm_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="chm-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="about.php">About</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="leadership.php">Leadership</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Vice Principal's Message</span>
        </nav>
        <div class="chm-hero-badge">From the Vice Principal's Desk</div>
        <h1>Vice Principal's <span>Message</span></h1>
        <p>A message from our Vice Principal &amp; Academic Incharge on teaching, learning and every student's academic progress.</p>
    </div>
</section>

<!-- ── Message ── -->
<section class="chm-section">
    <div class="container">
        <?php if ($vp && $paras): ?>

            <?php if ($pull_quote): ?>
            <blockquote class="chm-pull chm-reveal">
                <i class="fas fa-quote-left" aria-hidden="true"></i>
                <?php echo htmlspecialchars($pull_quote); ?>
            </blockquote>
            <?php endif; ?>

            <article class="chm-card chm-reveal">
                <div class="chm-side">
                    <div class="chm-portrait">
                        <?php if (!empty($vp['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($vp['photo']); ?>" alt="<?php echo htmlspecialchars($vp['name']); ?>"
                                 onerror="this.outerHTML='<div class=&quot;chm-initials&quot;><?php echo htmlspecialchars(vpInitials($vp['name']), ENT_QUOTES); ?></div>'">
                        <?php else: ?>
                            <div class="chm-initials" aria-hidden="true"><?php echo htmlspecialchars(vpInitials($vp['name'])); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($vp['role_title'])): ?><div class="chm-side-role"><?php echo htmlspecialchars($vp['role_title']); ?></div><?php endif; ?>
                    <h2><?php echo htmlspecialchars($vp['name']); ?></h2>
                    <?php if (!empty($vp['designation'])): ?><p><?php echo htmlspecialchars($vp['designation']); ?></p><?php endif; ?>
                </div>
                <div class="chm-body">
                    <div class="chm-label"><span>Message</span></div>
                    <h3>A Message from Our Vice Principal</h3>
                    <div class="chm-text">
                        <?php foreach ($paras as $p): ?><p><?php echo htmlspecialchars($p); ?></p><?php endforeach; ?>
                    </div>
                    <div class="chm-sign">
                        <?php if (!empty($vp['signature'])): ?>
                            <img src="<?php echo htmlspecialchars($vp['signature']); ?>" alt="Signature of <?php echo htmlspecialchars($vp['name']); ?>" loading="lazy" onerror="this.nextElementSibling.remove();this.remove();">
                            <span class="chm-sign-sep" aria-hidden="true"></span>
                        <?php endif; ?>
                        <div>
                            <strong><?php echo htmlspecialchars($vp['name']); ?></strong>
                            <span><?php echo htmlspecialchars(($vp['designation'] ?: 'Vice Principal') . ', ' . getSiteName()); ?></span>
                        </div>
                    </div>
                </div>
            </article>

        <?php else: ?>
            <div class="chm-empty">
                <span class="ui-icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></span>
                <h3>Vice Principal's message coming soon</h3>
                <p>A message from our Vice Principal will be published here shortly.</p>
                <a href="leadership.php" class="btn btn-primary"><i class="fas fa-users"></i> View Our Leadership</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Direction ── -->
<?php if (!empty($about['mission']) || !empty($about['vision'])): ?>
<section class="chm-direction">
    <div class="container">
        <div class="chm-sh">
            <div class="section-badge">Our Direction</div>
            <h2>The Direction <span>We Follow</span></h2>
            <p>The mission and vision that guide our leadership and every member of our school community.</p>
        </div>
        <div class="chm-mv">
            <?php if (!empty($about['mission'])): ?>
            <div class="ui-card chm-reveal">
                <span class="ui-icon"><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                <h3>Our Mission</h3>
                <p><?php echo htmlspecialchars($about['mission']); ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($about['vision'])): ?>
            <div class="ui-card chm-reveal">
                <span class="ui-icon"><i class="fas fa-eye" aria-hidden="true"></i></span>
                <h3>Our Vision</h3>
                <p><?php echo htmlspecialchars($about['vision']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── More ── -->
<section class="chm-more">
    <div class="container">
        <div class="chm-sh">
            <div class="section-badge">Learn More</div>
            <h2>More From <span>Our Leadership</span></h2>
        </div>
        <div class="chm-more-grid">
            <?php foreach ($more as $c): ?>
            <a href="<?php echo $c[0]; ?>" class="ui-card chm-reveal">
                <span class="ui-icon"><i class="fas <?php echo $c[1]; ?>" aria-hidden="true"></i></span>
                <h3><?php echo $c[2]; ?></h3>
                <p><?php echo $c[3]; ?></p>
                <span class="ui-more">Read more <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function () {
    var items = document.querySelectorAll('.chm-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { items.forEach(show); return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .chm-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 3) * 90 + 'ms';
            show(e.target); io.unobserve(e.target);
        });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
