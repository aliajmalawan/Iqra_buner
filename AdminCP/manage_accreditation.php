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

$statuses = [
    'accredited'  => 'Accredited',
    'provisional' => 'Provisional',
    'pending'     => 'Pending / Under Review',
    'expired'     => 'Expired',
];

// Image compression function (same approach used across the admin panel)
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
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT logo FROM accreditations WHERE id = $id"));
    if ($row && !empty($row['logo']) && file_exists('../' . $row['logo'])) {
        unlink('../' . $row['logo']);
    }
    if (mysqli_query($conn, "DELETE FROM accreditations WHERE id = $id")) {
        $message = "Record removed successfully!";
    } else {
        $message = "Error removing record.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $organization_name = mysqli_real_escape_string($conn, trim($_POST['organization_name'] ?? ''));
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $program = mysqli_real_escape_string($conn, trim($_POST['program'] ?? ''));
    $status = array_key_exists($_POST['status'] ?? '', $statuses) ? $_POST['status'] : 'pending';
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $website = mysqli_real_escape_string($conn, trim($_POST['website'] ?? ''));
    $valid_from = trim($_POST['valid_from'] ?? '');
    $valid_until = trim($_POST['valid_until'] ?? '');
    $valid_from_sql = $valid_from !== '' ? "'" . mysqli_real_escape_string($conn, $valid_from) . "'" : 'NULL';
    $valid_until_sql = $valid_until !== '' ? "'" . mysqli_real_escape_string($conn, $valid_until) . "'" : 'NULL';
    $display_order = intval($_POST['display_order'] ?? 0);
    $visibility = ($_POST['visibility'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($organization_name === '') {
        $message = "Organization name is required.";
    } else {
        // Logo upload (optional)
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/accreditation/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'accred_' . time() . '_' . uniqid() . '.jpg';
                $target = $upload_dir . $filename;
                if (compressImage($_FILES['logo']['tmp_name'], $target, 82)) {
                    $logo_path = 'uploads/accreditation/' . $filename;

                    if (!empty($_POST['record_id'])) {
                        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT logo FROM accreditations WHERE id = " . intval($_POST['record_id'])));
                        if ($old && !empty($old['logo']) && file_exists('../' . $old['logo'])) {
                            unlink('../' . $old['logo']);
                        }
                    }
                }
            }
        }
        $logo_sql = $logo_path !== null ? ", logo='" . mysqli_real_escape_string($conn, $logo_path) . "'" : '';

        if (!empty($_POST['record_id'])) {
            $id = intval($_POST['record_id']);
            $q = "UPDATE accreditations SET organization_name='$organization_name', title='$title', program='$program', status='$status', description='$description', website='$website', valid_from=$valid_from_sql, valid_until=$valid_until_sql, display_order=$display_order, visibility='$visibility'$logo_sql WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Record updated successfully!" : "Error updating record.";
        } else {
            $logo_col_sql = $logo_path !== null ? "'" . mysqli_real_escape_string($conn, $logo_path) . "'" : 'NULL';
            $q = "INSERT INTO accreditations (organization_name, title, program, status, description, website, valid_from, valid_until, display_order, visibility, logo) VALUES ('$organization_name', '$title', '$program', '$status', '$description', '$website', $valid_from_sql, $valid_until_sql, $display_order, '$visibility', $logo_col_sql)";
            if (mysqli_query($conn, $q)) {
                $new_id = mysqli_insert_id($conn);
                header('Location: manage_accreditation.php?edit=' . $new_id . '&msg=' . urlencode('Record added successfully!'));
                exit;
            } else {
                $message = "Error adding record.";
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Item being edited
$edit_record = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_record = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM accreditations WHERE id = $edit_id"));
}

$records_result = mysqli_query($conn, "SELECT * FROM accreditations ORDER BY display_order ASC, id DESC");

$show_form = $edit_record || $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliations & Accreditation - Admin Panel</title>
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
        .accred-status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-certificate"></i> Affiliations &amp; Accreditation</h1>
            <button type="button" onclick="toggleAccredForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Record</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Only add real, verifiable accreditation or affiliation records here — never mark something "Accredited" unless it genuinely is. Use "Pending / Under Review" for applications still in progress. This list is shown publicly on the "Affiliations &amp; Accreditation" page.
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="accred-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_record ? 'Edit Record' : 'Add Record'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_record): ?><input type="hidden" name="record_id" value="<?php echo $edit_record['id']; ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_1">Organization Name *</label>
                        <input id="auto_AdminCP_manage_accreditation_php_1" type="text" name="organization_name" required value="<?php echo $edit_record ? htmlspecialchars($edit_record['organization_name']) : ''; ?>" placeholder="e.g., Pakistan Nursing Council">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_2">Accreditation / Affiliation Title</label>
                        <input id="auto_AdminCP_manage_accreditation_php_2" type="text" name="title" value="<?php echo $edit_record ? htmlspecialchars($edit_record['title']) : ''; ?>" placeholder="e.g., Institutional Accreditation">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_3">Program (optional — leave blank if institution-wide)</label>
                        <input id="auto_AdminCP_manage_accreditation_php_3" type="text" name="program" value="<?php echo $edit_record ? htmlspecialchars($edit_record['program']) : ''; ?>" placeholder="e.g., BS Nursing">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_4">Status *</label>
                        <select id="auto_AdminCP_manage_accreditation_php_4" name="status">
                            <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($edit_record && $edit_record['status'] === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_accreditation_php_5">Description</label>
                    <textarea id="auto_AdminCP_manage_accreditation_php_5" name="description" rows="2" placeholder="Brief note about the scope of this accreditation/affiliation"><?php echo $edit_record ? htmlspecialchars($edit_record['description']) : ''; ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_6">Valid From (optional)</label>
                        <input id="auto_AdminCP_manage_accreditation_php_6" type="date" name="valid_from" value="<?php echo $edit_record && $edit_record['valid_from'] ? $edit_record['valid_from'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_7">Valid Until (optional)</label>
                        <input id="auto_AdminCP_manage_accreditation_php_7" type="date" name="valid_until" value="<?php echo $edit_record && $edit_record['valid_until'] ? $edit_record['valid_until'] : ''; ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_8">Website / Verification Link (optional)</label>
                        <input id="auto_AdminCP_manage_accreditation_php_8" type="text" name="website" value="<?php echo $edit_record ? htmlspecialchars($edit_record['website']) : ''; ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_9">Display Order</label>
                        <input id="auto_AdminCP_manage_accreditation_php_9" type="number" name="display_order" value="<?php echo $edit_record ? $edit_record['display_order'] : 0; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_accreditation_php_10">Visibility</label>
                        <select id="auto_AdminCP_manage_accreditation_php_10" name="visibility">
                            <option value="active" <?php echo (!$edit_record || $edit_record['visibility']=='active') ? 'selected':''; ?>>Active (shown on site)</option>
                            <option value="inactive" <?php echo ($edit_record && $edit_record['visibility']=='inactive') ? 'selected':''; ?>>Inactive (hidden)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_accreditation_php_11">Logo (optional)</label>
                    <input id="auto_AdminCP_manage_accreditation_php_11" type="file" name="logo" accept="image/*">
                    <?php if ($edit_record && !empty($edit_record['logo'])): ?>
                        <img src="../<?php echo htmlspecialchars($edit_record['logo']); ?>" style="height:60px;border-radius:8px;margin-top:8px;display:block;">
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_record ? 'Save Changes' : 'Add Record'; ?></button>
                <?php if ($edit_record): ?>
                    <a href="manage_accreditation.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="toggleAccredForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function toggleAccredForm() {
                var wrap = document.getElementById('accred-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <!-- List -->
        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Organization</th><th>Title</th><th>Program</th><th>Status</th><th>Valid Until</th><th>Visibility</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                $status_colors = ['accredited' => '#16A34A', 'provisional' => '#F59E0B', 'pending' => '#0BA6C9', 'expired' => '#DC2626'];
                ?>
                <?php if ($records_result && mysqli_num_rows($records_result) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($records_result)): ?>
                        <tr>
                            <td><?php echo $r['display_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['organization_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['title'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($r['program'] ?: '—'); ?></td>
                            <td><span class="accred-status-pill" style="background:<?php echo $status_colors[$r['status']]; ?>1a;color:<?php echo $status_colors[$r['status']]; ?>;"><?php echo $statuses[$r['status']]; ?></span></td>
                            <td><?php echo $r['valid_until'] ? date('d M Y', strtotime($r['valid_until'])) : '—'; ?></td>
                            <td><span class="status-pill" style="background: <?php echo $r['visibility'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($r['visibility']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $r['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $r['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove &quot;<?php echo htmlspecialchars(addslashes($r['organization_name'])); ?>&quot; from Affiliations &amp; Accreditation?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-certificate" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No accreditation/affiliation records added yet. Add your first verified record above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
