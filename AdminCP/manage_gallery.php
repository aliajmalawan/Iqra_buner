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

// Image compression function
function compressImage($source, $destination, $quality = 75) {
    // Without PHP's GD extension images can't be re-encoded: keep the original
    // upload instead of crashing (enable extension=gd in php.ini to compress).
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
        return copy($source, $destination);
    }
    $info = getimagesize($source);
    if ($info === false) return false;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    // Save compressed image
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);

    return file_exists($destination);
}

// Compress image to target size (100KB)
function compressToTargetSize($source, $destination, $maxSizeKB = 100) {
    $maxSizeBytes = $maxSizeKB * 1024;

    // No GD: can't recompress, so keep the original file as-is
    if (!function_exists('imagecreatefromjpeg')) {
        return copy($source, $destination);
    }

    // If already under size, just copy
    if (filesize($source) <= $maxSizeBytes) {
        copy($source, $destination);
        return true;
    }

    // Start with quality 75 and reduce until file is small enough
    $quality = 75;
    $attempts = 0;
    $maxAttempts = 10;

    while ($attempts < $maxAttempts) {
        compressImage($source, $destination, $quality);

        if (file_exists($destination) && filesize($destination) <= $maxSizeBytes) {
            return true;
        }

        // Reduce quality for next attempt
        $quality -= 10;
        $attempts++;

        if ($quality < 10) {
            $quality = 10;
        }
    }

    // If still too large, use the last compressed version
    return file_exists($destination);
}

// Handle bulk delete (checkboxes in the image grid)
if (isset($_POST['bulk_delete']) && !empty($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $_POST['ids']), function ($v) { return $v > 0; })));
    if ($ids) {
        $id_list = implode(',', $ids);
        $rows = mysqli_query($conn, "SELECT id, image_path FROM gallery WHERE id IN ($id_list)");
        while ($rows && ($r = mysqli_fetch_assoc($rows))) {
            $file = '../' . $r['image_path'];
            if (strpos($r['image_path'], 'uploads/gallery/') === 0 && is_file($file)) {
                unlink($file);
            }
        }
        if (mysqli_query($conn, "DELETE FROM gallery WHERE id IN ($id_list)")) {
            $deleted = mysqli_affected_rows($conn);
            $message = $deleted . ' image' . ($deleted === 1 ? '' : 's') . ' deleted successfully!';
        }
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete image file
    $img_query = mysqli_query($conn, "SELECT image_path FROM gallery WHERE id = $id");
    if ($img_row = mysqli_fetch_assoc($img_query)) {
        if (file_exists('../' . $img_row['image_path'])) {
            unlink('../' . $img_row['image_path']);
        }
    }

    if (mysqli_query($conn, "DELETE FROM gallery WHERE id = $id")) {
        $message = "Image deleted successfully!";
    }
}

// Handle category delete
if (isset($_GET['action']) && $_GET['action'] == 'delete_category' && isset($_GET['cat_id'])) {
    $cat_id = intval($_GET['cat_id']);
    $cat_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slug FROM gallery_categories WHERE id = $cat_id"));

    if ($cat_row) {
        $in_use = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM gallery WHERE category = '" . mysqli_real_escape_string($conn, $cat_row['slug']) . "'"));
        if ($in_use && $in_use['c'] > 0) {
            $message = "Error: Can't delete this category — {$in_use['c']} image(s) are still using it. Move or delete those images first.";
        } elseif (mysqli_query($conn, "DELETE FROM gallery_categories WHERE id = $cat_id")) {
            $message = "Category deleted successfully!";
        }
    }
}

// Handle category add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cat_name'])) {
    $cat_name = trim($_POST['cat_name']);
    $cat_icon = trim($_POST['cat_icon']) ?: 'fa-tag';
    $cat_order = intval($_POST['cat_order']);
    $cat_status = $_POST['cat_status'] === 'inactive' ? 'inactive' : 'active';

    if ($cat_name === '') {
        $message = "Category name is required.";
    } elseif (isset($_POST['cat_id']) && !empty($_POST['cat_id'])) {
        // Edit existing category — slug stays fixed so existing images keep their category
        $cid = intval($_POST['cat_id']);
        $q = "UPDATE gallery_categories SET name='" . mysqli_real_escape_string($conn, $cat_name) . "', icon='" . mysqli_real_escape_string($conn, $cat_icon) . "', display_order=$cat_order, status='$cat_status' WHERE id=$cid";
        $message = mysqli_query($conn, $q) ? "Category updated successfully!" : "Error updating category.";
    } else {
        // New category — derive a stable slug from the name
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', strtolower($cat_name)));
        $slug = trim($slug, '_');
        if ($slug === '') {
            $message = "Please enter a valid category name.";
        } else {
            $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM gallery_categories WHERE slug = '" . mysqli_real_escape_string($conn, $slug) . "'"));
            if ($exists) {
                $message = "Error: A category with a similar name already exists.";
            } else {
                $q = "INSERT INTO gallery_categories (slug, name, icon, display_order, status) VALUES ('" . mysqli_real_escape_string($conn, $slug) . "', '" . mysqli_real_escape_string($conn, $cat_name) . "', '" . mysqli_real_escape_string($conn, $cat_icon) . "', $cat_order, '$cat_status')";
                $message = mysqli_query($conn, $q) ? "Category added successfully!" : "Error adding category.";
            }
        }
    }
}

// Fetch category for editing
$edit_category = null;
if (isset($_GET['edit_cat'])) {
    $edit_cat_id = intval($_GET['edit_cat']);
    $edit_category = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM gallery_categories WHERE id = $edit_cat_id"));
}

// Fetch all categories (for the management table + the image form dropdown)
$categories = [];
$cat_result = mysqli_query($conn, "SELECT * FROM gallery_categories ORDER BY display_order ASC, name ASC");
if ($cat_result) {
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $row;
    }
}

// Compress + move a single uploaded file into uploads/gallery/, returns the relative path or false
function processGalleryUpload($tmp_path, $original_name) {
    $upload_dir = '../uploads/gallery/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }

    $new_filename = time() . '_' . uniqid() . '.jpg'; // Always saved as JPG after compression
    $target_file = $upload_dir . $new_filename;

    if (compressToTargetSize($tmp_path, $target_file, 100)) {
        return 'uploads/gallery/' . $new_filename;
    }
    return false;
}

// Turn "annual_sports-day.jpg" into "Annual Sports Day" for images uploaded without a title
function titleFromFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[_\-]+/', ' ', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return $name !== '' ? ucwords($name) : 'Untitled';
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['cat_name']) && !isset($_POST['bulk_delete'])) {
    $title_input = trim($_POST['title'] ?? '');
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $editing_id = (isset($_POST['gallery_id']) && !empty($_POST['gallery_id'])) ? intval($_POST['gallery_id']) : null;

    if ($editing_id) {
        // Editing one existing item — single optional image replace
        $title_sql = $title_input !== '' ? "title='" . mysqli_real_escape_string($conn, $title_input) . "', " : '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_path = processGalleryUpload($_FILES['image']['tmp_name'], $_FILES['image']['name']);
            if ($image_path) {
                $old_img_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM gallery WHERE id = $editing_id"));
                if ($old_img_row && file_exists('../' . $old_img_row['image_path'])) {
                    unlink('../' . $old_img_row['image_path']);
                }
                $query = "UPDATE gallery SET {$title_sql}image_path='$image_path', category='$category', display_order=$display_order, status='$status' WHERE id=$editing_id";
                $message = mysqli_query($conn, $query) ? "Image updated successfully!" : "Error saving image.";
            } else {
                $message = "Invalid file format. Only JPG, PNG, GIF, WEBP allowed.";
            }
        } else {
            $query = "UPDATE gallery SET {$title_sql}category='$category', display_order=$display_order, status='$status' WHERE id=$editing_id";
            $message = mysqli_query($conn, $query) ? "Gallery item updated successfully!" : "Error updating gallery item.";
        }
    } else {
        // Adding new image(s) — supports selecting multiple files at once
        $files = $_FILES['images'] ?? null;

        if (!$files || empty($files['name'][0])) {
            $message = "Please select at least one image to upload.";
        } else {
            $count = count($files['name']);
            $saved = 0;
            $failed = 0;

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $failed++;
                    continue;
                }

                $image_path = processGalleryUpload($files['tmp_name'][$i], $files['name'][$i]);
                if (!$image_path) {
                    $failed++;
                    continue;
                }

                if ($title_input !== '') {
                    $item_title = $count > 1 ? $title_input . ' ' . ($i + 1) : $title_input;
                } else {
                    $item_title = titleFromFilename($files['name'][$i]);
                }
                $item_title = mysqli_real_escape_string($conn, $item_title);

                $query = "INSERT INTO gallery (title, image_path, category, display_order, status) VALUES ('$item_title', '$image_path', '$category', $display_order, '$status')";
                if (mysqli_query($conn, $query)) {
                    $saved++;
                } else {
                    $failed++;
                }
            }

            if ($saved > 0) {
                $message = "$saved image" . ($saved > 1 ? 's' : '') . " uploaded successfully!" . ($failed > 0 ? " ($failed failed — invalid format or upload error.)" : '');
            } else {
                $message = "Error: no images were uploaded. Check the file formats (JPG, PNG, GIF, WEBP only).";
            }
        }
    }
}

// Fetch gallery item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM gallery WHERE id = $edit_id");
    $edit_item = mysqli_fetch_assoc($result);
}

// Category filter for the image grid (?cat=slug); empty = all categories
$filter_cat = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
$cat_counts = [];
$total_images = 0;
$cc = mysqli_query($conn, "SELECT category, COUNT(*) AS n FROM gallery GROUP BY category");
while ($cc && ($r = mysqli_fetch_assoc($cc))) { $cat_counts[$r['category']] = (int) $r['n']; $total_images += (int) $r['n']; }
if ($filter_cat !== '' && !isset($cat_counts[$filter_cat])) {
    // keep the filter if it is a real (but empty) category, otherwise drop it
    $known = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM gallery_categories WHERE slug = '" . mysqli_real_escape_string($conn, $filter_cat) . "'"));
    if (!$known) $filter_cat = '';
}
$where = $filter_cat !== '' ? "WHERE category = '" . mysqli_real_escape_string($conn, $filter_cat) . "'" : '';

// Fetch gallery items (oldest first within the same order, so the original upload is kept as "original")
$gallery_items = [];
$gq = mysqli_query($conn, "SELECT * FROM gallery $where ORDER BY display_order ASC, id ASC");
while ($gq && ($r = mysqli_fetch_assoc($gq))) { $gallery_items[] = $r; }

// Mark exact duplicates: same file content in the same category as an earlier image
$seen_hash = [];
$duplicate_count = 0;
foreach ($gallery_items as &$gi) {
    $gi['duplicate_of'] = null;
    $file = '../' . $gi['image_path'];
    if (is_file($file)) {
        $key = $gi['category'] . '|' . md5_file($file);
        if (isset($seen_hash[$key])) { $gi['duplicate_of'] = $seen_hash[$key]; $duplicate_count++; }
        else { $seen_hash[$key] = (int) $gi['id']; }
    }
}
unset($gi);
// grid order as before: display order, then newest first
usort($gallery_items, function ($a, $b) {
    return [(int) $a['display_order'], -(int) $a['id']] <=> [(int) $b['display_order'], -(int) $b['id']];
});
$filter_qs = $filter_cat !== '' ? '&cat=' . urlencode($filter_cat) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .gallery-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .gallery-item-info {
            padding: 15px;
        }
        .gallery-item-actions {
            padding: 10px 15px;
            background: var(--bg-light);
            display: flex;
            gap: 10px;
        }
        .gallery-item { position: relative; border: 2px solid transparent; }
        .gallery-item.is-selected { border-color: var(--danger-color); }
        .gallery-item.is-duplicate img { opacity: 0.85; }
        .gal-select {
            position: absolute; top: 10px; left: 10px; z-index: 2;
            width: 34px; height: 34px; border-radius: 8px;
            background: rgba(255,255,255,0.95); box-shadow: 0 2px 8px rgba(11,31,66,0.25);
            display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .gal-select input { width: 18px; height: 18px; cursor: pointer; accent-color: var(--danger-color); }
        .gal-dup-badge {
            position: absolute; top: 12px; right: 10px; z-index: 2;
            background: #FEF6E7; color: #975A16; border: 1px solid #F6D9A8;
            font-size: 11.5px; font-weight: 600; padding: 3px 9px; border-radius: 999px;
        }
        .gal-id { color: var(--text-light); font-weight: 500; font-size: 12px; }
        .gal-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .gal-head-count { color: var(--text-light); font-size: 14px; }
        .gal-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
        .gal-filter {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 14px; border-radius: 999px;
            border: 1px solid var(--border-color); background: #fff;
            color: var(--text-color); font-size: 13.5px; font-weight: 500; text-decoration: none;
            transition: border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease;
        }
        .gal-filter i { color: var(--accent-color); font-size: 12px; }
        .gal-filter span { background: #F1F5F9; color: var(--text-light); border-radius: 999px; padding: 0 8px; font-size: 12px; font-weight: 600; }
        .gal-filter:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .gal-filter.active { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
        .gal-filter.active i { color: var(--accent-bright); }
        .gal-filter.active span { background: rgba(255,255,255,0.18); color: #fff; }
        .gal-toolbar {
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            padding: 10px 12px; margin-bottom: 12px;
            background: #F4F7FB; border: 1px solid var(--border-color); border-radius: 10px;
        }
        .gal-check-all { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-right: auto; }
        .gal-check-all input { width: 18px; height: 18px; accent-color: var(--primary-color); }
        .gal-bulk-delete:disabled { opacity: 0.45; cursor: not-allowed; }
        .gal-dup-note { font-size: 13.5px; color: #975A16; background: #FEF6E7; border: 1px solid #F6D9A8; border-radius: 8px; padding: 9px 12px; margin: 0 0 14px; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-images"></i> Manage Gallery</h1>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_item ? 'Edit Gallery Item' : 'Add New Image(s)'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_item): ?>
                    <input type="hidden" name="gallery_id" value="<?php echo $edit_item['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_gallery_php_1">Image Title<?php echo $edit_item ? '' : ' (optional when uploading multiple images)'; ?></label>
                    <input id="auto_AdminCP_manage_gallery_php_1" type="text" name="title" value="<?php echo $edit_item ? htmlspecialchars($edit_item['title']) : ''; ?>" placeholder="e.g., Annual Sports Day 2024">
                    <?php if (!$edit_item): ?>
                        <small style="color: var(--text-light); font-size: 12px;">Left blank: each photo is titled from its filename. Filled in with multiple photos selected: used as "Title 1", "Title 2", etc.</small>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_gallery_php_2">Category *</label>
                        <select id="auto_AdminCP_manage_gallery_php_2" name="category" required>
                            <?php foreach ($categories as $c): if ($c['status'] !== 'active' && !($edit_item && $edit_item['category'] === $c['slug'])) continue; ?>
                            <option value="<?php echo htmlspecialchars($c['slug']); ?>" <?php echo ($edit_item && $edit_item['category'] == $c['slug']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_manage_gallery_php_3">Display Order</label>
                        <input id="auto_AdminCP_manage_gallery_php_3" type="number" name="display_order" value="<?php echo $edit_item ? $edit_item['display_order'] : '0'; ?>" placeholder="0">
                        <small style="color: var(--text-light); font-size: 12px;">Lower numbers appear first</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_manage_gallery_php_4">Status</label>
                        <select id="auto_AdminCP_manage_gallery_php_4" name="status">
                            <option value="active" <?php echo ($edit_item && $edit_item['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_item && $edit_item['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <?php if ($edit_item): ?>
                        <label for="auto_AdminCP_manage_gallery_php_5">Upload Image (optional - leave empty to keep current)</label>
                        <input id="auto_AdminCP_manage_gallery_php_5" type="file" name="image" accept="image/*">
                        <?php if (!empty($edit_item['image_path'])): ?>
                            <div style="margin-top: 15px;">
                                <img src="../<?php echo htmlspecialchars($edit_item['image_path']); ?>" alt="Current Image" style="max-width: 300px; border-radius: 8px; box-shadow: var(--shadow);">
                                <p style="margin-top: 5px; font-size: 13px; color: var(--text-light);">Current image</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <label for="auto_AdminCP_manage_gallery_php_6">Upload Image(s) *</label>
                        <input id="auto_AdminCP_manage_gallery_php_6" type="file" name="images[]" accept="image/*" multiple required>
                        <small style="color: var(--text-light); font-size: 12px; display: block; margin-top: 5px;">Select multiple files to upload them all at once — they'll share the same category, order, and status set above.</small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_item ? 'Update Image' : 'Add Image(s)'; ?>
                </button>
                <?php if ($edit_item): ?>
                    <a href="manage_gallery.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Manage Categories -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-tags"></i> <?php echo $edit_category ? 'Edit Category' : 'Gallery Categories'; ?></h2>

            <form method="POST" style="margin-bottom: 24px;"><?php echo csrf_field(); ?>
                <?php if ($edit_category): ?>
                    <input type="hidden" name="cat_id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>
                <div style="display: grid; grid-template-columns: 1.4fr 1fr 0.7fr 0.7fr auto; gap: 16px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="auto_AdminCP_manage_gallery_php_7">Category Name *</label>
                        <input id="auto_AdminCP_manage_gallery_php_7" type="text" name="cat_name" required value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" placeholder="e.g., Convocation">
                        <?php if ($edit_category): ?>
                            <small style="color: var(--text-light); font-size: 12px;">Slug: <?php echo htmlspecialchars($edit_category['slug']); ?> (fixed)</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="auto_AdminCP_manage_gallery_php_8">Icon (Font Awesome class)</label>
                        <input id="auto_AdminCP_manage_gallery_php_8" type="text" name="cat_icon" value="<?php echo $edit_category ? htmlspecialchars($edit_category['icon']) : 'fa-tag'; ?>" placeholder="fa-tag">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="auto_AdminCP_manage_gallery_php_9">Order</label>
                        <input id="auto_AdminCP_manage_gallery_php_9" type="number" name="cat_order" value="<?php echo $edit_category ? $edit_category['display_order'] : count($categories); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="auto_AdminCP_manage_gallery_php_10">Status</label>
                        <select id="auto_AdminCP_manage_gallery_php_10" name="cat_status">
                            <option value="active" <?php echo (!$edit_category || $edit_category['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_category && $edit_category['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_category ? 'Update' : 'Add'; ?></button>
                        <?php if ($edit_category): ?>
                            <a href="manage_gallery.php" class="btn btn-primary" style="background: var(--text-light);">Cancel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div style="overflow-x: auto;">
                <table class="data-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 10px;">Icon</th>
                            <th style="padding: 10px;">Name</th>
                            <th style="padding: 10px;">Slug</th>
                            <th style="padding: 10px;">Order</th>
                            <th style="padding: 10px;">Status</th>
                            <th style="padding: 10px;">Photos</th>
                            <th style="padding: 10px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c):
                            $photo_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM gallery WHERE category = '" . mysqli_real_escape_string($conn, $c['slug']) . "'"))['c'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px;"><i class="fas <?php echo htmlspecialchars($c['icon']); ?>" style="color: var(--primary-color);"></i></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($c['name']); ?></td>
                            <td style="padding: 10px; color: var(--text-light); font-size: 13px;"><?php echo htmlspecialchars($c['slug']); ?></td>
                            <td style="padding: 10px;"><?php echo $c['display_order']; ?></td>
                            <td style="padding: 10px;"><span style="background: <?php echo $c['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px;"><?php echo ucfirst($c['status']); ?></span></td>
                            <td style="padding: 10px;"><?php echo $photo_count; ?></td>
                            <td style="padding: 10px;">
                                <a href="?edit_cat=<?php echo $c['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?action=delete_category&cat_id=<?php echo $c['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete category &quot;<?php echo htmlspecialchars(addslashes($c['name'])); ?>&quot;?')"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                        <tr><td colspan="7" style="padding: 20px; text-align: center; color: var(--text-light);">No categories yet — add one above.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="card" id="images">
            <div class="gal-head">
                <h2 style="color: var(--primary-color); margin: 0;">Gallery Images</h2>
                <span class="gal-head-count"><?php echo count($gallery_items); ?> shown<?php echo $filter_cat !== '' ? ' of ' . $total_images : ''; ?></span>
            </div>

            <!-- Category filter -->
            <nav class="gal-filters" aria-label="Filter images by category">
                <a href="manage_gallery.php#images" class="gal-filter<?php echo $filter_cat === '' ? ' active' : ''; ?>">All <span><?php echo $total_images; ?></span></a>
                <?php foreach ($categories as $c): ?>
                <a href="manage_gallery.php?cat=<?php echo urlencode($c['slug']); ?>#images" class="gal-filter<?php echo $filter_cat === $c['slug'] ? ' active' : ''; ?>">
                    <i class="fas <?php echo htmlspecialchars($c['icon'] ?: 'fa-tag'); ?>"></i>
                    <?php echo htmlspecialchars($c['name']); ?> <span><?php echo $cat_counts[$c['slug']] ?? 0; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>

            <?php if (!empty($gallery_items)): ?>
            <form method="POST" action="manage_gallery.php<?php echo $filter_cat !== '' ? '?cat=' . urlencode($filter_cat) : ''; ?>#images" id="bulkForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="bulk_delete" value="1">

                <div class="gal-toolbar">
                    <label class="gal-check-all">
                        <input type="checkbox" id="checkAll"> <span>Select all shown</span>
                    </label>
                    <?php if ($duplicate_count > 0): ?>
                    <button type="button" class="action-btn btn-edit" id="selectDuplicates">
                        <i class="fas fa-clone"></i> Select duplicates (<?php echo $duplicate_count; ?>)
                    </button>
                    <?php endif; ?>
                    <button type="submit" class="action-btn btn-delete gal-bulk-delete" id="bulkDeleteBtn" disabled>
                        <i class="fas fa-trash"></i> Delete selected (<span id="selCount">0</span>)
                    </button>
                </div>
                <?php if ($duplicate_count > 0): ?>
                <p class="gal-dup-note"><i class="fas fa-circle-info"></i>
                    <?php echo $duplicate_count; ?> image<?php echo $duplicate_count === 1 ? ' is an exact copy' : 's are exact copies'; ?> of another image in the same category (marked <strong>Duplicate</strong>). Deleting them keeps the original.
                </p>
                <?php endif; ?>

                <div class="gallery-grid">
                    <?php
                    $cat_name_by_slug = [];
                    foreach ($categories as $c) { $cat_name_by_slug[$c['slug']] = $c['name']; }
                    foreach ($gallery_items as $item):
                        $is_dup = $item['duplicate_of'] !== null;
                    ?>
                    <div class="gallery-item<?php echo $is_dup ? ' is-duplicate' : ''; ?>">
                        <label class="gal-select" title="Select image">
                            <input type="checkbox" name="ids[]" value="<?php echo (int) $item['id']; ?>" class="gal-cb"<?php echo $is_dup ? ' data-duplicate="1"' : ''; ?> aria-label="Select <?php echo htmlspecialchars($item['title']); ?>">
                        </label>
                        <?php if ($is_dup): ?><span class="gal-dup-badge">Duplicate of #<?php echo (int) $item['duplicate_of']; ?></span><?php endif; ?>
                        <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                        <div class="gallery-item-info">
                            <h4 style="margin: 0 0 5px 0; color: var(--primary-color);"><?php echo htmlspecialchars($item['title']); ?> <small class="gal-id">#<?php echo (int) $item['id']; ?></small></h4>
                            <p style="margin: 0; font-size: 13px; color: var(--text-light);"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat_name_by_slug[$item['category']] ?? ucfirst($item['category'])); ?></p>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-light);"><i class="fas fa-sort"></i> Order: <?php echo (int) $item['display_order']; ?></p>
                            <span class="status-badge" style="background: <?php echo $item['status'] == 'active' ? '#F0FDF4' : '#FEF2F2'; ?>; color: <?php echo $item['status'] == 'active' ? '#166534' : '#991B1B'; ?>; margin-top: 6px;"><?php echo ucfirst(htmlspecialchars($item['status'])); ?></span>
                        </div>
                        <div class="gallery-item-actions">
                            <a href="?edit=<?php echo (int) $item['id']; ?>" class="action-btn btn-edit" style="flex: 1;"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?action=delete&id=<?php echo (int) $item['id'] . $filter_qs; ?>" class="action-btn btn-delete" style="flex: 1;" onclick="return confirm('Delete this image?')"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </form>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                    <i class="fas fa-images" style="font-size: 64px; margin-bottom: 18px; opacity: 0.3;"></i>
                    <h3 style="margin-bottom: 10px;"><?php echo $filter_cat !== '' ? 'No images in this category' : 'No Images Yet'; ?></h3>
                    <p><?php echo $filter_cat !== '' ? 'Pick another category above, or upload images into this one.' : 'Upload your first image to get started!'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
<script>
(function () {
    var form = document.getElementById('bulkForm');
    if (!form) return;
    var boxes = form.querySelectorAll('.gal-cb'), all = document.getElementById('checkAll');
    var btn = document.getElementById('bulkDeleteBtn'), count = document.getElementById('selCount');
    var dupBtn = document.getElementById('selectDuplicates');
    function update() {
        var n = form.querySelectorAll('.gal-cb:checked').length;
        count.textContent = n; btn.disabled = n === 0;
        all.checked = n > 0 && n === boxes.length; all.indeterminate = n > 0 && n < boxes.length;
        boxes.forEach(function (b) { b.closest('.gallery-item').classList.toggle('is-selected', b.checked); });
    }
    boxes.forEach(function (b) { b.addEventListener('change', update); });
    all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); update(); });
    if (dupBtn) dupBtn.addEventListener('click', function () {
        boxes.forEach(function (b) { b.checked = b.dataset.duplicate === '1'; }); update();
    });
    form.addEventListener('submit', function (e) {
        var n = form.querySelectorAll('.gal-cb:checked').length;
        if (!n || !confirm('Delete ' + n + ' selected image' + (n === 1 ? '' : 's') + '? This cannot be undone.')) e.preventDefault();
    });
    update();
})();
</script>
</body>
</html>
