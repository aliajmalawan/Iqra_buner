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

// Save settings
if (isset($_POST['save_erp_settings'])) {
    saveErpSetting($conn, 'erp_enabled', isset($_POST['erp_enabled']) ? '1' : '0');
    saveErpSetting($conn, 'erp_provider_name', trim($_POST['erp_provider_name'] ?? 'EduPortal'));
    saveErpSetting($conn, 'erp_api_url', trim($_POST['erp_api_url'] ?? ''));
    // Only overwrite the stored API key if a new one was actually typed — avoids blanking a saved secret on resave
    if (trim($_POST['erp_api_key'] ?? '') !== '') {
        saveErpSetting($conn, 'erp_api_key', trim($_POST['erp_api_key']));
    }
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
            <h1><i class="fas fa-network-wired"></i> ERP Integration</h1>
        </div>

        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:14px 18px;font-size:13px;color:var(--text-dark);margin-bottom:22px;line-height:1.7;">
            <i class="fas fa-circle-info"></i> <strong>Investigation finding:</strong> This college already uses a third-party student management system, <strong><?php echo htmlspecialchars($cfg['provider'] ?: 'EduPortal'); ?></strong> (embedded as a login iframe on the public "Campus Portal" page) — but there is currently no API connection, no stored credentials, and no published API documentation for it. The form below does <strong>not</strong> fabricate a live connection. Every new admission application and every student conversion already attempts a sync automatically — while this stays disabled, that attempt is a safe no-op recorded as "Not Configured". Once you obtain real API access from your ERP provider, fill in the URL/key below and switch it on.
        </div>

        <?php if ($message): ?>
            <div style="background: #F0FDF4; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

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
                        <input id="auto_AdminCP_manage_erp_php_2" type="text" name="erp_api_url" value="<?php echo htmlspecialchars($cfg['api_url']); ?>" placeholder="https://apps.eduportal.pk/api/v1">
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
