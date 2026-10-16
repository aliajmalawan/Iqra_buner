<?php
require_once 'includes/config.php';
$page_title = 'Contact Us';
$page_description = 'Get in touch with IQRA School & College Daggar Buner — find our address, phone number, email and campus locations.';

$contact_address = getSiteAddress();
$contact_phone = getSitePhone();
$contact_email = getSiteEmail();
$contact_main_campus = getMainCampus();
$contact_map_query = $contact_main_campus ? $contact_main_campus['map_query'] : str_replace(' ', '+', $contact_address);
$contact_hours = getContactHours();
$contact_subjects = getContactSubjects();
$contact_faqs = getContactFaqs();

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = mysqli_real_escape_string($conn, $_POST['name']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $q = "INSERT INTO contacts (name,email,phone,subject,message,status,created_at)
          VALUES ('$name','$email','$phone','$subject','$message','unread',NOW())";

    if (mysqli_query($conn, $q)) {
        // Admin notification
        $ma = "<html><body style='font-family:Arial;'>
            <div style='background:#102A56;color:#fff;padding:20px;text-align:center;'><h2>New Contact Message</h2></div>
            <div style='padding:20px;background:#F4F8FC;'>
            <table width='100%' style='border-collapse:collapse;'>
            <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;width:120px;'>Name</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($name)."</td></tr>
            <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Email</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($email)."</td></tr>
            <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Phone</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($phone)."</td></tr>
            <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Subject</td><td style='padding:10px;border-bottom:1px solid #ddd;'>".htmlspecialchars($subject)."</td></tr>
            <tr><td style='padding:10px;background:#f0f0f0;font-weight:bold;'>Message</td><td style='padding:10px;'>".nl2br(htmlspecialchars($message))."</td></tr>
            </table>
            <p style='background:#E9F6FA;padding:12px;border-left:4px solid #102A56;margin-top:16px;'>
            Reply to: <a href='mailto:".htmlspecialchars($email)."'>".htmlspecialchars($email)."</a> | Call: ".htmlspecialchars($phone)."</p>
            </div></body></html>";
        $mh = "MIME-Version:1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom:".SITE_NAME." <noreply@" . preg_replace('/^www\./', '', strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':')) . ">\r\nReply-To:".$email;
        mail(ADMIN_EMAIL, "New Contact – ".SITE_NAME, $ma, $mh);

        // Sender confirmation
        $ms = "<html><body style='font-family:Arial;'>
            <div style='background:#102A56;color:#fff;padding:24px;text-align:center;'><h2>".SITE_NAME."</h2><p>Thank You for Contacting Us</p></div>
            <div style='padding:24px;'>
            <p>Dear <strong>".htmlspecialchars($name)."</strong>,</p>
            <p>We have received your message about <strong>".htmlspecialchars($subject)."</strong>. Our team will get back to you within 24–48 hours.</p>
            <p style='background:#E9F6FA;padding:14px;border-left:4px solid #102A56;border-radius:4px;'>
            <strong>Contact Us Directly:</strong><br>
            📞 ".$contact_phone."<br>
            📧 ".$contact_email."<br>
            📍 ".$contact_address."
            </p>
            <p>Best Regards,<br><strong>".SITE_NAME." Team</strong></p>
            </div></body></html>";
        $sh = "MIME-Version:1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom:".SITE_NAME." <noreply@" . preg_replace('/^www\./', '', strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':')) . ">";
        mail($email, "Thank You – ".SITE_NAME, $ms, $sh);

        $success_message = "Your message has been sent! We'll get back to you within 24–48 hours.";
    } else {
        $error_message = "Error sending message. Please try again.";
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ── Contact Page ── */

/* Hero */
.ct-hero {
    position:relative; overflow:hidden;
    min-height:285px; display:flex; align-items:center;
    background:linear-gradient(130deg,rgba(11,31,66,0.86),rgba(16,42,86,0.92)),
               url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=1600') center/cover no-repeat;
    padding:62px 0;
}
.ct-hero-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(11,166,201,0.12); border:1px solid rgba(11,166,201,0.35);
    color:var(--accent-color); padding:6px 18px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:14px;
}
.ct-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; margin:0 0 10px; line-height:1.15; }
.ct-hero h1 span {
    background:linear-gradient(90deg,var(--accent-color),#0BA6C9);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.ct-hero p { color:rgba(255,255,255,0.72); font-size:var(--fs-lead); margin:0; max-width:460px; line-height:1.7; }
.ct-breadcrumb {
    display:flex; align-items:center; gap:8px;
    font-size:12px; color:rgba(255,255,255,0.5); margin-bottom:16px;
}
.ct-breadcrumb a { color:rgba(255,255,255,0.6); text-decoration:none; }
.ct-breadcrumb a:hover { color:var(--accent-color); }

/* ── Info Cards Strip ── */
.ct-info { padding:0; background:#F4F8FC; margin-top:-1px; }
.ct-info-grid {
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:0;
    border-bottom:1px solid rgba(16,42,86,0.1);
}
.ct-info-card {
    padding:28px 28px;
    border-right:1px solid rgba(16,42,86,0.1);
    display:flex; align-items:flex-start; gap:16px;
    transition:background 0.3s ease;
    opacity:0; transform:translateY(22px);
}
.ct-info-card.ct-in { animation:ctIn 0.55s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes ctIn {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
}
.ct-info-card:last-child { border-right:none; }
.ct-info-card:hover { background:rgba(16,42,86,0.04); }
.ct-info-ico {
    width:48px; height:48px; border-radius:var(--radius-card); flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:20px;
}
.ct-info-text h4 { font-size:14px; font-weight:700; color:var(--text-dark); margin:0 0 5px; }
.ct-info-text p  { font-size:13px; color:var(--text-light); margin:0 0 3px; line-height:1.55; }
.ct-info-text a  { color:var(--primary-color); font-weight:600; text-decoration:none; font-size:13px; }
.ct-info-text a:hover { color:#0B1F42; }

/* ── Main: Form + Map ── */
.ct-main { padding:var(--section-y) 0; background:#F4F8FC; }
.ct-layout {
    display:grid; grid-template-columns:1fr 1fr;
    gap:28px; align-items:start;
}

/* Form card */
.ct-form-card {
    background:#fff; border-radius:var(--radius-card);
    border:1px solid rgba(16,42,86,0.1);
    box-shadow:0 6px 28px rgba(0,0,0,0.07);
    overflow:hidden;
}
.ct-form-head {
    background:linear-gradient(130deg,var(--primary-color),#0B1F42);
    padding:22px 28px;
    display:flex; align-items:center; gap:13px;
}
.ct-form-head-ico {
    width:44px; height:44px; border-radius:12px;
    background:rgba(255,255,255,0.12);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; color:#fff; flex-shrink:0;
}
.ct-form-head h3 { color:#fff; font-size:16px; font-weight:700; margin:0 0 3px; }
.ct-form-head p  { color:rgba(255,255,255,0.7); font-size:12px; margin:0; }
.ct-form-body { padding:26px 28px; }

/* Alert */
.ct-alert {
    display:flex; align-items:flex-start; gap:10px;
    padding:13px 16px; border-radius:10px; margin-bottom:20px;
    font-size:13px; line-height:1.6;
}
.ct-alert-ok  { background:#d4edda; color:#155724; border:1px solid #b8dfc4; }
.ct-alert-err { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.ct-alert i   { font-size:15px; margin-top:1px; flex-shrink:0; }

/* Fields */
.ct-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.ct-field  { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.ct-field label { font-size:12.5px; font-weight:600; color:var(--text-dark); }
.ct-field label .req { color:#e53935; margin-left:2px; }
.ct-field input,
.ct-field select,
.ct-field textarea {
    border:1.5px solid rgba(16,42,86,0.18);
    border-radius:10px; padding:10px 13px;
    font-size:13.5px; color:var(--text-dark);
    background:#fff; transition:border-color 0.25s,box-shadow 0.25s;
    font-family:inherit; width:100%; box-sizing:border-box;
}
.ct-field input:focus,
.ct-field select:focus,
.ct-field textarea:focus {
    outline:none; border-color:var(--primary-color);
    box-shadow:0 0 0 3px rgba(16,42,86,0.1);
}
.ct-field textarea { min-height:110px; resize:vertical; }
.ct-submit {
    width:100%; padding:12px; border:none; border-radius:11px;
    background:var(--primary-color); color:#fff;
    font-size:14.5px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:8px;
    transition:color 0.3s cubic-bezier(0.25,1,0.5,1), background-color 0.3s cubic-bezier(0.25,1,0.5,1), border-color 0.3s cubic-bezier(0.25,1,0.5,1), box-shadow 0.3s cubic-bezier(0.25,1,0.5,1), transform 0.3s cubic-bezier(0.25,1,0.5,1), opacity 0.3s cubic-bezier(0.25,1,0.5,1);
    font-family:inherit; margin-top:6px;
}
.ct-submit:hover { background:#0B1F42; transform:translateY(-2px); box-shadow:0 10px 26px rgba(16,42,86,0.3); }

/* Right side */
.ct-right { display:flex; flex-direction:column; gap:20px; }

/* Map */
.ct-map {
    border-radius:var(--radius-card); overflow:hidden;
    border:1px solid rgba(16,42,86,0.12);
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    height:280px;
}
.ct-map iframe { width:100%; height:100%; border:none; display:block; }

/* Hours card */
.ct-hours-card {
    background:#fff; border-radius:var(--radius-card);
    border:1px solid rgba(16,42,86,0.1);
    box-shadow:0 4px 18px rgba(0,0,0,0.06);
    overflow:hidden;
}
.ct-hours-head {
    background:linear-gradient(130deg,var(--primary-color),#0B1F42);
    padding:14px 20px;
    display:flex; align-items:center; gap:10px;
}
.ct-hours-head i  { color:rgba(255,255,255,0.85); font-size:15px; }
.ct-hours-head h4 { color:#fff; font-size:14px; font-weight:700; margin:0; }
.ct-hours-body { padding:4px 0 12px; }
.ct-hour-row {
    display:flex; align-items:center; justify-content:space-between;
    padding:9px 20px; border-bottom:1px solid rgba(16,42,86,0.06);
    font-size:13px;
}
.ct-hour-row:last-child { border-bottom:none; }
.ct-hour-row .day  { font-weight:600; color:var(--text-dark); }
.ct-hour-row .time { color:var(--text-light); }
.ct-hour-row .closed { color:#e53935; font-weight:600; }

/* ── FAQ ── */
.ct-faq { padding:var(--section-y) 0; background:#fff; }
.ct-faq-sh { text-align:center; margin-bottom:38px; }
.ct-faq-badge {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(16,42,86,0.08); border:1px solid rgba(16,42,86,0.22);
    color:var(--primary-color); padding:6px 16px; border-radius:50px;
    font-size:10.5px; font-weight:700; letter-spacing:2px;
    text-transform:uppercase; margin-bottom:12px;
}
.ct-faq-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--text-dark); margin:0 0 8px; }
.ct-faq-sh h2 span { color:var(--primary-color); }
.ct-faq-sh p  { color:var(--text-light); font-size:var(--fs-body); margin:0; }

.ct-faq-wrap { max-width:760px; margin:0 auto; display:flex; flex-direction:column; gap:10px; }

/* Accordion item */
.ct-faq-item {
    border:1px solid rgba(16,42,86,0.12);
    border-radius:var(--radius-card); overflow:hidden;
    opacity:0; transform:translateY(20px);
}
.ct-faq-item.ct-in { animation:ctIn 0.55s cubic-bezier(0.22,1,0.36,1) both; }
.ct-faq-q {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px; cursor:pointer;
    background:#fff; transition:background 0.25s;
    font-size:14px; font-weight:700; color:var(--text-dark);
    gap:12px;
}
.ct-faq-q:hover { background:rgba(16,42,86,0.04); }
.ct-faq-q.open  { background:rgba(16,42,86,0.05); color:var(--primary-color); }
.ct-faq-chevron {
    width:28px; height:28px; border-radius:50%; flex-shrink:0;
    background:rgba(16,42,86,0.08);
    display:flex; align-items:center; justify-content:center;
    font-size:11px; color:var(--primary-color);
    transition:transform 0.3s ease, background 0.25s;
}
.ct-faq-q.open .ct-faq-chevron { transform:rotate(180deg); background:var(--primary-color); color:#fff; }
.ct-faq-a {
    max-height:0; overflow:hidden;
    transition:max-height 0.4s cubic-bezier(0.25,1,0.5,1), padding 0.3s;
    font-size:13.5px; color:var(--text-light); line-height:1.7;
    padding:0 20px; background:#fff;
}
.ct-faq-a.open { max-height:200px; padding:0 20px 16px; }

/* ── CTA ── */
.ct-cta {
    padding:var(--section-y-sm) 0;
    background:linear-gradient(130deg,var(--primary-color) 0%,#102A56 55%,#0B1F42 100%);
    position:relative; overflow:hidden;
}
.ct-cta::before {
    content:''; position:absolute; top:-100px; right:-100px;
    width:280px; height:280px;
    background:radial-gradient(circle,rgba(11,166,201,0.09) 0%,transparent 70%);
    border-radius:50%; pointer-events:none;
}

/* Responsive */
@media(max-width:900px) {
    .ct-layout   { grid-template-columns:1fr; }
    .ct-info-grid{ grid-template-columns:1fr; }
    .ct-info-card{ border-right:none; border-bottom:1px solid rgba(16,42,86,0.1); }
    .ct-info-card:last-child { border-bottom:none; }
    .ct-hero h1  { font-size:var(--fs-page-title); }
}
@media(max-width:580px) {
    .ct-row-2 { grid-template-columns:1fr; }
}
</style>

<!-- ── Hero ── -->
<section class="ct-hero">
    <div class="container" style="position:relative;z-index:2;">
        <div class="ct-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" style="font-size:9px;"></i>
            <span>Contact Us</span>
        </div>
        <div class="ct-hero-badge">
            <i class="fas fa-envelope" style="font-size:9px;"></i>
            Get in Touch
        </div>
        <h1>Contact <span>IQRA</span></h1>
        <p>We're here to help — reach out for admissions, course info, or any general inquiry.</p>
    </div>
</section>

<!-- ── Info Cards ── -->
<div class="ct-info">
    <div class="container" style="padding:0;">
        <div class="ct-info-grid">

            <div class="ct-info-card" data-ct-delay="0">
                <div class="ct-info-ico" style="background:rgba(16,42,86,0.1);">
                    <i class="fas fa-map-marker-alt" style="color:var(--primary-color);"></i>
                </div>
                <div class="ct-info-text">
                    <h4>Visit Us</h4>
                    <p><?php echo htmlspecialchars($contact_address); ?></p>
                    <a href="https://maps.google.com/?q=<?php echo htmlspecialchars($contact_map_query); ?>" target="_blank">
                        <i class="fas fa-directions" style="font-size:10px;"></i> Get Directions
                    </a>
                </div>
            </div>

            <div class="ct-info-card" data-ct-delay="110">
                <div class="ct-info-ico" style="background:rgba(37,211,102,0.1);">
                    <i class="fas fa-phone-alt" style="color:#25D366;"></i>
                </div>
                <div class="ct-info-text">
                    <h4>Call / WhatsApp</h4>
                    <a href="tel:<?php echo str_replace(' ','',$contact_phone); ?>"><?php echo htmlspecialchars($contact_phone); ?></a>
                    <p><?php echo htmlspecialchars(getOfficeHours()); ?></p>
                </div>
            </div>

            <div class="ct-info-card" data-ct-delay="220">
                <div class="ct-info-ico" style="background:rgba(11,166,201,0.1);">
                    <i class="fas fa-envelope" style="color:var(--accent-color);"></i>
                </div>
                <div class="ct-info-text">
                    <h4>Email Us</h4>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a>
                    <p>We reply within 24 hours</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Form + Map ── -->
<section class="ct-main">
    <div class="container">
        <div class="ct-layout">

            <!-- Contact Form -->
            <div class="ct-form-card">
                <div class="ct-form-head">
                    <div class="ct-form-head-ico"><i class="fas fa-paper-plane"></i></div>
                    <div>
                        <h3>Send Us a Message</h3>
                        <p>We'll get back to you within 24–48 hours</p>
                    </div>
                </div>
                <div class="ct-form-body">
                    <?php if($success_message): ?>
                    <div class="ct-alert ct-alert-ok">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo $success_message; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($error_message): ?>
                    <div class="ct-alert ct-alert-err">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div><?php echo $error_message; ?></div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="contactForm">
                        <div class="ct-row-2">
                            <div class="ct-field">
                                <label for="auto_contact_php_1">Your Name <span class="req">*</span></label>
                                <input id="auto_contact_php_1" type="text" name="name" required placeholder="Full name">
                            </div>
                            <div class="ct-field">
                                <label for="auto_contact_php_2">Phone Number <span class="req">*</span></label>
                                <input id="auto_contact_php_2" type="tel" name="phone" required placeholder="03001234567">
                            </div>
                        </div>
                        <div class="ct-field">
                            <label for="auto_contact_php_3">Email Address <span class="req">*</span></label>
                            <input id="auto_contact_php_3" type="email" name="email" required placeholder="your.email@example.com">
                        </div>
                        <div class="ct-field">
                            <label for="auto_contact_php_4">Subject <span class="req">*</span></label>
                            <select id="auto_contact_php_4" name="subject" required>
                                <option value="">— Select Subject —</option>
                                <?php foreach ($contact_subjects as $subj): ?>
                                <option><?php echo htmlspecialchars($subj); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ct-field">
                            <label for="auto_contact_php_5">Your Message <span class="req">*</span></label>
                            <textarea id="auto_contact_php_5" name="message" required placeholder="Write your message here..."></textarea>
                        </div>
                        <button type="submit" class="ct-submit">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>

            <!-- Map + Hours -->
            <div class="ct-right">
                <!-- Map -->
                <div class="ct-map">
                    <iframe
                        src="https://maps.google.com/maps?q=<?php echo htmlspecialchars($contact_map_query); ?>&output=embed&z=14"
                        allowfullscreen loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>

                <!-- Hours -->
                <div class="ct-hours-card">
                    <div class="ct-hours-head">
                        <i class="fas fa-clock"></i>
                        <h4>Office Hours</h4>
                    </div>
                    <div class="ct-hours-body">
                        <?php foreach ($contact_hours as $h): ?>
                        <div class="ct-hour-row">
                            <span class="day"><?php echo htmlspecialchars($h['day']); ?></span>
                            <span class="<?php echo strtolower($h['time']) === 'closed' ? 'closed' : 'time'; ?>"><?php echo htmlspecialchars($h['time']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick contact strip -->
                <div style="background:#fff;border:1px solid rgba(16,42,86,0.1);border-radius:var(--radius-card);padding:18px 20px;box-shadow:0 4px 18px rgba(0,0,0,0.06);">
                    <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--primary-color);margin-bottom:12px;">Quick Contact</div>
                    <a href="tel:<?php echo str_replace(' ','',$contact_phone); ?>"
                       style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(16,42,86,0.08);text-decoration:none;">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(37,211,102,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-phone-alt" style="color:#25D366;font-size:13px;"></i>
                        </div>
                        <div>
                            <div style="font-size:var(--fs-caption-sm);color:var(--text-light);">Call / WhatsApp</div>
                            <div style="font-size:13.5px;font-weight:700;color:var(--text-dark);"><?php echo htmlspecialchars($contact_phone); ?></div>
                        </div>
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"
                       style="display:flex;align-items:center;gap:10px;padding:9px 0;text-decoration:none;">
                        <div style="width:34px;height:34px;border-radius:10px;background:rgba(11,166,201,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-envelope" style="color:var(--accent-color);font-size:13px;"></i>
                        </div>
                        <div>
                            <div style="font-size:var(--fs-caption-sm);color:var(--text-light);">Email</div>
                            <div style="font-size:13px;font-weight:700;color:var(--text-dark);"><?php echo htmlspecialchars($contact_email); ?></div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── FAQ ── -->
<section class="ct-faq">
    <div class="container">
        <div class="ct-faq-sh">
            <div class="ct-faq-badge"><i class="fas fa-question-circle" style="font-size:9px;"></i> FAQs</div>
            <h2>Frequently Asked <span>Questions</span></h2>
            <p>Quick answers to the most common queries</p>
        </div>
        <div class="ct-faq-wrap">
        <?php foreach($contact_faqs as $i=>$f): ?>
        <div class="ct-faq-item" data-ct-delay="<?php echo $i*80; ?>">
            <div class="ct-faq-q" role="button" tabindex="0" aria-expanded="false" onclick="toggleFaq(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleFaq(this);}">
                <span><?php echo htmlspecialchars($f['question']); ?></span>
                <div class="ct-faq-chevron"><i class="fas fa-chevron-down"></i></div>
            </div>
            <div class="ct-faq-a"><?php echo htmlspecialchars($f['answer']); ?></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="ct-cta">
    <div class="container">
        <div style="display:flex;align-items:center;gap:44px;flex-wrap:wrap;position:relative;z-index:1;">
            <div style="flex:1 1 300px;">
                <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(11,166,201,0.12);border:1px solid rgba(11,166,201,0.3);color:var(--accent-color);padding:6px 16px;border-radius:50px;font-size:10.5px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px;">
                    <i class="fas fa-graduation-cap" style="font-size:9px;"></i> Take the Next Step
                </div>
                <h2 style="font-size:var(--fs-h2);font-weight:700;color:#fff;margin:0 0 10px;line-height:1.25;">
                    Ready to Join<br>
                    <span style="background:linear-gradient(90deg,var(--accent-color),#0BA6C9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"><?php echo getSiteName(); ?>?</span>
                </h2>
                <p style="color:rgba(255,255,255,0.68);font-size:13.5px;margin:0;line-height:1.7;">
                    Apply for admission or explore our courses — our team is ready to guide you.
                </p>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;flex-shrink:0;">
                <a href="admission.php" class="btn btn-accent">
                    <i class="fas fa-graduation-cap"></i> Apply Now
                </a>
                <a href="courses.php" class="btn btn-outline">
                    <i class="fas fa-book-open"></i> View Courses
                </a>
            </div>
        </div>
    </div>
</section>

<script>
(function(){
    /* Scroll animations */
    var els = document.querySelectorAll('.ct-info-card, .ct-faq-item');
    if(els.length){
        var obs = new IntersectionObserver(function(entries){
            entries.forEach(function(e){
                if(!e.isIntersecting) return;
                var d = parseInt(e.target.dataset.ctDelay)||0;
                setTimeout(function(){ e.target.classList.add('ct-in'); }, d);
                obs.unobserve(e.target);
            });
        },{threshold:0.1});
        els.forEach(function(el){ obs.observe(el); });
    }

    /* Form card fade-in */
    var fc = document.querySelector('.ct-form-card');
    if(fc){
        var fo = new IntersectionObserver(function(entries){
            if(entries[0].isIntersecting){
                fc.style.transition='opacity 0.7s ease,transform 0.7s ease';
                fc.style.opacity='1'; fc.style.transform='translateY(0)';
                fo.disconnect();
            }
        },{threshold:0.1});
        fc.style.opacity='0'; fc.style.transform='translateY(24px)';
        fo.observe(fc);
    }
})();

/* FAQ accordion */
function toggleFaq(btn){
    var ans  = btn.nextElementSibling;
    var open = btn.classList.contains('open');

    /* Close all */
    document.querySelectorAll('.ct-faq-q').forEach(function(b){
        b.classList.remove('open');
        b.setAttribute('aria-expanded', 'false');
        b.nextElementSibling.classList.remove('open');
    });

    /* Open clicked (if was closed) */
    if(!open){ btn.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); ans.classList.add('open'); }
}
</script>

<?php include 'includes/footer.php'; ?>
