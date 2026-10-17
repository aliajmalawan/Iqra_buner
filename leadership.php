<?php
require_once 'includes/config.php';
$page_title = 'Our Leadership';
$page_description = 'Meet the leadership of ' . getSiteName() . ' and read their messages to our students, parents and community.';

$leaders = getLeadershipMessages();          // AdminCP > About / Home Page > Leadership
$leader_count = count($leaders);
$ld_hero_image = getSetting('ld_hero_image', 'images/banners/banner-building.jpg');
$phone_numbers = array_filter(array_map('trim', explode(',', getSitePhone())));
$first_phone = reset($phone_numbers) ?: '';

function ldInitials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $ini = '';
    foreach (array_slice($parts, 0, 2) as $p) { $ini .= mb_strtoupper(mb_substr($p, 0, 1)); }
    return $ini ?: '?';
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Leadership — uses the shared system in css/style.css
   ================================================================ */

/* ── Hero ── */
.ld-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:360px; display:flex; align-items:center;
    padding:72px 0; background:var(--primary-dark);
}
.ld-hero-img { position:absolute; inset:0; z-index:-2; }
.ld-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.ld-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%);
}
.ld-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.ld-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.ld-breadcrumb a:hover { color:#fff; }
.ld-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.ld-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.ld-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.ld-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.ld-hero h1 span { color:var(--accent-bright); }
.ld-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:620px; }

.ld-sh { text-align:center; margin-bottom:44px; }
.ld-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.ld-sh h2 span { color:var(--accent-hover); }
.ld-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

/* Initials shown when a leader has no photo */
.ld-initials {
    width:100%; height:100%; display:flex; align-items:center; justify-content:center;
    font-family:var(--font-heading); font-size:64px; font-weight:700; color:var(--accent-color);
}

/* ── Messages ── */
.ld-messages { padding:var(--section-y) 0; background:var(--background-color); }
.ld-message {
    display:grid; grid-template-columns:340px 1fr; gap:0;
    background:#fff; border:1px solid var(--border-color);
    border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden;
    scroll-margin-top:var(--header-offset);
}
.ld-message + .ld-message { margin-top:32px; }
.ld-message.is-reverse { grid-template-columns:1fr 340px; }
.ld-message.is-reverse .ld-message-side { order:2; }

.ld-message-side {
    background:var(--primary-color); color:#fff;
    padding:44px 32px; text-align:center;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
}
.ld-portrait {
    width:176px; height:176px; border-radius:50%;
    outline:2px solid var(--gold-color); outline-offset:6px;
    overflow:hidden; background:rgba(255,255,255,0.08); margin-bottom:26px;
}
.ld-portrait img { width:100%; height:100%; object-fit:cover; object-position:center 18%; }
.ld-portrait .ld-initials { color:var(--accent-bright); font-size:56px; }
.ld-side-role { color:var(--accent-bright); font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:1.8px; text-transform:uppercase; margin-bottom:8px; }
.ld-side-name { font-family:var(--font-heading); font-size:21px; font-weight:600; line-height:1.3; margin:0 0 4px; color:#fff; }
.ld-side-desig { font-size:var(--fs-body-sm); color:rgba(255,255,255,0.7); margin:0; }

.ld-message-body { padding:48px 52px; display:flex; flex-direction:column; justify-content:center; }
.ld-message-label { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
.ld-message-label i { font-size:26px; color:var(--accent-color); }
.ld-message-label span { font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:2px; text-transform:uppercase; color:var(--accent-text); }
.ld-message-text { font-size:var(--fs-body); line-height:1.9; color:var(--text-color); }
.ld-message-text p { margin:0 0 14px; }
.ld-message-text p:first-child::first-letter {
    float:left; font-family:var(--font-heading); font-size:54px; line-height:0.9; font-weight:700;
    color:var(--primary-color); margin:6px 10px 0 0;
}
.ld-sign {
    display:flex; align-items:center; gap:18px;
    margin-top:26px; padding-top:22px; border-top:1px solid var(--border-color);
}
.ld-sign img { height:44px; width:auto; max-width:140px; opacity:0.85; filter:saturate(0); }
.ld-sign-sep { width:1px; height:38px; background:var(--border-color); }
.ld-sign strong { display:block; font-family:var(--font-heading); font-size:16px; font-weight:600; color:var(--primary-color); }
.ld-sign span { font-size:var(--fs-caption); color:var(--text-light); }

.ld-empty {
    max-width:640px; margin:0 auto; text-align:center;
    padding:48px 32px; border-radius:var(--radius-card);
    background:#fff; border:1px dashed var(--input-border);
}
.ld-empty .ui-icon { margin:0 auto 18px; width:60px; height:60px; font-size:24px; }
.ld-empty h3 { font-size:var(--fs-h3); color:var(--primary-color); margin:0 0 8px; }
.ld-empty p { color:var(--text-light); margin:0 0 22px; }

/* ── Values band ── */
.ld-band { padding:var(--section-y-sm) 0; background:var(--primary-color); }
.ld-band-inner { display:flex; align-items:center; justify-content:space-between; gap:28px; flex-wrap:wrap; }
.ld-band .section-badge { color:var(--accent-bright); }
.ld-band h2 { font-size:var(--fs-h3); color:#fff; margin:0; max-width:620px; line-height:1.4; }
.ld-band-btns { display:flex; gap:12px; flex-wrap:wrap; }

/* ── Contact the principal's office ── */
.ld-contact { padding:var(--section-y) 0; background:#fff; }
.ld-contact-box {
    display:grid; grid-template-columns:1.2fr 1fr; gap:32px; align-items:center;
    padding:40px 44px; border-radius:var(--radius-card);
    background:var(--background-color); border:1px solid var(--border-color);
    border-left:4px solid var(--gold-color);
}
.ld-contact-box h2 { font-size:var(--fs-h3); color:var(--primary-color); margin:0 0 8px; }
.ld-contact-box p { color:var(--text-light); margin:0; line-height:1.7; }
.ld-contact-list { display:flex; flex-direction:column; gap:12px; }
.ld-contact-list a {
    display:flex; align-items:center; gap:14px;
    padding:14px 16px; border-radius:10px;
    background:#fff; border:1px solid var(--border-color);
    color:var(--text-color); text-decoration:none; word-break:break-word;
    transition:border-color var(--dur) ease;
}
.ld-contact-list a:hover { border-color:var(--accent-color); }
.ld-contact-list .ui-icon { margin:0; width:40px; height:40px; font-size:15px; }
.ld-contact-list small { display:block; font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--text-light); }
.ld-contact-list strong { font-weight:600; color:var(--primary-color); }

/* Reveal on scroll */
.ld-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.ld-reveal.is-visible { opacity:1; transform:none; }

/* ── Responsive ── */
@media (max-width:1024px) {
    .ld-message, .ld-message.is-reverse { grid-template-columns:280px 1fr; }
    .ld-message.is-reverse { grid-template-columns:1fr 280px; }
    .ld-message-body { padding:40px 36px; }
}
@media (max-width:860px) {
    .ld-message, .ld-message.is-reverse { grid-template-columns:1fr; }
    .ld-message.is-reverse .ld-message-side { order:0; }
    .ld-message-side { padding:36px 24px; }
    .ld-portrait { width:150px; height:150px; }
    .ld-contact-box { grid-template-columns:1fr; }
}
@media (max-width:600px) {
    .ld-hero { min-height:0; padding:56px 0; }
    .ld-message-body { padding:30px 22px; }
    .ld-message-text p:first-child::first-letter { font-size:44px; }
    .ld-band-btns, .ld-band-btns .btn { width:100%; }
    .ld-contact-box { padding:28px 22px; }
}
@media (prefers-reduced-motion:reduce) { .ld-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="ld-hero">
    <div class="ld-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($ld_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="ld-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="about.php">About</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Leadership</span>
        </nav>
        <div class="ld-hero-badge">Meet the Team</div>
        <h1>Our <span>Leadership</span></h1>
        <p>The people guiding <?php echo htmlspecialchars(getSiteName()); ?> — and their message to our students, parents and community.</p>
    </div>
</section>

<?php if ($leader_count): ?>
<!-- ── Messages ── -->
<section class="ld-messages">
    <div class="container">
        <div class="ld-sh">
            <div class="section-badge">In Their Words</div>
            <h2>Messages from Our <span>Leadership</span></h2>
        </div>
        <?php foreach ($leaders as $i => $l): if (empty($l['message'])) continue;
            $paras = array_filter(array_map('trim', preg_split('/\R{2,}|\R/', $l['message'])));
        ?>
        <article class="ld-message ld-reveal<?php echo $i % 2 ? ' is-reverse' : ''; ?>" id="message-<?php echo (int) $l['id']; ?>">
            <div class="ld-message-side">
                <div class="ld-portrait">
                    <?php if (!empty($l['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($l['photo']); ?>" alt="<?php echo htmlspecialchars($l['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="ld-initials" aria-hidden="true"><?php echo htmlspecialchars(ldInitials($l['name'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($l['role_title'])): ?><div class="ld-side-role"><?php echo htmlspecialchars($l['role_title']); ?></div><?php endif; ?>
                <h3 class="ld-side-name"><?php echo htmlspecialchars($l['name']); ?></h3>
                <?php if (!empty($l['designation'])): ?><p class="ld-side-desig"><?php echo htmlspecialchars($l['designation']); ?></p><?php endif; ?>
            </div>
            <div class="ld-message-body">
                <div class="ld-message-label"><i class="fas fa-quote-left" aria-hidden="true"></i><span>Message from the <?php echo htmlspecialchars($l['role_title'] ?: $l['designation'] ?: 'Leadership'); ?></span></div>
                <div class="ld-message-text">
                    <?php foreach ($paras as $p): ?><p><?php echo htmlspecialchars($p); ?></p><?php endforeach; ?>
                </div>
                <div class="ld-sign">
                    <?php if (!empty($l['signature'])): ?>
                        <img src="<?php echo htmlspecialchars($l['signature']); ?>" alt="Signature of <?php echo htmlspecialchars($l['name']); ?>" loading="lazy" onerror="this.nextElementSibling.remove();this.remove();">
                        <span class="ld-sign-sep" aria-hidden="true"></span>
                    <?php endif; ?>
                    <div>
                        <strong><?php echo htmlspecialchars($l['name']); ?></strong>
                        <span><?php echo htmlspecialchars($l['designation']); ?></span>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php else: ?>
<!-- ── No leaders published yet ── -->
<section class="ld-messages">
    <div class="container">
        <div class="ld-empty">
            <span class="ui-icon"><i class="fas fa-users" aria-hidden="true"></i></span>
            <h3>Leadership profiles coming soon</h3>
            <p>Messages from our Principal and leadership team will be published here shortly.</p>
            <a href="contact.php" class="btn btn-primary"><i class="fas fa-envelope"></i> Contact the School</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── Values band ── -->
<section class="ld-band">
    <div class="container">
        <div class="ld-band-inner">
            <div>
                <div class="section-badge">Our Direction</div>
                <h2>Leadership guided by a clear mission and strong values.</h2>
            </div>
            <div class="ld-band-btns">
                <a href="mission-vision.php" class="btn btn-accent"><i class="fas fa-compass"></i> Mission &amp; Vision</a>
                <a href="core-values.php" class="btn btn-outline"><i class="fas fa-heart"></i> Core Values</a>
            </div>
        </div>
    </div>
</section>

<!-- ── Contact ── -->
<section class="ld-contact">
    <div class="container">
        <div class="ld-contact-box ld-reveal">
            <div>
                <div class="section-badge">Get in Touch</div>
                <h2>Contact the Principal's Office</h2>
                <p>Parents and students are welcome to contact the school administration about admissions, academics or any concern.</p>
            </div>
            <div class="ld-contact-list">
                <?php if ($first_phone): ?>
                <a href="tel:<?php echo htmlspecialchars(str_replace([' ', '-'], '', $first_phone)); ?>">
                    <span class="ui-icon"><i class="fas fa-phone-alt" aria-hidden="true"></i></span>
                    <span><small>Call</small><strong><?php echo htmlspecialchars($first_phone); ?></strong></span>
                </a>
                <?php endif; ?>
                <a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>">
                    <span class="ui-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                    <span><small>Email</small><strong><?php echo htmlspecialchars(getSiteEmail()); ?></strong></span>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var items = document.querySelectorAll('.ld-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach(show); return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .ld-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 3) * 90 + 'ms';
            show(e.target); io.unobserve(e.target);
        });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
    // a message opened from a profile card (#message-N) is shown immediately
    function reveal() { if (!location.hash) return; var t = document.getElementById(location.hash.slice(1)); if (t) show(t); }
    reveal(); window.addEventListener('hashchange', reveal);
})();
</script>

<?php include 'includes/footer.php'; ?>
