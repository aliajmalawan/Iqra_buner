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
require_once '../includes/alumni_helper.php';
require_once '../includes/audit_helper.php';

alumni_ensure_schema($conn);
$program_options = alumni_program_options($conn);
$BASE = '../';

// Moderators may review (view + change status); editing, deleting and export need an admin role
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$can_manage = in_array($admin_role, ['super_admin', 'admin'], true);

/* ── Filters (shared by the list, pagination and CSV export) ── */
$flt = [
    'q'       => trim((string) ($_GET['q'] ?? '')),
    'year'    => preg_match('/^\d{4}$/', $_GET['year'] ?? '') ? (int) $_GET['year'] : 0,
    'program' => trim((string) ($_GET['program'] ?? '')),
    'status'  => isset(ALUMNI_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '',
];
function alumniWhere($flt) {
    $sql = []; $types = ''; $args = [];
    if ($flt['q'] !== '') {
        $like = '%' . $flt['q'] . '%';
        $sql[] = '(student_name LIKE ? OR father_name LIKE ? OR email LIKE ? OR mobile_number LIKE ? OR current_job LIKE ? OR job_department LIKE ? OR job_city LIKE ?)';
        $types .= str_repeat('s', 7); array_push($args, $like, $like, $like, $like, $like, $like, $like);
    }
    if ($flt['year'])           { $sql[] = 'passing_year = ?'; $types .= 'i'; $args[] = $flt['year']; }
    if ($flt['program'] !== '') { $sql[] = 'course = ?';       $types .= 's'; $args[] = $flt['program']; }
    if ($flt['status'] !== '')  { $sql[] = 'status = ?';       $types .= 's'; $args[] = $flt['status']; }
    return [$sql ? 'WHERE ' . implode(' AND ', $sql) : '', $types, $args];
}
function alumniQuery($conn, $sql, $types = '', $args = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$args);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
function alumniUrl($extra = []) {
    global $flt;
    $q = array_filter(array_merge($flt, $extra), function ($v) { return $v !== '' && $v !== 0 && $v !== null; });
    return 'manage_alumni.php' . ($q ? '?' . http_build_query($q) : '');
}
function alumniBack($msg, $err = false) {
    $ret = (string) ($_POST['return'] ?? '');
    if (!preg_match('#^manage_alumni\.php(\?[^\s<>"]*)?$#', $ret)) $ret = 'manage_alumni.php';
    $ret = preg_replace('/([?&])(msg|err)=[^&]*&?/', '$1', $ret);
    $ret = rtrim($ret, '?&');
    header('Location: ' . $ret . (strpos($ret, '?') === false ? '?' : '&') . 'msg=' . urlencode($msg) . ($err ? '&err=1' : ''));
    exit;
}
function alumniFetch($conn, $id) {
    $row = mysqli_fetch_assoc(alumniQuery($conn, 'SELECT * FROM alumni WHERE id = ?', 'i', [$id]));
    return $row ?: null;
}

/* ── CSV export (current filters) ── */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$can_manage) { http_response_code(403); exit('Not allowed.'); }
    [$where, $types, $args] = alumniWhere($flt);
    try {
        $res = alumniQuery($conn, "SELECT * FROM alumni $where ORDER BY created_at DESC", $types, $args);
    } catch (Throwable $e) {
        error_log('[alumni] export: ' . $e->getMessage());
        header('Location: manage_alumni.php?msg=' . urlencode('The export could not be created. Please try again.') . '&err=1');
        exit;
    }
    audit_log($conn, 'export', 'alumni', null, 'CSV export of ' . mysqli_num_rows($res) . ' record(s)' . (array_filter($flt) ? ' (filtered)' : ''));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="iqra-alumni-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
    $safe = function ($v) { $v = (string) $v; return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; }; // no formula injection
    fputcsv($out, ['ID', 'Full Name', "Father's Name", 'Email', 'Mobile', 'WhatsApp', 'Passing Year', 'Last Class / Program',
                   'Occupation', 'Organization', 'City', 'Country', 'Show in Directory', 'Status', 'Additional Information', 'Admin Notes', 'Registered On']);
    while ($r = mysqli_fetch_assoc($res)) {
        fputcsv($out, array_map($safe, [$r['id'], $r['student_name'], $r['father_name'], $r['email'], $r['mobile_number'], $r['whatsapp_number'],
            $r['passing_year'], $r['course'], $r['current_job'], $r['job_department'], $r['job_city'], $r['country'],
            $r['show_public'] ? 'Yes' : 'No', ucfirst($r['status']), $r['review'], $r['admin_notes'], $r['created_at']]));
    }
    fclose($out);
    exit;
}

/* ── Actions (all POST + CSRF via csrf_guard) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $act = $_POST['act'] ?? '';

    if ($act === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = $_POST['status'] ?? '';
        if (!isset(ALUMNI_STATUSES[$st]) || !alumniFetch($conn, $id)) alumniBack('Record not found.', true);
        alumniQuery($conn, 'UPDATE alumni SET status = ? WHERE id = ?', 'si', [$st, $id]);
        audit_log($conn, 'status_change', 'alumni', '#' . $id, 'Status set to ' . ALUMNI_STATUSES[$st]);
        alumniBack('Status changed to ' . ALUMNI_STATUSES[$st] . '.');
    }

    if ($act === 'bulk') {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))));
        $do = $_POST['bulk_action'] ?? '';
        if (!$ids) alumniBack('Select at least one record first.', true);
        if (isset(ALUMNI_STATUSES[$do])) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            alumniQuery($conn, "UPDATE alumni SET status = ? WHERE id IN ($ph)", 's' . str_repeat('i', count($ids)), array_merge([$do], $ids));
            audit_log($conn, 'bulk_status', 'alumni', count($ids) . ' records', 'Status set to ' . ALUMNI_STATUSES[$do] . ' for #' . implode(', #', $ids));
            alumniBack(count($ids) . ' record(s) marked ' . ALUMNI_STATUSES[$do] . '.');
        }
        if ($do === 'delete') {
            if (!$can_manage) alumniBack('Only administrators can delete records.', true);
            foreach ($ids as $id) {
                $row = alumniFetch($conn, $id);
                if ($row) {
                    alumniQuery($conn, 'DELETE FROM alumni WHERE id = ?', 'i', [$id]);
                    alumni_delete_photo($row['photo'], $BASE);
                    audit_log($conn, 'delete', 'alumni', '#' . $id, 'Bulk delete: ' . $row['student_name'] . ' (' . $row['passing_year'] . ')');
                }
            }
            alumniBack(count($ids) . ' record(s) deleted.');
        }
        alumniBack('Choose a bulk action.', true);
    }

    if ($act === 'delete') {
        if (!$can_manage) alumniBack('Only administrators can delete records.', true);
        $row = alumniFetch($conn, (int) ($_POST['id'] ?? 0));
        if (!$row) alumniBack('Record not found.', true);
        alumniQuery($conn, 'DELETE FROM alumni WHERE id = ?', 'i', [$row['id']]);
        alumni_delete_photo($row['photo'], $BASE);
        audit_log($conn, 'delete', 'alumni', '#' . $row['id'], 'Deleted ' . $row['student_name'] . ' (' . $row['passing_year'] . ')');
        alumniBack('Alumni record deleted.');
    }

    if ($act === 'save') {
        if (!$can_manage) alumniBack('Only administrators can edit records.', true);
        $id = (int) ($_POST['id'] ?? 0);
        $old = $id ? alumniFetch($conn, $id) : null;
        if ($id && !$old) alumniBack('Record not found.', true);

        [$data, $errors] = alumni_validate($_POST, $program_options, true);
        $status = isset(ALUMNI_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : 'pending';
        $notes = mb_substr(trim((string) ($_POST['admin_notes'] ?? '')), 0, 2000);
        $whatsapp = alumni_clean($_POST['whatsapp'] ?? '', 20);
        if (!$errors && alumni_find_duplicate($conn, $data['email'], $data['mobile_number'], $id)) {
            $errors['email'] = 'Another active record already uses this email or mobile number.';
        }
        $photo = $old['photo'] ?? null;
        if (!$errors) {
            [$new_photo, $photo_err] = alumni_save_photo($_FILES['photo'] ?? null, $BASE);
            if ($photo_err) $errors['photo'] = $photo_err;
            elseif ($new_photo) { alumni_delete_photo($photo, $BASE); $photo = $new_photo; }
            elseif (!empty($_POST['remove_photo'])) { alumni_delete_photo($photo, $BASE); $photo = null; }
        }
        if ($errors) {
            $_SESSION['alumni_edit_errors'] = $errors;
            $_SESSION['alumni_edit_post'] = $_POST;
            header('Location: manage_alumni.php?edit=' . ($id ?: 'new'));
            exit;
        }
        $vals = [$data['student_name'], $data['father_name'], $data['email'], $data['mobile_number'], $whatsapp, $data['passing_year'],
                 $data['course'], $data['current_job'], $data['job_department'], $data['job_city'], $data['country'],
                 $photo, $data['review'], $data['show_public'], $status, $notes];
        $audit_labels = ['student_name' => 'name', 'father_name' => "father's name", 'email' => 'email', 'mobile_number' => 'mobile',
                         'whatsapp_number' => 'WhatsApp', 'passing_year' => 'passing year', 'course' => 'program', 'current_job' => 'occupation',
                         'job_department' => 'organization', 'job_city' => 'city', 'country' => 'country', 'photo' => 'photo',
                         'review' => 'additional info', 'show_public' => 'directory consent', 'status' => 'status', 'admin_notes' => 'admin notes'];
        $audit_new = array_combine(array_keys($audit_labels), $vals);
        if ($id) {
            alumniQuery($conn, 'UPDATE alumni SET student_name=?, father_name=?, email=?, mobile_number=?, whatsapp_number=?, passing_year=?, course=?,
                current_job=?, job_department=?, job_city=?, country=?, photo=?, review=?, show_public=?, status=?, admin_notes=? WHERE id=?',
                'sssssisssssssissi', array_merge($vals, [$id]));
            audit_log($conn, 'update', 'alumni', '#' . $id, audit_changed_fields($old, $audit_new, $audit_labels));
        } else {
            alumniQuery($conn, 'INSERT INTO alumni (student_name, father_name, email, mobile_number, whatsapp_number, passing_year, course,
                current_job, job_department, job_city, country, photo, review, show_public, status, admin_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                'sssssisssssssiss', $vals);
            $id = mysqli_insert_id($conn);
            audit_log($conn, 'create', 'alumni', '#' . $id, 'Added by admin: ' . $data['student_name'] . ' (' . $data['passing_year'] . ')');
        }
        header('Location: manage_alumni.php?view=' . $id . '&msg=' . urlencode('Alumni record saved.'));
        exit;
    }
    alumniBack('Unknown action.', true);
  } catch (mysqli_sql_exception $e) {
    // Meaningful message for the admin; technical detail only in the server error log
    error_log('[alumni] ' . ($act ?? '') . ': ' . $e->getMessage());
    alumniBack($e->getCode() == 1062 ? 'That email or mobile number is already used by another record.' : 'The change could not be saved because of a database error. Please try again.', true);
  }
}

/* ── Page data ── */
$message = isset($_GET['msg']) ? (string) $_GET['msg'] : '';
$is_error = isset($_GET['err']);
$view = isset($_GET['view']) ? alumniFetch($conn, (int) $_GET['view']) : null;
$edit_id = $_GET['edit'] ?? null;
$edit = null; $edit_errors = []; $edit_post = null;
if ($edit_id !== null && $can_manage) {
    $edit = $edit_id === 'new' ? ['id' => 0] : alumniFetch($conn, (int) $edit_id);
    $edit_errors = $_SESSION['alumni_edit_errors'] ?? [];
    $edit_post = $_SESSION['alumni_edit_post'] ?? null;
    unset($_SESSION['alumni_edit_errors'], $_SESSION['alumni_edit_post']);
}

$counts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$res = mysqli_query($conn, "SELECT status, COUNT(*) c FROM alumni GROUP BY status");
while ($res && ($r = mysqli_fetch_assoc($res))) { $counts[$r['status']] = (int) $r['c']; $counts['all'] += (int) $r['c']; }
$years = [];
$res = mysqli_query($conn, "SELECT DISTINCT passing_year FROM alumni ORDER BY passing_year DESC");
while ($res && ($r = mysqli_fetch_row($res))) $years[] = (int) $r[0];
$programs = [];
$res = mysqli_query($conn, "SELECT DISTINCT course FROM alumni ORDER BY course");
while ($res && ($r = mysqli_fetch_row($res))) $programs[] = $r[0];

$per_page = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
[$where, $types, $args] = alumniWhere($flt);
$total = (int) mysqli_fetch_row(alumniQuery($conn, "SELECT COUNT(*) FROM alumni $where", $types, $args))[0];
$pages = max(1, (int) ceil($total / $per_page));
$page = min($page, $pages);
$offset = ($page - 1) * $per_page;
$rows = alumniQuery($conn, "SELECT * FROM alumni $where ORDER BY created_at DESC, id DESC LIMIT $per_page OFFSET $offset", $types, $args);
$return_url = alumniUrl(['page' => $page > 1 ? $page : '']);

$h = function ($v) { return htmlspecialchars((string) $v); };
function alumniInitials($name) {
    $ini = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $p) $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    return $ini ?: '?';
}
function alumniStatusForm($id, $status, $return, $with_text = false) {
    $out = '';
    $btn = ['approved' => ['btn-approve', 'fa-check', 'Approve'], 'rejected' => ['btn-reject', 'fa-xmark', 'Reject'], 'pending' => ['btn-view', 'fa-clock', 'Mark pending']];
    foreach ($btn as $st => $b) {
        if ($st === $status) continue;
        $out .= '<form method="POST" class="al-inline">' . csrf_field()
              . '<input type="hidden" name="act" value="status"><input type="hidden" name="id" value="' . (int) $id . '">'
              . '<input type="hidden" name="status" value="' . $st . '"><input type="hidden" name="return" value="' . htmlspecialchars($return) . '">'
              . '<button type="submit" class="action-btn ' . $b[0] . '" title="' . $b[2] . '" aria-label="' . $b[2] . '"><i class="fas ' . $b[1] . '" aria-hidden="true"></i>' . ($with_text ? ' ' . $b[2] : '') . '</button></form>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Alumni Portal - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-light); }
        .al-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
        .al-head h1 { margin:0; }
        .al-head-btns { display:flex; gap:10px; flex-wrap:wrap; }
        .al-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:22px; }
        .al-stat { display:flex; align-items:center; gap:14px; padding:16px 18px; text-decoration:none; color:inherit; border:1px solid var(--border-color); border-radius:12px; background:#fff; transition:border-color .2s, box-shadow .2s; }
        .al-stat:hover, .al-stat.active { border-color:var(--accent-color); box-shadow:0 0 0 3px rgba(11,166,201,.12); }
        .al-stat i { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
        .al-stat strong { display:block; font-size:22px; color:var(--primary-color); line-height:1.1; }
        .al-stat span { font-size:13px; color:var(--text-light); }
        .al-ic-all { background:var(--light-blue); color:var(--accent-text); }
        .al-ic-pending { background:#FFFBEB; color:#B45309; }
        .al-ic-approved { background:#F0FDF4; color:#15803D; }
        .al-ic-rejected { background:#FEF2F2; color:#B91C1C; }

        .al-filters { display:grid; grid-template-columns:2fr 1fr 1.4fr 1fr auto; gap:12px; align-items:end; }
        .al-filters .form-group { margin:0; }
        .al-filter-btns { display:flex; gap:8px; }
        .al-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .al-bulk { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .al-bulk select { min-height:38px; padding:6px 12px; border:1px solid var(--border-color); border-radius:8px; font-family:inherit; }
        .al-meta { font-size:13px; color:var(--text-light); }

        .al-person { display:flex; align-items:center; gap:12px; min-width:190px; }
        .al-av { width:42px; height:42px; border-radius:50%; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--primary-color); color:#fff; font-weight:700; font-size:14px; }
        .al-av img { width:100%; height:100%; object-fit:cover; }
        .al-person small { display:block; color:var(--text-light); font-size:12.5px; }
        .al-inline { display:inline; margin:0; }
        .al-actions { min-width:84px; }
        .al-actions .action-btn { margin:2px; }
        .data-table td { vertical-align:middle; }
        .al-contact { word-break:break-all; min-width:150px; }
        .status-pending { background:#FFFBEB; color:#B45309; }
        .status-approved { background:#F0FDF4; color:#15803D; }
        .status-rejected { background:#FEF2F2; color:#B91C1C; }
        .al-pub { font-size:12px; color:var(--accent-text); }
        .al-empty { text-align:center; padding:40px 16px; color:var(--text-light); }
        .al-pager { display:flex; gap:6px; justify-content:center; flex-wrap:wrap; margin-top:18px; }
        .al-pager a, .al-pager span { min-width:36px; height:36px; padding:0 10px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid var(--border-color); text-decoration:none; color:var(--primary-color); font-weight:600; font-size:13px; background:#fff; }
        .al-pager .cur { background:var(--primary-color); color:#fff; border-color:var(--primary-color); }

        .al-profile { display:grid; grid-template-columns:260px minmax(0,1fr); gap:24px; }
        .al-profile-side { text-align:center; padding:26px 20px; border-radius:12px; background:var(--primary-color); color:#fff; border-bottom:3px solid var(--gold-color); align-self:start; }
        .al-profile-side .al-av { width:120px; height:120px; margin:0 auto 14px; font-size:36px; border:3px solid rgba(255,255,255,.2); background:rgba(255,255,255,.1); }
        .al-profile-side h2 { color:#fff !important; font-size:20px; margin:0 0 4px; }
        .al-profile-side p { color:rgba(255,255,255,.75); margin:0 0 14px; font-size:14px; }
        .al-dl { display:grid; grid-template-columns:200px minmax(0,1fr); margin:0; border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
        .al-dl dt, .al-dl dd { margin:0; padding:12px 16px; border-bottom:1px solid var(--border-color); font-size:14px; }
        .al-dl dt { background:#F8FAFC; color:var(--text-light); font-weight:600; }
        .al-dl dd { color:var(--text-color); word-break:break-word; white-space:pre-line; }
        .al-dl dt:nth-last-of-type(1), .al-dl dd:last-of-type { border-bottom:0; }
        .al-profile-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:center; }

        .al-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px 20px; }
        .al-form-grid .span-2 { grid-column:1 / -1; }
        .al-form-grid .form-group { margin:0; }
        .al-check { display:flex; gap:10px; align-items:center; font-weight:500; }
        .al-check input { width:18px; height:18px; accent-color:var(--primary-color); }
        .field-error { color:#B91C1C; font-size:13px; margin:4px 0 0; }
        .al-note { font-size:13px; color:var(--text-light); }

        @media (max-width:1100px) { .al-filters { grid-template-columns:1fr 1fr; } .al-filters .q { grid-column:1 / -1; } }
        @media (max-width:900px) { .al-stats { grid-template-columns:1fr 1fr; } .al-profile { grid-template-columns:1fr; } }
        @media (max-width:640px) {
            .al-filters, .al-form-grid { grid-template-columns:1fr; }
            .al-dl { grid-template-columns:1fr; }
            .al-dl dt { border-bottom:0; padding-bottom:4px; }
            .al-dl dd { padding-top:0; }
        }
    </style>
</head>
<body class="admin-body">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">

<?php if ($message): ?>
    <div class="alert <?php echo $is_error ? 'alert-error' : 'alert-success'; ?>" role="status"><?php echo $h($message); ?></div>
<?php endif; ?>

<?php if ($edit !== null && ($edit['id'] === 0 || $edit)): /* ───────────── EDIT / ADD ───────────── */
    $src = $edit_post ?? [
        'full_name' => $edit['student_name'] ?? '', 'father_name' => $edit['father_name'] ?? '', 'email' => $edit['email'] ?? '',
        'mobile' => $edit['mobile_number'] ?? '', 'whatsapp' => $edit['whatsapp_number'] ?? '', 'passing_year' => $edit['passing_year'] ?? '',
        'program' => $edit['course'] ?? '', 'occupation' => $edit['current_job'] ?? '', 'organization' => $edit['job_department'] ?? '',
        'city' => $edit['job_city'] ?? '', 'country' => $edit['country'] ?? 'Pakistan', 'additional_info' => $edit['review'] ?? '',
        'show_public' => $edit['show_public'] ?? 0, 'status' => $edit['status'] ?? 'approved', 'admin_notes' => $edit['admin_notes'] ?? '',
    ];
    $sv = function ($k) use ($src) { return htmlspecialchars((string) ($src[$k] ?? '')); };
    $fe = function ($k) use ($edit_errors) { return isset($edit_errors[$k]) ? '<p class="field-error">' . htmlspecialchars($edit_errors[$k]) . '</p>' : ''; };
    $prog_list = $program_options;
    if (!in_array($src['program'] ?? '', array_merge($prog_list, ['', 'Other']), true)) $prog_list[] = $src['program'];
?>
    <div class="al-head">
        <h1><i class="fas fa-user-pen"></i> <?php echo $edit['id'] ? 'Edit Alumni Record' : 'Add Alumni Record'; ?></h1>
        <div class="al-head-btns"><a href="<?php echo $edit['id'] ? 'manage_alumni.php?view=' . (int) $edit['id'] : 'manage_alumni.php'; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Cancel</a></div>
    </div>
    <?php if ($edit_errors): ?><div class="alert alert-error">Please correct the highlighted fields.</div><?php endif; ?>
    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="act" value="save">
            <input type="hidden" name="id" value="<?php echo (int) $edit['id']; ?>">
            <div class="al-form-grid">
                <div class="form-group"><label for="e_name">Full Name *</label><input id="e_name" name="full_name" required maxlength="100" value="<?php echo $sv('full_name'); ?>"><?php echo $fe('full_name'); ?></div>
                <div class="form-group"><label for="e_father">Father's Name *</label><input id="e_father" name="father_name" required maxlength="100" value="<?php echo $sv('father_name'); ?>"><?php echo $fe('father_name'); ?></div>
                <div class="form-group"><label for="e_email">Email *</label><input id="e_email" type="email" name="email" required maxlength="150" value="<?php echo $sv('email'); ?>"><?php echo $fe('email'); ?></div>
                <div class="form-group"><label for="e_mobile">Mobile *</label><input id="e_mobile" type="tel" name="mobile" required maxlength="20" value="<?php echo $sv('mobile'); ?>"><?php echo $fe('mobile'); ?></div>
                <div class="form-group"><label for="e_wa">WhatsApp</label><input id="e_wa" type="tel" name="whatsapp" maxlength="20" value="<?php echo $sv('whatsapp'); ?>"></div>
                <div class="form-group"><label for="e_year">Passing Year *</label><input id="e_year" type="number" name="passing_year" required min="1990" max="<?php echo date('Y'); ?>" value="<?php echo $sv('passing_year'); ?>"><?php echo $fe('passing_year'); ?></div>
                <div class="form-group"><label for="e_prog">Last Class / Program *</label>
                    <select id="e_prog" name="program" required>
                        <option value="">— Select —</option>
                        <?php foreach ($prog_list as $po): ?><option value="<?php echo $h($po); ?>"<?php echo $po === ($src['program'] ?? '') ? ' selected' : ''; ?>><?php echo $h($po); ?></option><?php endforeach; ?>
                        <option value="Other">Other…</option>
                    </select><?php echo $fe('program'); ?>
                </div>
                <div class="form-group"><label for="e_other">If "Other": class / program</label><input id="e_other" name="program_other" maxlength="100" value="<?php echo $sv('program_other'); ?>"></div>
                <div class="form-group"><label for="e_occ">Occupation</label><input id="e_occ" name="occupation" maxlength="150" value="<?php echo $sv('occupation'); ?>"></div>
                <div class="form-group"><label for="e_org">Organization</label><input id="e_org" name="organization" maxlength="150" value="<?php echo $sv('organization'); ?>"></div>
                <div class="form-group"><label for="e_city">City *</label><input id="e_city" name="city" required maxlength="100" value="<?php echo $sv('city'); ?>"><?php echo $fe('city'); ?></div>
                <div class="form-group"><label for="e_country">Country *</label><input id="e_country" name="country" required maxlength="100" value="<?php echo $sv('country'); ?>"><?php echo $fe('country'); ?></div>
                <div class="form-group span-2"><label for="e_info">Additional Information (from the alumnus)</label><textarea id="e_info" name="additional_info" rows="3" maxlength="1000"><?php echo $sv('additional_info'); ?></textarea><?php echo $fe('additional_info'); ?></div>
                <div class="form-group">
                    <label for="e_photo">Profile Photo (JPG/PNG/WebP, max 2 MB)</label>
                    <?php if (!empty($edit['photo'])): ?><div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;"><span class="al-av"><img src="../<?php echo $h($edit['photo']); ?>" alt=""></span><label class="al-check"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label></div><?php endif; ?>
                    <input id="e_photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"><?php echo $fe('photo'); ?>
                </div>
                <div class="form-group"><label for="e_status">Status *</label>
                    <select id="e_status" name="status"><?php foreach (ALUMNI_STATUSES as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo ($src['status'] ?? '') === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select>
                    <label class="al-check" style="margin-top:12px;"><input type="checkbox" name="show_public" value="1"<?php echo !empty($src['show_public']) ? ' checked' : ''; ?>> Show in public Alumni Directory (only with the alumnus' consent)</label>
                </div>
                <div class="form-group span-2"><label for="e_notes">Admin Notes (private)</label><textarea id="e_notes" name="admin_notes" rows="3" maxlength="2000" placeholder="Verification details, follow-up…"><?php echo $sv('admin_notes'); ?></textarea></div>
            </div>
            <div class="form-actions" style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Record</button>
                <a href="<?php echo $edit['id'] ? 'manage_alumni.php?view=' . (int) $edit['id'] : 'manage_alumni.php'; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

<?php elseif ($view): /* ───────────── PROFILE ───────────── */ ?>
    <div class="al-head">
        <h1><i class="fas fa-id-card"></i> Alumni Profile</h1>
        <div class="al-head-btns"><a href="manage_alumni.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to list</a></div>
    </div>
    <div class="card">
        <div class="al-profile">
            <div class="al-profile-side">
                <div class="al-av"><?php if ($view['photo']): ?><img src="../<?php echo $h($view['photo']); ?>" alt=""><?php else: echo $h(alumniInitials($view['student_name'])); endif; ?></div>
                <h2><?php echo $h($view['student_name']); ?></h2>
                <p><?php echo $h($view['course']); ?> · <?php echo (int) $view['passing_year']; ?></p>
                <p><span class="status-badge status-<?php echo $h($view['status']); ?>"><?php echo $h(ALUMNI_STATUSES[$view['status']] ?? $view['status']); ?></span></p>
                <div class="al-profile-actions">
                    <?php echo alumniStatusForm($view['id'], $view['status'], 'manage_alumni.php?view=' . (int) $view['id'], true); ?>
                    <?php if ($can_manage): ?>
                    <a href="manage_alumni.php?edit=<?php echo (int) $view['id']; ?>" class="action-btn btn-edit" title="Edit"><i class="fas fa-pen"></i> Edit</a>
                    <form method="POST" class="al-inline" onsubmit="return confirm('Delete this alumni record permanently?');"><?php echo csrf_field(); ?>
                        <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?php echo (int) $view['id']; ?>"><input type="hidden" name="return" value="manage_alumni.php">
                        <button type="submit" class="action-btn btn-delete" title="Delete"><i class="fas fa-trash"></i> Delete</button></form>
                    <?php endif; ?>
                </div>
            </div>
            <dl class="al-dl">
                <dt>Full Name</dt><dd><?php echo $h($view['student_name']); ?></dd>
                <dt>Father's Name</dt><dd><?php echo $h($view['father_name']); ?></dd>
                <dt>Email</dt><dd><?php echo $view['email'] ? '<a href="mailto:' . $h($view['email']) . '">' . $h($view['email']) . '</a>' : '—'; ?></dd>
                <dt>Mobile</dt><dd><a href="tel:<?php echo $h($view['mobile_number']); ?>"><?php echo $h($view['mobile_number']); ?></a><?php if ($view['whatsapp_number']): ?> · WhatsApp <?php echo $h($view['whatsapp_number']); ?><?php endif; ?></dd>
                <dt>Passing Year</dt><dd><?php echo (int) $view['passing_year']; ?></dd>
                <dt>Last Class / Program</dt><dd><?php echo $h($view['course']); ?></dd>
                <dt>Occupation</dt><dd><?php echo $h($view['current_job'] ?: '—'); ?></dd>
                <dt>Organization</dt><dd><?php echo $h($view['job_department'] ?: '—'); ?></dd>
                <dt>City / Country</dt><dd><?php echo $h(trim(($view['job_city'] ?? '') . ', ' . ($view['country'] ?? ''), ', ') ?: '—'); ?></dd>
                <dt>Public Directory</dt><dd><?php echo $view['show_public'] ? 'Yes — listed once approved' : 'No — private record'; ?></dd>
                <dt>Additional Information</dt><dd><?php echo $h($view['review'] ?: '—'); ?></dd>
                <dt>Admin Notes</dt><dd><?php echo $h($view['admin_notes'] ?: '—'); ?></dd>
                <dt>Registered</dt><dd><?php echo $h(date('d M Y, h:i A', strtotime($view['created_at']))); ?><?php if (!empty($view['updated_at'])): ?> · updated <?php echo $h(date('d M Y', strtotime($view['updated_at']))); ?><?php endif; ?></dd>
            </dl>
        </div>
    </div>

<?php else: /* ───────────── LIST ───────────── */ ?>
    <div class="al-head">
        <h1><i class="fas fa-user-graduate"></i> Alumni Portal</h1>
        <div class="al-head-btns">
            <a href="../alumni.php" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-up-right-from-square"></i> View Page</a>
            <?php if ($can_manage): ?>
            <a href="<?php echo $h(alumniUrl(['export' => 'csv'])); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
            <a href="manage_alumni.php?edit=new" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Record</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="al-stats">
        <?php foreach (['all' => ['All Registrations', 'fa-users'], 'pending' => ['Pending Review', 'fa-clock'], 'approved' => ['Approved', 'fa-circle-check'], 'rejected' => ['Rejected', 'fa-circle-xmark']] as $k => $s): ?>
        <a class="al-stat<?php echo ($flt['status'] === $k || ($k === 'all' && $flt['status'] === '')) ? ' active' : ''; ?>" href="<?php echo $h(alumniUrl(['status' => $k === 'all' ? '' : $k, 'page' => ''])); ?>">
            <i class="fas <?php echo $s[1]; ?> al-ic-<?php echo $k; ?>" aria-hidden="true"></i>
            <div><strong><?php echo $counts[$k]; ?></strong><span><?php echo $s[0]; ?></span></div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" class="al-filters" role="search">
            <div class="form-group q"><label for="f_q">Search</label><input id="f_q" type="search" name="q" value="<?php echo $h($flt['q']); ?>" placeholder="Name, email, mobile, occupation, city…"></div>
            <div class="form-group"><label for="f_year">Passing Year</label>
                <select id="f_year" name="year"><option value="">All years</option><?php foreach ($years as $y): ?><option value="<?php echo $y; ?>"<?php echo $flt['year'] === $y ? ' selected' : ''; ?>><?php echo $y; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="f_prog">Class / Program</label>
                <select id="f_prog" name="program"><option value="">All programs</option><?php foreach ($programs as $p): ?><option value="<?php echo $h($p); ?>"<?php echo $flt['program'] === $p ? ' selected' : ''; ?>><?php echo $h($p); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="f_status">Status</label>
                <select id="f_status" name="status"><option value="">All statuses</option><?php foreach (ALUMNI_STATUSES as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo $flt['status'] === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select></div>
            <div class="al-filter-btns">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <?php if (array_filter($flt)): ?><a href="manage_alumni.php" class="btn btn-secondary">Reset</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <form method="POST" id="bulkForm" onsubmit="var a=this.bulk_action.value; if(!a){alert('Choose a bulk action.');return false;} if(!this.querySelector('input[name=&quot;ids[]&quot;]:checked')){alert('Select at least one record.');return false;} return a!=='delete' || confirm('Delete the selected records permanently?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="act" value="bulk">
            <input type="hidden" name="return" value="<?php echo $h($return_url); ?>">
            <div class="al-toolbar">
                <div class="al-bulk">
                    <label for="bulk_action" class="visually-hidden">Bulk action</label>
                    <select id="bulk_action" name="bulk_action">
                        <option value="">Bulk action…</option>
                        <option value="approved">Approve selected</option>
                        <option value="rejected">Reject selected</option>
                        <option value="pending">Mark selected pending</option>
                        <?php if ($can_manage): ?><option value="delete">Delete selected</option><?php endif; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
                </div>
                <div class="al-meta"><?php echo $total; ?> record<?php echo $total === 1 ? '' : 's'; ?><?php echo array_filter($flt) ? ' match your filters' : ''; ?></div>
            </div>
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr>
                    <th style="width:36px;"><input type="checkbox" id="checkAll" aria-label="Select all"></th>
                    <th>Alumnus</th><th>Program &amp; Year</th><th>Occupation</th><th>Contact</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php $n = 0; while ($a = mysqli_fetch_assoc($rows)): $n++; ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo (int) $a['id']; ?>" aria-label="Select <?php echo $h($a['student_name']); ?>"></td>
                        <td><div class="al-person">
                            <span class="al-av"><?php if ($a['photo']): ?><img src="../<?php echo $h($a['photo']); ?>" alt="" loading="lazy"><?php else: echo $h(alumniInitials($a['student_name'])); endif; ?></span>
                            <div><strong><?php echo $h($a['student_name']); ?></strong><small>s/o <?php echo $h($a['father_name']); ?></small></div>
                        </div></td>
                        <td><?php echo $h($a['course']); ?><br><small class="al-meta"><?php echo (int) $a['passing_year']; ?></small></td>
                        <td><?php echo $h($a['current_job'] ?: '—'); ?><?php if ($a['job_department']): ?><br><small class="al-meta"><?php echo $h($a['job_department']); ?></small><?php endif; ?></td>
                        <td class="al-contact"><?php echo $h($a['mobile_number']); ?><?php if ($a['email']): ?><br><small class="al-meta"><?php echo $h($a['email']); ?></small><?php endif; ?></td>
                        <td><span class="status-badge status-<?php echo $h($a['status']); ?>"><?php echo $h(ALUMNI_STATUSES[$a['status']] ?? $a['status']); ?></span><?php if ($a['show_public']): ?><br><small class="al-pub"><i class="fas fa-globe"></i> Directory</small><?php endif; ?><br><small class="al-meta"><?php echo $h(date('d M Y', strtotime($a['created_at']))); ?></small></td>
                        <td class="al-actions">
                            <a href="manage_alumni.php?view=<?php echo (int) $a['id']; ?>" class="action-btn btn-view" title="View profile" aria-label="View profile"><i class="fas fa-eye"></i></a>
                            <?php if ($can_manage): ?><a href="manage_alumni.php?edit=<?php echo (int) $a['id']; ?>" class="action-btn btn-edit" title="Edit" aria-label="Edit"><i class="fas fa-pen"></i></a><?php endif; ?>
                            <button type="button" class="action-btn <?php echo $a['status'] === 'approved' ? 'btn-reject' : 'btn-approve'; ?>" data-status="<?php echo $a['status'] === 'approved' ? 'rejected' : 'approved'; ?>" data-id="<?php echo (int) $a['id']; ?>" title="<?php echo $a['status'] === 'approved' ? 'Reject' : 'Approve'; ?>" aria-label="<?php echo $a['status'] === 'approved' ? 'Reject' : 'Approve'; ?>"><i class="fas <?php echo $a['status'] === 'approved' ? 'fa-xmark' : 'fa-check'; ?>"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (!$n): ?>
                    <tr><td colspan="7" class="al-empty"><i class="fas fa-user-graduate" style="font-size:28px;display:block;margin-bottom:8px;color:var(--border-color);"></i><?php echo array_filter($flt) ? 'No records match these filters.' : 'No alumni registrations yet. They will appear here when former students register on the Alumni Portal page.'; ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </form>

        <?php if ($pages > 1): ?>
        <nav class="al-pager" aria-label="Pages">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?><span class="cur" aria-current="page"><?php echo $p; ?></span>
                <?php else: ?><a href="<?php echo $h(alumniUrl(['page' => $p])); ?>"><?php echo $p; ?></a><?php endif; ?>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
    </div>

    <!-- single-row status change (kept outside the bulk form: forms can't be nested) -->
    <form method="POST" id="rowStatusForm" class="al-inline">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="act" value="status"><input type="hidden" name="id" value=""><input type="hidden" name="status" value="">
        <input type="hidden" name="return" value="<?php echo $h($return_url); ?>">
    </form>
<?php endif; ?>

</div>
<script>
(function () {
    var all = document.getElementById('checkAll');
    if (all) all.addEventListener('change', function () {
        document.querySelectorAll('#bulkForm input[name="ids[]"]').forEach(function (c) { c.checked = all.checked; });
    });
    var rf = document.getElementById('rowStatusForm');
    document.querySelectorAll('button[data-status]').forEach(function (b) {
        b.addEventListener('click', function () {
            rf.elements.id.value = b.dataset.id; rf.elements.status.value = b.dataset.status; rf.submit();
        });
    });
})();
</script>
</body>
</html>
