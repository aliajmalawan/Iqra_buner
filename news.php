<?php
require_once 'includes/config.php';
$page_title = 'Latest News';
$page_description = 'Catch up on the latest news and announcements from IQRA School & College Daggar Buner.';

$news_result = mysqli_query($conn, "SELECT * FROM news WHERE status = 'active' ORDER BY created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<style>
.nw-hero {
    position:relative; overflow:hidden;
    min-height:280px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.82),rgba(16,42,86,0.88)),
               url('https://images.unsplash.com/photo-1495020689067-958852a7765e?w=1600') center/cover no-repeat;
    padding:70px 0;
}
.nw-breadcrumb {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    font-size:12px; color:rgba(255,255,255,0.5);
    margin-bottom:18px;
}
.nw-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.nw-breadcrumb a:hover { color:var(--accent-color); }
.nw-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
}
.nw-hero h1 {
    font-size:var(--fs-page-title); font-weight:700; color:#fff;
    margin:0 0 12px; line-height:1.15;
    text-shadow:0 4px 20px rgba(0,0,0,0.4);
}
.nw-hero h1 span {
    background:linear-gradient(90deg,var(--accent-color),#0BA6C9);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text;
}
.nw-hero p { color:rgba(255,255,255,0.75); font-size:var(--fs-lead); margin:0; max-width:560px; line-height:1.7; }

.nw-section { padding:var(--section-y) 0; background:var(--bg-light); }
.nw-list { max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:26px; }
.nw-card {
    background:#fff; border-radius:var(--radius-card); overflow:hidden;
    box-shadow:0 4px 24px rgba(0,0,0,0.06);
    display:grid; grid-template-columns:280px 1fr;
    opacity:0; transform:translateY(24px);
}
.nw-card.nw-in { animation:nwIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes nwIn { from{opacity:0;transform:translateY(16px);} to{opacity:1;transform:translateY(0);} }
.nw-card img { width:100%; height:100%; min-height:200px; object-fit:cover; display:block; }
.nw-card-body { padding:28px 30px; }
.nw-card-body.nw-full { grid-column:1 / -1; }
.nw-date {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(11,166,201,0.12); color:var(--accent-color);
    padding:4px 14px; border-radius:var(--radius-card); font-size:12px; font-weight:700;
    margin-bottom:14px;
}
.nw-card h2 { font-size:19px; font-weight:700; color:var(--text-dark); margin:0 0 12px; }
.nw-card p { font-size:14px; color:var(--text-light); line-height:1.8; margin:0 0 14px; }
.nw-author { font-size:12.5px; color:var(--text-light); font-style:italic; padding-top:14px; border-top:1px solid var(--bg-light); }
.nw-empty {
    text-align:center; padding:80px 20px; background:#fff; border-radius:var(--radius-card);
    box-shadow:0 4px 24px rgba(0,0,0,0.06);
}
.nw-empty i { font-size:70px; color:rgba(16,42,86,0.2); margin-bottom:20px; }
.nw-empty h3 { color:var(--text-dark); margin-bottom:10px; }
.nw-empty p { color:var(--text-light); }

@media(max-width:700px){
    .nw-card { grid-template-columns:1fr; }
    .nw-card img { max-height:220px; }
    .nw-hero h1 { font-size:var(--fs-page-title); }
}
</style>

<!-- Hero -->
<section class="nw-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="nw-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>News</span>
        </div>
        <div class="nw-hero-badge">
            <i class="fas fa-newspaper" style="font-size:9px;"></i> Stay Informed
        </div>
        <h1>Latest <span>News</span></h1>
        <p>Recent updates, announcements, and happenings from <?php echo getSiteName(); ?>.</p>
    </div>
</section>

<!-- News List -->
<section class="nw-section">
    <div class="container">
        <div class="nw-list">
            <?php if ($news_result && mysqli_num_rows($news_result) > 0): ?>
                <?php $i = 0; while ($item = mysqli_fetch_assoc($news_result)): $i++; ?>
                <div class="nw-card" data-nw-delay="<?php echo min($i,5)*80; ?>">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                        <div class="nw-card-body">
                    <?php else: ?>
                        <div class="nw-card-body nw-full">
                    <?php endif; ?>
                            <div class="nw-date"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($item['created_at'])); ?></div>
                            <h2><?php echo htmlspecialchars($item['title']); ?></h2>
                            <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                            <?php if (!empty($item['author'])): ?>
                                <div class="nw-author"><i class="fas fa-user-circle"></i> Published by <strong><?php echo htmlspecialchars($item['author']); ?></strong></div>
                            <?php endif; ?>
                        </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="nw-empty">
                    <i class="fas fa-newspaper"></i>
                    <h3>No News Available</h3>
                    <p>Check back soon for the latest updates from <?php echo getSiteName(); ?>!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function(){
    var els = document.querySelectorAll('.nw-card');
    var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if (!e.isIntersecting) return;
            var d = parseInt(e.target.dataset.nwDelay) || 0;
            setTimeout(function(){ e.target.classList.add('nw-in'); }, d);
            obs.unobserve(e.target);
        });
    },{threshold:0.1});
    els.forEach(function(el){ obs.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
