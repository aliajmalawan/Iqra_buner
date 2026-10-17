<?php
require_once 'includes/config.php';
$page_title = 'Admission';
$page_description = 'Apply for admission to IQRA School & College Daggar Buner — Play Group to Intermediate at our Boys and Girls campuses. View the admission process, required documents and the online application form.';

// Campuses (same editable settings as the Courses page)
$adm_campuses = array_values(array_filter([
    trim(getSetting('cr_campus1_name', 'Boys Campus')),
    trim(getSetting('cr_campus2_name', 'Girls Campus')),
]));

// Active programs grouped by level (AdminCP > Academics > Courses)
$adm_levels = [
    'school' => ['label' => 'School',       'sub' => 'Play Group – Class 8',   'icon' => 'fa-school'],
    'matric' => ['label' => 'Matric',       'sub' => 'Class 9 – 10 · Science', 'icon' => 'fa-flask'],
    'inter'  => ['label' => 'Intermediate', 'sub' => 'F.Sc · ICS · F.A',       'icon' => 'fa-graduation-cap'],
];
$adm_programs = [];
$cr = mysqli_query($conn, "SELECT id, name, category, eligibility FROM courses WHERE status='active' ORDER BY display_order ASC, name ASC");
while ($cr && ($c = mysqli_fetch_assoc($cr))) {
    $adm_programs[isset($adm_levels[$c['category']]) ? $c['category'] : 'other'][] = $c;
}
if (!empty($adm_programs['other'])) $adm_levels['other'] = ['label' => 'Other Programs', 'sub' => 'Additional programs', 'icon' => 'fa-book-open'];

$success_message = '';
$error_message   = '';
$application_number = '';
$submitted = [];
$errors = [];
// Sticky form values — repopulated on validation failure so the applicant doesn't retype everything
$f = [
    'student_name' => '', 'father_name' => '', 'cnic_bform' => '', 'phone' => '', 'email' => '',
    'address' => '', 'course_id' => '', 'campus' => '', 'qualification' => '', 'board_university' => '',
    'passing_year' => '', 'marks_percentage' => '', 'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($f as $key => $default) {
        $f[$key] = trim($_POST[$key] ?? '');
    }

    // ---- Validation (server-side; do not trust the browser) ----
    if ($f['student_name'] === '') $errors[] = 'Student name is required.';
    if ($f['father_name'] === '') $errors[] = 'Father / guardian name is required.';

    $cnic_digits = preg_replace('/\D/', '', $f['cnic_bform']);
    if ($f['cnic_bform'] === '') {
        $errors[] = 'CNIC / B-Form number is required.';
    } elseif (strlen($cnic_digits) !== 13) {
        $errors[] = 'CNIC / B-Form must be a valid 13-digit number (e.g., 12345-1234567-1).';
    }

    $phone_digits = preg_replace('/\D/', '', $f['phone']);
    if ($f['phone'] === '') {
        $errors[] = 'Phone number is required.';
    } elseif (strlen($phone_digits) < 10 || strlen($phone_digits) > 12) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address, or leave it blank.';
    }

    if ($f['address'] === '') $errors[] = 'Residential address is required.';

    $course_id = intval($f['course_id']);
    $course_row = $course_id > 0 ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name FROM courses WHERE id = $course_id AND status='active'")) : null;
    if (!$course_row) $errors[] = 'Please select a valid class / program.';

    if ($adm_campuses && !in_array($f['campus'], $adm_campuses, true)) $errors[] = 'Please select a campus.';

    if ($f['qualification'] === '') $errors[] = 'Last class / qualification is required.';

    if ($f['marks_percentage'] !== '') {
        if (!is_numeric($f['marks_percentage']) || $f['marks_percentage'] < 0 || $f['marks_percentage'] > 100) {
            $errors[] = 'Marks / percentage must be a number between 0 and 100.';
        }
    }

    if ($f['passing_year'] !== '') {
        $current_year = (int) date('Y');
        if (!preg_match('/^\d{4}$/', $f['passing_year']) || (int) $f['passing_year'] < 1990 || (int) $f['passing_year'] > $current_year + 1) {
            $errors[] = 'Please enter a valid passing year.';
        }
    }

    // ---- Document upload (optional, but validated when present) ----
    $document_path = '';
    if (empty($errors) && isset($_FILES['documents']) && $_FILES['documents']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['documents']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading your document. Please try again.';
        } else {
            $max_size = 5 * 1024 * 1024; // 5MB
            $ext = strtolower(pathinfo($_FILES['documents']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
            if ($_FILES['documents']['size'] > $max_size) {
                $errors[] = 'Uploaded document must be smaller than 5MB.';
            } elseif (!in_array($ext, $allowed_ext, true)) {
                $errors[] = 'Documents must be a PDF, JPG or PNG file.';
            } else {
                $upload_dir = 'uploads/documents/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $safe_name = 'app_' . time() . '_' . uniqid() . '.' . $ext;
                $target_file = $upload_dir . $safe_name;
                if (move_uploaded_file($_FILES['documents']['tmp_name'], $target_file)) {
                    compressUploadedImage($target_file); // no-op when the upload is a PDF, not an image
                    $document_path = $target_file;
                } else {
                    $errors[] = 'Could not save the uploaded document. Please try again.';
                }
            }
        }
    }

    if (empty($errors)) {
        $student_name       = mysqli_real_escape_string($conn, $f['student_name']);
        $father_name        = mysqli_real_escape_string($conn, $f['father_name']);
        $cnic_bform         = mysqli_real_escape_string($conn, $f['cnic_bform']);
        $phone              = mysqli_real_escape_string($conn, $f['phone']);
        $email              = mysqli_real_escape_string($conn, $f['email']);
        $address            = mysqli_real_escape_string($conn, $f['address']);
        $medium             = 'English';
        $qualification      = mysqli_real_escape_string($conn, $f['qualification']);
        $board_university   = mysqli_real_escape_string($conn, $f['board_university']);
        $passing_year       = $f['passing_year'] !== '' ? "'" . mysqli_real_escape_string($conn, $f['passing_year']) . "'" : 'NULL';
        $marks_percentage   = $f['marks_percentage'] !== '' ? floatval($f['marks_percentage']) : 'NULL';
        // The admissions table has no campus column — the chosen campus is kept at the top of the message so admins see it
        $full_message       = trim(($f['campus'] !== '' ? 'Campus: ' . $f['campus'] . "\n" : '') . $f['message']);
        $message            = mysqli_real_escape_string($conn, $full_message);
        // previous_education kept as a human-readable summary for backward compatibility with existing admin views/reports
        $previous_education = mysqli_real_escape_string($conn, trim($f['qualification'] . ($f['board_university'] !== '' ? ', ' . $f['board_university'] : '') . ($f['passing_year'] !== '' ? ', ' . $f['passing_year'] : '') . ($f['marks_percentage'] !== '' ? ' — ' . $f['marks_percentage'] . '%' : '')));

        $q = "INSERT INTO admissions (student_name,father_name,cnic_bform,phone,email,address,course_id,medium,previous_education,qualification,board_university,passing_year,marks_percentage,documents,message,status,created_at)
              VALUES ('$student_name','$father_name','$cnic_bform','$phone','$email','$address','$course_id','$medium','$previous_education','$qualification','$board_university',$passing_year,$marks_percentage,'$document_path','$message','pending',NOW())";

        if (mysqli_query($conn, $q)) {
            $new_id = mysqli_insert_id($conn);
            $application_number = 'IQRA-' . date('Y') . '-' . str_pad($new_id, 6, '0', STR_PAD_LEFT);
            mysqli_query($conn, "UPDATE admissions SET application_number = '" . mysqli_real_escape_string($conn, $application_number) . "' WHERE id = $new_id");

            // Best-effort ERP sync — safe no-op until AdminCP > ERP Integration is configured;
            // never affects the applicant-facing success message below.
            pushAdmissionToErp($conn, $new_id);

            $course_name = $course_row['name'];
            $campus_row  = $f['campus'] !== '' ? "<tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Campus</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($f['campus'])."</td></tr>" : '';

            // Admin email
            $msg_admin = "<html><body style='font-family:Arial;'>
                <div style='background:#102A56;color:#fff;padding:20px;text-align:center;'><h2>New Admission Application</h2></div>
                <div style='padding:20px;background:#F4F8FC;'>
                <table width='100%' style='border-collapse:collapse;'>
                <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;width:200px;'>Application #</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($application_number)."</td></tr>
                <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Student Name</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($f['student_name'])."</td></tr>
                <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Father Name</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($f['father_name'])."</td></tr>
                <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Phone</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($f['phone'])."</td></tr>
                <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Class / Program</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($course_name)."</td></tr>
                $campus_row
                <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Date</td><td style='padding:10px;'>".date('d M Y, h:i A')."</td></tr>
                </table></div>
                <div style='background:#333;color:#fff;padding:12px;text-align:center;font-size:12px;'>".SITE_NAME." — Automated Notification</div>
                </body></html>";
            $h = "MIME-Version:1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom:".SITE_NAME." <noreply@" . preg_replace('/^www\./', '', strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':')) . ">\r\nReply-To:".$f['email'];
            @mail(ADMIN_EMAIL, "New Admission - ".SITE_NAME, $msg_admin, $h);

            if (!empty($f['email'])) {
                $msg_stu = "<html><body style='font-family:Arial;'>
                    <div style='background:#102A56;color:#fff;padding:24px;text-align:center;'><h2>".SITE_NAME."</h2><p>Admission Application Received</p></div>
                    <div style='padding:24px;'>
                    <p>Dear <strong>".htmlspecialchars($f['student_name'])."</strong>,</p>
                    <p>Your application for <strong>".htmlspecialchars($course_name)."</strong>".($f['campus'] !== '' ? " at our <strong>".htmlspecialchars($f['campus'])."</strong>" : '')." has been received.</p>
                    <p style='background:#E9F6FA;padding:14px;border-left:4px solid #102A56;border-radius:4px;'><strong>Your Application Number:</strong> ".htmlspecialchars($application_number)."<br>Please keep this for your records — our team will contact you within 2-3 working days.</p>
                    <p>Contact: ".SITE_CONTACT." | ".ADMIN_EMAIL."</p>
                    <br><p>Best Regards,<br>Admission Team<br>".SITE_NAME."</p>
                    </div></body></html>";
                $hs = "MIME-Version:1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom:".SITE_NAME." <noreply@" . preg_replace('/^www\./', '', strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':')) . ">";
                @mail($f['email'], "Application Received - ".SITE_NAME, $msg_stu, $hs);
            }
            $success_message = "Your admission application has been submitted successfully! We will contact you within 2-3 working days.";
            $submitted = ['student' => $f['student_name'], 'program' => $course_name, 'campus' => $f['campus'], 'date' => date('d M Y')];
            // Clear sticky values now that submission succeeded
            foreach ($f as $key => $default) { $f[$key] = ''; }
        } else {
            $error_message = "Error submitting application. Please try again.";
        }
    } else {
        $error_message = "Please fix the following before submitting:";
    }
}

$selected_course = $f['course_id'] !== '' ? $f['course_id'] : ($_GET['course'] ?? '');
$selected_campus = $f['campus'] !== '' ? $f['campus'] : ($_GET['campus'] ?? '');
$adm_steps = getAdmissionSteps();
$adm_docs  = getAdmissionDocuments();
$adm_hero_image = getSetting('adm_hero_image', 'images/banners/banner-building.jpg');
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Admission — uses the shared system in css/style.css
   (.adm-field / .adm-submit / .adm-alert get their base styles there)
   ================================================================ */

/* ── Hero ── */
.adm-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:400px; display:flex; align-items:center;
    padding:72px 0 64px; background:var(--primary-dark);
}
.adm-hero-img { position:absolute; inset:0; z-index:-2; }
.adm-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 35%; }
.adm-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%);
}
.adm-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.adm-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.adm-breadcrumb a:hover { color:#fff; }
.adm-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.adm-hero-badge {
    display:inline-flex; align-items:center; gap:10px;
    color:rgba(255,255,255,0.9); font-family:var(--font-heading);
    font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px;
}
.adm-hero-badge .dot { width:8px; height:8px; border-radius:50%; background:#22C55E; box-shadow:0 0 0 4px rgba(34,197,94,0.25); }
.adm-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.adm-hero h1 span { color:var(--accent-bright); }
.adm-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:620px; }
.adm-hero-facts { display:flex; flex-wrap:wrap; gap:10px; margin-top:22px; }
.adm-hero-facts span {
    display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:999px;
    background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.18);
    color:#fff; font-size:var(--fs-caption); font-weight:500;
}
.adm-hero-facts i { color:var(--gold-color); font-size:12px; }
.adm-hero-btns { display:flex; flex-wrap:wrap; gap:12px; margin-top:28px; }

.adm-sh { text-align:center; margin-bottom:40px; }
.adm-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.adm-sh h2 span { color:var(--accent-hover); }
.adm-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

/* ── Process ── */
.adm-process { padding:var(--section-y) 0; background:#fff; }
.adm-steps { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; list-style:none; padding:0; margin:0; counter-reset:step; }
.adm-step {
    position:relative; padding:26px 24px 24px; border-radius:var(--radius-card);
    background:var(--background-color); border:1px solid var(--border-color);
    transition:transform var(--dur) var(--ease-out), box-shadow var(--dur) ease, border-color var(--dur) ease;
}
.adm-step:hover { transform:translateY(-4px); box-shadow:var(--shadow-card-hover); border-color:rgba(11,166,201,0.35); background:#fff; }
.adm-step-num {
    width:44px; height:44px; border-radius:12px; margin-bottom:16px;
    display:flex; align-items:center; justify-content:center;
    background:var(--primary-color); color:#fff; font-family:var(--font-heading); font-size:17px; font-weight:700;
}
.adm-step:last-child .adm-step-num { background:var(--gold-color); color:var(--primary-dark); }
.adm-step h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); margin:0 0 6px; }
.adm-step p { font-size:var(--fs-body-sm); color:var(--text-light); line-height:1.65; margin:0; }
.adm-step:not(:last-child)::after {
    content:'\f054'; font-family:'Font Awesome 6 Free'; font-weight:900;
    position:absolute; top:38px; right:-17px; z-index:1;
    width:24px; height:24px; border-radius:50%; background:#fff; border:1px solid var(--border-color);
    display:flex; align-items:center; justify-content:center; font-size:9px; color:var(--accent-color);
}

/* ── Who can apply ── */
.adm-levels { padding:var(--section-y) 0; background:var(--background-color); }
.adm-level-grid { display:grid; grid-template-columns:repeat(<?php echo max(1, min(3, count($adm_programs))); ?>,1fr); gap:24px; }
.adm-level {
    display:flex; flex-direction:column; border-radius:var(--radius-card); overflow:hidden;
    background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card);
}
.adm-level-head { display:flex; align-items:center; gap:14px; padding:20px 22px; background:var(--primary-color); border-bottom:3px solid var(--gold-color); }
.adm-level-head .ui-icon { margin:0; width:46px; height:46px; background:rgba(255,255,255,0.1); color:var(--accent-bright); }
.adm-level-head h3 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0; }
.adm-level-head span { font-size:var(--fs-caption); color:rgba(255,255,255,0.72); }
.adm-level ul { list-style:none; margin:0; padding:8px 22px; flex:1; }
.adm-level li { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-color); }
.adm-level li:last-child { border-bottom:none; }
.adm-level li strong { font-size:var(--fs-body-sm); font-weight:600; color:var(--text-color); line-height:1.45; }
.adm-level li small { flex-shrink:0; padding:3px 9px; border-radius:6px; background:var(--light-blue); color:var(--accent-text); font-size:12px; font-weight:600; white-space:nowrap; }
.adm-level-foot { padding:14px 22px 20px; }
.adm-level-foot a { font-size:var(--fs-body-sm); font-weight:600; color:var(--accent-text); text-decoration:none; display:inline-flex; align-items:center; gap:8px; min-height:44px; }
.adm-level-foot a:hover { color:var(--primary-color); }

/* ── Main: form + sidebar ── */
.adm-main { padding:var(--section-y) 0; background:#fff; }
.adm-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:32px; align-items:start; }

.adm-form-card { background:#fff; border-radius:var(--radius-card); border:1px solid var(--border-color); box-shadow:var(--shadow-card); overflow:hidden; }
.adm-form-header { display:flex; align-items:center; gap:16px; padding:24px 30px; background:var(--primary-color); border-bottom:3px solid var(--gold-color); }
.adm-form-header .ui-icon { margin:0; width:50px; height:50px; background:rgba(255,255,255,0.1); color:var(--accent-bright); }
.adm-form-header h2 { color:#fff; font-size:var(--fs-h3); font-weight:700; margin:0 0 3px; }
.adm-form-header p { color:rgba(255,255,255,0.75); font-size:var(--fs-body-sm); margin:0; }
.adm-form-body { padding:30px; }

.adm-error-list { margin:8px 0 0; padding-left:18px; }
.adm-error-list li { margin-bottom:3px; }

/* Sections inside the form */
.adm-fs { border:0; margin:0; padding:0; min-width:0; }
.adm-fs + .adm-fs { margin-top:30px; padding-top:28px; border-top:1px solid var(--border-color); }
.adm-fs legend { display:flex; align-items:center; gap:12px; padding:0; margin:0 0 18px; float:left; width:100%; }
.adm-fs legend + * { clear:both; }
.adm-fs-num {
    width:32px; height:32px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:var(--light-blue); color:var(--accent-text); font-family:var(--font-heading); font-size:14px; font-weight:700;
}
.adm-fs-title { font-family:var(--font-heading); font-size:var(--fs-card-title); font-weight:600; color:var(--primary-color); }
.adm-fs-title small { display:block; font-family:var(--font-body); font-size:var(--fs-caption); font-weight:400; color:var(--text-light); margin-top:2px; }

.adm-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.adm-grid .span-2 { grid-column:1 / -1; }

/* Campus choice */
.adm-campus-opts { display:grid; grid-template-columns:repeat(<?php echo max(1, count($adm_campuses)); ?>,1fr); gap:12px; }
.adm-campus-opt { position:relative; display:block; cursor:pointer; }
.adm-campus-opt input { position:absolute !important; top:50%; left:50%; width:1px !important; height:1px !important; min-height:0 !important; margin:0; padding:0; opacity:0 !important; pointer-events:none; }
.adm-campus-opt span {
    display:flex; align-items:center; gap:12px; min-height:56px; padding:12px 16px; border-radius:10px;
    border:1.5px solid var(--input-border, var(--border-color)); background:#fff;
    font-weight:600; color:var(--text-color);
    transition:border-color var(--dur) ease, background-color var(--dur) ease, box-shadow var(--dur) ease;
}
.adm-campus-opt span i { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; background:var(--background-color); color:var(--accent-color); flex-shrink:0; }
.adm-campus-opt span::after { content:''; margin-left:auto; width:18px; height:18px; border-radius:50%; border:2px solid var(--border-color); flex-shrink:0; transition:border var(--dur) ease; }
.adm-campus-opt:hover span { border-color:var(--input-border-hover, var(--primary-color)); }
.adm-campus-opt input:checked + span { border-color:var(--accent-color); background:rgba(11,166,201,0.06); }
.adm-campus-opt input:checked + span::after { border:6px solid var(--accent-color); }
.adm-campus-opt input:checked + span i { background:var(--accent-color); color:#fff; }
.adm-campus-opt input[aria-invalid="true"] + span { border-color:var(--danger-color); }
.adm-campus-opt input:focus-visible + span { box-shadow:0 0 0 3px rgba(11,166,201,0.3); }
.adm-label { display:block; font-size:var(--fs-body-sm); font-weight:600; color:var(--text-color); margin-bottom:8px; }
.adm-label .req { color:var(--danger-color); margin-left:2px; }

/* File upload */
.adm-upload {
    position:relative; border:1.5px dashed rgba(16,42,86,0.28); border-radius:12px;
    background:var(--background-color); transition:border-color var(--dur) ease, background-color var(--dur) ease;
}
.adm-upload:hover, .adm-upload.is-drag { border-color:var(--accent-color); background:rgba(11,166,201,0.05); }
.adm-upload:focus-within { border-color:var(--accent-color); box-shadow:0 0 0 3px rgba(11,166,201,0.2); }
.adm-upload-label { display:flex; align-items:center; gap:16px; padding:18px 20px; cursor:pointer; }
.adm-upload-label .ui-icon { margin:0; width:46px; height:46px; flex-shrink:0; }
.adm-upload-text strong { display:block; font-size:var(--fs-body-sm); color:var(--primary-color); overflow-wrap:anywhere; }
.adm-upload-text span { display:block; font-size:var(--fs-caption); color:var(--text-light); margin-top:2px; }

.adm-consent { display:flex; gap:10px; align-items:flex-start; margin-top:24px; font-size:var(--fs-caption); color:var(--text-light); line-height:1.6; }
.adm-consent i { color:var(--accent-color); margin-top:3px; }
.adm-submit { margin-top:18px; width:100%; }

/* Success state */
.adm-success { text-align:center; padding:12px 0 4px; }
.adm-success-ico {
    width:76px; height:76px; margin:0 auto 18px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:rgba(22,163,74,0.1); color:#16A34A; font-size:34px;
}
.adm-success h3 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0 0 8px; }
.adm-success > p { color:var(--text-light); font-size:var(--fs-body); max-width:480px; margin:0 auto; line-height:1.7; }
.adm-app-number {
    margin:24px auto 0; max-width:420px; padding:18px 20px; border-radius:12px;
    background:var(--background-color); border:1.5px dashed var(--accent-color);
}
.adm-app-number span { display:block; font-size:var(--fs-overline); font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--accent-text); }
.adm-app-number strong { display:block; font-family:var(--font-heading); font-size:clamp(1.35rem, 1.1rem + 1vw, 1.75rem); font-weight:700; color:var(--primary-color); letter-spacing:0.5px; margin:4px 0; }
.adm-app-number small { font-size:var(--fs-caption); color:var(--text-light); }
.adm-summary { max-width:420px; margin:18px auto 0; text-align:left; border:1px solid var(--border-color); border-radius:12px; overflow:hidden; }
.adm-summary div { display:flex; justify-content:space-between; gap:16px; padding:10px 16px; font-size:var(--fs-body-sm); }
.adm-summary div + div { border-top:1px solid var(--border-color); }
.adm-summary dt { color:var(--text-light); }
.adm-summary dd { margin:0; font-weight:600; color:var(--text-color); text-align:right; }
.adm-success-btns { display:flex; justify-content:center; flex-wrap:wrap; gap:12px; margin-top:26px; }

/* ── Sidebar ── */
.adm-sidebar { display:flex; flex-direction:column; gap:22px; position:sticky; top:110px; }
.adm-side-card { background:#fff; border-radius:var(--radius-card); border:1px solid var(--border-color); box-shadow:var(--shadow-card); overflow:hidden; }
.adm-side-head { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid var(--border-color); }
.adm-side-head i { color:var(--accent-color); font-size:15px; }
.adm-side-head h3 { font-size:15px; font-weight:600; color:var(--primary-color); margin:0; }
.adm-side-body { padding:14px 20px 18px; }
.adm-side-card.is-dark { background:var(--primary-color); border-color:var(--primary-color); border-bottom:3px solid var(--gold-color); }
.adm-side-card.is-dark .adm-side-head { border-color:rgba(255,255,255,0.12); }
.adm-side-card.is-dark .adm-side-head h3 { color:#fff; }
.adm-side-card.is-dark .adm-side-head i { color:var(--gold-color); }

.adm-doc-list { list-style:none; margin:0; padding:0; }
.adm-doc-list li { display:flex; gap:10px; padding:8px 0; font-size:var(--fs-body-sm); color:var(--text-color); line-height:1.5; }
.adm-doc-list li + li { border-top:1px solid var(--border-color); }
.adm-doc-list i { color:var(--accent-color); font-size:13px; margin-top:4px; flex-shrink:0; }

.adm-contact-row { display:flex; align-items:center; gap:12px; padding:10px 0; }
.adm-contact-row + .adm-contact-row { border-top:1px solid rgba(255,255,255,0.12); }
.adm-contact-ico { width:36px; height:36px; border-radius:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.1); color:var(--accent-bright); font-size:13px; }
.adm-contact-row .k { font-size:var(--fs-caption); color:rgba(255,255,255,0.6); }
.adm-contact-row a, .adm-contact-row .v { color:#fff; font-size:var(--fs-body-sm); font-weight:600; text-decoration:none; word-break:break-word; }
.adm-contact-row a:hover { color:var(--accent-bright); }

.adm-tools { list-style:none; margin:0; padding:0; }
.adm-tools a { display:flex; align-items:center; gap:12px; padding:10px 0; color:var(--text-color); text-decoration:none; font-size:var(--fs-body-sm); font-weight:500; }
.adm-tools li + li a { border-top:1px solid var(--border-color); }
.adm-tools a > i:first-child { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; background:var(--background-color); color:var(--accent-color); font-size:13px; flex-shrink:0; }
.adm-tools a > i:last-child { margin-left:auto; font-size:11px; color:var(--text-light); transition:transform var(--dur) ease; }
.adm-tools a:hover { color:var(--primary-color); }
.adm-tools a:hover > i:last-child { transform:translateX(3px); color:var(--accent-color); }

/* ── CTA ── */
.adm-cta { padding:var(--section-y-sm) 0; background:var(--background-color); }
.adm-cta-box {
    display:flex; align-items:center; justify-content:space-between; gap:32px; flex-wrap:wrap;
    padding:40px 44px; border-radius:var(--radius-card);
    background:var(--primary-color); border-bottom:4px solid var(--gold-color);
}
.adm-cta-box h2 { font-size:var(--fs-h3); font-weight:700; color:#fff; margin:0 0 6px; }
.adm-cta-box p { color:rgba(255,255,255,0.75); font-size:var(--fs-body); margin:0; max-width:560px; }
.adm-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }

/* Reveal on scroll */
.adm-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.adm-reveal.is-visible { opacity:1; transform:none; }
.adm-step.adm-reveal.is-visible:hover { transform:translateY(-4px); }

/* ── Responsive ── */
@media (max-width:1100px) {
    .adm-steps { grid-template-columns:1fr 1fr; }
    .adm-step:nth-child(2)::after { display:none; }
}
@media (max-width:960px) {
    .adm-layout { grid-template-columns:1fr; }
    .adm-sidebar { position:static; display:grid; grid-template-columns:1fr 1fr; }
    .adm-level-grid { grid-template-columns:1fr; max-width:640px; margin:0 auto; }
}
@media (max-width:640px) {
    .adm-hero { min-height:0; padding:56px 0 48px; }
    .adm-hero-btns .btn { flex:1 1 100%; }
    .adm-steps { grid-template-columns:1fr; }
    .adm-step:not(:last-child)::after { display:none; }
    .adm-sidebar { grid-template-columns:1fr; }
    .adm-grid, .adm-campus-opts { grid-template-columns:1fr; }
    .adm-form-header { padding:20px; }
    .adm-form-body { padding:22px 18px; }
    .adm-cta-box { padding:28px 24px; }
    .adm-cta-btns { width:100%; }
    .adm-cta-btns .btn { flex:1 1 100%; }
}
@media print {
    .site-header, .site-footer, .adm-hero, .adm-process, .adm-levels, .adm-sidebar, .adm-cta, .adm-success-btns { display:none !important; }
    .adm-layout { display:block; }
    .adm-form-card { box-shadow:none; }
}
@media (prefers-reduced-motion:reduce) { .adm-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="adm-hero">
    <div class="adm-hero-img" aria-hidden="true">
        <img src="<?php echo htmlspecialchars($adm_hero_image); ?>" alt="" fetchpriority="high">
    </div>
    <div class="container">
        <nav class="adm-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Admission</span>
        </nav>
        <div class="adm-hero-badge"><span class="dot" aria-hidden="true"></span><?php echo htmlspecialchars(getSetting('adm_hero_badge', 'Admissions Open')); ?></div>
        <h1>Begin Your Journey at <span>IQRA</span></h1>
        <p><?php echo htmlspecialchars(getSetting('adm_hero_subtitle', 'Apply online for Play Group to Intermediate at our Boys and Girls campuses. Fill in the form below and our admission team will guide you through the rest.')); ?></p>
        <div class="adm-hero-facts">
            <?php foreach ($adm_campuses as $cn): ?><span><i class="fas fa-building-columns" aria-hidden="true"></i><?php echo htmlspecialchars($cn); ?></span><?php endforeach; ?>
            <span><i class="fas fa-layer-group" aria-hidden="true"></i>Play Group – Intermediate</span>
            <span><i class="fas fa-laptop" aria-hidden="true"></i>Online Application</span>
        </div>
        <div class="adm-hero-btns">
            <a href="#applyForm" class="btn btn-accent btn-lg"><i class="fas fa-pen-to-square"></i> Apply Online</a>
            <a href="tel:<?php echo preg_replace('/[^\d+]/', '', SITE_CONTACT); ?>" class="btn btn-outline btn-lg"><i class="fas fa-phone"></i> <?php echo htmlspecialchars(SITE_CONTACT); ?></a>
        </div>
    </div>
</section>

<!-- ── Admission process ── -->
<section class="adm-process">
    <div class="container">
        <div class="adm-sh">
            <div class="section-badge">How It Works</div>
            <h2>Admission in <span><?php echo count($adm_steps); ?> Simple Steps</span></h2>
            <p>A clear, simple process — from your online application to the first day of class.</p>
        </div>
        <ol class="adm-steps">
            <?php foreach ($adm_steps as $i => $s): ?>
            <li class="adm-step adm-reveal">
                <div class="adm-step-num" aria-hidden="true"><?php echo $i + 1; ?></div>
                <h3><?php echo htmlspecialchars($s['title']); ?></h3>
                <p><?php echo htmlspecialchars($s['desc']); ?></p>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php if ($adm_programs): ?>
<!-- ── Who can apply ── -->
<section class="adm-levels">
    <div class="container">
        <div class="adm-sh">
            <div class="section-badge">Admissions Open For</div>
            <h2>Classes &amp; <span>Programs</span></h2>
            <p>Choose the class or program you are applying for. Both campuses offer every level, from Play Group to Intermediate.</p>
        </div>
        <div class="adm-level-grid">
            <?php foreach ($adm_levels as $key => $lv): if (empty($adm_programs[$key])) continue; ?>
            <div class="adm-level adm-reveal">
                <div class="adm-level-head">
                    <span class="ui-icon"><i class="fas <?php echo $lv['icon']; ?>" aria-hidden="true"></i></span>
                    <div>
                        <h3><?php echo htmlspecialchars($lv['label']); ?></h3>
                        <span><?php echo htmlspecialchars($lv['sub']); ?></span>
                    </div>
                </div>
                <ul>
                    <?php foreach ($adm_programs[$key] as $p): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                        <?php if (trim((string) $p['eligibility']) !== ''): ?><small><?php echo htmlspecialchars($p['eligibility']); ?></small><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="adm-level-foot">
                    <a href="courses.php#programs">View program details <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── Main: Form + Sidebar ── -->
<section class="adm-main">
    <div class="container">
        <div class="adm-layout">

            <!-- ── Application Form ── -->
            <div class="adm-form-card" id="applyForm">
                <div class="adm-form-header">
                    <span class="ui-icon"><i class="fas fa-file-signature" aria-hidden="true"></i></span>
                    <div>
                        <h2>Online Admission Form</h2>
                        <p><?php echo $success_message ? 'Application received' : 'Fields marked * are required. It takes about 5 minutes.'; ?></p>
                    </div>
                </div>

                <div class="adm-form-body">
                    <?php if ($success_message): ?>
                    <div class="adm-success" role="status">
                        <div class="adm-success-ico"><i class="fas fa-check" aria-hidden="true"></i></div>
                        <h3>Application Submitted!</h3>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                        <?php if ($application_number): ?>
                        <div class="adm-app-number">
                            <span>Your Application Number</span>
                            <strong><?php echo htmlspecialchars($application_number); ?></strong>
                            <small>Please save this number — you'll need it when you contact the admission office.</small>
                        </div>
                        <?php endif; ?>
                        <?php if ($submitted): ?>
                        <dl class="adm-summary">
                            <div><dt>Student</dt><dd><?php echo htmlspecialchars($submitted['student']); ?></dd></div>
                            <div><dt>Class / Program</dt><dd><?php echo htmlspecialchars($submitted['program']); ?></dd></div>
                            <?php if ($submitted['campus'] !== ''): ?><div><dt>Campus</dt><dd><?php echo htmlspecialchars($submitted['campus']); ?></dd></div><?php endif; ?>
                            <div><dt>Date</dt><dd><?php echo htmlspecialchars($submitted['date']); ?></dd></div>
                        </dl>
                        <?php endif; ?>
                        <div class="adm-success-btns">
                            <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                            <a href="index.php" class="btn btn-primary"><i class="fas fa-house"></i> Back to Home</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                    <div class="adm-alert adm-alert-err" role="alert">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        <div>
                            <?php echo htmlspecialchars($error_message); ?>
                            <?php if (!empty($errors)): ?>
                            <ul class="adm-error-list">
                                <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$success_message): ?>
                    <form method="POST" enctype="multipart/form-data" id="admissionForm">

                        <!-- 1. Class & Campus -->
                        <fieldset class="adm-fs">
                            <legend><span class="adm-fs-num">1</span><span class="adm-fs-title">Class &amp; Campus<small>What are you applying for?</small></span></legend>
                            <div class="adm-grid">
                                <div class="adm-field span-2">
                                    <label for="adm_course">Class / Program <span class="req">*</span></label>
                                    <select id="adm_course" name="course_id" required>
                                        <option value="">— Choose a class or program —</option>
                                        <?php foreach ($adm_levels as $key => $lv): if (empty($adm_programs[$key])) continue; ?>
                                        <optgroup label="<?php echo htmlspecialchars($lv['label'] . ' (' . $lv['sub'] . ')'); ?>">
                                            <?php foreach ($adm_programs[$key] as $p): ?>
                                            <option value="<?php echo (int) $p['id']; ?>"<?php echo (string) $selected_course === (string) $p['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if ($adm_campuses): ?>
                                <div class="adm-field span-2" role="radiogroup" aria-labelledby="adm_campus_lbl">
                                    <span class="adm-label" id="adm_campus_lbl">Campus <span class="req">*</span></span>
                                    <div class="adm-campus-opts">
                                        <?php foreach ($adm_campuses as $i => $cn): ?>
                                        <label class="adm-campus-opt">
                                            <input type="radio" name="campus" value="<?php echo htmlspecialchars($cn); ?>" required<?php echo $selected_campus === $cn ? ' checked' : ''; ?>>
                                            <span><i class="fas <?php echo stripos($cn, 'girl') !== false ? 'fa-person-dress' : 'fa-person'; ?>" aria-hidden="true"></i><?php echo htmlspecialchars($cn); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </fieldset>

                        <!-- 2. Student & Guardian -->
                        <fieldset class="adm-fs">
                            <legend><span class="adm-fs-num">2</span><span class="adm-fs-title">Student &amp; Guardian<small>As written on the B-Form / CNIC</small></span></legend>
                            <div class="adm-grid">
                                <div class="adm-field">
                                    <label for="adm_name">Student Name <span class="req">*</span></label>
                                    <input id="adm_name" type="text" name="student_name" required autocomplete="name" placeholder="Full name" value="<?php echo htmlspecialchars($f['student_name']); ?>">
                                </div>
                                <div class="adm-field">
                                    <label for="adm_father">Father / Guardian Name <span class="req">*</span></label>
                                    <input id="adm_father" type="text" name="father_name" required placeholder="Father or guardian" value="<?php echo htmlspecialchars($f['father_name']); ?>">
                                </div>
                                <div class="adm-field">
                                    <label for="adm_cnic">Student B-Form / CNIC <span class="req">*</span></label>
                                    <input id="adm_cnic" type="text" name="cnic_bform" required inputmode="numeric" maxlength="15" pattern="\d{5}-?\d{7}-?\d" placeholder="12345-1234567-1" value="<?php echo htmlspecialchars($f['cnic_bform']); ?>">
                                    <small>13-digit number</small>
                                </div>
                                <div class="adm-field">
                                    <label for="adm_phone">Parent / Guardian Phone <span class="req">*</span></label>
                                    <input id="adm_phone" type="tel" name="phone" required autocomplete="tel" placeholder="03XX XXXXXXX" value="<?php echo htmlspecialchars($f['phone']); ?>">
                                </div>
                                <div class="adm-field span-2">
                                    <label for="adm_email">Email Address</label>
                                    <input id="adm_email" type="email" name="email" autocomplete="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($f['email']); ?>">
                                    <small>Optional — used only to send your application confirmation.</small>
                                </div>
                                <div class="adm-field span-2">
                                    <label for="adm_address">Residential Address <span class="req">*</span></label>
                                    <textarea id="adm_address" name="address" required autocomplete="street-address" placeholder="House / village, area, tehsil, district"><?php echo htmlspecialchars($f['address']); ?></textarea>
                                </div>
                            </div>
                        </fieldset>

                        <!-- 3. Previous Education -->
                        <fieldset class="adm-fs">
                            <legend><span class="adm-fs-num">3</span><span class="adm-fs-title">Previous Education<small>Last class passed or attended</small></span></legend>
                            <div class="adm-grid">
                                <div class="adm-field">
                                    <label for="adm_qual">Last Class / Qualification <span class="req">*</span></label>
                                    <input id="adm_qual" type="text" name="qualification" required placeholder="e.g., Class 8 or Matric" value="<?php echo htmlspecialchars($f['qualification']); ?>">
                                    <small>For Play Group, write “None”.</small>
                                </div>
                                <div class="adm-field">
                                    <label for="adm_board">Previous School / Board</label>
                                    <input id="adm_board" type="text" name="board_university" placeholder="School name or examination board" value="<?php echo htmlspecialchars($f['board_university']); ?>">
                                </div>
                                <div class="adm-field">
                                    <label for="adm_year">Passing Year</label>
                                    <input id="adm_year" type="number" name="passing_year" min="1990" max="<?php echo date('Y') + 1; ?>" placeholder="e.g., <?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($f['passing_year']); ?>">
                                </div>
                                <div class="adm-field">
                                    <label for="adm_marks">Marks Obtained (%)</label>
                                    <input id="adm_marks" type="number" name="marks_percentage" min="0" max="100" step="0.01" placeholder="e.g., 78" value="<?php echo htmlspecialchars($f['marks_percentage']); ?>">
                                </div>
                            </div>
                        </fieldset>

                        <!-- 4. Documents & Notes -->
                        <fieldset class="adm-fs">
                            <legend><span class="adm-fs-num">4</span><span class="adm-fs-title">Documents &amp; Notes<small>Optional — originals can be brought to the office</small></span></legend>
                            <div class="adm-upload" style="margin-bottom:18px;">
                                <input type="file" id="docInput" name="documents" accept=".pdf,.jpg,.jpeg,.png" class="visually-hidden">
                                <label class="adm-upload-label" for="docInput">
                                    <span class="ui-icon"><i class="fas fa-cloud-arrow-up" aria-hidden="true"></i></span>
                                    <span class="adm-upload-text">
                                        <strong id="docName">Upload result card or B-Form</strong>
                                        <span>PDF, JPG or PNG — max 5MB</span>
                                    </span>
                                </label>
                            </div>
                            <div class="adm-field">
                                <label for="adm_msg">Additional Information</label>
                                <textarea id="adm_msg" name="message" placeholder="Anything we should know — transport, sibling studying at IQRA, etc."><?php echo htmlspecialchars($f['message']); ?></textarea>
                            </div>
                        </fieldset>

                        <p class="adm-consent"><i class="fas fa-shield-halved" aria-hidden="true"></i>Your information is used only for processing this admission and is kept confidential by the admission office.</p>
                        <button type="submit" class="btn btn-lg adm-submit">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Sidebar ── -->
            <aside class="adm-sidebar" aria-label="Admission information">

                <div class="adm-side-card">
                    <div class="adm-side-head">
                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                        <h3>Required Documents</h3>
                    </div>
                    <div class="adm-side-body">
                        <ul class="adm-doc-list">
                            <?php foreach ($adm_docs as $d): ?>
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i><?php echo htmlspecialchars($d); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="adm-side-card is-dark">
                    <div class="adm-side-head">
                        <i class="fas fa-headset" aria-hidden="true"></i>
                        <h3>Admission Office</h3>
                    </div>
                    <div class="adm-side-body">
                        <div class="adm-contact-row">
                            <span class="adm-contact-ico"><i class="fas fa-phone-alt" aria-hidden="true"></i></span>
                            <div><div class="k">Call Us</div><a href="tel:<?php echo preg_replace('/[^\d+]/', '', SITE_CONTACT); ?>"><?php echo htmlspecialchars(SITE_CONTACT); ?></a></div>
                        </div>
                        <div class="adm-contact-row">
                            <span class="adm-contact-ico"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                            <div><div class="k">Email</div><a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo htmlspecialchars(ADMIN_EMAIL); ?></a></div>
                        </div>
                        <div class="adm-contact-row">
                            <span class="adm-contact-ico"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                            <div><div class="k">Location</div><span class="v"><?php echo htmlspecialchars(SITE_LOCATION); ?></span></div>
                        </div>
                        <div class="adm-contact-row">
                            <span class="adm-contact-ico"><i class="fas fa-clock" aria-hidden="true"></i></span>
                            <div><div class="k">Office Hours</div><span class="v"><?php echo htmlspecialchars(getOfficeHours()); ?></span></div>
                        </div>
                    </div>
                </div>

                <div class="adm-side-card">
                    <div class="adm-side-head">
                        <i class="fas fa-compass" aria-hidden="true"></i>
                        <h3>Helpful Links</h3>
                    </div>
                    <div class="adm-side-body">
                        <ul class="adm-tools">
                            <li><a href="courses.php"><i class="fas fa-book-open" aria-hidden="true"></i>Courses &amp; Programs<i class="fas fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a href="campuses.php"><i class="fas fa-building-columns" aria-hidden="true"></i>Our Campuses<i class="fas fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i>Contact Us<i class="fas fa-chevron-right" aria-hidden="true"></i></a></li>
                            <li><a href="downloads.php"><i class="fas fa-download" aria-hidden="true"></i>Downloads<i class="fas fa-chevron-right" aria-hidden="true"></i></a></li>
                        </ul>
                    </div>
                </div>

            </aside>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="adm-cta">
    <div class="container">
        <div class="adm-cta-box adm-reveal">
            <div>
                <h2>Questions About Admission?</h2>
                <p>Our admission team is happy to help — call us, send a message, or visit the campus with your child.</p>
            </div>
            <div class="adm-cta-btns">
                <a href="contact.php" class="btn btn-accent"><i class="fas fa-envelope"></i> Contact Us</a>
                <a href="tel:<?php echo preg_replace('/[^\d+]/', '', SITE_CONTACT); ?>" class="btn btn-outline"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    /* After a submission (success or errors) bring the result into view */
    if (document.querySelector('.adm-success, .adm-alert')) {
        var card = document.getElementById('applyForm');
        if (card) card.scrollIntoView({ block: 'start' });
    }

    /* File upload: show the chosen file name, support drag & drop */
    var inp = document.getElementById('docInput'), box = inp && inp.closest('.adm-upload'), nameEl = document.getElementById('docName');
    if (inp) {
        var showName = function () { if (inp.files.length && nameEl) nameEl.textContent = inp.files[0].name; };
        inp.addEventListener('change', showName);
        ['dragenter', 'dragover'].forEach(function (ev) { box.addEventListener(ev, function (e) { e.preventDefault(); box.classList.add('is-drag'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { box.addEventListener(ev, function () { box.classList.remove('is-drag'); }); });
        box.addEventListener('drop', function (e) {
            e.preventDefault();
            if (e.dataTransfer && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; showName(); }
        });
    }

    /* Arriving from an "Apply for Scholarship" link — pre-fill the message field */
    var params = new URLSearchParams(window.location.search);
    var scholarshipName = params.get('scholarship');
    if (scholarshipName !== null) {
        var msg = document.querySelector('textarea[name="message"]');
        if (msg && !msg.value) {
            msg.value = scholarshipName
                ? 'I would like to apply for the "' + scholarshipName + '".'
                : 'I would like to apply for a scholarship.';
        }
    }

    /* Reveal on scroll */
    var items = document.querySelectorAll('.adm-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { items.forEach(show); return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .adm-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 4) * 80 + 'ms';
            show(e.target); io.unobserve(e.target);
        });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
