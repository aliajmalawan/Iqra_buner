<?php
/**
 * Audit log for sensitive AdminCP actions (who did what, when, from where).
 * Viewed in AdminCP > System > Audit Log (admins / super admins only).
 *
 * Never stores passwords, CNIC numbers, phone numbers or other personal values:
 * only the action, the record reference (e.g. employee ID or record #) and the
 * NAMES of fields that changed.
 */

function audit_ensure_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admin_audit_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_id INT(11) NULL,
            admin_username VARCHAR(50) NOT NULL DEFAULT '',
            admin_role VARCHAR(20) NOT NULL DEFAULT '',
            action VARCHAR(50) NOT NULL,
            entity VARCHAR(50) NOT NULL,
            entity_ref VARCHAR(60) NULL,
            details VARCHAR(500) NULL,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_audit_created (created_at),
            KEY idx_audit_entity (entity, entity_ref),
            KEY idx_audit_action (action),
            KEY idx_audit_admin (admin_username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } catch (Throwable $e) {
        error_log('[audit] schema: ' . $e->getMessage());
    }
}

/**
 * Records one action. Never throws and never blocks the action itself:
 * a logging failure is written to the PHP error log instead.
 *
 * @param string      $action  e.g. 'create', 'update', 'delete', 'status_change', 'export', 'login_failed'
 * @param string      $entity  e.g. 'alumni', 'support_staff', 'settings', 'auth'
 * @param string|null $ref     record reference shown to admins (record #, employee ID, username)
 * @param string      $details short, non-sensitive description
 * @param array|null  $who     override the actor (used by login before the session exists)
 */
function audit_log($conn, $action, $entity, $ref = null, $details = '', $who = null) {
    try {
        audit_ensure_schema($conn);
        $admin_id = $who['id'] ?? ($_SESSION['admin_id'] ?? null);
        $username = mb_substr((string) ($who['username'] ?? ($_SESSION['admin_username'] ?? '')), 0, 50);
        $role     = mb_substr((string) ($who['role'] ?? ($_SESSION['admin_role'] ?? '')), 0, 20);
        $ip       = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); // not X-Forwarded-For: that header can be forged
        $agent    = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $action   = mb_substr((string) $action, 0, 50);
        $entity   = mb_substr((string) $entity, 0, 50);
        $ref      = $ref === null ? null : mb_substr((string) $ref, 0, 60);
        $details  = mb_substr(trim((string) $details), 0, 500);
        $admin_id = $admin_id === null ? null : (int) $admin_id;

        $stmt = mysqli_prepare($conn, "INSERT INTO admin_audit_log
            (admin_id, admin_username, admin_role, action, entity, entity_ref, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issssssss', $admin_id, $username, $role, $action, $entity, $ref, $details, $ip, $agent);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
        error_log('[audit] ' . $e->getMessage());
    }
}

/** Names of the fields whose value changed between two rows (values are never logged). */
function audit_changed_fields(array $old, array $new, array $labels) {
    $out = [];
    foreach ($labels as $col => $label) {
        if (array_key_exists($col, $new) && (string) ($old[$col] ?? '') !== (string) ($new[$col] ?? '')) $out[] = $label;
    }
    return $out ? 'Changed: ' . implode(', ', $out) : 'No field changes';
}
