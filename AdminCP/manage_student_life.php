<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/csrf.php';
csrf_guard();

$valid_tabs = ['events', 'societies', 'news', 'alumni', 'alumni_reviews'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'events';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$is_error = isset($_GET['err']) && $_GET['err'] == '1';

function redirectBackStudentLife($tab, $msg, $err = false) {
    header('Location: manage_student_life.php?tab=' . urlencode($tab) . '&msg=' . urlencode($msg) . ($err ? '&err=1' : ''));
    exit;
}

/* =========================================================
   EVENTS
   ========================================================= */
$event_categories = [
    'general'     => 'General',
    'sports'      => 'Sports',
    'seminar'     => 'Seminar',
    'workshop'    => 'Workshop',
    'competition' => 'Competition',
    'cultural'    => 'Cultural',
    'community'   => 'Community',
];

if (isset($_POST['save_event'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = array_key_exists($_POST['category'] ?? '', $event_categories) ? $_POST['category'] : 'general';
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/events/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                $image_path = 'uploads/events/' . $filename;
            }
        }
    }

    if (!empty($_POST['event_id'])) {
        $id = intval($_POST['event_id']);
        if ($image_path) {
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM events WHERE id = $id"));
            if ($old && !empty($old['image']) && file_exists('../' . $old['image'])) unlink('../' . $old['image']);
            $query = "UPDATE events SET title='$title', category='$category', description='$description', event_date='$event_date', time='$time', location='$location', image='$image_path', status='$status' WHERE id=$id";
        } else {
            $query = "UPDATE events SET title='$title', category='$category', description='$description', event_date='$event_date', time='$time', location='$location', status='$status' WHERE id=$id";
        }
    } else {
        $query = "INSERT INTO events (title, category, description, event_date, time, location, image, status) VALUES ('$title', '$category', '$description', '$event_date', '$time', '$location', '$image_path', '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBackStudentLife('events', 'Event saved successfully!');
    }
    redirectBackStudentLife('events', 'Error saving event.', true);
}

if (isset($_GET['delete_event_id'])) {
    $id = intval($_GET['delete_event_id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM events WHERE id = $id"));
    if ($row && !empty($row['image']) && file_exists('../' . $row['image'])) unlink('../' . $row['image']);
    mysqli_query($conn, "DELETE FROM events WHERE id = $id");
    redirectBackStudentLife('events', 'Event deleted successfully!');
}

/* =========================================================
   STUDENT SOCIETIES
   ========================================================= */
if (isset($_POST['save_society'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $focus_area = mysqli_real_escape_string($conn, trim($_POST['focus_area'] ?? ''));
    $meeting_info = mysqli_real_escape_string($conn, trim($_POST['meeting_info'] ?? ''));
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active');

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/societies/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                $image_path = 'uploads/societies/' . $filename;
            }
        }
    }

    if ($name === '') {
        redirectBackStudentLife('societies', 'Society name is required.', true);
    }

    if (!empty($_POST['society_id'])) {
        $id = intval($_POST['society_id']);
        if ($image_path) {
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM student_societies WHERE id = $id"));
            if ($old && !empty($old['image']) && file_exists('../' . $old['image'])) unlink('../' . $old['image']);
            $query = "UPDATE student_societies SET name='$name', description='$description', focus_area='$focus_area', meeting_info='$meeting_info', image='$image_path', status='$status' WHERE id=$id";
        } else {
            $query = "UPDATE student_societies SET name='$name', description='$description', focus_area='$focus_area', meeting_info='$meeting_info', status='$status' WHERE id=$id";
        }
    } else {
        $query = "INSERT INTO student_societies (name, description, focus_area, meeting_info, image, status) VALUES ('$name', '$description', '$focus_area', '$meeting_info', '$image_path', '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBackStudentLife('societies', 'Society saved successfully!');
    }
    redirectBackStudentLife('societies', 'Error saving society.', true);
}

if (isset($_GET['delete_society_id'])) {
    $id = intval($_GET['delete_society_id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM student_societies WHERE id = $id"));
    if ($row && !empty($row['image']) && file_exists('../' . $row['image'])) unlink('../' . $row['image']);
    mysqli_query($conn, "DELETE FROM student_societies WHERE id = $id");
    redirectBackStudentLife('societies', 'Society deleted successfully!');
}

/* =========================================================
   NEWS
   ========================================================= */
if (isset($_POST['save_news'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/news/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                $image_path = 'uploads/news/' . $filename;
            }
        }
    }

    if (!empty($_POST['news_id'])) {
        $id = intval($_POST['news_id']);
        if ($image_path) {
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM news WHERE id = $id"));
            if ($old && !empty($old['image']) && file_exists('../' . $old['image'])) unlink('../' . $old['image']);
            $query = "UPDATE news SET title='$title', content='$content', author='$author', image='$image_path', status='$status' WHERE id=$id";
        } else {
            $query = "UPDATE news SET title='$title', content='$content', author='$author', status='$status' WHERE id=$id";
        }
    } else {
        $query = "INSERT INTO news (title, content, author, image, status) VALUES ('$title', '$content', '$author', '$image_path', '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBackStudentLife('news', 'News saved successfully!');
    }
    redirectBackStudentLife('news', 'Error saving news.', true);
}

if (isset($_GET['delete_news_id'])) {
    $id = intval($_GET['delete_news_id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM news WHERE id = $id"));
    if ($row && !empty($row['image']) && file_exists('../' . $row['image'])) unlink('../' . $row['image']);
    mysqli_query($conn, "DELETE FROM news WHERE id = $id");
    redirectBackStudentLife('news', 'News deleted successfully!');
}

/* =========================================================
   ALUMNI
   ========================================================= */
if (isset($_POST['add_alumni'])) {
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $current_job = mysqli_real_escape_string($conn, $_POST['current_job']);
    $job_department = mysqli_real_escape_string($conn, $_POST['job_department']);
    $job_city = mysqli_real_escape_string($conn, $_POST['job_city']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $passing_year = intval($_POST['passing_year']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $whatsapp_number = mysqli_real_escape_string($conn, $_POST['whatsapp_number']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $photo_name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['photo']['name']);
            $photo_path = 'uploads/alumni/' . $photo_name;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photo_path)) {
                compressUploadedImage('../' . $photo_path);
            }
        }
    }

    $sql = "INSERT INTO alumni (student_name, father_name, current_job, job_department, job_city, course, passing_year, photo, mobile_number, whatsapp_number, review, status)
            VALUES ('$student_name', '$father_name', '$current_job', '$job_department', '$job_city', '$course', $passing_year, '$photo_path', '$mobile_number', '$whatsapp_number', '$review', '$status')";
    if (mysqli_query($conn, $sql)) {
        redirectBackStudentLife('alumni', 'Alumni added successfully!');
    }
    redirectBackStudentLife('alumni', 'Error: ' . mysqli_error($conn), true);
}

if (isset($_GET['alumni_action']) && isset($_GET['alumni_id'])) {
    $id = intval($_GET['alumni_id']);
    $action = $_GET['alumni_action'];
    if ($action === 'approve') {
        mysqli_query($conn, "UPDATE alumni SET status = 'approved' WHERE id = $id");
        redirectBackStudentLife('alumni', 'Alumni approved!');
    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE alumni SET status = 'rejected' WHERE id = $id");
        redirectBackStudentLife('alumni', 'Alumni rejected!');
    } elseif ($action === 'delete') {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM alumni WHERE id = $id"));
        if ($row && $row['photo'] && file_exists('../' . $row['photo'])) unlink('../' . $row['photo']);
        mysqli_query($conn, "DELETE FROM alumni WHERE id = $id");
        redirectBackStudentLife('alumni', 'Alumni deleted!');
    }
}

/* =========================================================
   ALUMNI REVIEWS
   ========================================================= */
if (isset($_POST['add_review'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $passing_year = intval($_POST['passing_year']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $rating = intval($_POST['rating']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "INSERT INTO alumni_reviews (name, passing_year, review, rating, status) VALUES ('$name', $passing_year, '$review', $rating, '$status')";
    if (mysqli_query($conn, $sql)) {
        redirectBackStudentLife('alumni_reviews', 'Review added successfully!');
    }
    redirectBackStudentLife('alumni_reviews', 'Error: ' . mysqli_error($conn), true);
}

if (isset($_GET['review_action']) && isset($_GET['review_id'])) {
    $id = intval($_GET['review_id']);
    $action = $_GET['review_action'];
    if ($action === 'approve') {
        mysqli_query($conn, "UPDATE alumni_reviews SET status = 'approved' WHERE id = $id");
        redirectBackStudentLife('alumni_reviews', 'Review approved!');
    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE alumni_reviews SET status = 'rejected' WHERE id = $id");
        redirectBackStudentLife('alumni_reviews', 'Review rejected!');
    } elseif ($action === 'delete') {
        mysqli_query($conn, "DELETE FROM alumni_reviews WHERE id = $id");
        redirectBackStudentLife('alumni_reviews', 'Review deleted!');
    }
}

/* =========================================================
   DATA FOR ACTIVE TAB
   ========================================================= */
$edit_event = null;
if (isset($_GET['edit_event'])) {
    $edit_event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM events WHERE id = " . intval($_GET['edit_event'])));
}
$events_result = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date DESC, id DESC");

$edit_society = null;
if (isset($_GET['edit_society'])) {
    $edit_society = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM student_societies WHERE id = " . intval($_GET['edit_society'])));
}
$societies_result = mysqli_query($conn, "SELECT * FROM student_societies ORDER BY display_order ASC, id DESC");

$edit_news = null;
if (isset($_GET['edit_news'])) {
    $edit_news = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM news WHERE id = " . intval($_GET['edit_news'])));
}
$news_result = mysqli_query($conn, "SELECT * FROM news ORDER BY created_at DESC");

$alumni_result = mysqli_query($conn, "SELECT * FROM alumni ORDER BY created_at DESC");
$reviews_result = mysqli_query($conn, "SELECT * FROM alumni_reviews ORDER BY created_at DESC");

$tab_labels = [
    'events' => ['icon' => 'fas fa-calendar-alt', 'label' => 'Events & Activities'],
    'societies' => ['icon' => 'fas fa-people-group', 'label' => 'Student Societies'],
    'news' => ['icon' => 'fas fa-newspaper', 'label' => 'News'],
    'alumni' => ['icon' => 'fas fa-user-graduate', 'label' => 'Alumni'],
    'alumni_reviews' => ['icon' => 'fas fa-star', 'label' => 'Alumni Reviews'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Life - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg-light); }
        .tab-nav {
            display: flex; flex-wrap: wrap; gap: 8px;
            margin-bottom: 25px; border-bottom: 2px solid var(--border-color);
        }
        .tab-nav a {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 20px; text-decoration: none;
            color: var(--text-light); font-weight: 600; font-size: 14px;
            border-radius: 10px 10px 0 0; border-bottom: 3px solid transparent;
            transition: all 0.25s ease;
        }
        .tab-nav a:hover { background: rgba(16,42,86,0.06); color: var(--primary-color); }
        .tab-nav a.active { color: var(--primary-color); border-bottom-color: var(--accent-color); background: rgba(16,42,86,0.08); }

        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #fff; }
        .status-active, .status-approved { background: #16A34A; }
        .status-inactive, .status-rejected { background: #DC2626; }
        .status-pending { background: #F59E0B; color: #243247; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; }
        .data-table tr:hover { background: #F4F8FC; }
        .thumb-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .avatar-img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
        .action-btn { padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }
        .btn-approve { background: #16A34A; color: #fff; }
        .btn-reject { background: #DC2626; color: #fff; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-users"></i> Manage Student Life</h1>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo $is_error ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo $is_error ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas <?php echo $is_error ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="tab-nav">
            <?php foreach ($tab_labels as $key => $t): ?>
                <a href="?tab=<?php echo $key; ?>" class="<?php echo $active_tab === $key ? 'active' : ''; ?>">
                    <i class="<?php echo $t['icon']; ?>"></i> <?php echo $t['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($active_tab === 'events'): ?>
        <!-- ============ EVENTS TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_event ? 'Edit Event' : 'Add New Event'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_event): ?><input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_1">Event Title *</label>
                    <input id="auto_AdminCP_manage_student_life_php_1" type="text" name="title" required value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" placeholder="e.g., Annual Sports Day 2026">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_2">Description *</label>
                    <textarea id="auto_AdminCP_manage_student_life_php_2" name="description" required rows="4"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_3">Category *</label>
                        <select id="auto_AdminCP_manage_student_life_php_3" name="category">
                            <?php foreach ($event_categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($edit_event && $edit_event['category'] === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:var(--text-light);">Shown as a filter on the public Extra-Curricular Activities page.</small>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_4">Location</label>
                        <input id="auto_AdminCP_manage_student_life_php_4" type="text" name="location" value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>" placeholder="e.g., Main Ground">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_5">Event Date *</label>
                        <input id="auto_AdminCP_manage_student_life_php_5" type="date" name="event_date" required value="<?php echo $edit_event ? $edit_event['event_date'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_6">Time</label>
                        <input id="auto_AdminCP_manage_student_life_php_6" type="text" name="time" value="<?php echo $edit_event ? htmlspecialchars($edit_event['time']) : ''; ?>" placeholder="e.g., 9:00 AM - 5:00 PM">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_7">Status</label>
                        <select id="auto_AdminCP_manage_student_life_php_7" name="status">
                            <option value="active" <?php echo ($edit_event && $edit_event['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_event && $edit_event['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_8">Event Image</label>
                    <input id="auto_AdminCP_manage_student_life_php_8" type="file" name="image" accept="image/*">
                    <?php if ($edit_event && !empty($edit_event['image'])): ?>
                        <div style="margin-top:10px;"><img src="../<?php echo htmlspecialchars($edit_event['image']); ?>" style="max-width:200px;border-radius:8px;"></div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="save_event" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_event ? 'Update' : 'Add'; ?> Event</button>
                <?php if ($edit_event): ?><a href="?tab=events" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Date</th><th>Time</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
                    <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                        <tr>
                            <td>
                                <?php if (!empty($event['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($event['image']); ?>" class="thumb-img">
                                <?php else: ?>
                                    <div class="thumb-img" style="background:var(--bg-light);display:flex;align-items:center;justify-content:center;"><i class="fas fa-image" style="color:var(--text-light);"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($event_categories[$event['category']] ?? ucfirst($event['category'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                            <td><?php echo htmlspecialchars($event['time']); ?></td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td><span class="status-badge status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span></td>
                            <td>
                                <a href="?tab=events&edit_event=<?php echo $event['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=events&delete_event_id=<?php echo $event['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this event?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-light);">No events yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($active_tab === 'societies'): ?>
        <!-- ============ STUDENT SOCIETIES TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_society ? 'Edit Society' : 'Add New Student Society'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_society): ?><input type="hidden" name="society_id" value="<?php echo $edit_society['id']; ?>"><?php endif; ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_9">Society Name *</label>
                        <input id="auto_AdminCP_manage_student_life_php_9" type="text" name="name" required value="<?php echo $edit_society ? htmlspecialchars($edit_society['name']) : ''; ?>" placeholder="e.g., Debating Society">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_10">Focus Area</label>
                        <input id="auto_AdminCP_manage_student_life_php_10" type="text" name="focus_area" value="<?php echo $edit_society ? htmlspecialchars($edit_society['focus_area']) : ''; ?>" placeholder="e.g., Public speaking & debate">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_11">Description</label>
                    <textarea id="auto_AdminCP_manage_student_life_php_11" name="description" rows="3" placeholder="What this society does, who can join, etc."><?php echo $edit_society ? htmlspecialchars($edit_society['description']) : ''; ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_12">Meeting Info (optional)</label>
                        <input id="auto_AdminCP_manage_student_life_php_12" type="text" name="meeting_info" value="<?php echo $edit_society ? htmlspecialchars($edit_society['meeting_info']) : ''; ?>" placeholder="e.g., Fridays, 2 PM, Seminar Hall">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_13">Status</label>
                        <select id="auto_AdminCP_manage_student_life_php_13" name="status">
                            <option value="active" <?php echo (!$edit_society || $edit_society['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_society && $edit_society['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_14">Image (optional)</label>
                    <input id="auto_AdminCP_manage_student_life_php_14" type="file" name="image" accept="image/*">
                    <?php if ($edit_society && !empty($edit_society['image'])): ?>
                        <div style="margin-top:10px;"><img src="../<?php echo htmlspecialchars($edit_society['image']); ?>" style="max-width:200px;border-radius:8px;"></div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="save_society" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_society ? 'Update' : 'Add'; ?> Society</button>
                <?php if ($edit_society): ?><a href="?tab=societies" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Image</th><th>Name</th><th>Focus Area</th><th>Meeting Info</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($societies_result && mysqli_num_rows($societies_result) > 0): ?>
                    <?php while ($society = mysqli_fetch_assoc($societies_result)): ?>
                        <tr>
                            <td>
                                <?php if (!empty($society['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($society['image']); ?>" class="thumb-img">
                                <?php else: ?>
                                    <div class="thumb-img" style="background:var(--bg-light);display:flex;align-items:center;justify-content:center;"><i class="fas fa-people-group" style="color:var(--text-light);"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($society['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($society['focus_area'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($society['meeting_info'] ?: '—'); ?></td>
                            <td><span class="status-badge status-<?php echo $society['status']; ?>"><?php echo ucfirst($society['status']); ?></span></td>
                            <td>
                                <a href="?tab=societies&edit_society=<?php echo $society['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=societies&delete_society_id=<?php echo $society['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this society?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No student societies added yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($active_tab === 'news'): ?>
        <!-- ============ NEWS TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_news ? 'Edit News' : 'Add New News/Announcement'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_news): ?><input type="hidden" name="news_id" value="<?php echo $edit_news['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_15">Title *</label>
                    <input id="auto_AdminCP_manage_student_life_php_15" type="text" name="title" required value="<?php echo $edit_news ? htmlspecialchars($edit_news['title']) : ''; ?>" placeholder="e.g., Admissions Open for Session 2026">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_16">Content *</label>
                    <textarea id="auto_AdminCP_manage_student_life_php_16" name="content" required rows="8"><?php echo $edit_news ? htmlspecialchars($edit_news['content']) : ''; ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_17">Author</label>
                        <input id="auto_AdminCP_manage_student_life_php_17" type="text" name="author" value="<?php echo $edit_news ? htmlspecialchars($edit_news['author']) : 'Admin'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_18">Status</label>
                        <select id="auto_AdminCP_manage_student_life_php_18" name="status">
                            <option value="active" <?php echo ($edit_news && $edit_news['status']=='active') ? 'selected':''; ?>>Active (Published)</option>
                            <option value="inactive" <?php echo ($edit_news && $edit_news['status']=='inactive') ? 'selected':''; ?>>Inactive (Draft)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_student_life_php_19">Featured Image</label>
                    <input id="auto_AdminCP_manage_student_life_php_19" type="file" name="image" accept="image/*">
                    <?php if ($edit_news && !empty($edit_news['image'])): ?>
                        <div style="margin-top:10px;"><img src="../<?php echo htmlspecialchars($edit_news['image']); ?>" style="max-width:300px;border-radius:8px;"></div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="save_news" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_news ? 'Update News' : 'Publish News'; ?></button>
                <?php if ($edit_news): ?><a href="?tab=news" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Image</th><th>Title</th><th>Author</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($news_result && mysqli_num_rows($news_result) > 0): ?>
                    <?php while ($item = mysqli_fetch_assoc($news_result)): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" class="thumb-img">
                                <?php else: ?>
                                    <div class="thumb-img" style="background:var(--bg-light);display:flex;align-items:center;justify-content:center;"><i class="fas fa-newspaper" style="color:var(--text-light);"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong><br><small style="color:var(--text-light);"><?php echo substr(htmlspecialchars($item['content']), 0, 60); ?>...</small></td>
                            <td><?php echo htmlspecialchars($item['author']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                            <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo $item['status'] == 'active' ? 'Published' : 'Draft'; ?></span></td>
                            <td>
                                <a href="?tab=news&edit_news=<?php echo $item['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=news&delete_news_id=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this news?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No news yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($active_tab === 'alumni'): ?>
        <!-- ============ ALUMNI TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add New Alumni</h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_20">Student Name *</label><input id="auto_AdminCP_manage_student_life_php_20" type="text" name="student_name" required></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_21">Father Name *</label><input id="auto_AdminCP_manage_student_life_php_21" type="text" name="father_name" required></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_22">Course *</label><input id="auto_AdminCP_manage_student_life_php_22" type="text" name="course" required placeholder="e.g., Matric, Intermediate"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_23">Passing Year *</label><input id="auto_AdminCP_manage_student_life_php_23" type="number" name="passing_year" required min="1990" max="2050"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_24">Current Job</label><input id="auto_AdminCP_manage_student_life_php_24" type="text" name="current_job"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_25">Department</label><input id="auto_AdminCP_manage_student_life_php_25" type="text" name="job_department"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_26">City</label><input id="auto_AdminCP_manage_student_life_php_26" type="text" name="job_city"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_27">Mobile Number *</label><input id="auto_AdminCP_manage_student_life_php_27" type="text" name="mobile_number" required></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_28">WhatsApp Number</label><input id="auto_AdminCP_manage_student_life_php_28" type="text" name="whatsapp_number"></div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_29">Status *</label>
                        <select id="auto_AdminCP_manage_student_life_php_29" name="status" required>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_30">Photo (JPG, PNG)</label><input id="auto_AdminCP_manage_student_life_php_30" type="file" name="photo" accept="image/*"></div>
                <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_31">Review *</label><textarea id="auto_AdminCP_manage_student_life_php_31" name="review" required rows="3"></textarea></div>
                <button type="submit" name="add_alumni" class="btn btn-primary"><i class="fas fa-plus"></i> Add Alumni</button>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Photo</th><th>Name</th><th>Course</th><th>Year</th><th>Job</th><th>Mobile</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($alumni_result && mysqli_num_rows($alumni_result) > 0): ?>
                    <?php while ($alum = mysqli_fetch_assoc($alumni_result)): ?>
                        <tr>
                            <td>
                                <?php if ($alum['photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($alum['photo']); ?>" class="avatar-img">
                                <?php else: ?>
                                    <i class="fas fa-user-circle" style="font-size:40px;color:#ccc;"></i>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($alum['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($alum['course']); ?></td>
                            <td><?php echo $alum['passing_year']; ?></td>
                            <td><?php echo htmlspecialchars($alum['current_job'] ?: 'N/A'); ?><br><small><?php echo htmlspecialchars($alum['job_city'] ?: ''); ?></small></td>
                            <td><?php echo htmlspecialchars($alum['mobile_number']); ?></td>
                            <td><span class="status-badge status-<?php echo $alum['status']; ?>"><?php echo ucfirst($alum['status']); ?></span></td>
                            <td>
                                <?php if ($alum['status'] === 'pending'): ?>
                                    <a href="?tab=alumni&alumni_action=approve&alumni_id=<?php echo $alum['id']; ?>" class="action-btn btn-approve"><i class="fas fa-check"></i></a>
                                    <a href="?tab=alumni&alumni_action=reject&alumni_id=<?php echo $alum['id']; ?>" class="action-btn btn-reject"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                                <a href="?tab=alumni&alumni_action=delete&alumni_id=<?php echo $alum['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this alumni?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-light);">No alumni registrations yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($active_tab === 'alumni_reviews'): ?>
        <!-- ============ ALUMNI REVIEWS TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add New Review</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_32">Name *</label><input id="auto_AdminCP_manage_student_life_php_32" type="text" name="name" required></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_33">Passing Year</label><input id="auto_AdminCP_manage_student_life_php_33" type="number" name="passing_year" min="1990" max="2050"></div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_34">Rating *</label>
                        <select id="auto_AdminCP_manage_student_life_php_34" name="rating" required>
                            <option value="5">★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                            <option value="3">★★★☆☆ (3 Stars)</option>
                            <option value="2">★★☆☆☆ (2 Stars)</option>
                            <option value="1">★☆☆☆☆ (1 Star)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_student_life_php_35">Review *</label><textarea id="auto_AdminCP_manage_student_life_php_35" name="review" required rows="4"></textarea></div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_student_life_php_36">Status *</label>
                        <select id="auto_AdminCP_manage_student_life_php_36" name="status" required>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_review" class="btn btn-primary"><i class="fas fa-plus"></i> Add Review</button>
            </form>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Year</th><th>Rating</th><th>Review</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($reviews_result && mysqli_num_rows($reviews_result) > 0): ?>
                    <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['name']); ?></td>
                            <td><?php echo $review['passing_year'] ?: 'N/A'; ?></td>
                            <td><?php for ($i = 0; $i < $review['rating']; $i++) echo '<i class="fas fa-star" style="color:#0BA6C9;"></i>'; ?></td>
                            <td><?php echo substr(htmlspecialchars($review['review']), 0, 80); ?>...</td>
                            <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                            <td><span class="status-badge status-<?php echo $review['status']; ?>"><?php echo ucfirst($review['status']); ?></span></td>
                            <td>
                                <?php if ($review['status'] === 'pending'): ?>
                                    <a href="?tab=alumni_reviews&review_action=approve&review_id=<?php echo $review['id']; ?>" class="action-btn btn-approve"><i class="fas fa-check"></i></a>
                                    <a href="?tab=alumni_reviews&review_action=reject&review_id=<?php echo $review['id']; ?>" class="action-btn btn-reject"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                                <a href="?tab=alumni_reviews&review_action=delete&review_id=<?php echo $review['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this review?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No reviews yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>
    </div>
    </div>
</body>
</html>
