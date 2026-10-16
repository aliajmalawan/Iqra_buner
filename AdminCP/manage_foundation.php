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

function saveFoundationSetting($conn, $key, $value) {
    $key = mysqli_real_escape_string($conn, $key);
    $value = mysqli_real_escape_string($conn, $value);
    $exists = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key'");
    if ($exists && mysqli_num_rows($exists) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

/* =========================================================
   PAGE CONTENT
   ========================================================= */
if (isset($_POST['save_foundation_content'])) {
    saveFoundationSetting($conn, 'foundation_name', trim($_POST['foundation_name'] ?? ''));
    saveFoundationSetting($conn, 'foundation_tagline', trim($_POST['foundation_tagline'] ?? ''));
    saveFoundationSetting($conn, 'foundation_established', trim($_POST['foundation_established'] ?? ''));
    saveFoundationSetting($conn, 'foundation_purpose', trim($_POST['foundation_purpose'] ?? ''));
    saveFoundationSetting($conn, 'foundation_objectives_list', trim($_POST['foundation_objectives_list'] ?? ''));
    saveFoundationSetting($conn, 'foundation_relationship', trim($_POST['foundation_relationship'] ?? ''));

    if (isset($_FILES['foundation_logo']) && $_FILES['foundation_logo']['error'] == 0) {
        $upload_dir = '../uploads/foundation/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['foundation_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $filename = 'foundation_logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foundation_logo']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                saveFoundationSetting($conn, 'foundation_logo', 'uploads/foundation/' . $filename);
            }
        }
    }

    header('Location: manage_foundation.php?tab=content&msg=' . urlencode('Foundation page content saved successfully!'));
    exit;
}

/* =========================================================
   ACTIVITIES
   ========================================================= */
if (isset($_POST['save_activity'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $activity_date = trim($_POST['activity_date'] ?? '');
    $activity_date_sql = $activity_date !== '' ? "'" . mysqli_real_escape_string($conn, $activity_date) . "'" : 'NULL';
    $icon = mysqli_real_escape_string($conn, trim($_POST['icon'] ?? '') ?: 'fa-hand-holding-heart');
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($title === '') {
        header('Location: manage_foundation.php?tab=activities&msg=' . urlencode('Activity title is required.') . '&err=1');
        exit;
    }

    if (!empty($_POST['activity_id'])) {
        $id = intval($_POST['activity_id']);
        $q = "UPDATE foundation_activities SET title='$title', description='$description', activity_date=$activity_date_sql, icon='$icon', display_order=$display_order, status='$status' WHERE id=$id";
        $ok = mysqli_query($conn, $q);
    } else {
        $q = "INSERT INTO foundation_activities (title, description, activity_date, icon, display_order, status) VALUES ('$title', '$description', $activity_date_sql, '$icon', $display_order, '$status')";
        $ok = mysqli_query($conn, $q);
    }
    header('Location: manage_foundation.php?tab=activities&msg=' . urlencode($ok ? 'Activity saved successfully!' : 'Error saving activity.') . ($ok ? '' : '&err=1'));
    exit;
}

if (isset($_GET['delete_activity_id'])) {
    $id = intval($_GET['delete_activity_id']);
    mysqli_query($conn, "DELETE FROM foundation_activities WHERE id = $id");
    header('Location: manage_foundation.php?tab=activities&msg=' . urlencode('Activity removed successfully!'));
    exit;
}

$valid_tabs = ['content', 'activities'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'content';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$is_error = isset($_GET['err']) && $_GET['err'] == '1';

$edit_activity = null;
if (isset($_GET['edit_activity'])) {
    $edit_activity = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM foundation_activities WHERE id = " . intval($_GET['edit_activity'])));
}
$activities_result = mysqli_query($conn, "SELECT * FROM foundation_activities ORDER BY display_order ASC, id DESC");
$show_activity_form = $edit_activity || (isset($_GET['tab']) && $_GET['tab'] === 'activities' && isset($_GET['new']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welfare Foundation - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-nav { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 25px; border-bottom: 2px solid var(--border-color); }
        .tab-nav a { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; text-decoration: none; color: var(--text-light); font-weight: 600; font-size: 14px; border-radius: 10px 10px 0 0; border-bottom: 3px solid transparent; transition: all 0.25s ease; }
        .tab-nav a:hover { background: rgba(16,42,86,0.06); color: var(--primary-color); }
        .tab-nav a.active { color: var(--primary-color); border-bottom-color: var(--accent-color); background: rgba(16,42,86,0.08); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; }
        .data-table tr:hover { background: #F4F8FC; }
        .action-btn { padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }
        .status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-hand-holding-heart"></i> Welfare Foundation</h1>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Only enter verified information here — purpose, objectives, activities and the relationship with the school. Leave a field blank if it isn't confirmed yet; the public page shows an honest "not yet published" placeholder instead of guessing.
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo $is_error ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo $is_error ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="tab-nav">
            <a href="?tab=content" class="<?php echo $active_tab === 'content' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Page Content</a>
            <a href="?tab=activities" class="<?php echo $active_tab === 'activities' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Activities</a>
        </div>

        <?php if ($active_tab === 'content'): ?>
        <!-- ============ PAGE CONTENT TAB ============ -->
        <div class="card">
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_foundation_php_1">Foundation Name *</label>
                        <input id="auto_AdminCP_manage_foundation_php_1" type="text" name="foundation_name" value="<?php echo htmlspecialchars(getSetting('foundation_name', 'Welfare Foundation')); ?>" placeholder="e.g. IQRA Welfare Foundation">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_foundation_php_2">Established (year, optional)</label>
                        <input id="auto_AdminCP_manage_foundation_php_2" type="text" name="foundation_established" value="<?php echo htmlspecialchars(getSetting('foundation_established', '')); ?>" placeholder="e.g., 2015">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_3">Tagline (optional)</label>
                    <input id="auto_AdminCP_manage_foundation_php_3" type="text" name="foundation_tagline" value="<?php echo htmlspecialchars(getSetting('foundation_tagline', '')); ?>" placeholder="A short one-line tagline">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_4">Purpose</label>
                    <textarea id="auto_AdminCP_manage_foundation_php_4" name="foundation_purpose" rows="3" placeholder="Verified statement of the Foundation's purpose — leave blank if not confirmed yet"><?php echo htmlspecialchars(getSetting('foundation_purpose', '')); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_5">Objectives (one per line)</label>
                    <textarea id="auto_AdminCP_manage_foundation_php_5" name="foundation_objectives_list" rows="6" placeholder="e.g.&#10;Support underprivileged students with fee assistance&#10;Organize free medical camps"><?php echo htmlspecialchars(getSetting('foundation_objectives_list', '')); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_6">Relationship with the School</label>
                    <textarea id="auto_AdminCP_manage_foundation_php_6" name="foundation_relationship" rows="3" placeholder="Verified description of how the Foundation relates to the school — leave blank if not confirmed yet"><?php echo htmlspecialchars(getSetting('foundation_relationship', '')); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_7">Logo (optional)</label>
                    <input id="auto_AdminCP_manage_foundation_php_7" type="file" name="foundation_logo" accept="image/*">
                    <?php $logo = getSetting('foundation_logo', ''); if ($logo): ?>
                        <div style="margin-top:10px;"><img src="../<?php echo htmlspecialchars($logo); ?>" style="height:70px;border-radius:8px;"></div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="save_foundation_content" class="btn btn-primary"><i class="fas fa-save"></i> Save Content</button>
            </form>
        </div>

        <?php else: ?>
        <!-- ============ ACTIVITIES TAB ============ -->
        <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
            <a href="?tab=activities&new=1" class="btn btn-primary"><i class="fas fa-plus"></i> Add Activity</a>
        </div>

        <?php if ($show_activity_form): ?>
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_activity ? 'Edit Activity' : 'Add Activity'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_activity): ?><input type="hidden" name="activity_id" value="<?php echo $edit_activity['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_8">Activity Title *</label>
                    <input id="auto_AdminCP_manage_foundation_php_8" type="text" name="title" required value="<?php echo $edit_activity ? htmlspecialchars($edit_activity['title']) : ''; ?>" placeholder="e.g., Free Medical Camp — Chowki AJK">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_9">Description</label>
                    <textarea id="auto_AdminCP_manage_foundation_php_9" name="description" rows="3"><?php echo $edit_activity ? htmlspecialchars($edit_activity['description']) : ''; ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_foundation_php_10">Date (optional)</label>
                        <input id="auto_AdminCP_manage_foundation_php_10" type="date" name="activity_date" value="<?php echo $edit_activity && $edit_activity['activity_date'] ? $edit_activity['activity_date'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_foundation_php_11">Icon (Font Awesome class)</label>
                        <input id="auto_AdminCP_manage_foundation_php_11" type="text" name="icon" value="<?php echo $edit_activity ? htmlspecialchars($edit_activity['icon']) : 'fa-hand-holding-heart'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_foundation_php_12">Display Order</label>
                        <input id="auto_AdminCP_manage_foundation_php_12" type="number" name="display_order" value="<?php echo $edit_activity ? $edit_activity['display_order'] : 0; ?>" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_foundation_php_13">Status</label>
                    <select id="auto_AdminCP_manage_foundation_php_13" name="status">
                        <option value="active" <?php echo (!$edit_activity || $edit_activity['status']=='active') ? 'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_activity && $edit_activity['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" name="save_activity" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_activity ? 'Save Changes' : 'Add Activity'; ?></button>
                <a href="?tab=activities" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a>
            </form>
        </div>
        <?php endif; ?>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Title</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($activities_result && mysqli_num_rows($activities_result) > 0): ?>
                    <?php while ($a = mysqli_fetch_assoc($activities_result)): ?>
                        <tr>
                            <td><?php echo $a['display_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($a['title']); ?></strong></td>
                            <td><?php echo $a['activity_date'] ? date('d M Y', strtotime($a['activity_date'])) : '—'; ?></td>
                            <td><span class="status-pill" style="background: <?php echo $a['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($a['status']); ?></span></td>
                            <td>
                                <a href="?tab=activities&edit_activity=<?php echo $a['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=activities&delete_activity_id=<?php echo $a['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove this activity?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-light);">No verified activities added yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
