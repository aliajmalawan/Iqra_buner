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

// Courses for the dropdown
$courses = [];
$cr = mysqli_query($conn, "SELECT id, name FROM courses WHERE status='active' ORDER BY display_order ASC, name ASC");
if ($cr) {
    while ($row = mysqli_fetch_assoc($cr)) {
        $courses[$row['id']] = $row['name'];
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM eligibility_rules WHERE id = $id")) {
        $message = "Rule removed successfully!";
    } else {
        $message = "Error removing rule.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = intval($_POST['course_id'] ?? 0);
    $qualification = mysqli_real_escape_string($conn, trim($_POST['qualification'] ?? ''));
    $min_percentage = floatval($_POST['min_percentage'] ?? 0);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));
    $display_order = intval($_POST['display_order'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    if (!array_key_exists($course_id, $courses)) {
        $message = "Please select a valid program.";
    } elseif ($qualification === '') {
        $message = "Qualification is required.";
    } else {
        if (!empty($_POST['rule_id'])) {
            $id = intval($_POST['rule_id']);
            $q = "UPDATE eligibility_rules SET course_id=$course_id, qualification='$qualification', min_percentage=$min_percentage, notes='$notes', display_order=$display_order, status='$status' WHERE id=$id";
            $message = mysqli_query($conn, $q) ? "Rule updated successfully!" : "Error updating rule.";
        } else {
            $q = "INSERT INTO eligibility_rules (course_id, qualification, min_percentage, notes, display_order, status) VALUES ($course_id, '$qualification', $min_percentage, '$notes', $display_order, '$status')";
            if (mysqli_query($conn, $q)) {
                $new_id = mysqli_insert_id($conn);
                header('Location: manage_eligibility.php?edit=' . $new_id . '&msg=' . urlencode('Rule added successfully!'));
                exit;
            } else {
                $message = "Error adding rule.";
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Item being edited
$edit_rule = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_rule = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM eligibility_rules WHERE id = $edit_id"));
}

$rules_result = mysqli_query($conn, "SELECT er.*, c.name AS course_name FROM eligibility_rules er LEFT JOIN courses c ON c.id = er.course_id ORDER BY c.name ASC, er.display_order ASC, er.id DESC");

$show_form = $edit_rule || $_SERVER['REQUEST_METHOD'] === 'POST';

// Programs that still have zero configured rules — surfaced so admin knows what's outstanding
$configured_ids = [];
$cir = mysqli_query($conn, "SELECT DISTINCT course_id FROM eligibility_rules");
if ($cir) { while ($row = mysqli_fetch_assoc($cir)) { $configured_ids[] = (int) $row['course_id']; } }
$unconfigured = array_diff_key($courses, array_flip($configured_ids));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligibility Checker Rules - Admin Panel</title>
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
            <h1><i class="fas fa-clipboard-check"></i> Eligibility Checker Rules</h1>
            <button type="button" onclick="toggleRuleForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Rule</button>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-circle-info"></i> Only add rules that match actual, verified admission criteria for each program. A program with no rules here will show "Review Required" on the public Eligibility Checker instead of a guess.
        </div>

        <?php if (!empty($unconfigured)): ?>
        <div style="background:#fff8e1;border-left:4px solid #F59E0B;border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text-dark);margin-bottom:22px;">
            <i class="fas fa-triangle-exclamation" style="color:#F59E0B;"></i> No eligibility rules configured yet for: <strong><?php echo htmlspecialchars(implode(', ', $unconfigured)); ?></strong> — these will show "Review Required" until rules are added.
        </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="rule-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_rule ? 'Edit Rule' : 'Add Rule'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_rule): ?><input type="hidden" name="rule_id" value="<?php echo $edit_rule['id']; ?>"><?php endif; ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_eligibility_php_1">Program *</label>
                        <select id="auto_AdminCP_manage_eligibility_php_1" name="course_id" required>
                            <option value="">— Select Program —</option>
                            <?php foreach ($courses as $cid => $cname): ?>
                            <option value="<?php echo $cid; ?>" <?php echo ($edit_rule && (int)$edit_rule['course_id'] === $cid) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cname); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_eligibility_php_2">Accepted Qualification *</label>
                        <input id="auto_AdminCP_manage_eligibility_php_2" type="text" name="qualification" required value="<?php echo $edit_rule ? htmlspecialchars($edit_rule['qualification']) : ''; ?>" placeholder="e.g., F.Sc Pre-Medical">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_eligibility_php_3">Minimum Percentage Required *</label>
                        <input id="auto_AdminCP_manage_eligibility_php_3" type="number" name="min_percentage" required min="0" max="100" step="0.01" value="<?php echo $edit_rule ? $edit_rule['min_percentage'] : ''; ?>" placeholder="e.g., 60">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_eligibility_php_4">Display Order</label>
                        <input id="auto_AdminCP_manage_eligibility_php_4" type="number" name="display_order" value="<?php echo $edit_rule ? $edit_rule['display_order'] : 0; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_eligibility_php_5">Status</label>
                        <select id="auto_AdminCP_manage_eligibility_php_5" name="status">
                            <option value="active" <?php echo (!$edit_rule || $edit_rule['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_rule && $edit_rule['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_eligibility_php_6">Notes (optional)</label>
                    <input id="auto_AdminCP_manage_eligibility_php_6" type="text" name="notes" value="<?php echo $edit_rule ? htmlspecialchars($edit_rule['notes']) : ''; ?>" placeholder="e.g., Chemistry and Biology required as subjects">
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_rule ? 'Save Changes' : 'Add Rule'; ?></button>
                <?php if ($edit_rule): ?>
                    <a href="manage_eligibility.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="toggleRuleForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function toggleRuleForm() {
                var wrap = document.getElementById('rule-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <!-- List -->
        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Program</th><th>Qualification</th><th>Min %</th><th>Notes</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($rules_result && mysqli_num_rows($rules_result) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($rules_result)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['course_name'] ?: 'Unknown Program'); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['qualification']); ?></td>
                            <td><?php echo rtrim(rtrim(number_format($r['min_percentage'], 2), '0'), '.'); ?>%</td>
                            <td><?php echo htmlspecialchars($r['notes'] ?: '—'); ?></td>
                            <td><span class="status-pill" style="background: <?php echo $r['status'] == 'active' ? '#16A34A' : '#DC2626'; ?>;"><?php echo ucfirst($r['status']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $r['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $r['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Remove this eligibility rule?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-clipboard-check" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No eligibility rules configured yet. Add your first verified rule above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
