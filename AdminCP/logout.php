<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';
require_once '../includes/audit_helper.php';
if (!empty($_SESSION['admin_logged_in'])) audit_log($conn, 'logout', 'auth', $_SESSION['admin_username'] ?? null, 'Signed out');
session_unset();
session_destroy();
header('Location: login.php');
exit;
?>
