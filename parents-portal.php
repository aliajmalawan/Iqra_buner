<?php
require_once 'includes/config.php';
$page_title = 'Parents Portal';
$page_description = 'Parents Portal of ' . getSiteName() . ' — follow your child\'s results, attendance and fee details through the school\'s EduPortal system.';

// The portal itself is EduPortal (AdminCP > EduPortal & ERP > EduPortal Login Links).
// This page never shows or stores any student record — parents sign in on EduPortal,
// which only shows each parent the children linked to their own account.
$eduportal = getEduPortalLinks();
$parents_url = $eduportal['parents'];
$pp_hero_image = getSetting('parents_hero_image', 'images/banners/banner-building.jpg');
$tel = preg_replace('/[^\d+]/', '', SITE_CONTACT);

$features = [
    ['fa-chart-column',    'Academic Results',       'Term and annual examination results and progress.'],
    ['fa-calendar-check',  'Attendance',             'Daily attendance record of your child.'],
    ['fa-file-invoice',    'Fee Details & Dues',     'Fee vouchers, payments and any outstanding dues.'],
    ['fa-id-card',         'Student Information',    'Class, section and other details on record at school.'],
];
?>
<?php include 'includes/header.php'; ?>

<style>
/* ================================================================
   Parents Portal — information page; the portal itself is EduPortal
   ================================================================ */
.pp-hero {
    position:relative; overflow:hidden; isolation:isolate;
    min-height:400px; display:flex; align-items:center;
    padding:72px 0 64px; background:var(--primary-dark);
}
.pp-hero-img { position:absolute; inset:0; z-index:-2; }
.pp-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 35%; }
.pp-hero::after { content:''; position:absolute; inset:0; z-index:-1; background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.86) 45%, rgba(16,42,86,0.58) 100%); }
.pp-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.pp-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.pp-breadcrumb a:hover { color:#fff; }
.pp-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.pp-badge { display:inline-flex; align-items:center; gap:10px; color:rgba(255,255,255,0.9); font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px; }
.pp-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.pp-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.pp-hero h1 span { color:var(--gold-color); }
.pp-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:620px; }
.pp-hero-btns { display:flex; flex-wrap:wrap; gap:12px; margin-top:28px; }
.pp-gold { background:var(--gold-color) !important; border-color:var(--gold-color) !important; color:var(--primary-dark) !important; }
.pp-gold:hover { background:#E8B84A !important; border-color:#E8B84A !important; }
.pp-hero-note { display:flex; align-items:center; gap:8px; margin-top:14px; font-size:var(--fs-caption); color:rgba(255,255,255,0.65); }

.pp-sh { text-align:center; margin-bottom:40px; }
.pp-sh h2 { font-size:var(--fs-h2); font-weight:700; color:var(--primary-color); margin:0 0 12px; }
.pp-sh h2 span { color:var(--accent-hover); }
.pp-sh p { color:var(--text-light); font-size:var(--fs-body); max-width:640px; margin:0 auto; line-height:1.7; }

.pp-notice { display:flex; gap:16px; align-items:flex-start; max-width:900px; margin:0 auto; padding:22px 24px; border-radius:var(--radius-card); background:#FFFBEB; border:1px solid #FDE68A; color:#78350F; }
.pp-notice > i { font-size:22px; color:#B45309; margin-top:2px; }
.pp-notice h2 { font-size:var(--fs-card-title); color:#78350F; margin:0 0 6px; }
.pp-notice p { margin:0 0 12px; line-height:1.7; font-size:var(--fs-body-sm); }
.pp-notice-btns { display:flex; gap:10px; flex-wrap:wrap; }
.pp-status { padding:36px 0 0; background:#fff; }

.pp-features { padding:var(--section-y) 0; background:#fff; }
.pp-feat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; }
.pp-feat-grid .ui-card h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); margin:0 0 8px; }
.pp-feat-grid .ui-card p { font-size:var(--fs-body-sm); color:var(--text-light); line-height:1.7; margin:0; }
.pp-feat-note { text-align:center; color:var(--text-light); font-size:var(--fs-caption); margin:22px 0 0; }

.pp-how { padding:var(--section-y) 0; background:var(--background-color); }
.pp-how-grid { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:28px; align-items:start; }
.pp-steps { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:14px; counter-reset:s; }
.pp-steps li { counter-increment:s; display:flex; gap:16px; padding:20px 22px; border-radius:var(--radius-card); background:#fff; border:1px solid var(--border-color); box-shadow:var(--shadow-card); }
.pp-steps li::before { content:counter(s); width:36px; height:36px; border-radius:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:var(--primary-color); color:#fff; font-family:var(--font-heading); font-weight:700; }
.pp-steps h3 { font-size:var(--fs-card-title); font-weight:700; color:var(--primary-color); margin:0 0 4px; }
.pp-steps p { font-size:var(--fs-body-sm); color:var(--text-light); margin:0; line-height:1.65; }
.pp-safe { padding:24px; border-radius:var(--radius-card); background:var(--primary-color); color:rgba(255,255,255,0.85); border-bottom:3px solid var(--gold-color); }
.pp-safe h3 { color:#fff; font-size:var(--fs-card-title); margin:0 0 14px; display:flex; align-items:center; gap:10px; }
.pp-safe h3 i { color:var(--gold-color); }
.pp-safe ul { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px; font-size:var(--fs-body-sm); line-height:1.6; }
.pp-safe li { display:flex; gap:10px; }
.pp-safe li i { color:var(--accent-bright); margin-top:4px; font-size:12px; }

.pp-cta { padding:var(--section-y-sm) 0; background:#fff; }
.pp-cta-box { display:flex; align-items:center; justify-content:space-between; gap:28px; flex-wrap:wrap; padding:36px 40px; border-radius:var(--radius-card); background:var(--primary-color); border-bottom:4px solid var(--gold-color); }
.pp-cta-box h2 { color:#fff; font-size:var(--fs-h3); margin:0 0 6px; }
.pp-cta-box p { color:rgba(255,255,255,0.75); margin:0; }
.pp-cta-btns { display:flex; gap:12px; flex-wrap:wrap; }

@media (max-width:1024px) { .pp-feat-grid { grid-template-columns:1fr 1fr; } .pp-how-grid { grid-template-columns:1fr; } }
@media (max-width:600px) {
    .pp-hero { min-height:0; padding:56px 0 48px; }
    .pp-hero-btns .btn, .pp-cta-btns .btn { flex:1 1 100%; }
    .pp-feat-grid { grid-template-columns:1fr; }
    .pp-notice { flex-direction:column; }
    .pp-cta-box { padding:28px 22px; }
}
</style>

<section class="pp-hero">
    <div class="pp-hero-img" aria-hidden="true"><img src="<?php echo htmlspecialchars($pp_hero_image); ?>" alt="" fetchpriority="high"></div>
    <div class="container">
        <nav class="pp-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Parents Portal</span>
        </nav>
        <div class="pp-badge">For Parents &amp; Guardians</div>
        <h1>Parents <span>Portal</span></h1>
        <p>Follow your child's progress — results, attendance and fee details — through IQRA's school management system, EduPortal.</p>
        <div class="pp-hero-btns">
            <a href="parents-login.php" class="btn btn-lg pp-gold"><i class="fas fa-right-to-bracket"></i> Parents Login</a>
            <a href="tel:<?php echo $tel; ?>" class="btn btn-outline btn-lg"><i class="fas fa-phone"></i> <?php echo htmlspecialchars(SITE_CONTACT); ?></a>
        </div>
        <div class="pp-hero-note"><i class="fas fa-lock" aria-hidden="true"></i> You sign in on EduPortal's secure login page — this website never sees your password.</div>
    </div>
</section>


<section class="pp-features">
    <div class="container">
        <div class="pp-sh">
            <div class="section-badge">What You Can See</div>
            <h2>Everything About Your Child, <span>In One Place</span></h2>
            <p>After signing in, parents can view the information the school keeps in EduPortal for their own children.</p>
        </div>
        <div class="pp-feat-grid">
            <?php foreach ($features as $ft): ?>
            <div class="ui-card">
                <span class="ui-icon"><i class="fas <?php echo $ft[0]; ?>" aria-hidden="true"></i></span>
                <h3><?php echo $ft[1]; ?></h3>
                <p><?php echo $ft[2]; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="pp-feat-note">Available features depend on what the school has enabled in EduPortal.</p>
    </div>
</section>

<section class="pp-how">
    <div class="container">
        <div class="pp-sh">
            <div class="section-badge">Getting Started</div>
            <h2>How Parents <span>Get Access</span></h2>
        </div>
        <div class="pp-how-grid">
            <ol class="pp-steps">
                <li><div><h3>Get your login from the school</h3><p>Parents Portal accounts are issued by the school office and linked to your own children only. If you do not have one yet, contact the school office.</p></div></li>
                <li><div><h3>Sign in on EduPortal</h3><p>Open <a href="parents-login.php" style="color:var(--accent-text);font-weight:600;">Parents Login</a> and sign in with the email and password given to you.</p></div></li>
                <li><div><h3>Forgot your password?</h3><p>Use the "Forgot Password?" option on the EduPortal login page, or ask the school office to reset it.</p></div></li>
            </ol>
            <div class="pp-safe">
                <h3><i class="fas fa-shield-halved" aria-hidden="true"></i> Your Child's Data Stays Private</h3>
                <ul>
                    <li><i class="fas fa-check" aria-hidden="true"></i>Results, attendance and fee records are shown only inside EduPortal after you sign in — never on this public website.</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i>Each account shows only the children linked to it by the school.</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i>This website never asks for or stores your Parents Portal password.</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i>Never share your password — school staff will not ask for it.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="pp-cta">
    <div class="container">
        <div class="pp-cta-box">
            <div>
                <h2>Need help with the Parents Portal?</h2>
                <p>The school office can help with logins, password resets and any question about your child's record.</p>
            </div>
            <div class="pp-cta-btns">
                <a href="parents-login.php" class="btn btn-accent"><i class="fas fa-right-to-bracket"></i> Parents Login</a>
                <a href="tel:<?php echo $tel; ?>" class="btn btn-outline"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
