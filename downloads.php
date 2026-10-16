<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';
$page_title = 'Downloads';
$page_description = 'Download important admission forms, prospectuses and academic documents from IQRA School & College Daggar Buner.';

// Fetch active downloads
$downloads_result = mysqli_query($conn, "SELECT * FROM downloads WHERE status = 'active' ORDER BY display_order ASC, date DESC");
$downloads = [];
if ($downloads_result) while ($row = mysqli_fetch_assoc($downloads_result)) $downloads[] = $row;
?>
<?php include 'includes/header.php'; ?>

<style>
/* ── Downloads Page ── */
.dl-hero {
    position:relative; overflow:hidden;
    min-height:260px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.85),rgba(16,42,86,0.9)),
               url('https://images.unsplash.com/photo-1568667256549-094345857637?w=1600') center/cover no-repeat;
    padding:65px 0;
}
.dl-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:18px; }
.dl-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.dl-breadcrumb a:hover { color:var(--accent-color); }
.dl-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
}
.dl-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; margin:0 0 12px; line-height:1.15; text-shadow:0 4px 20px rgba(0,0,0,0.4); }
.dl-hero p { color:rgba(255,255,255,0.75); font-size:var(--fs-lead); margin:0; max-width:520px; line-height:1.7; }

.dl-section { padding:var(--section-y) 0; background:var(--bg-light); }
.dl-card { background:#fff; border:1px solid rgba(16,42,86,0.1); border-radius:var(--radius-card); overflow:hidden; box-shadow:0 4px 18px rgba(16,42,86,0.06); }
.dl-table-wrap { overflow-x:auto; }
.dl-table { width:100%; border-collapse:collapse; min-width:640px; }
.dl-table thead th {
    background:var(--primary-color); color:#fff; padding:16px 18px; text-align:left;
    font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;
}
.dl-table thead th.dl-action-col { text-align:center; }
.dl-table tbody td { padding:16px 18px; border-bottom:1px solid rgba(16,42,86,0.07); color:var(--text-dark); font-size:13.5px; }
.dl-table tbody tr:last-child td { border-bottom:none; }
.dl-table tbody tr { transition:background 0.2s ease; }
.dl-table tbody tr:hover { background:var(--bg-light); }
.dl-table tbody td.dl-date { font-weight:700; white-space:nowrap; }
.dl-filetype { display:inline-flex; align-items:center; gap:8px; font-weight:600; }
.dl-filetype i { color:var(--primary-color); }
.dl-action-col { text-align:center; }
.dl-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:9px 22px; background:var(--accent-color); color:#fff;
    border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;
    box-shadow:0 3px 10px rgba(11,166,201,0.3); transition:color 0.25s ease, background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease, opacity 0.25s ease;
}
.dl-btn:hover { background:#098BA9; transform:translateY(-2px); box-shadow:0 6px 16px rgba(11,166,201,0.4); }

.dl-empty { text-align:center; padding:70px 20px; }
.dl-empty i { font-size:56px; color:rgba(16,42,86,0.2); margin-bottom:16px; display:block; }
.dl-empty p { color:var(--text-light); margin:0; font-size:14px; }

@media(max-width:700px){ .dl-hero h1 { font-size:var(--fs-page-title); } }
</style>

<!-- ── Hero ── -->
<section class="dl-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="dl-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Downloads</span>
        </div>
        <div class="dl-hero-badge">
            <i class="fas fa-download" style="font-size:9px;"></i> Resources
        </div>
        <h1>Downloads</h1>
        <p>Important documents and files for students</p>
    </div>
</section>

<!-- ── Downloads Table ── -->
<section class="dl-section">
    <div class="container">
        <div class="dl-card">
        <?php if ($downloads): ?>
            <div class="dl-table-wrap">
                <table class="dl-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>File Type</th>
                            <th class="dl-action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($downloads as $download):
                        $file_ext = strtolower(pathinfo($download['file_name'], PATHINFO_EXTENSION));
                        $icon = 'fa-file';
                        if ($file_ext == 'pdf') $icon = 'fa-file-pdf';
                        elseif (in_array($file_ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                        elseif (in_array($file_ext, ['xls', 'xlsx'])) $icon = 'fa-file-excel';
                        elseif (in_array($file_ext, ['jpg', 'jpeg', 'png'])) $icon = 'fa-file-image';
                        elseif ($file_ext == 'zip') $icon = 'fa-file-zipper';
                    ?>
                        <tr>
                            <td class="dl-date"><?php echo date('d.m.Y', strtotime($download['date'])); ?></td>
                            <td><?php echo htmlspecialchars($download['description']); ?></td>
                            <td><span class="dl-filetype"><i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars(strtoupper($file_ext)); ?> File</span></td>
                            <td class="dl-action-col">
                                <a href="<?php echo htmlspecialchars($download['file_path']); ?>" download class="dl-btn"><i class="fas fa-download"></i> Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="dl-empty">
                <i class="fas fa-inbox"></i>
                <p>No downloads available at the moment</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
