<?php
// TEMPLATE — copy this file to includes/config.php and fill in real values.
// includes/config.php is listed in .gitignore so real passwords never reach GitHub.
// =====================================================================
// Database Configuration — auto-detects localhost vs. live cPanel so
// this file never needs to be hand-edited when you deploy or pull down
// a fresh copy. Detection is based on the request's HTTP_HOST: XAMPP/
// WAMP/local dev always answers on localhost/127.0.0.1/::1, anything
// else (your real domain on cPanel) falls through to the live branch.
// =====================================================================
$isLocalEnv = in_array(
    $_SERVER['HTTP_HOST'] ?? '',
    ['localhost', '127.0.0.1', '::1'],
    true
);

if ($isLocalEnv) {
    // ---------------------------------------------------------------
    // LOCALHOST DATABASE (XAMPP/WAMP) — used automatically while you're
    // developing on your own machine. Edit only if your local MySQL
    // credentials or database name differ from the XAMPP defaults.
    // ---------------------------------------------------------------
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'your_local_database');
} else {
    // ---------------------------------------------------------------
    // LIVE CPANEL DATABASE — used automatically once this same file is
    // uploaded to your cPanel hosting. Replace the three placeholders
    // below with the database name/username/password shown in cPanel's
    // "MySQL Databases" section (DB_HOST stays 'localhost' — that's the
    // standard value cPanel expects, not this machine's localhost).
    // ---------------------------------------------------------------
    define('DB_HOST', 'localhost');
    define('DB_USER', 'your_cpanel_db_user');
    define('DB_PASS', 'your_cpanel_db_password');
    define('DB_NAME', 'your_cpanel_db_name');
}

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed (" . ($isLocalEnv ? 'localhost' : 'live') . " environment): " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Auto-compression for uploaded images — every upload handler (public forms
// and AdminCP) calls compressUploadedImage() on the saved file; loaded here
// once since every page already includes config.php.
require_once __DIR__ . '/image_helper.php';

// Site Configuration
define('SITE_NAME', 'IQRA School & College Daggar Buner');

// SITE_URL auto-detects localhost vs. live the same way the DB credentials
// above do, so canonical URLs / social sharing tags / sitemap links are
// never accidentally left pointing at a local dev address in production.
$_siteScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_siteHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_siteBasePath = $isLocalEnv ? '/' . basename(dirname(__DIR__)) . '/' : '/';  // local: this project's folder name
define('SITE_URL', $_siteScheme . '://' . $_siteHost . $_siteBasePath);
define('ADMIN_EMAIL', 'principaliqradaggar@gmail.com');
define('SITE_LOCATION', 'iqra school and college daggar, Bunerwal, Pakistan');
define('SITE_CONTACT', '0333 9692343');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include settings helper functions
require_once __DIR__ . '/settings_helper.php';

// Include theme helper functions
require_once __DIR__ . '/theme_helper.php';

// Include ERP integration adapter (safe no-op until configured in AdminCP > ERP Integration)
require_once __DIR__ . '/erp_integration.php';
?>
