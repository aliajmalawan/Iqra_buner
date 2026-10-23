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
require_once '../includes/audit_helper.php';

$message = '';

function saveErpSetting($conn, $key, $value) {
    $key = mysqli_real_escape_string($conn, $key);
    $value = mysqli_real_escape_string($conn, $value);
    $exists = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key'");
    if ($exists && mysqli_num_rows($exists) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

// EduPortal login links (Admin Portal button + Campus Login page). https only; blank hides the button.
if (isset($_POST['save_eduportal_links'])) {
    $errs = [];
    $changed = [];
    foreach (['eduportal_parents_url' => 'Parents Portal', 'eduportal_admin_url' => 'Admin Portal', 'eduportal_campus_url' => 'Campus Login'] as $key => $label) {
        $val = trim($_POST[$key] ?? '');
        if ($val !== '' && eduportalCleanUrl($val) === '') { $errs[] = $label . ' link must be a full https:// address.'; continue; }
        if ($val !== getSetting($key, '')) $changed[] = $label . ' → ' . ($val === '' ? '(blank)' : $val);
        saveErpSetting($conn, $key, $val);
    }
    if ($changed) audit_log($conn, 'update', 'eduportal_links', null, implode('; ', $changed));
    header('Location: manage_erp.php?msg=' . urlencode($errs ? implode(' ', $errs) : 'EduPortal login links saved.') . ($errs ? '&err=1' : '') . '#eduportal-links');
    exit;
}

// Save settings
if (isset($_POST['save_erp_settings'])) {
    saveErpSetting($conn, 'erp_enabled', isset($_POST['erp_enabled']) ? '1' : '0');
    saveErpSetting($conn, 'erp_provider_name', trim($_POST['erp_provider_name'] ?? 'EduPortal'));
    saveErpSetting($conn, 'erp_api_url', trim($_POST['erp_api_url'] ?? ''));
    // Only overwrite the stored API key if a new one was actually typed — avoids blanking a saved secret on resave
    if (trim($_POST['erp_api_key'] ?? '') !== '') {
        saveErpSetting($conn, 'erp_api_key', trim($_POST['erp_api_key']));
    }
    audit_log($conn, 'update', 'erp_settings', null, 'ERP sync ' . (isset($_POST['erp_enabled']) ? 'enabled' : 'disabled') . (trim($_POST['erp_api_key'] ?? '') !== '' ? '; API key replaced (value not logged)' : ''));
    header('Location: manage_erp.php?msg=' . urlencode('ERP integration settings saved.'));
    exit;
}

// Retry a single failed / not-yet-synced sync
if (isset($_GET['retry_type'], $_GET['retry_id'])) {
    $rid = intval($_GET['retry_id']);
    if ($_GET['retry_type'] === 'admission') {
        pushAdmissionToErp($conn, $rid);
    } elseif ($_GET['retry_type'] === 'student') {
        pushStudentToErp($conn, $rid);
    }
    header('Location: manage_erp.php?msg=' . urlencode('Sync retried — check the status below.'));
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

$cfg = getErpConfig();
$edu_links = getEduPortalLinks();
$edu_admin_raw = getSetting('eduportal_admin_url', 'https://apps.eduportal.pk/Production/');
$edu_campus_raw = getSetting('eduportal_campus_url', 'https://apps.eduportal.pk//Production/?aims=aims');
$edu_parents_raw = getSetting('eduportal_parents_url', '');
$msg_is_error = isset($_GET['err']);

// Sync status counts
function statusCounts($conn, $table) {
    $counts = ['not_configured' => 0, 'pending' => 0, 'synced' => 0, 'failed' => 0];
    $r = mysqli_query($conn, "SELECT erp_sync_status, COUNT(*) AS c FROM $table GROUP BY erp_sync_status");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $counts[$row['erp_sync_status']] = (int) $row['c']; } }
    return $counts;
}
$admission_counts = statusCounts($conn, 'admissions');
$student_counts = statusCounts($conn, 'students');

// Recent sync attempts (anything that has actually been attempted, i.e. not the default not_configured with no timestamp)
// The `admissions` and `students` tables were created with different default collations
// (utf8mb4_unicode_ci vs utf8mb4_general_ci) — explicit COLLATE keeps the UNION legal.
$recent = mysqli_query($conn, "
    (SELECT 'admission' AS type, id, application_number COLLATE utf8mb4_general_ci AS ref, student_name COLLATE utf8mb4_general_ci AS name, erp_sync_status COLLATE utf8mb4_general_ci AS erp_sync_status, erp_sync_error COLLATE utf8mb4_general_ci AS erp_sync_error, erp_synced_at, updated_at
     FROM admissions WHERE erp_sync_status != 'not_configured' OR erp_synced_at IS NOT NULL)
    UNION ALL
    (SELECT 'student' AS type, id, registration_no COLLATE utf8mb4_general_ci AS ref, full_name COLLATE utf8mb4_general_ci AS name, erp_sync_status COLLATE utf8mb4_general_ci AS erp_sync_status, erp_sync_error COLLATE utf8mb4_general_ci AS erp_sync_error, erp_synced_at, updated_at
     FROM students WHERE erp_sync_status != 'not_configured' OR erp_synced_at IS NOT NULL)
    ORDER BY updated_at DESC LIMIT 25
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Integration - Admin Panel</title>
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
        .status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; }
        .erp-stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        .erp-stat-card { background:#fff; border-radius:12px; border:1px solid rgba(16,42,86,0.1); padding:16px 18px; }
        .erp-stat-card .num { font-size:24px; font-weight:800; color:var(--primary-color); }
        .erp-stat-card .lbl { font-size:11.5px; color:var(--text-light); text-transform:uppercase; letter-spacing:0.5px; font-weight:600; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-network-wired"></i> EduPortal &amp; ERP Integration</h1>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:14px 18px;font-size:13px;color:var(--text-dark);margin-bottom:22px;line-height:1.7;">
            <i class="fas fa-circle-info"></i> <strong>Investigation finding:</strong> This college already uses a third-party student management system, <strong><?php echo htmlspecialchars($cfg['provider'] ?: 'EduPortal'); ?></strong> (embedded as a login iframe on the public "Campus Portal" page) — but there is currently no API connection, no stored credentials, and no published API documentation for it. The form below does <strong>not</strong> fabricate a live connection. Every new admission application and every student conversion already attempts a sync automatically — while this stays disabled, that attempt is a safe no-op recorded as "Not Configured". Once you obtain real API access from your ERP provider, fill in the URL/key below and switch it on.
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $msg_is_error ? 'alert-error' : 'alert-success'; ?>">
                <i class="fas <?php echo $msg_is_error ? 'fa-triangle-exclamation' : 'fa-check-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card" id="eduportal-links" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 8px;"><i class="fas fa-user-shield"></i> EduPortal Login Links</h2>
            <p style="color:var(--text-light);font-size:14px;margin:0 0 18px;line-height:1.7;">
                The website's <strong>Parents Portal</strong> and <strong>Admin Portal</strong> buttons (header, mobile menu, footer) open EduPortal's own login pages in a new tab, where parents and administrators sign in with their EduPortal accounts.
                The website never sees or stores EduPortal passwords and keeps no copy of results, attendance or fee records. EduPortal has not provided an API or single sign-on, so no automatic login is attempted.
            </p>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="edu_parents">Parents Portal login URL <span style="font-weight:400;color:var(--text-light);">— required for the Parents Portal button</span></label>
                    <input id="edu_parents" type="url" name="eduportal_parents_url" value="<?php echo htmlspecialchars($edu_parents_raw); ?>" placeholder="https://… (EduPortal Parents Portal address given by EduPortal)">
                    <small style="color:var(--text-light);">
                    <?php if ($edu_links['parents']): ?>The Parents Portal button opens this address in a new tab. <a href="<?php echo htmlspecialchars($edu_links['parents']); ?>" target="_blank" rel="noopener noreferrer">Test link <i class="fas fa-arrow-up-right-from-square"></i></a>
                    <?php else: ?><strong style="color:#B45309;">Not set yet.</strong> Until it is, the Parents Portal button opens the website's <a href="../parents-portal.php" target="_blank" rel="noopener">Parents Portal information page</a>, which asks parents to contact the school office. Get the exact Parents Portal address from EduPortal and paste it here.<?php endif; ?></small>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="edu_admin">Admin Portal login URL</label>
                    <input id="edu_admin" type="url" name="eduportal_admin_url" value="<?php echo htmlspecialchars($edu_admin_raw); ?>" placeholder="https://… (EduPortal login address for school administrators)">
                    <small style="color:var(--text-light);">Default: EduPortal's official login page. Ask EduPortal support to confirm the exact address IQRA administrators should use; leave blank to hide the Admin Portal button.
                    <?php if ($edu_links['admin']): ?> <a href="<?php echo htmlspecialchars($edu_links['admin']); ?>" target="_blank" rel="noopener noreferrer">Test link <i class="fas fa-arrow-up-right-from-square"></i></a><?php else: ?> <strong style="color:#B91C1C;">Not set — the button is hidden.</strong><?php endif; ?></small>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="edu_campus">Campus Login (students / staff) URL</label>
                    <input id="edu_campus" type="url" name="eduportal_campus_url" value="<?php echo htmlspecialchars($edu_campus_raw); ?>" placeholder="https://…">
                    <small style="color:var(--text-light);">Shown inside the website's Campus Login page. The current address (with <code>?aims=aims</code>) came from the previous website template — please confirm it with EduPortal.
                    <?php if ($edu_links['campus']): ?> <a href="<?php echo htmlspecialchars($edu_links['campus']); ?>" target="_blank" rel="noopener noreferrer">Test link <i class="fas fa-arrow-up-right-from-square"></i></a><?php endif; ?></small>
                </div>
                <button type="submit" name="save_eduportal_links" class="btn btn-primary"><i class="fas fa-save"></i> Save Login Links</button>
            </form>
        </div>

        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Connection Settings</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="erp_enabled" value="1" <?php echo $cfg['enabled'] ? 'checked' : ''; ?> style="width:auto;">
                        Enable automatic ERP sync
                    </label>
                    <small style="color:var(--text-light);">Leave this off until you have real API credentials — applications and student records will keep saving locally either way.</small>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_erp_php_1">ERP / Provider Name</label>
                        <input id="auto_AdminCP_manage_erp_php_1" type="text" name="erp_provider_name" value="<?php echo htmlspecialchars($cfg['provider']); ?>" placeholder="e.g., EduPortal">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_erp_php_2">API Base URL</label>
                        <input id="auto_AdminCP_manage_erp_php_2" type="text" name="erp_api_url" value="<?php echo htmlspecialchars($cfg['api_url']); ?>" placeholder="API address from your ERP provider's documentation">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="auto_AdminCP_manage_erp_php_3">API Key</label>
                    <input id="auto_AdminCP_manage_erp_php_3" type="password" name="erp_api_key" placeholder="<?php echo $cfg['api_key'] !== '' ? 'Saved — leave blank to keep unchanged' : 'Not set yet'; ?>" autocomplete="off">
                    <small style="color:var(--text-light);">Sent as a Bearer token. Leave blank when saving to keep the currently stored key.</small>
                </div>
                <button type="submit" name="save_erp_settings" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>

        <div class="erp-stat-grid">
            <div class="erp-stat-card"><div class="num"><?php echo $admission_counts['synced'] + $student_counts['synced']; ?></div><div class="lbl">Synced</div></div>
            <div class="erp-stat-card"><div class="num"><?php echo $admission_counts['failed'] + $student_counts['failed']; ?></div><div class="lbl">Failed</div></div>
            <div class="erp-stat-card"><div class="num"><?php echo $admission_counts['pending'] + $student_counts['pending']; ?></div><div class="lbl">Pending</div></div>
            <div class="erp-stat-card"><div class="num"><?php echo $admission_counts['not_configured'] + $student_counts['not_configured']; ?></div><div class="lbl">Not Configured</div></div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Recent Sync Attempts</h2>
            <table class="data-table">
                <thead><tr><th>Type</th><th>Reference</th><th>Name</th><th>Status</th><th>Last Attempt</th><th>Error</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $status_colors = ['synced' => '#16A34A', 'failed' => '#DC2626', 'pending' => '#F59E0B', 'not_configured' => '#64748B']; ?>
                <?php if ($recent && mysqli_num_rows($recent) > 0): ?>
                    <?php while ($r = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><?php echo ucfirst($r['type']); ?></td>
                            <td><?php echo htmlspecialchars($r['ref'] ?: ('#' . $r['id'])); ?></td>
                            <td><?php echo htmlspecialchars($r['name']); ?></td>
                            <td><span class="status-pill" style="background:<?php echo $status_colors[$r['erp_sync_status']]; ?>;"><?php echo ucfirst(str_replace('_',' ',$r['erp_sync_status'])); ?></span></td>
                            <td><?php echo $r['erp_synced_at'] ? date('d M Y, h:i A', strtotime($r['erp_synced_at'])) : '—'; ?></td>
                            <td style="max-width:220px;white-space:normal;"><?php echo $r['erp_sync_error'] ? htmlspecialchars($r['erp_sync_error']) : '—'; ?></td>
                            <td>
                                <a href="?retry_type=<?php echo $r['type']; ?>&retry_id=<?php echo $r['id']; ?>" class="action-btn btn-edit"><i class="fas fa-rotate"></i> Retry</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-light);">
                        <i class="fas fa-network-wired" style="font-size:48px;opacity:0.3;display:block;margin-bottom:14px;"></i>
                        No sync attempts yet — these appear automatically as new admissions and student conversions happen.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
