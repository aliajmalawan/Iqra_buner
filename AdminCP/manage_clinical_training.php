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

// Image compression function (same approach used by Manage Gallery)
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
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT logo FROM clinical_partners WHERE id = $id"));
    if ($row && !empty($row['logo']) && file_exists('../' . $row['logo'])) {
        unlink('../' . $row['logo']);
    }
    if (mysqli_query($conn, "DELETE FROM clinical_partners WHERE id = $id")) {
        $message = "Clinical partner removed successfully!";
    } else {
        $message = "Error removing clinical partner.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hospital_name = mysqli_real_escape_string($conn, trim($_POST['hospital_name'] ?? ''));
    $location = mysqli_real_escape_string($conn, trim($_POST['location'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $facilities = mysqli_real_escape_string($conn, trim($_POST['facilities'] ?? ''));
    $programs = mysqli_real_escape_string($conn, trim($_POST['programs'] ?? ''));
    $website = mysqli_real_escape_string($conn, trim($_POST['website'] ?? ''));
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($hospital_name === '') {
        $message = "Hospital / partner name is required.";
    } else {
        // Logo upload (optional)
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/clinical/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'partner_' . time() . '_' . uniqid() . '.jpg';
                $target = $upload_dir . $filename;
                if (compressImage($_FILES['logo']['tmp_name'], $target, 82)) {
                    $logo_path = 'uploads/clinical/' . $filename;

                    if (!empty($_POST['partner_id'])) {
                        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT logo FROM clinical_partners WHERE id = " . intval($_POST['partner_id'])));
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
            $q = "UPDATE clinical_partners SET hospital_name='$hospital_name', location='$location', description='$description', facilities='$facilities', programs='$programs', website='$website', display_order=$display_order, status='$status'$logo_sql WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Clinical partner updated successfully!" : "Error updating clinical partner.";
        } else {
            $logo_col_sql = $logo_path !== null ? "'" . mysqli_real_escape_string($conn, $logo_path) . "'" : 'NULL';
            $q = "INSERT INTO clinical_partners (hospital_name, location, description, facilities, programs, website, display_order, status, logo) VALUES ('$hospital_name', '$location', '$description', '$facilities', '$programs', '$website', $display_order, '$status', $logo_col_sql)";
            if (mysqli_query($conn, $q)) {
                $new_id = mysqli_insert_id($conn);
                header('Location: manage_clinical_training.php?edit=' . $new_id . '&msg=' . urlencode('Clinical partner added successfully!'));
                exit;
            } else {
                $message = "Error adding clinical partner.";
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
    $edit_partner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM clinical_partners WHERE id = $edit_id"));
}

$partners_result = mysqli_query($conn, "SELECT * FROM clinical_partners ORDER BY display_order ASC, id DESC");

$show_form = $edit_partner || $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Training / Hospital Network - Admin Panel</title>
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
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-hospital"></i> Clinical Training / Hospital Network</h1>
            <button type="button" onclick="togglePartnerForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Partner</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Only add hospitals or clinical facilities you have a real, verified training partnership with — this list is shown publicly on the website as your official clinical training network.
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="partner-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_partner ? 'Edit Clinical Partner' : 'Add Clinical Partner'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_partner): ?><input type="hidden" name="partner_id" value="<?php echo $edit_partner['id']; ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_1">Hospital / Facility Name *</label>
                        <input id="auto_AdminCP_manage_clinical_training_php_1" type="text" name="hospital_name" required value="<?php echo $edit_partner ? htmlspecialchars($edit_partner['hospital_name']) : ''; ?>" placeholder="e.g., DHQ Hospital Arifwala">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_2">Location</label>
                        <input id="auto_AdminCP_manage_clinical_training_php_2" type="text" name="location" value="<?php echo $edit_partner ? htmlspecialchars($edit_partner['location']) : ''; ?>" placeholder="e.g., Arifwala, Pakpattan Road">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_clinical_training_php_3">Description</label>
                    <textarea id="auto_AdminCP_manage_clinical_training_php_3" name="description" rows="2" placeholder="Brief note about the partnership / what students do here"><?php echo $edit_partner ? htmlspecialchars($edit_partner['description']) : ''; ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_4">Training Facilities / Services (one per line)</label>
                        <textarea id="auto_AdminCP_manage_clinical_training_php_4" name="facilities" rows="4" placeholder="e.g.&#10;Emergency Department&#10;OPD&#10;Operation Theater"><?php echo $edit_partner ? htmlspecialchars($edit_partner['facilities']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_5">Relevant Programs / Departments (one per line)</label>
                        <textarea id="auto_AdminCP_manage_clinical_training_php_5" name="programs" rows="4" placeholder="e.g.&#10;Pharm-D&#10;DPT&#10;BS MLT"><?php echo $edit_partner ? htmlspecialchars($edit_partner['programs']) : ''; ?></textarea>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_6">Website (optional)</label>
                        <input id="auto_AdminCP_manage_clinical_training_php_6" type="text" name="website" value="<?php echo $edit_partner ? htmlspecialchars($edit_partner['website']) : ''; ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_7">Display Order</label>
                        <input id="auto_AdminCP_manage_clinical_training_php_7" type="number" name="display_order" value="<?php echo $edit_partner ? $edit_partner['display_order'] : 0; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_clinical_training_php_8">Status</label>
                        <select id="auto_AdminCP_manage_clinical_training_php_8" name="status">
                            <option value="active" <?php echo (!$edit_partner || $edit_partner['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_partner && $edit_partner['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_clinical_training_php_9">Logo / Photo (optional)</label>
                    <input id="auto_AdminCP_manage_clinical_training_php_9" type="file" name="logo" accept="image/*">
                    <?php if ($edit_partner && !empty($edit_partner['logo'])): ?>
                        <img src="../<?php echo htmlspecialchars($edit_partner['logo']); ?>" style="height:60px;border-radius:8px;margin-top:8px;display:block;">
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_partner ? 'Save Changes' : 'Add Partner'; ?></button>
                <?php if ($edit_partner): ?>
                    <a href="manage_clinical_training.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
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
                <thead><tr><th>Order</th><th>Hospital / Facility</th><th>Location</th><th>Programs</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($partners_result && mysqli_num_rows($partners_result) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($partners_result)): ?>
                        <tr>
                            <td><?php echo $p['display_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['hospital_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['location'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($p['programs'] ? implode(', ', array_slice(array_filter(array_map('trim', explode("\n", $p['programs']))), 0, 3)) : '—'); ?></td>
                            <td><span class="status-pill" style="background: <?php echo $p['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($p['status']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $p['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $p['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove &quot;<?php echo htmlspecialchars(addslashes($p['hospital_name'])); ?>&quot; from the clinical training network?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-hospital" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No clinical partners added yet. Add your first verified hospital / training partner above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
