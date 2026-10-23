<?php
require_once 'includes/config.php';
$page_title = 'Campus Portal';
$page_description = 'Log in to the IQRA School & College Daggar Buner campus portal (EduPortal) for students and staff.';

$eduportal = getEduPortalLinks();
// URL: AdminCP > EduPortal & ERP > Campus Login URL (falls back to the Admin Portal login)
$ep = [
    'url'        => $eduportal['campus'] ?: $eduportal['admin'],
    'icon'       => 'fa-graduation-cap',
    'badge'      => 'Campus Portal',
    'title'      => 'Campus <span>Login</span>',
    'intro'      => 'Students and staff sign in to EduPortal, the school management system, for results, attendance, fee status and academic records.',
    'card_title' => 'Student & Staff Login',
    'button'     => 'Login to Campus Portal',
    'points'     => [
        ['fa-window-restore', 'The login opens on EduPortal\'s own secure page in a new tab — sign in there with your EduPortal email and password.'],
        ['fa-key', 'Forgot your password? Use <strong>Forgot Password?</strong> on the EduPortal login page, or ask the school office.'],
        ['fa-shield-halved', 'This website never sees or stores your password.'],
    ],
    'help'       => [
        ['index.php', 'fa-house', 'Back to Website'],
        ['contact.php', 'fa-headset', 'Need Help?'],
    ],
];
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/eduportal_login.php'; ?>
<?php include 'includes/footer.php'; ?>
