<?php
require_once 'includes/config.php';
require_once 'includes/alumni_helper.php';
$page_title = 'Alumni Portal';
$page_description = 'Alumni Portal of ' . getSiteName() . ' — former students can register, reconnect with their school and share their journey.';

alumni_ensure_schema($conn);
$program_options = alumni_program_options($conn);
$year_now = (int) date('Y');

// One-time form token (blocks cross-site and repeated submissions) — public pages have no admin CSRF helper
if (empty($_SESSION['alumni_form_token'])) $_SESSION['alumni_form_token'] = bin2hex(random_bytes(16));
$form_token = $_SESSION['alumni_form_token'];
$token_ok = function () {
    $sent = $_POST['form_token'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals($_SESSION['alumni_form_token'] ?? '', $sent);
};

$errors = [];          // field => message
$form_error = '';      // general message above the form
$review_error = '';
$f = ['full_name' => '', 'father_name' => '', 'email' => '', 'mobile' => '', 'passing_year' => '', 'program' => '',
      'program_other' => '', 'occupation' => '', 'organization' => '', 'city' => '', 'country' => 'Pakistan',
      'additional_info' => '', 'show_public' => '', 'privacy_agree' => ''];

/* ── Alumni registration ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_alumni'])) {
    foreach ($f as $k => $v) $f[$k] = is_string($_POST[$k] ?? null) ? $_POST[$k] : '';

    if (!$token_ok() || !empty($_POST['website'])) {
        $form_error = 'Your session has expired. Please refresh the page and submit the form again.';
    } else {
        [$data, $errors] = alumni_validate($_POST, $program_options);
        if (empty($_POST['privacy_agree'])) $errors['privacy_agree'] = 'Please confirm that you have read the privacy notice.';

        if (!$errors) {
            $dup = alumni_find_duplicate($conn, $data['email'], $data['mobile_number']);
            if ($dup) {
                $form_error = $dup['status'] === 'approved'
                    ? 'You are already registered in our alumni records with this email or mobile number. Please contact the school office if your details need to be updated.'
                    : 'A registration with this email or mobile number has already been received and is waiting for review. There is no need to submit it again.';
            }
        }

        $photo = null;
        if (!$errors && !$form_error) {
            [$photo, $photo_err] = alumni_save_photo($_FILES['photo'] ?? null);
            if ($photo_err) $errors['photo'] = $photo_err;
        }

        if (!$errors && !$form_error) {
            $stmt = mysqli_prepare($conn, "INSERT INTO alumni
                (student_name, father_name, email, mobile_number, passing_year, course, current_job, job_department, job_city, country, photo, review, show_public, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            mysqli_stmt_bind_param($stmt, 'ssssisssssssi',
                $data['student_name'], $data['father_name'], $data['email'], $data['mobile_number'], $data['passing_year'],
                $data['course'], $data['current_job'], $data['job_department'], $data['job_city'], $data['country'],
                $photo, $data['review'], $data['show_public']);
            try {
                $saved = mysqli_stmt_execute($stmt);
            } catch (mysqli_sql_exception $ex) {
                error_log('[alumni] register: ' . $ex->getMessage());
                $saved = false;
            }
            if ($saved) {
                // Admin notification (best effort)
                $host = preg_replace('/^www\./', '', strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':'));
                $body = "<html><body style='font-family:Arial;'>"
                      . "<div style='background:#102A56;color:#fff;padding:18px;text-align:center;'><h2 style='margin:0;'>New Alumni Registration</h2></div>"
                      . "<div style='padding:20px;background:#F4F8FC;'><table cellpadding='8' style='border-collapse:collapse;'>"
                      . "<tr><td><b>Name</b></td><td>" . htmlspecialchars($data['student_name']) . "</td></tr>"
                      . "<tr><td><b>Program</b></td><td>" . htmlspecialchars($data['course']) . " ({$data['passing_year']})</td></tr>"
                      . "<tr><td><b>Occupation</b></td><td>" . htmlspecialchars($data['current_job'] ?: '—') . "</td></tr>"
                      . "<tr><td><b>City</b></td><td>" . htmlspecialchars($data['job_city'] . ', ' . $data['country']) . "</td></tr>"
                      . "</table><p>Review it in AdminCP &gt; Alumni Portal.</p></div></body></html>";
                @mail(ADMIN_EMAIL, 'New Alumni Registration - ' . SITE_NAME, $body,
                      "MIME-Version:1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom:" . SITE_NAME . " <noreply@{$host}>");

                $_SESSION['alumni_form_token'] = bin2hex(random_bytes(16));
                $_SESSION['alumni_flash'] = $data['student_name'];
                header('Location: alumni.php?registered=1#register');
                exit;
            }
            alumni_delete_photo($photo);
            $form_error = 'Sorry, your registration could not be saved. Please try again in a moment.';
        } elseif ($errors && !$form_error) {
            $form_error = 'Please correct the highlighted fields and submit again.';
        }
    }
}

/* ── Alumni review ── */
$review_done = false;
$rv = ['rv_name' => '', 'rv_year' => '', 'rv_rating' => '5', 'rv_text' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    foreach ($rv as $k => $v) $rv[$k] = is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
    $r_name = alumni_clean($rv['rv_name'], 100);
    $r_year = preg_match('/^\d{4}$/', $rv['rv_year']) ? (int) $rv['rv_year'] : null;
    $r_rating = max(1, min(5, (int) $rv['rv_rating']));
    $r_text = mb_substr(trim($rv['rv_text']), 0, 1000);
    if (!$token_ok() || !empty($_POST['website'])) $review_error = 'Your session has expired. Please refresh the page and try again.';
    elseif ($r_name === '' || mb_strlen($r_text) < 10) $review_error = 'Please enter your name and a review of at least 10 characters.';
    elseif ($r_year !== null && ($r_year < 1990 || $r_year > $year_now)) $review_error = 'Please enter a valid passing year.';
    else {
        $stmt = mysqli_prepare($conn, "INSERT INTO alumni_reviews (name, passing_year, review, rating, status) VALUES (?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, 'sisi', $r_name, $r_year, $r_text, $r_rating);
        try { $saved = mysqli_stmt_execute($stmt); } catch (mysqli_sql_exception $ex) { error_log('[alumni] review: ' . $ex->getMessage()); $saved = false; }
        if ($saved) {
            $_SESSION['alumni_form_token'] = bin2hex(random_bytes(16));
            header('Location: alumni.php?reviewed=1#reviews');
            exit;
        }
        $review_error = 'Sorry, your review could not be saved. Please try again.';
    }
}

$just_registered = isset($_GET['registered']) && !empty($_SESSION['alumni_flash']);
$registered_name = $just_registered ? $_SESSION['alumni_flash'] : '';
if ($just_registered) unset($_SESSION['alumni_flash']);
$just_reviewed = isset($_GET['reviewed']);

// Public directory: approved alumni who agreed to be listed — never email or phone
$directory = [];
$res = mysqli_query($conn, "SELECT student_name, course, passing_year, current_job, job_department, job_city, country, photo
                            FROM alumni WHERE status = 'approved' AND show_public = 1 ORDER BY passing_year DESC, student_name ASC LIMIT 24");
while ($res && ($r = mysqli_fetch_assoc($res))) $directory[] = $r;

$reviews = [];
$res = mysqli_query($conn, "SELECT name, passing_year, review, rating FROM alumni_reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6");
while ($res && ($r = mysqli_fetch_assoc($res))) $reviews[] = $r;

$alp_hero_image = getSetting('alumni_hero_image', 'images/banners/banner-courtyard.jpg');
$e = function ($k) use (&$errors) { return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : ''; };
$err = function ($k) use (&$errors) {
    return isset($errors[$k]) ? '<p class="field-error" id="err-' . $k . '"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> ' . htmlspecialchars($errors[$k]) . '</p>' : '';
};
$v = function ($k) use (&$f) { return htmlspecialchars($f[$k]); };
function alpInitials($name) {
    $ini = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $p) $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    return $ini ?: '?';
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Alumni Portal — uses the shared system in css/style.css
   ================================================================ */
.alp-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:400px; display:flex; align-items:center;
    padding:72px 0 64px; background:var(--primary-dark);
}
.alp-hero-img { position:absolute; inset:0; z-index:-2; }
.alp-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 40%; }
.alp-hero::after {
    content:''; position:absolute; inset:0; z-index:-1;
    background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%);
}
.alp-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.alp-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.alp-breadcrumb a:hover { color:#fff; }
.alp-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.alp-hero-badge { display:inline-flex; align-items:center; gap:10px; color:rgba(255,255,255,0.9); font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px; }
.alp-hero-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.alp-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.alp-hero h1 span { color:var(--accent-bright); }
.alp-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:600px; }
.alp-hero-btns { display:flex; flex-wrap:wrap; gap:12px; margin-top:28px; }

.alp-sh { text-align:center; margin-bottom:40px; }
.alp-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.alp-sh h2 span { color:var(--accent-hover); }
.alp-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

/* Why join */
.alp-why { padding:var(--section-y) 0; background:#fff; }
.alp-why-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
.alp-why .ui-card h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); margin:0 0 8px; }
.alp-why .ui-card p { color:var(--text-light); font-size:var(--fs-body-sm); line-height:1.7; margin:0; }

/* Registration */
.alp-reg { padding:var(--section-y) 0; background:var(--background-color); }
.alp-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:32px; align-items:start; }
.alp-card { background:#fff; border-radius:var(--radius-card); border:1px solid var(--border-color); box-shadow:var(--shadow-card); overflow:hidden; }
.alp-card-head { display:flex; align-items:center; gap:16px; padding:24px 30px; background:var(--primary-color); border-bottom:3px solid var(--gold-color); }
.alp-card-head .ui-icon { margin:0; width:50px; height:50px; background:rgba(255,255,255,0.1); color:var(--accent-bright); }
.alp-card-head h2 { color:#fff; font-size:var(--fs-h3); font-weight:700; margin:0 0 3px; }
.alp-card-head p { color:rgba(255,255,255,0.75); font-size:var(--fs-body-sm); margin:0; }
.alp-card-body { padding:30px; }

.alp-fs { border:0; margin:0; padding:0; min-width:0; }
.alp-fs + .alp-fs { margin-top:30px; padding-top:28px; border-top:1px solid var(--border-color); }
.alp-fs legend { display:flex; align-items:center; gap:12px; padding:0; margin:0 0 18px; float:left; width:100%; }
.alp-fs legend + * { clear:both; }
.alp-fs-num { width:32px; height:32px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:var(--light-blue); color:var(--accent-text); font-family:var(--font-heading); font-size:14px; font-weight:700; }
.alp-fs-title { font-family:var(--font-heading); font-size:var(--fs-card-title); font-weight:600; color:var(--primary-color); }
.alp-fs-title small { display:block; font-family:var(--font-body); font-size:var(--fs-caption); font-weight:400; color:var(--text-light); margin-top:2px; }
.alp-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.alp-grid .span-2 { grid-column:1 / -1; }
.alp-hidden { display:none !important; }
.alp-counter { text-align:right; }

.alp-photo { display:flex; align-items:center; gap:18px; padding:16px; border:1.5px dashed rgba(16,42,86,0.28); border-radius:12px; background:var(--background-color); }
.alp-photo:focus-within { border-color:var(--accent-color); box-shadow:0 0 0 3px rgba(11,166,201,0.2); }
.alp-photo-prev { width:72px; height:72px; border-radius:50%; flex-shrink:0; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#fff; border:1px solid var(--border-color); color:var(--text-light); font-size:26px; }
.alp-photo-prev img { width:100%; height:100%; object-fit:cover; }
.alp-photo-txt strong { display:block; color:var(--primary-color); font-size:var(--fs-body-sm); }
.alp-photo-txt span { display:block; color:var(--text-light); font-size:var(--fs-caption); margin-top:2px; }
.alp-photo-btns { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.alp-photo-btns label { cursor:pointer; }

.alp-check { display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border-radius:10px; border:1px solid var(--border-color); background:#fff; cursor:pointer; font-size:var(--fs-body-sm); color:var(--text-color); line-height:1.55; }
.alp-check + .alp-check { margin-top:10px; }
.alp-check input { width:18px; height:18px; margin-top:2px; flex-shrink:0; accent-color:var(--primary-color); }
.alp-check a { color:var(--accent-text); font-weight:600; }
.alp-check.is-invalid { border-color:var(--danger-color); }
.alp-hp { position:absolute !important; left:-9999px !important; width:1px; height:1px; overflow:hidden; }
.alp-submit { margin-top:22px; width:100%; }

.alp-alert { display:flex; gap:12px; padding:14px 18px; border-radius:12px; margin-bottom:22px; font-size:var(--fs-body-sm); line-height:1.6; }
.alp-alert i { margin-top:3px; }
.alp-alert-err { background:#FEF2F2; border:1px solid #FECACA; color:#991B1B; }
.alp-alert-ok { background:#F0FDF4; border:1px solid #BBF7D0; color:#166534; }

.alp-success { text-align:center; padding:10px 0; }
.alp-success-ico { width:76px; height:76px; margin:0 auto 18px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(22,163,74,0.1); color:#16A34A; font-size:34px; }
.alp-success h3 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0 0 10px; }
.alp-success p { color:var(--text-light); max-width:480px; margin:0 auto; line-height:1.7; }
.alp-success .btn { margin-top:22px; }

/* Sidebar */
.alp-side { display:flex; flex-direction:column; gap:22px; position:sticky; top:110px; }
.alp-side-card { background:#fff; border-radius:var(--radius-card); border:1px solid var(--border-color); box-shadow:var(--shadow-card); overflow:hidden; }
.alp-side-head { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid var(--border-color); }
.alp-side-head i { color:var(--accent-color); }
.alp-side-head h3 { font-size:15px; font-weight:600; color:var(--primary-color); margin:0; }
.alp-side-body { padding:16px 20px 18px; font-size:var(--fs-body-sm); color:var(--text-color); line-height:1.65; }
.alp-side-body p { margin:0 0 10px; }
.alp-side-body ul { list-style:none; margin:0; padding:0; }
.alp-side-body li { display:flex; gap:10px; padding:6px 0; }
.alp-side-body li i { color:var(--accent-color); margin-top:4px; font-size:12px; flex-shrink:0; }
.alp-side-card.is-dark { background:var(--primary-color); border-color:var(--primary-color); border-bottom:3px solid var(--gold-color); }
.alp-side-card.is-dark .alp-side-head { border-color:rgba(255,255,255,0.12); }
.alp-side-card.is-dark h3 { color:#fff; }
.alp-side-card.is-dark .alp-side-head i { color:var(--gold-color); }
.alp-side-card.is-dark .alp-side-body { color:rgba(255,255,255,0.82); }
.alp-steps { counter-reset:s; }
.alp-steps li { counter-increment:s; }
.alp-steps li::before { content:counter(s); width:24px; height:24px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.12); color:#fff; font-size:12px; font-weight:700; }

/* Directory */
.alp-dir { padding:var(--section-y) 0; background:#fff; }
.alp-dir-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; }
.alp-person { text-align:center; padding:26px 20px; border-radius:var(--radius-card); background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card); transition:transform var(--dur) var(--ease-out), box-shadow var(--dur) ease; }
.alp-person:hover { transform:translateY(-4px); box-shadow:var(--shadow-card-hover); }
.alp-avatar { width:84px; height:84px; margin:0 auto 14px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:var(--primary-color); color:var(--accent-bright); font-family:var(--font-heading); font-size:26px; font-weight:700; border:3px solid #fff; box-shadow:0 0 0 2px var(--gold-color); }
.alp-avatar img { width:100%; height:100%; object-fit:cover; }
.alp-person h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); margin:0 0 4px; }
.alp-person .prog { font-size:var(--fs-caption); color:var(--text-light); margin-bottom:12px; }
.alp-person .job { font-size:var(--fs-body-sm); font-weight:600; color:var(--text-color); }
.alp-person .loc { font-size:var(--fs-caption); color:var(--text-light); margin-top:4px; }
.alp-person .loc i { color:var(--accent-color); margin-right:4px; }
.alp-empty { text-align:center; padding:44px 20px; border:1px dashed var(--border-color); border-radius:var(--radius-card); color:var(--text-light); background:var(--background-color); }
.alp-empty i { font-size:40px; color:var(--border-color); display:block; margin-bottom:12px; }
.alp-empty h3 { color:var(--primary-color); font-size:var(--fs-card-title); margin:0 0 6px; }
.alp-empty p { margin:0 0 16px; }

/* Reviews */
.alp-reviews { padding:var(--section-y) 0; background:var(--background-color); }
.alp-rev-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; margin-bottom:36px; }
.alp-rev { padding:26px; border-radius:var(--radius-card); background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card); }
.alp-stars { color:var(--gold-color); font-size:13px; letter-spacing:2px; margin-bottom:12px; }
.alp-rev p { font-size:var(--fs-body-sm); color:var(--text-color); line-height:1.75; margin:0 0 14px; }
.alp-rev strong { color:var(--primary-color); font-size:var(--fs-body-sm); }
.alp-rev-form { max-width:720px; margin:0 auto; }
.alp-rev-form summary { list-style:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; width:fit-content; margin:0 auto; }
.alp-rev-form summary::-webkit-details-marker { display:none; }
.alp-rev-form[open] summary { margin-bottom:20px; }
.alp-rev-form .alp-card-body { padding:26px; }

.alp-reveal { opacity:0; transform:translateY(16px); transition:opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out); }
.alp-reveal.is-visible { opacity:1; transform:none; }

@media (max-width:1100px) { .alp-dir-grid { grid-template-columns:repeat(3,1fr); } }
@media (max-width:960px) {
    .alp-layout { grid-template-columns:1fr; }
    .alp-side { position:static; display:grid; grid-template-columns:1fr 1fr; }
    .alp-why-grid, .alp-rev-grid { grid-template-columns:1fr; max-width:640px; margin-left:auto; margin-right:auto; }
    .alp-dir-grid { grid-template-columns:repeat(2,1fr); }
}
@media (max-width:640px) {
    .alp-hero { min-height:0; padding:56px 0 48px; }
    .alp-hero-btns .btn { flex:1 1 100%; }
    .alp-side { grid-template-columns:1fr; }
    .alp-grid { grid-template-columns:1fr; }
    .alp-card-head { padding:20px; }
    .alp-card-body { padding:22px 18px; }
    .alp-photo { flex-direction:column; text-align:center; }
    .alp-photo-btns { justify-content:center; }
}
@media (max-width:420px) { .alp-dir-grid { grid-template-columns:1fr; } }
@media (prefers-reduced-motion:reduce) { .alp-reveal { opacity:1; transform:none; transition:none; } }
</style>

<!-- ── Hero ── -->
<section class="alp-hero">
    <div class="alp-hero-img" aria-hidden="true"><img src="<?php echo htmlspecialchars($alp_hero_image); ?>" alt="" fetchpriority="high"></div>
    <div class="container">
        <nav class="alp-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Alumni Portal</span>
        </nav>
        <div class="alp-hero-badge">Alumni Portal</div>
        <h1>Once IQRA, <span>Always IQRA</span></h1>
        <p>Former students of <?php echo htmlspecialchars(getSiteName()); ?> — register in our alumni records, reconnect with your school and inspire the students who follow in your footsteps.</p>
        <div class="alp-hero-btns">
            <a href="#register" class="btn btn-accent btn-lg"><i class="fas fa-user-plus"></i> Register as Alumni</a>
            <a href="#directory" class="btn btn-outline btn-lg"><i class="fas fa-users"></i> Alumni Directory</a>
        </div>
    </div>
</section>

<!-- ── Why register ── -->
<section class="alp-why">
    <div class="container">
        <div class="alp-sh">
            <div class="section-badge">Why Register</div>
            <h2>Stay Connected With <span>Your School</span></h2>
            <p>Your registration helps us keep in touch with our graduates and share their journeys with today's students.</p>
        </div>
        <div class="alp-why-grid">
            <div class="ui-card alp-reveal">
                <span class="ui-icon"><i class="fas fa-handshake" aria-hidden="true"></i></span>
                <h3>Reconnect</h3>
                <p>Stay in touch with your teachers, classmates and the school that shaped your early years.</p>
            </div>
            <div class="ui-card alp-reveal">
                <span class="ui-icon"><i class="fas fa-lightbulb" aria-hidden="true"></i></span>
                <h3>Inspire Students</h3>
                <p>Your career path shows current students what is possible — from Daggar to universities and professions.</p>
            </div>
            <div class="ui-card alp-reveal">
                <span class="ui-icon"><i class="fas fa-bullhorn" aria-hidden="true"></i></span>
                <h3>Hear From Us</h3>
                <p>Receive news about alumni gatherings, school events and opportunities to give back.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Registration ── -->
<section class="alp-reg" id="register">
    <div class="container">
        <div class="alp-layout">
            <div class="alp-card">
                <div class="alp-card-head">
                    <span class="ui-icon"><i class="fas fa-id-card" aria-hidden="true"></i></span>
                    <div>
                        <h2>Alumni Registration</h2>
                        <p><?php echo $just_registered ? 'Registration received' : 'Fields marked * are required. It takes about 3 minutes.'; ?></p>
                    </div>
                </div>
                <div class="alp-card-body">
                <?php if ($just_registered): ?>
                    <div class="alp-success" role="status">
                        <div class="alp-success-ico"><i class="fas fa-check" aria-hidden="true"></i></div>
                        <h3>Thank you, <?php echo htmlspecialchars($registered_name); ?>!</h3>
                        <p>Your alumni registration has been received. Our team will verify your details, and you may be contacted on the email or mobile number you provided.</p>
                        <a href="index.php" class="btn btn-primary"><i class="fas fa-house"></i> Back to Home</a>
                    </div>
                <?php else: ?>
                    <?php if ($form_error): ?>
                    <div class="alp-alert alp-alert-err" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><div><?php echo htmlspecialchars($form_error); ?></div></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="alumniForm">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($form_token); ?>">
                        <div class="alp-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                        <fieldset class="alp-fs">
                            <legend><span class="alp-fs-num">1</span><span class="alp-fs-title">Personal Details<small>So we can identify and contact you</small></span></legend>
                            <div class="alp-grid">
                                <div class="form-group" style="margin:0;">
                                    <label for="al_name">Full Name <span class="req">*</span></label>
                                    <input id="al_name" type="text" name="full_name" required maxlength="100" autocomplete="name" value="<?php echo $v('full_name'); ?>"<?php echo $e('full_name'); ?>>
                                    <?php echo $err('full_name'); ?>
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_father">Father's Name <span class="req">*</span></label>
                                    <input id="al_father" type="text" name="father_name" required maxlength="100" value="<?php echo $v('father_name'); ?>"<?php echo $e('father_name'); ?>>
                                    <?php echo $err('father_name'); ?>
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_email">Email Address <span class="req">*</span></label>
                                    <input id="al_email" type="email" name="email" required maxlength="150" autocomplete="email" placeholder="name@example.com" value="<?php echo $v('email'); ?>"<?php echo $e('email'); ?>>
                                    <?php echo $err('email'); ?>
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_mobile">Mobile Number <span class="req">*</span></label>
                                    <input id="al_mobile" type="tel" name="mobile" required maxlength="20" autocomplete="tel" placeholder="03001234567" pattern="[0-9+\s\-]{10,20}" title="Enter a valid mobile number, e.g. 03001234567 or +971501234567." value="<?php echo $v('mobile'); ?>"<?php echo $e('mobile'); ?>>
                                    <?php echo $err('mobile') ?: '<small>Pakistani or international number</small>'; ?>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="alp-fs">
                            <legend><span class="alp-fs-num">2</span><span class="alp-fs-title">Your Time at IQRA<small>The class or program you last attended with us</small></span></legend>
                            <div class="alp-grid">
                                <div class="form-group" style="margin:0;">
                                    <label for="al_year">Passing Year <span class="req">*</span></label>
                                    <select id="al_year" name="passing_year" required<?php echo $e('passing_year'); ?>>
                                        <option value="">— Select year —</option>
                                        <?php for ($y = $year_now; $y >= 1990; $y--): ?><option value="<?php echo $y; ?>"<?php echo (string) $y === $f['passing_year'] ? ' selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?>
                                    </select>
                                    <?php echo $err('passing_year'); ?>
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_program">Last Class / Program <span class="req">*</span></label>
                                    <select id="al_program" name="program" required<?php echo $e('program'); ?>>
                                        <option value="">— Select —</option>
                                        <?php foreach ($program_options as $po): ?><option value="<?php echo htmlspecialchars($po); ?>"<?php echo $po === $f['program'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($po); ?></option><?php endforeach; ?>
                                        <option value="Other"<?php echo $f['program'] === 'Other' ? ' selected' : ''; ?>>Other (please specify)</option>
                                    </select>
                                    <?php echo $err('program'); ?>
                                </div>
                                <div class="form-group span-2<?php echo $f['program'] === 'Other' ? '' : ' alp-hidden'; ?>" style="margin:0;" id="al_other_wrap">
                                    <label for="al_other">Class / Program Attended <span class="req">*</span></label>
                                    <input id="al_other" type="text" name="program_other" maxlength="100" placeholder="e.g. Class 10 (old syllabus), D.Com" value="<?php echo $v('program_other'); ?>">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="alp-fs">
                            <legend><span class="alp-fs-num">3</span><span class="alp-fs-title">Career &amp; Location<small>Where life has taken you since IQRA</small></span></legend>
                            <div class="alp-grid">
                                <div class="form-group" style="margin:0;">
                                    <label for="al_occ">Current Occupation</label>
                                    <input id="al_occ" type="text" name="occupation" maxlength="150" placeholder="e.g. Doctor, Engineer, Student" value="<?php echo $v('occupation'); ?>">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_org">Current Organization</label>
                                    <input id="al_org" type="text" name="organization" maxlength="150" placeholder="Company, hospital or university" value="<?php echo $v('organization'); ?>">
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_city">City <span class="req">*</span></label>
                                    <input id="al_city" type="text" name="city" required maxlength="100" autocomplete="address-level2" value="<?php echo $v('city'); ?>"<?php echo $e('city'); ?>>
                                    <?php echo $err('city'); ?>
                                </div>
                                <div class="form-group" style="margin:0;">
                                    <label for="al_country">Country <span class="req">*</span></label>
                                    <input id="al_country" type="text" name="country" required maxlength="100" list="alCountries" autocomplete="country-name" value="<?php echo $v('country'); ?>"<?php echo $e('country'); ?>>
                                    <datalist id="alCountries"><?php foreach (['Pakistan','Saudi Arabia','United Arab Emirates','Qatar','Oman','Kuwait','Bahrain','United Kingdom','United States','Canada','Australia','Germany','Malaysia','Turkey','China'] as $c): ?><option value="<?php echo $c; ?>"><?php endforeach; ?></datalist>
                                    <?php echo $err('country'); ?>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="alp-fs">
                            <legend><span class="alp-fs-num">4</span><span class="alp-fs-title">Photo &amp; Message<small>Both optional</small></span></legend>
                            <div class="alp-photo" style="margin-bottom:18px;">
                                <div class="alp-photo-prev" id="alPhotoPrev"><i class="fas fa-user" aria-hidden="true"></i></div>
                                <div class="alp-photo-txt">
                                    <strong>Profile Photograph</strong>
                                    <span>JPG, PNG or WebP — max 2 MB. A clear, recent photo of your face.</span>
                                    <div class="alp-photo-btns">
                                        <label for="al_photo" class="btn btn-secondary btn-sm"><i class="fas fa-upload"></i> Choose Photo</label>
                                        <button type="button" class="btn btn-sm btn-outline alp-hidden" id="alPhotoClear" style="color:var(--primary-color);border-color:var(--border-color);">Remove</button>
                                    </div>
                                    <input id="al_photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden"<?php echo $e('photo'); ?>>
                                    <?php echo $err('photo'); ?>
                                </div>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label for="al_info">Additional Information</label>
                                <textarea id="al_info" name="additional_info" maxlength="1000" rows="4" placeholder="Higher studies, achievements, or a message for current students…"<?php echo $e('additional_info'); ?>><?php echo $v('additional_info'); ?></textarea>
                                <?php echo $err('additional_info'); ?>
                                <small class="alp-counter"><span id="alInfoCount">0</span> / 1000</small>
                            </div>
                        </fieldset>

                        <div style="margin-top:26px;">
                            <label class="alp-check<?php echo isset($errors['privacy_agree']) ? ' is-invalid' : ''; ?>" for="al_agree">
                                <input id="al_agree" type="checkbox" name="privacy_agree" value="1" required<?php echo $f['privacy_agree'] ? ' checked' : ''; ?>>
                                <span>I confirm the information is correct and I have read the <a href="#privacy">privacy notice</a>. <span class="req" style="color:var(--danger-color);">*</span></span>
                            </label>
                            <?php echo $err('privacy_agree'); ?>
                            <label class="alp-check" for="al_public">
                                <input id="al_public" type="checkbox" name="show_public" value="1"<?php echo $f['show_public'] ? ' checked' : ''; ?>>
                                <span>Show my name, photo, program, occupation and city in the public <strong>Alumni Directory</strong> on this website (optional). My email and mobile number are never shown.</span>
                            </label>
                        </div>

                        <button type="submit" name="register_alumni" value="1" class="btn btn-accent btn-lg alp-submit"><i class="fas fa-paper-plane"></i> Submit Registration</button>
                    </form>
                <?php endif; ?>
                </div>
            </div>

            <aside class="alp-side" aria-label="About your data">
                <div class="alp-side-card" id="privacy">
                    <div class="alp-side-head"><i class="fas fa-shield-halved" aria-hidden="true"></i><h3>Privacy Notice</h3></div>
                    <div class="alp-side-body">
                        <p>The details you submit are used only by <?php echo htmlspecialchars(getSiteName()); ?> to:</p>
                        <ul>
                            <li><i class="fas fa-check" aria-hidden="true"></i>verify that you are a former student;</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i>keep our alumni records up to date;</li>
                            <li><i class="fas fa-check" aria-hidden="true"></i>contact you about alumni activities and school news.</li>
                        </ul>
                        <p style="margin-top:10px;">Your information is never sold or shared with third parties. Only authorised school administrators can see your email, mobile number and other details. Your profile appears in the public directory only if you choose that option and our team approves it.</p>
                        <p style="margin:0;">To correct or remove your record, contact <a href="mailto:<?php echo ADMIN_EMAIL; ?>" style="color:var(--accent-text);font-weight:600;word-break:break-all;"><?php echo htmlspecialchars(ADMIN_EMAIL); ?></a>.</p>
                    </div>
                </div>
                <div class="alp-side-card is-dark">
                    <div class="alp-side-head"><i class="fas fa-list-check" aria-hidden="true"></i><h3>What Happens Next</h3></div>
                    <div class="alp-side-body">
                        <ul class="alp-steps">
                            <li>You submit the registration form.</li>
                            <li>The school office verifies your details.</li>
                            <li>Once approved, you are added to our alumni records.</li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- ── Directory ── -->
<section class="alp-dir" id="directory">
    <div class="container">
        <div class="alp-sh">
            <div class="section-badge">Alumni Directory</div>
            <h2>Meet Our <span>Alumni</span></h2>
            <p>Former students who chose to share their journey — listed after verification by the school.</p>
        </div>
        <?php if ($directory): ?>
        <div class="alp-dir-grid">
            <?php foreach ($directory as $a): ?>
            <div class="alp-person alp-reveal">
                <div class="alp-avatar">
                    <?php if (!empty($a['photo'])): ?><img src="<?php echo htmlspecialchars($a['photo']); ?>" alt="" loading="lazy"><?php else: echo htmlspecialchars(alpInitials($a['student_name'])); endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($a['student_name']); ?></h3>
                <div class="prog"><?php echo htmlspecialchars($a['course']); ?> · <?php echo (int) $a['passing_year']; ?></div>
                <?php if ($a['current_job'] || $a['job_department']): ?>
                <div class="job"><?php echo htmlspecialchars(trim($a['current_job'] . ($a['current_job'] && $a['job_department'] ? ', ' : '') . $a['job_department'])); ?></div>
                <?php endif; ?>
                <?php if ($a['job_city']): ?><div class="loc"><i class="fas fa-location-dot" aria-hidden="true"></i><?php echo htmlspecialchars($a['job_city'] . ($a['country'] ? ', ' . $a['country'] : '')); ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alp-empty">
            <i class="fas fa-user-graduate" aria-hidden="true"></i>
            <h3>Our directory is just getting started</h3>
            <p>Register today and be among the first alumni featured here.</p>
            <a href="#register" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register Now</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Reviews ── -->
<section class="alp-reviews" id="reviews">
    <div class="container">
        <div class="alp-sh">
            <div class="section-badge">In Their Words</div>
            <h2>Alumni <span>Reviews</span></h2>
            <p>What our graduates say about their time at IQRA.</p>
        </div>
        <?php if ($just_reviewed): ?>
        <div class="alp-alert alp-alert-ok" role="status" style="max-width:720px;margin:0 auto 24px;"><i class="fas fa-check-circle" aria-hidden="true"></i><div>Thank you for your review! It will appear here after approval.</div></div>
        <?php endif; ?>
        <?php if ($reviews): ?>
        <div class="alp-rev-grid">
            <?php foreach ($reviews as $r): ?>
            <div class="alp-rev alp-reveal">
                <div class="alp-stars" aria-label="<?php echo (int) $r['rating']; ?> out of 5 stars"><?php echo str_repeat('<i class="fas fa-star" aria-hidden="true"></i>', max(1, min(5, (int) $r['rating']))); ?></div>
                <p>&ldquo;<?php echo htmlspecialchars($r['review']); ?>&rdquo;</p>
                <strong><?php echo htmlspecialchars($r['name']); ?><?php if (!empty($r['passing_year'])): ?> · <?php echo (int) $r['passing_year']; ?><?php endif; ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <details class="alp-rev-form"<?php echo $review_error ? ' open' : ''; ?>>
            <summary class="btn btn-secondary"><i class="fas fa-pen"></i> Write a Review</summary>
            <div class="alp-card">
                <div class="alp-card-body">
                    <?php if ($review_error): ?><div class="alp-alert alp-alert-err" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><div><?php echo htmlspecialchars($review_error); ?></div></div><?php endif; ?>
                    <form method="POST" action="alumni.php#reviews">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($form_token); ?>">
                        <div class="alp-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <div class="alp-grid">
                            <div class="form-group" style="margin:0;"><label for="rv_name">Your Name <span class="req">*</span></label><input id="rv_name" type="text" name="rv_name" required maxlength="100" value="<?php echo htmlspecialchars($rv['rv_name']); ?>"></div>
                            <div class="form-group" style="margin:0;"><label for="rv_year">Passing Year</label><input id="rv_year" type="number" name="rv_year" min="1990" max="<?php echo $year_now; ?>" value="<?php echo htmlspecialchars($rv['rv_year']); ?>"></div>
                            <div class="form-group span-2" style="margin:0;">
                                <label for="rv_rating">Rating <span class="req">*</span></label>
                                <select id="rv_rating" name="rv_rating" required>
                                    <?php for ($s = 5; $s >= 1; $s--): ?><option value="<?php echo $s; ?>"<?php echo (string) $s === $rv['rv_rating'] ? ' selected' : ''; ?>><?php echo $s; ?> star<?php echo $s > 1 ? 's' : ''; ?></option><?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group span-2" style="margin:0;"><label for="rv_text">Your Review <span class="req">*</span></label><textarea id="rv_text" name="rv_text" required minlength="10" maxlength="1000" rows="4"><?php echo htmlspecialchars($rv['rv_text']); ?></textarea></div>
                        </div>
                        <button type="submit" name="submit_review" value="1" class="btn btn-primary" style="margin-top:18px;"><i class="fas fa-star"></i> Submit Review</button>
                    </form>
                </div>
            </div>
        </details>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('alumniForm');
    if (form) {
        // "Other" program needs its own text
        var prog = document.getElementById('al_program'), otherWrap = document.getElementById('al_other_wrap'), other = document.getElementById('al_other');
        var syncOther = function () { var on = prog.value === 'Other'; otherWrap.classList.toggle('alp-hidden', !on); other.required = on; };
        prog.addEventListener('change', syncOther); syncOther();

        // Photo: preview + 2 MB / type check before upload
        var photo = document.getElementById('al_photo'), prev = document.getElementById('alPhotoPrev'), clear = document.getElementById('alPhotoClear');
        var resetPrev = function () { prev.innerHTML = '<i class="fas fa-user" aria-hidden="true"></i>'; clear.classList.add('alp-hidden'); };
        photo.addEventListener('change', function () {
            var file = photo.files[0];
            photo.setCustomValidity('');
            if (!file) { resetPrev(); return; }
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) photo.setCustomValidity('Photo must be a JPG, PNG or WebP image.');
            else if (file.size > 2 * 1024 * 1024) photo.setCustomValidity('Photo must be smaller than 2 MB.');
            if (!photo.checkValidity()) { photo.reportValidity(); photo.value = ''; resetPrev(); return; }
            var img = new Image(); img.alt = ''; img.src = URL.createObjectURL(file);
            prev.innerHTML = ''; prev.appendChild(img); clear.classList.remove('alp-hidden');
        });
        clear.addEventListener('click', function () { photo.value = ''; resetPrev(); });

        // Character counter
        var info = document.getElementById('al_info'), cnt = document.getElementById('alInfoCount');
        var count = function () { cnt.textContent = info.value.length; };
        info.addEventListener('input', count); count();

        // Server-side errors: bring the first one into view
        var firstBad = form.querySelector('[aria-invalid="true"]') || document.querySelector('.alp-alert-err');
        if (firstBad) firstBad.scrollIntoView({ block: 'center' });
    }

    var items = document.querySelectorAll('.alp-reveal');
    var show = function (el) { el.classList.add('is-visible'); };
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { items.forEach(show); return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var sibs = e.target.parentElement.querySelectorAll(':scope > .alp-reveal');
            e.target.style.transitionDelay = (Math.max(Array.prototype.indexOf.call(sibs, e.target), 0) % 4) * 80 + 'ms';
            show(e.target); io.unobserve(e.target);
        });
    }, { threshold: 0.08 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
