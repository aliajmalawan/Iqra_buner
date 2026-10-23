<?php
/**
 * Shared "sign in on EduPortal" page body for campus-portal.php and parents-login.php.
 *
 * EduPortal's login cannot work inside an <iframe> on this website: its session
 * cookie is set without "SameSite=None; Secure", so browsers drop it in a
 * cross-site frame and the login silently returns to the form. The login
 * therefore opens on EduPortal's own page (new tab), where it works normally.
 * This website never receives or stores the password.
 *
 * Expects before include: $ep = [
 *   'url' => EduPortal login URL, 'icon' => FA icon, 'badge' => small label,
 *   'title' => H1 (may contain <span>), 'intro' => paragraph, 'button' => button text,
 *   'card_title' => text above the button, 'points' => [[icon, text], ...],
 *   'help' => [[href, icon, label], ...]
 * ]
 */
$ep_url = $ep['url'] ?? '';
$ep_hero = getSetting('portal_hero_image', 'images/banners/banner-building.jpg');
?>
<style>
.epl-hero { position:relative; overflow:hidden; isolation:isolate; padding:64px 0 140px; background:var(--primary-dark); }
.epl-hero-img { position:absolute; inset:0; z-index:-2; }
.epl-hero-img img { width:100%; height:100%; object-fit:cover; object-position:center 35%; }
.epl-hero::after { content:''; position:absolute; inset:0; z-index:-1; background:linear-gradient(90deg, rgba(11,31,66,0.95) 0%, rgba(16,42,86,0.88) 50%, rgba(16,42,86,0.7) 100%); }
.epl-breadcrumb { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:var(--fs-caption); color:rgba(255,255,255,0.6); margin-bottom:18px; }
.epl-breadcrumb a { color:rgba(255,255,255,0.75); text-decoration:none; }
.epl-breadcrumb a:hover { color:#fff; }
.epl-breadcrumb .fa-chevron-right { font-size:9px; opacity:0.7; }
.epl-badge { display:inline-flex; align-items:center; gap:10px; color:rgba(255,255,255,0.9); font-family:var(--font-heading); font-size:var(--fs-overline); font-weight:600; letter-spacing:2.2px; text-transform:uppercase; margin-bottom:14px; }
.epl-badge::before { content:''; width:28px; height:2px; border-radius:2px; background:var(--gold-color); }
.epl-hero h1 { font-size:var(--fs-page-title); font-weight:700; color:#fff; line-height:1.15; margin:0 0 14px; }
.epl-hero h1 span { color:var(--accent-bright); }
.epl-hero p { color:rgba(255,255,255,0.82); font-size:var(--fs-lead); line-height:1.7; margin:0; max-width:620px; }

.epl-main { position:relative; margin-top:-100px; padding-bottom:var(--section-y); }
.epl-card { max-width:620px; margin:0 auto; background:#fff; border-radius:var(--radius-card); border:1px solid var(--border-color); box-shadow:0 20px 50px rgba(16,42,86,0.16); overflow:hidden; }
.epl-card-head { display:flex; align-items:center; gap:16px; padding:26px 32px; border-bottom:1px solid var(--border-color); }
.epl-card-head .ui-icon { margin:0; width:56px; height:56px; font-size:24px; }
.epl-card-head h2 { font-size:var(--fs-h3); font-weight:700; color:var(--primary-color); margin:0 0 4px; }
.epl-card-head p { font-size:var(--fs-body-sm); color:var(--text-light); margin:0; }
.epl-card-body { padding:28px 32px 32px; }
.epl-btn { width:100%; justify-content:center; min-height:56px; font-size:1.05rem; }
.epl-btn .fa-arrow-up-right-from-square { font-size:12px; }
.epl-alt { display:block; text-align:center; margin-top:6px; padding:10px 0; font-size:var(--fs-body-sm); font-weight:600; color:var(--accent-text); }
.epl-alt:hover { color:var(--primary-color); text-decoration:underline; }
.epl-points { list-style:none; margin:26px 0 0; padding:20px 0 0; border-top:1px solid var(--border-color); display:flex; flex-direction:column; gap:12px; }
.epl-points li { display:flex; gap:12px; align-items:flex-start; font-size:var(--fs-body-sm); color:var(--text-color); line-height:1.6; }
.epl-points i { color:var(--accent-color); margin-top:4px; width:16px; text-align:center; flex-shrink:0; }
.epl-help { display:flex; flex-wrap:wrap; justify-content:center; gap:10px; padding:18px 24px; background:var(--background-color); border-top:1px solid var(--border-color); }
.epl-help a { display:inline-flex; align-items:center; gap:8px; min-height:40px; padding:0 14px; border-radius:8px; border:1px solid var(--border-color); background:#fff; color:var(--primary-color); font-size:13.5px; font-weight:600; text-decoration:none; }
.epl-help a:hover { border-color:var(--accent-color); color:var(--accent-text); }
.epl-missing { padding:16px 18px; border-radius:10px; background:#FFFBEB; border:1px solid #FDE68A; color:#78350F; font-size:var(--fs-body-sm); line-height:1.6; }
@media (max-width:600px) {
    .epl-hero { padding:48px 0 120px; }
    .epl-card-head, .epl-card-body { padding-left:20px; padding-right:20px; }
}
</style>

<section class="epl-hero">
    <div class="epl-hero-img" aria-hidden="true"><img src="<?php echo htmlspecialchars($ep_hero); ?>" alt=""></div>
    <div class="container">
        <nav class="epl-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page"><?php echo htmlspecialchars($ep['badge']); ?></span>
        </nav>
        <div class="epl-badge"><?php echo htmlspecialchars($ep['badge']); ?></div>
        <h1><?php echo $ep['title']; ?></h1>
        <p><?php echo htmlspecialchars($ep['intro']); ?></p>
    </div>
</section>

<section class="epl-main">
    <div class="container">
        <div class="epl-card">
            <div class="epl-card-head">
                <span class="ui-icon"><i class="fas <?php echo htmlspecialchars($ep['icon']); ?>" aria-hidden="true"></i></span>
                <div>
                    <h2><?php echo htmlspecialchars($ep['card_title']); ?></h2>
                    <p>Sign in on EduPortal, the school management system.</p>
                </div>
            </div>
            <div class="epl-card-body">
                <?php if ($ep_url): ?>
                <a href="<?php echo htmlspecialchars($ep_url); ?>" class="btn btn-accent btn-lg epl-btn" target="_blank" rel="noopener" id="eplLogin">
                    <i class="fas fa-right-to-bracket" aria-hidden="true"></i> <?php echo htmlspecialchars($ep['button']); ?> <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                </a>
                <a href="<?php echo htmlspecialchars($ep_url); ?>" class="epl-alt">or open the login in this tab</a>
                <?php else: ?>
                <div class="epl-missing"><i class="fas fa-circle-info"></i> The EduPortal login link has not been set up yet. Please contact the school office.</div>
                <?php endif; ?>
                <ul class="epl-points">
                    <?php foreach ($ep['points'] as $pt): ?>
                    <li><i class="fas <?php echo htmlspecialchars($pt[0]); ?>" aria-hidden="true"></i><span><?php echo $pt[1]; ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="epl-help">
                <?php foreach ($ep['help'] as $h): ?>
                <a href="<?php echo htmlspecialchars($h[0]); ?>"><i class="fas <?php echo htmlspecialchars($h[1]); ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($h[2]); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
