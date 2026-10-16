<?php
require_once 'includes/config.php';
$page_title = 'Events & News';
$page_description = 'Stay updated with the latest events, seminars and news from IQRA School & College Daggar Buner.';

$events_result = mysqli_query($conn, "SELECT * FROM events WHERE event_date >= CURDATE() AND status = 'active' ORDER BY event_date ASC LIMIT 6");
$events = [];
if ($events_result) while ($row = mysqli_fetch_assoc($events_result)) $events[] = $row;

$news_result = mysqli_query($conn, "SELECT * FROM news WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");
$news_items = [];
if ($news_result) while ($row = mysqli_fetch_assoc($news_result)) $news_items[] = $row;
?>
<?php include 'includes/header.php'; ?>

<style>
/* ── Events & News Page ── */
.ev-hero {
    position:relative; overflow:hidden;
    min-height:280px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.85),rgba(16,42,86,0.9)),
               url('https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1600') center/cover no-repeat;
    padding:70px 0;
}
.ev-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:18px; }
.ev-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.ev-breadcrumb a:hover { color:var(--accent-color); }
.ev-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
}
.ev-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; margin:0 0 12px; line-height:1.15; text-shadow:0 4px 20px rgba(0,0,0,0.4); }
.ev-hero h1 span {
    background:linear-gradient(90deg,var(--accent-color),#0BA6C9);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.ev-hero p { color:rgba(255,255,255,0.75); font-size:var(--fs-lead); margin:0; max-width:560px; line-height:1.7; }

.ev-section { padding:var(--section-y) 0; }
.ev-section.ev-alt { background:var(--bg-light); }
.ev-sh { text-align:center; margin-bottom:40px; }
.ev-sh-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(16,42,86,0.08); border:1px solid rgba(16,42,86,0.22);
    color:var(--primary-color); padding:6px 16px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:12px;
}
.ev-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--text-dark); margin:0 0 8px; }
.ev-sh h2 span { color:var(--primary-color); }
.ev-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0; }

/* Event cards */
.ev-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:24px; }
.ev-card {
    background:#fff; border:1px solid rgba(16,42,86,0.1); border-radius:var(--radius-card); overflow:hidden;
    box-shadow:0 4px 18px rgba(16,42,86,0.06);
    transition:transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    opacity:0; transform:translateY(26px);
    display:flex; flex-direction:column;
}
.ev-card.ev-in { animation:evIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes evIn { from{opacity:0;transform:translateY(16px);} to{opacity:1;transform:translateY(0);} }
.ev-card:hover { transform:translateY(-4px); box-shadow:0 16px 40px rgba(16,42,86,0.12); border-color:rgba(16,42,86,0.2); }
.ev-card img { width:100%; height:190px; object-fit:cover; display:block; }
.ev-card-body { padding:22px 22px 24px; display:flex; flex-direction:column; flex:1; }
.ev-date-pill {
    display:inline-flex; align-items:center; gap:6px; align-self:flex-start;
    background:rgba(11,166,201,0.12); color:var(--accent-color);
    padding:5px 14px; border-radius:var(--radius-card); font-size:12px; font-weight:700; margin-bottom:14px;
}
.ev-card h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--text-dark); margin:0 0 10px; }
.ev-card p { font-size:13.5px; color:var(--text-light); line-height:1.7; margin:0 0 14px; }
.ev-meta { display:flex; flex-direction:column; gap:6px; margin-top:auto; padding-top:14px; border-top:1px solid rgba(16,42,86,0.07); }
.ev-meta-row { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-light); }
.ev-meta-row i { color:var(--primary-color); font-size:11px; width:13px; }

/* News list (mirrors news.php's card language) */
.ev-news-list { max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:24px; }
.ev-news-card {
    background:#fff; border-radius:var(--radius-card); overflow:hidden;
    box-shadow:0 4px 18px rgba(16,42,86,0.06);
    display:grid; grid-template-columns:260px 1fr;
    opacity:0; transform:translateY(26px);
}
.ev-news-card.ev-in { animation:evIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.ev-news-card img { width:100%; height:100%; min-height:180px; object-fit:cover; display:block; }
.ev-news-body { padding:26px 28px; }
.ev-news-body.ev-full { grid-column:1 / -1; }
.ev-news-date {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(11,166,201,0.12); color:var(--accent-color);
    padding:4px 14px; border-radius:var(--radius-card); font-size:12px; font-weight:700; margin-bottom:12px;
}
.ev-news-card h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--text-dark); margin:0 0 10px; }
.ev-news-card p { font-size:13.5px; color:var(--text-light); line-height:1.75; margin:0 0 12px; }
.ev-news-author { font-size:12px; color:var(--text-light); font-style:italic; padding-top:12px; border-top:1px solid var(--bg-light); }

.ev-empty {
    text-align:center; padding:70px 20px; background:#fff; border-radius:var(--radius-card);
    box-shadow:0 4px 24px rgba(0,0,0,0.06); grid-column:1 / -1;
}
.ev-empty i { font-size:62px; color:rgba(16,42,86,0.2); margin-bottom:18px; }
.ev-empty h3 { color:var(--text-dark); margin-bottom:8px; }
.ev-empty p { color:var(--text-light); margin:0; }

/* CTA */
.ev-cta {
    padding:var(--section-y-sm) 0;
    background:linear-gradient(130deg,var(--primary-color) 0%,#102A56 55%,#0B1F42 100%);
    position:relative; overflow:hidden; text-align:center;
}
.ev-cta::before {
    content:''; position:absolute; top:-100px; right:-100px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(11,166,201,0.09) 0%,transparent 70%);
    border-radius:50%; pointer-events:none;
}
.ev-cta h2 { font-size:var(--fs-h2); font-weight:700; color:#fff; margin:0 0 10px; position:relative; z-index:1; }
.ev-cta p { color:rgba(255,255,255,0.7); font-size:14.5px; margin:0 0 26px; position:relative; z-index:1; }

@media(max-width:700px){
    .ev-hero h1 { font-size:var(--fs-page-title); }
    .ev-news-card { grid-template-columns:1fr; }
    .ev-news-card img { max-height:200px; }
}
</style>

<!-- ── Hero ── -->
<section class="ev-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="ev-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Events & News</span>
        </div>
        <div class="ev-hero-badge">
            <i class="fas fa-calendar-days" style="font-size:9px;"></i> Campus Life
        </div>
        <h1>Events &amp; <span>News</span></h1>
        <p>Stay updated with the latest happenings at <?php echo htmlspecialchars(getSiteName()); ?>.</p>
    </div>
</section>

<!-- ── Upcoming Events ── -->
<section class="ev-section ev-alt">
    <div class="container">
        <div class="ev-sh">
            <div class="ev-sh-badge"><i class="fas fa-calendar-check" style="font-size:9px;"></i> Mark Your Calendar</div>
            <h2>Upcoming <span>Events</span></h2>
            <p>Exciting events and activities coming up on campus</p>
        </div>
        <div class="ev-grid">
        <?php if ($events): ?>
            <?php foreach ($events as $idx => $event): $delay = ($idx % 3) * 90; ?>
            <div class="ev-card" data-ev-delay="<?php echo $delay; ?>">
                <?php if (!empty($event['image'])): ?>
                    <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" loading="lazy">
                <?php endif; ?>
                <div class="ev-card-body">
                    <div class="ev-date-pill"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    <?php if (!empty($event['location']) || !empty($event['time'])): ?>
                    <div class="ev-meta">
                        <?php if (!empty($event['location'])): ?>
                        <div class="ev-meta-row"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($event['time'])): ?>
                        <div class="ev-meta-row"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($event['time']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="ev-empty">
                <i class="fas fa-calendar-xmark"></i>
                <h3>No Upcoming Events</h3>
                <p>Check back soon for exciting events and activities!</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Latest News ── -->
<section class="ev-section">
    <div class="container">
        <div class="ev-sh">
            <div class="ev-sh-badge"><i class="fas fa-newspaper" style="font-size:9px;"></i> Stay Informed</div>
            <h2>Latest News &amp; <span>Announcements</span></h2>
            <p>Recent updates from <?php echo htmlspecialchars(getSiteName()); ?></p>
        </div>
        <div class="ev-news-list">
        <?php if ($news_items): ?>
            <?php foreach ($news_items as $idx => $item): $delay = min($idx, 4) * 90; ?>
            <div class="ev-news-card" data-ev-delay="<?php echo $delay; ?>">
                <?php if (!empty($item['image'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                    <div class="ev-news-body">
                <?php else: ?>
                    <div class="ev-news-body ev-full">
                <?php endif; ?>
                        <div class="ev-news-date"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($item['created_at'])); ?></div>
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                        <?php if (!empty($item['author'])): ?>
                            <div class="ev-news-author"><i class="fas fa-user-circle"></i> Published by <strong><?php echo htmlspecialchars($item['author']); ?></strong></div>
                        <?php endif; ?>
                    </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="ev-empty">
                <i class="fas fa-newspaper"></i>
                <h3>No News Available</h3>
                <p>Check back soon for the latest updates and announcements from <?php echo htmlspecialchars(getSiteName()); ?>!</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="ev-cta">
    <div class="container">
        <h2>Stay Connected!</h2>
        <p>Follow us on social media to never miss an update</p>
        <div class="social-links" style="justify-content:center; position:relative; z-index:1;">
            <a href="https://www.facebook.com/forteducationsystem/" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="#" target="_blank"><i class="fab fa-youtube"></i></a>
            <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
        </div>
    </div>
</section>

<script>
(function(){
    var els = document.querySelectorAll('.ev-card, .ev-news-card');
    if(!els.length) return;
    var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(!e.isIntersecting) return;
            var d = parseInt(e.target.dataset.evDelay)||0;
            setTimeout(function(){ e.target.classList.add('ev-in'); }, d);
            obs.unobserve(e.target);
        });
    },{threshold:0.08});
    els.forEach(function(el){ obs.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
