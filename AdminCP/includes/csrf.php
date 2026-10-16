<?php
/**
 * CSRF protection for the admin panel. Requires session_start() to already
 * have run. Every state-changing request (POST forms, and GET links that
 * delete/toggle/approve something) must carry a token that matches the one
 * stored in the admin's own session, so a malicious third-party page can't
 * trigger admin actions using the admin's browser session.
 */

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify() {
    $sent = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && is_string($sent) && $sent !== '' && hash_equals($_SESSION['csrf_token'], $sent);
}

/**
 * True when the current GET request's query string names a mutating action
 * (delete_*, toggle_*, action=delete/approve/reject/etc., retry_type, or a
 * bare status-change id) rather than a read-only one like ?edit=5 or ?tab=x.
 * Centralized here so every AdminCP file can apply the same rule with one call.
 */
function csrf_get_is_mutating() {
    foreach ($_GET as $key => $value) {
        if (strpos($key, 'delete_') === 0) return true;
        if (strpos($key, 'toggle_') === 0) return true;
        // Covers action=, app_action=, alumni_action=, review_action=, tier_action=, etc.
        if ($key === 'action' || substr($key, -7) === '_action') return true;
    }
    if (isset($_GET['retry_type'], $_GET['retry_id'])) return true;
    if (isset($_GET['mark_read_id'])) return true;
    return false;
}

/**
 * Call at the top of every AdminCP page (after the admin_logged_in check):
 * rejects any POST request, or any GET request matching csrf_get_is_mutating(),
 * whose token doesn't match. Read-only GET navigation (?edit=, ?tab=, etc.)
 * is left untouched.
 */
function csrf_guard() {
    $needsCheck = ($_SERVER['REQUEST_METHOD'] === 'POST') || csrf_get_is_mutating();
    if ($needsCheck && !csrf_verify()) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#243247;">'
            . '<h2 style="color:#DC2626;">Security check failed</h2>'
            . '<p>This link or form has expired. Please go back, refresh the page, and try again.</p>'
            . '<a href="javascript:history.back()" style="color:#102A56;font-weight:600;">&larr; Go back</a>'
            . '</div>');
    }
}
