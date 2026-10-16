<?php
require_once 'includes/config.php';
$page_title = 'Notifications';
$page_description = 'Read the latest official notifications and announcements from IQRA School & College Daggar Buner.';

$notif_result = mysqli_query($conn, "SELECT * FROM notifications WHERE status = 'active' ORDER BY display_order ASC, created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<style>
.ntf-hero {
    position:relative; overflow:hidden;
    min-height:280px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.82),rgba(16,42,86,0.88)),
               url('https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1600') center/cover no-repeat;
    padding:70px 0;
}
.ntf-breadcrumb {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    font-size:12px; color:rgba(255,255,255,0.5);
    margin-bottom:18px;
}
.ntf-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.ntf-breadcrumb a:hover { color:var(--accent-color); }
.ntf-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
}
.ntf-hero h1 {
    font-size:var(--fs-page-title); font-weight:700; color:#fff;
    margin:0 0 12px; line-height:1.15;
    text-shadow:0 4px 20px rgba(0,0,0,0.4);
}
.ntf-hero h1 span {
    background:linear-gradient(90deg,var(--accent-color),#0BA6C9);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    background-clip:text;
}
.ntf-hero p { color:rgba(255,255,255,0.75); font-size:var(--fs-lead); margin:0; max-width:560px; line-height:1.7; }

.ntf-section { padding:var(--section-y) 0; background:#fff; }
.ntf-list { max-width:780px; margin:0 auto; display:flex; flex-direction:column; gap:14px; }
.ntf-item {
    display:flex; align-items:center; gap:16px;
    background:var(--bg-light); border-left:4px solid var(--primary-color);
    border-radius:var(--radius-card); padding:18px 22px;
    opacity:0; transform:translateX(-16px);
}
.ntf-item.ntf-in { animation:ntfIn 0.5s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes ntfIn { from{opacity:0;transform:translateX(-16px);} to{opacity:1;transform:translateX(0);} }
.ntf-item i.ntf-dot { color:var(--accent-color); font-size:9px; flex-shrink:0; }
.ntf-item .ntf-title { flex:1; font-size:14.5px; font-weight:600; color:var(--text-dark); }
.ntf-item a.ntf-title { text-decoration:none; }
.ntf-item a.ntf-title:hover { color:var(--primary-color); }
.ntf-item .ntf-when { font-size:12px; color:var(--text-light); white-space:nowrap; }
.ntf-empty {
    text-align:center; padding:80px 20px; background:var(--bg-light); border-radius:var(--radius-card);
}
.ntf-empty i { font-size:70px; color:rgba(16,42,86,0.2); margin-bottom:20px; }
.ntf-empty h3 { color:var(--text-dark); margin-bottom:10px; }
.ntf-empty p { color:var(--text-light); }

@media(max-width:600px){
    .ntf-hero h1 { font-size:var(--fs-page-title); }
    .ntf-item { flex-wrap:wrap; }
}
</style>

<!-- Hero -->
<section class="ntf-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="ntf-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Notifications</span>
        </div>
        <div class="ntf-hero-badge">
            <i class="fas fa-bullhorn" style="font-size:9px;"></i> Announcements
        </div>
        <h1>All <span>Notifications</span></h1>
        <p>Every announcement and notice from <?php echo getSiteName(); ?> in one place.</p>
    </div>
</section>

<!-- Notifications List -->
<section class="ntf-section">
    <div class="container">
        <div class="ntf-list">
            <?php if ($notif_result && mysqli_num_rows($notif_result) > 0): ?>
                <?php $i = 0; while ($n = mysqli_fetch_assoc($notif_result)): $i++; ?>
                <div class="ntf-item" data-ntf-delay="<?php echo min($i,10)*50; ?>">
                    <i class="fas fa-circle ntf-dot"></i>
                    <?php if (!empty($n['link'])): ?>
                        <a class="ntf-title" href="<?php echo htmlspecialchars($n['link']); ?>"><?php echo htmlspecialchars($n['title']); ?></a>
                    <?php else: ?>
                        <span class="ntf-title"><?php echo htmlspecialchars($n['title']); ?></span>
                    <?php endif; ?>
                    <span class="ntf-when"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="ntf-empty">
                    <i class="fas fa-bullhorn"></i>
                    <h3>No Notifications</h3>
                    <p>There are no active notifications right now. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function(){
    var els = document.querySelectorAll('.ntf-item');
    var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if (!e.isIntersecting) return;
            var d = parseInt(e.target.dataset.ntfDelay) || 0;
            setTimeout(function(){ e.target.classList.add('ntf-in'); }, d);
            obs.unobserve(e.target);
        });
    },{threshold:0.1});
    els.forEach(function(el){ obs.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
