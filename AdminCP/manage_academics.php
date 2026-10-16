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

$valid_tabs = ['courses', 'faculty', 'datesheets', 'board_results'];
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'courses';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$is_error = isset($_GET['err']) && $_GET['err'] == '1';

function redirectBackAcademics($tab, $msg, $err = false) {
    header('Location: manage_academics.php?tab=' . urlencode($tab) . '&msg=' . urlencode($msg) . ($err ? '&err=1' : ''));
    exit;
}

function saveAcademicsSetting($conn, $key, $value) {
    $key = mysqli_real_escape_string($conn, $key);
    $value = mysqli_real_escape_string($conn, $value);
    $exists = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key'");
    if ($exists && mysqli_num_rows($exists) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

/* =========================================================
   COURSES PAGE CONTENT (hero + requirements + CTA)
   ========================================================= */
if (isset($_POST['save_courses_hero'])) {
    saveAcademicsSetting($conn, 'courses_hero_badge', $_POST['courses_hero_badge'] ?? '');
    saveAcademicsSetting($conn, 'courses_hero_subtitle', $_POST['courses_hero_subtitle'] ?? '');
    redirectBackAcademics('courses', 'Hero banner saved successfully!');
}

if (isset($_POST['save_courses_requirements'])) {
    $keys = ['courses_req_badge', 'courses_req_title', 'courses_req_title_highlight', 'courses_req_subtitle',
             'courses_req1_head', 'courses_req1_items', 'courses_req2_head', 'courses_req2_items',
             'courses_req3_head', 'courses_req3_items'];
    foreach ($keys as $key) {
        saveAcademicsSetting($conn, $key, $_POST[$key] ?? '');
    }
    redirectBackAcademics('courses', 'Admission Requirements section saved successfully!');
}

if (isset($_POST['save_courses_cta'])) {
    $keys = ['courses_cta_badge', 'courses_cta_heading_line1', 'courses_cta_heading_line2',
             'courses_cta_subtext', 'courses_cta_pill1', 'courses_cta_pill2'];
    foreach ($keys as $key) {
        saveAcademicsSetting($conn, $key, $_POST[$key] ?? '');
    }
    redirectBackAcademics('courses', 'CTA section saved successfully!');
}

/* =========================================================
   COURSES
   ========================================================= */
if (isset($_POST['save_course'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Fee breakdown — each amount is optional; blank means "not set" rather than Rs. 0
    $admission_fee = (($_POST['admission_fee'] ?? '') !== '') ? floatval($_POST['admission_fee']) : 'NULL';
    $semester_fee  = (($_POST['semester_fee'] ?? '') !== '')  ? floatval($_POST['semester_fee'])  : 'NULL';
    $fee_period    = in_array($_POST['fee_period'] ?? '', ['Semester', 'Month'], true) ? $_POST['fee_period'] : 'Semester';
    $initial_fee   = (($_POST['initial_fee'] ?? '') !== '')   ? floatval($_POST['initial_fee'])   : 'NULL';
    $total_fee     = (($_POST['total_fee'] ?? '') !== '')     ? floatval($_POST['total_fee'])     : 'NULL';
    $affiliation   = mysqli_real_escape_string($conn, trim($_POST['affiliation'] ?? ''));
    $department    = mysqli_real_escape_string($conn, trim($_POST['department'] ?? ''));
    $eligibility   = mysqli_real_escape_string($conn, trim($_POST['eligibility'] ?? ''));

    if (!empty($_POST['course_id'])) {
        $id = intval($_POST['course_id']);
        $query = "UPDATE courses SET name='$name', description='$description', duration='$duration', category='$category', icon='$icon', display_order=$display_order, status='$status',
                   admission_fee=$admission_fee, semester_fee=$semester_fee, fee_period='$fee_period', initial_fee=$initial_fee, total_fee=$total_fee,
                   affiliation='$affiliation', department='$department', eligibility='$eligibility' WHERE id=$id";
    } else {
        $query = "INSERT INTO courses (name, description, duration, category, icon, display_order, status, admission_fee, semester_fee, fee_period, initial_fee, total_fee, affiliation, department, eligibility)
                   VALUES ('$name', '$description', '$duration', '$category', '$icon', $display_order, '$status', $admission_fee, $semester_fee, '$fee_period', $initial_fee, $total_fee, '$affiliation', '$department', '$eligibility')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBackAcademics('courses', 'Course saved successfully!');
    }
    redirectBackAcademics('courses', 'Error saving course.', true);
}

if (isset($_GET['delete_course_id'])) {
    $id = intval($_GET['delete_course_id']);
    mysqli_query($conn, "DELETE FROM courses WHERE id = $id");
    redirectBackAcademics('courses', 'Course deleted successfully!');
}

/* =========================================================
   FACULTY PAGE CONTENT (hero + stats + grid header + why + CTA)
   ========================================================= */
if (isset($_POST['save_faculty_hero'])) {
    saveAcademicsSetting($conn, 'faculty_hero_badge', $_POST['faculty_hero_badge'] ?? '');
    saveAcademicsSetting($conn, 'faculty_hero_subtitle', $_POST['faculty_hero_subtitle'] ?? '');
    redirectBackAcademics('faculty', 'Hero banner saved successfully!');
}

if (isset($_POST['save_faculty_stats'])) {
    $keys = ['faculty_stat2_number', 'faculty_stat2_label', 'faculty_stat3_number', 'faculty_stat3_label',
             'faculty_stat4_number', 'faculty_stat4_label'];
    foreach ($keys as $key) {
        saveAcademicsSetting($conn, $key, $_POST[$key] ?? '');
    }
    redirectBackAcademics('faculty', 'Stats bar saved successfully!');
}

if (isset($_POST['save_faculty_grid_header'])) {
    $keys = ['faculty_grid_badge', 'faculty_grid_title', 'faculty_grid_title_highlight', 'faculty_grid_subtitle'];
    foreach ($keys as $key) {
        saveAcademicsSetting($conn, $key, $_POST[$key] ?? '');
    }
    redirectBackAcademics('faculty', 'Section header saved successfully!');
}

if (isset($_POST['save_faculty_why'])) {
    $keys = ['faculty_why_badge', 'faculty_why_title', 'faculty_why_title_highlight', 'faculty_why_subtitle'];
    for ($i = 1; $i <= 6; $i++) {
        $keys[] = "faculty_why{$i}_title";
        $keys[] = "faculty_why{$i}_desc";
    }
    foreach ($keys as $key) {
        saveAcademicsSetting($conn, $key, $_POST[$key] ?? '');
    }
    redirectBackAcademics('faculty', '"Why Our Faculty Stands Out" section saved successfully!');
}

if (isset($_POST['save_faculty_cta'])) {
    $keys = ['faculty_cta_badge', 'faculty_cta_heading_line1', 'faculty_cta_heading_line2',
             'faculty_cta_subtext', 'faculty_cta_pill1', 'faculty_cta_pill2'];
    foreach ($keys as $key) {
        saveAcademicsSetting($conn, $key, $_POST[$key] ?? '');
    }
    redirectBackAcademics('faculty', 'CTA section saved successfully!');
}

/* =========================================================
   FACULTY
   ========================================================= */
if (isset($_POST['save_faculty'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $designation = mysqli_real_escape_string($conn, trim($_POST['designation'] ?? ''));
    $department = mysqli_real_escape_string($conn, trim($_POST['department'] ?? ''));
    $qualification = mysqli_real_escape_string($conn, trim($_POST['qualification'] ?? ''));
    $experience = intval($_POST['experience'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $photo_path = '';
    $is_edit = !empty($_POST['faculty_id']);
    if ($is_edit) {
        $id = intval($_POST['faculty_id']);
        $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM faculty WHERE id = $id"));
        $photo_path = $current['photo'];
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../images/faculty/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'faculty_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                if ($photo_path && file_exists('../' . $photo_path)) unlink('../' . $photo_path);
                $photo_path = 'images/faculty/' . $filename;
            }
        }
    }

    if ($is_edit) {
        $query = "UPDATE faculty SET name='$name', designation='$designation', department='$department', qualification='$qualification', experience=$experience, photo='$photo_path', status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO faculty (name, designation, department, qualification, experience, photo, status) VALUES ('$name', '$designation', '$department', '$qualification', $experience, '$photo_path', '$status')";
    }
    if (mysqli_query($conn, $query)) {
        redirectBackAcademics('faculty', 'Faculty member saved successfully!');
    }
    redirectBackAcademics('faculty', 'Error saving faculty member: ' . mysqli_error($conn), true);
}

if (isset($_GET['delete_faculty_id'])) {
    $id = intval($_GET['delete_faculty_id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM faculty WHERE id = $id"));
    mysqli_query($conn, "DELETE FROM faculty WHERE id = $id");
    if ($row && $row['photo'] && file_exists('../' . $row['photo'])) unlink('../' . $row['photo']);
    redirectBackAcademics('faculty', 'Faculty member deleted successfully!');
}

/* =========================================================
   EXAMINATION PAGE HEADER
   ========================================================= */
if (isset($_POST['save_exam_header'])) {
    saveAcademicsSetting($conn, 'exam_header_title', $_POST['exam_header_title'] ?? '');
    saveAcademicsSetting($conn, 'exam_header_subtitle', $_POST['exam_header_subtitle'] ?? '');

    if (isset($_FILES['exam_header_image']) && $_FILES['exam_header_image']['error'] == 0) {
        $upload_dir = '../images/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['exam_header_image']['type'], $allowed_types) && $_FILES['exam_header_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['exam_header_image']['name'], PATHINFO_EXTENSION);
            $filename = 'exam_header_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            if (move_uploaded_file($_FILES['exam_header_image']['tmp_name'], $upload_dir . $filename)) {
                compressUploadedImage($upload_dir . $filename);
                saveAcademicsSetting($conn, 'exam_header_image', 'images/' . $filename);
            }
        }
    }

    redirectBackAcademics('datesheets', 'Examination page header saved successfully!');
}

/* =========================================================
   EXAM DATESHEETS
   ========================================================= */
if (isset($_POST['add_datesheet'])) {
    $exam_name = mysqli_real_escape_string($conn, $_POST['exam_name']);
    $exam_year = intval($_POST['exam_year']);
    if (mysqli_query($conn, "INSERT INTO exam_datesheets (exam_name, exam_year) VALUES ('$exam_name', $exam_year)")) {
        redirectBackAcademics('datesheets', 'Datesheet created successfully!');
    }
    redirectBackAcademics('datesheets', 'Error creating datesheet.', true);
}

if (isset($_POST['add_detail'])) {
    $datesheet_id = intval($_POST['datesheet_id']);
    $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $day_name = mysqli_real_escape_string($conn, $_POST['day_name']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $sql = "INSERT INTO datesheet_details (datesheet_id, exam_date, day_name, class, subject) VALUES ($datesheet_id, '$exam_date', '$day_name', '$class', '$subject')";
    if (mysqli_query($conn, $sql)) {
        redirectBackAcademics('datesheets', 'Entry added successfully!');
    }
    redirectBackAcademics('datesheets', 'Error adding entry.', true);
}

if (isset($_GET['delete_datesheet_id'])) {
    $id = intval($_GET['delete_datesheet_id']);
    mysqli_query($conn, "DELETE FROM exam_datesheets WHERE id = $id");
    redirectBackAcademics('datesheets', 'Datesheet deleted!');
}

if (isset($_GET['delete_detail_id'])) {
    $id = intval($_GET['delete_detail_id']);
    mysqli_query($conn, "DELETE FROM datesheet_details WHERE id = $id");
    redirectBackAcademics('datesheets', 'Entry deleted!');
}

if (isset($_GET['toggle_datesheet_id'])) {
    $id = intval($_GET['toggle_datesheet_id']);
    mysqli_query($conn, "UPDATE exam_datesheets SET status = IF(status='active','inactive','active') WHERE id = $id");
    redirectBackAcademics('datesheets', 'Status updated!');
}

/* =========================================================
   BOARD RESULTS
   ========================================================= */
if (isset($_POST['add_result'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $board_type = mysqli_real_escape_string($conn, $_POST['board_type']);
    $year = intval($_POST['year']);
    $display_order = intval($_POST['display_order']);

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $upload_dir = '../uploads/results/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $image_name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                compressUploadedImage($upload_dir . $image_name, 2200, 2200, 88);
                $image_path = 'uploads/results/' . $image_name;
                $sql = "INSERT INTO board_results (title, board_type, year, image_path, display_order) VALUES ('$title', '$board_type', $year, '$image_path', $display_order)";
                if (mysqli_query($conn, $sql)) {
                    redirectBackAcademics('board_results', 'Board result added successfully!');
                }
                redirectBackAcademics('board_results', 'Database error saving result.', true);
            }
            redirectBackAcademics('board_results', 'Failed to upload image.', true);
        }
        redirectBackAcademics('board_results', 'Invalid file type. Only JPG, JPEG, PNG allowed.', true);
    }
    redirectBackAcademics('board_results', 'Please select an image.', true);
}

if (isset($_POST['edit_result'])) {
    $id = intval($_POST['result_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $board_type = mysqli_real_escape_string($conn, $_POST['board_type']);
    $year = intval($_POST['year']);
    $display_order = intval($_POST['display_order']);

    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM board_results WHERE id = $id"));
    $image_path = $current['image_path'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $upload_dir = '../uploads/results/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $image_name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                compressUploadedImage($upload_dir . $image_name, 2200, 2200, 88);
                if ($image_path && file_exists('../' . $image_path)) unlink('../' . $image_path);
                $image_path = 'uploads/results/' . $image_name;
            }
        }
    }

    $sql = "UPDATE board_results SET title='$title', board_type='$board_type', year=$year, image_path='$image_path', display_order=$display_order WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        redirectBackAcademics('board_results', 'Board result updated successfully!');
    }
    redirectBackAcademics('board_results', 'Error updating result.', true);
}

if (isset($_GET['delete_result_id'])) {
    $id = intval($_GET['delete_result_id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM board_results WHERE id = $id"));
    if ($row && $row['image_path'] && file_exists('../' . $row['image_path'])) unlink('../' . $row['image_path']);
    mysqli_query($conn, "DELETE FROM board_results WHERE id = $id");
    redirectBackAcademics('board_results', 'Result deleted!');
}

if (isset($_GET['toggle_result_id'])) {
    $id = intval($_GET['toggle_result_id']);
    mysqli_query($conn, "UPDATE board_results SET status = IF(status='active','inactive','active') WHERE id = $id");
    redirectBackAcademics('board_results', 'Status updated!');
}

/* =========================================================
   DATA FOR ACTIVE TAB
   ========================================================= */
$edit_course = null;
if (isset($_GET['edit_course'])) {
    $edit_course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE id = " . intval($_GET['edit_course'])));
}
$courses_result = mysqli_query($conn, "SELECT * FROM courses ORDER BY display_order ASC, id DESC");
$show_course_form = $edit_course || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course']));

$edit_faculty = null;
if (isset($_GET['edit_faculty'])) {
    $edit_faculty = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM faculty WHERE id = " . intval($_GET['edit_faculty'])));
}
$faculty_result = mysqli_query($conn, "SELECT * FROM faculty ORDER BY display_order ASC, id DESC");
$show_faculty_form = $edit_faculty || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faculty']));

$datesheets_result = mysqli_query($conn, "SELECT * FROM exam_datesheets ORDER BY exam_year DESC, id DESC");

$board_results_result = mysqli_query($conn, "SELECT * FROM board_results ORDER BY year DESC, display_order ASC");

$courses_hero = getCoursesHero();
$courses_req = getCoursesRequirements();
$courses_cta_content = getCoursesCta();
$faculty_hero = getFacultyHero();
$faculty_stats = getFacultyStats();
$faculty_grid_header = getFacultyGridHeader();
$faculty_why = getFacultyWhy();
$faculty_cta_content = getFacultyCta();
$exam_header = getExaminationHeader();

$tab_labels = [
    'courses' => ['icon' => 'fas fa-book', 'label' => 'Courses'],
    'faculty' => ['icon' => 'fas fa-chalkboard-teacher', 'label' => 'Faculty'],
    'datesheets' => ['icon' => 'fas fa-calendar-check', 'label' => 'Exam Datesheets'],
    'board_results' => ['icon' => 'fas fa-trophy', 'label' => 'Board Results'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academics - Admin Panel</title>
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
        .status-active { background: #16A34A; }
        .status-inactive { background: #DC2626; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; }
        .data-table tr:hover { background: #F4F8FC; }
        .avatar-img { width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color); }
        .action-btn { padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }
        .btn-toggle { background: #16A34A; color: #fff; }

        .result-card { border: 1px solid rgba(16,42,86,0.12); border-radius: 12px; padding: 15px; background: #fff; }
        .result-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }

        .form-modal {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 1000; overflow-y: auto; align-items: center; justify-content: center; padding: 20px;
        }
        .form-modal.active { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 15px; max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .preview-img { max-width: 220px; margin-top: 10px; border-radius: 10px; display: none; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .form-actions .btn-cancel {
            flex: 1; padding: 12px; background: var(--text-light); color: #fff; border: none;
            border-radius: 8px; cursor: pointer; font-weight: 600;
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-graduation-cap"></i> Manage Academics</h1>
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

        <script>
            function togglePageContent(tab) {
                var wrap = document.getElementById(tab + '-page-content');
                var chevron = document.getElementById(tab + '-pc-chevron');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        </script>

        <?php if ($active_tab === 'courses'): ?>
        <!-- ============ COURSES TAB ============ -->
        <div class="admin-page-title" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="color: var(--primary-color); margin:0; font-size:19px;"><i class="fas fa-book"></i> Courses &amp; Programs</h2>
            <button type="button" onclick="toggleCourseForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Course</button>
        </div>

        <div id="course-form-wrap" style="<?php echo $show_course_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?></h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <?php if ($edit_course): ?><input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_19">Course Name *</label>
                    <input id="auto_AdminCP_manage_academics_php_19" type="text" name="name" required value="<?php echo $edit_course ? htmlspecialchars($edit_course['name']) : ''; ?>" placeholder="e.g., FSc Pre-Engineering">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_20">Description *</label>
                    <textarea id="auto_AdminCP_manage_academics_php_20" name="description" required rows="3"><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_21">Duration</label>
                        <input id="auto_AdminCP_manage_academics_php_21" type="text" name="duration" value="<?php echo $edit_course ? htmlspecialchars($edit_course['duration']) : ''; ?>" placeholder="e.g., 5 Years">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_22">Department</label>
                        <input id="auto_AdminCP_manage_academics_php_22" type="text" name="department" value="<?php echo $edit_course ? htmlspecialchars($edit_course['department']) : ''; ?>" placeholder="e.g., Department of Pharmacy">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_23">Category</label>
                        <select id="auto_AdminCP_manage_academics_php_23" name="category">
                            <?php $cc = $edit_course['category'] ?? 'school'; ?>
                            <optgroup label="IQRA levels (shown on the Courses page)">
                                <option value="school" <?php echo $cc=='school' ? 'selected':''; ?>>School — Play Group to Class 8</option>
                                <option value="matric" <?php echo $cc=='matric' ? 'selected':''; ?>>Matric — Class 9 & 10</option>
                                <option value="inter" <?php echo $cc=='inter' ? 'selected':''; ?>>Intermediate — F.Sc / ICS / F.A</option>
                            </optgroup>
                            <optgroup label="Other">
                            <option value="regular" <?php echo ($edit_course && $edit_course['category']=='regular') ? 'selected':''; ?>>Regular Program</option>
                            <option value="test" <?php echo ($edit_course && $edit_course['category']=='test') ? 'selected':''; ?>>Test Prep</option>
                            <option value="short" <?php echo ($edit_course && $edit_course['category']=='short') ? 'selected':''; ?>>Short Course</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_24">Affiliation</label>
                        <input id="auto_AdminCP_manage_academics_php_24" type="text" name="affiliation" value="<?php echo $edit_course ? htmlspecialchars($edit_course['affiliation']) : ''; ?>" placeholder="e.g., Government College University Faisalabad (GCUF)">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_25">Eligibility</label>
                        <input id="auto_AdminCP_manage_academics_php_25" type="text" name="eligibility" value="<?php echo $edit_course ? htmlspecialchars($edit_course['eligibility']) : ''; ?>" placeholder="e.g., F.Sc Pre-Medical, 60% marks">
                    </div>
                </div>

                <h4 style="color:var(--primary-color);font-size:14px;margin:22px 0 12px;padding-top:16px;border-top:1px solid var(--border-color);">
                    <i class="fas fa-money-check-dollar"></i> Fee Breakdown
                </h4>
                <p style="color:var(--text-light);font-size:12.5px;margin:-6px 0 14px;">Leave any amount blank if it doesn't apply to this program — blank fields won't be shown on the public page.</p>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_26">Admission Fee (Rs.)</label>
                        <input id="auto_AdminCP_manage_academics_php_26" type="number" step="0.01" name="admission_fee" value="<?php echo $edit_course && $edit_course['admission_fee'] !== null ? htmlspecialchars($edit_course['admission_fee']) : ''; ?>" placeholder="e.g., 15000">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_27">Initial Fee (Rs.)</label>
                        <input id="auto_AdminCP_manage_academics_php_27" type="number" step="0.01" name="initial_fee" value="<?php echo $edit_course && $edit_course['initial_fee'] !== null ? htmlspecialchars($edit_course['initial_fee']) : ''; ?>" placeholder="e.g., 25000">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_28">Semester / Monthly Fee (Rs.)</label>
                        <input id="auto_AdminCP_manage_academics_php_28" type="number" step="0.01" name="semester_fee" value="<?php echo $edit_course && $edit_course['semester_fee'] !== null ? htmlspecialchars($edit_course['semester_fee']) : ''; ?>" placeholder="e.g., 60000">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_29">Fee Period</label>
                        <select id="auto_AdminCP_manage_academics_php_29" name="fee_period">
                            <option value="Semester" <?php echo (!$edit_course || $edit_course['fee_period']=='Semester') ? 'selected':''; ?>>Per Semester</option>
                            <option value="Month" <?php echo ($edit_course && $edit_course['fee_period']=='Month') ? 'selected':''; ?>>Per Month</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_30">Total Fee (Rs., full program)</label>
                        <input id="auto_AdminCP_manage_academics_php_30" type="number" step="0.01" name="total_fee" value="<?php echo $edit_course && $edit_course['total_fee'] !== null ? htmlspecialchars($edit_course['total_fee']) : ''; ?>" placeholder="e.g., 850000">
                    </div>
                    <div class="form-group">
                        <label style="visibility:hidden;">.</label>
                        <div style="background:#e7f3ff;border-left:4px solid var(--primary-color);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--text-dark);">
                            <i class="fas fa-circle-info"></i> Examination Fee, Verification Fee &amp; University Charges are always shown as <strong style="color:var(--primary-color);">Not Included</strong> on the public page — no need to enter them here.
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-top:6px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_31">Icon (Font Awesome, without "fa-")</label>
                        <input id="auto_AdminCP_manage_academics_php_31" type="text" name="icon" value="<?php echo $edit_course ? htmlspecialchars($edit_course['icon']) : 'fa-book'; ?>" placeholder="e.g., fa-atom">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_32">Display Order</label>
                        <input id="auto_AdminCP_manage_academics_php_32" type="number" name="display_order" value="<?php echo $edit_course ? $edit_course['display_order'] : 0; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_33">Status</label>
                        <select id="auto_AdminCP_manage_academics_php_33" name="status">
                            <option value="active" <?php echo ($edit_course && $edit_course['status']=='active') ? 'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_course && $edit_course['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="save_course" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_course ? 'Update' : 'Add'; ?> Course</button>
                <?php if ($edit_course): ?><a href="?tab=courses" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a>
                <?php else: ?><button type="button" onclick="toggleCourseForm()" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</button><?php endif; ?>
            </form>
        </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Name</th><th>Category</th><th>Duration</th><th>Semester/Month Fee</th><th>Total Fee</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($courses_result && mysqli_num_rows($courses_result) > 0): ?>
                    <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                        <tr>
                            <td><?php echo $course['display_order']; ?></td>
                            <td><strong><?php echo htmlspecialchars($course['name']); ?></strong></td>
                            <td><?php $cat_names = ['school' => 'School (PG–8)', 'matric' => 'Matric', 'inter' => 'Intermediate']; echo htmlspecialchars($cat_names[$course['category']] ?? ucfirst($course['category'])); ?></td>
                            <td><?php echo htmlspecialchars($course['duration']); ?></td>
                            <td><?php echo $course['semester_fee'] !== null ? 'Rs. ' . number_format($course['semester_fee']) . ' / ' . htmlspecialchars($course['fee_period']) : '—'; ?></td>
                            <td><?php echo $course['total_fee'] !== null ? 'Rs. ' . number_format($course['total_fee']) : '—'; ?></td>
                            <td><span class="status-badge status-<?php echo $course['status']; ?>"><?php echo ucfirst($course['status']); ?></span></td>
                            <td>
                                <a href="?tab=courses&edit_course=<?php echo $course['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=courses&delete_course_id=<?php echo $course['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this course?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No courses yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:25px;">
            <div onclick="togglePageContent('courses')" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="color: var(--primary-color); margin:0;"><i class="fas fa-sliders"></i> Public Page Content Settings</h2>
                <i class="fas fa-chevron-down" id="courses-pc-chevron" style="color:var(--text-light);transition:transform 0.25s ease;"></i>
            </div>
            <p style="color:var(--text-light);font-size:12.5px;margin:8px 0 0;">Hero banner, admission requirements and CTA text shown on the public Courses page. Rarely needs changing.</p>

            <div id="courses-page-content" style="display:none;margin-top:22px;">
                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-flag"></i> Hero Banner</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_1">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_1" type="text" name="courses_hero_badge" value="<?php echo htmlspecialchars($courses_hero['badge']); ?>"></div>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_2">Subtitle</label><textarea id="auto_AdminCP_manage_academics_php_2" name="courses_hero_subtitle" rows="2"><?php echo htmlspecialchars($courses_hero['subtitle']); ?></textarea></div>
                        <button type="submit" name="save_courses_hero" class="btn btn-primary"><i class="fas fa-save"></i> Save Hero Banner</button>
                    </form>
                </div>

                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-file-alt"></i> Admission Requirements Section</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_3">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_3" type="text" name="courses_req_badge" value="<?php echo htmlspecialchars($courses_req['badge']); ?>"></div>
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_4">Heading</label><input id="auto_AdminCP_manage_academics_php_4" type="text" name="courses_req_title" value="<?php echo htmlspecialchars($courses_req['title']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_5">Highlighted Word(s)</label><input id="auto_AdminCP_manage_academics_php_5" type="text" name="courses_req_title_highlight" value="<?php echo htmlspecialchars($courses_req['title_highlight']); ?>"></div>
                        </div>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_6">Subtitle</label><input id="auto_AdminCP_manage_academics_php_6" type="text" name="courses_req_subtitle" value="<?php echo htmlspecialchars($courses_req['subtitle']); ?>"></div>
                        <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div>
                                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:15px;">Card 1</h3>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_7">Heading</label><input id="auto_AdminCP_manage_academics_php_7" type="text" name="courses_req1_head" value="<?php echo htmlspecialchars($courses_req['req1_head']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_8">Items (one per line)</label><textarea id="auto_AdminCP_manage_academics_php_8" name="courses_req1_items" rows="5"><?php echo htmlspecialchars($courses_req['req1_items']); ?></textarea></div>
                            </div>
                            <div>
                                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:15px;">Card 2</h3>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_9">Heading</label><input id="auto_AdminCP_manage_academics_php_9" type="text" name="courses_req2_head" value="<?php echo htmlspecialchars($courses_req['req2_head']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_10">Items (one per line)</label><textarea id="auto_AdminCP_manage_academics_php_10" name="courses_req2_items" rows="5"><?php echo htmlspecialchars($courses_req['req2_items']); ?></textarea></div>
                            </div>
                            <div>
                                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:15px;">Card 3</h3>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_11">Heading</label><input id="auto_AdminCP_manage_academics_php_11" type="text" name="courses_req3_head" value="<?php echo htmlspecialchars($courses_req['req3_head']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_12">Items (one per line)</label><textarea id="auto_AdminCP_manage_academics_php_12" name="courses_req3_items" rows="5"><?php echo htmlspecialchars($courses_req['req3_items']); ?></textarea></div>
                            </div>
                        </div>
                        <button type="submit" name="save_courses_requirements" class="btn btn-primary"><i class="fas fa-save"></i> Save Requirements Section</button>
                    </form>
                </div>

                <div class="card" style="box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-rocket"></i> Bottom CTA Section</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_13">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_13" type="text" name="courses_cta_badge" value="<?php echo htmlspecialchars($courses_cta_content['badge']); ?>"></div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_14">Heading Line 1 (plain)</label><input id="auto_AdminCP_manage_academics_php_14" type="text" name="courses_cta_heading_line1" value="<?php echo htmlspecialchars($courses_cta_content['heading_line1']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_15">Heading Line 2 (gold gradient)</label><input id="auto_AdminCP_manage_academics_php_15" type="text" name="courses_cta_heading_line2" value="<?php echo htmlspecialchars($courses_cta_content['heading_line2']); ?>"></div>
                        </div>
                        <div class="form-group">
                            <label for="auto_AdminCP_manage_academics_php_16">Subtext</label>
                            <textarea id="auto_AdminCP_manage_academics_php_16" name="courses_cta_subtext" rows="2"><?php echo htmlspecialchars($courses_cta_content['subtext']); ?></textarea>
                            <small style="color:var(--text-light);">Use <code>{site_name}</code> anywhere you want the college name inserted automatically.</small>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_17">Pill 1</label><input id="auto_AdminCP_manage_academics_php_17" type="text" name="courses_cta_pill1" value="<?php echo htmlspecialchars($courses_cta_content['pill1']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_18">Pill 2</label><input id="auto_AdminCP_manage_academics_php_18" type="text" name="courses_cta_pill2" value="<?php echo htmlspecialchars($courses_cta_content['pill2']); ?>"></div>
                        </div>
                        <button type="submit" name="save_courses_cta" class="btn btn-primary"><i class="fas fa-save"></i> Save CTA Section</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function toggleCourseForm() {
                var wrap = document.getElementById('course-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <?php elseif ($active_tab === 'faculty'): ?>
        <!-- ============ FACULTY TAB ============ -->
        <div class="admin-page-title" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="color: var(--primary-color); margin:0; font-size:19px;"><i class="fas fa-chalkboard-teacher"></i> Faculty Members</h2>
            <button type="button" onclick="toggleFacultyForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Faculty</button>
        </div>

        <div id="faculty-form-wrap" style="<?php echo $show_faculty_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_faculty ? 'Edit Faculty Member' : 'Add New Faculty Member'; ?></h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_faculty): ?><input type="hidden" name="faculty_id" value="<?php echo $edit_faculty['id']; ?>"><?php endif; ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_58">Full Name *</label>
                    <input id="auto_AdminCP_manage_academics_php_58" type="text" name="name" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['name']) : ''; ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_59">Designation *</label>
                        <input id="auto_AdminCP_manage_academics_php_59" type="text" name="designation" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['designation']) : ''; ?>" placeholder="e.g., Senior Teacher">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_60">Department</label>
                        <input id="auto_AdminCP_manage_academics_php_60" type="text" name="department" value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['department']) : ''; ?>" placeholder="e.g., Pharmacy">
                        <small style="color:var(--text-light);">Used for department filtering on the public Faculty page.</small>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_63">Qualification</label>
                        <input id="auto_AdminCP_manage_academics_php_63" type="text" name="qualification" value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['qualification']) : ''; ?>" placeholder="e.g., MSc Physics">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_64">Experience (Years)</label>
                        <input id="auto_AdminCP_manage_academics_php_64" type="number" name="experience" value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['experience']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_66">Photo (leave empty to keep current)</label>
                    <input id="auto_AdminCP_manage_academics_php_66" type="file" name="photo" accept="image/*" onchange="previewImage(this,'faculty_photo_preview')">
                    <img id="faculty_photo_preview" style="max-width:120px;margin-top:10px;border-radius:10px;display:<?php echo ($edit_faculty && $edit_faculty['photo']) ? 'block' : 'none'; ?>;" src="<?php echo ($edit_faculty && $edit_faculty['photo']) ? '../' . htmlspecialchars($edit_faculty['photo']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_69">Status</label>
                    <select id="auto_AdminCP_manage_academics_php_69" name="status">
                        <option value="active" <?php echo ($edit_faculty && $edit_faculty['status']=='active') ? 'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_faculty && $edit_faculty['status']=='inactive') ? 'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" name="save_faculty" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_faculty ? 'Update' : 'Add'; ?> Faculty</button>
                <?php if ($edit_faculty): ?><a href="?tab=faculty" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</a>
                <?php else: ?><button type="button" onclick="toggleFacultyForm()" class="btn btn-primary" style="background:var(--text-light);margin-left:10px;">Cancel</button><?php endif; ?>
            </form>
        </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Order</th><th>Photo</th><th>Name</th><th>Designation</th><th>Department</th><th>Subjects</th><th>Exp.</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($faculty_result && mysqli_num_rows($faculty_result) > 0): ?>
                    <?php while ($member = mysqli_fetch_assoc($faculty_result)): ?>
                        <tr>
                            <td><?php echo $member['display_order']; ?></td>
                            <td>
                                <?php if ($member['photo']): ?>
                                    <img src="../<?php echo htmlspecialchars($member['photo']); ?>" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-img" style="background:var(--primary-color);display:flex;align-items:center;justify-content:center;"><i class="fas <?php echo htmlspecialchars($member['icon']); ?>" style="color:#fff;"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($member['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($member['designation']); ?></td>
                            <td><?php echo htmlspecialchars($member['department'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($member['subjects']); ?></td>
                            <td><?php echo $member['experience']; ?> yrs</td>
                            <td><span class="status-badge status-<?php echo $member['status']; ?>"><?php echo ucfirst($member['status']); ?></span></td>
                            <td>
                                <a href="?tab=faculty&edit_faculty=<?php echo $member['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?tab=faculty&delete_faculty_id=<?php echo $member['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this faculty member?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-light);">No faculty members yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:25px;">
            <div onclick="togglePageContent('faculty')" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;">
                <h2 style="color: var(--primary-color); margin:0;"><i class="fas fa-sliders"></i> Public Page Content Settings</h2>
                <i class="fas fa-chevron-down" id="faculty-pc-chevron" style="color:var(--text-light);transition:transform 0.25s ease;"></i>
            </div>
            <p style="color:var(--text-light);font-size:12.5px;margin:8px 0 0;">Hero banner, stats bar, section headers and CTA text shown on the public Faculty page. Rarely needs changing.</p>

            <div id="faculty-page-content" style="display:none;margin-top:22px;">
                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-flag"></i> Hero Banner</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_34">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_34" type="text" name="faculty_hero_badge" value="<?php echo htmlspecialchars($faculty_hero['badge']); ?>"></div>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_35">Subtitle</label><textarea id="auto_AdminCP_manage_academics_php_35" name="faculty_hero_subtitle" rows="2"><?php echo htmlspecialchars($faculty_hero['subtitle']); ?></textarea></div>
                        <button type="submit" name="save_faculty_hero" class="btn btn-primary"><i class="fas fa-save"></i> Save Hero Banner</button>
                    </form>
                </div>

                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-chart-bar"></i> Stats Bar</h2>
                    <p style="color:var(--text-light);font-size:12.5px;margin:-6px 0 15px;">Note: the first stat ("Expert Teachers" count) is calculated automatically from active faculty members and isn't editable here.</p>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_36">Stat 2 Number</label><input id="auto_AdminCP_manage_academics_php_36" type="text" name="faculty_stat2_number" value="<?php echo htmlspecialchars($faculty_stats['stat2_number']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_37">Stat 2 Label</label><input id="auto_AdminCP_manage_academics_php_37" type="text" name="faculty_stat2_label" value="<?php echo htmlspecialchars($faculty_stats['stat2_label']); ?>"></div>
                            </div>
                            <div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_38">Stat 3 Number</label><input id="auto_AdminCP_manage_academics_php_38" type="text" name="faculty_stat3_number" value="<?php echo htmlspecialchars($faculty_stats['stat3_number']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_39">Stat 3 Label</label><input id="auto_AdminCP_manage_academics_php_39" type="text" name="faculty_stat3_label" value="<?php echo htmlspecialchars($faculty_stats['stat3_label']); ?>"></div>
                            </div>
                            <div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_40">Stat 4 Number</label><input id="auto_AdminCP_manage_academics_php_40" type="text" name="faculty_stat4_number" value="<?php echo htmlspecialchars($faculty_stats['stat4_number']); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_41">Stat 4 Label</label><input id="auto_AdminCP_manage_academics_php_41" type="text" name="faculty_stat4_label" value="<?php echo htmlspecialchars($faculty_stats['stat4_label']); ?>"></div>
                            </div>
                        </div>
                        <button type="submit" name="save_faculty_stats" class="btn btn-primary"><i class="fas fa-save"></i> Save Stats Bar</button>
                    </form>
                </div>

                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-heading"></i> Faculty Grid Section Header</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_42">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_42" type="text" name="faculty_grid_badge" value="<?php echo htmlspecialchars($faculty_grid_header['badge']); ?>"></div>
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_43">Heading</label><input id="auto_AdminCP_manage_academics_php_43" type="text" name="faculty_grid_title" value="<?php echo htmlspecialchars($faculty_grid_header['title']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_44">Highlighted Word(s)</label><input id="auto_AdminCP_manage_academics_php_44" type="text" name="faculty_grid_title_highlight" value="<?php echo htmlspecialchars($faculty_grid_header['title_highlight']); ?>"></div>
                        </div>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_45">Subtitle</label><input id="auto_AdminCP_manage_academics_php_45" type="text" name="faculty_grid_subtitle" value="<?php echo htmlspecialchars($faculty_grid_header['subtitle']); ?>"></div>
                        <button type="submit" name="save_faculty_grid_header" class="btn btn-primary"><i class="fas fa-save"></i> Save Section Header</button>
                    </form>
                </div>

                <div class="card" style="margin-bottom: 25px; box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-star"></i> "Why Our Faculty Stands Out" Section</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_46">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_46" type="text" name="faculty_why_badge" value="<?php echo htmlspecialchars($faculty_why['badge']); ?>"></div>
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_47">Heading</label><input id="auto_AdminCP_manage_academics_php_47" type="text" name="faculty_why_title" value="<?php echo htmlspecialchars($faculty_why['title']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_48">Highlighted Word(s)</label><input id="auto_AdminCP_manage_academics_php_48" type="text" name="faculty_why_title_highlight" value="<?php echo htmlspecialchars($faculty_why['title_highlight']); ?>"></div>
                        </div>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_49">Subtitle</label><input id="auto_AdminCP_manage_academics_php_49" type="text" name="faculty_why_subtitle" value="<?php echo htmlspecialchars($faculty_why['subtitle']); ?>"></div>
                        <hr style="margin:22px 0;border:none;border-top:1px solid #EDF2F7;">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div>
                                <h3 style="color:var(--primary-color);margin-bottom:15px;font-size:15px;">Tile <?php echo $i; ?></h3>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_50_<?php echo $i; ?>">Title</label><input id="auto_AdminCP_manage_academics_php_50_<?php echo $i; ?>" type="text" name="faculty_why<?php echo $i; ?>_title" value="<?php echo htmlspecialchars($faculty_why["tile{$i}_title"]); ?>"></div>
                                <div class="form-group"><label for="auto_AdminCP_manage_academics_php_51_<?php echo $i; ?>">Description</label><textarea id="auto_AdminCP_manage_academics_php_51_<?php echo $i; ?>" name="faculty_why<?php echo $i; ?>_desc" rows="3"><?php echo htmlspecialchars($faculty_why["tile{$i}_desc"]); ?></textarea></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" name="save_faculty_why" class="btn btn-primary"><i class="fas fa-save"></i> Save "Why" Section</button>
                    </form>
                </div>

                <div class="card" style="box-shadow:none; border:1px solid var(--border-color);">
                    <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-rocket"></i> Bottom CTA Section</h2>
                    <form method="POST"><?php echo csrf_field(); ?>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_52">Top Badge Text</label><input id="auto_AdminCP_manage_academics_php_52" type="text" name="faculty_cta_badge" value="<?php echo htmlspecialchars($faculty_cta_content['badge']); ?>"></div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group">
                                <label for="auto_AdminCP_manage_academics_php_53">Heading Line 1 (plain)</label>
                                <input id="auto_AdminCP_manage_academics_php_53" type="text" name="faculty_cta_heading_line1" value="<?php echo htmlspecialchars($faculty_cta_content['heading_line1']); ?>">
                                <small style="color:var(--text-light);">Use <code>{site_name}</code> to insert the college name.</small>
                            </div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_54">Heading Line 2 (gold gradient)</label><input id="auto_AdminCP_manage_academics_php_54" type="text" name="faculty_cta_heading_line2" value="<?php echo htmlspecialchars($faculty_cta_content['heading_line2']); ?>"></div>
                        </div>
                        <div class="form-group"><label for="auto_AdminCP_manage_academics_php_55">Subtext</label><textarea id="auto_AdminCP_manage_academics_php_55" name="faculty_cta_subtext" rows="2"><?php echo htmlspecialchars($faculty_cta_content['subtext']); ?></textarea></div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_56">Pill 1</label><input id="auto_AdminCP_manage_academics_php_56" type="text" name="faculty_cta_pill1" value="<?php echo htmlspecialchars($faculty_cta_content['pill1']); ?>"></div>
                            <div class="form-group"><label for="auto_AdminCP_manage_academics_php_57">Pill 2</label><input id="auto_AdminCP_manage_academics_php_57" type="text" name="faculty_cta_pill2" value="<?php echo htmlspecialchars($faculty_cta_content['pill2']); ?>"></div>
                        </div>
                        <button type="submit" name="save_faculty_cta" class="btn btn-primary"><i class="fas fa-save"></i> Save CTA Section</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function toggleFacultyForm() {
                var wrap = document.getElementById('faculty-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            function previewImage(input, previewId){
                var preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display='block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>

        <?php elseif ($active_tab === 'datesheets'): ?>
        <!-- ============ EXAM DATESHEETS TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-flag"></i> Examination Page Header</h2>
            <p style="color:var(--text-light);font-size:12.5px;margin:-6px 0 15px;">This is the banner shown at the top of the public Examination page (Datesheets &amp; Board Results).</p>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_70">Background Image (leave empty to keep current)</label>
                    <input id="auto_AdminCP_manage_academics_php_70" type="file" name="exam_header_image" accept="image/*" onchange="previewImage(this,'exam_header_image_preview')">
                    <img src="<?php echo htmlspecialchars($exam_header['image']); ?>" id="exam_header_image_preview" style="display:block;max-width:220px;margin-top:10px;border-radius:10px;">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group"><label for="auto_AdminCP_manage_academics_php_71">Title</label><input id="auto_AdminCP_manage_academics_php_71" type="text" name="exam_header_title" value="<?php echo htmlspecialchars($exam_header['title']); ?>"></div>
                    <div class="form-group"><label for="auto_AdminCP_manage_academics_php_72">Subtitle</label><input id="auto_AdminCP_manage_academics_php_72" type="text" name="exam_header_subtitle" value="<?php echo htmlspecialchars($exam_header['subtitle']); ?>"></div>
                </div>
                <button type="submit" name="save_exam_header" class="btn btn-primary"><i class="fas fa-save"></i> Save Page Header</button>
            </form>
        </div>

        <script>
            function previewImage(input, previewId){
                var preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display='block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>

        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Create New Datesheet</h2>
            <form method="POST"><?php echo csrf_field(); ?>
                <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:15px;align-items:end;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_academics_php_73">Exam Name</label>
                        <input id="auto_AdminCP_manage_academics_php_73" type="text" name="exam_name" required placeholder="e.g., Annual Exam 2026">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_academics_php_74">Year</label>
                        <input id="auto_AdminCP_manage_academics_php_74" type="number" name="exam_year" required min="2020" max="2050" value="<?php echo date('Y'); ?>">
                    </div>
                    <button type="submit" name="add_datesheet" class="btn btn-primary"><i class="fas fa-plus"></i> Create</button>
                </div>
            </form>
        </div>

        <?php if ($datesheets_result && mysqli_num_rows($datesheets_result) > 0): ?>
            <?php while ($datesheet = mysqli_fetch_assoc($datesheets_result)): ?>
                <div class="card" style="margin-bottom: 25px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <div>
                            <h2 style="color:var(--primary-color);margin-bottom:5px;"><?php echo htmlspecialchars($datesheet['exam_name']); ?></h2>
                            <span class="status-badge status-<?php echo $datesheet['status']; ?>"><?php echo ucfirst($datesheet['status']); ?></span>
                        </div>
                        <div>
                            <a href="?tab=datesheets&toggle_datesheet_id=<?php echo $datesheet['id']; ?>" class="btn btn-primary" style="padding:8px 15px;margin-right:5px;"><i class="fas fa-toggle-on"></i> Toggle</a>
                            <a href="?tab=datesheets&delete_datesheet_id=<?php echo $datesheet['id']; ?>" class="btn btn-primary" style="padding:8px 15px;background:#DC2626;" onclick="return confirm('Delete this datesheet?')"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>

                    <form method="POST" style="margin-bottom:20px;background:#F4F8FC;padding:15px;border-radius:8px;"><?php echo csrf_field(); ?>
                        <input type="hidden" name="datesheet_id" value="<?php echo $datesheet['id']; ?>">
                        <div style="display:grid;grid-template-columns:150px 120px 100px 1fr auto;gap:10px;align-items:end;">
                            <div><label style="font-size:12px;font-weight:bold;" for="auto_AdminCP_manage_academics_php_75">Date</label><input id="auto_AdminCP_manage_academics_php_75" type="date" name="exam_date" required style="width:100%;padding:8px;border:1px solid #DCE5EF;border-radius:5px;"></div>
                            <div><label style="font-size:12px;font-weight:bold;" for="auto_AdminCP_manage_academics_php_76">Day</label><input id="auto_AdminCP_manage_academics_php_76" type="text" name="day_name" required placeholder="Monday" style="width:100%;padding:8px;border:1px solid #DCE5EF;border-radius:5px;"></div>
                            <div><label style="font-size:12px;font-weight:bold;" for="auto_AdminCP_manage_academics_php_77">Class</label><input id="auto_AdminCP_manage_academics_php_77" type="text" name="class" required placeholder="9" style="width:100%;padding:8px;border:1px solid #DCE5EF;border-radius:5px;"></div>
                            <div><label style="font-size:12px;font-weight:bold;" for="auto_AdminCP_manage_academics_php_78">Subject</label><input id="auto_AdminCP_manage_academics_php_78" type="text" name="subject" required placeholder="Physics" style="width:100%;padding:8px;border:1px solid #DCE5EF;border-radius:5px;"></div>
                            <button type="submit" name="add_detail" class="action-btn btn-edit"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </form>

                    <?php
                    $details = mysqli_query($conn, "SELECT * FROM datesheet_details WHERE datesheet_id = {$datesheet['id']} ORDER BY exam_date ASC, class ASC");
                    if ($details && mysqli_num_rows($details) > 0):
                    ?>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead><tr><th>Date</th><th>Day</th><th>Class</th><th>Subject</th><th style="text-align:center;">Action</th></tr></thead>
                            <tbody>
                            <?php while ($detail = mysqli_fetch_assoc($details)): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($detail['exam_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($detail['day_name']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['class']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['subject']); ?></td>
                                    <td style="text-align:center;"><a href="?tab=datesheets&delete_detail_id=<?php echo $detail['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this entry?')"><i class="fas fa-trash"></i></a></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <p style="text-align:center;color:var(--text-light);padding:20px;">No entries added yet. Use the form above.</p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card"><p style="text-align:center;color:var(--text-light);padding:20px;">No datesheets created yet</p></div>
        <?php endif; ?>

        <?php elseif ($active_tab === 'board_results'): ?>
        <!-- ============ BOARD RESULTS TAB ============ -->
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add Board Result</h2>
            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:15px;margin-bottom:15px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_academics_php_79">Title</label>
                        <input id="auto_AdminCP_manage_academics_php_79" type="text" name="title" required placeholder="e.g., SSC Annual Results 2026">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_academics_php_80">Board Type</label>
                        <select id="auto_AdminCP_manage_academics_php_80" name="board_type" required>
                            <option value="Matric">Matric</option>
                            <option value="Intermediate">Intermediate</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_academics_php_81">Year</label>
                        <input id="auto_AdminCP_manage_academics_php_81" type="number" name="year" required min="2000" max="2050" value="<?php echo date('Y'); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="auto_AdminCP_manage_academics_php_82">Display Order</label>
                        <input id="auto_AdminCP_manage_academics_php_82" type="number" name="display_order" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="auto_AdminCP_manage_academics_php_83">Result Image (JPG, PNG)</label>
                    <input id="auto_AdminCP_manage_academics_php_83" type="file" name="image" required accept="image/*">
                </div>
                <button type="submit" name="add_result" class="btn btn-primary"><i class="fas fa-plus"></i> Add Result</button>
            </form>
        </div>

        <div class="card">
            <h3 style="color: var(--primary-color); margin-bottom: 15px;">Matric Results</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-bottom:30px;">
                <?php
                mysqli_data_seek($board_results_result, 0);
                $has_matric = false;
                while ($result = mysqli_fetch_assoc($board_results_result)):
                    if ($result['board_type'] !== 'Matric') continue;
                    $has_matric = true;
                ?>
                <div class="result-card">
                    <img src="../<?php echo htmlspecialchars($result['image_path']); ?>">
                    <h4 style="color:var(--primary-color);margin-bottom:5px;"><?php echo htmlspecialchars($result['title']); ?></h4>
                    <p style="color:var(--text-light);margin-bottom:10px;">Year: <?php echo $result['year']; ?></p>
                    <span class="status-badge status-<?php echo $result['status']; ?>"><?php echo ucfirst($result['status']); ?></span>
                    <div style="margin-top:10px;">
                        <button class="action-btn btn-edit" onclick='openEditResultModal(<?php echo json_encode($result, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                        <a href="?tab=board_results&toggle_result_id=<?php echo $result['id']; ?>" class="action-btn btn-toggle"><i class="fas fa-toggle-on"></i></a>
                        <a href="?tab=board_results&delete_result_id=<?php echo $result['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this result?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (!$has_matric): ?><p style="color:var(--text-light);">No Matric results yet</p><?php endif; ?>
            </div>

            <h3 style="color: var(--primary-color); margin-bottom: 15px;">Intermediate Results</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">
                <?php
                mysqli_data_seek($board_results_result, 0);
                $has_inter = false;
                while ($result = mysqli_fetch_assoc($board_results_result)):
                    if ($result['board_type'] !== 'Intermediate') continue;
                    $has_inter = true;
                ?>
                <div class="result-card">
                    <img src="../<?php echo htmlspecialchars($result['image_path']); ?>">
                    <h4 style="color:var(--primary-color);margin-bottom:5px;"><?php echo htmlspecialchars($result['title']); ?></h4>
                    <p style="color:var(--text-light);margin-bottom:10px;">Year: <?php echo $result['year']; ?></p>
                    <span class="status-badge status-<?php echo $result['status']; ?>"><?php echo ucfirst($result['status']); ?></span>
                    <div style="margin-top:10px;">
                        <button class="action-btn btn-edit" onclick='openEditResultModal(<?php echo json_encode($result, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                        <a href="?tab=board_results&toggle_result_id=<?php echo $result['id']; ?>" class="action-btn btn-toggle"><i class="fas fa-toggle-on"></i></a>
                        <a href="?tab=board_results&delete_result_id=<?php echo $result['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this result?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (!$has_inter): ?><p style="color:var(--text-light);">No Intermediate results yet</p><?php endif; ?>
            </div>
        </div>

        <div id="editResultModal" class="form-modal">
            <div class="modal-content">
                <h2 style="margin-bottom:20px;color:var(--primary-color);"><i class="fas fa-edit"></i> Edit Board Result</h2>
                <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                    <input type="hidden" name="result_id" id="edit_result_id">
                    <div class="form-group"><label>Title</label><input type="text" name="title" id="edit_result_title" required></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div class="form-group">
                            <label>Board Type</label>
                            <select name="board_type" id="edit_result_board_type">
                                <option value="Matric">Matric</option>
                                <option value="Intermediate">Intermediate</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Year</label><input type="number" name="year" id="edit_result_year" min="2000" max="2050"></div>
                    </div>
                    <div class="form-group"><label>Display Order</label><input type="number" name="display_order" id="edit_result_display_order" min="0"></div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_academics_php_84">Result Image (leave empty to keep current)</label>
                        <input id="auto_AdminCP_manage_academics_php_84" type="file" name="image" accept="image/*" onchange="previewResultImage(this,'edit_result_image_preview')">
                        <img id="edit_result_image_preview" class="preview-img">
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="edit_result" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Update Result</button>
                        <button type="button" class="btn-cancel" onclick="closeEditResultModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditResultModal(result){
                document.getElementById('edit_result_id').value = result.id;
                document.getElementById('edit_result_title').value = result.title || '';
                document.getElementById('edit_result_board_type').value = result.board_type;
                document.getElementById('edit_result_year').value = result.year;
                document.getElementById('edit_result_display_order').value = result.display_order;
                var p = document.getElementById('edit_result_image_preview');
                if (result.image_path) { p.src = '../' + result.image_path; p.style.display='block'; } else { p.style.display='none'; }
                document.getElementById('editResultModal').classList.add('active');
            }
            function closeEditResultModal(){document.getElementById('editResultModal').classList.remove('active');}
            function previewResultImage(input, previewId){
                var preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display='block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            }
            window.onclick = function(e){ if (e.target.classList.contains('form-modal')) e.target.classList.remove('active'); };
        </script>

        <?php endif; ?>
    </div>
    </div>
</body>
</html>
