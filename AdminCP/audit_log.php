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
require_once '../includes/audit_helper.php';
audit_ensure_schema($conn);

// Admins and super admins only
if (!in_array($_SESSION['admin_role'] ?? 'admin', ['super_admin', 'admin'], true)) {
    audit_log($conn, 'denied', 'audit_log', null, 'Tried to open the audit log without permission');
    http_response_code(403);
    exit('<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#243247;"><h2>Access denied</h2><p>Only administrators can view the audit log.</p><a href="dashboard.php">&larr; Back to dashboard</a></div>');
}

$ENTITY_LABELS = [
    'auth' => 'Sign-in', 'alumni' => 'Alumni', 'support_staff' => 'Support Staff',
    'eduportal_links' => 'EduPortal Links', 'erp_settings' => 'ERP Settings', 'audit_log' => 'Audit Log',
];
$f = [
    'q'      => trim((string) ($_GET['q'] ?? '')),
    'entity' => preg_match('/^[a-z_]{1,50}$/', $_GET['entity'] ?? '') ? $_GET['entity'] : '',
    'action' => preg_match('/^[a-z_]{1,50}$/', $_GET['action_f'] ?? '') ? $_GET['action_f'] : '',
    'user'   => trim((string) ($_GET['user'] ?? '')),
    'from'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '',
    'to'     => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '',
];
$where = []; $types = ''; $args = [];
if ($f['q'] !== '')      { $where[] = '(details LIKE ? OR entity_ref LIKE ?)'; $types .= 'ss'; $args[] = '%' . $f['q'] . '%'; $args[] = '%' . $f['q'] . '%'; }
if ($f['entity'] !== '') { $where[] = 'entity = ?'; $types .= 's'; $args[] = $f['entity']; }
if ($f['action'] !== '') { $where[] = 'action = ?'; $types .= 's'; $args[] = $f['action']; }
if ($f['user'] !== '')   { $where[] = 'admin_username = ?'; $types .= 's'; $args[] = $f['user']; }
if ($f['from'] !== '')   { $where[] = 'created_at >= ?'; $types .= 's'; $args[] = $f['from'] . ' 00:00:00'; }
if ($f['to'] !== '')     { $where[] = 'created_at <= ?'; $types .= 's'; $args[] = $f['to'] . ' 23:59:59'; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
function auditQuery($conn, $sql, $types, $args) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$args);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
$qs = function ($extra = []) use ($f) {
    $m = array_merge(array_filter(['q' => $f['q'], 'entity' => $f['entity'], 'action_f' => $f['action'], 'user' => $f['user'], 'from' => $f['from'], 'to' => $f['to']], 'strlen'), array_filter($extra, 'strlen'));
    return 'audit_log.php' . ($m ? '?' . http_build_query($m) : '');
};

/* CSV export */
if (($_GET['export'] ?? '') === 'csv') {
    $res = auditQuery($conn, "SELECT * FROM admin_audit_log $where_sql ORDER BY id DESC LIMIT 20000", $types, $args);
    audit_log($conn, 'export', 'audit_log', null, 'Audit log CSV export (' . mysqli_num_rows($res) . ' rows)');
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="iqra-audit-log-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $safe = function ($v) { $v = (string) $v; return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v; };
    fputcsv($out, ['Date & Time', 'User', 'Role', 'Action', 'Area', 'Record', 'Details', 'IP Address']);
    while ($r = mysqli_fetch_assoc($res)) {
        fputcsv($out, array_map($safe, [$r['created_at'], $r['admin_username'], $r['admin_role'], $r['action'], $ENTITY_LABELS[$r['entity']] ?? $r['entity'], $r['entity_ref'], $r['details'], $r['ip_address']]));
    }
    fclose($out);
    exit;
}

$per_page = 50;
$total = (int) mysqli_fetch_row(auditQuery($conn, "SELECT COUNT(*) FROM admin_audit_log $where_sql", $types, $args))[0];
$pages = max(1, (int) ceil($total / $per_page));
$page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
$rows = auditQuery($conn, "SELECT * FROM admin_audit_log $where_sql ORDER BY id DESC LIMIT $per_page OFFSET " . (($page - 1) * $per_page), $types, $args);

$actions = []; $users = [];
$r = mysqli_query($conn, "SELECT DISTINCT action FROM admin_audit_log ORDER BY action");
while ($r && ($x = mysqli_fetch_row($r))) $actions[] = $x[0];
$r = mysqli_query($conn, "SELECT DISTINCT admin_username FROM admin_audit_log WHERE admin_username <> '' ORDER BY admin_username LIMIT 200");
while ($r && ($x = mysqli_fetch_row($r))) $users[] = $x[0];
$h = function ($v) { return htmlspecialchars((string) $v); };
$tone = function ($a) {
    if (in_array($a, ['delete', 'login_failed', 'account_locked', 'login_blocked', 'denied', 'delete_blocked'], true)) return 'bad';
    if (in_array($a, ['export', 'deactivate', 'bulk_status'], true)) return 'warn';
    if (in_array($a, ['create', 'login', 'activate'], true)) return 'ok';
    return 'info';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Audit Log - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-light); }
        .au-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        .au-head h1 { margin:0; }
        .au-intro { color:var(--text-light); font-size:14px; margin:0 0 20px; max-width:820px; line-height:1.6; }
        .au-filters { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
        .au-filters .form-group { margin:0; }
        .au-btns { display:flex; gap:8px; }
        .au-meta { font-size:13px; color:var(--text-light); margin-bottom:12px; }
        .au-time { white-space:nowrap; font-size:13px; }
        .au-time small { display:block; color:var(--text-light); }
        .au-user strong { display:block; }
        .au-user small { color:var(--text-light); font-size:12px; }
        .au-act { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; white-space:nowrap; }
        .au-ok { background:#F0FDF4; color:#15803D; } .au-bad { background:#FEF2F2; color:#B91C1C; }
        .au-warn { background:#FFFBEB; color:#B45309; } .au-info { background:var(--light-blue); color:var(--accent-text); }
        .au-ref { font-family:var(--font-heading); font-weight:600; color:var(--primary-color); font-size:13px; }
        .au-details { font-size:13.5px; color:var(--text-color); max-width:480px; word-break:break-word; }
        .au-ip { font-size:12.5px; color:var(--text-light); white-space:nowrap; }
        .au-empty { text-align:center; padding:36px 16px; color:var(--text-light); }
        .au-pager { display:flex; gap:6px; justify-content:center; flex-wrap:wrap; margin-top:18px; }
        .au-pager a, .au-pager span { min-width:36px; height:36px; padding:0 10px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid var(--border-color); text-decoration:none; color:var(--primary-color); font-weight:600; font-size:13px; background:#fff; }
        .au-pager .cur { background:var(--primary-color); color:#fff; border-color:var(--primary-color); }
        @media (max-width:1200px) { .au-filters { grid-template-columns:1fr 1fr 1fr; } .au-filters .q { grid-column:1 / -1; } }
        @media (max-width:640px) { .au-filters { grid-template-columns:1fr; } }
    </style>
</head>
<body class="admin-body">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="au-head">
        <h1><i class="fas fa-clipboard-list"></i> Audit Log</h1>
        <a href="<?php echo $h($qs(['export' => 'csv'])); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
    </div>
    <p class="au-intro">A permanent record of sensitive admin actions — sign-ins, alumni and support-staff changes, deletions, exports and EduPortal settings. Personal values such as CNIC or phone numbers are never written here; only which fields changed.</p>

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" class="au-filters" role="search">
            <div class="form-group q"><label for="a_q">Search</label><input id="a_q" type="search" name="q" value="<?php echo $h($f['q']); ?>" placeholder="Record, name or details…"></div>
            <div class="form-group"><label for="a_ent">Area</label><select id="a_ent" name="entity"><option value="">All</option><?php foreach ($ENTITY_LABELS as $k => $lbl): ?><option value="<?php echo $k; ?>"<?php echo $f['entity'] === $k ? ' selected' : ''; ?>><?php echo $lbl; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="a_act">Action</label><select id="a_act" name="action_f"><option value="">All</option><?php foreach ($actions as $a): ?><option value="<?php echo $h($a); ?>"<?php echo $f['action'] === $a ? ' selected' : ''; ?>><?php echo $h(ucwords(str_replace('_', ' ', $a))); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="a_user">User</label><select id="a_user" name="user"><option value="">All</option><?php foreach ($users as $u): ?><option value="<?php echo $h($u); ?>"<?php echo $f['user'] === $u ? ' selected' : ''; ?>><?php echo $h($u); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="a_from">From</label><input id="a_from" type="date" name="from" value="<?php echo $h($f['from']); ?>"></div>
            <div class="form-group"><label for="a_to">To</label><input id="a_to" type="date" name="to" value="<?php echo $h($f['to']); ?>"></div>
            <div class="au-btns">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <?php if (array_filter($f, 'strlen')): ?><a href="audit_log.php" class="btn btn-secondary">Reset</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="au-meta"><?php echo number_format($total); ?> entr<?php echo $total === 1 ? 'y' : 'ies'; ?><?php echo array_filter($f, 'strlen') ? ' match your filters' : ''; ?></div>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Area / Record</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php $n = 0; while ($row = mysqli_fetch_assoc($rows)): $n++; $ts = strtotime($row['created_at']); ?>
                <tr>
                    <td class="au-time"><?php echo $h(date('d M Y', $ts)); ?><small><?php echo $h(date('h:i:s A', $ts)); ?></small></td>
                    <td class="au-user"><strong><?php echo $h($row['admin_username'] ?: '—'); ?></strong><?php if ($row['admin_role']): ?><small><?php echo $h(str_replace('_', ' ', $row['admin_role'])); ?></small><?php endif; ?></td>
                    <td><span class="au-act au-<?php echo $tone($row['action']); ?>"><?php echo $h(ucwords(str_replace('_', ' ', $row['action']))); ?></span></td>
                    <td><?php echo $h($ENTITY_LABELS[$row['entity']] ?? $row['entity']); ?><?php if ($row['entity_ref'] !== null && $row['entity_ref'] !== ''): ?><br><span class="au-ref"><?php echo $h($row['entity_ref']); ?></span><?php endif; ?></td>
                    <td class="au-details"><?php echo $h($row['details']); ?></td>
                    <td class="au-ip"><?php echo $h($row['ip_address']); ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$n): ?><tr><td colspan="6" class="au-empty">No log entries<?php echo array_filter($f, 'strlen') ? ' match these filters' : ' yet'; ?>.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="au-pager" aria-label="Pages">
            <?php
            $show = array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pages], function ($p) use ($pages) { return $p >= 1 && $p <= $pages; }));
            sort($show); $prev = 0;
            foreach ($show as $p):
                if ($p - $prev > 1): ?><span>…</span><?php endif;
                if ($p === $page): ?><span class="cur" aria-current="page"><?php echo $p; ?></span><?php else: ?><a href="<?php echo $h($qs(['page' => (string) $p])); ?>"><?php echo $p; ?></a><?php endif;
                $prev = $p;
            endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
