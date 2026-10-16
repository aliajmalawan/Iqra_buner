<?php
require_once 'includes/config.php';
$page_title = 'Our Campuses';
$page_description = getSiteName() . ' has two campuses in Daggar, District Buner — a Boys Campus and a Girls Campus — both offering Play Group to Intermediate (Matric Science, F.Sc, ICS and F.A).';

// Campuses (AdminCP > Contact > Campuses)
$campuses = getCampuses();
$main_campus = getMainCampus();

// Short description per campus — same editable texts as the Courses page
$camp_texts = [
    getSetting('cr_campus1_text', 'A dedicated campus for boys, offering the complete journey from Play Group to Intermediate.'),
    getSetting('cr_campus2_text', 'A dedicated campus for girls, offering the complete journey from Play Group to Intermediate.'),
];

// What both campuses teach — read from AdminCP > Academics > Courses
$camp_levels = [
    'school' => ['label' => 'School',       'sub' => 'Play Group – Class 8',   'icon' => 'fa-school'],
    'matric' => ['label' => 'Matric',       'sub' => 'Class 9 – 10 · Science', 'icon' => 'fa-flask'],
    'inter'  => ['label' => 'Intermediate', 'sub' => 'F.Sc · ICS · F.A',       'icon' => 'fa-graduation-cap'],
];
$camp_programs = [];
$res = mysqli_query($conn, "SELECT id, name, category FROM courses WHERE status='active' ORDER BY display_order ASC, id ASC");
while ($res && ($r = mysqli_fetch_assoc($res))) {
    if (isset($camp_levels[$r['category']])) $camp_programs[$r['category']][] = $r;
}
$program_count = array_sum(array_map('count', $camp_programs));

// Stats: only real numbers — students / pass rate appear once set in AdminCP > Contact > Campuses
$camp_stats = [['number' => count($campuses), 'label' => count($campuses) === 1 ? 'Campus' : 'Campuses']];
if ($program_count) $camp_stats[] = ['number' => $program_count, 'label' => 'Classes & Programs'];
$camp_stats[] = ['number' => getSetting('camp_stat_years', '30+'), 'label' => 'Years of Education'];
foreach (['camp_stat_students' => 'Students', 'camp_stat_passrate' => 'Pass Rate'] as $k => $lbl) {
    $v = trim(getSetting($k, ''));
    if ($v !== '') $camp_stats[] = ['number' => $v, 'label' => $lbl];
}
if (count($camp_stats) < 4) $camp_stats[] = ['number' => 'PG – Inter', 'label' => 'At Every Campus'];

$camp_hero_image = getSetting('camp_hero_image', 'images/hero/slide_1792151929_4825.jpg');
$camp_tel = function ($p) { return preg_replace('/[^\d+]/', '', (string) $p); };
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Our Campuses — uses the shared system in css/style.css
   ================================================================ */

/* ── Hero ── */
.cp-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:380px; display:flex; align-items:center;
    padding:72px 0 64px; background:var(--primary-dark);
}
.cp-hero-img { position:absolute; inset:0; z-index:-2; }
.cp-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.cp-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%);
}
.cp-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.cp-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.cp-breadcrumb a:hover { color:#fff; }
.cp-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.cp-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.cp-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.cp-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.cp-hero h1 span { color:var(--accent-bright); }
.cp-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:620px; }
.cp-hero-facts { display:flex; flex-wrap:wrap; gap:10px; margin-top:24px; }
.cp-hero-facts span {
    display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:999px;
    background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18);
    color:#fff; font-size:var(--fs-caption); font-weight:500;
}
.cp-hero-facts i { color:var(--gold-color); font-size:12px; }

/* ── Stats ── */
.cp-stats { background:#fff; border-bottom:1px solid var(--border-color); }
.cp-stats-grid { display:grid; grid-template-columns:repeat(<?php echo count($camp_stats); ?>,1fr); }
.cp-stat { padding:30px 16px; text-align:center; }
.cp-stat + .cp-stat { border-left:1px solid var(--border-color); }
.cp-stat strong { display:block; font-family:var(--font-heading); font-size:clamp(1.5rem, 1.2rem + 1.2vw, 2.1rem); font-weight:700; color:var(--primary-color); line-height:1.1; }
.cp-stat span { display:block; margin-top:6px; font-size:var(--fs-caption); font-weight:500; color:var(--text-light); text-transform:uppercase; letter-spacing:1px; }

.cp-sh { text-align:center; margin-bottom:44px; }
.cp-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.cp-sh h2 span { color:var(--accent-hover); }
.cp-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

/* ── Campus cards ── */
.cp-campuses { padding:var(--section-y) 0; background:var(--background-color); }
.cp-grid { display:grid; grid-template-columns:repeat(<?php echo max(1, min(2, count($campuses))); ?>,1fr); gap:28px; }
.cp-card {
    display:flex; flex-direction:column; overflow:hidden;
    border-radius:var(--radius-card); background:#fff;
    border:1px solid var(--border-color); box-shadow:var(--shadow-card);
    transition:transform var(--dur) var(--ease-out), box-shadow var(--dur) ease;
}
.cp-card:hover { box-shadow:var(--shadow-card-hover); }
.cp-card-head {
    position:relative; overflow:hidden;
    display:flex; align-items:center; gap:18px; padding:28px 30px;
    background:var(--cp-grad, var(--primary-color)); color:#fff; border-bottom:4px solid var(--gold-color);
}
.cp-card-head::after {
    content:''; position:absolute; right:-40px; top:-40px; width:180px; height:180px; border-radius:50%;
    background:radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%); pointer-events:none;
}
.cp-card-head .ui-icon { margin:0; width:64px; height:64px; font-size:26px; border-radius:16px; background:rgba(255,255,255,0.12); color:#fff; flex-shrink:0; }
.cp-card-badge { display:inline-block; padding:3px 10px; border-radius:999px; background:rgba(255,255,255,0.14); font-size:11px; font-weight:600; letter-spacing:1px; text-transform:uppercase; margin-bottom:6px; }
.cp-card-head h3 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0 0 4px; }
.cp-card-head .area { display:flex; align-items:flex-start; gap:7px; font-size:var(--fs-body-sm); color:rgba(255,255,255,0.8); }
.cp-card-head .area i { margin-top:4px; font-size:12px; color:var(--gold-color); }

.cp-card-body { padding:26px 30px 8px; flex:1; }
.cp-card-body > p { color:var(--text-light); font-size:var(--fs-body); line-height:1.7; margin:0 0 20px; }
.cp-pills { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 20px; }
.cp-pills span { display:inline-flex; align-items:center; gap:7px; padding:5px 12px; border-radius:999px; background:var(--background-color); border:1px solid var(--border-color); font-size:var(--fs-caption); font-weight:600; color:var(--primary-color); }
.cp-pills i { color:var(--accent-color); font-size:11px; }

.cp-label { font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:1.6px; text-transform:uppercase; color:var(--accent-text); margin:0 0 12px; }
.cp-levels { list-style:none; margin:0 0 22px; padding:0; border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
.cp-levels li { display:flex; gap:14px; padding:14px 16px; }
.cp-levels li + li { border-top:1px solid var(--border-color); }
.cp-levels .ui-icon { margin:0; width:38px; height:38px; font-size:15px; flex-shrink:0; }
.cp-levels h4 { font-size:var(--fs-body-sm); font-weight:700; color:var(--primary-color); margin:0 0 6px; }
.cp-levels h4 small { font-weight:500; color:var(--text-light); margin-left:6px; }
.cp-tags { display:flex; flex-wrap:wrap; gap:6px; }
.cp-tags span { padding:3px 9px; border-radius:6px; background:var(--light-blue); color:var(--accent-text); font-size:12px; font-weight:600; }

.cp-fac { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 22px; }
.cp-fac span { display:inline-flex; align-items:center; gap:6px; font-size:var(--fs-caption); color:var(--text-color); }
.cp-fac i { color:#16A34A; font-size:12px; }

.cp-card-foot { display:flex; flex-wrap:wrap; align-items:center; gap:10px; padding:18px 30px 26px; border-top:1px solid var(--border-color); margin-top:auto; }
.cp-card-foot .cp-phone { display:inline-flex; align-items:center; gap:8px; margin-right:auto; min-height:44px; font-weight:600; color:var(--primary-color); text-decoration:none; }
.cp-card-foot .cp-phone i { color:var(--accent-color); }
.cp-card-foot .cp-phone:hover { color:var(--accent-text); }

/* ── Same standard (comparison) ── */
.cp-compare { padding:var(--section-y) 0; background:#fff; }
.cp-table-wrap { max-width:860px; margin:0 auto; border:1px solid var(--border-color); border-radius:var(--radius-card); overflow:hidden; box-shadow:var(--shadow-card); }
.cp-table-wrap .table-scroll { overflow-x:auto; }
.cp-table { width:100%; border-collapse:collapse; }
.cp-table th, .cp-table td { padding:15px 20px; text-align:left; font-size:var(--fs-body-sm); }
.cp-table thead th { background:var(--primary-color); color:#fff; font-family:var(--font-heading); font-weight:600; }
.cp-table thead th:not(:first-child), .cp-table td:not(:first-child) { text-align:center; width:150px; }
.cp-table tbody tr + tr td { border-top:1px solid var(--border-color); }
.cp-table tbody tr:nth-child(even) td { background:var(--background-color); }
.cp-table td strong { display:block; color:var(--primary-color); font-weight:600; }
.cp-table td small { color:var(--text-light); font-size:var(--fs-caption); }
.cp-yes { display:inline-flex; width:28px; height:28px; border-radius:50%; align-items:center; justify-content:center; background:rgba(22,163,74,0.1); color:#16A34A; font-size:12px; }

/* ── Location ── */
.cp-location { padding:var(--section-y) 0; background:var(--background-color); }
.cp-loc-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.25fr); gap:36px; align-items:stretch; }
.cp-loc-info h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.cp-loc-info h2 span { color:var(--accent-hover); }
.cp-loc-info > p { color:var(--text-light); font-size:var(--fs-body); line-height:1.7; margin:0 0 24px; }
.cp-loc-list { list-style:none; margin:0 0 26px; padding:0; display:flex; flex-direction:column; gap:14px; }
.cp-loc-list li { display:flex; gap:14px; align-items:flex-start; }
.cp-loc-list .ui-icon { margin:0; width:42px; height:42px; font-size:15px; flex-shrink:0; }
.cp-loc-list strong { display:block; font-size:var(--fs-caption); font-weight:600; letter-spacing:1px; text-transform:uppercase; color:var(--text-light); margin-bottom:2px; }
.cp-loc-list span, .cp-loc-list a { font-size:var(--fs-body); font-weight:500; color:var(--text-color); text-decoration:none; word-break:break-word; }
.cp-loc-list a:hover { color:var(--accent-text); }
.cp-map { min-height:380px; border-radius:var(--radius-card); overflow:hidden; border:1px solid var(--border-color); box-shadow:var(--shadow-card); background:#E2E8F0; }
.cp-map iframe { display:block; width:100%; height:100%; min-height:380px; border:0; }

/* ── CTA ── */
.cp-cta { padding:var(--section-y-sm) 0; background:#fff; }
.cp-cta-box {
    display:flex; align-items:center; justify-content:space-between; gap:32px; flex-wrap:wrap;
    padding:40px 44px; border-radius:var(--radius-card);
    background:var(--primary-color); border-bottom:4px solid var(--gold-color);
}
.cp-cta-box h2 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0 0 6px; }
.cp-cta-box p { color:rgba(255,255,255,0.75); font-size:var(--fs-body); margin:0; max-width:560px; }
.cp-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }

.cp-empty { text-align:center; padding:48px 20px; color:var(--text-light); }

/* Reveal on scroll */
.cp-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.cp-reveal.is-visible { opacity:1; transform:none; }

/* ── Responsive ── */
@media (max-width:960px) {
    .cp-grid { grid-template-columns:1fr; max-width:680px; margin:0 auto; }
    .cp-loc-grid { grid-template-columns:1fr; }
    .cp-stats-grid { grid-template-columns:repeat(2,1fr); }
    .cp-stat:nth-child(odd) { border-left:0; }
    .cp-stat:nth-child(n+3) { border-top:1px solid var(--border-color); }
}
@media (max-width:600px) {
    .cp-hero { min-height:0; padding:56px 0 48px; }
    .cp-card-head { padding:22px 20px; gap:14px; }
    .cp-card-head .ui-icon { width:54px; height:54px; font-size:22px; }
    .cp-card-body { padding:22px 20px 6px; }
    .cp-card-foot { padding:16px 20px 22px; }
    .cp-card-foot .btn { flex:1 1 100%; }
    .cp-table th, .cp-table td { padding:12px 12px; }
    .cp-table thead th:not(:first-child), .cp-table td:not(:first-child) { width:auto; }
    .cp-map, .cp-map iframe { min-height:300px; }
    .cp-cta-box { padding:28px 24px; }
    .cp-cta-btns { width:100%; }
    .cp-cta-btns .btn { flex:1 1 100%; }
}
@media (prefers-reduced-motion:reduce) { .cp-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="cp-hero">
    <div class="cp-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($camp_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="cp-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Our Campuses</span>
        </nav>
        <div class="cp-hero-badge">Our Campuses</div>
        <h1>Two Campuses, <span>One Standard</span></h1>
        <p>Separate campuses for boys and girls in Daggar, District Buner — both offering the complete journey from Play Group to Intermediate, with the same curriculum, care and standards.</p>
        <div class="cp-hero-facts">
            <?php foreach ($campuses as $c): ?><span><i class="fas <?php echo htmlspecialchars($c['icon'] ?: 'fa-building-columns'); ?>" aria-hidden="true"></i><?php echo htmlspecialchars($c['name']); ?></span><?php endforeach; ?>
            <span><i class="fas fa-location-dot" aria-hidden="true"></i>Daggar, Buner</span>
        </div>
    </div>
</section>

<!-- ── Stats ── -->
<section class="cp-stats" aria-label="At a glance">
    <div class="container">
        <div class="cp-stats-grid">
            <?php foreach ($camp_stats as $cs): ?>
            <div class="cp-stat">
                <strong><?php echo htmlspecialchars($cs['number']); ?></strong>
                <span><?php echo htmlspecialchars($cs['label']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Campus cards ── -->
<section class="cp-campuses" id="campuses">
    <div class="container">
        <div class="cp-sh">
            <div class="section-badge">Campus Details</div>
            <h2>Choose Your <span>Campus</span></h2>
            <p>Every class from Play Group to Intermediate is offered at both campuses, so brothers and sisters follow the same path.</p>
        </div>

        <?php if ($campuses): ?>
        <div class="cp-grid">
            <?php foreach ($campuses as $i => $c):
                $grad = 'linear-gradient(135deg,' . htmlspecialchars($c['color_from'] ?: '#0B1F42') . ',' . htmlspecialchars($c['color_to'] ?: '#102A56') . ')';
                $facilities = array_filter(array_map('trim', explode(',', $c['facilities'] ?? '')));
                $desc = $camp_texts[$i] ?? '';
                $icon = trim($c['icon'] ?: 'fa-building-columns');
            ?>
            <article class="cp-card cp-reveal" id="campus-<?php echo (int) $c['id']; ?>">
                <div class="cp-card-head" style="--cp-grad:<?php echo $grad; ?>;">
                    <span class="ui-icon"><i class="<?php echo strpos($icon, 'fa-') === 0 ? 'fas ' . htmlspecialchars($icon) : htmlspecialchars($icon); ?>" aria-hidden="true"></i></span>
                    <div>
                        <?php if (!empty($c['badge']) && strcasecmp($c['badge'], $c['name']) !== 0): ?><div class="cp-card-badge"><?php echo htmlspecialchars($c['badge']); ?></div><?php endif; ?>
                        <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                        <div class="area"><i class="fas fa-location-dot" aria-hidden="true"></i><?php echo htmlspecialchars($c['area']); ?></div>
                    </div>
                </div>

                <div class="cp-card-body">
                    <?php if ($desc !== ''): ?><p><?php echo htmlspecialchars($desc); ?></p><?php endif; ?>

                    <div class="cp-pills">
                        <span><i class="fas fa-layer-group" aria-hidden="true"></i>Play Group – Intermediate</span>
                        <?php if (!empty($c['programs'])): ?><span><i class="fas fa-book-open" aria-hidden="true"></i><?php echo htmlspecialchars($c['programs']); ?> Programs</span><?php elseif ($program_count): ?><span><i class="fas fa-book-open" aria-hidden="true"></i><?php echo $program_count; ?> Classes &amp; Programs</span><?php endif; ?>
                        <?php if (!empty($c['students'])): ?><span><i class="fas fa-users" aria-hidden="true"></i><?php echo htmlspecialchars($c['students']); ?> Students</span><?php endif; ?>
                        <?php if (!empty($c['since_year'])): ?><span><i class="fas fa-calendar" aria-hidden="true"></i>Est. <?php echo htmlspecialchars($c['since_year']); ?></span><?php endif; ?>
                    </div>

                    <?php if ($camp_programs): ?>
                    <div class="cp-label">Classes Offered</div>
                    <ul class="cp-levels">
                        <?php foreach ($camp_levels as $key => $lv): if (empty($camp_programs[$key])) continue; ?>
                        <li>
                            <span class="ui-icon"><i class="fas <?php echo $lv['icon']; ?>" aria-hidden="true"></i></span>
                            <div>
                                <h4><?php echo htmlspecialchars($lv['label']); ?><small><?php echo htmlspecialchars($lv['sub']); ?></small></h4>
                                <div class="cp-tags">
                                    <?php foreach ($camp_programs[$key] as $p): ?><span><?php echo htmlspecialchars($p['name']); ?></span><?php endforeach; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($facilities): ?>
                    <div class="cp-label">Facilities</div>
                    <div class="cp-fac">
                        <?php foreach ($facilities as $fac): ?><span><i class="fas fa-circle-check" aria-hidden="true"></i><?php echo htmlspecialchars($fac); ?></span><?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="cp-card-foot">
                    <?php if (!empty($c['phone'])): ?>
                    <a class="cp-phone" href="tel:<?php echo $camp_tel($c['phone']); ?>"><i class="fas fa-phone-alt" aria-hidden="true"></i><?php echo htmlspecialchars($c['phone']); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($c['map_query'])): ?>
                    <a href="https://maps.google.com/maps?q=<?php echo htmlspecialchars($c['map_query']); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><i class="fas fa-diamond-turn-right"></i> Directions</a>
                    <?php endif; ?>
                    <a href="admission.php?campus=<?php echo urlencode($c['name']); ?>#applyForm" class="btn btn-accent btn-sm"><i class="fas fa-pen-to-square"></i> Apply Here</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="cp-empty"><i class="fas fa-building-columns"></i> Campus details will be published soon.</div>
        <?php endif; ?>
    </div>
</section>

<?php if ($camp_programs && count($campuses) > 1): ?>
<!-- ── Same at both campuses ── -->
<section class="cp-compare">
    <div class="container">
        <div class="cp-sh">
            <div class="section-badge">One Standard</div>
            <h2>The Same Classes at <span>Both Campuses</span></h2>
            <p>Boys and girls get the same curriculum and the same opportunities, from their first school day until Intermediate.</p>
        </div>
        <div class="cp-table-wrap cp-reveal">
            <div class="table-scroll">
            <table class="cp-table">
                <thead>
                    <tr>
                        <th scope="col">Class / Program</th>
                        <?php foreach ($campuses as $c): ?><th scope="col"><?php echo htmlspecialchars($c['name']); ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($camp_levels as $key => $lv): if (empty($camp_programs[$key])) continue; foreach ($camp_programs[$key] as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><small><?php echo htmlspecialchars($lv['label']); ?></small></td>
                        <?php foreach ($campuses as $c): ?><td><span class="cp-yes" role="img" aria-label="Offered"><i class="fas fa-check" aria-hidden="true"></i></span></td><?php endforeach; ?>
                    </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($main_campus): ?>
<!-- ── Location ── -->
<section class="cp-location" id="location">
    <div class="container">
        <div class="cp-loc-grid">
            <div class="cp-loc-info cp-reveal">
                <div class="section-badge">Visit Us</div>
                <h2>Find Us in <span>Daggar, Buner</span></h2>
                <p>Parents are welcome to visit either campus, meet the staff and see the classrooms before admission.</p>
                <ul class="cp-loc-list">
                    <li>
                        <span class="ui-icon"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                        <div><strong>Address</strong><span><?php echo htmlspecialchars(SITE_LOCATION); ?></span></div>
                    </li>
                    <li>
                        <span class="ui-icon"><i class="fas fa-phone-alt" aria-hidden="true"></i></span>
                        <div><strong>Phone</strong><a href="tel:<?php echo $camp_tel(SITE_CONTACT); ?>"><?php echo htmlspecialchars(SITE_CONTACT); ?></a></div>
                    </li>
                    <li>
                        <span class="ui-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                        <div><strong>Email</strong><a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo htmlspecialchars(ADMIN_EMAIL); ?></a></div>
                    </li>
                    <li>
                        <span class="ui-icon"><i class="fas fa-clock" aria-hidden="true"></i></span>
                        <div><strong>Office Hours</strong><span><?php echo htmlspecialchars(getOfficeHours()); ?></span></div>
                    </li>
                </ul>
                <a href="https://maps.google.com/maps?q=<?php echo htmlspecialchars($main_campus['map_query']); ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="fas fa-map-location-dot"></i> Open in Google Maps</a>
            </div>
            <div class="cp-map cp-reveal">
                <iframe title="Map — <?php echo htmlspecialchars(getSiteName()); ?>"
                    src="https://maps.google.com/maps?q=<?php echo htmlspecialchars($main_campus['map_query']); ?>&amp;output=embed&amp;z=14"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── CTA ── -->
<section class="cp-cta">
    <div class="container">
        <div class="cp-cta-box cp-reveal">
            <div>
                <h2>Choose Your Campus &amp; Apply</h2>
                <p>Apply online for the Boys or Girls Campus — our admission team will contact you within 2–3 working days.</p>
            </div>
            <div class="cp-cta-btns">
                <a href="admission.php" class="btn btn-accent"><i class="fas fa-paper-plane"></i> Apply Now</a>
                <a href="contact.php" class="btn btn-outline"><i class="fas fa-phone-alt"></i> Contact Us</a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var items = document.querySelectorAll('.cp-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { items.forEach(show); return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .cp-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 2) * 100 + 'ms';
            show(e.target); io.unobserve(e.target);
        });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
