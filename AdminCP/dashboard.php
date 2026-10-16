<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/csrf.php';
csrf_guard();

// Fetch comprehensive statistics
$stats = [
    'total_courses' => 0,
    'total_faculty' => 0,
    'pending_admissions' => 0,
    'unread_contacts' => 0,
    'total_events' => 0,
    'total_news' => 0,
    'gallery_images' => 0,
    'approved_admissions' => 0
];

$courses_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
if ($courses_count) {
    $stats['total_courses'] = mysqli_fetch_assoc($courses_count)['count'];
}

$faculty_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM faculty WHERE status = 'active'");
if ($faculty_count) {
    $stats['total_faculty'] = mysqli_fetch_assoc($faculty_count)['count'];
}

$admissions_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM admissions WHERE status = 'pending'");
if ($admissions_count) {
    $stats['pending_admissions'] = mysqli_fetch_assoc($admissions_count)['count'];
}

$approved_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM admissions WHERE status = 'approved'");
if ($approved_count) {
    $stats['approved_admissions'] = mysqli_fetch_assoc($approved_count)['count'];
}

$contacts_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
if ($contacts_count) {
    $stats['unread_contacts'] = mysqli_fetch_assoc($contacts_count)['count'];
}

$events_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM events WHERE status = 'active'");
if ($events_count) {
    $stats['total_events'] = mysqli_fetch_assoc($events_count)['count'];
}

$news_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM news WHERE status = 'active'");
if ($news_count) {
    $stats['total_news'] = mysqli_fetch_assoc($news_count)['count'];
}

$gallery_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM gallery WHERE status = 'active'");
if ($gallery_count) {
    $stats['gallery_images'] = mysqli_fetch_assoc($gallery_count)['count'];
}

// Get recent admissions (with the course name, not just its id)
$recent_admissions = mysqli_query($conn, "SELECT a.*, COALESCE(c.name, a.course_id) AS course_name
                                          FROM admissions a
                                          LEFT JOIN courses c ON c.id = a.course_id
                                          ORDER BY a.created_at DESC LIMIT 5");

// Get recent contacts
$recent_contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");

$admin_display_name = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Administrator';
$stat_cards = [
    ['manage_academics.php?tab=courses',      'fa-book-open',          $stats['total_courses'],       'Active Courses'],
    ['manage_academics.php?tab=faculty',      'fa-chalkboard-user',    $stats['total_faculty'],       'Faculty Members'],
    ['manage_admission.php?tab=applications', 'fa-hourglass-half',     $stats['pending_admissions'],  'Pending Admissions', $stats['pending_admissions'] > 0],
    ['manage_admission.php?tab=applications', 'fa-user-check',         $stats['approved_admissions'], 'Approved Students'],
    ['manage_contact.php?tab=messages',       'fa-envelope',           $stats['unread_contacts'],     'Unread Messages', $stats['unread_contacts'] > 0],
    ['manage_student_life.php?tab=events',    'fa-calendar-days',      $stats['total_events'],        'Active Events'],
    ['manage_student_life.php?tab=news',      'fa-newspaper',          $stats['total_news'],          'Published News'],
    ['manage_gallery.php',                    'fa-images',             $stats['gallery_images'],      'Gallery Images'],
];
$quick_actions = [
    ['manage_academics.php?tab=courses',   'fa-circle-plus',    'Add Course',    'Create a new course'],
    ['manage_academics.php?tab=faculty',   'fa-user-plus',      'Add Faculty',   'Add a teacher profile'],
    ['manage_student_life.php?tab=events', 'fa-calendar-plus',  'Add Event',     'Create a new event'],
    ['manage_student_life.php?tab=news',   'fa-bullhorn',       'Publish News',  'Post an announcement'],
    ['manage_gallery.php',                 'fa-image',          'Upload Image',  'Add photos to the gallery'],
    ['settings.php',                       'fa-gear',           'Settings',      'Name, contact & social links'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars(getSiteName()); ?></title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Welcome bar */
        .dash-head {
            display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .dash-head h1 { font-size: 24px; font-weight: 700; color: var(--primary-color); margin: 0 0 4px; line-height: 1.25; }
        .dash-head p { color: var(--text-light); font-size: 14px; margin: 0; display: flex; align-items: center; gap: 8px; }
        .dash-head p i { color: var(--accent-color); }
        .dash-head-actions { display: flex; align-items: center; gap: 10px; }
        .icon-btn {
            position: relative;
            width: 42px; height: 42px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            background: #fff; border: 1px solid var(--border-color); color: var(--primary-color);
            text-decoration: none; font-size: 15px;
            transition: border-color var(--dur) ease, background-color var(--dur) ease, color var(--dur) ease;
        }
        .icon-btn:hover { border-color: var(--primary-color); background: var(--primary-color); color: #fff; }
        .icon-btn .count {
            position: absolute; top: -6px; right: -6px;
            min-width: 20px; height: 20px; padding: 0 5px; border-radius: 10px;
            background: var(--danger-color); color: #fff; border: 2px solid var(--admin-bg);
            font-size: 11px; font-weight: 700; line-height: 16px; text-align: center;
        }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
        .stat-card {
            display: flex; align-items: center; gap: 16px;
            padding: 20px; text-decoration: none; color: inherit;
            transition: border-color var(--dur) ease, box-shadow var(--dur) ease, transform var(--dur) ease;
        }
        .stat-card:hover { border-color: rgba(11,166,201,0.4); box-shadow: var(--shadow-card-hover); transform: translateY(-2px); }
        .stat-icon {
            width: 48px; height: 48px; flex-shrink: 0; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--light-blue); color: var(--accent-color); font-size: 19px;
            transition: background-color var(--dur) ease, color var(--dur) ease;
        }
        .stat-card:hover .stat-icon { background: var(--accent-color); color: #fff; }
        .stat-card.needs-attention .stat-icon { background: #FEF6E7; color: #B7791F; }
        .stat-info h3 { font-family: var(--font-heading); font-size: 26px; font-weight: 700; color: var(--primary-color); line-height: 1.1; margin: 0 0 4px; }
        .stat-info p { color: var(--text-light); font-size: 13.5px; font-weight: 500; margin: 0; }
        .stat-flag {
            margin-left: auto; align-self: flex-start;
            font-size: 11px; font-weight: 600; color: #B7791F; background: #FEF6E7;
            padding: 2px 8px; border-radius: 999px; white-space: nowrap;
        }

        /* Panels */
        .content-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; margin-bottom: 28px; }
        .card-container { padding: 22px 22px 14px; min-width: 0; }
        .card-header {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            margin-bottom: 14px;
        }
        .card-title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 600; color: var(--primary-color); margin: 0; }
        .card-title i { color: var(--accent-color); font-size: 15px; }
        .view-all-btn {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--accent-text); text-decoration: none; font-size: 13.5px; font-weight: 600;
        }
        .view-all-btn i { font-size: 11px; transition: transform var(--dur) ease; }
        .view-all-btn:hover { color: var(--primary-color); }
        .view-all-btn:hover i { transform: translateX(3px); }
        .empty-row td { text-align: center; color: var(--text-light) !important; padding: 36px 16px !important; }
        .empty-row i { display: block; font-size: 26px; color: var(--border-color); margin-bottom: 8px; }
        .muted { color: var(--text-light); font-size: 13px; }

        .badge-pending  { background: #FEF6E7; color: #975A16; }
        .badge-approved { background: #F0FDF4; color: #166534; }
        .badge-rejected { background: #FEF2F2; color: #991B1B; }
        .badge-unread   { background: var(--light-blue); color: var(--accent-text); }
        .badge-read     { background: #F1F5F9; color: #475569; }
        .badge-replied  { background: #F0FDF4; color: #166534; }

        /* Quick actions */
        .quick-actions-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; padding-bottom: 8px; }
        .action-card {
            display: flex; align-items: center; gap: 14px;
            padding: 16px; border-radius: 10px;
            border: 1px solid var(--border-color); background: #fff;
            text-decoration: none; color: inherit;
            transition: border-color var(--dur) ease, background-color var(--dur) ease;
        }
        .action-card:hover { border-color: var(--accent-color); background: #FAFDFE; }
        .action-card i {
            width: 40px; height: 40px; flex-shrink: 0; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: var(--light-blue); color: var(--accent-color); font-size: 16px;
            transition: background-color var(--dur) ease, color var(--dur) ease;
        }
        .action-card:hover i { background: var(--accent-color); color: #fff; }
        .action-card h3 { font-size: 14.5px; font-weight: 600; color: var(--primary-color); margin: 0 0 2px; }
        .action-card p { font-size: 13px; color: var(--text-light); margin: 0; }

        @media (max-width: 1280px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } .quick-actions-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 560px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-card { flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px; }
            .stat-flag { display: none; }
            .quick-actions-grid { grid-template-columns: 1fr; }
            .dash-head h1 { font-size: 20px; }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dash-head">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($admin_display_name); ?></h1>
                <p><i class="far fa-calendar"></i> <?php echo date('l, F d, Y'); ?></p>
            </div>
            <div class="dash-head-actions">
                <a href="manage_admission.php?tab=applications" class="icon-btn" title="Pending admissions" aria-label="Pending admissions: <?php echo (int) $stats['pending_admissions']; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <?php if ($stats['pending_admissions'] > 0): ?><span class="count"><?php echo (int) $stats['pending_admissions']; ?></span><?php endif; ?>
                </a>
                <a href="manage_contact.php?tab=messages" class="icon-btn" title="Unread messages" aria-label="Unread messages: <?php echo (int) $stats['unread_contacts']; ?>">
                    <i class="fas fa-envelope"></i>
                    <?php if ($stats['unread_contacts'] > 0): ?><span class="count"><?php echo (int) $stats['unread_contacts']; ?></span><?php endif; ?>
                </a>
                <a href="../index.php" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-up-right-from-square"></i> View Website
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <?php foreach ($stat_cards as $sc): $attn = !empty($sc[4]); ?>
            <a href="<?php echo $sc[0]; ?>" class="stat-card<?php echo $attn ? ' needs-attention' : ''; ?>">
                <div class="stat-icon"><i class="fas <?php echo $sc[1]; ?>"></i></div>
                <div class="stat-info">
                    <h3><?php echo (int) $sc[2]; ?></h3>
                    <p><?php echo $sc[3]; ?></p>
                </div>
                <?php if ($attn): ?><span class="stat-flag">Needs review</span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Recent activity -->
        <div class="content-grid">
            <section class="card-container">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-graduate"></i> Recent Admissions</h2>
                    <a href="manage_admission.php?tab=applications" class="view-all-btn">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Student</th><th>Course</th><th>Date</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_admissions && mysqli_num_rows($recent_admissions) > 0): ?>
                            <?php while ($admission = mysqli_fetch_assoc($recent_admissions)): $st = strtolower($admission['status']); ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admission['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admission['course_name']); ?></td>
                                <td class="muted"><?php echo date('M d, Y', strtotime($admission['created_at'])); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($st); ?>"><?php echo ucfirst(htmlspecialchars($st)); ?></span></td>
                                <td><a href="manage_admission.php?tab=applications" class="btn-view">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="empty-row"><td colspan="5"><i class="fas fa-inbox"></i>No admission applications yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="card-container">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-envelope"></i> Recent Messages</h2>
                    <a href="manage_contact.php?tab=messages" class="view-all-btn">View all <i class="fas fa-arrow-right"></i></a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th>Name</th><th>Subject</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_contacts && mysqli_num_rows($recent_contacts) > 0): ?>
                            <?php while ($contact = mysqli_fetch_assoc($recent_contacts)):
                                $subject = (string) $contact['subject'];
                                $short = mb_strlen($subject) > 30 ? mb_substr($subject, 0, 30) . '…' : $subject;
                                $st = strtolower($contact['status']); ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($contact['name']); ?></strong></td>
                                <td title="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($short); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($st); ?>"><?php echo ucfirst(htmlspecialchars($st)); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="empty-row"><td colspan="3"><i class="fas fa-inbox"></i>No messages yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>

        <!-- Quick actions -->
        <section class="card-container">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>
            <div class="quick-actions-grid">
                <?php foreach ($quick_actions as $qa): ?>
                <a href="<?php echo $qa[0]; ?>" class="action-card">
                    <i class="fas <?php echo $qa[1]; ?>"></i>
                    <div>
                        <h3><?php echo $qa[2]; ?></h3>
                        <p><?php echo $qa[3]; ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
