<?php
require_once 'includes/config.php';
$page_title = 'Courses & Programs';
$page_description = 'Play Group to Intermediate at ' . getSiteName() . ' — Early Years, Primary, Middle, Matric Science and Intermediate (F.Sc Pre-Medical, F.Sc Pre-Engineering, ICS, F.A) at our Boys and Girls campuses.';

// Programs (AdminCP > Academics > Courses). Category = level: school | matric | inter
$levels = [
    'school' => ['label' => 'School',       'sub' => 'Play Group – Class 8',          'icon' => 'fa-school'],
    'matric' => ['label' => 'Matric',       'sub' => 'Class 9 – 10 · Science',        'icon' => 'fa-flask'],
    'inter'  => ['label' => 'Intermediate', 'sub' => 'F.Sc · ICS · F.A',              'icon' => 'fa-graduation-cap'],
];
$by_level = [];
$other = [];
$res = mysqli_query($conn, "SELECT * FROM courses WHERE status='active' ORDER BY display_order ASC, id ASC");
while ($res && ($r = mysqli_fetch_assoc($res))) {
    if (isset($levels[$r['category']])) $by_level[$r['category']][] = $r; else $other[] = $r;
}
if ($other) $levels['other'] = ['label' => 'Other Programs', 'sub' => 'Additional programs', 'icon' => 'fa-book-open'];
if ($other) $by_level['other'] = $other;
$total_programs = array_sum(array_map('count', $by_level));

$courses_hero = getCoursesHero();
$courses_req  = getCoursesRequirements();
$courses_cta_content = getCoursesCta();
$cr_hero_image = getSetting('cr_hero_image', 'images/banners/banner-courtyard.jpg');

// Campuses (editable settings)
$campuses = [
    ['icon' => 'fa-person',       'name' => getSetting('cr_campus1_name', 'Boys Campus'),
     'text' => getSetting('cr_campus1_text', 'A dedicated campus for boys, offering the complete journey from Play Group to Intermediate.')],
    ['icon' => 'fa-person-dress', 'name' => getSetting('cr_campus2_name', 'Girls Campus'),
     'text' => getSetting('cr_campus2_text', 'A dedicated campus for girls, offering the complete journey from Play Group to Intermediate.')],
];
$pathway = [
    ['fa-shapes',            'Early Years',  'Play Group · Nursery · Prep'],
    ['fa-book-open-reader',  'Primary',      'Class 1 – 5'],
    ['fa-school',            'Middle',       'Class 6 – 8'],
    ['fa-flask',             'Matric',       'Class 9 – 10 · Science'],
    ['fa-graduation-cap',    'Intermediate', 'F.Sc · ICS · F.A'],
];
$req_cols = [];
foreach ([1, 2, 3] as $n) {
    $head = trim($courses_req["req{$n}_head"] ?? '');
    $items = array_filter(array_map('trim', explode("\n", str_replace('\n', "\n", $courses_req["req{$n}_items"] ?? ''))));
    if ($head !== '' && $items) $req_cols[] = [$head, $items];
}
function crIcon($icon) {
    $icon = trim($icon ?: 'fa-book');
    return strpos($icon, 'fa-') === 0 ? 'fas ' . $icon : $icon;
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Courses & Programs — uses the shared system in css/style.css
   ================================================================ */

/* ── Hero ── */
.cr-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:380px; display:flex; align-items:center;
    padding:72px 0 64px; background:var(--primary-dark);
}
.cr-hero-img { position:absolute; inset:0; z-index:-2; }
.cr-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.cr-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%);
}
.cr-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.cr-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.cr-breadcrumb a:hover { color:#fff; }
.cr-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.cr-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.cr-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.cr-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.cr-hero h1 span { color:var(--accent-bright); }
.cr-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:620px; }
.cr-hero-facts { display:flex; flex-wrap:wrap; gap:10px; margin-top:24px; }
.cr-hero-facts span {
    display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:999px;
    background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18);
    color:#fff; font-size:var(--fs-caption); font-weight:500;
}
.cr-hero-facts i { color:var(--gold-color); font-size:12px; }

.cr-sh { text-align:center; margin-bottom:44px; }
.cr-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.cr-sh h2 span { color:var(--accent-hover); }
.cr-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

/* ── Campuses ── */
.cr-campuses { padding:var(--section-y) 0; background:#fff; }
.cr-campus-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.cr-campus {
    position:relative; overflow:hidden;
    display:flex; gap:22px; align-items:flex-start;
    padding:30px; border-radius:var(--radius-card);
    background:var(--primary-color); color:#fff;
    border-bottom:4px solid var(--gold-color);
}
.cr-campus .ui-icon { margin:0; width:60px; height:60px; font-size:24px; background:rgba(255,255,255,0.1); color:var(--accent-bright); border-radius:14px; }
.cr-campus h3 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0 0 6px; }
.cr-campus p { color:rgba(255,255,255,0.78); font-size:var(--fs-body-sm); line-height:1.7; margin:0 0 14px; }
.cr-campus-levels { display:flex; flex-wrap:wrap; gap:6px; }
.cr-campus-levels span {
    padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600;
    background:rgba(255,255,255,0.1); color:#fff;
}

/* ── Pathway ── */
.cr-pathway { padding:var(--section-y) 0; background:var(--background-color); }
.cr-steps { display:grid; grid-template-columns:repeat(5,1fr); gap:0; position:relative; list-style:none; padding:0; margin:0; }
.cr-steps::before {
    content:''; position:absolute; top:31px; left:10%; right:10%; height:2px;
    background:linear-gradient(90deg, var(--border-color), var(--accent-color), var(--border-color));
}
.cr-step { position:relative; text-align:center; padding:0 10px; }
.cr-step-dot {
    position:relative; z-index:1;
    width:64px; height:64px; margin:0 auto 16px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:#fff; border:2px solid var(--accent-color); color:var(--accent-color); font-size:22px;
    box-shadow:0 0 0 6px var(--background-color);
}
.cr-step:last-child .cr-step-dot { background:var(--primary-color); border-color:var(--primary-color); color:var(--gold-color); }
.cr-step-num { font-family:var(--font-heading); font-size:11px; font-weight:700; letter-spacing:1.5px; color:var(--accent-text); text-transform:uppercase; }
.cr-step h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); margin:4px 0 4px; }
.cr-step p { font-size:var(--fs-caption); color:var(--text-light); margin:0; }

/* ── Programs ── */
.cr-programs { padding:var(--section-y) 0; background:#fff; }
.cr-tabs { display:flex; flex-wrap:wrap; justify-content:center; gap:8px; margin-bottom:40px; }
.cr-tab {
    display:inline-flex; align-items:center; gap:8px;
    min-height:42px; padding:0 18px; border-radius:999px;
    border:1px solid var(--border-color); background:#fff; color:var(--text-color);
    font-family:var(--font-heading); font-size:14px; font-weight:500; cursor:pointer;
    transition:background-color var(--dur) ease, color var(--dur) ease, border-color var(--dur) ease;
}
.cr-tab i { color:var(--accent-color); font-size:13px; }
.cr-tab .cnt { background:#F1F5F9; color:var(--text-light); border-radius:999px; padding:1px 8px; font-size:12px; font-weight:600; }
.cr-tab:hover { border-color:var(--primary-color); color:var(--primary-color); }
.cr-tab.active { background:var(--primary-color); border-color:var(--primary-color); color:#fff; }
.cr-tab.active i { color:var(--accent-bright); }
.cr-tab.active .cnt { background:rgba(255,255,255,0.18); color:#fff; }

.cr-level + .cr-level { margin-top:52px; }
.cr-level[hidden] { display:none; }
.cr-level-head { display:flex; align-items:center; gap:14px; margin-bottom:22px; padding-bottom:14px; border-bottom:1px solid var(--border-color); }
.cr-level-head .ui-icon { margin:0; width:46px; height:46px; }
.cr-level-head h3 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0; }
.cr-level-head span { font-size:var(--fs-caption); color:var(--text-light); }
.cr-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; }

.cr-card {
    display:flex; flex-direction:column;
    padding:26px; border-radius:var(--radius-card);
    background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card);
    transition:transform var(--dur) var(--ease-out), box-shadow var(--dur) ease, border-color var(--dur) ease;
}
.cr-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-card-hover); border-color:rgba(11,166,201,0.35); }
.cr-card-top { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
.cr-card .ui-icon { margin:0; }
.cr-card:hover .ui-icon { background:var(--accent-color); color:#fff; }
.cr-chip { padding:4px 10px; border-radius:6px; background:var(--light-blue); color:var(--accent-text); font-size:12px; font-weight:600; white-space:nowrap; }
.cr-card h4 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); line-height:1.35; margin:0 0 8px; }
.cr-card p { font-size:var(--fs-body-sm); color:var(--text-light); line-height:1.7; margin:0 0 16px; }
.cr-meta { display:flex; flex-direction:column; gap:8px; margin:0 0 18px; padding:14px 0 0; border-top:1px solid var(--border-color); }
.cr-meta div { display:flex; align-items:flex-start; gap:10px; font-size:var(--fs-caption); color:var(--text-color); }
.cr-meta i { color:var(--accent-color); width:14px; text-align:center; margin-top:3px; }
.cr-meta strong { font-weight:600; color:var(--primary-color); }
.cr-fee { margin:0 0 18px; padding:12px 14px; border-radius:10px; background:var(--background-color); border:1px solid var(--border-color); font-size:var(--fs-caption); }
.cr-fee div { display:flex; justify-content:space-between; gap:12px; padding:3px 0; }
.cr-fee .total { font-weight:700; color:var(--primary-color); border-top:1px solid var(--border-color); margin-top:4px; padding-top:7px; }
.cr-card .btn { margin-top:auto; align-self:flex-start; }
.cr-empty { text-align:center; padding:56px 20px; color:var(--text-light); }
.cr-empty i { font-size:44px; color:var(--border-color); display:block; margin-bottom:14px; }

/* ── Requirements ── */
.cr-req { padding:var(--section-y) 0; background:var(--background-color); }
.cr-req-grid { display:grid; grid-template-columns:repeat(<?php echo max(1, count($req_cols)); ?>,1fr); gap:24px; }
.cr-req-card { border-radius:var(--radius-card); overflow:hidden; background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card); }
.cr-req-card h3 {
    display:flex; align-items:center; gap:10px;
    margin:0; padding:16px 22px; font-size:16px; font-weight:600; color:#fff; background:var(--primary-color);
}
.cr-req-card h3 i { color:var(--gold-color); }
.cr-req-card ul { padding:18px 22px 20px; display:flex; flex-direction:column; gap:10px; }
.cr-req-card li { display:flex; gap:10px; font-size:var(--fs-body-sm); color:var(--text-color); line-height:1.5; }
.cr-req-card li i { color:var(--accent-color); font-size:13px; margin-top:4px; }

/* ── CTA ── */
.cr-cta { padding:var(--section-y-sm) 0; background:#fff; }
.cr-cta-box {
    display:flex; align-items:center; justify-content:space-between; gap:32px; flex-wrap:wrap;
    padding:40px 44px; border-radius:var(--radius-card);
    background:var(--primary-color); border-bottom:4px solid var(--gold-color);
}
.cr-cta-box h2 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0 0 6px; }
.cr-cta-box p { color:rgba(255,255,255,0.75); font-size:var(--fs-body); margin:0; max-width:560px; }
.cr-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }

/* Reveal on scroll */
.cr-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.cr-reveal.is-visible { opacity:1; transform:none; }
.cr-card.cr-reveal.is-visible:hover { transform:translateY(-4px); }

/* ── Responsive ── */
@media (max-width:1024px) { .cr-grid { grid-template-columns:1fr 1fr; } }
@media (max-width:900px) {
    .cr-campus-grid { grid-template-columns:1fr; }
    .cr-req-grid { grid-template-columns:1fr; max-width:640px; margin:0 auto; }
    .cr-steps { grid-template-columns:1fr; gap:14px; max-width:460px; margin:0 auto; }
    .cr-steps::before { top:32px; bottom:32px; left:31px; right:auto; width:2px; height:auto;
        background:linear-gradient(180deg, var(--border-color), var(--accent-color), var(--border-color)); }
    .cr-step { display:flex; align-items:center; gap:18px; text-align:left; padding:0; }
    .cr-step-dot { margin:0; flex-shrink:0; }
}
@media (max-width:600px) {
    .cr-hero { min-height:0; padding:56px 0 48px; }
    .cr-grid { grid-template-columns:1fr; }
    .cr-campus { flex-direction:column; gap:16px; padding:24px; }
    .cr-tabs { justify-content:flex-start; flex-wrap:nowrap; overflow-x:auto; margin:0 -16px 32px; padding:0 16px 4px; scrollbar-width:none; }
    .cr-tabs::-webkit-scrollbar { display:none; }
    .cr-tab { flex-shrink:0; }
    .cr-cta-box { padding:28px 24px; }
    .cr-cta-btns { width:100%; }
    .cr-cta-btns .btn { flex:1 1 100%; }
}
@media (prefers-reduced-motion:reduce) { .cr-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="cr-hero">
    <div class="cr-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($cr_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="cr-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Courses &amp; Programs</span>
        </nav>
        <div class="cr-hero-badge"><?php echo htmlspecialchars($courses_hero['badge']); ?></div>
        <h1>From Play Group to <span>Intermediate</span></h1>
        <p><?php echo htmlspecialchars($courses_hero['subtitle']); ?></p>
        <div class="cr-hero-facts">
            <?php foreach ($campuses as $c): ?><span><i class="fas fa-building-columns" aria-hidden="true"></i><?php echo htmlspecialchars($c['name']); ?></span><?php endforeach; ?>
            <span><i class="fas fa-layer-group" aria-hidden="true"></i><?php echo (int) $total_programs; ?> Programs</span>
        </div>
    </div>
</section>

<!-- ── Campuses ── -->
<section class="cr-campuses">
    <div class="container">
        <div class="cr-sh">
            <div class="section-badge">Our Campuses</div>
            <h2>Two Campuses, <span>One Standard</span></h2>
            <p>Separate campuses for boys and girls — both offering the complete journey from Play Group to Intermediate.</p>
        </div>
        <div class="cr-campus-grid">
            <?php foreach ($campuses as $c): ?>
            <div class="cr-campus cr-reveal">
                <span class="ui-icon"><i class="fas <?php echo $c['icon']; ?>" aria-hidden="true"></i></span>
                <div>
                    <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                    <p><?php echo htmlspecialchars($c['text']); ?></p>
                    <div class="cr-campus-levels">
                        <?php foreach ($pathway as $p): ?><span><?php echo htmlspecialchars($p[1]); ?></span><?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Pathway ── -->
<section class="cr-pathway">
    <div class="container">
        <div class="cr-sh">
            <div class="section-badge">Learning Pathway</div>
            <h2>A Complete <span>Learning Journey</span></h2>
            <p>Students can grow with us from their very first school day until Intermediate.</p>
        </div>
        <ol class="cr-steps">
            <?php foreach ($pathway as $i => $p): ?>
            <li class="cr-step cr-reveal">
                <div class="cr-step-dot"><i class="fas <?php echo $p[0]; ?>" aria-hidden="true"></i></div>
                <div>
                    <div class="cr-step-num">Step <?php echo $i + 1; ?></div>
                    <h3><?php echo htmlspecialchars($p[1]); ?></h3>
                    <p><?php echo htmlspecialchars($p[2]); ?></p>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- ── Programs ── -->
<section class="cr-programs" id="programs">
    <div class="container">
        <div class="cr-sh">
            <div class="section-badge">Programs</div>
            <h2>Our <span>Programs</span></h2>
            <p>Every program is offered at both our Boys and Girls campuses.</p>
        </div>

        <?php if ($total_programs): ?>
        <div class="cr-tabs" role="tablist" aria-label="Filter programs by level">
            <button type="button" class="cr-tab active" data-level="all" role="tab" aria-selected="true">
                <i class="fas fa-border-all" aria-hidden="true"></i> All <span class="cnt"><?php echo (int) $total_programs; ?></span>
            </button>
            <?php foreach ($levels as $key => $lv): if (empty($by_level[$key])) continue; ?>
            <button type="button" class="cr-tab" data-level="<?php echo $key; ?>" role="tab" aria-selected="false">
                <i class="fas <?php echo $lv['icon']; ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($lv['label']); ?> <span class="cnt"><?php echo count($by_level[$key]); ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($levels as $key => $lv): if (empty($by_level[$key])) continue; ?>
        <div class="cr-level" data-level="<?php echo $key; ?>">
            <div class="cr-level-head">
                <span class="ui-icon"><i class="fas <?php echo $lv['icon']; ?>" aria-hidden="true"></i></span>
                <div>
                    <h3><?php echo htmlspecialchars($lv['label']); ?></h3>
                    <span><?php echo htmlspecialchars($lv['sub']); ?></span>
                </div>
            </div>
            <div class="cr-grid">
                <?php foreach ($by_level[$key] as $c):
                    $has_fee = $c['admission_fee'] !== null || $c['initial_fee'] !== null || $c['semester_fee'] !== null || $c['total_fee'] !== null;
                ?>
                <article class="cr-card cr-reveal">
                    <div class="cr-card-top">
                        <span class="ui-icon"><i class="<?php echo htmlspecialchars(crIcon($c['icon'])); ?>" aria-hidden="true"></i></span>
                        <span class="cr-chip"><?php echo htmlspecialchars($lv['label']); ?></span>
                    </div>
                    <h4><?php echo htmlspecialchars($c['name']); ?></h4>
                    <?php if (!empty($c['description'])): ?><p><?php echo htmlspecialchars($c['description']); ?></p><?php endif; ?>
                    <div class="cr-meta">
                        <?php if (!empty($c['duration'])): ?><div><i class="far fa-clock" aria-hidden="true"></i><span><strong>Duration:</strong> <?php echo htmlspecialchars($c['duration']); ?></span></div><?php endif; ?>
                        <?php if (!empty($c['eligibility'])): ?><div><i class="fas fa-clipboard-check" aria-hidden="true"></i><span><strong>Eligibility:</strong> <?php echo htmlspecialchars($c['eligibility']); ?></span></div><?php endif; ?>
                        <?php if (!empty($c['department'])): ?><div><i class="fas fa-building-columns" aria-hidden="true"></i><span><strong>Campus:</strong> <?php echo htmlspecialchars($c['department']); ?></span></div><?php endif; ?>
                        <?php if (!empty($c['affiliation'])): ?><div><i class="fas fa-certificate" aria-hidden="true"></i><span><strong>Board:</strong> <?php echo htmlspecialchars($c['affiliation']); ?></span></div><?php endif; ?>
                    </div>
                    <?php if ($has_fee): ?>
                    <div class="cr-fee">
                        <?php if ($c['admission_fee'] !== null): ?><div><span>Admission Fee</span><span>Rs. <?php echo number_format($c['admission_fee']); ?></span></div><?php endif; ?>
                        <?php if ($c['initial_fee'] !== null): ?><div><span>Initial Fee</span><span>Rs. <?php echo number_format($c['initial_fee']); ?></span></div><?php endif; ?>
                        <?php if ($c['semester_fee'] !== null): ?><div><span><?php echo htmlspecialchars($c['fee_period'] ?: 'Monthly'); ?> Fee</span><span>Rs. <?php echo number_format($c['semester_fee']); ?></span></div><?php endif; ?>
                        <?php if ($c['total_fee'] !== null): ?><div class="total"><span>Total</span><span>Rs. <?php echo number_format($c['total_fee']); ?></span></div><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <a href="admission.php?course=<?php echo (int) $c['id']; ?>" class="btn btn-primary btn-sm">Apply <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="cr-empty"><i class="fas fa-book-open" aria-hidden="true"></i>Programs will be published here soon.</div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Requirements ── -->
<?php if ($req_cols): ?>
<section class="cr-req">
    <div class="container">
        <div class="cr-sh">
            <div class="section-badge"><?php echo htmlspecialchars($courses_req['badge']); ?></div>
            <h2><?php echo renderHighlightedTitle($courses_req['title'], $courses_req['title_highlight'], 'span'); ?></h2>
            <p><?php echo htmlspecialchars($courses_req['subtitle']); ?></p>
        </div>
        <div class="cr-req-grid">
            <?php foreach ($req_cols as $col): ?>
            <div class="cr-req-card cr-reveal">
                <h3><i class="fas fa-folder-open" aria-hidden="true"></i> <?php echo htmlspecialchars($col[0]); ?></h3>
                <ul>
                    <?php foreach ($col[1] as $item): ?><li><i class="fas fa-circle-check" aria-hidden="true"></i><span><?php echo htmlspecialchars($item); ?></span></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── CTA ── -->
<section class="cr-cta">
    <div class="container">
        <div class="cr-cta-box cr-reveal">
            <div>
                <h2><?php echo htmlspecialchars($courses_cta_content['heading_line1'] . ' ' . $courses_cta_content['heading_line2']); ?></h2>
                <p><?php echo htmlspecialchars(applySiteNamePlaceholder($courses_cta_content['subtext'])); ?></p>
            </div>
            <div class="cr-cta-btns">
                <a href="admission.php" class="btn btn-accent"><i class="fas fa-graduation-cap"></i> Apply for Admission</a>
                <a href="contact.php" class="btn btn-outline"><i class="fas fa-circle-question"></i> Ask a Question</a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    /* Level filter */
    var tabs = document.querySelectorAll('.cr-tab'), groups = document.querySelectorAll('.cr-level');
    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            var lv = t.dataset.level;
            tabs.forEach(function (x) { var on = x === t; x.classList.toggle('active', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
            groups.forEach(function (g) {
                g.hidden = !(lv === 'all' || g.dataset.level === lv);
                if (!g.hidden) g.querySelectorAll('.cr-reveal').forEach(function (c) { c.classList.add('is-visible'); });
            });
        });
    });

    /* Reveal on scroll */
    var items = document.querySelectorAll('.cr-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { items.forEach(show); return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .cr-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 3) * 80 + 'ms';
            show(e.target); io.unobserve(e.target);
        });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
