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

$valid_tabs = ['hero', 'notifications', 'general', 'dms', 'leadership', 'wcu', 'cta'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'hero';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$is_error = isset($_GET['err']) && $_GET['err'] == '1';

function redirectBack($tab, $msg, $err = false) {
    header('Location: manage_home.php?tab=' . urlencode($tab) . '&msg=' . urlencode($msg) . ($err ? '&err=1' : ''));
    exit;
}

function saveSettingsBatch($conn, $pairs) {
    foreach ($pairs as $key => $value) {
        $value_esc = mysqli_real_escape_string($conn, $value);
        $exists = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key'");
        if ($exists && mysqli_num_rows($exists) > 0) {
            mysqli_query($conn, "UPDATE settings SET setting_value = '$value_esc' WHERE setting_key = '$key'");
        } else {
            mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value_esc')");
        }
    }
}

/* =========================================================
   HERO CAROUSEL
   ========================================================= */
if (isset($_POST['add_slide'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
    $text_color = mysqli_real_escape_string($conn, $_POST['text_color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $image_path = '';
    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
        $upload_dir = '../images/hero/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['slide_image']['type'], $allowed_types) && $_FILES['slide_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION);
            $filename = 'slide_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                $image_path = 'images/hero/' . $filename;
            }
        }
    }

    if ($image_path) {
        $query = "INSERT INTO hero_carousel (image_path, title, subtitle, text_color, display_order, status)
                  VALUES ('$image_path', '$title', '$subtitle', '$text_color', $display_order, '$status')";
        if (mysqli_query($conn, $query)) {
            redirectBack('hero', 'Carousel slide added successfully!');
        }
        redirectBack('hero', 'Error adding slide: ' . mysqli_error($conn), true);
    }
    redirectBack('hero', 'Please upload a valid image (max 10MB).', true);
}

if (isset($_POST['edit_slide'])) {
    $id = intval($_POST['slide_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
    $text_color = mysqli_real_escape_string($conn, $_POST['text_color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $current_result = mysqli_query($conn, "SELECT image_path FROM hero_carousel WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $image_path = $current_data['image_path'];

    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
        $upload_dir = '../images/hero/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['slide_image']['type'], $allowed_types) && $_FILES['slide_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION);
            $filename = 'slide_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                if ($image_path && file_exists('../' . $image_path)) unlink('../' . $image_path);
                $image_path = 'images/hero/' . $filename;
            }
        }
    }

    $query = "UPDATE hero_carousel SET image_path='$image_path', title='$title', subtitle='$subtitle',
              text_color='$text_color', display_order=$display_order, status='$status' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        redirectBack('hero', 'Carousel slide updated successfully!');
    }
    redirectBack('hero', 'Error updating slide: ' . mysqli_error($conn), true);
}

if (isset($_GET['delete_slide_id'])) {
    $id = intval($_GET['delete_slide_id']);
    $result = mysqli_query($conn, "SELECT image_path FROM hero_carousel WHERE id = $id");
    $data = mysqli_fetch_assoc($result);
    if (mysqli_query($conn, "DELETE FROM hero_carousel WHERE id = $id")) {
        if ($data['image_path'] && file_exists('../' . $data['image_path'])) unlink('../' . $data['image_path']);
        redirectBack('hero', 'Carousel slide deleted successfully!');
    }
    redirectBack('hero', 'Error deleting slide.', true);
}

if (isset($_GET['toggle_slide_id'])) {
    $id = intval($_GET['toggle_slide_id']);
    $current_result = mysqli_query($conn, "SELECT status FROM hero_carousel WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $new_status = ($current_data['status'] == 'active') ? 'inactive' : 'active';
    mysqli_query($conn, "UPDATE hero_carousel SET status = '$new_status' WHERE id = $id");
    redirectBack('hero', 'Status updated successfully!');
}

/* =========================================================
   NOTIFICATIONS
   ========================================================= */
if (isset($_POST['add_notification'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $link = mysqli_real_escape_string($conn, $_POST['link']);
    $display_order = intval($_POST['display_order']);

    if (mysqli_query($conn, "INSERT INTO notifications (title, link, display_order) VALUES ('$title', '$link', $display_order)")) {
        redirectBack('notifications', 'Notification added successfully!');
    }
    redirectBack('notifications', 'Error adding notification.', true);
}

if (isset($_POST['edit_notification'])) {
    $id = intval($_POST['notif_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $link = mysqli_real_escape_string($conn, $_POST['link']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (mysqli_query($conn, "UPDATE notifications SET title='$title', link='$link', display_order=$display_order, status='$status' WHERE id=$id")) {
        redirectBack('notifications', 'Notification updated successfully!');
    }
    redirectBack('notifications', 'Error updating notification.', true);
}

if (isset($_GET['delete_notification_id'])) {
    $id = intval($_GET['delete_notification_id']);
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $id");
    redirectBack('notifications', 'Notification deleted!');
}

if (isset($_GET['toggle_notification_id'])) {
    $id = intval($_GET['toggle_notification_id']);
    mysqli_query($conn, "UPDATE notifications SET status = IF(status='active','inactive','active') WHERE id = $id");
    redirectBack('notifications', 'Status updated!');
}

/* =========================================================
   GENERAL (Hero fallback text + Statistics numbers)
   ========================================================= */
if (isset($_POST['save_general'])) {
    $keys = ['hero_title', 'hero_description', 'hero_button_text', 'hero_button_link',
             'stats_students', 'stats_teachers', 'stats_courses', 'stats_years',
             'stats_badge', 'stats_title', 'stats_title_highlight',
             'stats_sub_students', 'stats_sub_teachers', 'stats_sub_courses', 'stats_sub_years'];
    $pairs = [];
    foreach ($keys as $key) {
        $pairs[$key] = $_POST[$key] ?? '';
    }
    saveSettingsBatch($conn, $pairs);
    redirectBack('general', 'General homepage settings saved successfully!');
}

/* =========================================================
   DIGITAL MANAGEMENT SECTION HEADER + MOBILE APP CTA
   ========================================================= */
if (isset($_POST['save_dms_header'])) {
    $keys = ['dms_badge', 'dms_title_line1', 'dms_title_line2', 'dms_subtitle',
             'dms_cta_title', 'dms_cta_desc', 'dms_playstore_link'];
    $pairs = [];
    foreach ($keys as $key) {
        $pairs[$key] = $_POST[$key] ?? '';
    }
    saveSettingsBatch($conn, $pairs);
    redirectBack('dms', 'Section header saved successfully!');
}

/* =========================================================
   DIGITAL MANAGEMENT FEATURES
   ========================================================= */
if (isset($_POST['save_dms_feature'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (!empty($_POST['feature_id'])) {
        $id = intval($_POST['feature_id']);
        $query = "UPDATE dms_features SET title='$title', description='$description', icon='$icon', color='$color', display_order=$display_order, status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO dms_features (title, description, icon, color, display_order, status) VALUES ('$title', '$description', '$icon', '$color', $display_order, '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBack('dms', 'Feature saved successfully!');
    }
    redirectBack('dms', 'Error saving feature.', true);
}

if (isset($_GET['delete_dms_id'])) {
    $id = intval($_GET['delete_dms_id']);
    mysqli_query($conn, "DELETE FROM dms_features WHERE id = $id");
    redirectBack('dms', 'Feature deleted successfully!');
}

/* =========================================================
   LEADERSHIP SECTION HEADER
   ========================================================= */
if (isset($_POST['save_leadership_header'])) {
    $keys = ['leadership_badge', 'leadership_title', 'leadership_title_highlight', 'leadership_subtitle'];
    $pairs = [];
    foreach ($keys as $key) {
        $pairs[$key] = $_POST[$key] ?? '';
    }
    saveSettingsBatch($conn, $pairs);
    redirectBack('leadership', 'Section header saved successfully!');
}

/* =========================================================
   LEADERSHIP MESSAGES
   ========================================================= */
if (isset($_POST['add_leader']) || isset($_POST['edit_leader'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $role_title = mysqli_real_escape_string($conn, $_POST['role_title']);
    $leader_message = mysqli_real_escape_string($conn, $_POST['message']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $photo_path = '';
    $signature_path = '';
    $is_edit = isset($_POST['edit_leader']);

    if ($is_edit) {
        $id = intval($_POST['leader_id']);
        $current_result = mysqli_query($conn, "SELECT photo, signature FROM leadership WHERE id = $id");
        $current_data = mysqli_fetch_assoc($current_result);
        $photo_path = $current_data['photo'];
        $signature_path = $current_data['signature'];
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../images/leadership/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'leader_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                if ($photo_path && file_exists('../' . $photo_path)) unlink('../' . $photo_path);
                $photo_path = 'images/leadership/' . $filename;
            }
        }
    }

    if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
        $upload_dir = '../images/signatures/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['signature']['type'], $allowed_types) && $_FILES['signature']['size'] <= 2 * 1024 * 1024) {
            $extension = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
            $filename = 'signature_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['signature']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename, 800, 800);
                if ($signature_path && file_exists('../' . $signature_path)) unlink('../' . $signature_path);
                $signature_path = 'images/signatures/' . $filename;
            }
        }
    }

    if ($is_edit) {
        $query = "UPDATE leadership SET name='$name', designation='$designation', role_title='$role_title',
                  photo='$photo_path', signature='$signature_path', message='$leader_message',
                  display_order=$display_order, status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO leadership (name, designation, role_title, photo, signature, message, display_order, status)
                  VALUES ('$name', '$designation', '$role_title', '$photo_path', '$signature_path', '$leader_message', $display_order, '$status')";
    }

    if (mysqli_query($conn, $query)) {
        redirectBack('leadership', 'Leadership entry saved successfully!');
    }
    redirectBack('leadership', 'Error saving leadership entry: ' . mysqli_error($conn), true);
}

if (isset($_GET['delete_leader_id'])) {
    $id = intval($_GET['delete_leader_id']);
    $result = mysqli_query($conn, "SELECT photo, signature FROM leadership WHERE id = $id");
    $data = mysqli_fetch_assoc($result);
    if (mysqli_query($conn, "DELETE FROM leadership WHERE id = $id")) {
        if ($data['photo'] && file_exists('../' . $data['photo'])) unlink('../' . $data['photo']);
        if ($data['signature'] && file_exists('../' . $data['signature'])) unlink('../' . $data['signature']);
        redirectBack('leadership', 'Leadership entry deleted successfully!');
    }
    redirectBack('leadership', 'Error deleting leadership entry.', true);
}

if (isset($_GET['toggle_leader_id'])) {
    $id = intval($_GET['toggle_leader_id']);
    $current_result = mysqli_query($conn, "SELECT status FROM leadership WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $new_status = ($current_data['status'] == 'active') ? 'inactive' : 'active';
    mysqli_query($conn, "UPDATE leadership SET status = '$new_status' WHERE id = $id");
    redirectBack('leadership', 'Status updated successfully!');
}

/* =========================================================
   WHY CHOOSE US - SECTION HEADER
   ========================================================= */
if (isset($_POST['save_wcu_header'])) {
    $keys = ['wcu_badge', 'wcu_description'];
    $pairs = [];
    foreach ($keys as $key) {
        $pairs[$key] = $_POST[$key] ?? '';
    }
    saveSettingsBatch($conn, $pairs);
    redirectBack('wcu', 'Section header saved successfully!');
}

/* =========================================================
   WHY CHOOSE US
   ========================================================= */
if (isset($_POST['save_wcu_item'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (!empty($_POST['item_id'])) {
        $id = intval($_POST['item_id']);
        $query = "UPDATE why_choose_us SET title='$title', description='$description', icon='$icon', color='$color', display_order=$display_order, status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO why_choose_us (title, description, icon, color, display_order, status) VALUES ('$title', '$description', '$icon', '$color', $display_order, '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBack('wcu', 'Item saved successfully!');
    }
    redirectBack('wcu', 'Error saving item.', true);
}

if (isset($_GET['delete_wcu_id'])) {
    $id = intval($_GET['delete_wcu_id']);
    mysqli_query($conn, "DELETE FROM why_choose_us WHERE id = $id");
    redirectBack('wcu', 'Item deleted successfully!');
}

if (isset($_POST['save_wcu_stats'])) {
    $pairs = [
        'wcu_stat1_number' => $_POST['wcu_stat1_number'], 'wcu_stat1_label' => $_POST['wcu_stat1_label'],
        'wcu_stat2_number' => $_POST['wcu_stat2_number'], 'wcu_stat2_label' => $_POST['wcu_stat2_label'],
        'wcu_stat3_number' => $_POST['wcu_stat3_number'], 'wcu_stat3_label' => $_POST['wcu_stat3_label'],
    ];
    saveSettingsBatch($conn, $pairs);
    redirectBack('wcu', 'Stat pills saved successfully!');
}

/* =========================================================
   BOTTOM CTA SECTION
   ========================================================= */
if (isset($_POST['save_cta_section'])) {
    $keys = ['cta_badge', 'cta_heading_line1', 'cta_heading_line2', 'cta_subtext',
             'cta_pill1', 'cta_pill2', 'cta_pill3', 'cta_pill4'];
    $pairs = [];
    foreach ($keys as $key) {
        $pairs[$key] = $_POST[$key] ?? '';
    }
    saveSettingsBatch($conn, $pairs);
    redirectBack('cta', 'CTA section saved successfully!');
}

/* =========================================================
   DATA FOR ACTIVE TAB
   ========================================================= */
$slides_result = mysqli_query($conn, "SELECT * FROM hero_carousel ORDER BY display_order ASC, id ASC");
$notifications_result = mysqli_query($conn, "SELECT * FROM notifications ORDER BY display_order ASC, created_at DESC");
$hero = getHeroContent();
$stats = getStatistics();
$dms_features_result = mysqli_query($conn, "SELECT * FROM dms_features ORDER BY display_order ASC, id ASC");
$leaders_result = mysqli_query($conn, "SELECT * FROM leadership ORDER BY display_order ASC, id ASC");
$wcu_items_result = mysqli_query($conn, "SELECT * FROM why_choose_us ORDER BY display_order ASC, id ASC");
$wcu_stats = getWcuStats();
$stats_header = getStatsHeader();
$dms_header = getDmsHeader();
$leadership_header = getLeadershipHeader();
$wcu_header = getWcuHeader();
$cta_section = getCtaSection();

$tab_labels = [
    'hero' => ['icon' => 'fas fa-images', 'label' => 'Hero Carousel'],
    'notifications' => ['icon' => 'fas fa-bullhorn', 'label' => 'Notifications'],
    'general' => ['icon' => 'fas fa-sliders-h', 'label' => 'General & Stats'],
    'dms' => ['icon' => 'fas fa-microchip', 'label' => 'Digital Features'],
    'leadership' => ['icon' => 'fas fa-users', 'label' => 'Leadership'],
    'wcu' => ['icon' => 'fas fa-award', 'label' => 'Why Choose Us'],
    'cta' => ['icon' => 'fas fa-rocket', 'label' => 'Bottom CTA'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Home Page - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-light); }

        .tab-nav {
            display: flex; flex-wrap: wrap; gap: 8px;
            margin-bottom: 25px; border-bottom: 2px solid var(--border-color); padding-bottom: 0;
        }
        .tab-nav a {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 20px; text-decoration: none;
            color: var(--text-light); font-weight: 600; font-size: 14px;
            border-radius: 10px 10px 0 0; border-bottom: 3px solid transparent;
            transition: all 0.25s ease;
        }
        .tab-nav a:hover { background: rgba(16,42,86,0.06); color: var(--primary-color); }
        .tab-nav a.active {
            color: var(--primary-color); border-bottom-color: var(--accent-color);
            background: rgba(16,42,86,0.08);
        }

        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #fff; }
        .status-active { background: #16A34A; }
        .status-inactive { background: #DC2626; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; }
        .data-table tr:hover { background: #F4F8FC; }

        .thumb-img { width: 90px; height: 55px; border-radius: 6px; object-fit: cover; border: 2px solid var(--accent-color); }
        .avatar-img { width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color); }

        .action-btn {
            padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px;
            cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block;
        }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }
        .btn-toggle { background: #16A34A; color: #fff; }

        .form-modal {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 1000; overflow-y: auto; align-items: center; justify-content: center; padding: 20px;
        }
        .form-modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 15px; max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .preview-img { max-width: 220px; margin-top: 10px; border-radius: 10px; display: none; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .form-actions .btn-cancel {
            flex: 1; padding: 12px; background: var(--text-light); color: #fff; border: none;
            border-radius: 8px; cursor: pointer; font-weight: 600;
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-home"></i> Manage Home Page</h1>
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

        <script>
            function togglePageContent(tab) {
                var wrap = document.getElementById(tab + '-page-content');
                var chevron = document.getElementById(tab + '-pc-chevron');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        </script>

        <?php if ($active_tab === 'hero'): ?>
        <!-- ============ HERO CAROUSEL TAB ============ -->
        <div class="card" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="color: var(--primary-color);"><i class="fas fa-info-circle"></i> Carousel Info:</strong>
                Rotating hero images. Recommended size: 1920x600px. Max 10MB per image.
            </div>
            <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Slide</button>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Image</th><th>Title</th><th>Subtitle</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($slides_result && mysqli_num_rows($slides_result) > 0): ?>
                    <?php while ($slide = mysqli_fetch_assoc($slides_result)): ?>
                        <tr>
                            <td><strong><?php echo $slide['display_order']; ?></strong></td>
                            <td><img src="../<?php echo htmlspecialchars($slide['image_path']); ?>" class="thumb-img"></td>
                            <td><strong><?php echo htmlspecialchars($slide['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($slide['subtitle'], 0, 50)); ?></td>
                            <td><span class="status-badge status-<?php echo $slide['status']; ?>"><?php echo ucfirst($slide['status']); ?></span></td>
                            <td>
                                <button class="action-btn btn-edit" onclick='openEditModal(<?php echo json_encode($slide, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                <a href="?tab=hero&toggle_slide_id=<?php echo $slide['id']; ?>" class="action-btn btn-toggle" onclick="return confirm('Toggle status?')"><i class="fas fa-toggle-on"></i></a>
                                <a href="?tab=hero&delete_slide_id=<?php echo $slide['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this slide?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No carousel slides yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="addModal" class="form-modal">
            <div class="modal-content">
                <h2 style="margin-bottom:20px;color:var(--primary-color);"><i class="fas fa-plus-circle"></i> Add New Slide</h2>
                <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_1">Slide Image *</label>
                        <input id="auto_AdminCP_manage_home_php_1" type="file" name="slide_image" accept="image/*" required onchange="previewImage(this,'add_image_preview')">
                        <img id="add_image_preview" class="preview-img">
                    </div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_2">Title</label><input id="auto_AdminCP_manage_home_php_2" type="text" name="title" placeholder="Slide title"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_3">Subtitle</label><textarea id="auto_AdminCP_manage_home_php_3" name="subtitle" rows="2" placeholder="Slide subtitle"></textarea></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_4">Text Color</label><input id="auto_AdminCP_manage_home_php_4" type="color" name="text_color" value="#ffffff" style="height:42px;"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_5">Display Order</label><input id="auto_AdminCP_manage_home_php_5" type="number" name="display_order" value="0" min="0"></div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_6">Status</label>
                        <select id="auto_AdminCP_manage_home_php_6" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_slide" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Add Slide</button>
                        <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editModal" class="form-modal">
            <div class="modal-content">
                <h2 style="margin-bottom:20px;color:var(--primary-color);"><i class="fas fa-edit"></i> Edit Slide</h2>
                <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                    <input type="hidden" name="slide_id" id="edit_slide_id">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_7">Slide Image (leave empty to keep current)</label>
                        <input id="auto_AdminCP_manage_home_php_7" type="file" name="slide_image" accept="image/*" onchange="previewImage(this,'edit_image_preview')">
                        <img id="edit_image_preview" class="preview-img">
                    </div>
                    <div class="form-group"><label>Title</label><input type="text" name="title" id="edit_title"></div>
                    <div class="form-group"><label>Subtitle</label><textarea name="subtitle" id="edit_subtitle" rows="2"></textarea></div>
                    <div class="form-group"><label>Text Color</label><input type="color" name="text_color" id="edit_text_color" style="height:42px;"></div>
                    <div class="form-group"><label>Display Order</label><input type="number" name="display_order" id="edit_display_order" min="0"></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="edit_slide" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Update Slide</button>
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAddModal(){document.getElementById('addModal').classList.add('active');}
            function closeAddModal(){document.getElementById('addModal').classList.remove('active');}
            function openEditModal(slide){
                document.getElementById('edit_slide_id').value = slide.id;
                document.getElementById('edit_title').value = slide.title || '';
                document.getElementById('edit_subtitle').value = slide.subtitle || '';
                document.getElementById('edit_text_color').value = slide.text_color || '#ffffff';
                document.getElementById('edit_display_order').value = slide.display_order;
                document.getElementById('edit_status').value = slide.status;
                var p = document.getElementById('edit_image_preview');
                if (slide.image_path) { p.src = '../' + slide.image_path; p.style.display='block'; } else { p.style.display='none'; }
                document.getElementById('editModal').classList.add('active');
            }
            function closeEditModal(){document.getElementById('editModal').classList.remove('active');}
            function previewImage(input, previewId){
                var preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display='block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            }
            window.onclick = function(e){ if (e.target.classList.contains('form-modal')) e.target.classList.remove('active'); };
        </script>

        <?php elseif ($active_tab === 'notifications'): ?>
        <!-- ============ NOTIFICATIONS TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add New Notification</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div style="display:grid;grid-template-columns:3fr 2fr 1fr;gap:15px;margin-bottom:15px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_home_php_8">Notification Title</label>
                        <input id="auto_AdminCP_manage_home_php_8" type="text" name="title" required placeholder="e.g., Admissions Open for Session 2026">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_home_php_9">Link (Optional)</label>
                        <input id="auto_AdminCP_manage_home_php_9" type="text" name="link" placeholder="admission.php">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_home_php_10">Display Order</label>
                        <input id="auto_AdminCP_manage_home_php_10" type="number" name="display_order" value="0">
                    </div>
                </div>
                <button type="submit" name="add_notification" class="btn btn-primary"><i class="fas fa-plus"></i> Add Notification</button>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Link</th><th>Order</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($notifications_result && mysqli_num_rows($notifications_result) > 0): ?>
                    <?php while ($notif = mysqli_fetch_assoc($notifications_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($notif['title']); ?></td>
                            <td><?php echo $notif['link'] ? htmlspecialchars($notif['link']) : '-'; ?></td>
                            <td><?php echo $notif['display_order']; ?></td>
                            <td><span class="status-badge status-<?php echo $notif['status']; ?>"><?php echo ucfirst($notif['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($notif['created_at'])); ?></td>
                            <td>
                                <button class="action-btn btn-edit" onclick='openEditNotifModal(<?php echo json_encode($notif, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                <a href="?tab=notifications&toggle_notification_id=<?php echo $notif['id']; ?>" class="action-btn btn-toggle"><i class="fas fa-toggle-on"></i></a>
                                <a href="?tab=notifications&delete_notification_id=<?php echo $notif['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No notifications yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="editNotifModal" class="form-modal">
            <div class="modal-content">
                <h2 style="margin-bottom:20px;color:var(--primary-color);"><i class="fas fa-edit"></i> Edit Notification</h2>
                <form method="POST"><?php echo csrf_field(); ?>
                    <input type="hidden" name="notif_id" id="edit_notif_id">
                    <div class="form-group"><label>Notification Title</label><input type="text" name="title" id="edit_notif_title" required></div>
                    <div class="form-group"><label>Link (Optional)</label><input type="text" name="link" id="edit_notif_link"></div>
                    <div class="form-group"><label>Display Order</label><input type="number" name="display_order" id="edit_notif_display_order"></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_notif_status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="edit_notification" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Update Notification</button>
                        <button type="button" class="btn-cancel" onclick="closeEditNotifModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditNotifModal(notif){
                document.getElementById('edit_notif_id').value = notif.id;
                document.getElementById('edit_notif_title').value = notif.title || '';
                document.getElementById('edit_notif_link').value = notif.link || '';
                document.getElementById('edit_notif_display_order').value = notif.display_order;
                document.getElementById('edit_notif_status').value = notif.status;
                document.getElementById('editNotifModal').classList.add('active');
            }
            function closeEditNotifModal(){document.getElementById('editNotifModal').classList.remove('active');}
            window.onclick = function(e){ if (e.target.classList.contains('form-modal')) e.target.classList.remove('active'); };
        </script>

        <?php elseif ($active_tab === 'general'): ?>
        <!-- ============ GENERAL TAB (Hero fallback + Stats) ============ -->
        <form method="POST"><?php echo csrf_field(); ?>
            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-image"></i> Hero Fallback Content</h2>
                <p style="color: var(--text-light); font-size: 13px; margin-bottom: 15px;">Used only when no active carousel slides exist.</p>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_home_php_11">Hero Title</label>
                    <input id="auto_AdminCP_manage_home_php_11" type="text" name="hero_title" value="<?php echo htmlspecialchars($hero['title']); ?>">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_home_php_12">Hero Description</label>
                    <textarea id="auto_AdminCP_manage_home_php_12" name="hero_description" rows="3"><?php echo htmlspecialchars($hero['description']); ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_13">Button Text</label>
                        <input id="auto_AdminCP_manage_home_php_13" type="text" name="hero_button_text" value="<?php echo htmlspecialchars($hero['button_text']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_14">Button Link</label>
                        <input id="auto_AdminCP_manage_home_php_14" type="text" name="hero_button_link" value="<?php echo htmlspecialchars($hero['button_link']); ?>">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-chart-bar"></i> Statistics Section Numbers</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_15">Active Students Count</label>
                        <input id="auto_AdminCP_manage_home_php_15" type="number" name="stats_students" value="<?php echo htmlspecialchars($stats['students']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_16">Expert Teachers Count</label>
                        <input id="auto_AdminCP_manage_home_php_16" type="number" name="stats_teachers" value="<?php echo htmlspecialchars($stats['teachers']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_17">Courses Offered Count</label>
                        <input id="auto_AdminCP_manage_home_php_17" type="number" name="stats_courses" value="<?php echo htmlspecialchars($stats['courses']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_18">Years of Excellence</label>
                        <input id="auto_AdminCP_manage_home_php_18" type="number" name="stats_years" value="<?php echo htmlspecialchars($stats['years']); ?>">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 25px;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-heading"></i> Statistics Section Header</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_19">Top Badge Text</label>
                        <input id="auto_AdminCP_manage_home_php_19" type="text" name="stats_badge" value="<?php echo htmlspecialchars($stats_header['badge']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_20">Highlighted Word in Title</label>
                        <input id="auto_AdminCP_manage_home_php_20" type="text" name="stats_title_highlight" value="<?php echo htmlspecialchars($stats_header['highlight']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_home_php_21">Title</label>
                    <input id="auto_AdminCP_manage_home_php_21" type="text" name="stats_title" value="<?php echo htmlspecialchars($stats_header['title']); ?>">
                    <small style="color:var(--text-light);">The word entered above (if found inside this title) will be shown in gold.</small>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_22">Students Sub-label</label>
                        <input id="auto_AdminCP_manage_home_php_22" type="text" name="stats_sub_students" value="<?php echo htmlspecialchars($stats_header['sub_students']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_23">Teachers Sub-label</label>
                        <input id="auto_AdminCP_manage_home_php_23" type="text" name="stats_sub_teachers" value="<?php echo htmlspecialchars($stats_header['sub_teachers']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_24">Courses Sub-label</label>
                        <input id="auto_AdminCP_manage_home_php_24" type="text" name="stats_sub_courses" value="<?php echo htmlspecialchars($stats_header['sub_courses']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_25">Years Sub-label</label>
                        <input id="auto_AdminCP_manage_home_php_25" type="text" name="stats_sub_years" value="<?php echo htmlspecialchars($stats_header['sub_years']); ?>">
                    </div>
                </div>
            </div>

            <button type="submit" name="save_general" class="btn btn-primary" style="padding:14px 36px;"><i class="fas fa-save"></i> Save General Settings</button>
        </form>

        <?php elseif ($active_tab === 'dms'): ?>
        <!-- ============ DIGITAL MANAGEMENT FEATURES TAB ============ -->
        <?php
        $edit_feature = null;
        if (isset($_GET['edit_dms'])) {
            $r = mysqli_query($conn, "SELECT * FROM dms_features WHERE id = " . intval($_GET['edit_dms']));
            $edit_feature = mysqli_fetch_assoc($r);
        }
        $show_dms_form = $edit_feature || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dms_feature']));
        ?>
        <div class="admin-page-title" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="color: var(--primary-color); margin:0; font-size:19px;"><i class="fas fa-th-large"></i> Digital Management Features</h2>
            <button type="button" onclick="toggleDmsForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Feature</button>
        </div>

        <div id="dms-form-wrap" style="<?php echo $show_dms_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_feature ? 'Edit Feature' : 'Add New Feature'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_feature): ?><input type="hidden" name="feature_id" value="<?php echo $edit_feature['id']; ?>"><?php endif; ?>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_33">Title *</label><input id="auto_AdminCP_manage_home_php_33" type="text" name="title" required value="<?php echo $edit_feature ? htmlspecialchars($edit_feature['title']) : ''; ?>" placeholder="e.g., Online Exam Results"></div>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_34">Description *</label><textarea id="auto_AdminCP_manage_home_php_34" name="description" required rows="3"><?php echo $edit_feature ? htmlspecialchars($edit_feature['description']) : ''; ?></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_35">Icon (Font Awesome)</label><input id="auto_AdminCP_manage_home_php_35" type="text" name="icon" value="<?php echo $edit_feature ? htmlspecialchars($edit_feature['icon']) : 'fas fa-star'; ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_36">Color</label><input id="auto_AdminCP_manage_home_php_36" type="color" name="color" value="<?php echo $edit_feature ? htmlspecialchars($edit_feature['color']) : '#102A56'; ?>" style="height:42px;"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_37">Display Order</label><input id="auto_AdminCP_manage_home_php_37" type="number" name="display_order" value="<?php echo $edit_feature ? $edit_feature['display_order'] : 0; ?>" min="0"></div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_home_php_38">Status</label>
                    <select id="auto_AdminCP_manage_home_php_38" name="status">
                        <option value="active" <?php echo ($edit_feature && $edit_feature['status']=='active') ? 'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_feature && $edit_feature['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" name="save_dms_feature" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_feature ? 'Update' : 'Add'; ?> Feature</button>
                <?php if ($edit_feature): ?><a href="?tab=dms" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a>
                <?php else: ?><button type="button" onclick="toggleDmsForm()" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</button><?php endif; ?>
            </form>
        </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Icon</th><th>Title</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($dms_features_result && mysqli_num_rows($dms_features_result) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($dms_features_result)): ?>
                        <tr>
                            <td><?php echo $item['display_order']; ?></td>
                            <td><div style="width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:<?php echo htmlspecialchars($item['color']); ?>1a;"><i class="<?php echo htmlspecialchars($item['icon']); ?>" style="color:<?php echo htmlspecialchars($item['color']); ?>;"></i></div></td>
                            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                            <td><?php echo substr(htmlspecialchars($item['description']), 0, 60); ?></td>
                            <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                            <td>
                                <a href="?tab=dms&edit_dms=<?php echo $item['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=dms&delete_dms_id=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this feature?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No features yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:25px;">
            <div onclick="togglePageContent('dms')" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="color: var(--primary-color); margin:0;"><i class="fas fa-sliders"></i> Public Page Content Settings</h2>
                <i class="fas fa-chevron-down" id="dms-pc-chevron" style="color:var(--text-light);transition:transform 0.25s ease;"></i>
            </div>
            <p style="color:var(--text-light);font-size:12.5px;margin:8px 0 0;">Section header and Mobile App CTA box text shown on the public Home page. Rarely needs changing.</p>

            <div id="dms-page-content" style="display:none;margin-top:22px;">
                <div class="card" style="box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-heading"></i> Section Header</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_home_php_26">Top Badge Text</label><input id="auto_AdminCP_manage_home_php_26" type="text" name="dms_badge" value="<?php echo htmlspecialchars($dms_header['badge']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_home_php_27">Title Line 1 (plain)</label><input id="auto_AdminCP_manage_home_php_27" type="text" name="dms_title_line1" value="<?php echo htmlspecialchars($dms_header['title_line1']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_home_php_28">Title Line 2 (gold gradient)</label><input id="auto_AdminCP_manage_home_php_28" type="text" name="dms_title_line2" value="<?php echo htmlspecialchars($dms_header['title_line2']); ?>"></div>
                        </div>
                        <div class="form-group">
                            <label for="auto_AdminCP_manage_home_php_29">Subtitle</label>
                            <textarea id="auto_AdminCP_manage_home_php_29" name="dms_subtitle" rows="2"><?php echo htmlspecialchars($dms_header['subtitle']); ?></textarea>
                            <small style="color:var(--text-light);">Use <code>{site_name}</code> anywhere you want the college name inserted automatically.</small>
                        </div>
                        <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                        <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-mobile-alt"></i> Mobile App CTA Box</h3>
                        <div class="form-group"><label for="auto_AdminCP_manage_home_php_30">Title</label><input id="auto_AdminCP_manage_home_php_30" type="text" name="dms_cta_title" value="<?php echo htmlspecialchars($dms_header['cta_title']); ?>"></div>
                        <div class="form-group"><label for="auto_AdminCP_manage_home_php_31">Description</label><textarea id="auto_AdminCP_manage_home_php_31" name="dms_cta_desc" rows="2"><?php echo htmlspecialchars($dms_header['cta_desc']); ?></textarea></div>
                        <div class="form-group"><label for="auto_AdminCP_manage_home_php_32">Google Play Link</label><input id="auto_AdminCP_manage_home_php_32" type="text" name="dms_playstore_link" value="<?php echo htmlspecialchars($dms_header['playstore_link']); ?>"></div>
                        <button type="submit" name="save_dms_header" class="btn btn-primary"><i class="fas fa-save"></i> Save Section Header</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function toggleDmsForm() {
                var wrap = document.getElementById('dms-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <?php elseif ($active_tab === 'leadership'): ?>
        <!-- ============ LEADERSHIP TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-heading"></i> Section Header</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_39">Top Badge Text</label><input id="auto_AdminCP_manage_home_php_39" type="text" name="leadership_badge" value="<?php echo htmlspecialchars($leadership_header['badge']); ?>"></div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_40">Title</label>
                        <input id="auto_AdminCP_manage_home_php_40" type="text" name="leadership_title" value="<?php echo htmlspecialchars($leadership_header['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_41">Highlighted Word</label>
                        <input id="auto_AdminCP_manage_home_php_41" type="text" name="leadership_title_highlight" value="<?php echo htmlspecialchars($leadership_header['highlight']); ?>">
                    </div>
                </div>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_42">Subtitle</label><textarea id="auto_AdminCP_manage_home_php_42" name="leadership_subtitle" rows="2"><?php echo htmlspecialchars($leadership_header['subtitle']); ?></textarea></div>
                <button type="submit" name="save_leadership_header" class="btn btn-primary"><i class="fas fa-save"></i> Save Section Header</button>
            </form>
        </div>

        <div class="card" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
            <strong style="color: var(--primary-color);"><i class="fas fa-users"></i> Leadership Messages</strong>
            <button class="btn btn-primary" onclick="openAddLeaderModal()"><i class="fas fa-plus"></i> Add New Leader</button>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Photo</th><th>Name</th><th>Designation</th><th>Role Title</th><th>Status</th><th>Actions</th></tr></thead>
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
                            <td>
                                <button class="action-btn btn-edit" onclick='openEditLeaderModal(<?php echo json_encode($leader, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                <a href="?tab=leadership&toggle_leader_id=<?php echo $leader['id']; ?>" class="action-btn btn-toggle" onclick="return confirm('Toggle status?')"><i class="fas fa-toggle-on"></i></a>
                                <a href="?tab=leadership&delete_leader_id=<?php echo $leader['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this leadership entry?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No leadership entries yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="addLeaderModal" class="form-modal">
            <div class="modal-content">
                <h2 style="margin-bottom:20px;color:var(--primary-color);"><i class="fas fa-user-plus"></i> Add New Leader</h2>
                <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_43">Name *</label><input id="auto_AdminCP_manage_home_php_43" type="text" name="name" required></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_44">Designation *</label><input id="auto_AdminCP_manage_home_php_44" type="text" name="designation" required placeholder="e.g., Founder & Managing Director"><small style="color:var(--text-light);">Include the word "Director" or "Principal" here (or in Role Title) to have this person appear on the matching dedicated message page.</small></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_45">Role Title *</label><input id="auto_AdminCP_manage_home_php_45" type="text" name="role_title" required placeholder="e.g., Founder's Message"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_46">Photo</label><input id="auto_AdminCP_manage_home_php_46" type="file" name="photo" accept="image/*" onchange="previewImage(this,'add_photo_preview')"><img id="add_photo_preview" class="preview-img"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_47">Signature</label><input id="auto_AdminCP_manage_home_php_47" type="file" name="signature" accept="image/*" onchange="previewImage(this,'add_signature_preview')"><img id="add_signature_preview" class="preview-img"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_48">Message *</label><textarea id="auto_AdminCP_manage_home_php_48" name="message" required rows="5"></textarea></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_49">Display Order</label><input id="auto_AdminCP_manage_home_php_49" type="number" name="display_order" value="0" min="0"></div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_50">Status</label>
                        <select id="auto_AdminCP_manage_home_php_50" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_leader" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Add Leader</button>
                        <button type="button" class="btn-cancel" onclick="closeAddLeaderModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editLeaderModal" class="form-modal">
            <div class="modal-content">
                <h2 style="margin-bottom:20px;color:var(--primary-color);"><i class="fas fa-edit"></i> Edit Leader</h2>
                <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                    <input type="hidden" name="leader_id" id="edit_leader_id">
                    <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit_leader_name" required></div>
                    <div class="form-group"><label>Designation *</label><input type="text" name="designation" id="edit_leader_designation" required></div>
                    <div class="form-group"><label>Role Title *</label><input type="text" name="role_title" id="edit_leader_role_title" required></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_51">Photo (leave empty to keep current)</label><input id="auto_AdminCP_manage_home_php_51" type="file" name="photo" accept="image/*" onchange="previewImage(this,'edit_photo_preview')"><img id="edit_photo_preview" class="preview-img"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_52">Signature (leave empty to keep current)</label><input id="auto_AdminCP_manage_home_php_52" type="file" name="signature" accept="image/*" onchange="previewImage(this,'edit_signature_preview')"><img id="edit_signature_preview" class="preview-img"></div>
                    <div class="form-group"><label>Message *</label><textarea name="message" id="edit_leader_message" required rows="5"></textarea></div>
                    <div class="form-group"><label>Display Order</label><input type="number" name="display_order" id="edit_leader_display_order" min="0"></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_leader_status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="edit_leader" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Update Leader</button>
                        <button type="button" class="btn-cancel" onclick="closeEditLeaderModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openAddLeaderModal(){document.getElementById('addLeaderModal').classList.add('active');}
            function closeAddLeaderModal(){document.getElementById('addLeaderModal').classList.remove('active');}
            function openEditLeaderModal(leader){
                document.getElementById('edit_leader_id').value = leader.id;
                document.getElementById('edit_leader_name').value = leader.name;
                document.getElementById('edit_leader_designation').value = leader.designation;
                document.getElementById('edit_leader_role_title').value = leader.role_title;
                document.getElementById('edit_leader_message').value = leader.message;
                document.getElementById('edit_leader_display_order').value = leader.display_order;
                document.getElementById('edit_leader_status').value = leader.status;
                var pp = document.getElementById('edit_photo_preview');
                if (leader.photo) { pp.src = '../' + leader.photo; pp.style.display='block'; } else { pp.style.display='none'; }
                var sp = document.getElementById('edit_signature_preview');
                if (leader.signature) { sp.src = '../' + leader.signature; sp.style.display='block'; } else { sp.style.display='none'; }
                document.getElementById('editLeaderModal').classList.add('active');
            }
            function closeEditLeaderModal(){document.getElementById('editLeaderModal').classList.remove('active');}
            function previewImage(input, previewId){
                var preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display='block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            }
            window.onclick = function(e){ if (e.target.classList.contains('form-modal')) e.target.classList.remove('active'); };
        </script>

        <?php elseif ($active_tab === 'wcu'): ?>
        <!-- ============ WHY CHOOSE US TAB ============ -->
        <?php
        $edit_wcu = null;
        if (isset($_GET['edit_wcu'])) {
            $r = mysqli_query($conn, "SELECT * FROM why_choose_us WHERE id = " . intval($_GET['edit_wcu']));
            $edit_wcu = mysqli_fetch_assoc($r);
        }
        $show_wcu_form = $edit_wcu || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wcu_item']));
        ?>
        <div class="admin-page-title" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="color: var(--primary-color); margin:0; font-size:19px;"><i class="fas fa-star"></i> Why Choose Us Items</h2>
            <button type="button" onclick="toggleWcuForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Item</button>
        </div>

        <div id="wcu-form-wrap" style="<?php echo $show_wcu_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_wcu ? 'Edit Item' : 'Add New Item'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_wcu): ?><input type="hidden" name="item_id" value="<?php echo $edit_wcu['id']; ?>"><?php endif; ?>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_61">Title *</label><input id="auto_AdminCP_manage_home_php_61" type="text" name="title" required value="<?php echo $edit_wcu ? htmlspecialchars($edit_wcu['title']) : ''; ?>" placeholder="e.g., Expert Faculty"></div>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_62">Description *</label><textarea id="auto_AdminCP_manage_home_php_62" name="description" required rows="3"><?php echo $edit_wcu ? htmlspecialchars($edit_wcu['description']) : ''; ?></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_63">Icon (Font Awesome)</label><input id="auto_AdminCP_manage_home_php_63" type="text" name="icon" value="<?php echo $edit_wcu ? htmlspecialchars($edit_wcu['icon']) : 'fas fa-star'; ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_64">Color</label><input id="auto_AdminCP_manage_home_php_64" type="color" name="color" value="<?php echo $edit_wcu ? htmlspecialchars($edit_wcu['color']) : '#102A56'; ?>" style="height:42px;"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_65">Display Order</label><input id="auto_AdminCP_manage_home_php_65" type="number" name="display_order" value="<?php echo $edit_wcu ? $edit_wcu['display_order'] : 0; ?>" min="0"></div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_home_php_66">Status</label>
                    <select id="auto_AdminCP_manage_home_php_66" name="status">
                        <option value="active" <?php echo ($edit_wcu && $edit_wcu['status']=='active') ? 'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_wcu && $edit_wcu['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" name="save_wcu_item" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_wcu ? 'Update' : 'Add'; ?> Item</button>
                <?php if ($edit_wcu): ?><a href="?tab=wcu" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a>
                <?php else: ?><button type="button" onclick="toggleWcuForm()" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</button><?php endif; ?>
            </form>
        </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Icon</th><th>Title</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($wcu_items_result && mysqli_num_rows($wcu_items_result) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($wcu_items_result)): ?>
                        <tr>
                            <td><?php echo $item['display_order']; ?></td>
                            <td><div style="width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:<?php echo htmlspecialchars($item['color']); ?>1a;"><i class="<?php echo htmlspecialchars($item['icon']); ?>" style="color:<?php echo htmlspecialchars($item['color']); ?>;"></i></div></td>
                            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                            <td><?php echo substr(htmlspecialchars($item['description']), 0, 60); ?></td>
                            <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                            <td>
                                <a href="?tab=wcu&edit_wcu=<?php echo $item['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=wcu&delete_wcu_id=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this item?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No items yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:25px;">
            <div onclick="togglePageContent('wcu')" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="color: var(--primary-color); margin:0;"><i class="fas fa-sliders"></i> Public Page Content Settings</h2>
                <i class="fas fa-chevron-down" id="wcu-pc-chevron" style="color:var(--text-light);transition:transform 0.25s ease;"></i>
            </div>
            <p style="color:var(--text-light);font-size:12.5px;margin:8px 0 0;">Section header and stat pills shown on the public Home page. Rarely needs changing.</p>

            <div id="wcu-page-content" style="display:none;margin-top:22px;">
                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-heading"></i> Section Header</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_home_php_53">Top Badge Text</label><input id="auto_AdminCP_manage_home_php_53" type="text" name="wcu_badge" value="<?php echo htmlspecialchars($wcu_header['badge']); ?>"></div>
                        <div class="form-group">
                            <label for="auto_AdminCP_manage_home_php_54">Description</label>
                            <textarea id="auto_AdminCP_manage_home_php_54" name="wcu_description" rows="2"><?php echo htmlspecialchars($wcu_header['description']); ?></textarea>
                        </div>
                        <p style="color:var(--text-light);font-size:12.5px;margin:-6px 0 15px;">Note: the "Why Choose <?php echo htmlspecialchars(SITE_NAME); ?>?" title itself always uses the site name automatically and isn't editable here.</p>
                        <button type="submit" name="save_wcu_header" class="btn btn-primary"><i class="fas fa-save"></i> Save Section Header</button>
                    </form>
                </div>

                <div class="card" style="box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;">Top Stat Pills (98% / 500+ / 15+ row)</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div>
                                <div class="form-group"><label for="auto_AdminCP_manage_home_php_55">Stat 1 Number</label><input id="auto_AdminCP_manage_home_php_55" type="text" name="wcu_stat1_number" value="<?php echo htmlspecialchars($wcu_stats[0]['number']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_home_php_56">Stat 1 Label</label><input id="auto_AdminCP_manage_home_php_56" type="text" name="wcu_stat1_label" value="<?php echo htmlspecialchars($wcu_stats[0]['label']); ?>"></div>
                            </div>
                            <div>
                                <div class="form-group"><label for="auto_AdminCP_manage_home_php_57">Stat 2 Number</label><input id="auto_AdminCP_manage_home_php_57" type="text" name="wcu_stat2_number" value="<?php echo htmlspecialchars($wcu_stats[1]['number']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_home_php_58">Stat 2 Label</label><input id="auto_AdminCP_manage_home_php_58" type="text" name="wcu_stat2_label" value="<?php echo htmlspecialchars($wcu_stats[1]['label']); ?>"></div>
                            </div>
                            <div>
                                <div class="form-group"><label for="auto_AdminCP_manage_home_php_59">Stat 3 Number</label><input id="auto_AdminCP_manage_home_php_59" type="text" name="wcu_stat3_number" value="<?php echo htmlspecialchars($wcu_stats[2]['number']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_home_php_60">Stat 3 Label</label><input id="auto_AdminCP_manage_home_php_60" type="text" name="wcu_stat3_label" value="<?php echo htmlspecialchars($wcu_stats[2]['label']); ?>"></div>
                            </div>
                        </div>
                        <button type="submit" name="save_wcu_stats" class="btn btn-primary"><i class="fas fa-save"></i> Save Stat Pills</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function toggleWcuForm() {
                var wrap = document.getElementById('wcu-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <?php elseif ($active_tab === 'cta'): ?>
        <!-- ============ BOTTOM CTA SECTION TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-rocket"></i> "Ready to Start Your Educational Journey?" Section</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group"><label for="auto_AdminCP_manage_home_php_67">Top Badge Text</label><input id="auto_AdminCP_manage_home_php_67" type="text" name="cta_badge" value="<?php echo htmlspecialchars($cta_section['badge']); ?>"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_68">Heading Line 1 (plain)</label>
                        <input id="auto_AdminCP_manage_home_php_68" type="text" name="cta_heading_line1" value="<?php echo htmlspecialchars($cta_section['heading_line1']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_home_php_69">Heading Line 2 (gold gradient)</label>
                        <input id="auto_AdminCP_manage_home_php_69" type="text" name="cta_heading_line2" value="<?php echo htmlspecialchars($cta_section['heading_line2']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_home_php_70">Subtext</label>
                    <textarea id="auto_AdminCP_manage_home_php_70" name="cta_subtext" rows="3"><?php echo htmlspecialchars($cta_section['subtext']); ?></textarea>
                    <small style="color:var(--text-light);">Use <code>{site_name}</code> anywhere you want the college name inserted automatically.</small>
                </div>
                <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:16px;"><i class="fas fa-check-circle"></i> Trust Pills</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_71">Pill 1</label><input id="auto_AdminCP_manage_home_php_71" type="text" name="cta_pill1" value="<?php echo htmlspecialchars($cta_section['pill1']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_72">Pill 2</label><input id="auto_AdminCP_manage_home_php_72" type="text" name="cta_pill2" value="<?php echo htmlspecialchars($cta_section['pill2']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_73">Pill 3</label><input id="auto_AdminCP_manage_home_php_73" type="text" name="cta_pill3" value="<?php echo htmlspecialchars($cta_section['pill3']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_home_php_74">Pill 4</label><input id="auto_AdminCP_manage_home_php_74" type="text" name="cta_pill4" value="<?php echo htmlspecialchars($cta_section['pill4']); ?>"></div>
                </div>
                <button type="submit" name="save_cta_section" class="btn btn-primary" style="padding:14px 36px;"><i class="fas fa-save"></i> Save CTA Section</button>
            </form>
        </div>

        <?php endif; ?>
    </div>
    </div>
</body>
</html>
