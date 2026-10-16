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
    'ongoing'   => 'Ongoing',
    'completed' => 'Completed',
    'upcoming'  => 'Upcoming',
];

// Image compression function (same approach used by Manage Gallery / Clinical Training / Networks)
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

// Handle delete (also removes the uploaded image, if any)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM projects WHERE id = $id"));
    if ($row && !empty($row['image']) && file_exists('../' . $row['image'])) {
        unlink('../' . $row['image']);
    }
    if (mysqli_query($conn, "DELETE FROM projects WHERE id = $id")) {
        $message = "Project removed successfully!";
    } else {
        $message = "Error removing project.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $category = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
    $project_date = trim($_POST['project_date'] ?? '');
    $project_date_sql = $project_date !== '' ? "'" . mysqli_real_escape_string($conn, $project_date) . "'" : 'NULL';
    $status = array_key_exists($_POST['status'] ?? '', $statuses) ? $_POST['status'] : 'ongoing';
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $details = mysqli_real_escape_string($conn, trim($_POST['details'] ?? ''));
    $display_order = intval($_POST['display_order'] ?? 0);
    $visibility = ($_POST['visibility'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if ($title === '') {
        $message = "Project title is required.";
    } else {
        // Image upload (optional)
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../uploads/projects/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'project_' . time() . '_' . uniqid() . '.jpg';
                $target = $upload_dir . $filename;
                if (compressImage($_FILES['image']['tmp_name'], $target, 82)) {
                    $image_path = 'uploads/projects/' . $filename;

                    if (!empty($_POST['project_id'])) {
                        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM projects WHERE id = " . intval($_POST['project_id'])));
                        if ($old && !empty($old['image']) && file_exists('../' . $old['image'])) {
                            unlink('../' . $old['image']);
                        }
                    }
                }
            }
        }
        $image_sql = $image_path !== null ? ", image='" . mysqli_real_escape_string($conn, $image_path) . "'" : '';

        if (!empty($_POST['project_id'])) {
            $id = intval($_POST['project_id']);
            $q = "UPDATE projects SET title='$title', category='$category', project_date=$project_date_sql, status='$status', description='$description', details='$details', display_order=$display_order, visibility='$visibility'$image_sql WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Project updated successfully!" : "Error updating project.";
        } else {
            $image_col_sql = $image_path !== null ? "'" . mysqli_real_escape_string($conn, $image_path) . "'" : 'NULL';
            $q = "INSERT INTO projects (title, category, project_date, status, description, details, display_order, visibility, image) VALUES ('$title', '$category', $project_date_sql, '$status', '$description', '$details', $display_order, '$visibility', $image_col_sql)";
            if (mysqli_query($conn, $q)) {
                $new_id = mysqli_insert_id($conn);
                header('Location: manage_projects.php?edit=' . $new_id . '&msg=' . urlencode('Project added successfully!'));
                exit;
            } else {
                $message = "Error adding project.";
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Item being edited
$edit_project = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_project = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM projects WHERE id = $edit_id"));
}

$projects_result = mysqli_query($conn, "SELECT * FROM projects ORDER BY display_order ASC, project_date DESC, id DESC");

$show_form = $edit_project || $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Projects - Admin Panel</title>
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
        .proj-status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-diagram-project"></i> Our Projects</h1>
            <button type="button" onclick="toggleProjectForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Project</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Add real institutional, research, community or student projects here — this list is shown publicly on the "Our Projects" page.
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="project-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_project ? 'Edit Project' : 'Add Project'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_project): ?><input type="hidden" name="project_id" value="<?php echo $edit_project['id']; ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_1">Project Title *</label>
                        <input id="auto_AdminCP_manage_projects_php_1" type="text" name="title" required value="<?php echo $edit_project ? htmlspecialchars($edit_project['title']) : ''; ?>" placeholder="e.g., Free Community Health Camp">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_2">Category</label>
                        <input id="auto_AdminCP_manage_projects_php_2" type="text" name="category" value="<?php echo $edit_project ? htmlspecialchars($edit_project['category']) : ''; ?>" placeholder="e.g., Community Health">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_3">Date</label>
                        <input id="auto_AdminCP_manage_projects_php_3" type="date" name="project_date" value="<?php echo $edit_project && $edit_project['project_date'] ? $edit_project['project_date'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_4">Status *</label>
                        <select id="auto_AdminCP_manage_projects_php_4" name="status">
                            <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($edit_project && $edit_project['status'] === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_5">Display Order</label>
                        <input id="auto_AdminCP_manage_projects_php_5" type="number" name="display_order" value="<?php echo $edit_project ? $edit_project['display_order'] : 0; ?>" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_projects_php_6">Short Description</label>
                    <textarea id="auto_AdminCP_manage_projects_php_6" name="description" rows="2" placeholder="Brief summary shown on the project card"><?php echo $edit_project ? htmlspecialchars($edit_project['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_projects_php_7">Full Details (optional)</label>
                    <textarea id="auto_AdminCP_manage_projects_php_7" name="details" rows="4" placeholder="Longer write-up shown when a visitor expands the project"><?php echo $edit_project ? htmlspecialchars($edit_project['details']) : ''; ?></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_8">Image (optional)</label>
                        <input id="auto_AdminCP_manage_projects_php_8" type="file" name="image" accept="image/*">
                        <?php if ($edit_project && !empty($edit_project['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($edit_project['image']); ?>" style="height:60px;border-radius:8px;margin-top:8px;display:block;">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_projects_php_9">Visibility</label>
                        <select id="auto_AdminCP_manage_projects_php_9" name="visibility">
                            <option value="active" <?php echo (!$edit_project || $edit_project['visibility']=='active') ? 'selected':''; ?>>Active (shown on site)</option>
                            <option value="inactive" <?php echo ($edit_project && $edit_project['visibility']=='inactive') ? 'selected':''; ?>>Inactive (hidden)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_project ? 'Save Changes' : 'Add Project'; ?></button>
                <?php if ($edit_project): ?>
                    <a href="manage_projects.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="toggleProjectForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function toggleProjectForm() {
                var wrap = document.getElementById('project-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <!-- List -->
        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th>Visibility</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                $status_colors = ['ongoing' => '#0BA6C9', 'completed' => '#16A34A', 'upcoming' => '#F59E0B'];
                ?>
                <?php if ($projects_result && mysqli_num_rows($projects_result) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($projects_result)): ?>
                        <tr>
                            <td><?php echo $p['display_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['category'] ?: '—'); ?></td>
                            <td><?php echo $p['project_date'] ? date('d M Y', strtotime($p['project_date'])) : '—'; ?></td>
                            <td><span class="proj-status-pill" style="background:<?php echo $status_colors[$p['status']]; ?>1a;color:<?php echo $status_colors[$p['status']]; ?>;"><?php echo $statuses[$p['status']]; ?></span></td>
                            <td><span class="status-pill" style="background: <?php echo $p['visibility'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($p['visibility']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $p['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $p['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove &quot;<?php echo htmlspecialchars(addslashes($p['title'])); ?>&quot; from Our Projects?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-diagram-project" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No projects added yet. Add your first project above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
