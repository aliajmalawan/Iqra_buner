<?php
// Serves a support-staff photo to logged-in admins only. uploads/staff/ itself
// is closed to direct web access (uploads/staff/.htaccess), so staff photos are
// never reachable through a public URL.
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit;
}
require_once '../includes/support_staff_helper.php';

$stmt = mysqli_prepare($conn, 'SELECT photo FROM support_staff WHERE id = ?');
$id = (int) ($_GET['id'] ?? 0);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = db_fetch_assoc(db_stmt_result($stmt));
$path = $row['photo'] ?? '';
$file = __DIR__ . '/../' . $path;

if (!$path || strpos($path, STAFF_PHOTO_DIR) !== 0 || strpos($path, '..') !== false || !is_file($file)) {
    http_response_code(404);
    exit;
}
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!isset($types[$ext])) { http_response_code(404); exit; }

header('Content-Type: ' . $types[$ext]);
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($file);
