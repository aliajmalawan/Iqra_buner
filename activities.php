<?php
require_once 'includes/config.php';
$page_title = 'Extra-Curricular Activities';
$page_description = 'Discover extra-curricular activities, sports, seminars and student societies at IQRA School & College Daggar Buner.';

$category_meta = [
    'sports'      => ['label' => 'Sports',      'icon' => 'fa-futbol',            'color' => '#0BA6C9', 'bg' => 'rgba(11,166,201,0.1)'],
    'seminar'     => ['label' => 'Seminars',    'icon' => 'fa-chalkboard-user',   'color' => '#102A56', 'bg' => 'rgba(16,42,86,0.08)'],
    'workshop'    => ['label' => 'Workshops',   'icon' => 'fa-toolbox',           'color' => '#0B1F42', 'bg' => 'rgba(11,31,66,0.1)'],
    'competition' => ['label' => 'Competitions','icon' => 'fa-trophy',            'color' => '#3DBBD8', 'bg' => 'rgba(11,166,201,0.12)'],
    'cultural'    => ['label' => 'Cultural',    'icon' => 'fa-masks-theater',     'color' => '#102A56', 'bg' => 'rgba(16,42,86,0.08)'],
    'community'   => ['label' => 'Community',   'icon' => 'fa-hand-holding-heart','color' => '#0BA6C9', 'bg' => 'rgba(11,166,201,0.1)'],
    'general'     => ['label' => 'General',     'icon' => 'fa-calendar-day',      'color' => '#64748B', 'bg' => 'rgba(100,116,139,0.1)'],
];

$activities = [];
$ar = mysqli_query($conn, "SELECT * FROM events WHERE status='active' ORDER BY event_date DESC, id DESC");
if ($ar) {
    while ($row = mysqli_fetch_assoc($ar)) {
        $activities[] = $row;
    }
}
$has_activities = count($activities) > 0;

$societies = [];
$sr = mysqli_query($conn, "SELECT * FROM student_societies WHERE status='active' ORDER BY display_order ASC, id ASC");
if ($sr) {
    while ($row = mysqli_fetch_assoc($sr)) {
        $societies[] = $row;
    }
}
$has_societies = count($societies) > 0;

// Only show filter chips for categories that actually have at least one real activity
$present_categories = [];
foreach ($activities as $a) {
    $present_categories[$a['category']] = true;
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== EXTRA-CURRICULAR ACTIVITIES PAGE ===== */

/* --- Hero --- */
.eca-hero {
    background: linear-gradient(135deg,#0B1F42 0%,#102A56 55%,#0B1F42 100%);
    padding: 62px 0 50px;
    position: relative; overflow: hidden;
}
.eca-hero::after {
    content:'';
    position:absolute; bottom:-1px; left:0; right:0;
    height:40px;
    background:#F4F8FC;
    clip-path:ellipse(55% 100% at 50% 100%);
}
.eca-breadcrumb {
    display:flex; align-items:center; gap:8px;
    font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:18px;
    position:relative; z-index:1;
}
.eca-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.eca-breadcrumb a:hover { color:var(--accent-color); }
.eca-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.14); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
    position:relative; z-index:1;
}
.eca-hero h1 {
    font-size:var(--fs-page-title); font-weight:700; color:#fff;
    margin:0 0 12px; line-height:1.15;
    position:relative; z-index:1;
}
.eca-hero h1 span { color:var(--accent-color); }
.eca-hero p {
    color:rgba(255,255,255,0.72); font-size:var(--fs-lead); margin:0; max-width:600px; line-height:1.7;
    position:relative; z-index:1;
}

/* --- Filter chips --- */
.eca-section { padding:var(--section-y) 0; background:#F4F8FC; }
.eca-filters { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-bottom:36px; }
.eca-chip {
    display:inline-flex; align-items:center; gap:7px;
    padding:8px 18px; border-radius:50px; border:1.5px solid rgba(16,42,86,0.15);
    background:#fff; color:var(--text-dark);
    font-size:12.5px; font-weight:700; cursor:pointer;
    transition:color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease, opacity 0.2s ease; font-family:var(--font);
}
.eca-chip:hover { border-color:var(--primary-color); }
.eca-chip.active { background:var(--primary-color); border-color:var(--primary-color); color:#fff; }

/* --- Activity cards --- */
.eca-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
@media (max-width:900px) { .eca-grid { grid-template-columns:1fr; } }

.eca-card {
    background:#fff; border-radius:var(--radius-card); border:1px solid rgba(16,42,86,0.1);
    overflow:hidden; display:flex; flex-direction:column;
    transition:transform 0.3s ease, box-shadow 0.3s ease;
}
.eca-card:hover { transform:translateY(-5px); box-shadow:0 16px 40px rgba(16,42,86,0.12); }
.eca-card-img { width:100%; height:160px; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.eca-card-img img { width:100%; height:100%; object-fit:cover; }
.eca-card-img i { font-size:32px; color:#fff; }
.eca-card-body { padding:20px 22px 22px; flex:1; display:flex; flex-direction:column; }
.eca-cat-tag {
    display:inline-flex; align-items:center; gap:6px; align-self:flex-start;
    padding:4px 12px; border-radius:var(--radius-card); font-size:10.5px; font-weight:700;
    text-transform:uppercase; letter-spacing:0.4px; margin-bottom:10px;
}
.eca-card h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--text-dark); margin:0 0 8px; line-height:1.35; }
.eca-meta { font-size:var(--fs-caption-sm); color:var(--text-light); display:flex; flex-wrap:wrap; gap:12px; margin-bottom:10px; }
.eca-meta span { display:flex; align-items:center; gap:5px; }
.eca-desc { color:var(--text-light); font-size:13px; line-height:1.7; margin:0; }

/* --- Societies section --- */
.eca-soc-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; }
@media (max-width:900px) { .eca-soc-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:600px) { .eca-soc-grid { grid-template-columns:1fr; } }
.eca-soc-card {
    background:#fff; border-radius:var(--radius-card); border:1px solid rgba(16,42,86,0.1);
    padding:22px; text-align:center;
    transition:transform 0.3s ease, box-shadow 0.3s ease;
}
.eca-soc-card:hover { transform:translateY(-4px); box-shadow:0 14px 34px rgba(16,42,86,0.1); }
.eca-soc-logo {
    width:58px; height:58px; border-radius:var(--radius-card); margin:0 auto 14px; overflow:hidden;
    background:linear-gradient(135deg,var(--primary-color),#0B1F42);
    display:flex; align-items:center; justify-content:center;
}
.eca-soc-logo img { width:100%; height:100%; object-fit:cover; }
.eca-soc-logo i { color:#fff; font-size:22px; }
.eca-soc-card h4 { font-size:var(--fs-card-title-sm); font-weight:700; color:var(--text-dark); margin:0 0 4px; }
.eca-soc-focus { font-size:var(--fs-caption-sm); color:var(--accent-color); font-weight:700; margin-bottom:8px; }
.eca-soc-card p.eca-soc-desc { color:var(--text-light); font-size:12.5px; line-height:1.6; margin:0 0 8px; }
.eca-soc-meeting { font-size:var(--fs-caption-sm); color:var(--text-dark); display:flex; align-items:center; justify-content:center; gap:5px; }
.eca-soc-meeting i { color:var(--primary-color); }

/* --- Empty states --- */
.eca-empty {
    grid-column:1/-1; text-align:center; padding:60px 30px;
    background:#fff; border-radius:var(--radius-card); border:1px solid rgba(16,42,86,0.1);
}
.eca-empty i { font-size:44px; color:var(--border-color); margin-bottom:16px; display:block; }
.eca-empty h3 { color:var(--text-dark); font-size:18px; margin:0 0 8px; }
.eca-empty p { color:var(--text-light); font-size:13.5px; margin:0 auto; max-width:440px; line-height:1.7; }
</style>

<!-- HERO -->
<section class="eca-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="eca-breadcrumb">
            <a href="index.php"><i class="fas fa-home" style="font-size:11px;"></i> Home</a>
            <span><i class="fas fa-chevron-right" style="font-size:9px;"></i></span>
            <span>Extra-Curricular Activities</span>
        </div>
        <div class="eca-hero-badge">
            <i class="fas fa-star"></i> Beyond the Classroom
        </div>
        <h1>Extra-Curricular <span>Activities</span></h1>
        <p>Real sports, seminars, workshops, competitions, cultural and community activities, plus the student societies running at <?php echo htmlspecialchars(getSiteName()); ?> — shown here only once they're confirmed.</p>
    </div>
</section>

<!-- ACTIVITIES -->
<section class="eca-section">
    <div class="container">

        <?php if ($has_activities): ?>
        <div class="eca-filters" id="ecaFilters">
            <button type="button" class="eca-chip active" data-filter="all">All Activities</button>
            <?php foreach ($category_meta as $key => $meta): if (!isset($present_categories[$key])) continue; ?>
            <button type="button" class="eca-chip" data-filter="<?php echo $key; ?>"><i class="fas <?php echo $meta['icon']; ?>" style="font-size:10.5px;"></i> <?php echo $meta['label']; ?></button>
            <?php endforeach; ?>
        </div>

        <div class="eca-grid" id="ecaGrid">
            <?php foreach ($activities as $a): $meta = $category_meta[$a['category']] ?? $category_meta['general']; ?>
            <div class="eca-card" data-category="<?php echo htmlspecialchars($a['category']); ?>">
                <div class="eca-card-img" style="background:linear-gradient(135deg,<?php echo $meta['color']; ?>,#0B1F42);">
                    <?php if (!empty($a['image'])): ?>
                        <img src="<?php echo htmlspecialchars($a['image']); ?>" alt="<?php echo htmlspecialchars($a['title']); ?>" loading="lazy">
                    <?php else: ?>
                        <i class="fas <?php echo $meta['icon']; ?>"></i>
                    <?php endif; ?>
                </div>
                <div class="eca-card-body">
                    <span class="eca-cat-tag" style="background:<?php echo $meta['bg']; ?>;color:<?php echo $meta['color']; ?>;"><i class="fas <?php echo $meta['icon']; ?>"></i> <?php echo $meta['label']; ?></span>
                    <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                    <div class="eca-meta">
                        <span><i class="fas fa-calendar-days"></i> <?php echo date('d M Y', strtotime($a['event_date'])); ?></span>
                        <?php if (!empty($a['time'])): ?><span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($a['time']); ?></span><?php endif; ?>
                        <?php if (!empty($a['location'])): ?><span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($a['location']); ?></span><?php endif; ?>
                    </div>
                    <?php if (!empty($a['description'])): ?>
                    <p class="eca-desc"><?php echo htmlspecialchars($a['description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="eca-grid">
            <div class="eca-empty">
                <i class="fas fa-star"></i>
                <h3>Activities — Coming Soon</h3>
                <p>We're putting together our extra-curricular activities showcase. Real sports days, seminars, workshops, competitions, cultural and community activities will be listed here as they're confirmed — nothing is invented in the meantime.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- STUDENT SOCIETIES -->
<section class="eca-section" style="background:#fff;">
    <div class="container">
        <div style="text-align:center;margin-bottom:34px;">
            <div class="eca-chip active" style="display:inline-flex;cursor:default;margin-bottom:12px;"><i class="fas fa-people-group" style="font-size:10.5px;"></i> Student Societies</div>
            <h2 style="font-size:var(--fs-h2);font-weight:700;color:var(--text-dark);margin:0 0 8px;">Student <span style="color:var(--primary-color);">Societies &amp; Clubs</span></h2>
            <p style="color:var(--text-light);font-size:14px;margin:0;">Verified student-run societies currently active on campus.</p>
        </div>

        <div class="eca-soc-grid">
            <?php if ($has_societies): ?>
                <?php foreach ($societies as $s): ?>
                <div class="eca-soc-card">
                    <div class="eca-soc-logo">
                        <?php if (!empty($s['image'])): ?>
                            <img src="<?php echo htmlspecialchars($s['image']); ?>" alt="<?php echo htmlspecialchars($s['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas <?php echo htmlspecialchars($s['icon'] ?: 'fa-people-group'); ?>"></i>
                        <?php endif; ?>
                    </div>
                    <h4><?php echo htmlspecialchars($s['name']); ?></h4>
                    <?php if (!empty($s['focus_area'])): ?><div class="eca-soc-focus"><?php echo htmlspecialchars($s['focus_area']); ?></div><?php endif; ?>
                    <?php if (!empty($s['description'])): ?><p class="eca-soc-desc"><?php echo htmlspecialchars($s['description']); ?></p><?php endif; ?>
                    <?php if (!empty($s['meeting_info'])): ?><div class="eca-soc-meeting"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($s['meeting_info']); ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="eca-empty">
                    <i class="fas fa-people-group"></i>
                    <h3>Student Societies — Coming Soon</h3>
                    <p>We're confirming our active student societies and clubs. This section will list them here as they're added — nothing is invented in the meantime.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($has_activities): ?>
<script>
(function() {
    var chips = document.querySelectorAll('#ecaFilters .eca-chip');
    var cards = document.querySelectorAll('#ecaGrid .eca-card');
    chips.forEach(function(chip) {
        chip.addEventListener('click', function() {
            chips.forEach(function(c) { c.classList.remove('active'); });
            chip.classList.add('active');
            var filter = chip.dataset.filter;
            cards.forEach(function(card) {
                card.style.display = (filter === 'all' || card.dataset.category === filter) ? '' : 'none';
            });
        });
    });
})();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
