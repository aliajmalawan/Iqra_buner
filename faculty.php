<?php
require_once 'includes/config.php';
$page_title = 'Our Faculty';
$page_description = 'Meet the expert faculty of IQRA School & College Daggar Buner — qualified, experienced educators dedicated to student success.';

$faculty_result = mysqli_query($conn, "SELECT * FROM faculty WHERE status='active' ORDER BY display_order ASC");
$db_faculty = [];
if ($faculty_result && mysqli_num_rows($faculty_result) > 0)
    while ($r = mysqli_fetch_assoc($faculty_result)) $db_faculty[] = $r;

$faculty = $db_faculty;

// Department filter — only built from departments that actually exist on active faculty, with counts
$departments = [];
foreach ($faculty as $f) {
    if (!empty($f['department'])) {
        $departments[$f['department']] = ($departments[$f['department']] ?? 0) + 1;
    }
}
ksort($departments);
$show_department_filter = count($departments) > 1;

$faculty_hero = getFacultyHero();
$faculty_stats = getFacultyStats();
$faculty_grid_header = getFacultyGridHeader();
$faculty_why = getFacultyWhy();
$faculty_cta_content = getFacultyCta();

// Avatar gradient pool — on-brand navy/cyan variations for visual variety
$gradients = [
    'linear-gradient(135deg,#102A56,#0B1F42)',
    'linear-gradient(135deg,#0BA6C9,#102A56)',
    'linear-gradient(135deg,#0B1F42,#0BA6C9)',
    'linear-gradient(135deg,#0BA6C9,#0B1F42)',
    'linear-gradient(135deg,#102A56,#0BA6C9)',
    'linear-gradient(135deg,#0B1F42,#102A56)',
    'linear-gradient(135deg,#0BA6C9,#0B1F42)',
    'linear-gradient(135deg,#0BA6C9,#102A56)',
];

// Card banner themes — pastel navy/cyan tints (brand-consistent) paired with a solid accent
// used for the initials/wave. Mirrors a soft-banner-plus-overlapping-avatar card style.
$card_themes = [
    ['banner' => 'linear-gradient(135deg,#E9F6FA 0%,#D9EEF9 100%)', 'accent' => '#102A56'],
    ['banner' => 'linear-gradient(135deg,#E1F6FC 0%,#CBEEFA 100%)', 'accent' => '#0BA6C9'],
    ['banner' => 'linear-gradient(135deg,#ECEEF9 0%,#DCE2F4 100%)', 'accent' => '#0B1F42'],
    ['banner' => 'linear-gradient(135deg,#DEF6FA 0%,#C7EDF6 100%)', 'accent' => '#3DBBD8'],
];
?>
<?php include 'includes/header.php'; ?>

<style>
/* ── Faculty Page ── */

/* Hero */
.fac-hero {
    position:relative; overflow:hidden;
    min-height:290px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.85),rgba(16,42,86,0.91)),
               url('https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=1600') center/cover no-repeat;
    padding:65px 0;
}
.fac-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:14px;
}
.fac-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; margin:0 0 10px; line-height:1.15; }
.fac-hero h1 span {
    background:linear-gradient(90deg,var(--accent-color),#0BA6C9);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.fac-hero p { color:rgba(255,255,255,0.72); font-size:var(--fs-lead); margin:0; max-width:480px; line-height:1.7; }
.fac-breadcrumb {
    display:flex; align-items:center; gap:8px;
    font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:16px;
}
.fac-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.fac-breadcrumb a:hover { color:var(--accent-color); }

/* ── Stats bar ── */
.fac-stats-bar {
    background:var(--primary-color);
    padding:16px 0;
}
.fac-stats-inner {
    display:flex; justify-content:center; gap:0;
    flex-wrap:wrap;
}
.fac-stat {
    padding:8px 36px; text-align:center;
    border-right:1px solid rgba(255,255,255,0.15);
    color:#fff;
}
.fac-stat:last-child { border-right:none; }
.fac-stat-n { font-size:22px; font-weight:700; color:var(--accent-color); line-height:1; }
.fac-stat-l { font-size:var(--fs-caption-sm); opacity:0.75; margin-top:3px; letter-spacing:0.3px; }

/* ── Faculty Grid ── */
.fac-section { padding:var(--section-y) 0; background:#F4F8FC; }
.fac-sh { text-align:center; margin-bottom:42px; }
.fac-sh-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(16,42,86,0.08); border:1px solid rgba(16,42,86,0.22);
    color:var(--primary-color); padding:6px 16px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:12px;
}
.fac-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--text-dark); margin:0 0 8px; }
.fac-sh h2 span { color:var(--primary-color); }
.fac-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0; }

/* Department filter chips */
.fac-filters { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-bottom:32px; }
.fac-chip {
    display:inline-flex; align-items:center; gap:7px;
    padding:8px 18px; border-radius:50px; border:1.5px solid rgba(16,42,86,0.15);
    background:#fff; color:var(--text-dark);
    font-size:12.5px; font-weight:700; cursor:pointer;
    transition:color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease, opacity 0.2s ease; font-family:var(--font);
}
.fac-chip:hover { border-color:var(--primary-color); }
.fac-chip.active { background:var(--primary-color); border-color:var(--primary-color); color:#fff; }

/* Department badge */
.fac-dept { font-size:10.5px; font-weight:700; color:var(--accent-color); text-transform:uppercase; letter-spacing:0.4px; margin-bottom:6px; }

.fac-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}

/* Profile Card */
.fac-card {
    background:#fff;
    border:1px solid rgba(16,42,86,0.1);
    border-radius:var(--radius-card);
    overflow:hidden;
    transition:transform 0.35s cubic-bezier(0.25,1,0.5,1),
               box-shadow 0.35s ease, border-color 0.3s;
    opacity:0; transform:translateY(38px);
    display:flex; flex-direction:column;
}
.fac-card.fac-in { animation:facIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes facIn {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}
.fac-card:hover {
    transform:translateY(-4px);
    box-shadow:0 20px 48px rgba(16,42,86,0.13);
    border-color:rgba(16,42,86,0.22);
}

/* Card top — full-bleed photo, or a pastel mountain banner with an initials avatar */
.fac-card-top {
    aspect-ratio: 1 / 0.94;
    position:relative;
    overflow:hidden;
    background:#E9F6FA;
}
.fac-photo-banner { width:100%; height:100%; object-fit:cover; display:block; }
.fac-pastel-banner { width:100%; height:100%; position:relative; }
.fac-mtn { position:absolute; bottom:-1px; left:0; width:100%; height:40%; }

/* Avatar circle — only used on the pastel (no-photo) banner, overlapping its bottom edge */
.fac-avatar-circle {
    width:104px; height:104px; border-radius:50%;
    position:absolute; left:50%; bottom:-22px; transform:translateX(-50%);
    background:#fff; border:4px solid #fff;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 8px 22px rgba(16,42,86,0.2);
}
.fac-avatar-initials {
    font-size:30px; font-weight:700; letter-spacing:0.5px;
}

/* Card body — name / designation / department */
.fac-card-body {
    padding:34px 20px 16px;
    text-align:center;
}
.fac-card-body h3 {
    font-size:var(--fs-card-title); font-weight:700; color:var(--text-dark);
    margin:0 0 6px; line-height:1.25;
}
.fac-desig {
    font-size:12px; font-weight:600; color:var(--primary-color);
    margin-bottom:10px; line-height:1.3;
}

/* Card footer */
.fac-card-foot {
    padding:12px 20px 16px;
    border-top:1px solid rgba(16,42,86,0.07);
    margin-top:auto;
    display:flex; flex-direction:column; gap:6px;
}
.fac-info-row {
    display:flex; align-items:center; gap:8px;
    font-size:var(--fs-caption-sm); color:var(--text-light);
}
.fac-info-row i { color:var(--primary-color); font-size:10px; width:13px; }
.fac-info-row strong { color:var(--text-dark); font-weight:600; }

/* ── Why Faculty ── */
.fac-why { padding:var(--section-y) 0; background:#fff; }
.fac-why-grid {
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:18px; margin-top:40px;
}
.fac-why-tile {
    background:#fff; border:1px solid rgba(16,42,86,0.1);
    border-radius:var(--radius-card); padding:24px 20px 20px;
    transition:color 0.35s cubic-bezier(0.25,1,0.5,1), background-color 0.35s cubic-bezier(0.25,1,0.5,1), border-color 0.35s cubic-bezier(0.25,1,0.5,1), box-shadow 0.35s cubic-bezier(0.25,1,0.5,1), transform 0.35s cubic-bezier(0.25,1,0.5,1), opacity 0.35s cubic-bezier(0.25,1,0.5,1);
    position:relative; overflow:hidden;
    opacity:0; transform:translateY(28px);
}
.fac-why-tile.fac-in { animation:facIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.fac-why-tile::before {
    content:''; position:absolute; top:0; left:0; right:0; height:2px;
    transform:scaleX(0); transform-origin:left; transition:transform 0.4s ease;
}
.fac-why-tile:hover {
    transform:translateY(-4px);
    border-color:rgba(16,42,86,0.22);
    box-shadow:0 14px 38px rgba(16,42,86,0.1);
}
.fac-why-tile:hover::before { transform:scaleX(1); }
.fac-why-ico {
    width:46px; height:46px; border-radius:13px;
    display:flex; align-items:center; justify-content:center;
    font-size:19px; margin-bottom:14px;
    transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
}
.fac-why-tile:hover .fac-why-ico { transform:scale(1.05); }
.fac-why-tile h4 { font-size:var(--fs-card-title-sm); font-weight:700; color:var(--text-dark); margin:0 0 7px; }
.fac-why-tile p  { font-size:12px; color:var(--text-light); margin:0; line-height:1.6; }

/* ── CTA ── */
.fac-cta {
    padding:var(--section-y-sm) 0;
    background:linear-gradient(130deg,var(--primary-color) 0%,#102A56 55%,#0B1F42 100%);
    position:relative; overflow:hidden;
}
.fac-cta::before {
    content:''; position:absolute; top:-100px; right:-100px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(11,166,201,0.09) 0%,transparent 70%);
    border-radius:50%; pointer-events:none;
}

/* Responsive */
@media(max-width:1100px) { .fac-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:780px)  {
    .fac-grid     { grid-template-columns:repeat(2,1fr); }
    .fac-why-grid { grid-template-columns:repeat(2,1fr); }
    .fac-hero h1  { font-size:var(--fs-page-title); }
    .fac-stat     { padding:8px 20px; }
}
@media(max-width:480px)  {
    .fac-grid     { grid-template-columns:1fr 1fr; gap:14px; }
    .fac-why-grid { grid-template-columns:1fr; }
}
</style>

<!-- ── Hero ── -->
<section class="fac-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="fac-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Faculty</span>
        </div>
        <div class="fac-hero-badge">
            <i class="fas fa-chalkboard-teacher" style="font-size:9px;"></i>
            <?php echo htmlspecialchars($faculty_hero['badge']); ?>
        </div>
        <h1>Our <span>Expert Faculty</span></h1>
        <p><?php echo htmlspecialchars($faculty_hero['subtitle']); ?></p>
    </div>
</section>

<!-- ── Stats bar ── -->
<div class="fac-stats-bar">
    <div class="container">
        <div class="fac-stats-inner">
            <div class="fac-stat"><div class="fac-stat-n"><?php echo count($faculty); ?>+</div><div class="fac-stat-l">Expert Teachers</div></div>
            <div class="fac-stat"><div class="fac-stat-n"><?php echo htmlspecialchars($faculty_stats['stat2_number']); ?></div><div class="fac-stat-l"><?php echo htmlspecialchars($faculty_stats['stat2_label']); ?></div></div>
            <div class="fac-stat"><div class="fac-stat-n"><?php echo htmlspecialchars($faculty_stats['stat3_number']); ?></div><div class="fac-stat-l"><?php echo htmlspecialchars($faculty_stats['stat3_label']); ?></div></div>
            <div class="fac-stat"><div class="fac-stat-n"><?php echo htmlspecialchars($faculty_stats['stat4_number']); ?></div><div class="fac-stat-l"><?php echo htmlspecialchars($faculty_stats['stat4_label']); ?></div></div>
        </div>
    </div>
</div>

<!-- ── Faculty Grid ── -->
<section class="fac-section">
    <div class="container">
        <div class="fac-sh">
            <div class="fac-sh-badge"><i class="fas fa-users" style="font-size:9px;"></i> <?php echo htmlspecialchars($faculty_grid_header['badge']); ?></div>
            <h2><?php echo renderHighlightedTitle($faculty_grid_header['title'], $faculty_grid_header['title_highlight'], 'span'); ?></h2>
            <p><?php echo htmlspecialchars($faculty_grid_header['subtitle']); ?></p>
        </div>

        <?php if (empty($faculty)): ?>
        <div style="text-align:center; padding:60px 20px; color:var(--text-light);">
            <i class="fas fa-chalkboard-teacher" style="font-size:64px; margin-bottom:20px; opacity:0.3;"></i>
            <h3 style="margin-bottom:10px; color:var(--text-dark);">Faculty Profiles Coming Soon</h3>
            <p>We're putting together our faculty listing — check back shortly.</p>
        </div>
        <?php else: ?>

        <?php if ($show_department_filter): ?>
        <div class="fac-filters" id="facFilters">
            <button type="button" class="fac-chip active" data-filter="all">All (<?php echo count($faculty); ?>)</button>
            <?php foreach ($departments as $dept => $cnt): ?>
            <button type="button" class="fac-chip" data-filter="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?> (<?php echo $cnt; ?>)</button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="fac-grid" id="facGrid">
        <?php foreach($faculty as $idx => $t):
            $theme  = $card_themes[$idx % count($card_themes)];
            $delay  = ($idx % 4) * 85;
            $nameParts = preg_split('/\s+/', trim($t['name']));
            $initials  = mb_strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
        ?>
            <div class="fac-card" data-fac-delay="<?php echo $delay; ?>" data-department="<?php echo htmlspecialchars($t['department'] ?? ''); ?>">

                <div class="fac-card-top">
                    <?php if(!empty($t['photo'])): ?>
                        <img class="fac-photo-banner"
                             src="<?php echo htmlspecialchars($t['photo']); ?>"
                             alt="<?php echo htmlspecialchars($t['name']); ?>"
                             loading="lazy"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                    <?php endif; ?>
                    <div class="fac-pastel-banner" style="background:<?php echo $theme['banner']; ?>;<?php echo !empty($t['photo']) ? 'display:none;' : ''; ?>">
                        <svg class="fac-mtn" viewBox="0 0 300 90" preserveAspectRatio="none">
                            <path d="M0,90 L0,55 L45,15 L85,50 L120,25 L160,55 L205,10 L245,45 L300,20 L300,90 Z" fill="<?php echo $theme['accent']; ?>" opacity="0.16"></path>
                            <path d="M0,90 L0,70 L60,38 L100,62 L145,42 L190,68 L235,35 L300,58 L300,90 Z" fill="<?php echo $theme['accent']; ?>" opacity="0.24"></path>
                        </svg>
                        <div class="fac-avatar-circle">
                            <span class="fac-avatar-initials" style="color:<?php echo $theme['accent']; ?>;"><?php echo htmlspecialchars($initials); ?></span>
                        </div>
                    </div>
                </div>

                <div class="fac-card-body">
                    <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                    <div class="fac-desig"><?php echo htmlspecialchars($t['designation']); ?></div>
                    <?php if(!empty($t['department'])): ?>
                    <div class="fac-dept"><?php echo htmlspecialchars($t['department']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="fac-card-foot">
                    <?php if(!empty($t['qualification'])): ?>
                    <div class="fac-info-row">
                        <i class="fas fa-certificate"></i>
                        <span><?php echo htmlspecialchars($t['qualification']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($t['experience'])): ?>
                    <div class="fac-info-row">
                        <i class="fas fa-clock"></i>
                        <strong><?php echo htmlspecialchars($t['experience']); ?> yrs</strong>
                        <span>experience</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Why Our Faculty Stands Out ── -->
<section class="fac-why">
    <div class="container">
        <div class="fac-sh">
            <div class="fac-sh-badge"><i class="fas fa-star" style="font-size:9px;"></i> <?php echo htmlspecialchars($faculty_why['badge']); ?></div>
            <h2><?php echo renderHighlightedTitle($faculty_why['title'], $faculty_why['title_highlight'], 'span'); ?></h2>
            <p><?php echo htmlspecialchars($faculty_why['subtitle']); ?></p>
        </div>
        <div class="fac-why-grid">
        <?php
        $why = [
            ['ic'=>'fas fa-graduation-cap','clr'=>'#102A56','bg'=>'rgba(16,42,86,0.1)', 'bar'=>'linear-gradient(90deg,var(--primary-color),#0BA6C9)',
             'title'=>$faculty_why['tile1_title'], 'desc'=>$faculty_why['tile1_desc']],
            ['ic'=>'fas fa-medal',         'clr'=>'#0BA6C9','bg'=>'rgba(11,166,201,0.1)',  'bar'=>'linear-gradient(90deg,#0BA6C9,#0BA6C9)',
             'title'=>$faculty_why['tile2_title'], 'desc'=>$faculty_why['tile2_desc']],
            ['ic'=>'fas fa-heart',         'clr'=>'#0B1F42','bg'=>'rgba(11,31,66,0.1)',   'bar'=>'linear-gradient(90deg,#0B1F42,#0BA6C9)',
             'title'=>$faculty_why['tile3_title'], 'desc'=>$faculty_why['tile3_desc']],
            ['ic'=>'fas fa-book-reader',   'clr'=>'#098BA9','bg'=>'rgba(11,166,201,0.12)', 'bar'=>'linear-gradient(90deg,#0BA6C9,#102A56)',
             'title'=>$faculty_why['tile4_title'], 'desc'=>$faculty_why['tile4_desc']],
            ['ic'=>'fas fa-comments',      'clr'=>'#102A56','bg'=>'rgba(16,42,86,0.08)',  'bar'=>'linear-gradient(90deg,#102A56,#0BA6C9)',
             'title'=>$faculty_why['tile5_title'], 'desc'=>$faculty_why['tile5_desc']],
            ['ic'=>'fas fa-hands-helping', 'clr'=>'#0B1F42','bg'=>'rgba(11,31,66,0.08)',  'bar'=>'linear-gradient(90deg,#0B1F42,#0BA6C9)',
             'title'=>$faculty_why['tile6_title'], 'desc'=>$faculty_why['tile6_desc']],
        ];
        foreach($why as $i=>$w):
        ?>
        <div class="fac-why-tile" data-fac-delay="<?php echo $i*80; ?>">
            <style>.fac-why-tile:nth-child(<?php echo $i+1; ?>)::before{background:<?php echo $w['bar']; ?>;}</style>
            <div class="fac-why-ico" style="background:<?php echo $w['bg']; ?>;">
                <i class="<?php echo $w['ic']; ?>" style="color:<?php echo $w['clr']; ?>;"></i>
            </div>
            <h4><?php echo htmlspecialchars($w['title']); ?></h4>
            <p><?php echo htmlspecialchars($w['desc']); ?></p>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="fac-cta">
    <div class="container">
        <div style="display:flex;align-items:center;gap:44px;flex-wrap:wrap;position:relative;z-index:1;">
            <div style="flex:1 1 320px;">
                <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(11,166,201,0.12);border:1px solid rgba(11,166,201,0.3);color:var(--accent-color);padding:6px 16px;border-radius:50px;font-size:10.5px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px;">
                    <i class="fas fa-chalkboard-teacher" style="font-size:9px;"></i> <?php echo htmlspecialchars($faculty_cta_content['badge']); ?>
                </div>
                <h2 style="font-size:var(--fs-h2);font-weight:700;color:#fff;margin:0 0 10px;line-height:1.22;">
                    <?php echo htmlspecialchars(applySiteNamePlaceholder($faculty_cta_content['heading_line1'])); ?><br>
                    <span style="background:linear-gradient(90deg,var(--accent-color),#0BA6C9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"><?php echo htmlspecialchars($faculty_cta_content['heading_line2']); ?></span>
                </h2>
                <p style="color:rgba(255,255,255,0.68);font-size:14px;margin:0;line-height:1.7;">
                    <?php echo htmlspecialchars($faculty_cta_content['subtext']); ?>
                </p>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px;flex:0 1 auto;max-width:100%;min-width:0;">
                <div style="display:flex;gap:12px;flex-wrap:wrap;max-width:100%;">
                    <a href="admission.php" class="btn btn-accent">
                        <i class="fas fa-graduation-cap"></i> Apply for Admission
                    </a>
                    <a href="courses.php" class="btn btn-outline">
                        <i class="fas fa-book-open"></i> View Courses
                    </a>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.72);padding:5px 13px;border-radius:50px;font-size:11.5px;font-weight:600;">
                        <i class="fas fa-check-circle" style="color:var(--accent-color);font-size:10px;"></i> <?php echo htmlspecialchars($faculty_cta_content['pill1']); ?>
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.72);padding:5px 13px;border-radius:50px;font-size:11.5px;font-weight:600;">
                        <i class="fas fa-check-circle" style="color:var(--accent-color);font-size:10px;"></i> <?php echo htmlspecialchars($faculty_cta_content['pill2']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
    var els = document.querySelectorAll('.fac-card, .fac-why-tile');
    if(!els.length) return;
    var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(!e.isIntersecting) return;
            var d = parseInt(e.target.dataset.facDelay)||0;
            setTimeout(function(){ e.target.classList.add('fac-in'); }, d);
            obs.unobserve(e.target);
        });
    },{threshold:0.08});
    els.forEach(function(el){ obs.observe(el); });
})();
</script>

<?php if ($show_department_filter): ?>
<script>
(function() {
    var chips = document.querySelectorAll('#facFilters .fac-chip');
    var cards = document.querySelectorAll('#facGrid .fac-card');
    chips.forEach(function(chip) {
        chip.addEventListener('click', function() {
            chips.forEach(function(c) { c.classList.remove('active'); });
            chip.classList.add('active');
            var filter = chip.dataset.filter;
            cards.forEach(function(card) {
                card.style.display = (filter === 'all' || card.dataset.department === filter) ? '' : 'none';
            });
        });
    });
})();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
