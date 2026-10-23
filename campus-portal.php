<?php
require_once 'includes/config.php';
$page_title = 'Campus Portal';
$page_description = 'Log in to the IQRA School & College Daggar Buner student portal to access your grades, attendance, fee status and academic records.';
$eduportal = getEduPortalLinks();
$campus_url = $eduportal['campus'] ?: $eduportal['admin'];
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== CAMPUS PORTAL PAGE ===== */
:root {
    --primary-color: #102A56;
    --accent-color:  #0BA6C9;
    --text-dark:     #243247;
}

/* Remove any bottom padding from body so iframe fills the screen */
body { overflow-x: hidden; }

/* --- Portal Top Bar --- */
.portal-topbar {
    background: linear-gradient(135deg, #0B1F42 0%, #102A56 60%, #0B1F42 100%);
    padding: 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 24px rgba(11,31,66,0.3);
}
.portal-topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    height: 58px;
    flex-wrap: nowrap;
}

/* Left: breadcrumb + title */
.portal-topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.portal-topbar-divider {
    width: 1px;
    height: 28px;
    background: rgba(255,255,255,0.2);
    flex-shrink: 0;
}
.portal-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.portal-brand-icon {
    width: 34px; height: 34px;
    border-radius: 9px;
    background: rgba(11,166,201,0.2);
    border: 1px solid rgba(11,166,201,0.35);
    display: flex; align-items: center; justify-content: center;
    color: #3DBBD8; font-size: 15px;
    flex-shrink: 0;
}
.portal-brand-text { min-width: 0; }
.portal-brand-text h1 {
    display: block; margin: 0;
    color: #fff; font-size: 14px; font-weight: 700; line-height: 1.2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.portal-brand-text span {
    color: rgba(255,255,255,0.55); font-size: var(--fs-caption-sm); font-weight: 400;
}
.portal-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: rgba(255,255,255,0.55);
}
.portal-breadcrumb a {
    color: rgba(255,255,255,0.55); text-decoration: none;
    transition: color .2s;
}
.portal-breadcrumb a:hover { color: var(--accent-color); }
.portal-breadcrumb .cur { color: #3DBBD8; font-weight: 500; }

/* Right: action buttons */
.portal-topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.portal-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 8px;
    font-size: 12px; font-weight: 600;
    cursor: pointer; text-decoration: none; white-space: nowrap;
    transition: color .22s ease, background-color .22s ease, border-color .22s ease, box-shadow .22s ease, transform .22s ease, opacity .22s ease; border: none;
    font-family: inherit;
    min-height:36px; min-width:36px; justify-content:center;
}
.portal-action-btn.outline {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.85);
}
.portal-action-btn.outline:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
}
.portal-action-btn.gold {
    background: var(--accent-color);
    color: #FFFFFF;
    box-shadow: 0 3px 12px rgba(11,166,201,0.35);
}
.portal-action-btn.gold:hover {
    background: #098BA9;
    transform: translateY(-1px);
}

/* --- Status Bar --- */
.portal-status-bar {
    background: #E9F6FA;
    border-bottom: 1px solid rgba(16,42,86,0.12);
    padding: 8px 0;
}
.portal-status-inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
}
.portal-status-left {
    display: flex; align-items: center; gap: 18px;
}
.portal-status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 500; color: var(--text-light, #64748B);
}
.portal-status-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #16A34A;
    box-shadow: 0 0 0 3px rgba(22,163,74,0.2);
    animation: dotPulse 2s ease-in-out infinite;
}
@keyframes dotPulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(22,163,74,0.2); }
    50%      { box-shadow: 0 0 0 6px rgba(22,163,74,0.08); }
}
.portal-status-info {
    display: flex; align-items: center; gap: 5px;
    font-size: var(--fs-caption-sm); color: #64748B;
}
.portal-status-info i { font-size: 11px; color: var(--primary-color); }

/* --- Iframe Wrapper --- */
.portal-frame-wrap {
    position: relative;
    /* Viewport minus: main-header (~78px) + top-bar (~38px) + portal-topbar (58px) + status-bar (~38px) */
    height: calc(100vh - 212px);
    min-height: 500px;
    background: #f0f4f4;
}
.portal-frame-wrap iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

/* Loading overlay */
.portal-loading {
    position: absolute; inset: 0;
    background: #E9F6FA;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 20px;
    z-index: 5;
    transition: opacity .4s ease;
}
.portal-loading.hidden {
    opacity: 0; pointer-events: none;
}
.portal-spinner {
    width: 52px; height: 52px;
    border-radius: 50%;
    border: 4px solid rgba(16,42,86,0.15);
    border-top-color: var(--primary-color);
    animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.portal-loading-text {
    text-align: center;
}
.portal-loading-text strong {
    display: block; color: var(--text-dark);
    font-size: 16px; font-weight: 700; margin-bottom: 4px;
}
.portal-loading-text span {
    color: #64748B; font-size: 13px;
}

/* --- Bottom Info Bar --- */
.portal-footer-bar {
    background: #fff;
    border-top: 1px solid rgba(16,42,86,0.1);
    padding: 10px 0;
}
.portal-footer-inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
}
.portal-footer-left {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; color: #64748B;
}
.portal-footer-left i { color: var(--primary-color); font-size: 13px; }
.portal-footer-left strong { color: var(--text-dark); font-weight: 600; }
.portal-footer-right {
    display: flex; align-items: center; gap: 8px;
}
.portal-footer-link {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 500; color: var(--primary-color);
    text-decoration: none; padding: 5px 12px;
    border-radius: 7px; background: rgba(16,42,86,0.07);
    border: 1px solid rgba(16,42,86,0.12);
    transition: color .2s ease, background-color .2s ease, border-color .2s ease, box-shadow .2s ease, transform .2s ease, opacity .2s ease;
}
.portal-footer-link:hover {
    background: var(--primary-color); color: #fff;
}

@media(max-width: 640px) {
    .portal-brand-text span { display: none; }
    .portal-breadcrumb { display: none; }
    .portal-topbar-divider { display: none; }
    .portal-action-btn span { display: none; }
    .portal-status-info { display: none; }
    .portal-frame-wrap { height: calc(100vh - 190px); }
}
</style>

<!-- PORTAL TOP BAR -->
<div class="portal-topbar">
    <div class="container">
        <div class="portal-topbar-inner">
            <div class="portal-topbar-left">
                <div class="portal-brand">
                    <div class="portal-brand-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="portal-brand-text">
                        <h1><?php echo htmlspecialchars(SITE_NAME); ?> Campus Portal</h1>
                        <span>Student &amp; Staff Login</span>
                    </div>
                </div>
                <div class="portal-topbar-divider"></div>
                <div class="portal-breadcrumb">
                    <a href="index.php"><i class="fas fa-home" style="font-size:10px;"></i> Home</a>
                    <i class="fas fa-chevron-right" style="font-size:9px;"></i>
                    <span class="cur">Campus Login</span>
                </div>
            </div>
            <div class="portal-topbar-right">
                <button class="portal-action-btn outline" onclick="reloadPortal()" title="Refresh Portal">
                    <i class="fas fa-rotate-right"></i>
                    <span>Refresh</span>
                </button>
                <a href="<?php echo htmlspecialchars($campus_url); ?>" target="_blank" rel="noopener" class="portal-action-btn gold">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    <span>Open Full Screen</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- STATUS BAR -->
<div class="portal-status-bar">
    <div class="container">
        <div class="portal-status-inner">
            <div class="portal-status-left">
                <div class="portal-status-pill">
                    <i class="fas fa-circle-check" style="color:var(--accent-color);font-size:11px;"></i>
                    Official EduPortal Login
                </div>
                <?php if (stripos($campus_url, 'https://') === 0): ?>
                <div class="portal-status-pill">
                    <i class="fas fa-shield-halved" style="color:var(--primary-color);font-size:11px;"></i>
                    Encrypted (HTTPS)
                </div>
                <?php endif; ?>
            </div>
            <div class="portal-status-info">
                <i class="fas fa-circle-info"></i>
                Powered by EduPortal &mdash; Official <?php echo htmlspecialchars(SITE_NAME); ?> Student Management System
            </div>
        </div>
    </div>
</div>

<!-- IFRAME WRAPPER -->
<div class="portal-frame-wrap">
    <!-- Loading Overlay -->
    <div class="portal-loading" id="portalLoading">
        <div class="portal-spinner"></div>
        <div class="portal-loading-text">
            <strong>Loading Campus Portal</strong>
            <span>Please wait while we connect you securely…</span>
        </div>
    </div>
    <iframe
        id="portalFrame"
        src="<?php echo htmlspecialchars($campus_url); ?>"
        title="IQRA Campus Portal"
        allow="fullscreen"
        onload="hideLoader()">
    </iframe>
</div>

<!-- FOOTER INFO BAR -->
<div class="portal-footer-bar">
    <div class="container">
        <div class="portal-footer-inner">
            <div class="portal-footer-left">
                <i class="fas fa-lock"></i>
                <span>This portal is an official service of <strong><?php echo htmlspecialchars(SITE_NAME); ?></strong> — your data is safe and secure.</span>
            </div>
            <div class="portal-footer-right">
                <a href="contact.php" class="portal-footer-link">
                    <i class="fas fa-headset"></i> Need Help?
                </a>
                <a href="index.php" class="portal-footer-link">
                    <i class="fas fa-house"></i> Back to Website
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function hideLoader() {
    const loader = document.getElementById('portalLoading');
    if (loader) loader.classList.add('hidden');
}

function reloadPortal() {
    const frame = document.getElementById('portalFrame');
    const loader = document.getElementById('portalLoading');
    if (loader) loader.classList.remove('hidden');
    frame.src = frame.src;
}

/* Auto-hide loader after 12s as fallback (some sites block onload) */
setTimeout(() => {
    const loader = document.getElementById('portalLoading');
    if (loader && !loader.classList.contains('hidden')) {
        loader.classList.add('hidden');
    }
}, 12000);
</script>

<?php include 'includes/footer.php'; ?>
