<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';
$page_title = 'Alumni';
$page_description = 'Meet the alumni of IQRA School & College Daggar Buner and read their success stories, or register to join our growing alumni network.';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_alumni'])) {
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

    // Photo upload — the stored filename is generated from scratch (timestamp +
    // random + validated extension only) so an attacker-controlled original
    // filename can never introduce path traversal or an unexpected extension.
    // getimagesize() also confirms the upload is really a decodable image,
    // not just something renamed to look like one.
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png');
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024;

        if (in_array($file_ext, $allowed, true) && $_FILES['photo']['size'] <= $max_size && @getimagesize($_FILES['photo']['tmp_name']) !== false) {
            $photo_name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
            $photo_path = 'uploads/alumni/' . $photo_name;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                compressUploadedImage($photo_path);
            }
        }
    }

    $sql = "INSERT INTO alumni (student_name, father_name, current_job, job_department, job_city, course, passing_year, photo, mobile_number, whatsapp_number, review)
            VALUES ('$student_name', '$father_name', '$current_job', '$job_department', '$job_city', '$course', $passing_year, '$photo_path', '$mobile_number', '$whatsapp_number', '$review')";

    if (mysqli_query($conn, $sql)) {
        $message = 'Thank you! Your alumni registration has been submitted and is pending approval.';
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $passing_year = intval($_POST['passing_year']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $rating = intval($_POST['rating']);

    $sql = "INSERT INTO alumni_reviews (name, passing_year, review, rating) VALUES ('$name', $passing_year, '$review', $rating)";

    if (mysqli_query($conn, $sql)) {
        $message = 'Thank you for your review! It will be displayed after approval.';
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Fetch approved alumni
$alumni_result = mysqli_query($conn, "SELECT * FROM alumni WHERE status = 'approved' ORDER BY passing_year DESC LIMIT 12");
$alumni = [];
if ($alumni_result) while ($row = mysqli_fetch_assoc($alumni_result)) $alumni[] = $row;

// Fetch approved reviews
$reviews_result = mysqli_query($conn, "SELECT * FROM alumni_reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6");
$reviews = [];
if ($reviews_result) while ($row = mysqli_fetch_assoc($reviews_result)) $reviews[] = $row;
?>
<?php include 'includes/header.php'; ?>

<style>
/* ── Alumni Page ── */
.al-hero {
    position:relative; overflow:hidden;
    min-height:280px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.85),rgba(16,42,86,0.9)),
               url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1600') center/cover no-repeat;
    padding:70px 0;
}
.al-breadcrumb {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
    font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:18px;
}
.al-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.al-breadcrumb a:hover { color:var(--accent-color); }
.al-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:16px;
}
.al-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; margin:0 0 12px; line-height:1.15; text-shadow:0 4px 20px rgba(0,0,0,0.4); }
.al-hero h1 span {
    background:linear-gradient(90deg,var(--accent-color),#0BA6C9);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.al-hero p { color:rgba(255,255,255,0.75); font-size:var(--fs-lead); margin:0; max-width:560px; line-height:1.7; }

.al-alert { padding:15px 18px; border-radius:10px; text-align:center; font-weight:600; }
.al-alert-success { background:rgba(22,163,74,0.1); border:1px solid rgba(22,163,74,0.3); color:#15803D; }
.al-alert-error { background:rgba(220,38,38,0.1); border:1px solid rgba(220,38,38,0.3); color:#B91C1C; }

.al-section { padding:var(--section-y) 0; }
.al-section.al-alt { background:var(--bg-light); }
.al-sh { text-align:center; margin-bottom:40px; }
.al-sh-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(16,42,86,0.08); border:1px solid rgba(16,42,86,0.22);
    color:var(--primary-color); padding:6px 16px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:12px;
}
.al-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--text-dark); margin:0 0 8px; }
.al-sh h2 span { color:var(--primary-color); }
.al-sh p { color:var(--text-light); font-size:var(--fs-body); margin:0; }

.al-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:24px; }
.al-card {
    background:#fff; border:1px solid rgba(16,42,86,0.1); border-radius:var(--radius-card);
    padding:30px 24px; text-align:center;
    box-shadow:0 4px 18px rgba(16,42,86,0.06);
    transition:transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    opacity:0; transform:translateY(26px);
}
.al-card.al-in { animation:alIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes alIn { from{opacity:0;transform:translateY(16px);} to{opacity:1;transform:translateY(0);} }
.al-card:hover { transform:translateY(-4px); box-shadow:0 16px 40px rgba(16,42,86,0.12); border-color:rgba(16,42,86,0.2); }
.al-avatar {
    width:96px; height:96px; border-radius:50%; margin:0 auto 16px; overflow:hidden;
    background:linear-gradient(135deg,var(--primary-color),#0B1F42);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:36px; box-shadow:0 8px 22px rgba(16,42,86,0.2);
}
.al-avatar img { width:100%; height:100%; object-fit:cover; }
.al-card h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--text-dark); margin:0 0 4px; }
.al-course { font-size:12.5px; color:var(--text-light); margin-bottom:12px; }
.al-job { font-size:13px; color:var(--primary-color); font-weight:700; margin-bottom:4px; }
.al-city { font-size:12px; color:var(--text-light); }
.al-city i { color:var(--accent-color); margin-right:4px; }

.al-review-card {
    background:#fff; border:1px solid rgba(16,42,86,0.1); border-radius:var(--radius-card); padding:28px;
    box-shadow:0 4px 18px rgba(16,42,86,0.06);
    opacity:0; transform:translateY(26px);
    transition:transform 0.3s ease, box-shadow 0.3s ease;
}
.al-review-card.al-in { animation:alIn 0.6s cubic-bezier(0.22,1,0.36,1) both; }
.al-review-card:hover { transform:translateY(-4px); box-shadow:0 14px 34px rgba(16,42,86,0.1); }
.al-stars { color:var(--accent-color); margin-bottom:14px; font-size:14px; letter-spacing:2px; }
.al-review-text { font-style:italic; color:var(--text-dark); margin:0 0 16px; line-height:1.75; font-size:14px; }
.al-review-name { font-weight:700; color:var(--primary-color); font-size:13.5px; }

.al-empty {
    text-align:center; padding:70px 20px; background:#fff; border-radius:var(--radius-card);
    box-shadow:0 4px 24px rgba(0,0,0,0.06); grid-column:1 / -1;
}
.al-empty i { font-size:62px; color:rgba(16,42,86,0.2); margin-bottom:18px; }
.al-empty h3 { color:var(--text-dark); margin-bottom:8px; }
.al-empty p { color:var(--text-light); margin:0; }

.al-form-card { max-width:820px; margin:0 auto; }
.al-form-card .al-sh { margin-bottom:32px; }
.al-form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.al-form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; }

@media(max-width:700px){
    .al-hero h1 { font-size:var(--fs-page-title); }
    .al-form-row, .al-form-row-3 { grid-template-columns:1fr; }
}
</style>

<!-- ── Hero ── -->
<section class="al-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="al-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Alumni</span>
        </div>
        <div class="al-hero-badge">
            <i class="fas fa-user-graduate" style="font-size:9px;"></i> Alumni Network
        </div>
        <h1>Our <span>Alumni</span></h1>
        <p>Join our alumni community and share your success story with fellow graduates.</p>
    </div>
</section>

<?php if ($message): ?>
<div class="container" style="margin-top:30px;">
    <div class="al-alert al-alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="container" style="margin-top:30px;">
    <div class="al-alert al-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
</div>
<?php endif; ?>

<!-- ── Alumni Grid ── -->
<section class="al-section">
    <div class="container">
        <div class="al-sh">
            <div class="al-sh-badge"><i class="fas fa-users" style="font-size:9px;"></i> Our Graduates</div>
            <h2>Meet Our <span>Alumni</span></h2>
            <p>Graduates who are now making their mark in the field</p>
        </div>
        <div class="al-grid">
        <?php if ($alumni): ?>
            <?php foreach ($alumni as $idx => $alum): $delay = ($idx % 4) * 80; ?>
            <div class="al-card" data-al-delay="<?php echo $delay; ?>">
                <div class="al-avatar">
                    <?php if (!empty($alum['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($alum['photo']); ?>" alt="<?php echo htmlspecialchars($alum['student_name']); ?>" loading="lazy">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($alum['student_name']); ?></h3>
                <div class="al-course"><?php echo htmlspecialchars($alum['course']); ?> &middot; <?php echo htmlspecialchars($alum['passing_year']); ?></div>
                <?php if (!empty($alum['current_job'])): ?>
                    <div class="al-job"><?php echo htmlspecialchars($alum['current_job']); ?></div>
                <?php endif; ?>
                <?php if (!empty($alum['job_city'])): ?>
                    <div class="al-city"><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($alum['job_city']); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="al-empty">
                <i class="fas fa-user-graduate"></i>
                <h3>No Alumni Listed Yet</h3>
                <p>Be the first to join our alumni network &mdash; register below!</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Alumni Reviews ── -->
<section class="al-section al-alt">
    <div class="container">
        <div class="al-sh">
            <div class="al-sh-badge"><i class="fas fa-star" style="font-size:9px;"></i> Testimonials</div>
            <h2>Alumni <span>Reviews</span></h2>
            <p>What our graduates say about their time with us</p>
        </div>
        <div class="al-grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr));">
        <?php if ($reviews): ?>
            <?php foreach ($reviews as $idx => $review): $delay = ($idx % 3) * 80; ?>
            <div class="al-review-card" data-al-delay="<?php echo $delay; ?>">
                <div class="al-stars">
                    <?php for ($i = 0; $i < $review['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                </div>
                <p class="al-review-text">&ldquo;<?php echo htmlspecialchars($review['review']); ?>&rdquo;</p>
                <div class="al-review-name">
                    <?php echo htmlspecialchars($review['name']); ?><?php if (!empty($review['passing_year'])): ?> &middot; <?php echo htmlspecialchars($review['passing_year']); ?><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="al-empty">
                <i class="fas fa-star"></i>
                <h3>No Reviews Yet</h3>
                <p>Share your experience using the form below!</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── Registration Form ── -->
<section class="al-section" id="registration">
    <div class="container">
        <div class="card al-form-card">
            <div class="al-sh">
                <div class="al-sh-badge"><i class="fas fa-id-card" style="font-size:9px;"></i> Join The Network</div>
                <h2>Alumni <span>Registration</span></h2>
                <p>Fill in your details to be added to our alumni directory</p>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="al-form-row">
                    <div class="form-group">
                        <label for="al_student_name">Student Name *</label>
                        <input id="al_student_name" type="text" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="al_father_name">Father Name *</label>
                        <input id="al_father_name" type="text" name="father_name" required>
                    </div>
                </div>
                <div class="al-form-row">
                    <div class="form-group">
                        <label for="al_course">Course Passed *</label>
                        <input id="al_course" type="text" name="course" required placeholder="e.g., Matric, Intermediate">
                    </div>
                    <div class="form-group">
                        <label for="al_passing_year">Passing Year *</label>
                        <input id="al_passing_year" type="number" name="passing_year" required min="1990" max="2030">
                    </div>
                </div>
                <div class="al-form-row-3">
                    <div class="form-group">
                        <label for="al_current_job">Current Job</label>
                        <input id="al_current_job" type="text" name="current_job" placeholder="Job title">
                    </div>
                    <div class="form-group">
                        <label for="al_job_department">Department</label>
                        <input id="al_job_department" type="text" name="job_department" placeholder="Department">
                    </div>
                    <div class="form-group">
                        <label for="al_job_city">City</label>
                        <input id="al_job_city" type="text" name="job_city" placeholder="City">
                    </div>
                </div>
                <div class="al-form-row">
                    <div class="form-group">
                        <label for="al_mobile">Mobile Number *</label>
                        <input id="al_mobile" type="text" name="mobile_number" required placeholder="03001234567">
                    </div>
                    <div class="form-group">
                        <label for="al_whatsapp">WhatsApp Number</label>
                        <input id="al_whatsapp" type="text" name="whatsapp_number" placeholder="03001234567">
                    </div>
                </div>
                <div class="form-group">
                    <label for="al_photo">Photo (JPG, PNG)</label>
                    <input id="al_photo" type="file" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="al_review">Your Review/Feedback * (Compulsory)</label>
                    <textarea id="al_review" name="review" required rows="5" placeholder="Share your experience with <?php echo htmlspecialchars(getSiteName()); ?>..."></textarea>
                </div>
                <button type="submit" name="submit_alumni" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-paper-plane"></i> Submit Registration
                </button>
            </form>
        </div>
    </div>
</section>

<!-- ── Submit Review Separately ── -->
<section class="al-section al-alt" id="review">
    <div class="container">
        <div class="card al-form-card" style="max-width:600px;">
            <div class="al-sh">
                <div class="al-sh-badge"><i class="fas fa-comment-dots" style="font-size:9px;"></i> Share Feedback</div>
                <h2>Submit Your <span>Review</span></h2>
                <p>Already registered? Leave a quick review for future students</p>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="al_rev_name">Your Name *</label>
                    <input id="al_rev_name" type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label for="al_rev_year">Passing Year</label>
                    <input id="al_rev_year" type="number" name="passing_year" min="1990" max="2030">
                </div>
                <div class="form-group">
                    <label for="al_rev_rating">Rating *</label>
                    <select id="al_rev_rating" name="rating" required>
                        <option value="5">&#9733;&#9733;&#9733;&#9733;&#9733; (5 Stars)</option>
                        <option value="4">&#9733;&#9733;&#9733;&#9733; (4 Stars)</option>
                        <option value="3">&#9733;&#9733;&#9733; (3 Stars)</option>
                        <option value="2">&#9733;&#9733; (2 Stars)</option>
                        <option value="1">&#9733; (1 Star)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="al_rev_text">Your Review *</label>
                    <textarea id="al_rev_text" name="review" required rows="4" placeholder="Share your thoughts..."></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-star"></i> Submit Review
                </button>
            </form>
        </div>
    </div>
</section>

<script>
(function(){
    var els = document.querySelectorAll('.al-card, .al-review-card');
    if(!els.length) return;
    var obs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){
            if(!e.isIntersecting) return;
            var d = parseInt(e.target.dataset.alDelay)||0;
            setTimeout(function(){ e.target.classList.add('al-in'); }, d);
            obs.unobserve(e.target);
        });
    },{threshold:0.08});
    els.forEach(function(el){ obs.observe(el); });
})();
</script>

<?php include 'includes/footer.php'; ?>
