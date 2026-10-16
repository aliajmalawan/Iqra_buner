<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/csrf.php';
csrf_guard();

$valid_tabs = ['about', 'mission_vision', 'core_values', 'leadership', 'explore', 'cta'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'about';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$is_error = isset($_GET['err']) && $_GET['err'] == '1';

function redirectBackAbout($tab, $msg, $err = false) {
    header('Location: manage_about.php?tab=' . urlencode($tab) . '&msg=' . urlencode($msg) . ($err ? '&err=1' : ''));
    exit;
}

function saveSetting($conn, $key, $value) {
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
   ABOUT PAGE (hero + who-we-are + description + stats strip)
   ========================================================= */
if (isset($_POST['save_about'])) {
    saveSetting($conn, 'about_hero_badge', $_POST['about_hero_badge'] ?? '');
    saveSetting($conn, 'about_hero_subtitle', $_POST['about_hero_subtitle'] ?? '');

    if (isset($_FILES['about_hero_image']) && $_FILES['about_hero_image']['error'] == 0) {
        $upload_dir = '../images/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['about_hero_image']['type'], $allowed_types) && $_FILES['about_hero_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['about_hero_image']['name'], PATHINFO_EXTENSION);
            $filename = 'about_hero_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['about_hero_image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                saveSetting($conn, 'about_hero_image', 'images/' . $filename);
            }
        }
    }

    saveSetting($conn, 'about_who_badge', $_POST['about_who_badge'] ?? '');
    saveSetting($conn, 'about_who_title', $_POST['about_who_title'] ?? '');
    saveSetting($conn, 'about_who_title_highlight', $_POST['about_who_title_highlight'] ?? '');
    saveSetting($conn, 'about_description', $_POST['about_description'] ?? '');
    saveSetting($conn, 'about_who_paragraph2', $_POST['about_who_paragraph2'] ?? '');
    saveSetting($conn, 'about_who_hl1', $_POST['about_who_hl1'] ?? '');
    saveSetting($conn, 'about_who_hl2', $_POST['about_who_hl2'] ?? '');
    saveSetting($conn, 'about_who_hl3', $_POST['about_who_hl3'] ?? '');
    saveSetting($conn, 'about_who_hl4', $_POST['about_who_hl4'] ?? '');
    saveSetting($conn, 'about_image_badge_num', $_POST['about_image_badge_num'] ?? '');
    saveSetting($conn, 'about_image_badge_label', $_POST['about_image_badge_label'] ?? '');

    if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0) {
        $upload_dir = '../images/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['about_image']['type'], $allowed_types) && $_FILES['about_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['about_image']['name'], PATHINFO_EXTENSION);
            $filename = 'about_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['about_image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                saveSetting($conn, 'about_image', 'images/' . $filename);
            }
        }
    }

    for ($i = 1; $i <= 4; $i++) {
        saveSetting($conn, "about_stat{$i}_number", $_POST["about_stat{$i}_number"] ?? '');
        saveSetting($conn, "about_stat{$i}_label", $_POST["about_stat{$i}_label"] ?? '');
    }
    redirectBackAbout('about', 'About page content saved successfully!');
}

/* =========================================================
   EXPLORE MORE SECTION
   ========================================================= */
if (isset($_POST['save_explore'])) {
    saveSetting($conn, 'about_explore_badge', $_POST['about_explore_badge'] ?? '');
    saveSetting($conn, 'about_explore_title', $_POST['about_explore_title'] ?? '');
    saveSetting($conn, 'about_explore_title_highlight', $_POST['about_explore_title_highlight'] ?? '');
    saveSetting($conn, 'about_explore_subtitle', $_POST['about_explore_subtitle'] ?? '');
    for ($i = 1; $i <= 3; $i++) {
        saveSetting($conn, "about_explore_card{$i}_title", $_POST["about_explore_card{$i}_title"] ?? '');
        saveSetting($conn, "about_explore_card{$i}_desc", $_POST["about_explore_card{$i}_desc"] ?? '');
    }
    redirectBackAbout('explore', 'Explore section saved successfully!');
}

/* =========================================================
   BOTTOM CTA SECTION
   ========================================================= */
if (isset($_POST['save_about_cta'])) {
    saveSetting($conn, 'about_cta_badge', $_POST['about_cta_badge'] ?? '');
    saveSetting($conn, 'about_cta_heading_line1', $_POST['about_cta_heading_line1'] ?? '');
    saveSetting($conn, 'about_cta_heading_line2', $_POST['about_cta_heading_line2'] ?? '');
    saveSetting($conn, 'about_cta_subtext', $_POST['about_cta_subtext'] ?? '');
    saveSetting($conn, 'about_cta_pill1', $_POST['about_cta_pill1'] ?? '');
    saveSetting($conn, 'about_cta_pill2', $_POST['about_cta_pill2'] ?? '');
    redirectBackAbout('cta', 'CTA section saved successfully!');
}

/* =========================================================
   MISSION & VISION
   ========================================================= */
if (isset($_POST['save_mission_vision'])) {
    saveSetting($conn, 'mission_statement', $_POST['mission_statement'] ?? '');
    saveSetting($conn, 'vision_statement', $_POST['vision_statement'] ?? '');
    redirectBackAbout('mission_vision', 'Mission & Vision saved successfully!');
}

/* =========================================================
   CORE VALUES
   ========================================================= */
if (isset($_POST['save_core_value'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (!empty($_POST['value_id'])) {
        $id = intval($_POST['value_id']);
        $query = "UPDATE core_values SET title='$title', description='$description', icon='$icon', color='$color', display_order=$display_order, status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO core_values (title, description, icon, color, display_order, status) VALUES ('$title', '$description', '$icon', '$color', $display_order, '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBackAbout('core_values', 'Core value saved successfully!');
    }
    redirectBackAbout('core_values', 'Error saving core value.', true);
}

if (isset($_GET['delete_cv_id'])) {
    $id = intval($_GET['delete_cv_id']);
    mysqli_query($conn, "DELETE FROM core_values WHERE id = $id");
    redirectBackAbout('core_values', 'Core value deleted successfully!');
}

/* =========================================================
   DATA FOR ACTIVE TAB
   ========================================================= */
$about = getAboutContent();
$about_stats = getAboutStats();
$about_hero = getAboutHero();
$about_who = getAboutWho();
$about_explore = getAboutExplore();
$about_cta_content = getAboutCta();

$edit_cv = null;
if (isset($_GET['edit_cv'])) {
    $r = mysqli_query($conn, "SELECT * FROM core_values WHERE id = " . intval($_GET['edit_cv']));
    $edit_cv = mysqli_fetch_assoc($r);
}
$core_values_result = mysqli_query($conn, "SELECT * FROM core_values ORDER BY display_order ASC, id ASC");

$leaders_result = mysqli_query($conn, "SELECT * FROM leadership ORDER BY display_order ASC, id ASC");

$tab_labels = [
    'about' => ['icon' => 'fas fa-info-circle', 'label' => 'About Page'],
    'mission_vision' => ['icon' => 'fas fa-compass', 'label' => 'Mission & Vision'],
    'core_values' => ['icon' => 'fas fa-heart', 'label' => 'Core Values'],
    'leadership' => ['icon' => 'fas fa-users', 'label' => 'Leadership'],
    'explore' => ['icon' => 'fas fa-map-signs', 'label' => 'Explore Cards'],
    'cta' => ['icon' => 'fas fa-rocket', 'label' => 'Bottom CTA'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Section - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-light); }
        .tab-nav {
            display: flex; flex-wrap: wrap; gap: 8px;
            margin-bottom: 25px; border-bottom: 2px solid var(--border-color);
        }
        .tab-nav a {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 20px; text-decoration: none;
            color: var(--text-light); font-weight: 600; font-size: 14px;
            border-radius: 10px 10px 0 0; border-bottom: 3px solid transparent;
            transition: all 0.25s ease;
        }
        .tab-nav a:hover { background: rgba(16,42,86,0.06); color: var(--primary-color); }
        .tab-nav a.active { color: var(--primary-color); border-bottom-color: var(--accent-color); background: rgba(16,42,86,0.08); }

        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #fff; }
        .status-active { background: #16A34A; }
        .status-inactive { background: #DC2626; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; }
        .data-table tr:hover { background: #F4F8FC; }
        .avatar-img { width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color); }
        .action-btn { padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }

        .info-box { background: #e7f3ff; border-left: 4px solid var(--primary-color); padding: 18px 20px; border-radius: 8px; }
        .info-box strong { color: var(--primary-color); }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-info-circle"></i> Manage About Section</h1>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo $is_error ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo $is_error ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas <?php echo $is_error ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tab-nav">
            <?php foreach ($tab_labels as $key => $t): ?>
                <a href="?tab=<?php echo $key; ?>" class="<?php echo $active_tab === $key ? 'active' : ''; ?>">
                    <i class="<?php echo $t['icon']; ?>"></i> <?php echo $t['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($active_tab === 'about'): ?>
        <!-- ============ ABOUT PAGE TAB ============ -->
        <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-flag"></i> Hero Banner</h2>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_1">Background Image (leave empty to keep current)</label>
                    <input id="auto_AdminCP_manage_about_php_1" type="file" name="about_hero_image" accept="image/*" onchange="previewImage(this,'about_hero_image_preview')">
                    <img src="<?php echo htmlspecialchars($about_hero['image']); ?>" id="about_hero_image_preview" class="preview-img" style="display:block;max-width:220px;margin-top:10px;border-radius:10px;">
                    <small style="color:var(--text-light);">Recommended size: 1600x600px or larger.</small>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_2">Top Badge Text</label>
                    <input id="auto_AdminCP_manage_about_php_2" type="text" name="about_hero_badge" value="<?php echo htmlspecialchars($about_hero['badge']); ?>">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_3">Subtitle</label>
                    <textarea id="auto_AdminCP_manage_about_php_3" name="about_hero_subtitle" rows="2"><?php echo htmlspecialchars($about_hero['subtitle']); ?></textarea>
                </div>
                <p style="color:var(--text-light);font-size:12.5px;margin:-6px 0 0;">Note: the "About <?php echo htmlspecialchars(SITE_NAME); ?>" title always uses the site name automatically and isn't editable here.</p>
            </div>

            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-university"></i> Who We Are</h2>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_4">Top Badge Text</label>
                    <input id="auto_AdminCP_manage_about_php_4" type="text" name="about_who_badge" value="<?php echo htmlspecialchars($about_who['badge']); ?>">
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_5">Heading</label>
                        <input id="auto_AdminCP_manage_about_php_5" type="text" name="about_who_title" value="<?php echo htmlspecialchars($about_who['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_6">Highlighted Word(s)</label>
                        <input id="auto_AdminCP_manage_about_php_6" type="text" name="about_who_title_highlight" value="<?php echo htmlspecialchars($about_who['title_highlight']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_7">Description (Paragraph 1)</label>
                    <textarea id="auto_AdminCP_manage_about_php_7" name="about_description" rows="4"><?php echo htmlspecialchars($about['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_8">Paragraph 2</label>
                    <textarea id="auto_AdminCP_manage_about_php_8" name="about_who_paragraph2" rows="3"><?php echo htmlspecialchars($about_who['paragraph2']); ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_9">Highlight Pill 1</label><input id="auto_AdminCP_manage_about_php_9" type="text" name="about_who_hl1" value="<?php echo htmlspecialchars($about_who['hl1']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_10">Highlight Pill 2</label><input id="auto_AdminCP_manage_about_php_10" type="text" name="about_who_hl2" value="<?php echo htmlspecialchars($about_who['hl2']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_11">Highlight Pill 3</label><input id="auto_AdminCP_manage_about_php_11" type="text" name="about_who_hl3" value="<?php echo htmlspecialchars($about_who['hl3']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_12">Highlight Pill 4</label><input id="auto_AdminCP_manage_about_php_12" type="text" name="about_who_hl4" value="<?php echo htmlspecialchars($about_who['hl4']); ?>"></div>
                </div>
                <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-image"></i> Campus Photo</h3>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_13">Photo (leave empty to keep current)</label>
                    <input id="auto_AdminCP_manage_about_php_13" type="file" name="about_image" accept="image/*" onchange="previewImage(this,'about_image_preview')">
                    <img src="../<?php echo htmlspecialchars($about_who['image']); ?>" id="about_image_preview" class="preview-img" style="display:block;max-width:220px;margin-top:10px;border-radius:10px;">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_14">Photo Badge Number</label><input id="auto_AdminCP_manage_about_php_14" type="text" name="about_image_badge_num" value="<?php echo htmlspecialchars($about_who['image_badge_num']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_15">Photo Badge Label</label><input id="auto_AdminCP_manage_about_php_15" type="text" name="about_image_badge_label" value="<?php echo htmlspecialchars($about_who['image_badge_label']); ?>"></div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-chart-bar"></i> Stats Strip (4 items)</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:20px;">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div>
                        <div class="form-group">
                            <label for="auto_AdminCP_manage_about_php_16">Stat <?php echo $i; ?> Number</label>
                            <input id="auto_AdminCP_manage_about_php_16" type="text" name="about_stat<?php echo $i; ?>_number" value="<?php echo htmlspecialchars($about_stats[$i-1]['number']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="auto_AdminCP_manage_about_php_17">Stat <?php echo $i; ?> Label</label>
                            <input id="auto_AdminCP_manage_about_php_17" type="text" name="about_stat<?php echo $i; ?>_label" value="<?php echo htmlspecialchars($about_stats[$i-1]['label']); ?>">
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <button type="submit" name="save_about" class="btn btn-primary" style="padding:14px 36px;"><i class="fas fa-save"></i> Save About Page</button>
        </form>

        <script>
            function previewImage(input, previewId){
                var preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display='block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>

        <?php elseif ($active_tab === 'mission_vision'): ?>
        <!-- ============ MISSION & VISION TAB ============ -->
        <form method="POST"><?php echo csrf_field(); ?>
            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-bullseye"></i> Our Mission</h2>
                <div class="form-group">
                    <textarea name="mission_statement" rows="4"><?php echo htmlspecialchars($about['mission']); ?></textarea>
                </div>
            </div>
            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-eye"></i> Our Vision</h2>
                <div class="form-group">
                    <textarea name="vision_statement" rows="4"><?php echo htmlspecialchars($about['vision']); ?></textarea>
                </div>
            </div>
            <button type="submit" name="save_mission_vision" class="btn btn-primary" style="padding:14px 36px;"><i class="fas fa-save"></i> Save Mission & Vision</button>
        </form>

        <?php elseif ($active_tab === 'core_values'): ?>
        <!-- ============ CORE VALUES TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_cv ? 'Edit Core Value' : 'Add New Core Value'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_cv): ?><input type="hidden" name="value_id" value="<?php echo $edit_cv['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_18">Title *</label>
                    <input id="auto_AdminCP_manage_about_php_18" type="text" name="title" required value="<?php echo $edit_cv ? htmlspecialchars($edit_cv['title']) : ''; ?>" placeholder="e.g., Excellence">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_19">Description *</label>
                    <textarea id="auto_AdminCP_manage_about_php_19" name="description" required rows="4" placeholder="Short description of this value..."><?php echo $edit_cv ? htmlspecialchars($edit_cv['description']) : ''; ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_20">Icon (Font Awesome)</label>
                        <input id="auto_AdminCP_manage_about_php_20" type="text" name="icon" value="<?php echo $edit_cv ? htmlspecialchars($edit_cv['icon']) : 'fas fa-star'; ?>" placeholder="e.g., fas fa-star">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_21">Color</label>
                        <input id="auto_AdminCP_manage_about_php_21" type="color" name="color" value="<?php echo $edit_cv ? htmlspecialchars($edit_cv['color']) : '#102A56'; ?>" style="height:42px;">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_22">Display Order</label>
                        <input id="auto_AdminCP_manage_about_php_22" type="number" name="display_order" value="<?php echo $edit_cv ? $edit_cv['display_order'] : 0; ?>" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_23">Status</label>
                    <select id="auto_AdminCP_manage_about_php_23" name="status">
                        <option value="active" <?php echo ($edit_cv && $edit_cv['status']=='active') ? 'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_cv && $edit_cv['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" name="save_core_value" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_cv ? 'Update' : 'Add'; ?> Value</button>
                <?php if ($edit_cv): ?><a href="?tab=core_values" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Icon</th><th>Title</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($core_values_result && mysqli_num_rows($core_values_result) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($core_values_result)): ?>
                        <tr>
                            <td><?php echo $item['display_order']; ?></td>
                            <td><div style="width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:<?php echo htmlspecialchars($item['color']); ?>1a;"><i class="<?php echo htmlspecialchars($item['icon']); ?>" style="color:<?php echo htmlspecialchars($item['color']); ?>;"></i></div></td>
                            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                            <td><?php echo substr(htmlspecialchars($item['description']), 0, 60); ?></td>
                            <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                            <td>
                                <a href="?tab=core_values&edit_cv=<?php echo $item['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=core_values&delete_cv_id=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this core value?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No core values yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($active_tab === 'leadership'): ?>
        <!-- ============ LEADERSHIP TAB (shared with Home Page) ============ -->
        <div class="info-box" style="margin-bottom: 25px;">
            <strong><i class="fas fa-info-circle"></i> Shared Content:</strong>
            Leadership messages appear on both the Home page and the About &gt; Leadership page, so they're managed in one place to avoid duplicate entries.
            <div style="margin-top: 14px;">
                <a href="manage_home.php?tab=leadership" class="btn btn-primary"><i class="fas fa-external-link-alt"></i> Manage Leadership Messages</a>
            </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Current Leadership Entries (read-only preview)</h2>
            <table class="data-table">
                <thead><tr><th>Order</th><th>Photo</th><th>Name</th><th>Designation</th><th>Role Title</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($leaders_result && mysqli_num_rows($leaders_result) > 0): ?>
                    <?php while ($leader = mysqli_fetch_assoc($leaders_result)): ?>
                        <tr>
                            <td><?php echo $leader['display_order']; ?></td>
                            <td>
                                <?php if ($leader['photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($leader['photo']); ?>" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-img" style="background:var(--primary-color);display:flex;align-items:center;justify-content:center;"><i class="fas fa-user" style="color:#fff;"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($leader['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($leader['designation']); ?></td>
                            <td><?php echo htmlspecialchars($leader['role_title']); ?></td>
                            <td><span class="status-badge status-<?php echo $leader['status']; ?>"><?php echo ucfirst($leader['status']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No leadership entries yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($active_tab === 'explore'): ?>
        <!-- ============ EXPLORE MORE SECTION TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-map-signs"></i> Section Header</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_24">Top Badge Text</label><input id="auto_AdminCP_manage_about_php_24" type="text" name="about_explore_badge" value="<?php echo htmlspecialchars($about_explore['badge']); ?>"></div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_25">Heading</label>
                        <input id="auto_AdminCP_manage_about_php_25" type="text" name="about_explore_title" value="<?php echo htmlspecialchars($about_explore['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_26">Highlighted Word(s)</label>
                        <input id="auto_AdminCP_manage_about_php_26" type="text" name="about_explore_title_highlight" value="<?php echo htmlspecialchars($about_explore['title_highlight']); ?>">
                    </div>
                </div>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_27">Subtitle</label><input id="auto_AdminCP_manage_about_php_27" type="text" name="about_explore_subtitle" value="<?php echo htmlspecialchars($about_explore['subtitle']); ?>"></div>

                <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-compass"></i> Card 1 — Mission & Vision</h3>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_28">Title</label><input id="auto_AdminCP_manage_about_php_28" type="text" name="about_explore_card1_title" value="<?php echo htmlspecialchars($about_explore['card1_title']); ?>"></div>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_29">Description</label><textarea id="auto_AdminCP_manage_about_php_29" name="about_explore_card1_desc" rows="2"><?php echo htmlspecialchars($about_explore['card1_desc']); ?></textarea></div>

                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-heart"></i> Card 2 — Core Values</h3>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_30">Title</label><input id="auto_AdminCP_manage_about_php_30" type="text" name="about_explore_card2_title" value="<?php echo htmlspecialchars($about_explore['card2_title']); ?>"></div>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_31">Description</label><textarea id="auto_AdminCP_manage_about_php_31" name="about_explore_card2_desc" rows="2"><?php echo htmlspecialchars($about_explore['card2_desc']); ?></textarea></div>

                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-users"></i> Card 3 — Our Leadership</h3>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_32">Title</label><input id="auto_AdminCP_manage_about_php_32" type="text" name="about_explore_card3_title" value="<?php echo htmlspecialchars($about_explore['card3_title']); ?>"></div>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_33">Description</label><textarea id="auto_AdminCP_manage_about_php_33" name="about_explore_card3_desc" rows="2"><?php echo htmlspecialchars($about_explore['card3_desc']); ?></textarea></div>

                <button type="submit" name="save_explore" class="btn btn-primary" style="padding:14px 36px;"><i class="fas fa-save"></i> Save Explore Section</button>
            </form>
        </div>

        <?php elseif ($active_tab === 'cta'): ?>
        <!-- ============ BOTTOM CTA SECTION TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-rocket"></i> "Join Our Educational Community" Section</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group"><label for="auto_AdminCP_manage_about_php_34">Top Badge Text</label><input id="auto_AdminCP_manage_about_php_34" type="text" name="about_cta_badge" value="<?php echo htmlspecialchars($about_cta_content['badge']); ?>"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_35">Heading Line 1 (plain)</label>
                        <input id="auto_AdminCP_manage_about_php_35" type="text" name="about_cta_heading_line1" value="<?php echo htmlspecialchars($about_cta_content['heading_line1']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_about_php_36">Heading Line 2 (gold gradient)</label>
                        <input id="auto_AdminCP_manage_about_php_36" type="text" name="about_cta_heading_line2" value="<?php echo htmlspecialchars($about_cta_content['heading_line2']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_about_php_37">Subtext</label>
                    <textarea id="auto_AdminCP_manage_about_php_37" name="about_cta_subtext" rows="3"><?php echo htmlspecialchars($about_cta_content['subtext']); ?></textarea>
                    <small style="color:var(--text-light);">Use <code>{site_name}</code> anywhere you want the college name inserted automatically.</small>
                </div>
                <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-check-circle"></i> Trust Pills</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_38">Pill 1</label><input id="auto_AdminCP_manage_about_php_38" type="text" name="about_cta_pill1" value="<?php echo htmlspecialchars($about_cta_content['pill1']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_about_php_39">Pill 2</label><input id="auto_AdminCP_manage_about_php_39" type="text" name="about_cta_pill2" value="<?php echo htmlspecialchars($about_cta_content['pill2']); ?>"></div>
                </div>
                <button type="submit" name="save_about_cta" class="btn btn-primary" style="padding:14px 36px;"><i class="fas fa-save"></i> Save CTA Section</button>
            </form>
        </div>

        <?php endif; ?>
    </div>
    </div>
</body>
</html>
