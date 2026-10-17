<?php
require_once 'includes/config.php';
$page_title = 'Home';
$page_description = getSiteName() . ' — quality education that builds knowledge, character and confidence. Explore admissions, programs, faculty and campus life.';
?>
<?php include 'includes/header.php'; ?>

<!-- ===== HERO CAROUSEL (slides managed in AdminCP > Home > Hero Carousel) ===== -->
<?php
$carousel_slides = getHeroCarouselSlides();
if (empty($carousel_slides)) {
    // Fallback when no slides are active: a single slide built from the hero settings
    $hero = getHeroContent();
    $carousel_slides = [[
        'image_path' => $hero['image'],
        'title'      => 'Welcome to ' . getSiteName(),
        'subtitle'   => $hero['description'],
        'text_color' => '#ffffff',
    ]];
}
$slide_count = count($carousel_slides);
$HERO_INTERVAL = 6500; // ms per slide

// Crisp inline SVG icons for the slider (same stroke weight everywhere)
function heroIcon($name) {
    $paths = [
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'chevron'     => '<path d="m9 6 6 6-6 6"/>',
        'pause'       => '<path d="M9 5v14"/><path d="M15 5v14"/>',
        'play'        => '<path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.4-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5Z"/>',
        'award'       => '<circle cx="12" cy="9" r="6"/><path d="m8.5 13.9-1.5 8.1 5-3 5 3-1.5-8.1"/>',
        'building'    => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M6 12H4a2 2 0 0 0-2 2v8h4"/><path d="M18 9h2a2 2 0 0 1 2 2v11h-4"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M2 22h20"/>',
        'cap'         => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3.5 2.7 8.5 2.7 12 0v-5"/><path d="M22 10v6"/>',
    ];
    return '<svg class="hi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}
$hero_highlights = [
    ['award',    'Since ' . getSetting('camp_since_year', '1993')],
    ['building', 'Boys & Girls Campuses'],
    ['cap',      'Play Group to Intermediate'],
];
?>

<style>
/* ── Hero frame ── */
.hero-carousel {
    position:relative; overflow:hidden; padding:0;
    height:clamp(560px, calc(100vh - 138px), 720px);
    background:var(--primary-dark);
    isolation:isolate;
}

/* ── Slides: cross-fade; inactive slides are hidden from clicks and screen readers ── */
.carousel-slide {
    position:absolute; inset:0;
    display:flex; align-items:center;
    opacity:0; visibility:hidden;
    transition:opacity 0.9s ease, visibility 0s linear 0.9s;
}
.carousel-slide.active {
    opacity:1; visibility:visible; z-index:1;
    transition:opacity 0.9s ease, visibility 0s;
}

/* Photo: real <img> (better loading than a CSS background), slow subtle zoom while showing */
.cs-media { position:absolute; inset:0; overflow:hidden; }
.cs-media img {
    width:100%; height:100%;
    object-fit:cover; object-position:center 40%;
    transform:scale(1.03);
    transition:transform 8s ease-out;
}
.carousel-slide.active .cs-media img { transform:scale(1.09); }

/* Navy overlay: solid behind the text, opening up to show the campus on the right */
.cs-overlay {
    position:absolute; inset:0;
    background:
        linear-gradient(0deg, rgba(11,31,66,0.7) 0%, rgba(11,31,66,0) 32%),
        linear-gradient(90deg, rgba(11,31,66,0.92) 0%, rgba(16,42,86,0.80) 38%, rgba(16,42,86,0.38) 68%, rgba(16,42,86,0.10) 100%);
}

/* ── Content ── */
.cs-content { position:relative; z-index:2; width:100%; padding-bottom:64px; }
.cs-inner { max-width:720px; }

.cs-eyebrow {
    display:inline-flex; align-items:center; gap:12px;
    color:rgba(255,255,255,0.92);
    font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600;
    letter-spacing:2.4px; text-transform:uppercase;
    margin-bottom:20px;
}
.cs-eyebrow::before { content:''; width:34px; height:2px; border-radius:2px; background:var(--gold-color); flex-shrink:0; }

.cs-title {
    font-size:var(--fs-display);
    font-weight:700; line-height:1.14; letter-spacing:-0.6px;
    color:#fff; margin-bottom:18px;
    text-wrap:balance;
    text-shadow:0 2px 24px rgba(11,31,66,0.35);
}
.cs-sub {
    font-size:var(--fs-lead); line-height:1.75;
    color:rgba(255,255,255,0.86);
    max-width:580px; margin-bottom:34px;
    text-shadow:0 1px 12px rgba(11,31,66,0.35);
}
.hero-carousel .hi { width:18px; height:18px; flex-shrink:0; display:block; }
.cs-btns { display:flex; flex-wrap:wrap; gap:14px; }
.cs-btns .btn { padding:0 26px; gap:10px; }
.cs-btns .btn .hi { transition:transform 0.25s var(--ease-out); }
.cs-btns .btn:hover .hi { transform:translateX(4px); }
.cs-btns .btn-accent { box-shadow:0 10px 26px rgba(11,166,201,0.32); }
.cs-btns .cs-btn-ghost { background:rgba(255,255,255,0.06); border-color:rgba(255,255,255,0.5); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); }
.cs-btns .cs-btn-ghost:hover { background:#fff; border-color:#fff; color:var(--primary-color); }
.cs-btns .cs-btn-ghost .hi { width:16px; height:16px; }

/* Highlights under the buttons */
.cs-highlights { display:flex; flex-wrap:wrap; gap:10px 26px; list-style:none; margin:30px 0 0; padding:22px 0 0; border-top:1px solid rgba(255,255,255,0.16); max-width:620px; }
.cs-highlights li { display:inline-flex; align-items:center; gap:10px; color:rgba(255,255,255,0.88); font-size:var(--fs-body-sm); font-weight:500; }
.cs-hl-ico { width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:rgba(217,166,46,0.14); border:1px solid rgba(217,166,46,0.35); color:var(--gold-color); }
.cs-hl-ico .hi { width:17px; height:17px; }

/* Staggered entrance each time a slide becomes active */
.carousel-slide.active .cs-anim { animation:cs-fade-up 0.8s var(--ease-out) both; }
.carousel-slide.active .cs-anim:nth-child(2) { animation-delay:0.1s; }
.carousel-slide.active .cs-anim:nth-child(3) { animation-delay:0.2s; }
.carousel-slide.active .cs-anim:nth-child(4) { animation-delay:0.3s; }
@keyframes cs-fade-up { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:none; } }

/* ── Controls: counter + progress segments (left), pause / prev / next (right) ── */
.cs-controls { position:absolute; left:0; right:0; bottom:28px; z-index:5; }
.cs-controls .container { display:flex; align-items:center; justify-content:space-between; gap:20px; }
.cs-nav-left { display:flex; align-items:center; gap:18px; min-width:0; }
.cs-counter {
    font-family:var(--font-heading); font-weight:600; font-size:14px; letter-spacing:1px;
    color:rgba(255,255,255,0.6); white-space:nowrap; font-variant-numeric:tabular-nums;
}
.cs-counter b { color:#fff; font-size:17px; font-weight:700; }
.carousel-dots { display:flex; align-items:center; gap:8px; }
.carousel-dot {
    position:relative; width:46px; height:36px; padding:0;
    background:none; border:0; cursor:pointer;
}
.carousel-dot::before,
.carousel-dot span {
    content:''; position:absolute; left:0; right:0; top:50%; height:3px; margin-top:-1.5px; border-radius:3px;
}
.carousel-dot::before { background:rgba(255,255,255,0.3); transition:background 0.2s ease; }
.carousel-dot:hover::before { background:rgba(255,255,255,0.55); }
.carousel-dot span { right:auto; width:0; background:var(--gold-color); }
.carousel-dot.active span { width:100%; }
.hero-carousel.is-playing .carousel-dot.active span { width:0; animation:cs-progress var(--cs-interval) linear forwards; }
.hero-carousel.is-hold .carousel-dot.active span { animation-play-state:paused; }
@keyframes cs-progress { from { width:0; } to { width:100%; } }

/* One glass control bar: pause | previous · next */
.carousel-arrows {
    display:flex; align-items:center; gap:4px; padding:5px;
    border-radius:999px;
    background:rgba(11,31,66,0.42);
    border:1px solid rgba(255,255,255,0.16);
    backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
    box-shadow:0 10px 30px rgba(6,18,40,0.28);
}
.cs-btn-round {
    width:44px; height:44px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    color:#fff; cursor:pointer; padding:0;
    background:transparent; border:0;
    transition:background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}
.cs-btn-round:hover { background:rgba(255,255,255,0.14); }
.cs-btn-round:active { transform:scale(0.94); }
.carousel-next { background:#fff; color:var(--primary-color); }
.carousel-next:hover { background:var(--accent-color); color:#fff; }
.cs-ctrl-sep { width:1px; height:22px; background:rgba(255,255,255,0.22); margin:0 4px; }
.cs-pause .hi { width:17px; height:17px; }
.cs-pause .ico-play .hi { fill:currentColor; stroke:none; width:15px; height:15px; margin-left:2px; }
.cs-pause .ico-play, .hero-carousel:not(.is-playing) .cs-pause .ico-pause { display:none; }
.hero-carousel:not(.is-playing) .cs-pause .ico-play { display:flex; }
.hero-carousel button:focus-visible,
.hero-carousel a:focus-visible { outline:2px solid #fff; outline-offset:3px; }

/* ── Responsive ── */
@media (max-width:1024px) {
    .hero-carousel { height:clamp(540px, calc(100vh - 68px), 640px); }
}
@media (max-width:768px) {
    .hero-carousel { height:auto; min-height:560px; }
    .carousel-slide { align-items:flex-end; }
    .cs-media img { object-position:center; }
    .cs-overlay {
        background:
            linear-gradient(0deg, rgba(11,31,66,0.92) 0%, rgba(11,31,66,0.72) 45%, rgba(16,42,86,0.45) 100%);
    }
    .cs-content { padding:96px 0 92px; }
    .cs-eyebrow { letter-spacing:1.8px; margin-bottom:14px; }
    .cs-sub { margin-bottom:26px; }
    .cs-controls { bottom:20px; }
    .cs-counter { display:none; }
    .carousel-dot { width:34px; }
    .cs-btn-round { width:40px; height:40px; }
    .cs-highlights { gap:10px 18px; margin-top:24px; padding-top:18px; }
    .cs-highlights li { font-size:13px; }
    .cs-hl-ico { width:30px; height:30px; border-radius:9px; }
}
@media (max-width:480px) {
    .cs-btns { flex-direction:column; align-items:stretch; }
    .cs-btns .btn { justify-content:center; }
    .cs-highlights li:nth-child(3) { display:none; }
}

@media (prefers-reduced-motion:reduce) {
    .cs-media img, .carousel-slide.active .cs-media img { transform:none; transition:none; }
    .carousel-slide.active .cs-anim { animation:none; }
    .carousel-slide, .carousel-slide.active { transition:none; }
}
</style>

<section class="hero-carousel" id="heroCarousel" aria-roledescription="carousel" aria-label="Highlights" style="--cs-interval:<?php echo (int) $HERO_INTERVAL; ?>ms;">
    <?php foreach ($carousel_slides as $index => $slide):
        $txt = htmlspecialchars($slide['text_color'] ?? '#ffffff');
        // Only the first slide is a real <h1>; the rest use <h2> so the page never has two H1s
        $csTag = $index === 0 ? 'h1' : 'h2';
        $title = $slide['title'] ?: 'Welcome to ' . getSiteName();
    ?>
    <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>" id="heroSlide<?php echo $index; ?>"
         role="group" aria-roledescription="slide" aria-label="<?php echo ($index + 1) . ' of ' . $slide_count; ?>"
         <?php echo $index === 0 ? '' : 'aria-hidden="true"'; ?>>
        <div class="cs-media">
            <img src="<?php echo htmlspecialchars($slide['image_path']); ?>" alt=""
                 <?php echo $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
        </div>
        <div class="cs-overlay"></div>

        <div class="cs-content">
            <div class="container">
                <div class="cs-inner">
                    <div class="cs-eyebrow cs-anim"><?php echo htmlspecialchars(getSiteName()); ?></div>

                    <<?php echo $csTag; ?> class="cs-title cs-anim" style="color:<?php echo $txt; ?>;"><?php echo htmlspecialchars($title); ?></<?php echo $csTag; ?>>

                    <?php if (!empty($slide['subtitle'])): ?>
                    <p class="cs-sub cs-anim"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                    <?php endif; ?>

                    <div class="cs-btns cs-anim">
                        <a href="admission.php" class="btn btn-accent btn-lg">Apply Now <?php echo heroIcon('arrow-right'); ?></a>
                        <a href="courses.php" class="btn btn-outline btn-lg cs-btn-ghost">Explore Programs <?php echo heroIcon('chevron'); ?></a>
                    </div>

                    <ul class="cs-highlights cs-anim" aria-label="Highlights">
                        <?php foreach ($hero_highlights as $hl): ?>
                        <li><span class="cs-hl-ico"><?php echo heroIcon($hl[0]); ?></span><?php echo htmlspecialchars($hl[1]); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($slide_count > 1): ?>
    <div class="cs-controls">
        <div class="container">
            <div class="cs-nav-left">
                <div class="cs-counter" aria-hidden="true"><b id="heroCurrent">01</b> / <?php echo str_pad($slide_count, 2, '0', STR_PAD_LEFT); ?></div>
                <div class="carousel-dots" role="group" aria-label="Choose slide">
                    <?php foreach ($carousel_slides as $index => $slide): ?>
                    <button type="button" class="carousel-dot<?php echo $index === 0 ? ' active' : ''; ?>"
                            data-slide="<?php echo $index; ?>" aria-controls="heroSlide<?php echo $index; ?>"
                            aria-label="Go to slide <?php echo $index + 1; ?>"<?php echo $index === 0 ? ' aria-current="true"' : ''; ?>><span></span></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="carousel-arrows">
                <button type="button" class="cs-btn-round cs-pause" id="heroPause" aria-label="Pause slideshow"><span class="ico-pause"><?php echo heroIcon('pause'); ?></span><span class="ico-play"><?php echo heroIcon('play'); ?></span></button>
                <span class="cs-ctrl-sep" aria-hidden="true"></span>
                <button type="button" class="cs-btn-round carousel-prev" aria-label="Previous slide"><?php echo heroIcon('arrow-left'); ?></button>
                <button type="button" class="cs-btn-round carousel-next" aria-label="Next slide"><?php echo heroIcon('arrow-right'); ?></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var section = document.getElementById('heroCarousel');
    if (!section) return;
    var slides  = section.querySelectorAll('.carousel-slide');
    var dots    = section.querySelectorAll('.carousel-dot');
    var total   = slides.length;
    if (total < 2) return;

    var prevBtn  = section.querySelector('.carousel-prev');
    var nextBtn  = section.querySelector('.carousel-next');
    var pauseBtn = document.getElementById('heroPause');
    var counter  = document.getElementById('heroCurrent');
    var INTERVAL = <?php echo (int) $HERO_INTERVAL; ?>;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var current = 0;
    var playing = !reduceMotion;  // user's play/pause choice
    var held    = false;          // temporary hold: hover, keyboard focus, hidden tab
    var timer   = null, remaining = INTERVAL, startedAt = 0;

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function render() {
        section.classList.toggle('is-playing', playing);
        section.classList.toggle('is-hold', held);
        pauseBtn.setAttribute('aria-label', playing ? 'Pause slideshow' : 'Play slideshow');
    }

    function show(idx) {
        current = (idx + total) % total;
        slides.forEach(function (s, i) {
            var on = i === current;
            s.classList.toggle('active', on);
            if (on) s.removeAttribute('aria-hidden'); else s.setAttribute('aria-hidden', 'true');
        });
        dots.forEach(function (d, i) {
            var on = i === current;
            d.classList.toggle('active', on);
            if (on) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
            // restart the gold progress animation on the newly active segment
            var bar = d.querySelector('span'); bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = '';
        });
        if (counter) counter.textContent = pad(current + 1);
        remaining = INTERVAL;
        schedule();
    }

    function schedule() {
        clearTimeout(timer); timer = null;
        if (!playing || held) return;
        startedAt = Date.now();
        timer = setTimeout(function () { show(current + 1); }, remaining);
    }

    function hold(on) {
        if (held === on) return;
        held = on;
        if (on && timer) { clearTimeout(timer); timer = null; remaining = Math.max(400, remaining - (Date.now() - startedAt)); }
        render(); schedule();
    }

    nextBtn.addEventListener('click', function () { show(current + 1); });
    prevBtn.addEventListener('click', function () { show(current - 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); }); });
    pauseBtn.addEventListener('click', function () {
        playing = !playing; remaining = INTERVAL;
        // restart the progress segment so it matches the fresh timer
        show(current); render();
    });

    // Hold while the pointer is over the slider, while keyboard focus is inside it, or while the tab is hidden
    section.addEventListener('mouseenter', function () { hold(true); });
    section.addEventListener('mouseleave', function () { if (!section.contains(document.activeElement)) hold(false); });
    section.addEventListener('focusin', function () { hold(true); });
    section.addEventListener('focusout', function (e) { if (!section.contains(e.relatedTarget) && !section.matches(':hover')) hold(false); });
    document.addEventListener('visibilitychange', function () { hold(document.hidden); });

    // Arrow keys: only when focus is in the slider or nowhere in particular (never while typing)
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        var a = document.activeElement;
        if (a && a !== document.body && !section.contains(a)) return;
        show(current + (e.key === 'ArrowRight' ? 1 : -1));
    });

    // Swipe on touch screens
    var touchX = 0, touchY = 0;
    section.addEventListener('touchstart', function (e) { touchX = e.changedTouches[0].clientX; touchY = e.changedTouches[0].clientY; }, { passive: true });
    section.addEventListener('touchend', function (e) {
        var dx = touchX - e.changedTouches[0].clientX, dy = touchY - e.changedTouches[0].clientY;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(current + (dx > 0 ? 1 : -1));
    }, { passive: true });

    render(); schedule();
});
</script>

<!-- ===== NOTIFICATIONS TICKER ===== -->
<?php
$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE status = 'active' ORDER BY display_order ASC LIMIT 10");
if ($notifications && mysqli_num_rows($notifications) > 0):
?>
<div style="background:#fff;border-bottom:1px solid rgba(16,42,86,0.1);overflow:hidden;position:relative;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
    <div style="display:flex;align-items:stretch;">
        <div style="background:linear-gradient(135deg,var(--primary-color),#0B1F42);color:white;padding:13px 26px;font-weight:700;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;display:flex;align-items:center;gap:9px;flex-shrink:0;z-index:2;">
            <i class="fas fa-bullhorn" style="font-size:14px;color:var(--accent-color);"></i> Notifications
        </div>
        <div class="ticker-viewport" style="overflow:hidden;width:100%;display:flex;align-items:center;padding:0 20px;">
            <div class="notification-ticker" style="display:flex;animation:tickerScroll 30s linear infinite;white-space:nowrap;">
                <?php
                while ($notif = mysqli_fetch_assoc($notifications)) {
                    $tag = $notif['link'] ? 'a href="' . htmlspecialchars($notif['link']) . '"' : 'span';
                    $close = $notif['link'] ? 'a' : 'span';
                    echo '<' . $tag . ' style="color:var(--text-dark);text-decoration:none;font-size:13.5px;font-weight:500;padding:0 40px;display:inline-flex;align-items:center;gap:10px;">';
                    echo '<span style="width:7px;height:7px;border-radius:50%;background:var(--accent-color);flex-shrink:0;"></span>';
                    echo htmlspecialchars($notif['title']);
                    echo '</' . $close . '>';
                }
                mysqli_data_seek($notifications, 0);
                while ($notif = mysqli_fetch_assoc($notifications)) {
                    $tag = ($notif['link'] ? 'a href="' . htmlspecialchars($notif['link']) . '" tabindex="-1"' : 'span') . ' aria-hidden="true"';
                    $close = $notif['link'] ? 'a' : 'span';
                    echo '<' . $tag . ' style="color:var(--text-dark);text-decoration:none;font-size:13.5px;font-weight:500;padding:0 40px;display:inline-flex;align-items:center;gap:10px;">';
                    echo '<span style="width:7px;height:7px;border-radius:50%;background:var(--accent-color);flex-shrink:0;"></span>';
                    echo htmlspecialchars($notif['title']);
                    echo '</' . $close . '>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<style>
@keyframes tickerScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.notification-ticker:hover,
.notification-ticker:focus-within { animation-play-state: paused; }
/* Reduced motion: the strip stops moving and can be swiped/scrolled instead */
@media (prefers-reduced-motion: reduce) {
    .ticker-viewport { overflow-x: auto !important; }
}
</style>
<?php endif; ?>

<!-- ===== HOMEPAGE SECTIONS — shared styles (cards / icons / headers come from css/style.css §3) ===== -->
<style>
/* Section rhythm: alternating white / soft-blue surfaces */
.hp-section { padding:var(--section-y) 0; background:#fff; }
.hp-section.is-soft { background:var(--background-color); }
.hp-sh { text-align:center; margin-bottom:44px; }
.hp-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); line-height:1.25; letter-spacing:-0.3px; margin:0 0 12px; }
.hp-sh h2 span { color:var(--accent-hover); }
.hp-sh p { color:var(--text-light); font-size:var(--fs-body); line-height:1.7; max-width:620px; margin:0 auto; }
.hp-sh.is-left { text-align:left; margin-bottom:0; }
.hp-sh.is-left p { margin:0; }
.hp-more-link { text-align:center; margin-top:40px; }

.hp-grid { display:grid; gap:24px; }
.hp-grid.cols-3 { grid-template-columns:repeat(3,1fr); }
.hp-grid.cols-4 { grid-template-columns:repeat(4,1fr); }
.hp-grid.cols-5 { grid-template-columns:repeat(5,1fr); }

/* Gentle reveal on scroll (added by the script at the bottom of this block) */
.hp-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s ease, transform 0.6s ease; }
.hp-reveal.is-visible { opacity:1; transform:none; }

/* ── Stats band ── */
.hp-stats { padding:var(--section-y-sm) 0; background:var(--primary-color); color:#fff; }
.hp-stats .hp-sh h2 { color:#fff; }
.hp-stats .hp-sh h2 em { font-style:normal; color:var(--accent-bright); }
.hp-stats .section-badge { color:var(--accent-bright); }
.hp-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); }
.hp-stat { text-align:center; padding:6px 24px; }
.hp-stat + .hp-stat { border-left:1px solid rgba(255,255,255,0.12); }
.hp-stat i { font-size:22px; color:var(--accent-bright); margin-bottom:16px; display:block; }
.hp-stat-num { display:block; font-size:44px; font-weight:700; line-height:1; letter-spacing:-0.5px; margin-bottom:10px; }
.hp-stat-label { font-size:15px; font-weight:600; margin:0; }
.hp-stat-sub { font-size:13px; color:rgba(255,255,255,0.6); margin:4px 0 0; }

/* ── About ── */
.hp-about-grid { display:grid; grid-template-columns:5fr 7fr; gap:56px; align-items:center; }
.hp-about-text p.hp-lead { font-size:16px; line-height:1.8; color:var(--text-color); margin:20px 0 30px; }
.hp-mv { display:grid; gap:20px; }
.hp-mv .ui-card { flex-direction:row; gap:20px; align-items:flex-start; }
.hp-mv .ui-icon { margin-bottom:0; }

/* ── Programs ── */
.hp-prog .ui-card h3 { margin-bottom:12px; }
.hp-chip {
    display:inline-flex; align-items:center;
    padding:3px 10px; border-radius:6px;
    background:var(--light-blue); color:var(--accent-text);
    font-size:12px; font-weight:600;
}

/* ── Digital campus ── */
.hp-dms .ui-card { padding:24px; }
.hp-dms .ui-card h3 { font-size:var(--fs-card-title); }
.hp-dms .ui-card p { font-size:14px; }
.hp-app {
    margin-top:32px;
    display:flex; align-items:center; gap:28px; flex-wrap:wrap;
    background:var(--primary-color); color:#fff;
    border-radius:var(--radius-card); padding:32px 36px;
}
.hp-app-icon { width:60px; height:60px; border-radius:12px; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; font-size:26px; color:var(--accent-bright); flex-shrink:0; }
.hp-app-text { flex:1 1 280px; }
.hp-app-text h3 { font-size:20px; font-weight:600; margin:0 0 6px; color:#fff; }
.hp-app-text p { font-size:14.5px; line-height:1.7; color:rgba(255,255,255,0.7); margin:0; }
.hp-app-btns { display:flex; gap:12px; flex-wrap:wrap; }
.hp-store {
    display:inline-flex; align-items:center; gap:11px;
    padding:11px 20px; border-radius:var(--radius-sm);
    border:1px solid rgba(255,255,255,0.25); color:#fff;
    transition:background var(--transition), border-color var(--transition);
}
.hp-store:hover { background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.5); color:#fff; }
.hp-store i { font-size:22px; }
.hp-store small { display:block; font-size:var(--fs-caption-sm); letter-spacing:1px; opacity:0.7; }
.hp-store strong { display:block; font-size:14px; font-weight:600; }
.hp-store.is-disabled { opacity:0.45; cursor:not-allowed; }
.hp-store.is-disabled:hover { background:none; border-color:rgba(255,255,255,0.25); }

/* ── Why choose us ── */
.hp-wcu-grid { display:grid; grid-template-columns:4fr 7fr; gap:56px; align-items:center; }
.hp-wcu-text p { font-size:16px; line-height:1.75; color:var(--text-light); margin:16px 0 28px; }
.hp-wcu-stats { display:flex; gap:28px; margin-bottom:30px; }
.hp-wcu-stats div + div { padding-left:28px; border-left:1px solid var(--border-color); }
.hp-wcu-num { font-size:26px; font-weight:700; color:var(--primary-color); line-height:1; }
.hp-wcu-lbl { font-size:13px; color:var(--text-light); margin-top:6px; }
.hp-wcu-tiles {
    display:grid; grid-template-columns:repeat(3,1fr);
    background:#fff; border:1px solid var(--border-color);
    border-radius:var(--radius-card); box-shadow:var(--shadow-card); overflow:hidden;
}
.hp-wcu-tile { padding:28px 24px; border-right:1px solid var(--border-color); border-bottom:1px solid var(--border-color); transition:background var(--transition); }
.hp-wcu-tile:nth-child(3n) { border-right:0; }
.hp-wcu-tile:nth-last-child(-n+3) { border-bottom:0; }
.hp-wcu-tile:hover { background:var(--background-color); }
.hp-wcu-tile .ui-icon { width:42px; height:42px; font-size:16px; margin-bottom:16px; }
.hp-wcu-tile h3 { font-size:var(--fs-card-title-sm); font-weight:600; color:var(--primary-color); margin:0 0 6px; }
.hp-wcu-tile p { font-size:13.5px; line-height:1.65; color:var(--text-light); margin:0; }

/* ── Faculty ── */
.hp-fac-grid { display:flex; flex-wrap:wrap; justify-content:center; gap:24px; }
.hp-fac-grid .ui-card { flex:0 1 264px; padding:0; overflow:hidden; text-align:center; }
.hp-fac-photo { aspect-ratio:4 / 4.4; background:var(--light-blue); display:flex; align-items:center; justify-content:center; color:var(--accent-color); font-size:40px; }
.hp-fac-photo img { object-position:center 20%; }
.hp-fac-body { padding:20px 20px 22px; }
.hp-fac-body h3 { margin-bottom:4px; }
.hp-fac-desig { font-size:14px; font-weight:600; color:var(--accent-text); }
.hp-fac-dept { font-size:13px; color:var(--text-light); margin-top:4px; }

/* ── Institute ── */
.hp-inst .ui-card { padding:24px 20px; }
.hp-inst .ui-card h3 { font-size:var(--fs-card-title-sm); }
.hp-inst .ui-card p { font-size:13.5px; line-height:1.6; }


/* ── Activities ── */
.hp-act .ui-card { padding:0; overflow:hidden; }
.hp-act-media { display:flex; align-items:center; justify-content:center; color:var(--accent-color); font-size:34px; }
.hp-act-body { padding:22px 24px 24px; display:flex; flex-direction:column; flex:1; }
.hp-act-body .ui-meta { margin-bottom:10px; }

/* ── Closing CTA ── */
.hp-cta { padding:var(--section-y-sm) 0; background:var(--primary-color); }
.hp-cta-inner { display:flex; align-items:center; justify-content:space-between; gap:40px; flex-wrap:wrap; }
.hp-cta-text { flex:1 1 420px; }
.hp-cta .section-badge { color:var(--accent-bright); }
.hp-cta h2 { font-size:var(--fs-h2); font-weight:700; line-height:1.25; color:#fff; margin:0 0 12px; letter-spacing:-0.3px; }
.hp-cta h2 span { color:var(--accent-bright); }
.hp-cta p { font-size:15.5px; line-height:1.75; color:rgba(255,255,255,0.72); margin:0; max-width:520px; }
.hp-cta-actions { display:flex; flex-direction:column; gap:18px; }
.hp-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }
.hp-cta-pills { display:flex; gap:8px 18px; flex-wrap:wrap; }
.hp-cta-pills span { display:inline-flex; align-items:center; gap:7px; font-size:13px; color:rgba(255,255,255,0.75); }
.hp-cta-pills i { color:var(--gold-color); font-size:12px; }

/* ── Responsive ── */
@media (max-width:1100px) {
    .hp-grid.cols-5 { grid-template-columns:repeat(3,1fr); }
}
@media (max-width:900px) {
    .hp-grid.cols-3 { grid-template-columns:repeat(2,1fr); }
    .hp-grid.cols-4 { grid-template-columns:repeat(2,1fr); }
    .hp-about-grid, .hp-wcu-grid { grid-template-columns:1fr; gap:36px; }
    .hp-stats-grid { grid-template-columns:repeat(2,1fr); row-gap:36px; }
    .hp-stat:nth-child(3) { border-left:0; }
    .hp-wcu-tiles { grid-template-columns:repeat(2,1fr); }
    .hp-wcu-tile:nth-child(3n) { border-right:1px solid var(--border-color); }
    .hp-wcu-tile:nth-child(2n) { border-right:0; }
    .hp-wcu-tile:nth-last-child(-n+3) { border-bottom:1px solid var(--border-color); }
    .hp-wcu-tile:nth-last-child(-n+2) { border-bottom:0; }
}
@media (max-width:600px) {
    .hp-sh h2, .hp-cta h2 { font-size:var(--fs-h2); }
    .hp-sh { margin-bottom:32px; }
    .hp-grid.cols-3, .hp-grid.cols-4 { grid-template-columns:1fr; }
    .hp-grid.cols-5 { grid-template-columns:repeat(2,1fr); gap:14px; }
    .hp-stat { padding:4px 12px; }
    .hp-stat-num { font-size:34px; }
    .hp-mv .ui-card { flex-direction:column; gap:0; }
    .hp-mv .ui-icon { margin-bottom:16px; }
    .hp-wcu-tiles { grid-template-columns:1fr; }
    .hp-wcu-tile { border-right:0 !important; border-bottom:1px solid var(--border-color) !important; }
    .hp-wcu-tile:last-child { border-bottom:0 !important; }
    .hp-tier + .hp-tier { border-left:0; }
    .hp-tiers { grid-template-columns:repeat(2,1fr); }
    .hp-tier { border-bottom:1px solid var(--border-color); }
    .hp-tier:nth-child(odd) { border-right:1px solid var(--border-color); }
    .hp-app { padding:26px 22px; }
}
@media (prefers-reduced-motion:reduce) {
    .hp-reveal { opacity:1; transform:none; transition:none; }
}
</style>

<!-- ===== STATS ===== -->
<?php $stats = getStatistics(); $stats_header = getStatsHeader(); ?>
<section class="hp-stats">
    <div class="container">
        <div class="hp-sh">
            <div class="section-badge"><?php echo htmlspecialchars($stats_header['badge']); ?></div>
            <h2><?php echo renderHighlightedTitle($stats_header['title'], $stats_header['highlight']); ?></h2>
        </div>
        <div class="hp-stats-grid">
            <?php
            $stat_items = [
                ['fa-user-graduate',      $stats['students'], 'Active Students',     $stats_header['sub_students']],
                ['fa-chalkboard-teacher', $stats['teachers'], 'Expert Teachers',     $stats_header['sub_teachers']],
                ['fa-book-open',          $stats['courses'],  'Courses Offered',     $stats_header['sub_courses']],
                ['fa-award',              $stats['years'],    'Years of Excellence', $stats_header['sub_years']],
            ];
            foreach ($stat_items as $si): ?>
            <div class="hp-stat">
                <i class="fas <?php echo $si[0]; ?>"></i>
                <span class="hp-stat-num" data-count="<?php echo intval($si[1]); ?>"><?php echo intval($si[1]); ?>+</span>
                <p class="hp-stat-label"><?php echo $si[2]; ?></p>
                <p class="hp-stat-sub"><?php echo htmlspecialchars($si[3]); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== ABOUT ===== -->
<?php $home_about = getAboutContent(); ?>
<section class="hp-section hp-about">
    <div class="container">
        <div class="hp-about-grid">
            <div class="hp-about-text hp-reveal">
                <div class="hp-sh is-left">
                    <div class="section-badge">About Us</div>
                    <h2>Who We <span>Are</span></h2>
                </div>
                <?php if (!empty($home_about['description'])): ?>
                <p class="hp-lead"><?php echo htmlspecialchars($home_about['description']); ?></p>
                <?php endif; ?>
                <a href="about.php" class="ui-btn-outline">Learn More About Us <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (!empty($home_about['mission']) || !empty($home_about['vision'])): ?>
            <div class="hp-mv">
                <?php if (!empty($home_about['mission'])): ?>
                <div class="ui-card hp-reveal">
                    <div class="ui-icon"><i class="fas fa-bullseye"></i></div>
                    <div>
                        <h3>Our Mission</h3>
                        <p><?php echo htmlspecialchars($home_about['mission']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($home_about['vision'])): ?>
                <div class="ui-card hp-reveal">
                    <div class="ui-icon"><i class="fas fa-eye"></i></div>
                    <div>
                        <h3>Our Vision</h3>
                        <p><?php echo htmlspecialchars($home_about['vision']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== PROGRAMS ===== -->
<?php
$home_courses = [];
$hcr = mysqli_query($conn, "SELECT id, name, category, icon, duration, total_fee, admission_fee FROM courses WHERE status='active' ORDER BY display_order ASC, name ASC LIMIT 6");
if ($hcr) { while ($row = mysqli_fetch_assoc($hcr)) { $home_courses[] = $row; } }
$course_cat_labels = ['school' => 'Play Group – Class 8', 'matric' => 'Matric', 'inter' => 'Intermediate', 'regular' => 'Degree Program', 'short' => 'Short Course'];
?>
<?php if (!empty($home_courses)): ?>
<section class="hp-section is-soft hp-prog">
    <div class="container">
        <div class="hp-sh">
            <div class="section-badge">Programs</div>
            <h2>Our Academic <span>Programs</span></h2>
            <p>Programs currently offered at <?php echo htmlspecialchars(getSiteName()); ?>.</p>
        </div>
        <div class="hp-grid cols-3">
            <?php foreach ($home_courses as $c):
                $cat = strtolower(trim($c['category'] ?? ''));
                $cat_label = $course_cat_labels[$cat] ?? ucwords($cat);
            ?>
            <a href="courses.php" class="ui-card hp-reveal">
                <div class="ui-icon"><i class="fas <?php echo htmlspecialchars($c['icon'] ?: 'fa-book'); ?>"></i></div>
                <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                <div class="ui-meta">
                    <?php if ($cat_label !== ''): ?><span class="hp-chip"><?php echo htmlspecialchars($cat_label); ?></span><?php endif; ?>
                    <?php if (!empty($c['duration'])): ?><span><i class="far fa-clock"></i> <?php echo htmlspecialchars($c['duration']); ?></span><?php endif; ?>
                </div>
                <span class="ui-more">View details <i class="fas fa-arrow-right"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="hp-more-link"><a href="courses.php" class="ui-btn-outline">View All Programs <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>
<?php endif; ?>

<!-- ===== DIGITAL CAMPUS ===== -->
<?php $dms_header = getDmsHeader(); $features = getDmsFeatures(); ?>
<section class="hp-section hp-dms">
    <div class="container">
        <div class="hp-sh">
            <div class="section-badge"><?php echo htmlspecialchars($dms_header['badge']); ?></div>
            <h2><?php echo htmlspecialchars($dms_header['title_line1']); ?> <span><?php echo htmlspecialchars($dms_header['title_line2']); ?></span></h2>
            <p><?php echo htmlspecialchars(applySiteNamePlaceholder($dms_header['subtitle'])); ?></p>
        </div>

        <?php if (!empty($features)): ?>
        <div class="hp-grid cols-4">
            <?php foreach ($features as $f): ?>
            <div class="ui-card is-hoverable hp-reveal">
                <div class="ui-icon"><i class="<?php echo htmlspecialchars($f['icon']); ?>"></i></div>
                <h3><?php echo htmlspecialchars($f['title']); ?></h3>
                <p><?php echo htmlspecialchars($f['description']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="hp-app hp-reveal">
            <div class="hp-app-icon"><i class="fas fa-mobile-alt"></i></div>
            <div class="hp-app-text">
                <h3><?php echo htmlspecialchars($dms_header['cta_title']); ?></h3>
                <p><?php echo htmlspecialchars($dms_header['cta_desc']); ?></p>
            </div>
            <div class="hp-app-btns">
                <a href="<?php echo htmlspecialchars($dms_header['playstore_link']); ?>" class="hp-store">
                    <i class="fab fa-google-play"></i>
                    <span><small>GET IT ON</small><strong>Google Play</strong></span>
                </a>
                <a href="#" class="hp-store is-disabled" title="Coming Soon" aria-disabled="true" onclick="return false;">
                    <i class="fab fa-apple"></i>
                    <span><small>COMING SOON</small><strong>App Store</strong></span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===== LEADERSHIP ===== -->
<?php include 'includes/leadership_section.php'; ?>

<!-- ===== WHY CHOOSE US ===== -->
<?php $wcu_header = getWcuHeader(); $wcu_stats = getWcuStats(); $why = getWhyChooseUs(); ?>
<section class="hp-section hp-wcu">
    <div class="container">
        <div class="hp-wcu-grid">
            <div class="hp-wcu-text hp-reveal">
                <div class="hp-sh is-left">
                    <div class="section-badge"><?php echo htmlspecialchars($wcu_header['badge']); ?></div>
                    <h2>Why Choose <span><?php echo htmlspecialchars(SITE_NAME); ?>?</span></h2>
                </div>
                <p><?php echo htmlspecialchars($wcu_header['description']); ?></p>
                <?php if (!empty($wcu_stats)): ?>
                <div class="hp-wcu-stats">
                    <?php foreach ($wcu_stats as $s): ?>
                    <div>
                        <div class="hp-wcu-num"><?php echo htmlspecialchars($s['number']); ?></div>
                        <div class="hp-wcu-lbl"><?php echo htmlspecialchars($s['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <a href="admission.php" class="btn btn-primary">Apply Now <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (!empty($why)): ?>
            <div class="hp-wcu-tiles hp-reveal">
                <?php foreach ($why as $w): ?>
                <div class="hp-wcu-tile">
                    <div class="ui-icon"><i class="<?php echo htmlspecialchars($w['icon']); ?>"></i></div>
                    <h3><?php echo htmlspecialchars($w['title']); ?></h3>
                    <p><?php echo htmlspecialchars($w['description']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== FACULTY ===== -->
<?php
$home_faculty = [];
$hfr = mysqli_query($conn, "SELECT name, designation, department, photo, icon FROM faculty WHERE status='active' ORDER BY display_order ASC LIMIT 4");
if ($hfr) { while ($row = mysqli_fetch_assoc($hfr)) { $home_faculty[] = $row; } }
?>
<?php if (!empty($home_faculty)): ?>
<section class="hp-section is-soft hp-faculty">
    <div class="container">
        <div class="hp-sh">
            <div class="section-badge">Our Faculty</div>
            <h2>Meet Our <span>Faculty</span></h2>
        </div>
        <div class="hp-fac-grid">
            <?php foreach ($home_faculty as $f): ?>
            <a href="faculty.php" class="ui-card hp-reveal">
                <div class="ui-media hp-fac-photo">
                    <?php if (!empty($f['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($f['photo']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <i class="fas <?php echo htmlspecialchars($f['icon'] ?: 'fa-user-doctor'); ?>"></i>
                    <?php endif; ?>
                </div>
                <div class="hp-fac-body">
                    <h3><?php echo htmlspecialchars($f['name']); ?></h3>
                    <?php if (!empty($f['designation'])): ?><div class="hp-fac-desig"><?php echo htmlspecialchars($f['designation']); ?></div><?php endif; ?>
                    <?php if (!empty($f['department'])): ?><div class="hp-fac-dept"><?php echo htmlspecialchars($f['department']); ?></div><?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="hp-more-link"><a href="faculty.php" class="ui-btn-outline">Meet All Faculty <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>
<?php endif; ?>

<!-- ===== ACTIVITIES ===== -->
<?php
$home_activities = [];
$har = mysqli_query($conn, "SELECT title, category, event_date, image FROM events WHERE status='active' ORDER BY event_date DESC LIMIT 3");
if ($har) { while ($row = mysqli_fetch_assoc($har)) { $home_activities[] = $row; } }
?>
<?php if (!empty($home_activities)): ?>
<section class="hp-section hp-act">
    <div class="container">
        <div class="hp-sh">
            <div class="section-badge">Student Life</div>
            <h2>Recent <span>Activities</span></h2>
        </div>
        <div class="hp-grid cols-3">
            <?php foreach ($home_activities as $a): ?>
            <a href="activities.php" class="ui-card hp-reveal">
                <div class="ui-media hp-act-media">
                    <?php if (!empty($a['image'])): ?>
                        <img src="<?php echo htmlspecialchars($a['image']); ?>" alt="<?php echo htmlspecialchars($a['title']); ?>" loading="lazy">
                    <?php else: ?>
                        <i class="fas fa-calendar-days"></i>
                    <?php endif; ?>
                </div>
                <div class="hp-act-body">
                    <div class="ui-meta">
                        <span><i class="far fa-calendar"></i> <?php echo date('d M Y', strtotime($a['event_date'])); ?></span>
                        <?php if (!empty($a['category'])): ?><span class="hp-chip"><?php echo htmlspecialchars(ucwords($a['category'])); ?></span><?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                    <span class="ui-more">Read more <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="hp-more-link"><a href="activities.php" class="ui-btn-outline">View All Activities <i class="fas fa-arrow-right"></i></a></div>
    </div>
</section>
<?php endif; ?>

<!-- ===== CLOSING CTA ===== -->
<?php $cta_section = getCtaSection(); ?>
<section class="hp-cta">
    <div class="container">
        <div class="hp-cta-inner">
            <div class="hp-cta-text">
                <div class="section-badge"><?php echo htmlspecialchars($cta_section['badge']); ?></div>
                <h2><?php echo htmlspecialchars($cta_section['heading_line1']); ?> <span><?php echo htmlspecialchars($cta_section['heading_line2']); ?></span></h2>
                <p><?php echo htmlspecialchars(applySiteNamePlaceholder($cta_section['subtext'])); ?></p>
            </div>
            <div class="hp-cta-actions">
                <div class="hp-cta-btns">
                    <a href="admission.php" class="btn btn-accent">Apply for Admission <i class="fas fa-arrow-right"></i></a>
                    <a href="contact.php" class="btn btn-outline"><i class="fas fa-phone-alt"></i> Contact Us</a>
                </div>
                <div class="hp-cta-pills">
                    <?php foreach (['pill1', 'pill2', 'pill3', 'pill4'] as $pk): if (empty($cta_section[$pk])) continue; ?>
                    <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($cta_section[$pk]); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Reveal cards as they scroll into view (staggered within each row) */
    var items = document.querySelectorAll('.hp-reveal');
    if (!('IntersectionObserver' in window) || reduce) {
        items.forEach(function (el) { el.classList.add('is-visible'); });
    } else {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                var el = e.target, siblings = el.parentElement.querySelectorAll(':scope > .hp-reveal');
                var i = Array.prototype.indexOf.call(siblings, el);
                el.style.transitionDelay = (Math.max(i, 0) % 5) * 70 + 'ms';
                el.classList.add('is-visible');
                io.unobserve(el);
            });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    }

    /* Count stats up once when they first appear (final value is already in the HTML) */
    var nums = document.querySelectorAll('.hp-stat-num[data-count]');
    if (reduce || !nums.length || !('IntersectionObserver' in window)) return;
    var so = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var el = e.target, target = parseInt(el.dataset.count, 10) || 0, t0 = null;
            function tick(t) {
                if (!t0) t0 = t;
                var p = Math.min((t - t0) / 1400, 1), eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased) + '+';
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
            so.unobserve(el);
        });
    }, { threshold: 0.4 });
    nums.forEach(function (el) { so.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
