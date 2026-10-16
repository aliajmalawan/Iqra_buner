<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/csrf.php';
csrf_guard();

$message = '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM scholarships WHERE id = $id")) {
        $message = "Scholarship removed successfully!";
    } else {
        $message = "Error removing scholarship.";
    }
}

// Handle merit tier delete
if (isset($_GET['tier_action']) && $_GET['tier_action'] == 'delete' && isset($_GET['tier_id'])) {
    $tid = intval($_GET['tier_id']);
    if (mysqli_query($conn, "DELETE FROM merit_scholarship_tiers WHERE id = $tid")) {
        $message = "Merit tier removed successfully!";
    } else {
        $message = "Error removing merit tier.";
    }
}

// Handle merit tier add/edit
if (isset($_POST['save_tier'])) {
    $min_percentage = intval($_POST['min_percentage'] ?? 0);
    $has_max = trim($_POST['max_percentage'] ?? '') !== '';
    $max_percentage = $has_max ? intval($_POST['max_percentage']) : null;
    $discount_pct = intval($_POST['discount_pct'] ?? 0);
    $tier_display_order = intval($_POST['tier_display_order'] ?? 0);
    $tier_status = ($_POST['tier_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $max_sql = $max_percentage === null ? 'NULL' : $max_percentage;

    if (!empty($_POST['tier_id'])) {
        $tid = intval($_POST['tier_id']);
        $q = "UPDATE merit_scholarship_tiers SET min_percentage=$min_percentage, max_percentage=$max_sql, discount_pct=$discount_pct, display_order=$tier_display_order, status='$tier_status' WHERE id=$tid";
        $message = mysqli_query($conn, $q) ? "Merit tier updated successfully!" : "Error updating merit tier.";
    } else {
        $q = "INSERT INTO merit_scholarship_tiers (min_percentage, max_percentage, discount_pct, display_order, status) VALUES ($min_percentage, $max_sql, $discount_pct, $tier_display_order, '$tier_status')";
        $message = mysqli_query($conn, $q) ? "Merit tier added successfully!" : "Error adding merit tier.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $category = in_array($_POST['category'] ?? '', ['merit', 'need_based', 'other'], true) ? $_POST['category'] : 'merit';
    $percentage = mysqli_real_escape_string($conn, trim($_POST['percentage'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $eligibility = mysqli_real_escape_string($conn, trim($_POST['eligibility'] ?? ''));
    $application_procedure = mysqli_real_escape_string($conn, trim($_POST['application_procedure'] ?? ''));
    $terms_conditions = mysqli_real_escape_string($conn, trim($_POST['terms_conditions'] ?? ''));
    $icon = mysqli_real_escape_string($conn, trim($_POST['icon'] ?? '') ?: 'fa-award');
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($title === '') {
        $message = "Scholarship title is required.";
    } else {
        if (!empty($_POST['scholarship_id'])) {
            $id = intval($_POST['scholarship_id']);
            $q = "UPDATE scholarships SET title='$title', category='$category', percentage='$percentage', description='$description', eligibility='$eligibility', application_procedure='$application_procedure', terms_conditions='$terms_conditions', icon='$icon', display_order=$display_order, status='$status' WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Scholarship updated successfully!" : "Error updating scholarship.";
        } else {
            $q = "INSERT INTO scholarships (title, category, percentage, description, eligibility, application_procedure, terms_conditions, icon, display_order, status) VALUES ('$title', '$category', '$percentage', '$description', '$eligibility', '$application_procedure', '$terms_conditions', '$icon', $display_order, '$status')";
            if (mysqli_query($conn, $q)) {
                $new_id = mysqli_insert_id($conn);
                header('Location: manage_scholarships.php?edit=' . $new_id . '&msg=' . urlencode('Scholarship added successfully!'));
                exit;
            } else {
                $message = "Error adding scholarship.";
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

$edit_scholarship = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_scholarship = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM scholarships WHERE id = $edit_id"));
}

$scholarships_result = mysqli_query($conn, "SELECT * FROM scholarships ORDER BY display_order ASC, id DESC");

$show_form = $edit_scholarship || ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_tier']));

$category_labels = ['merit' => 'Merit', 'need_based' => 'Need-Based', 'other' => 'Other'];

// Merit scholarship tiers (used by fee-calculator.php and scholarships.php's reference table)
$edit_tier = null;
if (isset($_GET['edit_tier'])) {
    $et_id = intval($_GET['edit_tier']);
    $edit_tier = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM merit_scholarship_tiers WHERE id = $et_id"));
}
$tiers_result = mysqli_query($conn, "SELECT * FROM merit_scholarship_tiers ORDER BY min_percentage DESC, display_order ASC");
$show_tier_form = $edit_tier || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tier']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; }
        .data-table tr:hover { background: #F4F8FC; }
        .action-btn { padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }
        .status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; }
        .cat-pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:rgba(16,42,86,0.08); color:var(--primary-color); }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-award"></i> Scholarships</h1>
            <button type="button" onclick="toggleScholarshipForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Scholarship</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Only add scholarships that are actually offered and verified — this list is shown publicly with a direct "Apply" link.
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div id="scholarship-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_scholarship ? 'Edit Scholarship' : 'Add Scholarship'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_scholarship): ?><input type="hidden" name="scholarship_id" value="<?php echo $edit_scholarship['id']; ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_1">Scholarship Title *</label>
                        <input id="auto_AdminCP_manage_scholarships_php_1" type="text" name="title" required value="<?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['title']) : ''; ?>" placeholder="e.g., Merit Scholarship">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_2">Category</label>
                        <select id="auto_AdminCP_manage_scholarships_php_2" name="category">
                            <option value="merit" <?php echo (!$edit_scholarship || $edit_scholarship['category']=='merit') ? 'selected':''; ?>>Merit</option>
                            <option value="need_based" <?php echo ($edit_scholarship && $edit_scholarship['category']=='need_based') ? 'selected':''; ?>>Need-Based</option>
                            <option value="other" <?php echo ($edit_scholarship && $edit_scholarship['category']=='other') ? 'selected':''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_3">Scholarship % / Amount</label>
                        <input id="auto_AdminCP_manage_scholarships_php_3" type="text" name="percentage" value="<?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['percentage']) : ''; ?>" placeholder="e.g., Up to 50%">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_scholarships_php_4">Description</label>
                    <textarea id="auto_AdminCP_manage_scholarships_php_4" name="description" rows="2" placeholder="Brief overview of this scholarship"><?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['description']) : ''; ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_5">Eligibility Criteria (one per line)</label>
                        <textarea id="auto_AdminCP_manage_scholarships_php_5" name="eligibility" rows="4" placeholder="e.g.&#10;Minimum 80% marks in F.Sc&#10;First attempt only"><?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['eligibility']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_6">Application Procedure (one step per line)</label>
                        <textarea id="auto_AdminCP_manage_scholarships_php_6" name="application_procedure" rows="4" placeholder="e.g.&#10;Submit admission form&#10;Attach result card&#10;Apply within 15 days of admission"><?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['application_procedure']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_scholarships_php_7">Terms &amp; Conditions (one per line)</label>
                    <textarea id="auto_AdminCP_manage_scholarships_php_7" name="terms_conditions" rows="3" placeholder="e.g.&#10;Renewable each semester subject to CGPA&#10;Cannot be combined with other discounts"><?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['terms_conditions']) : ''; ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_8">Icon (Font Awesome, without "fa-")</label>
                        <input id="auto_AdminCP_manage_scholarships_php_8" type="text" name="icon" value="<?php echo $edit_scholarship ? htmlspecialchars($edit_scholarship['icon']) : 'fa-award'; ?>" placeholder="e.g., fa-medal">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_9">Display Order</label>
                        <input id="auto_AdminCP_manage_scholarships_php_9" type="number" name="display_order" value="<?php echo $edit_scholarship ? $edit_scholarship['display_order'] : 0; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_scholarships_php_10">Status</label>
                        <select id="auto_AdminCP_manage_scholarships_php_10" name="status">
                            <option value="active" <?php echo (!$edit_scholarship || $edit_scholarship['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_scholarship && $edit_scholarship['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_scholarship ? 'Save Changes' : 'Add Scholarship'; ?></button>
                <?php if ($edit_scholarship): ?>
                    <a href="manage_scholarships.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="toggleScholarshipForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function toggleScholarshipForm() {
                var wrap = document.getElementById('scholarship-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Title</th><th>Category</th><th>%/Amount</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($scholarships_result && mysqli_num_rows($scholarships_result) > 0): ?>
                    <?php while ($s = mysqli_fetch_assoc($scholarships_result)): ?>
                        <tr>
                            <td><?php echo $s['display_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($s['title']); ?></strong></td>
                            <td><span class="cat-pill"><?php echo htmlspecialchars($category_labels[$s['category']] ?? ucfirst($s['category'])); ?></span></td>
                            <td><?php echo htmlspecialchars($s['percentage'] ?: '—'); ?></td>
                            <td><span class="status-pill" style="background: <?php echo $s['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($s['status']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $s['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $s['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove &quot;<?php echo htmlspecialchars(addslashes($s['title'])); ?>&quot;?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-award" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No scholarships added yet. Add your first one above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-page-title" style="margin-top:36px;">
            <h1><i class="fas fa-percent"></i> Merit Scholarship Tiers</h1>
            <button type="button" onclick="toggleTierForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Tier</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> These percentage tiers are the single source of truth used by both the public <strong>Scholarships</strong> reference table and the live <strong>Fee Calculator</strong> — change a discount here and it updates in both places automatically.
        </div>

        <div id="tier-form-wrap" style="<?php echo $show_tier_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_tier ? 'Edit Merit Tier' : 'Add Merit Tier'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_tier): ?><input type="hidden" name="tier_id" value="<?php echo $edit_tier['id']; ?>"><?php endif; ?>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="tier_min">Minimum Marks % *</label>
                        <input id="tier_min" type="number" name="min_percentage" required min="0" max="100" value="<?php echo $edit_tier ? htmlspecialchars($edit_tier['min_percentage']) : ''; ?>" placeholder="e.g., 90">
                    </div>
                    <div class="form-group">
                        <label for="tier_max">Maximum Marks % (leave blank for "and above")</label>
                        <input id="tier_max" type="number" name="max_percentage" min="0" max="100" value="<?php echo ($edit_tier && $edit_tier['max_percentage'] !== null) ? htmlspecialchars($edit_tier['max_percentage']) : ''; ?>" placeholder="e.g., 94">
                    </div>
                    <div class="form-group">
                        <label for="tier_pct">Scholarship Discount % *</label>
                        <input id="tier_pct" type="number" name="discount_pct" required min="0" max="100" value="<?php echo $edit_tier ? htmlspecialchars($edit_tier['discount_pct']) : ''; ?>" placeholder="e.g., 35">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="tier_order">Display Order</label>
                        <input id="tier_order" type="number" name="tier_display_order" min="0" value="<?php echo $edit_tier ? $edit_tier['display_order'] : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label for="tier_status">Status</label>
                        <select id="tier_status" name="tier_status">
                            <option value="active" <?php echo (!$edit_tier || $edit_tier['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_tier && $edit_tier['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="save_tier" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_tier ? 'Save Changes' : 'Add Tier'; ?></button>
                <?php if ($edit_tier): ?>
                    <a href="manage_scholarships.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="toggleTierForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function toggleTierForm() {
                var wrap = document.getElementById('tier-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Marks Range</th><th>Discount</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($tiers_result && mysqli_num_rows($tiers_result) > 0): ?>
                    <?php while ($t = mysqli_fetch_assoc($tiers_result)):
                        $range = $t['max_percentage'] === null ? $t['min_percentage'] . '% and above' : $t['min_percentage'] . '% – ' . $t['max_percentage'] . '%';
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($range); ?></strong></td>
                            <td><span class="cat-pill"><?php echo (int) $t['discount_pct']; ?>%</span></td>
                            <td><?php echo (int) $t['display_order']; ?></td>
                            <td><span class="status-pill" style="background: <?php echo $t['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($t['status']); ?></span></td>
                            <td>
                                <a href="?edit_tier=<?php echo $t['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tier_action=delete&tier_id=<?php echo $t['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove the <?php echo htmlspecialchars(addslashes($range)); ?> tier?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-percent" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No merit tiers configured yet. Add your first one above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
