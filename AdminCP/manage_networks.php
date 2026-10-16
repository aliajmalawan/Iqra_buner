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

$categories = [
    'academic'              => 'Academic Partner',
    'affiliated_institution' => 'Affiliated Institution',
    'industry_professional' => 'Industry / Professional Partner',
];

// Image compression function (same approach used by Manage Gallery / Clinical Training)
function compressImage($source, $destination, $quality = 80) {
    // Without PHP's GD extension images can't be re-encoded: keep the original
    // upload instead of crashing (enable extension=gd in php.ini to compress).
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
        return copy($source, $destination);
    }
    $info = getimagesize($source);
    if ($info === false) return false;
    if (!$info) return false;
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($source); break;
        case 'image/png':  $image = imagecreatefrompng($source);  break;
        case 'image/gif':  $image = imagecreatefromgif($source);  break;
        case 'image/webp': $image = imagecreatefromwebp($source); break;
        default: return false;
    }
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return file_exists($destination);
}

// Handle delete (also removes the uploaded logo, if any)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT logo FROM network_partners WHERE id = $id"));
    if ($row && !empty($row['logo']) && file_exists('../' . $row['logo'])) {
        unlink('../' . $row['logo']);
    }
    if (mysqli_query($conn, "DELETE FROM network_partners WHERE id = $id")) {
        $message = "Partner removed successfully!";
    } else {
        $message = "Error removing partner.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = array_key_exists($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'academic';
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $location = mysqli_real_escape_string($conn, trim($_POST['location'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $website = mysqli_real_escape_string($conn, trim($_POST['website'] ?? ''));
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($name === '') {
        $message = "Partner / institution name is required.";
    } else {
        // Logo upload (optional)
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/networks/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'network_' . time() . '_' . uniqid() . '.jpg';
                $target = $upload_dir . $filename;
                if (compressImage($_FILES['logo']['tmp_name'], $target, 82)) {
                    $logo_path = 'uploads/networks/' . $filename;

                    if (!empty($_POST['partner_id'])) {
                        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT logo FROM network_partners WHERE id = " . intval($_POST['partner_id'])));
                        if ($old && !empty($old['logo']) && file_exists('../' . $old['logo'])) {
                            unlink('../' . $old['logo']);
                        }
                    }
                }
            }
        }
        $logo_sql = $logo_path !== null ? ", logo='" . mysqli_real_escape_string($conn, $logo_path) . "'" : '';

        if (!empty($_POST['partner_id'])) {
            $id = intval($_POST['partner_id']);
            $q = "UPDATE network_partners SET category='$category', name='$name', location='$location', description='$description', website='$website', display_order=$display_order, status='$status'$logo_sql WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Partner updated successfully!" : "Error updating partner.";
        } else {
            $logo_col_sql = $logo_path !== null ? "'" . mysqli_real_escape_string($conn, $logo_path) . "'" : 'NULL';
            $q = "INSERT INTO network_partners (category, name, location, description, website, display_order, status, logo) VALUES ('$category', '$name', '$location', '$description', '$website', $display_order, '$status', $logo_col_sql)";
            if (mysqli_query($conn, $q)) {
                $new_id = mysqli_insert_id($conn);
                header('Location: manage_networks.php?edit=' . $new_id . '&msg=' . urlencode('Partner added successfully!'));
                exit;
            } else {
                $message = "Error adding partner.";
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Item being edited
$edit_partner = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_partner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM network_partners WHERE id = $edit_id"));
}

$partners_result = mysqli_query($conn, "SELECT * FROM network_partners ORDER BY category ASC, display_order ASC, id DESC");

$show_form = $edit_partner || $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Networks - Admin Panel</title>
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
        .category-pill { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#E9F6FA; color:var(--primary-color); }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-diagram-project"></i> Our Networks</h1>
            <button type="button" onclick="togglePartnerForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Partner</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Only add institutions/organizations you have a real, verified affiliation with — this list is shown publicly on the "Our Networks" page. Partner Hospitals &amp; Clinical Training Partners are managed separately under <a href="manage_clinical_training.php" style="color:var(--primary-color);font-weight:700;">Clinical Training</a> and appear automatically on the Our Networks page.
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="partner-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_partner ? 'Edit Partner' : 'Add Partner'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_partner): ?><input type="hidden" name="partner_id" value="<?php echo $edit_partner['id']; ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_networks_php_1">Category *</label>
                        <select id="auto_AdminCP_manage_networks_php_1" name="category">
                            <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($edit_partner && $edit_partner['category'] === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_networks_php_2">Name *</label>
                        <input id="auto_AdminCP_manage_networks_php_2" type="text" name="name" required value="<?php echo $edit_partner ? htmlspecialchars($edit_partner['name']) : ''; ?>" placeholder="e.g., University of Health Sciences Lahore">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_networks_php_3">Location</label>
                        <input id="auto_AdminCP_manage_networks_php_3" type="text" name="location" value="<?php echo $edit_partner ? htmlspecialchars($edit_partner['location']) : ''; ?>" placeholder="e.g., Lahore, Pakistan">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_networks_php_4">Website (optional)</label>
                        <input id="auto_AdminCP_manage_networks_php_4" type="text" name="website" value="<?php echo $edit_partner ? htmlspecialchars($edit_partner['website']) : ''; ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_networks_php_5">Description</label>
                    <textarea id="auto_AdminCP_manage_networks_php_5" name="description" rows="2" placeholder="Brief note about the nature of this affiliation/partnership"><?php echo $edit_partner ? htmlspecialchars($edit_partner['description']) : ''; ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_networks_php_6">Display Order</label>
                        <input id="auto_AdminCP_manage_networks_php_6" type="number" name="display_order" value="<?php echo $edit_partner ? $edit_partner['display_order'] : 0; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_networks_php_7">Status</label>
                        <select id="auto_AdminCP_manage_networks_php_7" name="status">
                            <option value="active" <?php echo (!$edit_partner || $edit_partner['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_partner && $edit_partner['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_networks_php_8">Logo (optional)</label>
                    <input id="auto_AdminCP_manage_networks_php_8" type="file" name="logo" accept="image/*">
                    <?php if ($edit_partner && !empty($edit_partner['logo'])): ?>
                        <img src="../<?php echo htmlspecialchars($edit_partner['logo']); ?>" style="height:60px;border-radius:8px;margin-top:8px;display:block;">
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_partner ? 'Save Changes' : 'Add Partner'; ?></button>
                <?php if ($edit_partner): ?>
                    <a href="manage_networks.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="togglePartnerForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function togglePartnerForm() {
                var wrap = document.getElementById('partner-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <!-- List -->
        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Category</th><th>Name</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($partners_result && mysqli_num_rows($partners_result) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($partners_result)): ?>
                        <tr>
                            <td><?php echo $p['display_order']; ?></td>
                            <td><span class="category-pill"><?php echo htmlspecialchars($categories[$p['category']] ?? $p['category']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['location'] ?: '—'); ?></td>
                            <td><span class="status-pill" style="background: <?php echo $p['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($p['status']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $p['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $p['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove &quot;<?php echo htmlspecialchars(addslashes($p['name'])); ?>&quot; from Our Networks?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-diagram-project" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No network partners added yet. Add your first verified academic partner, affiliated institution, or industry partner above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
