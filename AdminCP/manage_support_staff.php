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
require_once '../includes/support_staff_helper.php';
require_once '../includes/audit_helper.php';

staff_ensure_schema($conn);
$campuses = staff_campus_options($conn);
$BASE = '../';

// Admins / super admins: full access. Moderators: read-only directory with CNIC, phone and address masked.
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$full = in_array($admin_role, ['super_admin', 'admin'], true);

/* ── Filters ── */
$flt = [
    'q'        => trim((string) ($_GET['q'] ?? '')),
    'category' => isset(STAFF_CATEGORIES[$_GET['category'] ?? '']) ? $_GET['category'] : '',
    'emp'      => isset(STAFF_EMPLOYMENT[$_GET['emp'] ?? '']) ? $_GET['emp'] : '',
    'campus'   => in_array($_GET['campus'] ?? '', $campuses, true) ? $_GET['campus'] : '',
    'active'   => in_array($_GET['active'] ?? '', ['1', '0'], true) ? $_GET['active'] : '',
];
function staffWhere($flt, $full) {
    $sql = []; $types = ''; $args = [];
    if ($flt['q'] !== '') {
        $like = '%' . $flt['q'] . '%';
        // CNIC / mobile are only searchable by users who may see them
        $cols = $full ? ['full_name', 'father_name', 'employee_id', 'designation', 'cnic', "REPLACE(cnic, '-', '')", 'mobile'] : ['full_name', 'father_name', 'employee_id', 'designation'];
        $sql[] = '(' . implode(' OR ', array_map(function ($c) { return "$c LIKE ?"; }, $cols)) . ')';
        $types .= str_repeat('s', count($cols)); $args = array_merge($args, array_fill(0, count($cols), $like));
    }
    foreach (['category' => 'category', 'emp' => 'employment_status', 'campus' => 'campus'] as $k => $col) {
        if ($flt[$k] !== '') { $sql[] = "$col = ?"; $types .= 's'; $args[] = $flt[$k]; }
    }
    if ($flt['active'] !== '') { $sql[] = 'is_active = ?'; $types .= 'i'; $args[] = (int) $flt['active']; }
    return [$sql ? 'WHERE ' . implode(' AND ', $sql) : '', $types, $args];
}
function staffQuery($conn, $sql, $types = '', $args = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$args);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
function staffFetch($conn, $id) {
    $r = mysqli_fetch_assoc(staffQuery($conn, 'SELECT * FROM support_staff WHERE id = ?', 'i', [$id]));
    return $r ?: null;
}
function staffUrl($extra = []) {
    global $flt;
    $q = array_filter(array_merge($flt, $extra), function ($v) { return $v !== '' && $v !== null; });
    return 'manage_support_staff.php' . ($q ? '?' . http_build_query($q) : '');
}
function staffBack($msg, $err = false, $to = null) {
    $ret = $to ?? (string) ($_POST['return'] ?? '');
    if (!preg_match('#^manage_support_staff\.php(\?[^\s<>"]*)?$#', $ret)) $ret = 'manage_support_staff.php';
    $ret = rtrim(preg_replace('/([?&])(msg|err)=[^&]*&?/', '$1', $ret), '?&');
    header('Location: ' . $ret . (strpos($ret, '?') === false ? '?' : '&') . 'msg=' . urlencode($msg) . ($err ? '&err=1' : ''));
    exit;
}
function staffNeedFull($full) { if (!$full) staffBack('Your account does not have permission for this action.', true); }

/* ── CSV export (admins only, current filters) ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$full) { http_response_code(403); exit('Not allowed.'); }
    [$where, $types, $args] = staffWhere($flt, $full);
    try {
        $res = staffQuery($conn, "SELECT * FROM support_staff $where ORDER BY employee_id", $types, $args);
    } catch (Throwable $e) {
        error_log('[support_staff] export: ' . $e->getMessage());
        header('Location: manage_support_staff.php?msg=' . urlencode('The export could not be created. Please try again.') . '&err=1');
        exit;
    }
    audit_log($conn, 'export', 'support_staff', null, 'CSV export of ' . mysqli_num_rows($res) . ' record(s) incl. CNIC/phone' . (array_filter($flt, 'strlen') ? ' (filtered)' : ''));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="iqra-support-staff-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $safe = function ($v) { $v = (string) $v; return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; };
    fputcsv($out, ['Employee ID', 'Full Name', "Father's Name", 'CNIC', 'Mobile', 'Address', 'Category', 'Designation', 'Campus',
                   'Joining Date', 'Employment Status', 'Record', 'Emergency Contact', 'Emergency Phone']);
    while ($r = mysqli_fetch_assoc($res)) {
        fputcsv($out, array_map($safe, [$r['employee_id'], $r['full_name'], $r['father_name'], $r['cnic'], $r['mobile'], $r['address'],
            STAFF_CATEGORIES[$r['category']] ?? $r['category'], $r['designation'], $r['campus'], $r['joining_date'],
            STAFF_EMPLOYMENT[$r['employment_status']] ?? $r['employment_status'], $r['is_active'] ? 'Active' : 'Inactive',
            $r['emergency_name'], $r['emergency_phone']]));
    }
    fclose($out);
    exit;
}

/* ── Actions (POST + CSRF) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['act'] ?? '';
  if (!$full) audit_log($conn, 'denied', 'support_staff', $_POST['id'] ?? null, 'Blocked "' . mb_substr((string) $act, 0, 20) . '" — account role lacks permission');
  staffNeedFull($full);
  try {

    if ($act === 'toggle') {
        $row = staffFetch($conn, (int) ($_POST['id'] ?? 0));
        if (!$row) staffBack('Record not found.', true);
        $new = $row['is_active'] ? 0 : 1;
        staffQuery($conn, 'UPDATE support_staff SET is_active = ? WHERE id = ?', 'ii', [$new, $row['id']]);
        audit_log($conn, $new ? 'activate' : 'deactivate', 'support_staff', $row['employee_id'], ($new ? 'Activated ' : 'Deactivated ') . $row['full_name']);
        staffBack($row['full_name'] . ' is now ' . ($new ? 'active' : 'inactive') . '.');
    }

    if ($act === 'delete') {
        $row = staffFetch($conn, (int) ($_POST['id'] ?? 0));
        if (!$row) staffBack('Record not found.', true);
        // Second confirmation: the admin must type the employee ID exactly
        if (strcasecmp(trim((string) ($_POST['confirm_employee_id'] ?? '')), $row['employee_id']) !== 0) {
            audit_log($conn, 'delete_blocked', 'support_staff', $row['employee_id'], 'Delete not confirmed (employee ID typed incorrectly)');
            staffBack('Not deleted: type the employee ID exactly to confirm.', true, 'manage_support_staff.php?view=' . $row['id']);
        }
        staffQuery($conn, 'DELETE FROM support_staff WHERE id = ?', 'i', [$row['id']]);
        staff_delete_photo($row['photo'], $BASE);
        audit_log($conn, 'delete', 'support_staff', $row['employee_id'], 'Deleted ' . $row['full_name'] . ' (' . (STAFF_CATEGORIES[$row['category']] ?? $row['category']) . ')');
        staffBack('Staff record ' . $row['employee_id'] . ' deleted.', false, 'manage_support_staff.php');
    }

    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $old = $id ? staffFetch($conn, $id) : null;
        if ($id && !$old) staffBack('Record not found.', true);
        [$d, $errors] = staff_validate($_POST, $campuses);
        if (!$errors) {
            $dup = staff_find_duplicate($conn, $d['employee_id'], $d['cnic'], $id);
            if ($dup === 'employee_id') $errors['employee_id'] = 'This employee ID is already assigned to another staff member.';
            if ($dup === 'cnic') $errors['cnic'] = 'A staff record with this CNIC already exists.';
        }
        $photo = $old['photo'] ?? null;
        if (!$errors) {
            [$new_photo, $perr] = staff_save_photo($_FILES['photo'] ?? null, $BASE);
            if ($perr) $errors['photo'] = $perr;
            elseif ($new_photo) { staff_delete_photo($photo, $BASE); $photo = $new_photo; }
            elseif (!empty($_POST['remove_photo'])) { staff_delete_photo($photo, $BASE); $photo = null; }
        }
        if ($errors) {
            $_SESSION['staff_edit_errors'] = $errors;
            $_SESSION['staff_edit_post'] = array_diff_key($_POST, ['csrf_token' => 1]);
            header('Location: manage_support_staff.php?edit=' . ($id ?: 'new'));
            exit;
        }
        $vals = [$d['employee_id'], $d['full_name'], $d['father_name'], $d['cnic'], $d['mobile'], $d['address'], $d['category'],
                 $d['designation'], $d['campus'] ?: null, $d['joining_date'], $d['employment_status'], $d['is_active'], $photo,
                 $d['emergency_name'] ?: null, $d['emergency_phone'] ?: null];
        $cols = 'employee_id=?, full_name=?, father_name=?, cnic=?, mobile=?, address=?, category=?, designation=?, campus=?, joining_date=?, employment_status=?, is_active=?, photo=?, emergency_name=?, emergency_phone=?';
        $types = 'sssssssssssisss';
        $audit_labels = ['employee_id' => 'employee ID', 'full_name' => 'name', 'father_name' => "father's name", 'cnic' => 'CNIC', 'mobile' => 'mobile',
                         'address' => 'address', 'category' => 'category', 'designation' => 'designation', 'campus' => 'campus', 'joining_date' => 'joining date',
                         'employment_status' => 'employment status', 'is_active' => 'active', 'photo' => 'photo', 'emergency_name' => 'emergency contact',
                         'emergency_phone' => 'emergency phone'];
        $audit_new = array_combine(array_keys($audit_labels), $vals);
        if ($id) {
            staffQuery($conn, "UPDATE support_staff SET $cols WHERE id = ?", $types . 'i', array_merge($vals, [$id]));
            audit_log($conn, 'update', 'support_staff', $d['employee_id'], audit_changed_fields($old, $audit_new, $audit_labels));
        } else {
            staffQuery($conn, "INSERT INTO support_staff SET $cols", $types, $vals);
            $id = mysqli_insert_id($conn);
            audit_log($conn, 'create', 'support_staff', $d['employee_id'], 'Added ' . $d['full_name'] . ' (' . STAFF_CATEGORIES[$d['category']] . ')');
        }
        staffBack('Staff record saved.', false, 'manage_support_staff.php?view=' . $id);
    }
    staffBack('Unknown action.', true);
  } catch (mysqli_sql_exception $e) {
    error_log('[support_staff] ' . $act . ': ' . $e->getMessage());
    staffBack($e->getCode() == 1062 ? 'That employee ID or CNIC is already used by another staff record.' : 'The change could not be saved because of a database error. Please try again.', true);
  }
}

/* ── Page data ── */
$message = (string) ($_GET['msg'] ?? '');
$is_error = isset($_GET['err']);
$view = isset($_GET['view']) ? staffFetch($conn, (int) $_GET['view']) : null;
$edit = null; $edit_errors = []; $edit_post = null;
if (isset($_GET['edit']) && $full) {
    $edit = $_GET['edit'] === 'new' ? ['id' => 0] : staffFetch($conn, (int) $_GET['edit']);
    $edit_errors = $_SESSION['staff_edit_errors'] ?? [];
    $edit_post = $_SESSION['staff_edit_post'] ?? null;
    unset($_SESSION['staff_edit_errors'], $_SESSION['staff_edit_post']);
}

$counts = ['all' => 0, 'active' => 0, 'inactive' => 0];
$res = mysqli_query($conn, "SELECT is_active, COUNT(*) c FROM support_staff GROUP BY is_active");
while ($res && ($r = mysqli_fetch_assoc($res))) { $counts[$r['is_active'] ? 'active' : 'inactive'] = (int) $r['c']; $counts['all'] += (int) $r['c']; }
$cat_counts = [];
$res = mysqli_query($conn, "SELECT category, COUNT(*) c FROM support_staff WHERE is_active = 1 GROUP BY category");
while ($res && ($r = mysqli_fetch_assoc($res))) $cat_counts[$r['category']] = (int) $r['c'];

$per_page = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
[$where, $types, $args] = staffWhere($flt, $full);
$total = (int) mysqli_fetch_row(staffQuery($conn, "SELECT COUNT(*) FROM support_staff $where", $types, $args))[0];
$pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $pages);
$offset = ($page - 1) * $per_page;
$rows = staffQuery($conn, "SELECT * FROM support_staff $where ORDER BY is_active DESC, full_name ASC LIMIT $per_page OFFSET $offset", $types, $args);
$return_url = staffUrl(['page' => $page > 1 ? $page : '']);

$h = function ($v) { return htmlspecialchars((string) $v); };
$cnic = function ($v) use ($full) { return $full ? $v : staff_mask_cnic($v); };
$phone = function ($v) use ($full) { return $full ? $v : staff_mask_phone($v); };
function staffInitials($name) {
    $ini = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $p) $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    return $ini ?: '?';
}
function staffAvatar($row, $h) {
    // Photos are served through staff_photo.php (login required), never from a public URL
    return $row['photo']
        ? '<img src="staff_photo.php?id=' . (int) $row['id'] . '&amp;v=' . substr(md5($row['photo']), 0, 8) . '" alt="" loading="lazy">'
        : $h(staffInitials($row['full_name']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Support Staff - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-light); }
        .st-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
        .st-head h1 { margin:0; }
        .st-head-btns { display:flex; gap:10px; flex-wrap:wrap; }
        .st-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:16px; }
        .st-stat { display:flex; align-items:center; gap:14px; padding:16px 18px; text-decoration:none; color:inherit; border:1px solid var(--border-color); border-radius:12px; background:#fff; transition:border-color .2s, box-shadow .2s; }
        .st-stat:hover, .st-stat.active { border-color:var(--accent-color); box-shadow:0 0 0 3px rgba(11,166,201,.12); }
        .st-stat i { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
        .st-stat strong { display:block; font-size:22px; color:var(--primary-color); line-height:1.1; }
        .st-stat span { font-size:13px; color:var(--text-light); }
        .st-ic-all { background:var(--light-blue); color:var(--accent-text); }
        .st-ic-active { background:#F0FDF4; color:#15803D; }
        .st-ic-inactive { background:#F1F5F9; color:#64748B; }
        .st-cats { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:22px; }
        .st-cat { display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:999px; border:1px solid var(--border-color); background:#fff; color:var(--text-color); text-decoration:none; font-size:13px; font-weight:600; }
        .st-cat b { background:#F1F5F9; color:var(--text-light); border-radius:999px; padding:0 8px; font-size:12px; }
        .st-cat:hover { border-color:var(--primary-color); color:var(--primary-color); }
        .st-cat.active { background:var(--primary-color); border-color:var(--primary-color); color:#fff; }
        .st-cat.active b { background:rgba(255,255,255,.2); color:#fff; }

        .st-filters { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
        .st-filters .form-group { margin:0; }
        .st-filter-btns { display:flex; gap:8px; }
        .st-meta { font-size:13px; color:var(--text-light); }
        .st-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
        .st-person { display:flex; align-items:center; gap:12px; min-width:200px; }
        .st-av { width:42px; height:42px; border-radius:50%; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--primary-color); color:#fff; font-weight:700; font-size:14px; }
        .st-av img { width:100%; height:100%; object-fit:cover; }
        .st-person small, .st-sub { display:block; color:var(--text-light); font-size:12.5px; }
        .st-id { font-family:var(--font-heading); font-weight:600; color:var(--accent-text); font-size:13px; white-space:nowrap; }
        .st-actions { min-width:84px; }
        .st-inline { display:inline; margin:0; }
        .badge-on { background:#F0FDF4; color:#15803D; }
        .badge-off { background:#F1F5F9; color:#64748B; }
        .badge-emp { background:var(--light-blue); color:var(--accent-text); }
        .badge-emp.warn { background:#FFFBEB; color:#B45309; }
        .badge-emp.bad { background:#FEF2F2; color:#B91C1C; }
        .st-empty { text-align:center; padding:40px 16px; color:var(--text-light); }
        .st-pager { display:flex; gap:6px; justify-content:center; flex-wrap:wrap; margin-top:18px; }
        .st-pager a, .st-pager span { min-width:36px; height:36px; padding:0 10px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid var(--border-color); text-decoration:none; color:var(--primary-color); font-weight:600; font-size:13px; background:#fff; }
        .st-pager .cur { background:var(--primary-color); color:#fff; border-color:var(--primary-color); }
        .st-lock { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-radius:10px; background:#FFFBEB; border:1px solid #FDE68A; color:#92400E; font-size:13.5px; margin-bottom:18px; }

        .st-profile { display:grid; grid-template-columns:260px minmax(0,1fr); gap:24px; }
        .st-side { text-align:center; padding:26px 20px; border-radius:12px; background:var(--primary-color); color:#fff; border-bottom:3px solid var(--gold-color); align-self:start; }
        .st-side .st-av { width:120px; height:120px; margin:0 auto 14px; font-size:36px; border:3px solid rgba(255,255,255,.2); background:rgba(255,255,255,.1); }
        .st-side h2 { color:#fff !important; font-size:20px; margin:0 0 4px; }
        .st-side p { color:rgba(255,255,255,.78); margin:0 0 12px; font-size:14px; }
        .st-side .st-id { color:var(--gold-color); }
        .st-side-btns { display:flex; gap:8px; flex-wrap:wrap; justify-content:center; margin-top:6px; }
        .st-dl { display:grid; grid-template-columns:190px minmax(0,1fr); margin:0; border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
        .st-dl dt, .st-dl dd { margin:0; padding:12px 16px; border-bottom:1px solid var(--border-color); font-size:14px; }
        .st-dl dt { background:#F8FAFC; color:var(--text-light); font-weight:600; }
        .st-dl dd { color:var(--text-color); word-break:break-word; }
        .st-dl dt:last-of-type, .st-dl dd:last-of-type { border-bottom:0; }
        .st-danger { margin-top:22px; padding:18px; border:1px solid #FECACA; border-radius:12px; background:#FEF2F2; }
        .st-danger h3 { color:#991B1B !important; font-size:16px; margin:0 0 6px; }
        .st-danger p { color:#7F1D1D; font-size:13.5px; margin:0 0 12px; }
        .st-danger-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .st-danger input { max-width:220px; min-height:40px; padding:8px 12px; border:1px solid #FCA5A5; border-radius:8px; font-family:inherit; }

        .st-form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px 20px; }
        .st-form-grid .span-2 { grid-column:span 2; }
        .st-form-grid .span-3 { grid-column:1 / -1; }
        .st-form-grid .form-group { margin:0; }
        .st-section { font-family:var(--font-heading); font-size:15px; font-weight:600; color:var(--primary-color); margin:26px 0 14px; padding-bottom:8px; border-bottom:1px solid var(--border-color); }
        .st-section:first-of-type { margin-top:0; }
        .st-check { display:flex; gap:10px; align-items:center; font-weight:500; }
        .st-check input { width:18px; height:18px; accent-color:var(--primary-color); }
        .field-error { color:#B91C1C; font-size:13px; margin:4px 0 0; }

        @media (max-width:1200px) { .st-filters { grid-template-columns:1fr 1fr 1fr; } .st-filters .q { grid-column:1 / -1; } }
        @media (max-width:900px) { .st-profile { grid-template-columns:1fr; } .st-form-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width:640px) {
            .st-stats { grid-template-columns:1fr 1fr; }
            .st-filters, .st-form-grid { grid-template-columns:1fr; }
            .st-form-grid .span-2 { grid-column:auto; }
            .st-dl { grid-template-columns:1fr; }
            .st-dl dt { border-bottom:0; padding-bottom:4px; }
            .st-dl dd { padding-top:0; }
        }
    </style>
</head>
<body class="admin-body">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">

<?php if ($message): ?>
    <div class="alert <?php echo $is_error ? 'alert-error' : 'alert-success'; ?>" role="status"><?php echo $h($message); ?></div>
<?php endif; ?>

<?php if ($edit): /* ───────────── ADD / EDIT ───────────── */
    $src = $edit_post ?? ($edit['id'] ? $edit : ['employee_id' => staff_next_employee_id($conn), 'employment_status' => 'permanent', 'is_active' => 1, 'joining_date' => date('Y-m-d')]);
    $sv = function ($k) use ($src) { return htmlspecialchars((string) ($src[$k] ?? '')); };
    $fe = function ($k) use ($edit_errors) { return isset($edit_errors[$k]) ? '<p class="field-error">' . htmlspecialchars($edit_errors[$k]) . '</p>' : ''; };
    $inv = function ($k) use ($edit_errors) { return isset($edit_errors[$k]) ? ' aria-invalid="true"' : ''; };
    $back = $edit['id'] ? 'manage_support_staff.php?view=' . (int) $edit['id'] : 'manage_support_staff.php';
?>
    <div class="st-head">
        <h1><i class="fas fa-user-pen"></i> <?php echo $edit['id'] ? 'Edit Staff Record' : 'Add Support Staff'; ?></h1>
        <div class="st-head-btns"><a href="<?php echo $back; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Cancel</a></div>
    </div>
    <?php if ($edit_errors): ?><div class="alert alert-error">Please correct the highlighted fields.</div><?php endif; ?>
    <div class="card">
        <form method="POST" enctype="multipart/form-data" autocomplete="off">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="act" value="save">
            <input type="hidden" name="id" value="<?php echo (int) $edit['id']; ?>">

            <div class="st-section">Employment</div>
            <div class="st-form-grid">
                <div class="form-group"><label for="s_eid">Employee ID *</label><input id="s_eid" name="employee_id" required maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9\-\/]{1,29}" title="Letters, numbers, - or /" value="<?php echo $sv('employee_id'); ?>"<?php echo $inv('employee_id'); ?>><?php echo $fe('employee_id') ?: '<small>Must be unique, e.g. SS-0001</small>'; ?></div>
                <div class="form-group"><label for="s_cat">Staff Category *</label>
                    <select id="s_cat" name="category" required<?php echo $inv('category'); ?>><option value="">— Select —</option><?php foreach (STAFF_CATEGORIES as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo ($src['category'] ?? '') === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select><?php echo $fe('category'); ?></div>
                <div class="form-group"><label for="s_des">Designation</label><input id="s_des" name="designation" maxlength="100" placeholder="e.g. Van Driver, Peon, Gatekeeper" value="<?php echo $sv('designation'); ?>"></div>
                <div class="form-group"><label for="s_join">Joining Date *</label><input id="s_join" type="date" name="joining_date" required min="1990-01-01" max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" value="<?php echo $sv('joining_date'); ?>"<?php echo $inv('joining_date'); ?>><?php echo $fe('joining_date'); ?></div>
                <div class="form-group"><label for="s_emp">Employment Status *</label>
                    <select id="s_emp" name="employment_status" required<?php echo $inv('employment_status'); ?>><?php foreach (STAFF_EMPLOYMENT as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo ($src['employment_status'] ?? '') === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select><?php echo $fe('employment_status'); ?></div>
                <div class="form-group"><label for="s_campus">Campus</label>
                    <select id="s_campus" name="campus"><option value="">— Not set —</option><?php foreach ($campuses as $c): ?><option value="<?php echo $h($c); ?>"<?php echo ($src['campus'] ?? '') === $c ? ' selected' : ''; ?>><?php echo $h($c); ?></option><?php endforeach; ?></select></div>
                <div class="form-group span-3"><label class="st-check"><input type="checkbox" name="is_active" value="1"<?php echo !empty($src['is_active']) ? ' checked' : ''; ?>> Active record (uncheck to deactivate without deleting)</label></div>
            </div>

            <div class="st-section">Personal Details</div>
            <div class="st-form-grid">
                <div class="form-group"><label for="s_name">Full Name *</label><input id="s_name" name="full_name" required maxlength="100" value="<?php echo $sv('full_name'); ?>"<?php echo $inv('full_name'); ?>><?php echo $fe('full_name'); ?></div>
                <div class="form-group"><label for="s_father">Father's Name *</label><input id="s_father" name="father_name" required maxlength="100" value="<?php echo $sv('father_name'); ?>"<?php echo $inv('father_name'); ?>><?php echo $fe('father_name'); ?></div>
                <div class="form-group"><label for="s_cnic">CNIC *</label><input id="s_cnic" name="cnic" required maxlength="15" inputmode="numeric" placeholder="15101-1234567-1" value="<?php echo $sv('cnic'); ?>"<?php echo $inv('cnic'); ?>><?php echo $fe('cnic'); ?></div>
                <div class="form-group"><label for="s_mob">Mobile Number *</label><input id="s_mob" type="tel" name="mobile" required maxlength="20" placeholder="03001234567" value="<?php echo $sv('mobile'); ?>"<?php echo $inv('mobile'); ?>><?php echo $fe('mobile'); ?></div>
                <div class="form-group span-2"><label for="s_addr">Address *</label><input id="s_addr" name="address" required maxlength="255" placeholder="Village / mohalla, tehsil, district" value="<?php echo $sv('address'); ?>"<?php echo $inv('address'); ?>><?php echo $fe('address'); ?></div>
            </div>

            <div class="st-section">Emergency Contact &amp; Photo <small style="font-weight:400;color:var(--text-light);">(optional)</small></div>
            <div class="st-form-grid">
                <div class="form-group"><label for="s_en">Emergency Contact Name</label><input id="s_en" name="emergency_name" maxlength="100" placeholder="Name and relation" value="<?php echo $sv('emergency_name'); ?>"></div>
                <div class="form-group"><label for="s_ep">Emergency Contact Number</label><input id="s_ep" type="tel" name="emergency_phone" maxlength="20" value="<?php echo $sv('emergency_phone'); ?>"<?php echo $inv('emergency_phone'); ?>><?php echo $fe('emergency_phone'); ?></div>
                <div class="form-group">
                    <label for="s_photo">Photograph (JPG/PNG/WebP, max 2 MB)</label>
                    <?php if (!empty($edit['photo'])): ?><div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="st-av"><?php echo staffAvatar($edit, $h); ?></span><label class="st-check"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label></div><?php endif; ?>
                    <input id="s_photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"><?php echo $fe('photo'); ?>
                </div>
            </div>

            <div class="form-actions" style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Record</button>
                <a href="<?php echo $back; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <script>
    // CNIC: format as 12345-1234567-1 while typing
    (function () {
        var c = document.getElementById('s_cnic');
        c.addEventListener('input', function () {
            var d = c.value.replace(/\D/g, '').slice(0, 13), o = d.slice(0, 5);
            if (d.length > 5) o += '-' + d.slice(5, 12);
            if (d.length > 12) o += '-' + d.slice(12);
            c.value = o;
        });
    })();
    </script>

<?php elseif ($view): /* ───────────── PROFILE ───────────── */
    $emp = $view['employment_status'];
    $emp_cls = in_array($emp, ['resigned', 'retired', 'terminated'], true) ? ' bad' : ($emp === 'on_leave' ? ' warn' : '');
?>
    <div class="st-head">
        <h1><i class="fas fa-id-badge"></i> Staff Profile</h1>
        <div class="st-head-btns"><a href="manage_support_staff.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to directory</a></div>
    </div>
    <?php if (!$full): ?><div class="st-lock"><i class="fas fa-lock"></i><div>CNIC, phone numbers and address are partly hidden for your account role.</div></div><?php endif; ?>
    <div class="card">
        <div class="st-profile">
            <div class="st-side">
                <div class="st-av"><?php echo staffAvatar($view, $h); ?></div>
                <h2><?php echo $h($view['full_name']); ?></h2>
                <p><?php echo $h($view['designation'] ?: STAFF_CATEGORIES[$view['category']] ?? ''); ?></p>
                <p><span class="st-id"><?php echo $h($view['employee_id']); ?></span></p>
                <p><span class="badge <?php echo $view['is_active'] ? 'badge-on' : 'badge-off'; ?>"><?php echo $view['is_active'] ? 'Active' : 'Inactive'; ?></span>
                   <span class="badge badge-emp<?php echo $emp_cls; ?>"><?php echo $h(STAFF_EMPLOYMENT[$emp] ?? $emp); ?></span></p>
                <?php if ($full): ?>
                <div class="st-side-btns">
                    <a href="manage_support_staff.php?edit=<?php echo (int) $view['id']; ?>" class="action-btn btn-edit"><i class="fas fa-pen"></i> Edit</a>
                    <form method="POST" class="st-inline"><?php echo csrf_field(); ?>
                        <input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $view['id']; ?>">
                        <input type="hidden" name="return" value="manage_support_staff.php?view=<?php echo (int) $view['id']; ?>">
                        <button type="submit" class="action-btn <?php echo $view['is_active'] ? 'btn-reject' : 'btn-approve'; ?>"><i class="fas <?php echo $view['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i> <?php echo $view['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div>
                <dl class="st-dl">
                    <dt>Employee ID</dt><dd><?php echo $h($view['employee_id']); ?></dd>
                    <dt>Full Name</dt><dd><?php echo $h($view['full_name']); ?></dd>
                    <dt>Father's Name</dt><dd><?php echo $h($view['father_name']); ?></dd>
                    <dt>CNIC</dt><dd><?php echo $h($cnic($view['cnic'])); ?></dd>
                    <dt>Mobile</dt><dd><?php echo $full ? '<a href="tel:' . $h($view['mobile']) . '">' . $h($view['mobile']) . '</a>' : $h($phone($view['mobile'])); ?></dd>
                    <dt>Address</dt><dd><?php echo $full ? $h($view['address']) : '<em style="color:var(--text-light);">Hidden</em>'; ?></dd>
                    <dt>Category</dt><dd><?php echo $h(STAFF_CATEGORIES[$view['category']] ?? $view['category']); ?></dd>
                    <dt>Designation</dt><dd><?php echo $h($view['designation'] ?: '—'); ?></dd>
                    <dt>Campus</dt><dd><?php echo $h($view['campus'] ?: '—'); ?></dd>
                    <dt>Joining Date</dt><dd><?php echo $view['joining_date'] ? $h(date('d M Y', strtotime($view['joining_date']))) : '—'; ?></dd>
                    <dt>Employment Status</dt><dd><?php echo $h(STAFF_EMPLOYMENT[$emp] ?? $emp); ?></dd>
                    <dt>Emergency Contact</dt><dd><?php echo $view['emergency_name'] || $view['emergency_phone'] ? $h(trim($view['emergency_name'] . ' ' . ($view['emergency_phone'] ? '· ' . $phone($view['emergency_phone']) : ''))) : '—'; ?></dd>
                    <dt>Record</dt><dd>Added <?php echo $h(date('d M Y', strtotime($view['created_at']))); ?><?php if ($view['updated_at']): ?> · updated <?php echo $h(date('d M Y', strtotime($view['updated_at']))); ?><?php endif; ?></dd>
                </dl>
                <?php if ($full): ?>
                <div class="st-danger">
                    <h3><i class="fas fa-triangle-exclamation"></i> Delete this record</h3>
                    <p>Deleting is permanent and also removes the photo. To keep the history, use <strong>Deactivate</strong> instead. To confirm, type the employee ID <strong><?php echo $h($view['employee_id']); ?></strong>.</p>
                    <form method="POST" class="st-danger-row" onsubmit="return confirm('Permanently delete <?php echo $h(addslashes($view['full_name'])); ?>?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?php echo (int) $view['id']; ?>">
                        <label for="del_confirm" class="visually-hidden">Employee ID</label>
                        <input id="del_confirm" name="confirm_employee_id" required autocomplete="off" placeholder="<?php echo $h($view['employee_id']); ?>">
                        <button type="submit" class="action-btn btn-delete"><i class="fas fa-trash"></i> Delete permanently</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: /* ───────────── DIRECTORY ───────────── */ ?>
    <div class="st-head">
        <h1><i class="fas fa-people-carry-box"></i> Support Staff</h1>
        <div class="st-head-btns">
            <?php if ($full): ?>
            <a href="<?php echo $h(staffUrl(['export' => 'csv'])); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
            <a href="manage_support_staff.php?edit=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Staff</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!$full): ?><div class="st-lock"><i class="fas fa-lock"></i><div>View-only access: CNIC and phone numbers are partly hidden for your account role.</div></div><?php endif; ?>

    <div class="st-stats">
        <?php foreach (['all' => ['All Staff', 'fa-users', ''], 'active' => ['Active', 'fa-user-check', '1'], 'inactive' => ['Inactive', 'fa-user-slash', '0']] as $k => $s): ?>
        <a class="st-stat<?php echo $flt['active'] === $s[2] ? ' active' : ''; ?>" href="<?php echo $h(staffUrl(['active' => $s[2], 'page' => ''])); ?>">
            <i class="fas <?php echo $s[1]; ?> st-ic-<?php echo $k; ?>" aria-hidden="true"></i>
            <div><strong><?php echo $counts[$k]; ?></strong><span><?php echo $s[0]; ?></span></div>
        </a>
        <?php endforeach; ?>
    </div>
    <nav class="st-cats" aria-label="Staff categories">
        <a class="st-cat<?php echo $flt['category'] === '' ? ' active' : ''; ?>" href="<?php echo $h(staffUrl(['category' => '', 'page' => ''])); ?>">All categories</a>
        <?php foreach (STAFF_CATEGORIES as $k => $lbl): ?>
        <a class="st-cat<?php echo $flt['category'] === $k ? ' active' : ''; ?>" href="<?php echo $h(staffUrl(['category' => $k, 'page' => ''])); ?>"><?php echo $lbl; ?> <b><?php echo $cat_counts[$k] ?? 0; ?></b></a>
        <?php endforeach; ?>
    </nav>

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" class="st-filters" role="search">
            <div class="form-group q"><label for="f_q">Search</label><input id="f_q" type="search" name="q" value="<?php echo $h($flt['q']); ?>" placeholder="<?php echo $full ? 'Name, employee ID, designation, CNIC, mobile…' : 'Name, employee ID, designation…'; ?>"></div>
            <div class="form-group"><label for="f_cat">Category</label><select id="f_cat" name="category"><option value="">All</option><?php foreach (STAFF_CATEGORIES as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo $flt['category'] === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="f_emp">Employment</label><select id="f_emp" name="emp"><option value="">All</option><?php foreach (STAFF_EMPLOYMENT as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo $flt['emp'] === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="f_campus">Campus</label><select id="f_campus" name="campus"><option value="">All</option><?php foreach ($campuses as $c): ?><option value="<?php echo $h($c); ?>"<?php echo $flt['campus'] === $c ? ' selected' : ''; ?>><?php echo $h($c); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="f_act">Record</label><select id="f_act" name="active"><option value="">All</option><option value="1"<?php echo $flt['active'] === '1' ? ' selected' : ''; ?>>Active</option><option value="0"<?php echo $flt['active'] === '0' ? ' selected' : ''; ?>>Inactive</option></select></div>
            <div class="st-filter-btns">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <?php if (array_filter($flt, 'strlen')): ?><a href="manage_support_staff.php" class="btn btn-secondary">Reset</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="st-toolbar"><div class="st-meta"><?php echo $total; ?> staff member<?php echo $total === 1 ? '' : 's'; ?><?php echo array_filter($flt, 'strlen') ? ' match your filters' : ''; ?></div></div>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Staff Member</th><th>Employee ID</th><th>Category / Designation</th><th>CNIC &amp; Mobile</th><th>Employment</th><th>Record</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $n = 0; while ($s = mysqli_fetch_assoc($rows)): $n++;
                $emp = $s['employment_status'];
                $emp_cls = in_array($emp, ['resigned', 'retired', 'terminated'], true) ? ' bad' : ($emp === 'on_leave' ? ' warn' : ''); ?>
                <tr>
                    <td><div class="st-person"><span class="st-av"><?php echo staffAvatar($s, $h); ?></span>
                        <div><strong><?php echo $h($s['full_name']); ?></strong><small>s/o <?php echo $h($s['father_name']); ?></small></div></div></td>
                    <td><span class="st-id"><?php echo $h($s['employee_id']); ?></span><?php if ($s['campus']): ?><span class="st-sub"><?php echo $h($s['campus']); ?></span><?php endif; ?></td>
                    <td><?php echo $h(STAFF_CATEGORIES[$s['category']] ?? $s['category']); ?><?php if ($s['designation']): ?><span class="st-sub"><?php echo $h($s['designation']); ?></span><?php endif; ?></td>
                    <td><span style="white-space:nowrap;"><?php echo $h($cnic($s['cnic'])); ?></span><span class="st-sub"><?php echo $h($phone($s['mobile'])); ?></span></td>
                    <td><span class="badge badge-emp<?php echo $emp_cls; ?>"><?php echo $h(STAFF_EMPLOYMENT[$emp] ?? $emp); ?></span><?php if ($s['joining_date']): ?><span class="st-sub">since <?php echo $h(date('M Y', strtotime($s['joining_date']))); ?></span><?php endif; ?></td>
                    <td><span class="badge <?php echo $s['is_active'] ? 'badge-on' : 'badge-off'; ?>"><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td class="st-actions">
                        <a href="manage_support_staff.php?view=<?php echo (int) $s['id']; ?>" class="action-btn btn-view" title="View profile" aria-label="View profile of <?php echo $h($s['full_name']); ?>"><i class="fas fa-eye"></i></a>
                        <?php if ($full): ?>
                        <a href="manage_support_staff.php?edit=<?php echo (int) $s['id']; ?>" class="action-btn btn-edit" title="Edit" aria-label="Edit <?php echo $h($s['full_name']); ?>"><i class="fas fa-pen"></i></a>
                        <form method="POST" class="st-inline"><?php echo csrf_field(); ?>
                            <input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>"><input type="hidden" name="return" value="<?php echo $h($return_url); ?>">
                            <button type="submit" class="action-btn <?php echo $s['is_active'] ? 'btn-reject' : 'btn-approve'; ?>" title="<?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>" aria-label="<?php echo ($s['is_active'] ? 'Deactivate ' : 'Activate ') . $h($s['full_name']); ?>"><i class="fas <?php echo $s['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$n): ?>
                <tr><td colspan="7" class="st-empty"><i class="fas fa-people-carry-box" style="font-size:28px;display:block;margin-bottom:8px;color:var(--border-color);"></i><?php echo array_filter($flt, 'strlen') ? 'No staff members match these filters.' : 'No support staff added yet.' . ($full ? ' Use “Add Staff” to create the first record.' : ''); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="st-pager" aria-label="Pages">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?><span class="cur" aria-current="page"><?php echo $p; ?></span>
                <?php else: ?><a href="<?php echo $h(staffUrl(['page' => $p])); ?>"><?php echo $p; ?></a><?php endif; ?>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

</div>
</body>
</html>
