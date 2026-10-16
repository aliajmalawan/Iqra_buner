<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/csrf.php';
csrf_guard();

$message = '';

function nextRegistrationNo($conn) {
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM students"))['c'];
    return 'IQRA-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// Handle delete (also removes the uploaded photo, if any)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM students WHERE id = $id"));
    if ($row && !empty($row['photo']) && file_exists('../' . $row['photo'])) {
        unlink('../' . $row['photo']);
    }
    if (mysqli_query($conn, "DELETE FROM students WHERE id = $id")) {
        $message = "Student record deleted successfully!";
    } else {
        $message = "Error deleting student record.";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $father_name = mysqli_real_escape_string($conn, trim($_POST['father_name'] ?? ''));
    $cnic = mysqli_real_escape_string($conn, trim($_POST['cnic'] ?? ''));
    $gender = $_POST['gender'] === 'female' ? 'female' : 'male';
    $dob = !empty($_POST['date_of_birth']) ? "'" . mysqli_real_escape_string($conn, $_POST['date_of_birth']) . "'" : 'NULL';
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));
    $course_id = intval($_POST['course_id'] ?? 0) ?: 'NULL';
    $semester = mysqli_real_escape_string($conn, trim($_POST['semester'] ?? ''));
    $admission_date = !empty($_POST['admission_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['admission_date']) . "'" : 'NULL';
    $status = in_array($_POST['status'] ?? '', ['active', 'graduated', 'left', 'suspended']) ? $_POST['status'] : 'active';
    $previous_education = mysqli_real_escape_string($conn, trim($_POST['previous_education'] ?? ''));
    $special_notes = mysqli_real_escape_string($conn, trim($_POST['special_notes'] ?? ''));
    $registration_no = mysqli_real_escape_string($conn, trim($_POST['registration_no'] ?? ''));

    if ($full_name === '' || $father_name === '' || $registration_no === '') {
        $message = "Full name, father's name, and registration number are required.";
    } else {
        // Photo upload (optional)
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $upload_dir = '../uploads/students/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'student_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    compressUploadedImage($upload_dir . $filename);
                    $photo_path = 'uploads/students/' . $filename;

                    // Remove the old photo when replacing one on an existing record
                    if (!empty($_POST['student_id'])) {
                        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM students WHERE id = " . intval($_POST['student_id'])));
                        if ($old && !empty($old['photo']) && file_exists('../' . $old['photo'])) {
                            unlink('../' . $old['photo']);
                        }
                    }
                }
            }
        }
        $photo_sql = $photo_path !== null ? ", photo='" . mysqli_real_escape_string($conn, $photo_path) . "'" : '';

        if (!empty($_POST['student_id'])) {
            $id = intval($_POST['student_id']);
            $reg_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM students WHERE registration_no = '$registration_no' AND id != $id"));
            if ($reg_check) {
                $message = "Error: this registration number is already in use by another student.";
            } else {
                $q = "UPDATE students SET registration_no='$registration_no', full_name='$full_name', father_name='$father_name', cnic='$cnic', gender='$gender', date_of_birth=$dob, phone='$phone', email='$email', address='$address', course_id=$course_id, semester='$semester', admission_date=$admission_date, status='$status', previous_education='$previous_education', special_notes='$special_notes'$photo_sql WHERE id=$id";
                $message = mysqli_query($conn, $q) ? "Student record updated successfully!" : "Error updating student record.";
            }
        } else {
            $reg_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM students WHERE registration_no = '$registration_no'"));
            if ($reg_check) {
                $message = "Error: this registration number is already in use.";
            } else {
                $converted_from = intval($_POST['converted_from_admission_id'] ?? 0) ?: 'NULL';
                $photo_col_sql = $photo_path !== null ? "'" . mysqli_real_escape_string($conn, $photo_path) . "'" : 'NULL';
                $q = "INSERT INTO students (registration_no, full_name, father_name, cnic, gender, date_of_birth, phone, email, address, course_id, semester, admission_date, status, previous_education, special_notes, converted_from_admission_id, photo) VALUES ('$registration_no', '$full_name', '$father_name', '$cnic', '$gender', $dob, '$phone', '$email', '$address', $course_id, '$semester', $admission_date, '$status', '$previous_education', '$special_notes', $converted_from, $photo_col_sql)";
                if (mysqli_query($conn, $q)) {
                    $new_id = mysqli_insert_id($conn);
                    if ($converted_from !== 'NULL') {
                        mysqli_query($conn, "UPDATE admissions SET converted_student_id = $new_id WHERE id = $converted_from");
                    }
                    // Best-effort ERP sync — safe no-op until AdminCP > ERP Integration is configured
                    pushStudentToErp($conn, $new_id);
                    header('Location: manage_students.php?edit=' . $new_id . '&msg=' . urlencode('Student record created successfully!'));
                    exit;
                } else {
                    $message = "Error creating student record.";
                }
            }
        }
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Item being edited
$edit_student = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE id = $edit_id"));
}

// Pre-fill from an approved admission application being converted
$from_admission = null;
if (isset($_GET['from_admission']) && !$edit_student) {
    $adm_id = intval($_GET['from_admission']);
    $from_admission = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admissions WHERE id = $adm_id AND status = 'approved' AND converted_student_id IS NULL"));
}

// Courses for the dropdown
$courses_list = [];
$cq = mysqli_query($conn, "SELECT id, name FROM courses WHERE status = 'active' ORDER BY display_order ASC");
if ($cq) { while ($row = mysqli_fetch_assoc($cq)) { $courses_list[] = $row; } }

// Search / filter
$search = trim($_GET['q'] ?? '');
$filter_course = intval($_GET['course_id'] ?? 0);
$filter_status = $_GET['status'] ?? '';

$where = [];
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(s.full_name LIKE '%$s%' OR s.father_name LIKE '%$s%' OR s.registration_no LIKE '%$s%')";
}
if ($filter_course > 0) {
    $where[] = "s.course_id = $filter_course";
}
if (in_array($filter_status, ['active', 'graduated', 'left', 'suspended'])) {
    $where[] = "s.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$students_result = mysqli_query($conn, "SELECT s.*, c.name as course_name FROM students s LEFT JOIN courses c ON s.course_id = c.id $where_sql ORDER BY s.created_at DESC");

$status_colors = ['active' => '#16A34A', 'graduated' => '#0BA6C9', 'left' => '#64748B', 'suspended' => '#F59E0B'];

// The form starts collapsed behind an "Add Student" button — it only opens by
// itself when there's something to show in it: editing, converting from an
// application, or re-displaying after a failed submit (fields would just be
// blank otherwise and the error message would have nothing to point at).
$show_form = $edit_student || $from_admission || $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: var(--primary-color); color: #fff; padding: 14px; text-align: left; white-space: nowrap; }
        .data-table td { padding: 14px; border-bottom: 1px solid #EDF2F7; vertical-align: middle; white-space: nowrap; }
        .data-table tr:hover { background: #F4F8FC; }
        .action-btn { padding: 6px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: var(--primary-color); color: #fff; }
        .btn-delete { background: #DC2626; color: #fff; }
        .filter-bar { display: flex; gap: 12px; align-items: flex-end; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-bar .form-group { margin-bottom: 0; min-width: 180px; }
        .avatar-circle {
            width: 36px; height: 36px; border-radius: 50%; overflow: hidden;
            background: var(--primary-color); color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; flex-shrink: 0;
        }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .status-pill { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; color:#fff; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="admin-page-title">
            <h1><i class="fas fa-user-graduate"></i> Manage Students</h1>
            <button type="button" onclick="toggleStudentForm()" class="btn btn-primary"><i class="fas fa-plus"></i> Add Student</button>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div id="student-form-wrap" style="<?php echo $show_form ? '' : 'display:none;'; ?>">
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">
                <?php echo $edit_student ? 'Edit Student' : ($from_admission ? 'Convert Application to Student' : 'Add Student'); ?>
            </h2>

            <?php if ($from_admission): ?>
                <p style="color: var(--text-light); margin-top:-12px; margin-bottom:18px; font-size:13.5px;">
                    Pre-filled from admission application #<?php echo $from_admission['id']; ?> (<?php echo htmlspecialchars($from_admission['student_name']); ?>). Fill in the registration number and any remaining details, then save.
                </p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                <?php if ($edit_student): ?>
                    <input type="hidden" name="student_id" value="<?php echo $edit_student['id']; ?>">
                <?php elseif ($from_admission): ?>
                    <input type="hidden" name="converted_from_admission_id" value="<?php echo $from_admission['id']; ?>">
                <?php endif; ?>

                <?php
                $v = $edit_student ?: [];
                if ($from_admission) {
                    $v = [
                        'full_name' => $from_admission['student_name'],
                        'father_name' => $from_admission['father_name'],
                        'cnic' => $from_admission['cnic_bform'],
                        'phone' => $from_admission['phone'],
                        'email' => $from_admission['email'],
                        'address' => $from_admission['address'],
                        'course_id' => $from_admission['course_id'],
                        'previous_education' => $from_admission['previous_education'],
                    ];
                }
                ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_1">Registration No. *</label>
                        <input id="auto_AdminCP_manage_students_php_1" type="text" name="registration_no" required value="<?php echo htmlspecialchars($edit_student['registration_no'] ?? nextRegistrationNo($conn)); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_2">Full Name *</label>
                        <input id="auto_AdminCP_manage_students_php_2" type="text" name="full_name" required value="<?php echo htmlspecialchars($v['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_3">Father's Name *</label>
                        <input id="auto_AdminCP_manage_students_php_3" type="text" name="father_name" required value="<?php echo htmlspecialchars($v['father_name'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_4">CNIC</label>
                        <input id="auto_AdminCP_manage_students_php_4" type="text" name="cnic" placeholder="xxxxx-xxxxxxx-x" value="<?php echo htmlspecialchars($v['cnic'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_5">Gender</label>
                        <select id="auto_AdminCP_manage_students_php_5" name="gender">
                            <option value="male" <?php echo (($v['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (($v['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_6">Date of Birth</label>
                        <input id="auto_AdminCP_manage_students_php_6" type="date" name="date_of_birth" value="<?php echo htmlspecialchars($v['date_of_birth'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_7">Phone</label>
                        <input id="auto_AdminCP_manage_students_php_7" type="text" name="phone" value="<?php echo htmlspecialchars($v['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_8">Email</label>
                        <input id="auto_AdminCP_manage_students_php_8" type="email" name="email" value="<?php echo htmlspecialchars($v['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_students_php_9">Address</label>
                    <textarea id="auto_AdminCP_manage_students_php_9" name="address" rows="2"><?php echo htmlspecialchars($v['address'] ?? ''); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_10">Course / Program</label>
                        <select id="auto_AdminCP_manage_students_php_10" name="course_id">
                            <option value="">— Select —</option>
                            <?php foreach ($courses_list as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (($v['course_id'] ?? '') == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_11">Semester / Year</label>
                        <input id="auto_AdminCP_manage_students_php_11" type="text" name="semester" placeholder="e.g. 1st Semester" value="<?php echo htmlspecialchars($v['semester'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_12">Admission Date</label>
                        <input id="auto_AdminCP_manage_students_php_12" type="date" name="admission_date" value="<?php echo htmlspecialchars($v['admission_date'] ?? ($edit_student ? '' : date('Y-m-d'))); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_13">Status</label>
                        <select id="auto_AdminCP_manage_students_php_13" name="status">
                            <?php foreach (['active' => 'Active', 'graduated' => 'Graduated', 'left' => 'Left', 'suspended' => 'Suspended'] as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo (($v['status'] ?? 'active') === $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_14">Previous Education</label>
                        <input id="auto_AdminCP_manage_students_php_14" type="text" name="previous_education" value="<?php echo htmlspecialchars($v['previous_education'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="auto_AdminCP_manage_students_php_15">Photo</label>
                        <input id="auto_AdminCP_manage_students_php_15" type="file" name="photo" accept="image/*">
                        <?php if ($edit_student && !empty($edit_student['photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($edit_student['photo']); ?>" style="height:50px;border-radius:8px;margin-top:8px;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_manage_students_php_16">Special Notes</label>
                    <textarea id="auto_AdminCP_manage_students_php_16" name="special_notes" rows="2"><?php echo htmlspecialchars($v['special_notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_student ? 'Save Changes' : 'Create Student Record'; ?></button>
                <?php if ($edit_student || $from_admission): ?>
                    <a href="manage_students.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php else: ?>
                    <button type="button" onclick="toggleStudentForm()" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        </div>

        <script>
            function toggleStudentForm() {
                var wrap = document.getElementById('student-form-wrap');
                var open = wrap.style.display !== 'none';
                wrap.style.display = open ? 'none' : '';
                if (!open) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        </script>

        <!-- Filters -->
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label for="auto_AdminCP_manage_students_php_17">Search</label>
                <input id="auto_AdminCP_manage_students_php_17" type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, father's name, reg. no.">
            </div>
            <div class="form-group">
                <label for="auto_AdminCP_manage_students_php_18">Course</label>
                <select id="auto_AdminCP_manage_students_php_18" name="course_id">
                    <option value="">All Courses</option>
                    <?php foreach ($courses_list as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $filter_course == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="auto_AdminCP_manage_students_php_19">Status</label>
                <select id="auto_AdminCP_manage_students_php_19" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['active', 'graduated', 'left', 'suspended'] as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $filter_status === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            <?php if ($search !== '' || $filter_course || $filter_status !== ''): ?>
                <a href="manage_students.php" class="btn btn-primary" style="background: var(--text-light);">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Students List -->
        <div class="card" style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th></th><th>Reg. No.</th><th>Name</th><th>Course</th><th>Father's Name</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($students_result && mysqli_num_rows($students_result) > 0): ?>
                    <?php while ($s = mysqli_fetch_assoc($students_result)): ?>
                        <tr>
                            <td>
                                <div class="avatar-circle">
                                    <?php if (!empty($s['photo'])): ?>
                                        <img src="../<?php echo htmlspecialchars($s['photo']); ?>" alt="">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($s['registration_no']); ?></td>
                            <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($s['course_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($s['father_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['phone'] ?: '—'); ?></td>
                            <td><span class="status-pill" style="background: <?php echo $status_colors[$s['status']] ?? '#64748B'; ?>;"><?php echo ucfirst($s['status']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $s['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $s['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete the student record for &quot;<?php echo htmlspecialchars(addslashes($s['full_name'])); ?>&quot;?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-light);">No students found<?php echo ($search || $filter_course || $filter_status) ? ' for these filters' : ' yet'; ?>.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
