<?php
require_once 'includes/config.php';
$page_title = 'Parents Login';
$page_description = 'Parents Login for IQRA School & College Daggar Buner — sign in to EduPortal to see your child\'s results, attendance and fee details.';

$eduportal = getEduPortalLinks();
// URL: AdminCP > EduPortal & ERP > Parents Portal login URL; until that is set, EduPortal's official login page
$ep = [
    'url'        => $eduportal['parents'] ?: ($eduportal['admin'] ?: $eduportal['campus']),
    'icon'       => 'fa-people-roof',
    'badge'      => 'Parents Login',
    'title'      => 'Parents <span>Login</span>',
    'intro'      => 'Follow your child\'s results, attendance and fee details through EduPortal, the school management system.',
    'card_title' => 'Parents Portal Login',
    'button'     => 'Login to Parents Portal',
    'points'     => [
        ['fa-window-restore', 'The login opens on EduPortal\'s own secure page in a new tab — sign in there with the email and password the school gave you.'],
        ['fa-user-plus', 'No account yet? See <a href="parents-portal.php" style="color:var(--accent-text);font-weight:600;">how to get access</a> or contact the school office.'],
        ['fa-shield-halved', 'This website never sees or stores your password.'],
    ],
    'help'       => [
        ['parents-portal.php', 'fa-circle-question', 'How to Get Access'],
        ['contact.php', 'fa-headset', 'Need Help?'],
    ],
];
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/eduportal_login.php'; ?>
<?php include 'includes/footer.php'; ?>
