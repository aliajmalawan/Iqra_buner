<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';
$page_title = 'Examination';
$page_description = 'View exam datesheets and board results for IQRA School & College Daggar Buner students, updated each academic term.';

// Fetch active datesheets
$datesheets_result = mysqli_query($conn, "SELECT * FROM exam_datesheets WHERE status = 'active' ORDER BY exam_year DESC");
$datesheets = [];
if ($datesheets_result) while ($row = mysqli_fetch_assoc($datesheets_result)) $datesheets[] = $row;

// Fetch active board results
$matric_result_q = mysqli_query($conn, "SELECT * FROM board_results WHERE status = 'active' AND board_type = 'Matric' ORDER BY year DESC, display_order ASC");
$matric_results = [];
if ($matric_result_q) while ($row = mysqli_fetch_assoc($matric_result_q)) $matric_results[] = $row;

$inter_result_q = mysqli_query($conn, "SELECT * FROM board_results WHERE status = 'active' AND board_type = 'Intermediate' ORDER BY year DESC, display_order ASC");
$inter_results = [];
if ($inter_result_q) while ($row = mysqli_fetch_assoc($inter_result_q)) $inter_results[] = $row;

$exam_header = getExaminationHeader();
?>
<?php include 'includes/header.php'; ?>

<style>
/* ── Examination Page ── */
.ex-hero {
    position:relative; overflow:hidden;
    min-height:280px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.85),rgba(16,42,86,0.9)),
               url('<?php echo htmlspecialchars($exam_header['image']); ?>') center/cover no-repeat;
    padding:70px 0;
}
.ex-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:18px; }
.ex-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.ex-breadcrumb a:hover { color:var(--accent-color); }
.ex-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
}
.ex-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; margin:0 0 12px; line-height:1.15; text-shadow:0 4px 20px rgba(0,0,0,0.4); }
.ex-hero p { color:rgba(255,255,255,0.75); font-size:var(--fs-lead); margin:0; max-width:560px; line-height:1.7; }

.ex-section { padding:var(--section-y) 0; }
.ex-section.ex-alt { background:var(--bg-light); }
.ex-sh { text-align:center; margin-bottom:40px; }
.ex-sh-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(16,42,86,0.08); border:1px solid rgba(16,42,86,0.22);
    color:var(--primary-color); padding:6px 16px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:12px;
}
.ex-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--text-dark); margin:0 0 8px; }
.ex-sh h2 span { color:var(--primary-color); }
.ex-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0; }

/* Datesheet cards */
.ex-datesheet-card {
    background:#fff; border:1px solid rgba(16,42,86,0.1); border-radius:var(--radius-card);
    padding:30px; margin-bottom:30px; box-shadow:0 4px 18px rgba(16,42,86,0.06);
}
.ex-datesheet-card h3 { text-align:center; color:var(--primary-color); font-size:var(--fs-card-title); font-weight:700; margin:0 0 22px; }
.ex-table-wrap { overflow-x:auto; border-radius:12px; border:1px solid rgba(16,42,86,0.1); }
.ex-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.ex-table thead th {
    background:var(--primary-color); color:#fff; padding:14px; text-align:center;
    font-weight:700; white-space:nowrap;
}
.ex-table thead th:first-child { text-align:left; }
.ex-table thead th small { display:block; font-weight:400; opacity:0.75; font-size:var(--fs-caption-sm); }
.ex-table tbody td { padding:13px 14px; text-align:center; border-bottom:1px solid rgba(16,42,86,0.07); color:var(--text-dark); }
.ex-table tbody td:first-child { text-align:left; font-weight:700; background:var(--bg-light); color:var(--primary-color); }
.ex-table tbody tr:last-child td { border-bottom:none; }

/* Board result cards */
.ex-results-group { margin-bottom:50px; }
.ex-results-group:last-child { margin-bottom:0; }
.ex-results-title {
    display:flex; align-items:center; gap:12px;
    color:var(--primary-color); font-size:19px; font-weight:700; margin:0 0 24px;
}
.ex-results-title::before { content:''; width:5px; height:22px; border-radius:3px; background:linear-gradient(180deg,var(--primary-color),var(--accent-color)); }
.ex-results-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:24px; }
.ex-result-card {
    background:#fff; border:1px solid rgba(16,42,86,0.1); border-radius:var(--radius-card); overflow:hidden;
    box-shadow:0 4px 18px rgba(16,42,86,0.06);
    transition:transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
}
.ex-result-card:hover { transform:translateY(-4px); box-shadow:0 16px 40px rgba(16,42,86,0.12); border-color:rgba(16,42,86,0.2); }
.ex-result-card img { width:100%; height:230px; object-fit:cover; display:block; cursor:pointer; }
.ex-result-body { padding:18px 20px 20px; }
.ex-result-card h4 { color:var(--text-dark); font-size:var(--fs-card-title-sm); font-weight:700; margin:0 0 6px; }
.ex-result-card p { color:var(--text-light); font-size:12.5px; margin:0; }

.ex-empty {
    text-align:center; padding:60px 20px; background:#fff; border-radius:var(--radius-card);
    box-shadow:0 4px 24px rgba(0,0,0,0.06); grid-column:1 / -1;
}
.ex-empty i { font-size:56px; color:rgba(16,42,86,0.2); margin-bottom:16px; display:block; }
.ex-empty p { color:var(--text-light); margin:0; font-size:14px; }

@media(max-width:700px){ .ex-hero h1 { font-size:var(--fs-page-title); } }
</style>

<!-- ── Hero ── -->
<section class="ex-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="ex-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Examination</span>
        </div>
        <div class="ex-hero-badge">
            <i class="fas fa-file-lines" style="font-size:9px;"></i> Academic Records
        </div>
        <h1><?php echo htmlspecialchars($exam_header['title']); ?></h1>
        <p><?php echo htmlspecialchars($exam_header['subtitle']); ?></p>
    </div>
</section>

<!-- ── Datesheets ── -->
<section class="ex-section">
    <div class="container">
        <div class="ex-sh">
            <div class="ex-sh-badge"><i class="fas fa-calendar-alt" style="font-size:9px;"></i> Schedules</div>
            <h2>Exam <span>Datesheets</span></h2>
            <p>Upcoming examination schedules by class</p>
        </div>

        <?php if ($datesheets): ?>
            <?php foreach ($datesheets as $datesheet): ?>
            <div class="ex-datesheet-card">
                <h3><?php echo htmlspecialchars($datesheet['exam_name']); ?></h3>
                <?php
                $details_result = mysqli_query($conn, "SELECT * FROM datesheet_details WHERE datesheet_id = " . intval($datesheet['id']) . " ORDER BY exam_date ASC, class ASC");
                $dates = [];
                if ($details_result) {
                    while ($detail = mysqli_fetch_assoc($details_result)) {
                        $dates[$detail['exam_date']][] = $detail;
                    }
                }
                if ($dates):
                    $date_keys = array_keys($dates);
                    $classes = [];
                    foreach ($dates as $date => $entries) {
                        foreach ($entries as $entry) {
                            $classes[$entry['class']][$date] = $entry['subject'];
                        }
                    }
                ?>
                <div class="ex-table-wrap">
                    <table class="ex-table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <?php foreach ($date_keys as $date): ?>
                                <th><?php echo date('d.m.Y', strtotime($date)); ?><small><?php echo htmlspecialchars($dates[$date][0]['day_name']); ?></small></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class => $subjects_by_date): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($class); ?></td>
                                <?php foreach ($date_keys as $date): ?>
                                <td><?php echo isset($subjects_by_date[$date]) ? htmlspecialchars($subjects_by_date[$date]) : '&mdash;'; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="text-align:center; color:var(--text-light); margin:0;">Schedule not available yet</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="ex-empty">
                <i class="fas fa-calendar-xmark"></i>
                <p>No datesheets available at the moment</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Board Results ── -->
<section class="ex-section ex-alt">
    <div class="container">
        <div class="ex-sh">
            <div class="ex-sh-badge"><i class="fas fa-trophy" style="font-size:9px;"></i> Achievements</div>
            <h2>Board <span>Results</span></h2>
            <p>Gazette results announced by the examination board</p>
        </div>

        <div class="ex-results-group">
            <h3 class="ex-results-title">Intermediate Results</h3>
            <div class="ex-results-grid">
                <?php if ($inter_results): ?>
                    <?php foreach ($inter_results as $result): ?>
                    <div class="ex-result-card">
                        <img src="<?php echo htmlspecialchars($result['image_path']); ?>" alt="<?php echo htmlspecialchars($result['title']); ?>" loading="lazy" onclick="window.open('<?php echo htmlspecialchars($result['image_path']); ?>','_blank')">
                        <div class="ex-result-body">
                            <h4><?php echo htmlspecialchars($result['title']); ?></h4>
                            <p>Year: <?php echo htmlspecialchars($result['year']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ex-empty"><i class="fas fa-trophy"></i><p>No Intermediate results available yet</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="ex-results-group">
            <h3 class="ex-results-title">Matric Results</h3>
            <div class="ex-results-grid">
                <?php if ($matric_results): ?>
                    <?php foreach ($matric_results as $result): ?>
                    <div class="ex-result-card">
                        <img src="<?php echo htmlspecialchars($result['image_path']); ?>" alt="<?php echo htmlspecialchars($result['title']); ?>" loading="lazy" onclick="window.open('<?php echo htmlspecialchars($result['image_path']); ?>','_blank')">
                        <div class="ex-result-body">
                            <h4><?php echo htmlspecialchars($result['title']); ?></h4>
                            <p>Year: <?php echo htmlspecialchars($result['year']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ex-empty"><i class="fas fa-trophy"></i><p>No Matric results available yet</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
